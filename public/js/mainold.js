function crmAlert(message, type = "info", title = "") {
    const icon = type === "danger" ? "error" : type;

    if (typeof Swal === "undefined") {
        console.info(message);
        return Promise.resolve();
    }

    return Swal.fire({
        icon: icon || "info",
        title: title || (icon === "success" ? "Success!" : icon === "error" ? "Error!" : "Notice"),
        text: message,
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 1800,
        timerProgressBar: true,
        customClass: { popup: "rounded-4 shadow" },
    });
}

function crmConfirm(message, options = {}) {
    if (typeof Swal === "undefined") {
        return Promise.resolve({ isConfirmed: window["confirm"](message) });
    }

    return Swal.fire({
        title: options.title || "Are you sure?",
        text: message,
        icon: options.icon || "warning",
        showCancelButton: true,
        confirmButtonText: options.confirmButtonText || "Yes, continue",
        cancelButtonText: options.cancelButtonText || "Cancel",
        confirmButtonColor: options.confirmButtonColor || "#dc3545",
        cancelButtonColor: options.cancelButtonColor || "#0a2540",
        customClass: {
            popup: "rounded-4 shadow",
        },
    });
}

function showAlert(type, message, title = "", redirectUrl = null) {
    crmAlert(message, type, title || (type === "success" ? "Success!" : "Error!")).then(() => {
        if (redirectUrl) window.location.href = redirectUrl;
    });
}

function showDeleteConfirm(message = "You won't be able to revert this!", options = {}) {
    return crmConfirm(message, {
        title: options.title || "Are you sure?",
        icon: options.icon || "warning",
        confirmButtonText: options.confirmButtonText || "Yes, delete it!",
        cancelButtonText: options.cancelButtonText || "Cancel",
        confirmButtonColor: options.confirmButtonColor || "#dc3545",
        cancelButtonColor: options.cancelButtonColor || "#0a2540",
    });
}

function buttonLoader(btn, text = "", show = true) {
    const button = $(btn);
    if (show) {
        if (!button.data("original-text")) {
            button.data("original-text", button.html());
        }
        button.prop("disabled", true).html(`
            <span class="spinner-border spinner-border-sm me-2"></span>
            ${text}...
        `);
    } else {
        button.prop("disabled", false).html(button.data("original-text"));
    }
}

window.crmAlert = crmAlert;
window.crmConfirm = crmConfirm;
window.showAlert = showAlert;
window.buttonLoader = buttonLoader;
window.showDeleteConfirm = showDeleteConfirm;

(function () {
    function extractConfirmMessage(value) {
        if (!value) {
            return "";
        }

        const match = value.match(/confirm\((['"])(.*?)\1\)/);
        return match ? match[2] : "";
    }

    document.addEventListener("click", function (event) {
        const target = event.target.closest("[data-confirm-message], [onclick*='confirm(']");
        if (!target) {
            return;
        }

        const isLink = target.tagName === "A" && target.href;
        const isButton = target.tagName === "BUTTON" || target.tagName === "INPUT";
        if (!isLink && !isButton) {
            return;
        }

        const message =
            target.getAttribute("data-confirm-message") ||
            extractConfirmMessage(target.getAttribute("onclick"));

        if (!message) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        showDeleteConfirm(message).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            if (isLink) {
                window.location.href = target.href;
                return;
            }

            const form = target.form;
            if (form) {
                form.removeAttribute("onsubmit");
                target.removeAttribute("onclick");
                form.submit();
            }
        });
    }, true);

    document.addEventListener("submit", function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const message =
            form.getAttribute("data-confirm-message") ||
            extractConfirmMessage(form.getAttribute("onsubmit"));

        if (!message || form.dataset.confirmed === "true") {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        showDeleteConfirm(message).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            form.dataset.confirmed = "true";
            form.removeAttribute("onsubmit");
            form.submit();
        });
    }, true);
})();

(function () {
    const historicalDatePattern = [
        "dob",
        "birth",
        "anniversary",
        "registration",
        "issue",
        "purchase",
        "opening",
        "deposit",
        "transaction",
        "invoice_date",
    ];

    function todayDateString() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, "0");
        const day = String(today.getDate()).padStart(2, "0");

        return `${year}-${month}-${day}`;
    }

    function shouldBlockPastDate(input) {
        if (!(input instanceof HTMLInputElement)) {
            return false;
        }

        if (input.dataset.allowPastDate === "true" || input.readOnly || input.disabled) {
            return false;
        }

        if (input.type !== "date" && input.type !== "datetime-local") {
            return false;
        }

        const key = [
            input.name,
            input.id,
            input.placeholder,
            input.getAttribute("aria-label"),
        ].filter(Boolean).join(" ").toLowerCase();

        return !historicalDatePattern.some((pattern) => key.includes(pattern));
    }

    function applyNoPastDate(input) {
        if (!shouldBlockPastDate(input)) {
            return;
        }

        const today = todayDateString();
        input.min = input.type === "datetime-local" ? `${today}T00:00` : today;
        input.dataset.noPastDate = "true";

        if (input._flatpickr) {
            input._flatpickr.set("minDate", input.type === "datetime-local" ? `${today} 00:00` : today);
        }
    }

    function normalizeDateValue(value) {
        return String(value || "").slice(0, 10);
    }

    function isPastDateValue(input) {
        if (!input.value) {
            return false;
        }

        return normalizeDateValue(input.value) < todayDateString();
    }

    function warnPastDate(input) {
        input.value = "";
        window.crmAlert?.("Past dates are not allowed for this field.", "warning", "Invalid Date");
    }

    function applyNoPastDates(root = document) {
        if (!document.body.classList.contains("crm-form-page")) {
            return;
        }

        root.querySelectorAll?.('input[type="date"], input[type="datetime-local"]').forEach(applyNoPastDate);
    }

    document.addEventListener("DOMContentLoaded", function () {
        applyNoPastDates();

        const observer = new MutationObserver(function (mutations) {
            if (!document.body.classList.contains("crm-form-page")) {
                return;
            }

            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!(node instanceof Element)) {
                        return;
                    }

                    if (node.matches?.('input[type="date"], input[type="datetime-local"]')) {
                        applyNoPastDate(node);
                    }

                    applyNoPastDates(node);
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    });

    document.addEventListener("change", function (event) {
        const input = event.target;

        if (!(input instanceof HTMLInputElement) || input.dataset.noPastDate !== "true") {
            return;
        }

        if (isPastDateValue(input)) {
            warnPastDate(input);
        }
    });

    document.addEventListener("submit", function (event) {
        const form = event.target;

        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        const pastInput = Array.from(form.querySelectorAll('input[data-no-past-date="true"]'))
            .find(isPastDateValue);

        if (!pastInput) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        warnPastDate(pastInput);
        pastInput.focus();
    }, true);

    window.crmApplyNoPastDates = applyNoPastDates;
})();
