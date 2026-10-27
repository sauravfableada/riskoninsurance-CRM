(function () {
    function notify(message, type = "info") {
        const mappedType =
            {
                success: "success",
                error: "error",
                warning: "warning",
                info: "info",
            }[type] || "info";

        if (typeof window.showAlert === "function") {
            window.showAlert(mappedType, message);
            return;
        }

        window.crmAlert(message);
    }

    function getCsrfToken() {
        return (
            document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
            document.querySelector('input[name="_token"]')?.value ||
            ""
        );
    }

    function authHeaders(extraHeaders = {}) {
        if (typeof window.crmApplyAuthHeaders === "function") {
            return window.crmApplyAuthHeaders(extraHeaders);
        }

        return extraHeaders;
    }

    function clearFormErrors($form) {
        $form.find(".is-invalid").removeClass("is-invalid");
        $form.find(".ts-wrapper.is-invalid").removeClass("is-invalid");
        $form.find(".invalid-feedback").html("");
        $form.find(".invalid-feedback.ajax-error").remove();
        $form.find(".ajax-alert").remove();
        $form.find("#formErrors").addClass("d-none").html("");
    }

    function showFormErrors($form, errors) {
        $.each(errors, function (field, messages) {
            const $input = $form.find(`[name="${field}"]`);
            const $error = $form.find(`#${field}-error`);

            if ($input.length) {
                $input.addClass("is-invalid");
                if ($input[0].tomselect) {
                    $input.next(".ts-wrapper").addClass("is-invalid");
                }
            }

            if ($error.length) {
                $error.html(messages[0]);
            } else if ($input.length) {
                $input.after(`<div class="invalid-feedback ajax-error">${messages[0]}</div>`);
            }
        });
    }

    function initTicketForms() {
        $(document).on("submit", ".ajax-ticket-form", function (e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalHtml = $submitBtn.html();
            const isEdit = $form.find('input[name="_method"][value="PUT"]').length > 0;
            const redirectUrl = $form.data("redirect") || "/tickets";
            const savingText = isEdit ? "Updating..." : "Saving...";
            const defaultText = isEdit ? "Update Ticket" : "Create Ticket";

            clearFormErrors($form);

            if ($form.find("#btnSpinner").length && $form.find("#btnText").length) {
                $form.find("#btnSpinner").removeClass("d-none");
                $form.find("#btnText").text(savingText);
                $submitBtn.prop("disabled", true);
            } else {
                $submitBtn
                    .prop("disabled", true)
                    .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ' + savingText);
            }

            $.ajax({
                url: $form.attr("action"),
                type: "POST",
                data: $form.serialize(),
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": getCsrfToken(),
                    Accept: "application/json",
                }),
                success: function (response) {
                    notify(
                        response.message ||
                            (isEdit
                                ? "Ticket updated successfully."
                                : "Ticket created successfully."),
                        "success",
                    );

                    setTimeout(function () {
                        window.location.href = response.redirect || redirectUrl;
                    }, 300);
                },
                error: function (xhr) {
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        showFormErrors($form, xhr.responseJSON.errors);
                        return;
                    }

                    notify(
                        xhr.responseJSON?.message || "Something went wrong. Please try again.",
                        "error",
                    );
                },
                complete: function () {
                    if ($form.find("#btnSpinner").length && $form.find("#btnText").length) {
                        $form.find("#btnSpinner").addClass("d-none");
                        $form.find("#btnText").text(defaultText);
                        $submitBtn.prop("disabled", false);
                    } else {
                        $submitBtn.prop("disabled", false).html(originalHtml);
                    }
                },
            });
        });

        $(document).on("input change", ".ajax-ticket-form input, .ajax-ticket-form select, .ajax-ticket-form textarea", function () {
            $(this).removeClass("is-invalid");
            if (this.tomselect) {
                $(this).next(".ts-wrapper").removeClass("is-invalid");
            }
            const inputId = $(this).attr("id");
            if (inputId) {
                $(`#${inputId}-error`).html("");
            }
        });
    }

    function initTicketIndexDelete() {
        $(document).on("click", ".delete-ticket", function () {
            const $btn = $(this);
            const url = $btn.data("url");
            const $row = $btn.closest("tr");
            const originalHtml = $btn.html();

            window.showDeleteConfirm("You want to delete this ticket. This action cannot be undone.").then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                $btn
                    .prop("disabled", true)
                    .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

                $.ajax({
                    url: url,
                    type: "POST",
                    data: {
                        _token: getCsrfToken(),
                        _method: "DELETE",
                    },
                    dataType: "json",
                    headers: authHeaders({
                        "X-Requested-With": "XMLHttpRequest",
                        Accept: "application/json",
                    }),
                    success: function (response) {
                        if (response.success) {
                            notify(response.message || "Ticket deleted successfully.", "success");
                            $row.fadeOut(200, function () {
                                $(this).remove();
                                if (!$("tbody tr").length) {
                                    window.location.reload();
                                }
                            });
                            return;
                        }

                        notify(response.message || "Failed to delete ticket.", "error");
                        $btn.prop("disabled", false).html(originalHtml);
                    },
                    error: function (xhr) {
                        notify(xhr.responseJSON?.message || "Failed to delete ticket.", "error");
                        $btn.prop("disabled", false).html(originalHtml);
                    },
                });
            });
        });
    }

    function updateStatusUI(status) {
        $("#currentStatusText").text(status);
        $('.ajax-status-form button.dropdown-item').removeClass("active");
        $(`.ajax-status-form button.dropdown-item[data-status="${status}"]`).addClass("active");

        if (status === "Resolved" || status === "Closed") {
            $("#resolveForm").addClass("d-none");
        } else {
            $("#resolveForm").removeClass("d-none");
        }

        if (status === "Closed") {
            $("#closeForm").addClass("d-none");
            $("#replyFormContainer").addClass("d-none");
            $("#closedMessage").removeClass("d-none");
        } else {
            $("#closeForm").removeClass("d-none");
            $("#replyFormContainer").removeClass("d-none");
            $("#closedMessage").addClass("d-none");
        }
    }

    function initTicketShowActions() {
        const $replyForm = $("#replyForm");
        const $deleteBtn = $("#deleteTicketBtn");

        if ($replyForm.length) {
            $replyForm.on("submit", function (e) {
                e.preventDefault();

                const $form = $(this);
                const $submitBtn = $("#postReplyBtn");
                const originalHtml = $submitBtn.html();
                const $messageInput = $("#message");

                $submitBtn
                    .prop("disabled", true)
                    .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Posting...');

                $.ajax({
                    url: $form.attr("action"),
                    type: "POST",
                    data: $form.serialize(),
                    dataType: "json",
                    headers: authHeaders({
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": getCsrfToken(),
                        Accept: "application/json",
                    }),
                    success: function (response) {
                        if (!response.success) {
                            notify("Failed to post reply. Please try again.", "error");
                            return;
                        }

                        $("#noRepliesMessage").remove();

                        const alignment = response.reply.is_current_user ? "text-end" : "";
                        const bgColor = response.reply.is_current_user
                            ? "bg-primary-subtle border-primary-subtle"
                            : "bg-white";

                        const replyHtml = `
                            <div class="mb-3 ${alignment}">
                                <div class="small fw-semibold text-muted mb-1">
                                    ${response.reply.user_name} | <span class="fw-normal">${response.reply.created_at}</span>
                                </div>
                                <div class="p-1 px-3 d-inline-block rounded shadow-sm border text-start ${bgColor}" style="max-width: 85%;">
                                    ${response.reply.message}
                                </div>
                            </div>
                        `;

                        $("#repliesContainer").append(replyHtml);
                        $messageInput.val("");

                        if (response.ticket_status) {
                            updateStatusUI(response.ticket_status);
                        }

                        notify(response.message || "Reply posted successfully.", "success");
                    },
                    error: function (xhr) {
                        notify(
                            xhr.responseJSON?.message || "Failed to post reply. Please try again.",
                            "error",
                        );
                    },
                    complete: function () {
                        $submitBtn.prop("disabled", false).html(originalHtml);
                    },
                });
            });
        }

        $(document).on("submit", ".ajax-status-form", function (e) {
            e.preventDefault();

            const $form = $(this);

            $.ajax({
                url: $form.attr("action"),
                type: "POST",
                data: $form.serialize(),
                dataType: "json",
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": getCsrfToken(),
                    Accept: "application/json",
                }),
                success: function (response) {
                    if (response.success) {
                        updateStatusUI(response.status);
                        notify(response.message || "Ticket status updated successfully.", "success");
                        return;
                    }

                    notify(response.message || "Failed to update status.", "error");
                },
                error: function (xhr) {
                    notify(xhr.responseJSON?.message || "Failed to update status.", "error");
                },
            });
        });

        if ($deleteBtn.length) {
            $deleteBtn.on("click", function () {
                const $btn = $(this);
                const originalHtml = $btn.html();
                const redirectUrl = $btn.data("redirect") || "/tickets";

                window.showDeleteConfirm("You want to delete this ticket. This action cannot be undone.").then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $btn
                        .prop("disabled", true)
                        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');

                    $.ajax({
                        url: $btn.data("url"),
                        type: "POST",
                        data: {
                            _token: getCsrfToken(),
                            _method: "DELETE",
                        },
                        dataType: "json",
                        headers: authHeaders({
                            "X-Requested-With": "XMLHttpRequest",
                            Accept: "application/json",
                        }),
                        success: function (response) {
                            if (response.success) {
                                notify(response.message || "Ticket deleted successfully.", "success");
                                setTimeout(function () {
                                    window.location.href = redirectUrl;
                                }, 300);
                                return;
                            }

                            notify(response.message || "Failed to delete ticket.", "error");
                            $btn.prop("disabled", false).html(originalHtml);
                        },
                        error: function (xhr) {
                            notify(xhr.responseJSON?.message || "Failed to delete ticket.", "error");
                            $btn.prop("disabled", false).html(originalHtml);
                        },
                    });
                });
            });
        }
    }

    $(document).ready(function () {
        initTicketForms();
        initTicketIndexDelete();
        initTicketShowActions();
    });
})();
