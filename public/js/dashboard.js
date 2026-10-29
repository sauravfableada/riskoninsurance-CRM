(function () {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initDashboard);
    } else {
        initDashboard();
    }

    const endpoints = {
        stats: "/api/dashboard/stats",
        renewalPolicies: "/api/dashboard/renewal-policies-widget",
        trend: "/api/dashboard/trend",
        customerReport: "/api/dashboard/customer-report",
        insuranceInsights: "/api/dashboard/insurance-insights",
        commissionsWidget: "/api/dashboard/commissions-widget",
    };

    let trendChart = null;
    let customerReportChart = null;
    let policyPortfolioChart = null;
    let policyExpiryChart = null;
    let commissionDistributionChart = null;
    let leadBoardSliderBound = false;

    function initDashboard() {
        if (!document.getElementById("dashboardStats")) {
            return;
        }

        loadStats();
        loadRenewalPolicies();
        loadInsuranceInsights();
        loadCommissionsWidget();
    }

    function notifyError(message) {
        if (typeof window.showAlert === "function") {
            window.showAlert("error", message || "Something went wrong.");
            return;
        }

        console.error(message);
    }

    function getJson(url) {
        const headers = typeof window.crmApplyAuthHeaders === "function"
            ? window.crmApplyAuthHeaders({
                "X-Requested-With": "XMLHttpRequest",
                "Accept": "application/json",
            })
            : {
            "X-Requested-With": "XMLHttpRequest",
            "Accept": "application/json",
        };
        
        // if (token) {
        //     headers["Authorization"] = "Bearer " + token;
        // }
        
        // console.log('Fetching:', url, 'with token:', token ? 'yes' : 'no');
        
        return fetch(url, {
            method: "GET",
            headers: headers,
        }).then(async function (response) {
            // console.log('Response status:', response.status, 'for', url);
            const payload = await response.json().catch(function () { return null; });
            // console.log('Response payload:', payload);
            if (!response.ok) {
                throw new Error(payload && payload.message ? payload.message : "Request failed with status " + response.status);
            }
            return payload;
        });
    }

    function loadStats() {
        getJson(endpoints.stats)
            .then(function (response) {
                // console.log('Stats response:', response);
                const data = response && response.data ? response.data : {};
                setText("metricCustomers", formatNumber(data.customers));
                setText("metricTotalPolicies", formatNumber(data.total_policies));
                setText("metricActivePolicies", formatNumber(data.active_policies));
                setText("metricRenewalDue", formatNumber(data.renewal_due));
            })
            .catch(function (error) {
                console.error('Stats error:', error);
                notifyError("Failed to load dashboard stats.");
            });
    }

    function loadLeadBoard() {
        const container = document.getElementById("leadBoardContainer");
        if (!container) {
            return;
        }

        getJson(endpoints.leadBoard)
            .then(function (response) {
                const stages = response && response.data ? response.data : [];
                const themes = ["indigo", "slate", "green", "orange"];

                if (!Array.isArray(stages) || stages.length === 0) {
                    container.innerHTML = '<div class="card border-0 shadow-sm w-100"><div class="card-body text-muted small">No lead stages available.</div></div>';
                    return;
                }

                container.innerHTML = stages.map(function (stage, index) {
                    const theme = themes[index % themes.length];
                    const lead = Array.isArray(stage.leads) && stage.leads.length ? stage.leads[0] : null;

                    if (!lead) {
                        return '<div class="status-column status-column--' + theme + '">'
                            + '<div class="status-column__head status-column__head--' + theme + '">'
                            + '<span>' + escapeHtml(stage.name || "Stage") + '</span>'
                            + '<span class="status-column__count">' + formatNumber(stage.count || 0) + '</span>'
                            + '</div>'
                            + '<div class="status-column__body status-column__body--empty">'
                            + '<div class="status-column__empty">No leads available.</div>'
                            + '</div>'
                            + '</div>';
                    }

                    return '<div class="status-column status-column--' + theme + '">'
                        + '<div class="status-column__head status-column__head--' + theme + '">'
                        + '<span>' + escapeHtml(stage.name || "Stage") + '</span>'
                        + '<span class="status-column__count">' + formatNumber(stage.count || 0) + '</span>'
                        + '</div>'
                        + '<div class="status-column__body status-column__body--filled">'
                        + '<div class="status-lead-card">'
                        + '<div class="status-lead-card__body">'
                        + '<div class="status-lead-name">' + escapeHtml(lead.name || "--") + '</div>'
                        + '<div class="status-lead-row"><i class="bi bi-envelope-fill"></i><span>' + escapeHtml(lead.email || "--") + '</span></div>'
                        + '<div class="status-lead-row"><i class="bi bi-telephone-fill"></i><span>' + escapeHtml(lead.phone || "--") + '</span></div>'
                        + '<div class="status-lead-row"><i class="bi bi-person-plus-fill"></i><span>' + escapeHtml(lead.assigned_to || "Unassigned") + '</span></div>'
                        + '<div class="status-lead-row"><i class="bi bi-calendar-event-fill"></i><span>' + escapeHtml(formatDate(lead.created_at)) + '</span></div>'
                        + '</div>'
                        + '<div class="status-lead-card__footer">'
                        + '<div class="status-lead-actions">'
                        + '<a href="/leads/' + encodeURIComponent(lead.id) + '" class="status-lead-btn" title="View"><i class="bi bi-eye"></i></a>'
                        + '<a href="/leads/' + encodeURIComponent(lead.id) + '/edit" class="status-lead-btn" title="Edit"><i class="bi bi-pencil-square"></i></a>'
                        + '<a href="/follow-ups/create?lead_id=' + encodeURIComponent(lead.id) + '" class="status-lead-btn" title="Follow Up"><i class="bi bi-whatsapp"></i></a>'
                        + '</div>'
                        + '<button type="button" class="status-lead-more" title="More"><i class="bi bi-three-dots-vertical"></i></button>'
                        + '</div>'
                        + '</div>'
                        + '</div>'
                        + '</div>';
                }).join("");
            })
            .catch(function (error) {
                console.error('Failed to load lead board:', error);
                container.innerHTML = '<div class="card border-0 shadow-sm w-100"><div class="card-body text-danger small">Failed to load lead board.</div></div>';
            })
            .finally(function () {
                initLeadBoardSlider();
            });
    }

    function initLeadBoardSlider() {
        var container = document.getElementById("leadBoardContainer");
        var btnLeft = document.getElementById("leadBoardLeft");
        var btnRight = document.getElementById("leadBoardRight");

        if (!container || !btnLeft || !btnRight) {
            return;
        }

        function updateArrows() {
            var scrollLeft = Math.round(container.scrollLeft);
            var maxScroll = container.scrollWidth - container.clientWidth;
            btnLeft.disabled = scrollLeft <= 0;
            btnRight.disabled = scrollLeft >= maxScroll - 1;
        }

        function getScrollAmount() {
            var firstCol = container.querySelector(".status-column");
            if (firstCol) {
                return firstCol.offsetWidth + 14;
            }
            return container.clientWidth * 0.85;
        }

        if (!leadBoardSliderBound) {
            btnLeft.addEventListener("click", function () {
                container.scrollBy({ left: -getScrollAmount(), behavior: "smooth" });
            });

            btnRight.addEventListener("click", function () {
                container.scrollBy({ left: getScrollAmount(), behavior: "smooth" });
            });

            container.addEventListener("scroll", updateArrows);
            window.addEventListener("resize", updateArrows);
            leadBoardSliderBound = true;
        }

        updateArrows();
    }

    function loadRenewalPolicies() {
        const tbody = document.querySelector("#dashboardRenewalPoliciesTable tbody");
        const modalBody = document.getElementById("renewalPoliciesModalBody");
        if (!tbody) {
            return;
        }

        getJson(endpoints.renewalPolicies)
            .then(function (response) {
                const policies = response && response.data ? response.data : [];

                renderRenewalModal(modalBody, policies);

                if (!Array.isArray(policies) || policies.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No policies due for renewal in the next 30 days.</td></tr>';
                    return;
                }

                function policyNumberBadges(policy) {
                    const numbers = [policy.policy_number_no]
                        .filter(function (number) {
                            return Boolean(number);
                        });

                    if (!numbers.length) {
                        return '<span class="text-muted">--</span>';
                    }

                    return '<div class="policy-number-badges">' + numbers.map(function (number) {
                        return '<span class="policy-number-badge">' + escapeHtml(number) + '</span>';
                    }).join('') + '</div>';
                }

                tbody.innerHTML = policies.map(function (policy) {
                    const days = Number(policy.days_remaining || 0);
                    const dueLabel = days === 0 ? "Due today" : days + (days === 1 ? " day" : " days");

                    return '<tr class="renewal-policy-main-row" data-view-url="' + escapeHtml(policy.view_url || "") + '">'
                        + '<td>' + policyNumberBadges(policy) + '</td>'
                        + '<td class="d-none d-md-table-cell">' + escapeHtml(policy.proposer || "--") + '</td>'
                        + '<td class="text-center">' + escapeHtml(policy.end_date || "--") + '</td>'
                        + '<td class="text-center d-none d-md-table-cell"><span class="badge-priority ' + (days <= 7 ? 'high' : 'medium') + ' renewal-due-soon">' + escapeHtml(dueLabel) + '</span></td>'
                        + '<td class="text-center d-md-none">'
                        + '<button type="button" class="btn-task-expand" data-target="' + policy.id + '">'
                        + '<i class="fa-solid fa-plus"></i>'
                        + '</button>'
                        + '</td>'
                        + '</tr>'
                        + '<tr class="task-expand-row d-md-none" id="renewal-policy-expand-' + policy.id + '" style="display: none;">'
                        + '<td colspan="5">'
                        + '<div class="task-expand-content">'
                        + '<div class="row g-3">'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-user"></i> Proposer Name :</div>'
                        + '<div class="expand-value">' + escapeHtml(policy.proposer || "--") + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-shield-halved"></i> Policy Type :</div>'
                        + '<div class="expand-value">' + escapeHtml(policy.policy_type || "--") + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-building"></i> Company :</div>'
                        + '<div class="expand-value">' + escapeHtml(policy.company || "--") + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-clock"></i> Due In :</div>'
                        + '<div class="expand-value">' + escapeHtml(dueLabel) + '</div>'
                        + '</div>'
                        + '</div>'
                        + '</div>'
                        + '</td>'
                        + '</tr>';
                }).join("");

                tbody.querySelectorAll(".renewal-policy-main-row").forEach(function (row) {
                    row.style.cursor = "pointer";
                    row.addEventListener("click", function (e) {
                        if (e.target.closest(".btn-task-expand")) {
                            return; // Don't navigate if clicking the expand button
                        }
                        const viewUrl = this.getAttribute("data-view-url");
                        if (viewUrl) {
                            window.location.href = viewUrl;
                        }
                    });
                });

                // Handle expand button click
                tbody.querySelectorAll(".btn-task-expand").forEach(function (btn) {
                    btn.addEventListener("click", function (e) {
                        e.stopPropagation();
                        const policyId = this.getAttribute("data-target");
                        const expandRow = document.getElementById("renewal-policy-expand-" + policyId);
                        const icon = this.querySelector("i");
                        
                        if (expandRow) {
                            const isVisible = expandRow.style.display !== "none";
                            expandRow.style.display = isVisible ? "none" : "table-row";
                            
                            if (icon) {
                                icon.classList.remove(isVisible ? "fa-minus" : "fa-plus");
                                icon.classList.add(isVisible ? "fa-plus" : "fa-minus");
                            }
                            
                            this.classList.toggle("active", !isVisible);
                        }
                    });
                });
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Failed to load renewal policies.</td></tr>';
                if (modalBody) {
                    modalBody.innerHTML = '<div class="text-center text-danger py-4">Renewal policies could not be loaded. Please try again.</div>';
                }
            })
            .finally(function () {
                showRenewalModalAfterLogin();
            });
    }

    function renderRenewalModal(container, policies) {
        if (!container) {
            return;
        }

        if (!Array.isArray(policies) || policies.length === 0) {
            container.innerHTML = '<div class="text-center py-4">'
                + '<i class="fa-solid fa-circle-check text-success fs-2 mb-3"></i>'
                + '<div class="fw-bold">No renewals due</div>'
                + '<div class="text-muted small">There are no policies expiring in the next 30 days.</div>'
                + '</div>';
            return;
        }

        container.innerHTML = '<div class="d-grid gap-3">' + policies.map(function (policy) {
            const days = Number(policy.days_remaining || 0);
            const dueLabel = days === 0 ? 'Due today' : 'Due in ' + days + (days === 1 ? ' day' : ' days');
            const policyNumber = policy.policy_number_no || policy.policy_number || '--';

            return '<a class="renewal-reminder-item d-flex flex-column flex-sm-row justify-content-between align-items-sm-center text-decoration-none" href="' + escapeHtml(policy.view_url || '#') + '">'
                + '<div><div class="renewal-reminder-item-name">' + escapeHtml(policy.proposer || 'Unknown policy holder') + '</div>'
                + '<div class="renewal-reminder-item-meta">' + escapeHtml(policyNumber) + ' &middot; ' + escapeHtml(policy.policy_type || 'Policy') + '</div>'
                + '</div>'
                + '<div class="renewal-reminder-date-wrap text-end"><div class="renewal-reminder-date">' + escapeHtml(policy.end_date || '--') + '</div>'
                + '<div class="renewal-reminder-due">' + escapeHtml(dueLabel) + '</div></div>'
                + '</a>';
        }).join('') + '</div>';
    }

    function showRenewalModalAfterLogin(attempt) {
        const page = document.getElementById('dashboardPage');
        const modalElement = document.getElementById('renewalPoliciesModal');
        const serverRequested = page && page.dataset.showRenewalModal === '1';
        const browserRequested = sessionStorage.getItem('show_renewal_policies_modal') === '1';

        if (!page || (!serverRequested && !browserRequested) || !modalElement) {
            return;
        }

        if (typeof window.bootstrap === 'undefined' || !window.bootstrap.Modal) {
            if ((attempt || 0) < 20) {
                setTimeout(function () {
                    showRenewalModalAfterLogin((attempt || 0) + 1);
                }, 100);
            }
            return;
        }

        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
        page.dataset.showRenewalModal = '0';
        sessionStorage.removeItem('show_renewal_policies_modal');
    }

    function loadTrendChart() {
        const canvas = document.getElementById("dashboardTrendChart");
        if (!canvas) {
            return;
        }

        // Wait for Chart to be available
        if (typeof window.Chart === "undefined") {
            setTimeout(loadTrendChart, 100);
            return;
        }

        getJson(endpoints.trend)
            .then(function (response) {
                const data = response && response.data ? response.data : {};
                const labels = Array.isArray(data.labels) ? data.labels : [];
                const datasets = data.datasets || {};

                if (trendChart) {
                    trendChart.destroy();
                }

                trendChart = new Chart(canvas, {
                    type: "line",
                    data: {
                        labels: labels,
                        datasets: [
                            buildDataset("Leads", datasets.leads, "#3B5BDB"),
                            buildDataset("Follow Up", datasets.followups, "#F43F5E"),
                            buildDataset("Customer", datasets.customers, "#475569"),
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: "index", intersect: false },
                        plugins: {
                            legend: { position: "top" },
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                                grid: { color: "rgba(148, 163, 184, 0.2)" },
                            },
                            x: {
                                grid: { color: "rgba(148, 163, 184, 0.15)" },
                            },
                        },
                    },
                });
            })
            .catch(function (error) {
                console.error("Failed to load dashboard chart:", error);
                notifyError("Failed to load dashboard chart.");
            });
    }

    function loadCustomerReport(year) {
        const canvas = document.getElementById("customerReportChart");
        if (!canvas) {
            return;
        }

        // Wait for Chart to be available
        if (typeof window.Chart === "undefined") {
            setTimeout(() => loadCustomerReport(year), 100);
            return;
        }

        const selectedYear = year || (document.getElementById("customerReportYear") ? document.getElementById("customerReportYear").value : "");
        const url = selectedYear ? (endpoints.customerReport + "?year=" + encodeURIComponent(selectedYear)) : endpoints.customerReport;

        getJson(url)
            .then(function (response) {
                const data = response && response.data ? response.data : {};
                const labels = Array.isArray(data.labels) ? data.labels : [];
                const series = Array.isArray(data.series) ? data.series : [];

                if (customerReportChart) {
                    customerReportChart.destroy();
                }

                customerReportChart = new Chart(canvas, {
                    type: "line",
                    data: {
                        labels: labels,
                        datasets: [{
                            label: "Customers",
                            data: series,
                            borderColor: "#3B5BDB",
                            backgroundColor: "rgba(59,91,219,.10)",
                            fill: true,
                            tension: 0.35,
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0 },
                                grid: { color: "rgba(148, 163, 184, .2)" },
                            },
                            x: { grid: { display: false } },
                        },
                    },
                });
            })
            .catch(function (error) {
                console.error("Failed to load customer report:", error);
                notifyError("Failed to load customer report.");
            });
    }

    function loadInsuranceInsights() {
        const portfolioCanvas = document.getElementById("policyPortfolioChart");
        const expiryCanvas = document.getElementById("policyExpiryChart");
        const insurerBody = document.querySelector("#insurerPerformanceTable tbody");

        if (!portfolioCanvas && !expiryCanvas && !insurerBody) {
            return;
        }
        if (typeof window.Chart === "undefined") {
            setTimeout(loadInsuranceInsights, 100);
            return;
        }

        getJson(endpoints.insuranceInsights)
            .then(function (response) {
                const data = response && response.data ? response.data : {};
                renderPortfolioMix(
                    portfolioCanvas,
                    Array.isArray(data.portfolio) ? data.portfolio : [],
                    data.portfolio_summary || {}
                );
                renderExpiryExposure(expiryCanvas, Array.isArray(data.expiry_buckets) ? data.expiry_buckets : []);
                renderInsurerPerformance(insurerBody, Array.isArray(data.insurers) ? data.insurers : []);

                if (data.commissions || data.commission_summary) {
                    const commissionCanvas = document.getElementById("commissionDistributionChart");
                    const commissionBody = document.querySelector("#staffSubAgentCommissionTable tbody");
                    if (commissionBody && Array.isArray(data.commissions)) {
                        renderCommissionRecords(commissionBody, data.commissions);
                    }
                    if (commissionCanvas && data.commission_summary) {
                        renderCommissionDistribution(
                            commissionCanvas,
                            Array.isArray(data.commission_summary.distribution) ? data.commission_summary.distribution : [],
                            data.commission_summary
                        );
                    }
                }
            })
            .catch(function (error) {
                console.error("Failed to load insurance insights:", error);
                if (insurerBody) {
                    insurerBody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-4">Failed to load insurer performance.</td></tr>';
                }
                notifyError("Failed to load insurance dashboard insights.");
            });
    }

    function renderPortfolioMix(canvas, rows, summary) {
        if (!canvas) return;
        if (policyPortfolioChart) policyPortfolioChart.destroy();
        const colors = ["#2563EB", "#06B6D4", "#10B981", "#F59E0B", "#F43F5E", "#8B5CF6", "#64748B"];
        const totalPolicies = Number(summary.total_policies || 0);
        const legend = document.getElementById("policyPortfolioLegend");
        const totalPremium = document.getElementById("portfolioTotalPremium");
        const topSegment = document.getElementById("portfolioTopSegment");

        if (totalPremium) {
            totalPremium.textContent = formatCompactCurrency(summary.total_premium);
        }
        if (topSegment) {
            topSegment.textContent = (summary.top_category || "--") + " · " + formatNumber(summary.top_category_share) + "%";
            topSegment.title = summary.top_category || "";
        }
        if (legend) {
            legend.innerHTML = rows.length ? rows.map(function (row, index) {
                const share = totalPolicies ? Math.round((Number(row.policies) / totalPolicies) * 100) : 0;
                return '<div class="portfolio-category-row">'
                    + '<span class="portfolio-category-dot" style="background:' + colors[index % colors.length] + '"></span>'
                    + '<div class="portfolio-category-name" title="' + escapeHtml(row.name) + '">' + escapeHtml(row.name) + '</div>'
                    + '<div class="portfolio-category-value"><strong>' + formatNumber(row.policies) + '</strong><small>' + share + '%</small></div>'
                    + '</div>';
            }).join("") : '<div class="portfolio-empty">No policy data available.</div>';
        }

        const centerTextPlugin = {
            id: "portfolioCenterText",
            afterDraw: function (chart) {
                if (!chart.chartArea) return;
                const ctx = chart.ctx;
                const x = (chart.chartArea.left + chart.chartArea.right) / 2;
                const y = (chart.chartArea.top + chart.chartArea.bottom) / 2;
                ctx.save();
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillStyle = "#0F172A";
                ctx.font = "700 24px Arial";
                ctx.fillText(formatNumber(totalPolicies), x, y - 7);
                ctx.fillStyle = "#64748B";
                ctx.font = "600 10px Arial";
                ctx.fillText("TOTAL POLICIES", x, y + 14);
                ctx.restore();
            },
        };

        policyPortfolioChart = new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: rows.map(function (row) { return row.name; }),
                datasets: [{
                    data: rows.map(function (row) { return row.policies; }),
                    backgroundColor: colors,
                    borderColor: "#FFFFFF",
                    borderWidth: 3,
                    hoverOffset: 5,
                }],
            },
            plugins: [centerTextPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "68%",
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const row = rows[context.dataIndex] || {};
                                return " " + formatNumber(row.policies) + " policies | " + formatCompactCurrency(row.premium);
                            },
                        },
                    },
                },
            },
        });
    }

    function renderExpiryExposure(canvas, rows) {
        if (!canvas) return;
        if (policyExpiryChart) policyExpiryChart.destroy();

        policyExpiryChart = new Chart(canvas, {
            type: "bar",
            data: {
                labels: rows.map(function (row) { return row.label; }),
                datasets: [
                    {
                        label: "Policies",
                        data: rows.map(function (row) { return row.policies; }),
                        backgroundColor: ["#EF4444", "#F97316", "#F59E0B", "#06B6D4", "#2563EB"],
                        borderRadius: 7,
                        yAxisID: "y",
                    },
                    {
                        label: "Premium exposure",
                        data: rows.map(function (row) { return row.premium; }),
                        type: "line",
                        borderColor: "#0F172A",
                        backgroundColor: "#0F172A",
                        tension: 0.35,
                        pointRadius: 4,
                        yAxisID: "premium",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: "index", intersect: false },
                plugins: {
                    legend: { position: "top" },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return context.datasetIndex === 1
                                    ? " Premium: " + formatCompactCurrency(context.raw)
                                    : " Policies: " + formatNumber(context.raw);
                            },
                        },
                    },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: "rgba(148,163,184,.2)" } },
                    premium: {
                        beginAtZero: true,
                        position: "right",
                        grid: { drawOnChartArea: false },
                        ticks: { callback: function (value) { return formatCompactCurrency(value); } },
                    },
                    x: { grid: { display: false } },
                },
            },
        });
    }

    function renderInsurerPerformance(tbody, rows) {
        if (!tbody) return;
        if (!rows.length) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">No policy data available.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(function (row, index) {
            const activeRate = row.policies ? Math.round((Number(row.active) / Number(row.policies)) * 100) : 0;
            return '<tr>'
                + '<td><div class="insurer-rank"><span>' + (index + 1) + '</span><strong title="' + escapeHtml(row.name) + '">' + escapeHtml(row.name) + '</strong></div></td>'
                + '<td class="text-center fw-semibold">' + formatNumber(row.policies) + '</td>'
                + '<td class="text-center"><span class="insurer-active-rate">' + activeRate + '%</span></td>'
                + '<td class="text-end fw-semibold">' + formatCompactCurrency(row.premium) + '</td>'
                + '</tr>';
        }).join("");
    }

    function loadCommissionsWidget() {
        const commissionCanvas = document.getElementById("commissionDistributionChart");
        const commissionBody = document.querySelector("#staffSubAgentCommissionTable tbody");

        if (!commissionCanvas && !commissionBody) {
            return;
        }

        if (typeof window.Chart === "undefined") {
            setTimeout(loadCommissionsWidget, 100);
            return;
        }

        getJson(endpoints.commissionsWidget)
            .then(function (response) {
                const data = response && response.data ? response.data : {};
                renderCommissionRecords(commissionBody, Array.isArray(data.commissions) ? data.commissions : []);
                renderCommissionDistribution(
                    commissionCanvas,
                    Array.isArray(data.summary?.distribution) ? data.summary.distribution : [],
                    data.summary || {}
                );
            })
            .catch(function (error) {
                console.error("Failed to load commissions widget:", error);
                if (commissionBody) {
                    commissionBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No commission records available.</td></tr>';
                }
            });
    }

    function renderCommissionDistribution(canvas, rows, summary) {
        if (!canvas) return;
        if (commissionDistributionChart) commissionDistributionChart.destroy();
        const colors = ["#10B981", "#2563EB", "#F59E0B", "#06B6D4", "#8B5CF6", "#F43F5E", "#64748B"];
        const totalProfit = Number(summary.total_admin_profit || 0);
        const totalAdminComm = Number(summary.total_admin_commission || 0);
        const legend = document.getElementById("commissionDistributionLegend");

        // Update the 5 metric cards matching Image 2
        const totalPolEl = document.getElementById("commWidgetTotalPolicies");
        const grossPremEl = document.getElementById("commWidgetGrossPremium");
        const adminCommEl = document.getElementById("commWidgetAdminCommission");
        const staffCommEl = document.getElementById("commWidgetStaffCommission");
        const adminProfitEl = document.getElementById("commWidgetAdminProfit");

        if (totalPolEl) totalPolEl.textContent = formatNumber(summary.total_policies || 0);
        if (grossPremEl) grossPremEl.textContent = "₹" + formatCurrency(summary.total_gross_premium || 0);
        if (adminCommEl) adminCommEl.textContent = "₹" + formatCurrency(summary.total_admin_commission || 0);
        if (staffCommEl) staffCommEl.textContent = "₹" + formatCurrency(summary.total_staff_commission || 0);
        if (adminProfitEl) adminProfitEl.textContent = "₹" + formatCurrency(summary.total_admin_profit || 0);

        if (legend) {
            legend.innerHTML = rows.length ? rows.map(function (row, index) {
                const base = totalAdminComm > 0 ? totalAdminComm : (totalProfit + Number(summary.total_staff_commission || 0));
                const share = base ? Math.round((Number(row.amount) / base) * 100) : (row.share || 0);
                return '<div class="portfolio-category-row">'
                    + '<span class="portfolio-category-dot" style="background:' + (row.color || colors[index % colors.length]) + '"></span>'
                    + '<div class="portfolio-category-name" title="' + escapeHtml(row.name) + '">' + escapeHtml(row.name) + '</div>'
                    + '<div class="portfolio-category-value"><strong>' + formatCompactCurrency(row.amount) + '</strong><small>' + share + '%</small></div>'
                    + '</div>';
            }).join("") : '<div class="portfolio-empty">No commission data available.</div>';
        }

        const centerTextPlugin = {
            id: "commissionCenterText",
            afterDraw: function (chart) {
                if (!chart.chartArea) return;
                const ctx = chart.ctx;
                const x = (chart.chartArea.left + chart.chartArea.right) / 2;
                const y = (chart.chartArea.top + chart.chartArea.bottom) / 2;
                const isDark = document.documentElement.getAttribute("data-theme") === "dark";
                ctx.save();
                ctx.textAlign = "center";
                ctx.textBaseline = "middle";
                ctx.fillStyle = isDark ? "#F8FAFC" : "#0F172A";
                ctx.font = "700 20px Arial";
                ctx.fillText(formatCompactCurrency(totalProfit), x, y - 7);
                ctx.fillStyle = isDark ? "#94A3B8" : "#64748B";
                ctx.font = "600 10px Arial";
                ctx.fillText("NET PROFIT", x, y + 14);
                ctx.restore();
            },
        };

        const chartData = rows.length && (totalProfit > 0 || totalAdminComm > 0)
            ? rows.map(function (r) { return Number(r.amount || 0); })
            : [1];
        const chartColors = rows.length && (totalProfit > 0 || totalAdminComm > 0)
            ? rows.map(function (r, i) { return r.color || colors[i % colors.length]; })
            : ["#E2E8F0"];
        const chartLabels = rows.length && (totalProfit > 0 || totalAdminComm > 0)
            ? rows.map(function (r) { return r.name; })
            : ["No Data"];

        commissionDistributionChart = new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: chartColors,
                    borderColor: "#FFFFFF",
                    borderWidth: 3,
                    hoverOffset: 5,
                }],
            },
            plugins: [centerTextPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "68%",
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                if (totalProfit <= 0 && totalAdminComm <= 0) return " No commission data";
                                const row = rows[context.dataIndex] || {};
                                return " " + formatCompactCurrency(row.amount) + " (" + (row.share || 0) + "%)";
                            },
                        },
                    },
                },
            },
        });
    }

    function renderCommissionRecords(tbody, rows) {
        if (!tbody) return;
        if (!rows || !rows.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No commission records available.</td></tr>';
            return;
        }

        tbody.innerHTML = rows.map(function (row, index) {
            const isStaff = (row.role_type === 'staff');
            const roleBadgeClass = isStaff
                ? 'badge bg-success-subtle text-success border border-success-subtle'
                : 'badge bg-primary-subtle text-primary border border-primary-subtle';

            const statusLower = (row.status || '').toLowerCase();
            const statusBadge = statusLower === 'paid'
                ? '<span class="insurer-active-rate">Paid</span>'
                : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="border-radius: 999px; padding: 4px 9px; font-weight: 700; font-size: 0.7rem;">Pending</span>';

            return '<tr>'
                + '<td>'
                +   '<div class="insurer-rank">'
                +     '<span>' + (index + 1) + '</span>'
                +     '<div class="text-truncate" style="max-width: 175px;">'
                +       '<strong class="d-block text-truncate" title="' + escapeHtml(row.name) + '">' + escapeHtml(row.name) + '</strong>'
                +       '<span class="' + roleBadgeClass + '" style="font-size: 0.65rem; padding: 1px 6px;">' + escapeHtml(row.role || 'Agent') + '</span>'
                +     '</div>'
                +   '</div>'
                + '</td>'
                + '<td>'
                +   '<strong class="d-block text-dark text-truncate" style="max-width: 160px;" title="' + escapeHtml(row.policy_number) + '">' + escapeHtml(row.policy_number) + '</strong>'
                +   '<small class="text-muted d-block text-truncate" style="max-width: 160px;" title="' + escapeHtml(row.company) + '">' + escapeHtml(row.company) + '</small>'
                + '</td>'
                + '<td class="text-end fw-semibold">' + formatCompactCurrency(row.premium) + '</td>'
                + '<td class="text-end">'
                +   '<strong class="text-dark d-block">' + formatCompactCurrency(row.commission_amount) + '</strong>'
                +   '<small class="text-muted">' + (row.commission_percentage ? row.commission_percentage + '%' : '') + '</small>'
                + '</td>'
                + '<td class="text-end">'
                +   '<strong class="text-success d-block">' + formatCompactCurrency(row.admin_profit) + '</strong>'
                +   '<small class="text-muted" style="font-size: 0.65rem;">Profit</small>'
                + '</td>'
                + '<td class="text-center">' + statusBadge + '</td>'
                + '</tr>';
        }).join("");
    }

    function loadDealsWidget() {
        const tbody = document.querySelector("#dashboardDealsTable tbody");
        if (!tbody) {
            return;
        }

        getJson(endpoints.dealsWidget)
            .then(function (response) {
                const deals = response && response.data ? response.data : [];
                if (!Array.isArray(deals) || deals.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No deals found.</td></tr>';
                    return;
                }

                tbody.innerHTML = deals.map(function (deal) {
                    const statusName = deal && deal.status && typeof deal.status === "object"
                        ? (deal.status.name || "--")
                        : (deal.status || "--");
                    const normalized = statusName.toLowerCase().replace(/\s+/g, "_").replace(/-/g, "_");
                    const probability = (deal.probability || 0).toString() + "%";
                    const dealCustomerName = deal.customer || deal.customer_name || "--";
                    const dealAssignedTo = deal.assigned_to || deal.assigned_user || "--";
                    const dealExpectedCloseDate = deal.expected_close_date || "--";

                    return '<tr class="deal-main-row" data-deal-id="' + deal.id + '">'
                        + '<td>' + escapeHtml(deal.name || deal.title || "--") + '</td>'
                        + '<td class="d-none d-md-table-cell">₹' + formatCurrency(deal.amount) + '</td>'
                        + '<td class="text-center">' + escapeHtml(probability) + '</td>'
                        + '<td class="text-center d-none d-md-table-cell"><span class="badge-status ' + escapeHtml(normalized) + '">' + escapeHtml(statusName.replace(/_/g, "-").toUpperCase()) + '</span></td>'
                        + '<td class="text-center d-md-none">'
                        + '<button type="button" class="btn-deal-expand" data-target="' + deal.id + '">'
                        + '<i class="fa-solid fa-plus"></i>'
                        + '</button>'
                        + '</td>'
                        + '</tr>'
                        + '<tr class="deal-expand-row d-md-none" id="deal-expand-' + deal.id + '" style="display: none;">'
                        + '<td colspan="5">'
                        + '<div class="task-expand-content">'
                        + '<div class="row g-3">'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-building"></i> Customer Name :</div>'
                        + '<div class="expand-value">' + escapeHtml(dealCustomerName) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Amount :</div>'
                        + '<div class="expand-value">₹' + formatCurrency(deal.amount) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-user-tie"></i> Assigned User :</div>'
                        + '<div class="expand-value">' + escapeHtml(dealAssignedTo) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-calendar-day"></i> Expected Close Date :</div>'
                        + '<div class="expand-value">' + escapeHtml(dealExpectedCloseDate) + '</div>'
                        + '</div>'
                        + '<div class="col-12 d-flex justify-content-between align-items-center">'
                        + '<div class="expand-label"><i class="fa-solid fa-circle-info"></i> Status :</div>'
                        + '<div><span class="badge-status ' + escapeHtml(normalized) + '">' + escapeHtml(statusName.replace(/_/g, "-").toUpperCase()) + '</span></div>'
                        + '</div>'
                        + '</div>'
                        + '</div>'
                        + '</td>'
                        + '</tr>';
                }).join("");

                tbody.querySelectorAll(".deal-main-row").forEach(function (row) {
                    row.style.cursor = "pointer";
                    row.addEventListener("click", function (e) {
                        if (e.target.closest(".btn-deal-expand")) {
                            return;
                        }
                        const id = this.getAttribute("data-deal-id");
                        if (id) {
                            window.location.href = "/deals/" + id;
                        }
                    });
                });

                // Handle expansion
                tbody.querySelectorAll(".btn-deal-expand").forEach(function (btn) {
                    btn.addEventListener("click", function (e) {
                        e.stopPropagation();
                        const dealId = this.getAttribute("data-target");
                        const expandRow = document.getElementById("deal-expand-" + dealId);
                        const icon = this.querySelector("i");

                        if (expandRow) {
                            const isVisible = expandRow.style.display !== "none";
                            expandRow.style.display = isVisible ? "none" : "table-row";

                            if (icon) {
                                icon.classList.remove(isVisible ? "fa-minus" : "fa-plus");
                                icon.classList.add(isVisible ? "fa-plus" : "fa-minus");
                            }

                            this.classList.toggle("active", !isVisible);
                        }
                    });
                });
            })
            .catch(function () {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load deals.</td></tr>';
            });
    }

    function buildDataset(label, values, color) {
        return {
            label: label,
            data: Array.isArray(values) ? values : [],
            borderColor: color,
            backgroundColor: color + "22",
            fill: false,
            tension: 0.4,
            pointRadius: 3,
            pointHoverRadius: 5,
            borderWidth: 2,
        };
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    }

    function formatNumber(value) {
        const n = Number(value || 0);
        return Number.isFinite(n) ? n.toLocaleString("en-IN") : "0";
    }

    function formatCurrency(value) {
        const n = Number(value || 0);
        return Number.isFinite(n)
            ? n.toLocaleString("en-IN", { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : "0.00";
    }

    function formatCompactCurrency(value) {
        const n = Number(value || 0);
        return "₹" + (Number.isFinite(n)
            ? n.toLocaleString("en-IN", { notation: "compact", maximumFractionDigits: 1 })
            : "0");
    }

    function formatDate(value) {
        if (!value) {
            return "--";
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return "--";
        }

        return date.toLocaleString("en-GB", {
            day: "2-digit",
            month: "short",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        });
    }

    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return "";
        }

        return String(value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
