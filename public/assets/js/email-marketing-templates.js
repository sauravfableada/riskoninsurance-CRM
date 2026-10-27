document.addEventListener('DOMContentLoaded', function () {
    const cards = document.querySelectorAll('.template-card');
    const hiddenId = document.getElementById('selectedTemplateId');
    const errorEl = document.getElementById('templateSelectError');
    const form = document.getElementById('emailMarketingForm');

    if (cards.length && hiddenId) {
        cards.forEach(card => {
            card.addEventListener('click', function () {
                cards.forEach(c => c.classList.remove('border-primary', 'shadow'));
                this.classList.add('border-primary', 'shadow');
                hiddenId.value = this.getAttribute('data-template-id');
                if (errorEl) errorEl.style.display = 'none';
            });
        });

        // Pre-select when editing
        if (hiddenId.value) {
            const active = document.querySelector('.template-card[data-template-id="' + hiddenId.value + '"]');
            if (active) {
                active.classList.add('border-primary', 'shadow');
            }
        }
    }

    if (form && hiddenId) {
        form.addEventListener('submit', function (e) {
            if (!hiddenId.value) {
                e.preventDefault();
                if (errorEl) errorEl.style.display = 'block';
            }
        });
    }
});

