(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initProfilePage);
    } else {
        initProfilePage();
    }

    function initProfilePage() {
        bindPreview("avatar-input", "avatar-preview");
        bindPreview("company-logo-input", "company-logo-preview");
        bindPreview("company-logo-input", "company-logo-preview-small");
        bindPreview("short-company-logo-input", "short-company-logo-preview");
        bindProfileForm();
        bindPasswordForm();
        bindGoogleDisconnect();
        syncGoogleUi();
        openPasswordModalOnServerErrors();
        bindPasswordToggles();
    }

    function authHeaders(extraHeaders = {}) {
        if (typeof window.crmApplyAuthHeaders === "function") {
            return window.crmApplyAuthHeaders(extraHeaders);
        }

        return extraHeaders;
    }

    function bindPreview(inputId, imgId) {
        const input = document.getElementById(inputId);
        const img = document.getElementById(imgId);
        if (!input || !img) {
            return;
        }

        input.addEventListener("change", function () {
            const file = this.files && this.files[0] ? this.files[0] : null;
            if (!file) {
                return;
            }

            if (!file.type.startsWith("image/")) {
                this.value = "";
                notify("Please select an image file.", "warning");
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    function bindProfileForm() {
        const form = document.getElementById("profileForm");
        const config = window.profilePageConfig || {};
        if (!form || !config.updateUrl) {
            return;
        }

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            fetch(config.updateUrl, {
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
                    notify(payload.message || "Profile updated successfully.", "success");
                    window.location.reload();
                })
                .catch(function (error) {
                    notify(error.message || "Profile update failed.", "error");
                });
        });
    }

    function bindPasswordForm() {
        const form = document.getElementById("changePasswordForm");
        const config = window.profilePageConfig || {};
        if (!form || !config.passwordUrl) {
            return;
        }

        form.addEventListener("submit", function (event) {
            event.preventDefault();

            const formData = new FormData(form);

            fetch(config.passwordUrl, {
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
                    notify(payload.message || "Password updated successfully.", "success");
                    const modalElement = document.getElementById("changePasswordModal");
                    if (modalElement && window.bootstrap) {
                        window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
                    }
                    form.reset();
                })
                .catch(function (error) {
                    notify(error.message || "Password update failed.", "error");
                });
        });
    }
    function bindPasswordToggles() {
        document.querySelectorAll('.toggle-password').forEach(function (button) {
            button.addEventListener('click', function () {
                const icon = this.querySelector('i');
                const input = this.previousElementSibling;
                if (!input || !icon) return;

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    }


    function bindGoogleDisconnect() {
        const config = window.profilePageConfig || {};
        if (!config.disconnectGoogleUrl) {
            return;
        }

        document.addEventListener("submit", function (event) {
            const form = event.target;
            if (!form || !form.classList.contains("google-disconnect-form")) {
                return;
            }

            event.preventDefault();

            fetch(config.disconnectGoogleUrl, {
                method: "POST",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrf(),
                }),
            })
                .then(parseJson)
                .then(function (payload) {
                    notify(payload.message || "Google disconnected successfully.", "success");
                    renderGoogleUi(false);
                })
                .catch(function (error) {
                    notify(error.message || "Failed to disconnect Google.", "error");
                });
        });
    }

    function syncGoogleUi() {
        const config = window.profilePageConfig || {};
        if (!config.googleStatusUrl) {
            return;
        }

        fetch(config.googleStatusUrl, {
            method: "GET",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json",
            }),
        })
            .then(parseJson)
            .then(function (payload) {
                renderGoogleUi(!!payload.data?.is_authenticated);
            })
            .catch(function () {
            });
    }

    function renderGoogleUi(isConnected) {
        const config = window.profilePageConfig || {};
        const cardTitle = document.querySelector(".profile-hero-subtitle");
        const cardAction = document.getElementById("profileGoogleAction");
        const topbarAction = document.getElementById("topbarGoogleAction");

        if (cardTitle) {
            cardTitle.textContent = isConnected ? "Google Connected" : "Connect To Google";
        }

        if (cardAction) {
            cardAction.innerHTML = isConnected
                ? `
                    <form method="POST" action="#" class="m-0 google-disconnect-form">
                        <button type="submit" class="profile-dark-btn profile-google-btn" style="min-width: 180px;">
                            <i class="bi bi-google"></i>
                            <span>Disconnect Google</span>
                        </button>
                    </form>
                `
                : `
                    <a href="${config.googleAuthUrl || "#"}" class="profile-dark-btn profile-google-btn" style="min-width: 180px;">
                        <i class="bi bi-link-45deg"></i>
                        <span>Connect to Google</span>
                    </a>
                `;
        }

        if (topbarAction) {
            topbarAction.innerHTML = isConnected
                ? `
                    <form method="POST" action="#" class="m-0 google-disconnect-form">
                        <button type="submit" class="dropdown-item w-100 border-0 bg-transparent text-start">
                            <i class="bi bi-google"></i><span>Disconnect Google</span>
                        </button>
                    </form>
                `
                : `
                    <a class="dropdown-item" href="${config.googleAuthUrl || "#"}">
                        <i class="bi bi-google"></i><span>Connect Google</span>
                    </a>
                `;
        }
    }

    function openPasswordModalOnServerErrors() {
        if (!window.profilePageConfig || !window.profilePageConfig.openPasswordModal) {
            return;
        }

        const modalElement = document.getElementById("changePasswordModal");
        if (modalElement && window.bootstrap) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }
    }

    function parseJson(response) {
        return response.json().catch(function () { return {}; }).then(function (payload) {
            if (!response.ok || payload.success === false) {
                if (response.status === 419) {
                    window.location.reload();
                    return {};
                }
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
        if (typeof window.showAlert === "function") {
            window.showAlert(type, message);
            return;
        }

        if (typeof window.showToast === "function") {
            window.showToast(message, type);
            return;
        }

        if (typeof window.toastr !== "undefined" && typeof window.toastr[type] === "function") {
            window.toastr[type](message);
            return;
        }

        console[type === "error" ? "error" : "log"](message);
    }

    function csrf() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.getAttribute("content") : "";
    }
})();
