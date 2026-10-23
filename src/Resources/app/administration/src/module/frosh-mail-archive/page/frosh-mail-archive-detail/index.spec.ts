import { afterEach, describe, expect, it, vi } from 'vitest';
import {
    flushPromises,
    mountShopwareComponent,
} from '@friendsofshopware/vitest-shopware-admin-bridge/test-utils';
import './index';

async function mountDetail(fetchEmlHeaders: ReturnType<typeof vi.fn>) {
    return mountShopwareComponent('frosh-mail-archive-detail', {
        props: { archiveId: 'first' },
        global: {
            provide: {
                froshMailArchiveService: { fetchEmlHeaders },
                repositoryFactory: {
                    create: () => ({
                        get: vi.fn(async (id: string) => ({
                            id,
                            subject: `Message ${id}`,
                            sender: {},
                            receiver: {},
                            attachments: [],
                            createdAt: '2026-10-23T10:00:00+00:00',
                        })),
                    }),
                },
            },
            stubs: {
                'frosh-mail-resend-history': true,
                'sw-button-process': true,
                'sw-alert': true,
                'sw-description-list': { template: '<dl><slot /></dl>' },
                'router-link': true,
                'sw-card': {
                    template: '<div><slot /><slot name="grid" /></div>',
                },
                'sw-card-section': true,
                'sw-context-menu-item': true,
                'sw-data-grid': true,
                'sw-tagged-field': true,
                'sw-button': true,
            },
        },
    });
}

describe('mail detail header loading', () => {
    const wrappers: Awaited<ReturnType<typeof mountDetail>>[] = [];

    afterEach(() => {
        wrappers.splice(0).forEach((wrapper) => wrapper.unmount());
    });

    it('clears old recipients while loading the next message', async () => {
        const next = Promise.withResolvers<Record<string, string>>();
        const fetchHeaders = vi
            .fn()
            .mockResolvedValueOnce({ Cc: 'first@example.com' })
            .mockReturnValueOnce(next.promise);
        const wrapper = await mountDetail(fetchHeaders);
        wrappers.push(wrapper);
        await flushPromises();
        expect(wrapper.text()).toContain('first@example.com');

        await wrapper.setProps({ archiveId: 'second' });
        await flushPromises();
        expect(wrapper.text()).not.toContain('first@example.com');

        next.resolve({ Bcc: 'second@example.com' });
        await flushPromises();
        expect(wrapper.text()).toContain('second@example.com');
    });

    it('ignores an older response that finishes after the current request', async () => {
        const first = Promise.withResolvers<Record<string, string>>();
        const fetchHeaders = vi
            .fn()
            .mockReturnValueOnce(first.promise)
            .mockResolvedValueOnce({ Cc: 'second@example.com' });
        const wrapper = await mountDetail(fetchHeaders);
        wrappers.push(wrapper);

        await wrapper.setProps({ archiveId: 'second' });
        await flushPromises();
        first.resolve({ Cc: 'first@example.com' });
        await flushPromises();

        expect(wrapper.text()).toContain('Message second');
        expect(wrapper.text()).toContain('second@example.com');
        expect(wrapper.text()).not.toContain('first@example.com');
    });

    it('keeps recipients empty when the next header load fails', async () => {
        const fetchHeaders = vi
            .fn()
            .mockResolvedValueOnce({ Cc: 'first@example.com' })
            .mockRejectedValueOnce(new Error('Cannot load headers'));
        const wrapper = await mountDetail(fetchHeaders);
        wrappers.push(wrapper);
        await flushPromises();

        await wrapper.setProps({ archiveId: 'second' });
        await flushPromises();

        expect(wrapper.text()).toContain('Message second');
        expect(wrapper.text()).not.toContain('first@example.com');
    });

    it('does not clear current recipients when an older request fails', async () => {
        const first = Promise.withResolvers<Record<string, string>>();
        const fetchHeaders = vi
            .fn()
            .mockReturnValueOnce(first.promise)
            .mockResolvedValueOnce({ Cc: 'second@example.com' });
        const wrapper = await mountDetail(fetchHeaders);
        wrappers.push(wrapper);

        await wrapper.setProps({ archiveId: 'second' });
        await flushPromises();
        first.reject(new Error('Previous request failed'));
        await flushPromises();

        expect(wrapper.text()).toContain('second@example.com');
    });

    it('ignores the original request after navigating back to the same message', async () => {
        const original = Promise.withResolvers<Record<string, string>>();
        const fetchHeaders = vi
            .fn()
            .mockReturnValueOnce(original.promise)
            .mockResolvedValueOnce({ Cc: 'second@example.com' })
            .mockResolvedValueOnce({ Cc: 'current@example.com' });
        const wrapper = await mountDetail(fetchHeaders);
        wrappers.push(wrapper);

        await wrapper.setProps({ archiveId: 'second' });
        await flushPromises();
        await wrapper.setProps({ archiveId: 'first' });
        await flushPromises();
        original.resolve({ Cc: 'outdated@example.com' });
        await flushPromises();

        expect(wrapper.text()).toContain('Message first');
        expect(wrapper.text()).toContain('current@example.com');
        expect(wrapper.text()).not.toContain('outdated@example.com');
    });
});
