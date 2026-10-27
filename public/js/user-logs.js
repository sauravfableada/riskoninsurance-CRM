(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initUserLogs);
    } else {
        initUserLogs();
    }

    function initUserLogs() {
        const config = window.userLogsConfig || {};
        const tableBody = document.querySelector("#userLogsTable tbody");
        const searchInput = document.getElementById("userLogsSearch");
        const perPageInput = document.getElementById("per_page");
        const pagination = document.getElementById("userLogsPagination");
        const summary = document.getElementById("userLogsSummary");
        const refreshButton = document.getElementById("userLogsRefreshBtn");
        const deleteAllButton = document.getElementById("userLogsDeleteAllBtn");

        if (!config.indexUrl || !tableBody || !searchInput || !perPageInput || !pagination || !summary) {
            return;
        }

        let state = {
            q: searchInput.value || "",
            per_page: perPageInput.value || "10",
            page: 1,
        };
        let searchTimer;

        function authHeaders(extraHeaders = {}) {
            if (typeof window.crmApplyAuthHeaders === "function") {
                return window.crmApplyAuthHeaders(extraHeaders);
            }

            return extraHeaders;
        }

        function loadLogs() {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">Loading user logs...</td></tr>';

            const params = new URLSearchParams(state);

            fetch(config.indexUrl + "?" + params.toString(), {
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                }),
            })
                .then(parseJson)
                .then(function (payload) {
                    renderRows(payload.data || []);
                    renderSummary(payload.meta || {});
                    renderPagination(payload.meta || {});
                })
                .catch(function (error) {
                    tableBody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-5">' + escapeHtml(error.message || "Failed to load user logs.") + '</td></tr>';
                });
        }

        function renderRows(rows) {
            if (!Array.isArray(rows) || rows.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-5">No user logs found.</td></tr>';
                return;
            }

            tableBody.innerHTML = rows.map(function (row) {
                const actionClass = String(row.taken_action || "").toLowerCase();
                return '<tr>'
                    + '<td class="ps-4">' + escapeHtml(row.actioned_by || "--") + '</td>'
                    + '<td><span class="user-log-module">' + escapeHtml(row.module || "Activity") + '</span></td>'
                    + '<td><span class="user-log-action ' + escapeHtml(actionClass) + '">' + escapeHtml(row.taken_action || "--") + '</span></td>'
                    + '<td><div class="user-log-message">' + escapeHtml(row.message || "--") + '</div><div class="user-log-summary-text">' + escapeHtml(row.summary || "") + '</div></td>'
                    + '<td class="text-nowrap">' + escapeHtml(row.created_at || "--") + '</td>'
                    + '<td class="text-center"><div class="user-log-actions">'
                    + '<button type="button" class="btn btn-dark-blue btn-sm user-logs-clear-btn" data-id="' + escapeHtml(row.id) + '">Clear</button>'
                    + '</div></td>'
                    + '</tr>';
            }).join("");

            tableBody.querySelectorAll("[data-id]").forEach(function (button) {
                button.addEventListener("click", function () {
                    const id = this.getAttribute("data-id");
                    if (id) {
                        clearLog(id);
                    }
                });
            });
        }

        function renderSummary(meta) {
            summary.textContent = "Showing " + (meta.from || 0) + " to " + (meta.to || 0) + " of " + (meta.total || 0) + " entries";
        }

        function renderPagination(meta) {
            const current = Number(meta.current_page || 1);
            const last = Number(meta.last_page || 1);
            const from = Number(meta.from || 0);
            const to = Number(meta.to || 0);
            const total = Number(meta.total || 0);

            if (last <= 1) {
                pagination.innerHTML = '<div class="crm-pagination-container"><ul class="pagination crm-pagination mb-0"><li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li><li class="page-item active"><span class="page-link">1</span></li><li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li></ul></div>';
                return;
            }

            let html = '<div class="crm-pagination-container"><ul class="pagination crm-pagination mb-0">';
            html += pageItem(current - 1, '<i class="bi bi-chevron-left"></i>', current <= 1, false);

            const pages = buildPages(current, last);

            pages.forEach(function (p) {
                if (p === "...") {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    return;
                }

                html += pageItem(p, String(p), false, p === current);
            });

            html += pageItem(current + 1, '<i class="bi bi-chevron-right"></i>', current >= last, false);
            html += '</ul></div>';
            pagination.innerHTML = html;

            pagination.querySelectorAll("[data-page]").forEach(function (link) {
                link.addEventListener("click", function (event) {
                    event.preventDefault();
                    const page = Number(this.getAttribute("data-page"));
                    if (page > 0 && page <= last) {
                        state.page = page;
                        loadLogs();
                    }
                });
            });
        }

        function pageItem(page, label, disabled, active) {
            return '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '">'
                + '<a class="page-link" href="#" data-page="' + page + '">' + label + '</a></li>';
        }

        function clearLog(id) {
            showDeleteConfirm("This user log will be permanently deleted.").then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                fetch(config.destroyBaseUrl + "/" + encodeURIComponent(id), {
                    method: "DELETE",
                    headers: authHeaders({
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    }),
                })
                    .then(parseJson)
                    .then(function (payload) {
                        notify(payload.message || "User log cleared successfully.", "success");
                        loadLogs();
                    })
                    .catch(function (error) {
                        notify(error.message || "Failed to clear user log.", "error");
                    });
            });
        }

        function clearAllLogs() {
            if (!config.destroyAllUrl) {
                return;
            }

            showDeleteConfirm("All user logs will be permanently deleted. This action cannot be undone.", {
                title: "Delete All Logs?",
                confirmButtonText: "Yes, delete all!",
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                fetch(config.destroyAllUrl, {
                    method: "DELETE",
                    headers: authHeaders({
                        "X-Requested-With": "XMLHttpRequest",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN": csrf(),
                    }),
                })
                    .then(parseJson)
                    .then(function (payload) {
                        notify(payload.message || "All user logs cleared successfully.", "success");
                        state.page = 1;
                        loadLogs();
                    })
                    .catch(function (error) {
                        notify(error.message || "Failed to clear user logs.", "error");
                    });
            });
        }

        function openDetail(id) {
            if (!config.showBaseUrl || !detailModal) {
                return;
            }

            fetch(config.showBaseUrl + "/" + encodeURIComponent(id), {
                headers: authHeaders({
                    "X-Requested-With": "XMLHttpRequest",
                    "Accept": "application/json",
                }),
            })
                .then(parseJson)
                .then(function (payload) {
                    renderDetail(payload.data || {});
                    detailModal.show();
                })
                .catch(function (error) {
                    notify(error.message || "Failed to load log details.", "error");
                });
        }

        function renderDetail(data) {
            setText("userLogDetailModule", data.module || "Activity");
            setText("userLogDetailTitle", data.record_name ? data.record_name : "Activity details");
            setText("userLogDetailMeta", (data.actioned_by || "--") + " • " + (data.created_at || "--"));
            setText("userLogDetailMessage", data.message || "--");
            setText("userLogDetailSummary", data.summary || "No summary available.");

            const actionBadge = document.getElementById("userLogDetailAction");
            if (actionBadge) {
                actionBadge.textContent = data.taken_action || "UPDATE";
                actionBadge.className = "badge rounded-pill user-log-action-pill " + String(data.taken_action || "").toLowerCase();
            }

            const groupsWrap = document.getElementById("userLogDetailGroups");
            const emptyState = document.getElementById("userLogDetailEmpty");

            if (!groupsWrap || !emptyState) {
                return;
            }

            const groups = data.groups || {};
            const sections = [
                { key: "added", title: "Added", tone: "success" },
                { key: "updated", title: "Updated", tone: "primary" },
                { key: "deleted", title: "Deleted", tone: "danger" },
            ].filter(function (section) {
                return Array.isArray(groups[section.key]) && groups[section.key].length > 0;
            });

            if (!sections.length) {
                groupsWrap.innerHTML = "";
                emptyState.classList.remove("d-none");
                return;
            }

            emptyState.classList.add("d-none");
            groupsWrap.innerHTML = sections.map(function (section) {
                return '<div class="col-12 col-lg-4">'
                    + '<div class="user-log-detail-card h-100">'
                    + '<div class="user-log-section-title text-' + section.tone + '">' + section.title + '</div>'
                    + groups[section.key].map(function (item) {
                        return '<div class="user-log-change-item">'
                            + '<div class="user-log-change-label">' + escapeHtml(item.label || item.field || "Field") + '</div>'
                            + '<div class="user-log-change-value">' + escapeHtml(item.value || "Not available") + '</div>'
                            + '</div>';
                    }).join("")
                    + '</div></div>';
            }).join("");
        }

        function setText(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }

        function buildPages(current, last) {
            if (last <= 7) {
                return range(1, last);
            }

            if (current <= 4) {
                return [1, 2, 3, 4, 5, "...", last];
            }

            if (current >= last - 3) {
                return [1, "..."].concat(range(last - 4, last));
            }

            return [1, "...", current - 1, current, current + 1, "...", last];
        }

        function range(start, end) {
            const values = [];
            for (let p = start; p <= end; p += 1) {
                values.push(p);
            }
            return values;
        }

        searchInput.addEventListener("input", function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                state.q = searchInput.value || "";
                state.page = 1;
                loadLogs();
            }, 350);
        });

        perPageInput.addEventListener("change", function () {
            state.per_page = perPageInput.value || "10";
            state.page = 1;
            loadLogs();
        });

        if (refreshButton) {
            refreshButton.addEventListener("click", loadLogs);
        }

        if (deleteAllButton) {
            deleteAllButton.addEventListener("click", clearAllLogs);
        }

        loadLogs();
    }

    function parseJson(response) {
        return response.json().catch(function () { return {}; }).then(function (payload) {
            if (!response.ok || payload.success === false) {
                throw new Error(payload.message || "Request failed.");
            }
            return payload;
        });
    }

    function notify(message, type) {
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

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return "";
        }

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
