import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    flushPromises,
    mountShopwareComponent,
} from '@friendsofshopware/vitest-shopware-admin-bridge/test-utils';
import enGB from '../../snippet/en-GB.json';
import './index';

function createSearch(total = 0) {
    return vi.fn(async () => ({ total, length: total }));
}

async function mountTab(email: string | null, search = createSearch()) {
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
                    'sw-button': true,
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

function lastCriteria(search: ReturnType<typeof createSearch>) {
    return (search.mock.calls as unknown[][]).at(-1)?.[0] as {
        page: number;
        limit: number;
        filters: unknown[];
        sortings: unknown[];
    };
}

describe('customer mail archive tab', () => {
    beforeEach(() => {
        Shopware.Locale.extend('en-GB', enGB);
    });

    it('filters the archive by the customer email and shows the newest mails first', async () => {
        const { search } = await mountTab('a@b.de');

        const criteria = lastCriteria(search);
        expect(criteria.filters).toEqual([
            { type: 'contains', field: 'receiver', value: '"a@b.de"' },
        ]);
        expect(criteria.sortings).toEqual([
            { field: 'createdAt', order: 'DESC', naturalSorting: false },
        ]);
        expect(criteria.page).toBe(1);
    });

    it('escapes quotes in the email like the stored receiver JSON', async () => {
        const { search } = await mountTab('o"brien@x.de');

        expect(lastCriteria(search).filters).toEqual([
            { type: 'contains', field: 'receiver', value: '"o\\"brien@x.de"' },
        ]);
    });

    it('does not search the archive when the customer has no email', async () => {
        const { search } = await mountTab(null);

        expect(search).not.toHaveBeenCalled();
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
        expect(criteria.filters).toEqual([
            { type: 'contains', field: 'receiver', value: '"c@d.de"' },
        ]);
    });
});
