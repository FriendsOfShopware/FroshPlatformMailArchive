import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    flushPromises,
    mountShopwareComponent,
} from '@friendsofshopware/vitest-shopware-admin-bridge/test-utils';
import enGB from '../../snippet/en-GB.json';
import './index';

const customerTab = enGB['frosh-mail-archive'].customerTab;

function createSearch(total = 0) {
    return vi.fn(async () => ({ total, length: total }));
}

async function mountTab(
    email: string | null,
    search: ReturnType<typeof vi.fn> = createSearch()
) {
    const wrapper = await mountShopwareComponent(
        'frosh-mail-archive-customer-tab',
        {
            props: { customer: { id: 'customer', email } },
            global: {
                provide: {
                    repositoryFactory: { create: () => ({ search }) },
                },
                stubs: {
                    'sw-card': { template: '<div><slot /></div>' },
                    'sw-button': {
                        emits: ['click'],
                        template:
                            '<button @click="$emit(\'click\')"><slot /></button>',
                    },
                    'sw-alert': { template: '<div><slot /></div>' },
                    'sw-entity-listing': {
                        name: 'sw-entity-listing',
                        props: ['columns', 'disableDataFetching'],
                        template: '<div />',
                    },
                    'sw-empty-state': true,
                    'sw-context-menu-item': true,
                    'router-link': true,
                },
            },
        }
    );
    await flushPromises();

    return { wrapper, search };
}

function lastCriteria(search: ReturnType<typeof vi.fn>) {
    return (search.mock.calls as unknown[][]).at(-1)?.[0] as {
        page: number;
        limit: number;
        filters: unknown[];
        sortings: unknown[];
    };
}

function customerFilter(email: string) {
    return [
        {
            type: 'multi',
            operator: 'OR',
            queries: [
                { type: 'equals', field: 'customerId', value: 'customer' },
                { type: 'contains', field: 'receiver', value: email },
            ],
        },
    ];
}

describe('customer mail archive tab', () => {
    beforeEach(() => {
        Shopware.Locale.extend('en-GB', enGB);
    });

    it('finds mails by customer id or by the customer email and shows the newest first', async () => {
        const { search } = await mountTab('a@b.de');

        const criteria = lastCriteria(search);
        expect(criteria.filters).toEqual(customerFilter('"a@b.de"'));
        expect(criteria.sortings).toEqual([
            { field: 'createdAt', order: 'DESC', naturalSorting: false },
        ]);
        expect(criteria.page).toBe(1);
    });

    it('escapes quotes in the email like the stored receiver JSON', async () => {
        const { search } = await mountTab('o"brien@x.de');

        expect(lastCriteria(search).filters).toEqual(
            customerFilter('"o\\"brien@x.de"')
        );
    });

    it('only searches by customer id when the customer has no email', async () => {
        const { search } = await mountTab(null);

        expect(lastCriteria(search).filters).toEqual([
            {
                type: 'multi',
                operator: 'OR',
                queries: [
                    { type: 'equals', field: 'customerId', value: 'customer' },
                ],
            },
        ]);
    });

    it('loads the requested page when the pagination changes', async () => {
        const { wrapper, search } = await mountTab('a@b.de');

        (wrapper.vm as any).onPageChange({ page: 3, limit: 25 });
        await flushPromises();

        const criteria = lastCriteria(search);
        expect(criteria.page).toBe(3);
        expect(criteria.limit).toBe(25);
    });

    it('keeps fetching and sorting out of the listing so a page change sends a single request', async () => {
        const { wrapper } = await mountTab('a@b.de', createSearch(1));

        const listing = wrapper.findComponent({ name: 'sw-entity-listing' });
        expect(listing.props('disableDataFetching')).toBe(true);
        expect(
            (listing.props('columns') as { sortable: boolean }[]).every(
                (column) => column.sortable === false
            )
        ).toBe(true);
    });

    it('starts at the first page again when the customer email changes', async () => {
        const { wrapper, search } = await mountTab('a@b.de');
        (wrapper.vm as any).onPageChange({ page: 3, limit: 10 });
        await flushPromises();

        await wrapper.setProps({
            customer: { id: 'customer', email: 'c@d.de' },
        });
        await flushPromises();

        const criteria = lastCriteria(search);
        expect(criteria.page).toBe(1);
        expect(criteria.filters).toEqual(customerFilter('"c@d.de"'));
    });

    it('reloads when another customer with the same email is opened', async () => {
        const { wrapper, search } = await mountTab('a@b.de');
        const callsBefore = search.mock.calls.length;

        await wrapper.setProps({
            customer: { id: 'other', email: 'a@b.de' },
        });
        await flushPromises();

        expect(search.mock.calls.length).toBe(callsBefore + 1);
        expect(lastCriteria(search).filters).toEqual([
            {
                type: 'multi',
                operator: 'OR',
                queries: [
                    { type: 'equals', field: 'customerId', value: 'other' },
                    { type: 'contains', field: 'receiver', value: '"a@b.de"' },
                ],
            },
        ]);
    });

    it('shows an error instead of an empty card when the first search fails', async () => {
        const search = vi.fn().mockRejectedValue(new Error('offline'));
        const { wrapper } = await mountTab('a@b.de', search);

        expect(wrapper.text()).toContain(customerTab.loadError);
        expect(wrapper.text()).toContain(customerTab.retry);
    });

    it('drops the previous results and shows the error when a later search fails', async () => {
        const search = vi
            .fn()
            .mockResolvedValueOnce({ total: 1, length: 1 })
            .mockRejectedValue(new Error('offline'));
        const { wrapper } = await mountTab('a@b.de', search);
        expect(
            wrapper.findComponent({ name: 'sw-entity-listing' }).exists()
        ).toBe(true);

        (wrapper.vm as any).onPageChange({ page: 2, limit: 10 });
        await flushPromises();

        expect(
            wrapper.findComponent({ name: 'sw-entity-listing' }).exists()
        ).toBe(false);
        expect(wrapper.text()).toContain(customerTab.loadError);
    });

    it('searches again and clears the error when the retry button is clicked', async () => {
        const search = vi
            .fn()
            .mockRejectedValueOnce(new Error('offline'))
            .mockResolvedValue({ total: 0, length: 0 });
        const { wrapper } = await mountTab('a@b.de', search);

        await wrapper.find('button').trigger('click');
        await flushPromises();

        expect(search).toHaveBeenCalledTimes(2);
        expect(wrapper.text()).not.toContain(customerTab.loadError);
    });
});
