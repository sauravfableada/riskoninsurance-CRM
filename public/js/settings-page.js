(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initSettingsPage);
    } else {
        initSettingsPage();
    }

    function initSettingsPage() {
        const config = window.settingsPageConfig || {};
        bindTabHash();
        bindSettingsFormMemory();
        bindIntegrationCarets();
        bindFeatureToggles();
        const saveUrl = config.settingsUpdate || config.apiSettingsUpdate;
        bindAjaxForm("smtpSettingsForm", config.apiSettingsIndex, saveUrl, "smtpSettingsStatus");
        //hydrateSettings(config.apiSettingsIndex);
    }

    function bindSettingsFormMemory() {
        document.querySelectorAll(".settings-shell form").forEach(function (form) {
            form.addEventListener("submit", rememberActiveSettingsTab, true);
        });
    }

    function bindFeatureToggles() {
        document.querySelectorAll(".integration-feature-toggle input[type='checkbox']").forEach(function (toggle) {
            toggle.addEventListener("change", function () {
                const form = toggle.closest("form");
                if (form) {
                    rememberActiveSettingsTab();
                    form.submit();
                }
            });
        });
    }

    function authHeaders(extraHeaders = {}) {
        if (typeof window.crmApplyAuthHeaders === "function") {
            return window.crmApplyAuthHeaders(extraHeaders);
        }

        return extraHeaders;
    }

    function bindAjaxForm(formId, fetchUrl, saveUrl, statusId) {
        const form = document.getElementById(formId);
        if (!form || !saveUrl) {
            return;
        }

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            const statusEl = statusId ? document.getElementById(statusId) : null;
            const formData = new FormData(form);

            setStatus(statusEl, "Saving...", "text-muted");

            fetch(saveUrl, {
                method: "POST",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrf(),
                }),
                body: formData,
            })
                .then(parseJson)
                .then(function (payload) {
                    setStatus(statusEl, payload.message || "Saved successfully.", "text-success");
                    notify(payload.message || "Settings saved successfully.", "success");
                    if (fetchUrl) {
                        hydrateSettings(fetchUrl);
                    }
                })
                .catch(function (error) {
                    setStatus(statusEl, error.message || "Save failed.", "text-danger");
                    notify(error.message || "Save failed.", "error");
                });
        });
    }

    function hydrateSettings(fetchUrl) {
        if (!fetchUrl) {
            return;
        }

        fetch(fetchUrl, {
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json",
            }),
        })
            .then(parseJson)
            .then(function (payload) {
                const settings = payload && payload.data && payload.data.settings ? payload.data.settings : {};

                setField("input[name='mail_host']", settings.mail_host || "");
                setField("input[name='mail_port']", settings.mail_port || "587");
                setField("input[name='mail_username']", settings.mail_username || "");
                setField("input[name='mail_password']", settings.mail_password || "");
                setField("select[name='mail_encryption']", settings.mail_encryption || "tls");
                setField("input[name='mail_from_name']", settings.mail_from_name || "");
                setField("input[name='google_client_id']", settings.google_client_id || "");
                setField("input[name='google_client_secret']", settings.google_client_secret || "");
            })
            .catch(function () {
                // silent to avoid noisy settings load failures on first paint
            });
    }

    function bindTabHash() {
        const settingsTabs = document.getElementById("settingsTabs");
        if (!settingsTabs) {
            return;
        }

        let savedTab = "";
        try {
            savedTab = window.sessionStorage.getItem("settings.activeTab") || "";
        } catch (error) {
            savedTab = "";
        }

        const targetTab = window.location.hash || savedTab;
        if (targetTab && window.bootstrap) {
            const trigger = settingsTabs.querySelector('[data-bs-target="' + targetTab + '"]');
            if (trigger) {
                window.bootstrap.Tab.getOrCreateInstance(trigger).show();
            }
        }

        settingsTabs.querySelectorAll("[data-bs-target]").forEach(function (button) {
            button.addEventListener("shown.bs.tab", function (event) {
                const target = event.target.getAttribute("data-bs-target");
                if (target) {
                    history.replaceState(null, "", target);
                    try {
                        window.sessionStorage.setItem("settings.activeTab", target);
                    } catch (error) {
                        // Storage can be unavailable in restricted browser modes.
                    }
                }
            });
        });
    }

    function rememberActiveSettingsTab() {
        const activeTab = document.querySelector("#settingsTabs .nav-link.active[data-bs-target]");
        const target = activeTab ? activeTab.getAttribute("data-bs-target") : "";
        if (!target) {
            return;
        }

        try {
            window.sessionStorage.setItem("settings.activeTab", target);
        } catch (error) {
            // Storage can be unavailable in restricted browser modes.
        }
    }

    function bindIntegrationCarets() {
        document.querySelectorAll("#integrationsAccordion .integration-panel").forEach(function (panel) {
            panel.addEventListener("shown.bs.collapse", function () {
                toggleCaret(panel.id, true);
            });

            panel.addEventListener("hidden.bs.collapse", function () {
                toggleCaret(panel.id, false);
            });
        });

        document.querySelectorAll("#integrationsAccordion .integration-inner-collapse").forEach(function (panel) {
            panel.addEventListener("shown.bs.collapse", function () {
                toggleInnerCaret(panel.id, true);
            });

            panel.addEventListener("hidden.bs.collapse", function () {
                toggleInnerCaret(panel.id, false);
            });
        });
    }

    function toggleCaret(panelId, expanded) {
        const button = document.querySelector('[data-bs-target="#' + panelId + '"]');
        const icon = button ? button.querySelector(".integration-caret") : null;
        if (!icon) {
            return;
        }

        icon.classList.toggle("bi-chevron-up", expanded);
        icon.classList.toggle("bi-chevron-down", !expanded);
    }

    function toggleInnerCaret(panelId, expanded) {
        const button = document.querySelector('[data-bs-target="#' + panelId + '"]');
        const icon = button ? button.querySelector(".integration-inner-caret") : null;
        if (!icon) {
            return;
        }

        icon.classList.toggle("bi-chevron-up", expanded);
        icon.classList.toggle("bi-chevron-down", !expanded);
    }

    function setField(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.value = value;
        }
    }

    function setStatus(element, message, className) {
        if (!element) {
            return;
        }

        element.className = "settings-form-status " + (className || "");
        element.textContent = message || "";
    }

    function parseJson(response) {
        return response.json().catch(function () { return {}; }).then(function (payload) {
            if (!response.ok || payload.success === false) {
                let message = payload.message || "Request failed.";
                if (payload.errors) {
                    message = Object.values(payload.errors).flat().join(" ");
                }
                throw new Error(message);
            }

            return payload;
        });
    }

    function notify(message, type) {
        if (typeof window.toastr !== "undefined" && typeof window.toastr[type] === "function") {
            window.toastr[type](message);
            return;
        }

        if (typeof window.showAlert === "function") {
            window.showAlert(type, message);
            return;
        }

        console[type === "error" ? "error" : "log"](message);
    }

    function csrf() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute("content") : "";
    }
})();
