(function () {
    const config = window.aiRenewalConfig;
    if (!config) return;
    const form = document.getElementById('policy_ai_form');
    const saveButton = document.getElementById('btn_save_policy');
    let busy = false;
    ['user_id','family_group_id','staff_id'].forEach(id => {
        const field = document.getElementById(id);
        if (field) { field.disabled = true; field.closest('.ai-field-wrap')?.querySelectorAll('.ai-quick-add').forEach(b => b.remove()); }
    });
    saveButton.textContent = 'Review & save renewal';
    const comparison = document.getElementById('aiRenewalComparison');
    document.getElementById('pdf_file_input').multiple = false;
    function displayValue(row, value, old) {
        if (row.key === 'company_id' && old) return config.companyName || value || '--';
        if (row.key === 'policy_type_id' && old) return config.typeName || value || '--';
        if (['company_id','policy_type_id'].includes(row.key)) return Array.from(document.getElementById(row.key).options).find(o => String(o.value) === String(value))?.text || value || '--';
        return value === null || value === '' || value === undefined ? '--' : String(value);
    }
    function render(review) {
        comparison.replaceChildren();
        const scroll = document.createElement('div'); scroll.className = 'table-responsive';
        const table = document.createElement('table'); table.className = 'table table-sm align-middle';
        const head = table.createTHead().insertRow();
        ['Field','Previous policy','Reviewed renewal','Comparison'].forEach(label => { const cell = document.createElement('th'); cell.textContent = label; head.append(cell); });
        const body = table.createTBody();
        review.rows.forEach(row => {
            const tr = body.insertRow();
            [row.label,displayValue(row,row.old,true),displayValue(row,row.new,false),row.status.replace(/_/g,' ')].forEach((value,index) => {
                const cell = tr.insertCell(); cell.textContent = value;
                if (index === 3) cell.className = row.status === 'matching' ? 'text-success' : row.status === 'missing' ? 'text-danger' : 'text-warning';
            });
            if (row.status === 'carried_forward') {
                const clear = document.createElement('button'); clear.type='button';clear.className='btn btn-sm btn-outline-secondary ms-2';clear.textContent='Clear for new term';
                clear.addEventListener('click',()=>{
                    const hidden=document.createElement('input');hidden.type='hidden';hidden.name='clear_fields[]';hidden.value=row.key;form.append(hidden);
                    const field=form.elements[row.key];if (field && 'value' in field) field.value='';
                    clear.disabled=true;clear.textContent='Will clear on next review';
                });
                tr.lastElementChild.append(clear);
            }
        });
        scroll.append(table); comparison.append(scroll);
        [...review.blocked || [], ...review.issues || []].forEach(message => { const note = document.createElement('p'); note.className = 'small text-danger mb-1'; note.textContent = message; comparison.append(note); });
    }
    window.fillAiRenewal = function (data) {
        if (!document.getElementById('covered_members_input').value) document.getElementById('covered_members_input').value = '[]';
        if (!document.getElementById('addon_covers_input').value) document.getElementById('addon_covers_input').value = '[]';
        document.getElementById('renewalHolderName').value = data.fields?.holder_name?.value || '';
        render({ rows: Object.entries(config.source).map(([key,old]) => ({ key,label:key.replace(/_/g,' '),old,new:form.elements[key]?.value || null,status:'review' })),issues:[],blocked:[] });
    };
    async function post(url, data) {
        const response = await fetch(url,{ method:'POST',headers:{'Accept':'application/json','X-CSRF-TOKEN':form.querySelector('[name="_token"]').value},body:data });
        const result = await response.json();
        if (!response.ok || !result.status) throw new Error(Object.values(result.errors || {}).flat().join('\n') || result.message || 'Renewal could not be processed.');
        return result;
    }
    window.prepareAiRenewal = async function () {
        if (busy) return;
        busy = true; saveButton.disabled = true;
        try {
            const values = new FormData(form);
            const result = await post(config.reviewUrl,values);
            render(result.review);
            comparison.scrollIntoView({block:'center',behavior:'smooth'});
            if (result.review.blocked.length) { await Swal.fire({icon:'error',title:'Renewal blocked',text:result.review.blocked.join(' ')}); return; }
            const confirmation = document.createElement('div'); confirmation.className = 'text-start small';
            const compared = comparison.querySelector('.table-responsive').cloneNode(true);
            compared.querySelectorAll('button').forEach(button=>button.remove());
            compared.style.maxHeight = '260px'; compared.style.overflowY = 'auto';
            confirmation.append(compared);
            const note = document.createElement('p'); note.textContent = 'Previous policy information and documents will remain unchanged. This creates a new linked term for the same customer.'; confirmation.append(note);
            const choice = await Swal.fire({title:'Confirm reviewed renewal',html:confirmation,width:850,showCancelButton:true,confirmButtonText:'Save linked renewal',confirmButtonColor:'#153858'});
            if (!choice.isConfirmed) return;
            values.set('renewal_confirmed','1');
            const saved = await post(config.saveUrl,values);
            await Swal.fire({icon:'success',title:'Renewal saved',text:saved.message,confirmButtonText:'View renewed policy',confirmButtonColor:'#153858'});
            window.location.href = saved.redirect_url;
        } catch (error) { await Swal.fire({icon:'error',title:'Please review the renewal',text:error.message,confirmButtonColor:'#153858'}); }
        finally { busy = false; saveButton.disabled = false; }
    };
})();
