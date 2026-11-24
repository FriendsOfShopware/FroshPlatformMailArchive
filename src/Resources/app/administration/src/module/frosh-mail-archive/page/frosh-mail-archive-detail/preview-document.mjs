function toOrigin(source) {
    try {
        const url = new URL(source);

        return ['http:', 'https:'].includes(url.protocol) ? url.origin : null;
    } catch {
        return null;
    }
}

/**
 * @param {string} html
 * @param {Function} sanitize
 * @param {string[]} imageSources origins the shop itself serves images from, everything else stays blocked
 */
export function createPreviewDataUrl(html, sanitize, imageSources = []) {
    const imageOrigins = [...new Set(imageSources.map(toOrigin))].filter(
        Boolean
    );
    const policy = [
        "default-src 'none'",
        ['img-src data: cid:', ...imageOrigins].join(' '),
        "style-src 'unsafe-inline'",
        'font-src data:',
        "base-uri 'none'",
        "form-action 'none'",
        "navigate-to 'none'",
    ].join('; ');
    const content = sanitize(html, {
        FORBID_TAGS: ['base', 'embed', 'form', 'iframe', 'meta', 'object'],
        FORBID_ATTR: ['srcset'],
    });
    const preview = `<!doctype html><html><head><meta http-equiv="Content-Security-Policy" content="${policy}"></head><body>${content}</body></html>`;
    const binary = new TextEncoder().encode(preview);
    let result = '';

    binary.forEach((byte) => {
        result += String.fromCharCode(byte);
    });

    return 'data:text/html;charset=utf-8;base64,' + btoa(result);
}
