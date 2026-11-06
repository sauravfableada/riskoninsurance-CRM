(function () {
    const mapping = { insurance_company: 'company_id', policy_type: 'policy_type_id', customer_phone: 'customer_phone', broker_name: 'agent_name', broker_contact: 'broker_contact', nominee_name: 'nominee_name', nominee_contact: 'nominee_contact', agent_code: 'agent_code', sum_assured: 'sum_assured', policy_number: 'policy_number', start_date: 'start_date', end_date: 'end_date', premium_amount: 'premium' };
    window.renderPolicySuggestions = function (data) {
        document.querySelectorAll('.ai-field-suggestions').forEach(el => el.remove());
        Object.entries(mapping).forEach(function ([key, id]) {
            const field = document.getElementById(id);
            if (!field) return;
            const options = data.suggestions?.[key] || [];
            const missing = !data.fields?.[key]?.value;
            if (!options.length && !missing) return;
            const box = document.createElement('div');
            box.className = 'ai-field-suggestions';
            const label = document.createElement('div');
            label.className = 'ai-suggestion-label';
            label.textContent = options.length > 1 ? options.length + ' possible values — select the correct one' : (options.length ? 'Found in PDF — click to use' : 'Not clearly found in the PDF. Please enter or select manually.');
            box.appendChild(label);
            options.forEach(function (candidate) {
                const button = document.createElement('button');
                button.type = 'button'; button.className = 'ai-suggestion-choice';
                button.textContent = String(candidate.label || candidate.value) + (candidate.requires_creation ? ' · Add' : '');
                button.title = candidate.source_text || 'Extracted candidate; please verify against the PDF.';
                const currentValue = field.tagName === 'SELECT' ? String(candidate.id || '') : String(candidate.value);
                button.classList.toggle('is-selected', !!currentValue && String(field.value) === currentValue);
                button.addEventListener('click', function () {
                    if (field.tagName === 'SELECT') {
                        if (!candidate.id) {
                            const addButton = field.closest('.ai-field-wrap')?.querySelector('.ai-quick-add');
                            if (addButton) {
                                field.dataset.suggestedName = candidate.value;
                                addButton.click();
                            }
                            return;
                        }
                        if (!Array.from(field.options).some(o => String(o.value) === String(candidate.id))) field.add(new Option(candidate.label || candidate.value, candidate.id));
                        $(field).val(candidate.id).trigger('change');
                    } else {
                        let value = String(candidate.value);
                        if (field.type === 'number') {
                            value = value.replace(/,/g, '').replace(/^(?:₹|Rs\.?|INR)\s*/i, '').trim();
                            const amount = value.match(/^(\d+(?:\.\d+)?)\s*(lakh?s?|lacs?|crores?)?$/i);
                            if (!amount) { if (window.toastr) toastr.info('Please verify and enter this amount manually.'); return; }
                            value = String(Number(amount[1]) * (/^crore/i.test(amount[2] || '') ? 10000000 : amount[2] ? 100000 : 1));
                        }
                        field.value = value;
                        $(field).trigger('input').trigger('change');
                    }
                    box.querySelectorAll('button').forEach(b => b.classList.remove('is-selected'));
                    button.classList.add('is-selected');
                });
                box.appendChild(button);
                if (candidate.source_text) {
                    const evidence = document.createElement('small');
                    evidence.className = 'ai-suggestion-evidence';
                    evidence.textContent = candidate.source_text.slice(0, 200);
                    box.appendChild(evidence);
                }
            });
            field.closest('.ai-field-wrap')?.appendChild(box);
        });
    };
})();
