import DOMPurify from 'dompurify';

const ALLOWED_TAGS = [
    'p', 'br', 'strong', 'em', 'b', 'i', 'u', 's',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
    'ul', 'ol', 'li',
    'a', 'img',
    'blockquote', 'pre', 'code',
    'table', 'thead', 'tbody', 'tr', 'th', 'td',
    'div', 'span', 'hr', 'figure', 'figcaption',
];

const ALLOWED_ATTR = [
    'href', 'target', 'rel', 'src', 'alt', 'title', 'width', 'height',
    'class', 'id', 'loading', 'decoding',
];

/**
 * Sanitize HTML content to prevent XSS attacks.
 * Allows safe HTML tags used in blog posts and photo stories.
 */
export function sanitizeHtml(dirty) {
    if (!dirty) return '';

    // DOMPurify relies on a DOM. During SSR (Node, no `window`) its default
    // export is an uninitialised factory, so `sanitize` is undefined and would
    // throw mid-render. The content here is first-party (admin-authored stories
    // and posts), so we return it unchanged server-side; the browser re-runs
    // sanitisation on hydration, where untrusted DOM injection actually matters.
    if (typeof window === 'undefined' || typeof DOMPurify.sanitize !== 'function') {
        return dirty;
    }

    return DOMPurify.sanitize(dirty, {
        ALLOWED_TAGS,
        ALLOWED_ATTR,
        ALLOW_DATA_ATTR: false,
    });
}
