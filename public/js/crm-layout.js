(function () {
    function showToast(message, type = "info", duration = 5000) {
        const toastId = "toast-" + Date.now();
        const bgColor =
            {
                success: "bg-success",
                error: "bg-danger",
                warning: "bg-warning",
                info: "bg-info",
            }[type] || "bg-info";

        const icon =
            {
                success: "bi-check-circle-fill",
                error: "bi-exclamation-triangle-fill",
                warning: "bi-exclamation-circle-fill",
                info: "bi-info-circle-fill",
            }[type] || "bi-info-circle-fill";

        const toast = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgColor} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true" data-bs-autohide="true" data-bs-delay="${duration}">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-center">
                        <i class="bi ${icon} me-2"></i>
                        <span>${message}</span>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        $("#toastContainer").append(toast);
        const toastElement = new bootstrap.Toast(document.getElementById(toastId));
        toastElement.show();

        $(`#${toastId}`).on("hidden.bs.toast", function () {
            $(this).remove();
        });
    }

    function enhanceFormLabels() {
        if (!document.body.classList.contains("crm-form-page")) {
            return;
        }

        const iconMatchers = [
            { match: /(policy number|policy no)/, icon: "bi-shield-check" },
            { match: /(policy holder|nominee)/, icon: "bi-person-vcard-fill" },
            { match: /(family group)/, icon: "bi-people-fill" },
            { match: /(policy sub type|policy type|insurance type)/, icon: "bi-diagram-3-fill" },
            { match: /(segment)/, icon: "bi-grid-fill" },
            { match: /(branch)/, icon: "bi-signpost-split-fill" },
            { match: /(vehicle registration|vehicle model|vehicle idv)/, icon: "bi-car-front-fill" },
            { match: /(engine|chassis)/, icon: "bi-gear-fill" },
            { match: /(fuel|cng|lpg)/, icon: "bi-fuel-pump-fill" },
            { match: /(gross vehicle weight|gvw|weight)/, icon: "bi-speedometer" },
            { match: /(no claim bonus|ncb)/, icon: "bi-award-fill" },
            { match: /(year of manufacture)/, icon: "bi-calendar2-check-fill" },
            { match: /(payment mode|payment type)/, icon: "bi-credit-card-fill" },
            { match: /(sum assured|premium|tax amount|payment amount|amount)/, icon: "bi-currency-rupee" },
            { match: /(policy pdf|document|file)/, icon: "bi-file-earmark-pdf-fill" },
            { match: /(customer|client|assigned to|assigned staff|assigned for|lead name|lead|staff|user|contact)/, icon: "bi-person-fill" },
            { match: /(email|mail)/, icon: "bi-envelope-fill" },
            { match: /(phone|mobile|whatsapp|call)/, icon: "bi-telephone-fill" },
            { match: /(address|location|city|country)/, icon: "bi-geo-alt-fill" },
            { match: /(status)/, icon: "bi-info-circle-fill" },
            { match: /(priority)/, icon: "bi-exclamation-circle-fill" },
            { match: /(date|time|due|follow up|scheduled|start|end|deadline)/, icon: "bi-calendar-event-fill" },
            { match: /(comment|description|agenda|note|remarks|message|details)/, icon: "bi-text-paragraph" },
            { match: /(meeting type|type|category|stage|source)/, icon: "bi-diagram-3-fill" },
            { match: /(probability|percent|percentage)/, icon: "bi-percent" },
            { match: /(image|photo|logo|avatar|icon)/, icon: "bi-image-fill" },
            { match: /(company|organization|business)/, icon: "bi-building" },
            { match: /(^|\s)name(\s|$)/, icon: "bi-person-fill" },
            { match: /(project)/, icon: "bi-folder-fill" },
            { match: /(task)/, icon: "bi-list-check" },
            { match: /(deal|opportunity)/, icon: "bi-briefcase-fill" },
            { match: /(ticket|support)/, icon: "bi-life-preserver" },
            { match: /(service)/, icon: "bi-tools" },
            { match: /(meeting)/, icon: "bi-camera-video-fill" },
        ];

        const getLabelText = function (label) {
            return Array.from(label.childNodes)
                .filter(function (node) {
                    return node.nodeType === Node.TEXT_NODE || (node.nodeType === Node.ELEMENT_NODE && !node.classList.contains("text-danger"));
                })
                .map(function (node) {
                    return node.textContent || "";
                })
                .join(" ")
                .replace(/\s+/g, " ")
                .trim()
                .toLowerCase();
        };

        const findAssociatedField = function (label) {
            if (label.htmlFor) {
                return document.getElementById(label.htmlFor);
            }

            const fieldContainer = label.closest(".col, .col-md-6, .col-md-4, .col-md-3, .col-lg-6, .col-lg-4, .col-lg-3, .col-xl-6, .col-xl-4, .col-xl-3") || label.parentElement;
            return fieldContainer ? fieldContainer.querySelector("input, select, textarea") : null;
        };

        const resolveIcon = function (label, field) {
            const parts = [getLabelText(label)];
            if (field) {
                parts.push((field.name || "").replaceAll("_", " "));
                parts.push((field.id || "").replaceAll("_", " "));
            }

            const haystack = parts.join(" ").toLowerCase();
            const match = iconMatchers.find(function (entry) {
                return entry.match.test(haystack);
            });

            return match ? match.icon : null;
        };

        document.querySelectorAll(".crm-form-page form label.form-label").forEach(function (label) {
            if (label.dataset.iconEnhanced === "true" || label.querySelector(".crm-label-icon, .fa, .fas, .far, .fab, .bi")) {
                return;
            }

            const field = findAssociatedField(label);
            const iconClass = resolveIcon(label, field);
            if (!iconClass) {
                return;
            }

            const icon = document.createElement("i");
            icon.className = "bi " + iconClass + " crm-label-icon";
            icon.setAttribute("aria-hidden", "true");

            label.classList.add("crm-label-with-icon");
            label.prepend(icon);
            label.dataset.iconEnhanced = "true";
        });
    }

    function initCrmRemoteSelect(selector, options) {
        if (!window.TomSelect) {
            return null;
        }

        const element = document.querySelector(selector);
        if (!element || element.tomselect) {
            return null;
        }

        const searchUrl = element.dataset.searchUrl;
        if (!searchUrl) {
            return null;
        }

        const inferredSearchType = options.searchType
            || element.dataset.searchType
            || (searchUrl.includes("/users/search") ? "user" : null)
            || (searchUrl.includes("/customers/search") ? "customer" : null)
            || ((element.name || element.id || "").includes("user") ? "user" : null)
            || ((element.name || element.id || "").includes("customer") ? "customer" : null)
            || "default";
        const placeholder = element.dataset.searchPlaceholder
            || options.placeholder
            || (inferredSearchType === "user" ? "-- Search User --" : null)
            || (inferredSearchType === "customer" ? "-- Search Customer --" : null)
            || "-- Search --";

        const configByType = {
            user: {
                searchField: ["name", "email"],
                render: {
                    option: function (item, escape) {
                        const name = item.name || item.text || "";
                        const email = item.email || item.data_email || "";
                        return '<div class="py-2 px-3"><div class="fw-bold">' + escape(name) + "</div>" +
                            (email ? '<div class="text-muted small">' + escape(email) + "</div>" : "") +
                            "</div>";
                    },
                    item: function (item, escape) {
                        return "<div>" + escape(item.name || item.text || "") + "</div>";
                    }
                }
            },
            customer: {
                searchField: ["name", "email", "phone"],
                render: {
                    option: function (item, escape) {
                        const name = item.name || item.text || "";
                        const email = item.email || item.data_email || "";
                        const phone = item.phone || item.data_phone || "";
                        const details = [email, phone].filter(Boolean).join(" | ");
                        return '<div class="py-2 px-3"><div class="fw-bold">' + escape(name) + "</div>" +
                            (details ? '<div class="text-muted small">' + escape(details) + "</div>" : "") +
                            "</div>";
                    },
                    item: function (item, escape) {
                        return "<div>" + escape(item.name || item.text || "") + "</div>";
                    }
                }
            },
            default: {
                searchField: ["name"],
                render: {
                    option: function (item, escape) {
                        return '<div class="py-2 px-3">' + escape(item.name || item.text || "") + "</div>";
                    },
                    item: function (item, escape) {
                        return "<div>" + escape(item.name || item.text || "") + "</div>";
                    }
                }
            }
        };

        const config = configByType[inferredSearchType] || configByType.default;
        const initialOptions = Array.from(element.options)
            .filter(function (option) {
                return option.value !== "";
            })
            .map(function (option) {
                return {
                    id: option.value,
                    name: option.textContent.trim(),
                    email: option.dataset.email || "",
                    phone: option.dataset.phone || "",
                };
            });
        const initialItems = Array.from(element.selectedOptions)
            .filter(function (option) {
                return option.value !== "";
            })
            .map(function (option) {
                return option.value;
            });

        return new TomSelect(selector, {
            valueField: "id",
            labelField: "name",
            searchField: config.searchField,
            options: initialOptions,
            items: initialItems,
            preload: true,
            hideSelected: true,
            load: function (query, callback) {
                const requestUrl = searchUrl + "?q=" + encodeURIComponent(query || "");
                fetch(requestUrl, {
                    method: "GET",
                    headers: (typeof window.crmApplyAuthHeaders === "function"
                        ? window.crmApplyAuthHeaders({
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        })
                        : {
                            Accept: "application/json",
                            "X-Requested-With": "XMLHttpRequest",
                        }),
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (json) {
                        const fetchedItems = Array.isArray(json) ? json : [];
                        const selectedItems = Array.from(element.options)
                            .filter(function (option) {
                                return option.selected && option.value !== "";
                            })
                            .map(function (option) {
                                return {
                                    id: option.value,
                                    name: option.textContent.trim(),
                                    email: option.dataset.email || "",
                                    phone: option.dataset.phone || "",
                                };
                            });

                        const mergedItems = [...selectedItems, ...initialOptions, ...fetchedItems].filter(function (item, index, items) {
                            return item && item.id && items.findIndex(function (candidate) {
                                return String(candidate.id) === String(item.id);
                            }) === index;
                        });

                        callback(mergedItems);
                    })
                    .catch(function () {
                        callback();
                    });
            },
            render: config.render,
            placeholder: placeholder,
            allowEmptyOption: true,
            copyAttributesToOptions: true,
        });
    }

    function initPageChrome() {
        const forms = document.querySelectorAll(".js-validate");
        forms.forEach(function (form) {
            form.addEventListener("submit", function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add("was-validated");
            }, false);
        });

        const sidebarToggle = document.getElementById("sidebarToggle");
        const sidebar = document.querySelector(".crm-sidebar");
        const sidebarBackdrop = document.getElementById("crmSidebarBackdrop");

        if (sidebarToggle && sidebar) {
            const syncToggleState = function () {
                const expanded = window.innerWidth < 992
                    ? sidebar.classList.contains("open")
                    : !sidebar.classList.contains("collapsed");
                sidebarToggle.setAttribute("aria-expanded", String(expanded));
            };

            sidebarToggle.addEventListener("click", function () {
                if (window.innerWidth < 992) {
                    sidebar.classList.toggle("open");
                    if (sidebarBackdrop) {
                        sidebarBackdrop.classList.toggle("show");
                    }
                } else {
                    sidebar.classList.toggle("collapsed");
                }
                syncToggleState();
            });

            window.addEventListener("resize", function () {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove("open");
                    if (sidebarBackdrop) {
                        sidebarBackdrop.classList.remove("show");
                    }
                }
                syncToggleState();
            });

            syncToggleState();
        }

        if (sidebarBackdrop && sidebar) {
            sidebarBackdrop.addEventListener("click", function () {
                sidebar.classList.remove("open");
                sidebarBackdrop.classList.remove("show");
            });
        }

        if (window.flatpickr) {
            document.querySelectorAll(".js-date").forEach(function (input) {
                flatpickr(input, {
                    allowInput: true,
                    dateFormat: "Y-m-d",
                    altInput: true,
                    altFormat: "d/m/Y",
                    minDate: input.getAttribute("min") || null,
                });
            });

            document.querySelectorAll(".js-datetime").forEach(function (input) {
                flatpickr(input, {
                    allowInput: true,
                    enableTime: true,
                    time_24hr: false,
                    dateFormat: "Y-m-d H:i",
                    altInput: true,
                    altFormat: "d/m/Y h:i K",
                    minDate: input.getAttribute("min") || null,
                });
            });
        }

        enhanceFormLabels();
    }

    function initPolicyTermDates() {
        const modeInput = document.getElementById("policy_mode");
        const startInput = document.getElementById("start_date");
        const endInput = document.getElementById("end_date");

        if (!modeInput || !startInput || !endInput) {
            return;
        }

        const termMonths = {
            monthly: 1,
            quarterly: 3,
            "half-yearly": 6,
            "half yearly": 6,
            halfyearly: 6,
            yearly: 12,
            annual: 12,
            annually: 12,
        };

        const calculateEndDate = function () {
            const parts = startInput.value.split("-").map(Number);
            const normalizedMode = modeInput.value.trim().toLowerCase();
            const monthsToAdd = termMonths[normalizedMode];

            if (parts.length !== 3 || parts.some(Number.isNaN) || !monthsToAdd) {
                return;
            }

            const year = parts[0];
            const monthIndex = parts[1] - 1;
            const day = parts[2];
            const targetMonthIndex = monthIndex + monthsToAdd;
            const targetYear = year + Math.floor(targetMonthIndex / 12);
            const targetMonth = ((targetMonthIndex % 12) + 12) % 12;
            const lastDayOfTargetMonth = new Date(targetYear, targetMonth + 1, 0).getDate();
            const targetDay = Math.min(day, lastDayOfTargetMonth);

            endInput.value = [
                targetYear,
                String(targetMonth + 1).padStart(2, "0"),
                String(targetDay).padStart(2, "0"),
            ].join("-");
            endInput.dispatchEvent(new Event("input", { bubbles: true }));
        };

        startInput.addEventListener("change", calculateEndDate);
        modeInput.addEventListener("change", calculateEndDate);
    }

    function initRemoteSelects() {
        if (!window.TomSelect) {
            return;
        }

        document.querySelectorAll("select[data-search-url]").forEach(function (element) {
            if (!element.id || element.tomselect) {
                return;
            }

            initCrmRemoteSelect("#" + element.id, {
                searchType: element.dataset.searchType,
                placeholder: element.dataset.searchPlaceholder,
            });
        });
    }

    window.showToast = showToast;
    window.initCrmRemoteSelect = initCrmRemoteSelect;

    document.addEventListener("DOMContentLoaded", function () {
        initPageChrome();
        initPolicyTermDates();
        initRemoteSelects();
    });

    $(document).ready(function() {
        // Assign to me handler
        $(document).on("click", ".btn-assign-me", function (e) {
            const btn = $(this);
            const targetId = btn.data("target");
            const userId = btn.data("userId");
            const userName = btn.data("userName") || "Me";
            
            if (!targetId || !userId) return;
            
            const selectEl = document.querySelector(targetId);
            if (!selectEl) return;
            
            if (selectEl.tomselect) {
                selectEl.tomselect.addOption({ id: userId, name: userName });
                selectEl.tomselect.addItem(userId);
            } else {
                let $select = $(targetId);
                if (!$select.find(`option[value="${userId}"]`).length) {
                    $select.append(new Option(userName, userId, true, true));
                }
                $select.val(userId).trigger("change");
            }
        });

        // Handle Add Staff Modal Submit
        $(document).on("submit", "#formAddStaff", function (e) {
            e.preventDefault();
            let $form = $(this);
            let url = $form.attr("action");
            let btn = $form.find('button[type="submit"]');
            let spinner = btn.find('.spinner-border');
            
            btn.prop('disabled', true);
            spinner.removeClass('d-none');
            
            // Note: clearErrors and showErrors must be globally available
            if (typeof clearErrors === 'function') clearErrors($form);
            
            $.ajax({
                url: url,
                type: "POST",
                data: $form.serialize(),
                headers: (typeof window.crmApplyAuthHeaders === "function" ? window.crmApplyAuthHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    Accept: "application/json",
                }) : {
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    Accept: "application/json",
                }),
                success: function (response) {
                    btn.prop('disabled', false);
                    spinner.addClass('d-none');
                    
                    if (response.success && response.data) {
                        let user = response.data;
                        let tsElement = document.querySelector("#assigned_user_id");
                        if (tsElement && tsElement.tomselect) {
                            tsElement.tomselect.addOption({
                                id: user.id,
                                name: user.name,
                                email: user.email || ''
                            });
                            tsElement.tomselect.addItem(user.id);
                        } else {
                            // Fallback in case TomSelect is not used
                            let $select = $("#assigned_user_id");
                            $select.append(new Option(user.name, user.id, true, true));
                            $select.trigger("change");
                        }
                        $("#modalAddStaff").modal("hide");
                        $form[0].reset();
                        if (typeof window.showAlert === "function") {
                            window.showAlert("success", "Staff added successfully.");
                        }
                    }
                },
                error: function (xhr) {
                    btn.prop('disabled', false);
                    spinner.addClass('d-none');
                    
                    if (xhr.status === 422 && xhr.responseJSON?.errors && typeof showErrors === 'function') {
                        showErrors($form, xhr.responseJSON.errors);
                    } else if (typeof window.showAlert === "function") {
                        window.showAlert("error", xhr.responseJSON?.message || "Error adding staff.");
                    }
                }
            });
        });
    });
})();
