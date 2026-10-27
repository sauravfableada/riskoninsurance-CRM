(function () {
    const cardEl = document.getElementById('chatbotCard');
    if (!cardEl) return;

    const toggleBtn = document.getElementById('chatbotToggleBtn');
    const closeBtn = document.getElementById('chatbotCloseBtn');
    const sendBtn = document.getElementById('chatbotSendBtn');
    const inputEl = document.getElementById('chatbotInput');
    const messagesEl = document.getElementById('chatbotMessages');
    const promptButtons = document.querySelectorAll('.chatbot-prompt-btn');
    const inputWrapper = document.getElementById('chatbotInputWrapper');

    const menuData = {
        main: [
            { label: 'Leads', target: 'leads' },
            { label: 'Customers', target: 'customers' },
            { label: 'Followups', target: 'followups' },
            { label: 'Tasks', target: 'tasks' },
            { label: 'Meetings', target: 'meetings' },
            { label: 'Staff', target: 'staff' }
        ],
        leads: [
            { label: 'Today', value: 'fetch_leads_today' },
            { label: 'This Week', value: 'fetch_leads_this_week' },
            { label: 'This Month', value: 'fetch_leads_this_month' },
            { label: 'All', action: 'show_index', url: '/leads', category: 'Leads' }
        ],
        customers: [
            { label: 'Today', value: 'fetch_customers_today' },
            { label: 'This Week', value: 'fetch_customers_this_week' },
            { label: 'This Month', value: 'fetch_customers_this_month' },
            { label: 'All', action: 'show_index', url: '/masters/customers', category: 'Customers' }
        ],
        followups: [
            { label: 'Today', value: 'fetch_followups_today' },
            { label: 'This Week', value: 'fetch_followups_this_week' },
            { label: 'This Month', value: 'fetch_followups_this_month' },
            { label: 'All', action: 'show_index', url: '/follow-ups', category: 'Followups' }
        ],
        tasks: [
            { label: 'Today', value: 'fetch_tasks_today' },
            { label: 'This Week', value: 'fetch_tasks_this_week' },
            { label: 'This Month', value: 'fetch_tasks_this_month' },
            { label: 'All', action: 'show_index', url: '/tasks', category: 'Tasks' }
        ],
        meetings: [
            { label: 'Today', value: 'fetch_meetings_today' },
            { label: 'This Week', value: 'fetch_meetings_this_week' },
            { label: 'This Month', value: 'fetch_meetings_this_month' },
            { label: 'All', action: 'show_index', url: '/meetings', category: 'Meetings' }
        ],
        staff: [
            { label: 'Today', value: 'fetch_staff_today' },
            { label: 'This Week', value: 'fetch_staff_this_week' },
            { label: 'This Month', value: 'fetch_staff_this_month' },
            { label: 'All', action: 'show_index', url: '/users', category: 'Staff' }
        ]
    };

    menuData.main.push({ label: 'Other', action: 'create_ticket', fullWidth: true });

    if (!toggleBtn || !closeBtn || !sendBtn || !inputEl || !messagesEl) return;

    let hasWelcomeMessage = false;
    let awaitingTicketMessage = false;

    function scrollToBottom() {
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function hideInput() {
        if (inputWrapper) {
            inputWrapper.classList.add('d-none');
        }
        if (inputEl) {
            inputEl.value = '';
        }
    }

    function showInput() {
        if (inputWrapper) {
            inputWrapper.classList.remove('d-none');
        }
        if (inputEl) {
            inputEl.focus();
        }
    }

    function createOptionButtons(options, isSubMenu = false, parentKey = 'main', currentKey = null) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-options-wrapper';

        options.forEach(opt => {
            const btn = document.createElement('div');
            btn.className = 'chatbot-option-btn';
            btn.textContent = opt.label;
            if (options.length === 1 || opt.fullWidth) btn.classList.add('full-width');
            
            btn.onclick = () => {
                if (opt.action === 'create_ticket') {
                    appendMessage(opt.label, true);
                    showTicketPrompt();
                } else if (opt.action === 'show_index') {
                    appendMessage(opt.label, true);
                    showIndexLink(opt.category, opt.url);
                } else if (opt.target) {
                    appendMessage(opt.label, true);
                    showMenu(opt.target, currentKey || 'main');
                } else if (opt.value) {
                    appendMessage(opt.label, true);
                    sendRequest(opt.value);
                }
            };
            wrapper.appendChild(btn);
        });

        if (isSubMenu) {
            const backBtn = document.createElement('div');
            backBtn.className = 'chatbot-option-btn full-width back-btn';
            backBtn.textContent = 'Back';
            backBtn.onclick = () => showMenu(parentKey);
            wrapper.appendChild(backBtn);
        }

        return wrapper;
    }

    function showIndexLink(category, url) {
        const container = document.createElement('div');
        container.className = 'chatbot-message bot';
        container.innerHTML = `<div class="chatbot-avatar">Bot</div>`;

        const content = document.createElement('div');
        content.style.width = '100%';

        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-options-wrapper';

        const linkBtn = document.createElement('a');
        linkBtn.className = 'chatbot-option-btn full-width';
        linkBtn.textContent = 'View all ' + category;
        linkBtn.href = url;
        linkBtn.style.textDecoration = 'none';
        linkBtn.style.color = 'inherit';
        linkBtn.style.display = 'block';
        linkBtn.style.textAlign = 'center';

        wrapper.appendChild(linkBtn);
        content.appendChild(wrapper);
        container.appendChild(content);
        messagesEl.appendChild(container);
        scrollToBottom();

        renderBackButton(() => {
            removeBackButton();
            showMenu('main');
        });
    }

    function showMenu(menuKey, parentKey = 'main') {
        const options = menuData[menuKey];
        if (!options) return;

        const container = document.createElement('div');
        container.className = 'chatbot-message bot';
        container.innerHTML = `<div class="chatbot-avatar">Bot</div>`;
        
        const content = document.createElement('div');
        content.style.width = '100%';
        content.appendChild(createOptionButtons(options, menuKey !== 'main', parentKey, menuKey));
        
        container.appendChild(content);
        messagesEl.appendChild(container);
        scrollToBottom();
    }

    function removeBackButton() {
        const existing = messagesEl.querySelector('.chatbot-ticket-back-wrapper');
        if (existing) {
            existing.remove();
        }
    }

    function renderBackButton(onClick) {
        removeBackButton();

        const backBtnWrapper = document.createElement('div');
        backBtnWrapper.className = 'chatbot-ticket-back-wrapper';
        backBtnWrapper.style.width = '100%';
        backBtnWrapper.style.marginTop = '10px';

        const optionsWrapper = document.createElement('div');
        optionsWrapper.className = 'chatbot-options-wrapper';

        const backBtn = document.createElement('div');
        backBtn.className = 'chatbot-option-btn full-width back-btn';
        backBtn.textContent = 'Back';
        backBtn.onclick = onClick || (() => {
            removeBackButton();
            showMenu('main');
        });

        optionsWrapper.appendChild(backBtn);
        backBtnWrapper.appendChild(optionsWrapper);
        messagesEl.appendChild(backBtnWrapper);
        scrollToBottom();
    }

    async function sendRequest(val) {
        const loader = showBotTyping();
        const bot = await getBotResponseFromServer(val);
        loader.remove();
        appendMessage(bot.reply, false);
        if (bot.data) renderData(bot.data);

        // Always add Back button after the response
        const backBtnWrapper = document.createElement('div');
        backBtnWrapper.style.width = '100%';
        const optionsWrapper = document.createElement('div');
        optionsWrapper.className = 'chatbot-options-wrapper';
        const backBtn = document.createElement('div');
        backBtn.className = 'chatbot-option-btn full-width back-btn';
        backBtn.textContent = 'Back';
        backBtn.onclick = () => showMenu('main');
        optionsWrapper.appendChild(backBtn);
        backBtnWrapper.appendChild(optionsWrapper);
        messagesEl.appendChild(backBtnWrapper);

        scrollToBottom();
    }

    function createMessageBubble(text, isUser) {
        const wrapper = document.createElement('div');
        wrapper.className = 'chatbot-message ' + (isUser ? 'user' : 'bot');

        const avatar = document.createElement('div');
        avatar.className = 'chatbot-avatar';
        avatar.textContent = isUser ? 'You' : 'Bot';

        const bubble = document.createElement('div');
        bubble.className = 'chatbot-bubble';
        bubble.textContent = text;

        if (isUser) {
            wrapper.appendChild(bubble);
            wrapper.appendChild(avatar);
        } else {
            wrapper.appendChild(avatar);
            wrapper.appendChild(bubble);
        }

        return wrapper;
    }

    function appendMessage(text, isUser) {
        const placeholder = messagesEl.querySelector('.chatbot-empty-state');
        if (placeholder) placeholder.remove();

        const message = createMessageBubble(text, isUser);
        messagesEl.appendChild(message);
        scrollToBottom();
    }

    // --- NEW: Loader bubble ---
    function showBotTyping() {
        const loader = document.createElement('div');
        loader.className = 'chatbot-message bot typing-bubble';
        loader.innerHTML = `
            <div class="chatbot-avatar">Bot</div>
            <div class="chatbot-bubble">...</div>
        `;
        messagesEl.appendChild(loader);
        scrollToBottom();
        return loader;
    }

    // --- UPDATED: Send message to Laravel AI assistant ---   
    async function getBotResponseFromServer(userText, options = {}) {
        try {
            const payload = { message: userText, ...options };
            const response = await fetch("/crm-assistant", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(payload)
            });

            return await response.json();
        } catch (error) {
            return { reply: "⚠ Server error — please try again.", data: null };
        }
    }

    async function handleSend() {
        const text = inputEl.value.trim();
        if (!text) {
            inputEl.focus();
            return;
        }

        const wasTicketMessage = awaitingTicketMessage;
        appendMessage(text, true);
        inputEl.value = '';
        inputEl.focus();

        const loader = showBotTyping();

        let bot;
        if (awaitingTicketMessage) {
            bot = await createTicket(text);
            awaitingTicketMessage = false;
        } else {
            bot = await getBotResponseFromServer(text);
        }

        loader.remove();

        appendMessage(bot.reply, false);

        if (bot.data) {
            renderData(bot.data);
        }

        if (wasTicketMessage) {
            hideInput();
            renderBackButton(() => {
                awaitingTicketMessage = false;
                hideInput();
                removeBackButton();
                showMenu('main');
            });
        } else {
            renderBackButton(() => {
                removeBackButton();
                showMenu('main');
            });
        }

        scrollToBottom();
    }

    function renderData(data) {
        if (!Array.isArray(data)) {
            // Fallback for non-array data
            const formatted = document.createElement('pre');
            formatted.className = "chatbot-data";
            formatted.textContent = JSON.stringify(data, null, 2);
            messagesEl.appendChild(formatted);
            return;
        }

        if (data.length === 0) {
            appendMessage("I couldn't find any matching records.", false);
            return;
        }

        const container = document.createElement('div');
        container.className = 'chatbot-message bot';
        container.innerHTML = `<div class="chatbot-avatar">Bot</div>`;

        const content = document.createElement('div');
        content.style.width = '100%';
        content.innerHTML = renderRecordList(data);
        
        container.appendChild(content);
        messagesEl.appendChild(container);
    }

    function renderRecordList(records) {
        let html = `<div class="chatbot-records-list">`;
        records.forEach(record => {
            const name = record.name || record.title || record.ticket_name || record.purpose || record.id || 'Record';
            const detailsUrl = record.url && record.url !== '#' ? record.url : '';
            const title = detailsUrl
                ? `<a href="${escapeHtml(detailsUrl)}" class="chatbot-record-title">${escapeHtml(name)}</a>`
                : `<span class="chatbot-record-title">${escapeHtml(name)}</span>`;
            const fields = [
                ['fa-user', 'Lead Name', record.lead_name],
                ['fa-user', 'Customer Name', record.customer_name],
                ['fa-user-check', 'Assigned To', record.assigned_to],
                ['fa-id-badge', 'Job Title', record.job_title],
                ['fa-building', 'Company', record.company_name],
                ['fa-location-dot', 'Address', record.address],
                ['fa-briefcase', 'Service', record.service_name],
                ['fa-box', 'Product', record.product_name],
                ['fa-video', 'Type', record.meeting_type],
                ['fa-calendar-check', 'Scheduled At', record.scheduled_at],
                ['fa-calendar-plus', 'Start Date', record.start_date],
                ['fa-calendar-xmark', 'Due Date', record.due_date],
                ['fa-calendar-minus', 'End Date', record.end_date],
                ['fa-calendar', 'Invoice Date', record.invoice_date],
                ['fa-calendar-check', 'Date', record.follow_up_date || record.date],
                ['fa-tag', 'Price', formatMoney(record.price)],
                ['fa-coins', 'Amount', formatMoney(record.amount)],
                ['fa-hashtag', 'Invoice #', record.invoice_number],
                ['fa-barcode', 'SKU', record.sku],
                ['fa-cubes', 'Stock', record.stock_quantity],
                ['fa-percent', 'Probability', record.probability !== null && record.probability !== undefined ? record.probability + '%' : null],
                ['fa-bullseye', 'Purpose', record.purpose],
                ['fa-align-left', 'Description', truncateText(record.description, 90)],
                ['fa-flag', 'Priority', record.priority],
            ];
            html += `
                <div class="chatbot-record-card">
                    <div class="chatbot-record-header">
                        <i class="fa-solid fa-file-lines"></i>${title}
                        <span class="chatbot-record-status badge ${getStatusBadgeClass(record.status)}">${escapeHtml(record.status || '--')}</span>
                    </div>
                    <div class="chatbot-record-details">
                        ${record.email ? renderContactField('fa-envelope', 'Email', record.email, 'mailto:' + record.email) : ''}
                        ${record.phone ? renderContactField('fa-phone', 'Phone', record.phone, 'tel:' + String(record.phone).replace(/[^+\d]/g, '')) : ''}
                        ${fields.map(field => renderRecordField(field[0], field[1], field[2], detailsUrl)).join('')}
                    </div>
                    ${detailsUrl ? `<a href="${escapeHtml(detailsUrl)}" class="chatbot-view-link"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Details</a>` : ''}
                </div>
            `;
        });
        html += `</div>`;
        return html;
    }

    function renderRecordField(icon, label, value, detailsUrl) {
        if (value === null || value === undefined || value === '') return '';
        const escapedValue = escapeHtml(value);
        const displayValue = detailsUrl
            ? `<a href="${escapeHtml(detailsUrl)}">${escapedValue}</a>`
            : escapedValue;
        return `<div class="chatbot-record-field"><i class="fa-solid ${icon}"></i><span>${escapeHtml(label)}:</span>${displayValue}</div>`;
    }

    function renderContactField(icon, label, value, href) {
        return `<div class="chatbot-record-field"><i class="fa-solid ${icon}"></i><span>${label}:</span><a href="${escapeHtml(href)}">${escapeHtml(value)}</a></div>`;
    }

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') return null;
        const number = Number(value);
        return Number.isFinite(number) ? number.toLocaleString(undefined, { maximumFractionDigits: 2 }) : value;
    }

    function truncateText(value, limit) {
        if (!value) return null;
        const text = String(value);
        return text.length > limit ? text.substring(0, limit - 3) + '...' : text;
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, character => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
        })[character]);
    }

    function getStatusBadgeClass(status) {
        if (!status) return 'bg-secondary';
        const s = status.toLowerCase();
        if (s.includes('inactive') || s.includes('lost') || s.includes('close') || s.includes('danger')) return 'bg-danger text-white';
        if (s.includes('active') || s.includes('won') || s.includes('success')) return 'bg-success text-white';
        if (s.includes('pending') || s.includes('progress') || s.includes('medium')) return 'bg-warning text-dark';
        return 'bg-secondary text-white';
    }

    function showTicketPrompt() {
        awaitingTicketMessage = true;
        showInput();
        appendMessage('Please type the message you want to save as a ticket. I will create it with priority Medium and status In Progress.', false);
        renderBackButton(() => {
            awaitingTicketMessage = false;
            hideInput();
            removeBackButton();
            showMenu('main');
        });
    }

    function showWelcomeMessage() {
        if (hasWelcomeMessage) return;

        appendMessage(
            'Hi! I’m your Bot. Ask me about customers, leads, tickets, reports, or perform updates like “update lead 12 to won”.',
            false
        );

        hasWelcomeMessage = true;
    }

    function openChatbot() {
        // Clear previous messages and restore empty state
        messagesEl.innerHTML = `
            <div class="chatbot-empty-state pt-0">
                👋 Hi there!<br>
                Ask me anything about <strong>leads, customers, tickets, reports,</strong> or CRM settings.
            </div>
        `;
        hasWelcomeMessage = false;
        awaitingTicketMessage = false;

        cardEl.classList.add('open');
        cardEl.removeAttribute('aria-hidden');
        showMenu('main');
        hideInput();
        removeBackButton();
    }

    function closeChatbot() {
        cardEl.classList.remove('open');
        cardEl.setAttribute('aria-hidden', 'true');
        hideInput();
        removeBackButton();
    }

    function toggleChatbot() {
        if (cardEl.classList.contains('open')) {
            closeChatbot();
        } else {
            openChatbot();
        }
    }

    async function createTicket(message) {
        return await getBotResponseFromServer(message, { create_ticket: true });
    }

    toggleBtn.addEventListener('click', toggleChatbot);
    closeBtn.addEventListener('click', closeChatbot);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && cardEl.classList.contains('open')) {
            closeChatbot();
        }
    });

    sendBtn.addEventListener('click', handleSend);

    function submitQuickPrompt(promptText) {
        inputEl.value = promptText;
        handleSend();
    }

    if (promptButtons.length) {
        promptButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                submitQuickPrompt(button.dataset.prompt);
            });
        });
    }

    inputEl.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            handleSend();
        }
    });
})();
