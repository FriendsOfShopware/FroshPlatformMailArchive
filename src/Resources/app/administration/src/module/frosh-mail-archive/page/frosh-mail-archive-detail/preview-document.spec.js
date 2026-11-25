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
        expect(sanitizerConfiguration.WHOLE_DOCUMENT).toBe(true);
        expect(document.indexOf('Content-Security-Policy')).toBeLessThan(
            document.indexOf('tracker.example')
        );
        expect(document).toMatch(/default-src 'none'/);
        expect(document).not.toContain('navigate-to');
    });

    it('only allows images from the given shop origins', () => {
        const dataUrl = createPreviewDataUrl(
            '<img src="https://shop.example/media/logo.png">',
            (html) => html,
            [
                'https://shop.example/en',
                'https://shop.example/de',
                'http://cdn.example:8080/media',
                'default.headless0',
                'javascript:alert(1)',
                'https://evil.example"; script-src *',
            ]
        );
        const document = Buffer.from(dataUrl.split(',')[1], 'base64').toString(
            'utf8'
        );

        expect(document).toContain(
            'img-src data: cid: https://shop.example http://cdn.example:8080;'
        );
        expect(document).not.toContain('evil.example"');
    });

    it('blocks remote images when no shop origin is known', () => {
        const dataUrl = createPreviewDataUrl('', (html) => html);
        const document = Buffer.from(dataUrl.split(',')[1], 'base64').toString(
            'utf8'
        );

        expect(document).toContain('img-src data: cid:;');
    });
});
