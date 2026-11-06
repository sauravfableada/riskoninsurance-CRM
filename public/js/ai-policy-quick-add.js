(function () {
    function init() {
        const form = document.getElementById('policy_ai_form');
        if (!form) return;
        const fields = {
            user_id: ['customer', 'Customer'], family_group_id: ['family', 'Family group', 'user_id', 'customer'],
            policy_type_id: ['policy-type', 'Policy type'], sub_type_id: ['sub-type', 'Policy subtype', 'policy_type_id', 'policy type'],
            segment_id: ['segment', 'Segment', 'policy_type_id', 'policy type'], company_id: ['company', 'Insurance company'],
            branch_id: ['branch', 'Branch', 'company_id', 'insurance company'], broker_id: ['broker', 'Broker']
        };
        Object.entries(fields).forEach(function ([id, config]) {
            const select = document.getElementById(id);
            const label = select?.closest('.ai-field-wrap')?.querySelector('.ai-field-label');
            if (!select || !label || select.disabled) return;
            const button = document.createElement('button');
            button.type = 'button'; button.className = 'ai-quick-add';
            button.title = 'Add ' + config[1].toLowerCase(); button.setAttribute('aria-label', button.title);
            button.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
            label.appendChild(button);
            button.addEventListener('click', async function () {
                const parentId = config[2] ? document.getElementById(config[2])?.value : null;
                if (config[2] && !parentId) { Swal.fire({ icon: 'info', text: 'Select a ' + config[3] + ' first.', confirmButtonColor: '#153858' }); return; }
                const customer = config[0] === 'customer';
                const result = await Swal.fire({
                    title: 'Add ' + config[1].toLowerCase(),
                    html: '<div class="text-start"><label for="aiNewName" class="small mb-1">Name <span class="text-danger">*</span></label><input id="aiNewName" class="form-control" maxlength="255" autocomplete="off">'
                        + (customer ? '<label for="aiNewEmail" class="small mt-3 mb-1">Email (optional)</label><input id="aiNewEmail" type="email" class="form-control"><label for="aiNewPhone" class="small mt-3 mb-1">Phone (optional)</label><input id="aiNewPhone" class="form-control" maxlength="20">' : '<label for="aiNewDescription" class="small mt-3 mb-1">Description (optional)</label><textarea id="aiNewDescription" class="form-control" rows="2" maxlength="2000"></textarea>') + '</div>',
                    showCancelButton: true, confirmButtonText: 'Add', confirmButtonColor: '#153858',
                    showLoaderOnConfirm: true, allowOutsideClick: () => !Swal.isLoading(),
                    didOpen: () => {
                        document.getElementById('aiNewName').value = select.dataset.suggestedName || '';
                        delete select.dataset.suggestedName;
                        document.getElementById('aiNewName').focus();
                    },
                    preConfirm: async function () {
                        const name = document.getElementById('aiNewName').value.trim();
                        if (!name) { Swal.showValidationMessage('Please enter a name.'); return false; }
                        const payload = { name: name, parent_id: parentId };
                        if (customer) { payload.email = document.getElementById('aiNewEmail').value; payload.phone = document.getElementById('aiNewPhone').value; }
                        else payload.description = document.getElementById('aiNewDescription').value;
                        try {
                            const response = await fetch(form.dataset.quickAddUrl + '/' + config[0], {
                                method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value }, body: JSON.stringify(payload)
                            });
                            const data = await response.json();
                            if (!response.ok || !data.status) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Could not add record.');
                            return { ...data, email: payload.email, phone: payload.phone };
                        } catch (error) { Swal.showValidationMessage(error.message); return false; }
                    }
                });
                if (!result.isConfirmed || !result.value) return;
                const record = result.value;
                let option = Array.from(select.options).find(o => String(o.value) === String(record.id));
                if (!option) { option = new Option(record.name, record.id); select.add(option); }
                if (config[0] === 'family') option.dataset.holderId = parentId;
                $(select).val(record.id).trigger('change');
                if (customer) { $('#customer_email').val(record.email || ''); $('#customer_phone').val(record.phone || ''); }
            });
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
