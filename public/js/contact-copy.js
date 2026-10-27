(function () {
    'use strict';

    const whatsappPattern = /whats?app/i;
    const phonePattern = /(phone|mobile|contact)/i;

    function fieldKey(field) {
        return `${field.name || ''} ${field.id || ''}`;
    }

    function findPhoneField(whatsappField) {
        const scope = whatsappField.form || whatsappField.closest('form') || document;
        const fields = Array.from(scope.querySelectorAll('input[type="text"], input[type="tel"], input[type="number"]'));
        const whatsappIndex = fields.indexOf(whatsappField);

        return fields
            .filter(field => field !== whatsappField && phonePattern.test(fieldKey(field)) && !whatsappPattern.test(fieldKey(field)))
            .sort((first, second) => Math.abs(fields.indexOf(first) - whatsappIndex) - Math.abs(fields.indexOf(second) - whatsappIndex))[0] || null;
    }

    function addCopyButton(whatsappField) {
        if (fieldKey(whatsappField).includes('country_code')) {
            return;
        }

        if (whatsappField.dataset.contactCopyReady === 'true') {
            return;
        }

        const phoneField = findPhoneField(whatsappField);
        if (!phoneField) {
            return;
        }

        whatsappField.dataset.contactCopyReady = 'true';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline-secondary crm-copy-contact-btn';
        button.title = 'Copy mobile number to WhatsApp';
        button.setAttribute('aria-label', 'Copy mobile number to WhatsApp');
        button.innerHTML = '<i class="bi bi-copy"></i><span>Copy mobile</span>';

        button.addEventListener('click', function () {
            whatsappField.value = phoneField.value;

            const form = whatsappField.form || whatsappField.closest('form');
            const phoneCountryCode = form?.querySelector('[name="country_code"]');
            const whatsappCountryCode = form?.querySelector('[name="whatsapp_country_code"]');

            if (phoneCountryCode && whatsappCountryCode) {
                whatsappCountryCode.value = phoneCountryCode.value || '+91';
                whatsappCountryCode.dispatchEvent(new Event('input', { bubbles: true }));
                whatsappCountryCode.dispatchEvent(new Event('change', { bubbles: true }));
            }

            whatsappField.dispatchEvent(new Event('input', { bubbles: true }));
            whatsappField.dispatchEvent(new Event('change', { bubbles: true }));
            whatsappField.focus();
        });

        const wrapper = document.createElement('div');
        wrapper.className = 'crm-whatsapp-copy-wrap';
        whatsappField.parentNode.insertBefore(wrapper, whatsappField);
        wrapper.appendChild(whatsappField);
        wrapper.appendChild(button);
    }

    function enhanceContactFields(root) {
        const scope = root && root.querySelectorAll ? root : document;
        scope.querySelectorAll('input').forEach(function (field) {
            if (whatsappPattern.test(fieldKey(field))) {
                addCopyButton(field);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        enhanceContactFields(document);

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        enhanceContactFields(node);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    });
})();
