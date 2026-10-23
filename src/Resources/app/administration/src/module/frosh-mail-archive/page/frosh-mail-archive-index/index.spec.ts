import { describe, expect, it, vi } from 'vitest';
import {
    buildShopwareComponent,
    flushPromises,
} from '@friendsofshopware/vitest-shopware-admin-bridge/test-utils';
import './index';

const { methods } = await buildShopwareComponent('frosh-mail-archive-index');

function createPage(resendMail = vi.fn().mockResolvedValue(undefined)) {
    return {
        selectedItems: { first: {}, second: {}, third: {} },
        isLoading: false,
        froshMailArchiveService: { resendMail },
        $refs: { table: { resetSelection: vi.fn() } },
        $tc: (key: string) => key,
        createNotificationError: vi.fn(),
        getList: vi.fn().mockResolvedValue(undefined),
        bulkResendFunc(ids: string[]) {
            return methods.bulkResendFunc.call(this, ids);
        },
    };
}

describe('mail archive bulk resend', () => {
    it('waits for each message to finish before sending the next one', async () => {
        const first = Promise.withResolvers<void>();
        const second = Promise.withResolvers<void>();
        const resendMail = vi
            .fn()
            .mockReturnValueOnce(first.promise)
            .mockReturnValueOnce(second.promise)
            .mockResolvedValueOnce(undefined);
        const page = createPage(resendMail);

        const sending = methods.onBulkResendClick.call(page);
        expect(resendMail.mock.calls).toEqual([['first']]);
        expect(page.isLoading).toBe(true);

        first.resolve();
        await flushPromises();
        expect(resendMail.mock.calls).toEqual([['first'], ['second']]);
        expect(page.getList).not.toHaveBeenCalled();

        second.resolve();
        await sending;
        expect(resendMail.mock.calls).toEqual([
            ['first'],
            ['second'],
            ['third'],
        ]);
        expect(page.$refs.table.resetSelection).toHaveBeenCalledOnce();
        expect(page.getList).toHaveBeenCalledOnce();
        expect(page.isLoading).toBe(false);
    });

    it('stops on failure, reports the error and restores the listing', async () => {
        const resendMail = vi
            .fn()
            .mockResolvedValueOnce(undefined)
            .mockRejectedValueOnce(new Error('Mailer connection limit'));
        const page = createPage(resendMail);

        await methods.onBulkResendClick.call(page);

        expect(resendMail.mock.calls).toEqual([['first'], ['second']]);
        expect(page.createNotificationError).toHaveBeenCalledOnce();
        expect(page.$refs.table.resetSelection).toHaveBeenCalledOnce();
        expect(page.getList).toHaveBeenCalledOnce();
        expect(page.isLoading).toBe(false);
    });

    it('clears the loading indicator even when refreshing the list fails', async () => {
        const page = createPage();
        const failure = new Error('Cannot refresh listing');
        page.getList.mockRejectedValueOnce(failure);

        await expect(methods.onBulkResendClick.call(page)).rejects.toBe(
            failure
        );

        expect(page.isLoading).toBe(false);
    });

    it('does nothing when no messages are selected', async () => {
        const page = createPage();
        page.selectedItems = {};

        await methods.onBulkResendClick.call(page);

        expect(page.froshMailArchiveService.resendMail).not.toHaveBeenCalled();
        expect(page.getList).not.toHaveBeenCalled();
        expect(page.isLoading).toBe(false);
    });
});
