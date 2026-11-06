(() => {
    if (window.crmWhatsappPreviewReady) return;
    window.crmWhatsappPreviewReady = true;
    document.addEventListener('click', async event => {
        const button = event.target.closest('.policy-whatsapp-reminder, .celebration-whatsapp-wish');
        if (!button || button.disabled) return;
        event.preventDefault(); event.stopPropagation();
        button.disabled = true;
        let overview = null;
        try {
            let draft;
            if (button.dataset.whatsappDraft) {
                draft = JSON.parse(button.dataset.whatsappDraft);
            } else {
                const response = await fetch(button.dataset.draftUrl, {headers:{Accept:'application/json'}, cache:'no-store'});
                draft = await response.json();
                if (!response.ok) throw new Error(Object.values(draft.errors || {}).flat().join(' ') || draft.message || 'Unable to prepare message.');
            }
            const openOverview = document.querySelector('#todaysOverviewModal.show');
            if (openOverview) {
                overview = bootstrap.Modal.getInstance(openOverview);
                await new Promise(resolve => {
                    openOverview.addEventListener('hidden.bs.modal', resolve, {once:true});
                    overview.hide();
                });
            }
            const recipient = document.createElement('div');
            recipient.className = 'wa-preview-recipient';
            const avatar = document.createElement('span');
            avatar.className = 'wa-preview-avatar';
            avatar.textContent = (draft.holder || 'Customer').trim().charAt(0).toUpperCase();
            const details = document.createElement('div');
            const label = document.createElement('span');
            label.className = 'wa-preview-recipient-label';
            label.textContent = 'RECIPIENT';
            const name = document.createElement('strong');
            name.textContent = draft.holder;
            const phone = document.createElement('span');
            phone.className = 'wa-preview-phone';
            phone.textContent = '+' + draft.phone;
            details.append(label, name, phone);
            recipient.append(avatar, details);
            await Swal.fire({
                title: button.dataset.whatsappDraft ? 'Send a little joy' : 'Renewal reminder',
                html: recipient,
                inputLabel: 'MESSAGE PREVIEW',
                input: 'textarea', inputValue: draft.message,
                inputAttributes: {'aria-label':'Edit WhatsApp message', maxlength:'4000', rows:'10'},
                customClass: {container:'wa-preview-container', popup:'wa-preview-popup', title:'wa-preview-title', htmlContainer:'wa-preview-content', input:'wa-preview-editor', inputLabel:'wa-preview-label', actions:'wa-preview-actions', confirmButton:'wa-preview-confirm', cancelButton:'wa-preview-cancel', closeButton:'wa-preview-close'},
                width:560, showCloseButton:true, showCancelButton:true, reverseButtons:true,
                confirmButtonText:'<i class="bi bi-whatsapp" aria-hidden="true"></i> Open WhatsApp', cancelButtonText:'Cancel', buttonsStyling:false,
                didOpen: popup => {
                    const headingIcon = document.createElement('span');
                    headingIcon.className = 'wa-preview-heading-icon';
                    headingIcon.innerHTML = '<i class="bi bi-whatsapp" aria-hidden="true"></i>';
                    const header = document.createElement('div');
                    header.className = 'wa-preview-header';
                    header.append(headingIcon, Swal.getTitle(), Swal.getCloseButton());
                    popup.prepend(header);
                    const editor = Swal.getInput();
                    const helper = document.createElement('div');
                    helper.className = 'wa-preview-helper';
                    const tip = document.createElement('span');
                    tip.textContent = 'Make it personal. You can edit the message above.';
                    const count = document.createElement('span');
                    const updateCount = () => { count.textContent = editor.value.length + ' / 4000'; };
                    updateCount();
                    editor.addEventListener('input', updateCount);
                    helper.append(tip, count);
                    editor.after(helper);
                    const note = document.createElement('p');
                    note.className = 'wa-preview-note';
                    note.textContent = 'Your message opens in WhatsApp Web. Press Send there to deliver it.';
                    helper.after(note);
                },
                inputValidator: value => !value.trim() ? 'Enter a message.' : undefined,
                preConfirm: message => {
                    const url = 'https://web.whatsapp.com/send?phone=' + encodeURIComponent(draft.phone) + '&text=' + encodeURIComponent(message);
                    const chat = window.open(url, '_blank');
                    if (!chat) { Swal.showValidationMessage('Allow pop-ups for this CRM to open WhatsApp Web.'); return false; }
                    chat.opener = null;
                    return true;
                }
            });
        } catch (error) {
            await Swal.fire({icon:'info', title:'WhatsApp message', text:error.message});
        } finally {
            if (overview) overview.show();
            button.disabled = false;
        }
    });
})();
