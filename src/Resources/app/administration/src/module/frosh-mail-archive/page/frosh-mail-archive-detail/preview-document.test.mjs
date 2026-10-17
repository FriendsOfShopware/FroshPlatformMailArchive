import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
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

        assert.deepEqual(sanitizerConfiguration.FORBID_TAGS, [
            'base',
            'embed',
            'form',
            'iframe',
            'meta',
            'object',
        ]);
        assert.deepEqual(sanitizerConfiguration.FORBID_ATTR, ['srcset']);
        assert.ok(
            document.indexOf('Content-Security-Policy') <
                document.indexOf('tracker.example')
        );
        assert.match(document, /default-src 'none'/);
        assert.match(document, /navigate-to 'none'/);
    });
});
