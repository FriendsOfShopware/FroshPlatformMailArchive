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

    it('keeps styles and body attributes of a full mail', () => {
        const dataUrl = createPreviewDataUrl(
            '<!doctype html><html><head><meta http-equiv="refresh" content="0; url=https://tracker.example">' +
                '<style>.badge { display: inline-block; }</style></head>' +
                '<body style="background-color: #eeeeee" onload="alert(1)">' +
                '<a class="badge" style="display: inline-block; border-radius: 4px">Badge</a>' +
                '<script>alert(1)</script></body></html>',
            Shopware.Helper.SanitizerHelper.sanitize
        );
        const preview = new DOMParser().parseFromString(
            Buffer.from(dataUrl.split(',')[1], 'base64').toString('utf8'),
            'text/html'
        );
        const policies = preview.querySelectorAll('meta');

        expect(policies).toHaveLength(1);
        expect(policies[0].getAttribute('content')).toContain(
            "default-src 'none'"
        );
        expect(preview.head.contains(policies[0])).toBe(true);
        expect(preview.querySelector('style').textContent).toContain(
            'display: inline-block'
        );
        expect(preview.body.getAttribute('style')).toContain(
            'background-color'
        );
        expect(preview.querySelector('.badge').getAttribute('style')).toContain(
            'border-radius'
        );
        expect(preview.body.hasAttribute('onload')).toBe(false);
        expect(preview.querySelector('script')).toBeNull();
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
