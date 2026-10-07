import './bootstrap';

import Alpine from 'alpinejs';
import QRCode from 'qrcode';
import './i18n';

window.Alpine = Alpine;

Alpine.start();

window.labelQrReady = Promise.all(
    Array.from(document.querySelectorAll('[data-qr-value]'), (element) => {
        const value = element.dataset.qrValue;
        if (!value) return Promise.resolve();
        return QRCode.toCanvas(element, value, {
            errorCorrectionLevel: 'M',
            margin: 1,
            width: 220,
            color: { dark: '#000000', light: '#ffffff' },
        });
    }),
);
