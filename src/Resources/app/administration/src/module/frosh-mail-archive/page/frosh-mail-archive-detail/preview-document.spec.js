import { Buffer } from 'node:buffer';
import { describe, expect, it } from 'vitest';
import { createPreviewDataUrl } from './preview-document.mjs';

describe('mail archive preview document', () => {
    it('sanitizes content and installs a restrictive policy before it', () => {
        let sanitizerConfiguration;
        const dataUrl = createPreviewDataUrl(
            '<img src="https://tracker.example/pixel">',
            (html, configuration) => {
                sanitizerConfiguration = configuration;

                return html;
            }
        );
        const document = Buffer.from(dataUrl.split(',')[1], 'base64').toString(
            'utf8'
        );

        expect(sanitizerConfiguration.FORBID_TAGS).toEqual([
            'base',
            'embed',
            'form',
            'iframe',
            'meta',
            'object',
        ]);
        expect(sanitizerConfiguration.FORBID_ATTR).toEqual(['srcset']);
        expect(document.indexOf('Content-Security-Policy')).toBeLessThan(
            document.indexOf('tracker.example')
        );
        expect(document).toMatch(/default-src 'none'/);
        expect(document).toMatch(/navigate-to 'none'/);
    });
});
