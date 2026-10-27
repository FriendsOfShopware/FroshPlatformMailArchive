import { describe, expect, it } from 'vitest';
import { getDeleteErrorDetail } from './delete-error.mjs';

describe('mail archive delete error', () => {
    it('reads the api error detail from an axios response', () => {
        const detail = getDeleteErrorDetail({
            response: {
                data: {
                    errors: [
                        {
                            detail: 'Missing privilege',
                        },
                    ],
                },
            },
        });

        expect(detail).toBe('Missing privilege');
    });

    it('joins several api error details', () => {
        const detail = getDeleteErrorDetail({
            response: {
                errors: [
                    { detail: 'First' },
                    { detail: 'Second' },
                    { detail: '' },
                ],
            },
        });

        expect(detail).toBe('First\nSecond');
    });

    it('returns null when the response has no readable detail', () => {
        expect(getDeleteErrorDetail(undefined)).toBeNull();
        expect(getDeleteErrorDetail({ response: { data: {} } })).toBeNull();
        expect(
            getDeleteErrorDetail({ errors: [{ title: 'Forbidden' }] })
        ).toBeNull();
    });
});
