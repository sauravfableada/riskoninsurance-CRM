document.addEventListener("DOMContentLoaded", function () {
    const csrfToken =
        document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
    const authHeaders = (extraHeaders = {}) =>
        typeof window.crmApplyAuthHeaders === "function"
            ? window.crmApplyAuthHeaders(extraHeaders)
            : extraHeaders;

    function savePermissions(form) {
        const formData = new FormData(form);

        fetch(form.action, {
            method: "POST",
            headers: authHeaders({
                "X-Requested-With": "XMLHttpRequest",
                Accept: "application/json",
            }),
            body: formData,
        }).catch(() => {});
    }

    function syncRow(row) {
        const allToggle = row.querySelector(".module-all-toggle");
        const actions = Array.from(row.querySelectorAll(".module-action-toggle"));
        const viewToggle = row.querySelector('.module-action-toggle[value^="view_"]');
        const nonViewActions = actions.filter((item) => item !== viewToggle);

        if (!allToggle || !actions.length) {
            return;
        }

        if (viewToggle) {
            const hasNonViewChecked = nonViewActions.some((item) => item.checked);

            if (hasNonViewChecked) {
                viewToggle.checked = true;
            }

            if (!viewToggle.checked) {
                nonViewActions.forEach((item) => {
                    item.checked = false;
                });
            }
        }

        allToggle.checked = actions.every((item) => item.checked);
        allToggle.indeterminate = false;
    }

    document.querySelectorAll(".permissions-form").forEach((form) => {
        let saveTimer = null;

        function queueSave() {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(() => savePermissions(form), 150);
        }

        form.querySelectorAll("tbody tr").forEach((row) => {
            syncRow(row);
        });

        form.querySelectorAll(".module-all-toggle").forEach((toggle) => {
            toggle.addEventListener("change", function () {
                const row = this.closest("tr");
                row.querySelectorAll(".module-action-toggle").forEach((checkbox) => {
                    checkbox.checked = this.checked;
                });
                this.indeterminate = false;
                queueSave();
            });
        });

        form.querySelectorAll(".module-action-toggle").forEach((checkbox) => {
            checkbox.addEventListener("change", function () {
                const row = this.closest("tr");
                syncRow(row);
                queueSave();
            });
        });
    });

    document.querySelectorAll(".permissions-modal").forEach((modal) => {
        modal.addEventListener("shown.bs.modal", function () {
            modal.querySelectorAll("tbody tr").forEach((row) => {
                syncRow(row);
            });
        });
    });

    function paintStatusButton(button, isActive) {
        button.dataset.active = isActive ? "1" : "0";
        button.textContent = isActive ? "Active" : "Inactive";
        button.classList.toggle("status-active", isActive);
        button.classList.toggle("status-inactive", !isActive);
        button.setAttribute("aria-pressed", isActive ? "true" : "false");
    }

    document.querySelectorAll(".user-status-toggle").forEach((button) => {
        button.addEventListener("click", async function () {
            if (this.dataset.loading === "1") {
                return;
            }

            const currentActive = this.dataset.active === "1";
            const nextActive = !currentActive;
            const requestUrl = this.dataset.url;

            if (!requestUrl) {
                return;
            }

            this.dataset.loading = "1";
            this.disabled = true;

            try {
                const response = await fetch(requestUrl, {
                    method: "PATCH",
                    headers: authHeaders({
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": csrfToken,
                    }),
                    body: JSON.stringify({
                        is_active: nextActive ? 1 : 0,
                    }),
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok || payload.success === false) {
                    throw new Error(payload.message || "Unable to update staff status.");
                }

                paintStatusButton(this, !!payload.is_active);

                notify(payload.message || "Staff status updated successfully.", "success");
            } catch (error) {
                notify(error.message || "Unable to update staff status.", "error");
            } finally {
                this.disabled = false;
                this.dataset.loading = "0";
            }
        });
    });
});
    function notify(message, type) {
        if (typeof window.showAlert === "function") {
            const mappedType = type === "error" ? "error" : "success";
            window.showAlert(mappedType, message);
            return;
        }

        if (typeof showToast === "function") {
            showToast(message, type);
        }
    }
