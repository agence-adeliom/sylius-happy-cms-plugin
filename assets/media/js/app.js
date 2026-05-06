import { EasyMedia } from '@adeliom/easy-media-manager';
import '@adeliom/easy-media-manager/style.css';

// Intercept fetch to inject CSRF token from <meta name="csrf-token">
const originalFetch = window.fetch;
window.fetch = function (input, init = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (token) {
        init.headers = { ...init.headers, 'X-CSRF-Token': token };
    }
    return originalFetch(input, init);
};

window.EasyMedia = EasyMedia;
