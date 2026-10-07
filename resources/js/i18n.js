import english from '../../lang/en.json';
import vietnamese from '../../lang/vi.json';

const locale = document.documentElement.lang.split('-')[0];
const translations = locale === 'en' ? english : vietnamese;
const skippedElements = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA', 'CODE', 'PRE']);
const translatedAttributes = ['placeholder', 'title', 'aria-label', 'alt'];

function translated(value) {
    return Object.hasOwn(translations, value) ? translations[value] : value;
}

function translateTextNode(node) {
    const original = node.nodeValue;
    const value = original.trim();
    if (!value) return;

    const replacement = translated(value);
    if (replacement !== value) {
        const leading = original.match(/^\s*/)?.[0] ?? '';
        const trailing = original.match(/\s*$/)?.[0] ?? '';
        node.nodeValue = `${leading}${replacement}${trailing}`;
    }
}

function translateElement(root) {
    if (!(root instanceof Element) || root.closest('[data-no-translate]')) return;

    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode(node) {
            return skippedElements.has(node.parentElement?.tagName)
                ? NodeFilter.FILTER_REJECT
                : NodeFilter.FILTER_ACCEPT;
        },
    });

    while (walker.nextNode()) translateTextNode(walker.currentNode);

    const elements = [root, ...root.querySelectorAll('*')];
    for (const element of elements) {
        if (skippedElements.has(element.tagName)) continue;

        for (const attribute of translatedAttributes) {
            const value = element.getAttribute(attribute);
            if (value) element.setAttribute(attribute, translated(value));
        }

        if (['button', 'submit', 'reset'].includes(element.type) && element.value) {
            element.value = translated(element.value);
        }
    }
}

translateElement(document.body);

for (const dialog of ['alert', 'confirm']) {
    const nativeDialog = window[dialog].bind(window);
    window[dialog] = (message, ...args) => nativeDialog(
        message === undefined ? message : translated(String(message)),
        ...args,
    );
}

const observer = new MutationObserver((mutations) => {
    for (const mutation of mutations) {
        if (mutation.type === 'characterData') {
            translateTextNode(mutation.target);
            continue;
        }

        for (const node of mutation.addedNodes) {
            if (node.nodeType === Node.TEXT_NODE) {
                translateTextNode(node);
            } else if (node instanceof Element) {
                translateElement(node);
            }
        }
    }
});

observer.observe(document.body, { childList: true, characterData: true, subtree: true });
