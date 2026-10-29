<!-- Modal: Add Policy Type -->
<div class="modal fade" id="modalAddPolicyType" tabindex="-1" aria-labelledby="modalAddPolicyTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalAddPolicyTypeLabel">Add Policy Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddPolicyType" action="{{ url('policy-type/store') }}" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Policy Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="policy_type_name" id="modal_policy_type_name" placeholder="e.g. HEALTH INSURANCE, MOTOR" required>
                        <div class="invalid-feedback" id="policy_type_name-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="policy_description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="btnSavePolicyType">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Policy Sub Type -->
<div class="modal fade" id="modalAddPolicySubType" tabindex="-1" aria-labelledby="modalAddPolicySubTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalAddPolicySubTypeLabel">Add Policy Sub Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAddPolicySubType" action="{{ url('policy-sub-type/store') }}" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Policy Type <span class="text-danger">*</span></label>
                        <select name="type_id" id="modal_sub_type_policy_type_id" class="form-select" required>
                            <option value="">-- Select Policy Type --</option>
                            @foreach($policyTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="type_id-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sub Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="modal_sub_type_name" placeholder="e.g. COMPREHENSIVE, FLOATER" required>
                        <div class="invalid-feedback" id="sub_type_name-error"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue" id="btnSavePolicySubType">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function initPolicyTypeModals() {
        if (!window.jQuery) {
            setTimeout(initPolicyTypeModals, 50);
            return;
        }

        var $ = window.jQuery;
        if (window.__crmPolicyModalsInitialized) return;
        window.__crmPolicyModalsInitialized = true;

        function getAuthHeaders(extra) {
            extra = extra || {};
            if (typeof window.crmApplyAuthHeaders === "function") {
                return window.crmApplyAuthHeaders(extra);
            }
            var csrf = $('meta[name="csrf-token"]').attr("content") || "";
            return Object.assign({
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": csrf,
                "Accept": "application/json"
            }, extra);
        }

        function syncSubTypeModalPolicyType() {
            var currentTypeId = $("#policy_type_id").val();
            if (currentTypeId) {
                $("#modal_sub_type_policy_type_id").val(currentTypeId);
            }
        }

        $(document).on("click", '[data-bs-target="#modalAddPolicySubType"]', syncSubTypeModalPolicyType);
        $(document).on("show.bs.modal", "#modalAddPolicySubType", syncSubTypeModalPolicyType);

        var inFlightSubTypesXhr = null;

        function fetchAndPopulateSubTypes(policyTypeId, selectId) {
            var $subSelect = $("#policy_sub_type_id");
            if (!$subSelect.length) return;

            if (inFlightSubTypesXhr && inFlightSubTypesXhr.readyState !== 4) {
                inFlightSubTypesXhr.abort();
            }

            if (!policyTypeId) {
                $subSelect.html('<option value="">-- Select Policy Sub Type --</option>');
                return;
            }

            $subSelect.html('<option value="">Loading sub-types...</option>');

            inFlightSubTypesXhr = $.ajax({
                url: "/policy-sub-types/" + encodeURIComponent(policyTypeId),
                type: "GET",
                dataType: "json",
                headers: getAuthHeaders(),
                success: function (data) {
                    var html = '<option value="">-- Select Policy Sub Type --</option>';
                    var seenNames = {};
                    var foundSelected = false;
                    if (Array.isArray(data)) {
                        data.forEach(function (st) {
                            var nameTrimmed = (st.name || "").trim();
                            var nameLower = nameTrimmed.toLowerCase();
                            if (nameTrimmed && !seenNames[nameLower]) {
                                seenNames[nameLower] = true;
                                var isSel = false;
                                if (selectId) {
                                    if (String(st.id) === String(selectId) || nameLower === String(selectId).toLowerCase()) {
                                        isSel = true;
                                        foundSelected = true;
                                    }
                                }
                                var escapedName = $("<div>").text(nameTrimmed).html();
                                html += '<option value="' + st.id + '"' + (isSel ? ' selected' : '') + '>' + escapedName + '</option>';
                            }
                        });
                    }
                    $subSelect.html(html);
                    if (selectId && foundSelected) {
                        $subSelect.val(selectId);
                    }
                },
                error: function (xhr, status) {
                    if (status !== 'abort') {
                        $subSelect.html('<option value="">-- Select Policy Sub Type --</option>');
                    }
                }
            });
        }

        $(document).on("change", "#policy_type_id", function () {
            var typeId = $(this).val();
            fetchAndPopulateSubTypes(typeId, null);
            $("#modal_sub_type_policy_type_id").val(typeId);
        });

        // Initialize sub-types on load if policy type is already selected
        var initTypeId = $("#policy_type_id").val();
        var initSelectedSubType = $("#policy_sub_type_id").attr("data-selected") || $("#policy_sub_type_id").val();
        if (initTypeId && $("#policy_sub_type_id option").length <= 1) {
            fetchAndPopulateSubTypes(initTypeId, initSelectedSubType);
        }

        // Save Policy Type via AJAX
        function handleSavePolicyType() {
            var $form = $("#formAddPolicyType");
            var $btn = $("#btnSavePolicyType");
            var $spinner = $btn.find(".spinner-border");
            var nameInput = $("#modal_policy_type_name").val().trim();

            if (!nameInput) {
                $("#modal_policy_type_name").addClass("is-invalid");
                $("#policy_type_name-error").text("Policy Type Name is required.");
                return;
            }

            $("#modal_policy_type_name").removeClass("is-invalid");
            $("#policy_type_name-error").text("");
            $btn.prop("disabled", true);
            $spinner.removeClass("d-none");

            $.ajax({
                url: $form.attr("action") || "/policy-type/store",
                type: "POST",
                data: $form.serialize(),
                dataType: "json",
                headers: getAuthHeaders(),
                success: function (res) {
                    $btn.prop("disabled", false);
                    $spinner.addClass("d-none");

                    if (res.status && res.id) {
                        var resNameLower = (res.name || "").trim().toLowerCase();
                        var exists = false;
                        $("#policy_type_id option").each(function () {
                            if (String(this.value) === String(res.id) || $(this).text().trim().toLowerCase() === resNameLower) {
                                exists = true;
                                this.value = res.id;
                            }
                        });
                        if (!exists) {
                            $("#policy_type_id").append(new Option(res.name, res.id, true, true));
                        }
                        $("#policy_type_id").val(res.id).trigger("change");

                        var modalSubExists = false;
                        $("#modal_sub_type_policy_type_id option").each(function () {
                            if (String(this.value) === String(res.id) || $(this).text().trim().toLowerCase() === resNameLower) {
                                modalSubExists = true;
                                this.value = res.id;
                            }
                        });
                        if (!modalSubExists) {
                            $("#modal_sub_type_policy_type_id").append(new Option(res.name, res.id, true, true));
                        }
                        $("#modal_sub_type_policy_type_id").val(res.id);

                        var modalEl = document.getElementById("modalAddPolicyType");
                        var modal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.hide();
                        $form[0].reset();

                        if (typeof window.showAlert === "function") {
                            window.showAlert("success", res.message || "Policy type saved successfully.");
                        } else if (typeof Swal !== "undefined") {
                            Swal.fire({ icon: "success", title: res.message || "Policy type saved successfully.", timer: 1500, showConfirmButton: false });
                        }
                    }
                },
                error: function (xhr) {
                    $btn.prop("disabled", false);
                    $spinner.addClass("d-none");
                    var msg = xhr.responseJSON?.message || "Failed to add policy type.";
                    if (xhr.status === 422 && xhr.responseJSON?.errors?.policy_type_name) {
                        $("#modal_policy_type_name").addClass("is-invalid");
                        $("#policy_type_name-error").text(xhr.responseJSON.errors.policy_type_name[0]);
                    } else if (typeof window.showAlert === "function") {
                        window.showAlert("error", msg);
                    } else if (typeof Swal !== "undefined") {
                        Swal.fire({ icon: "error", title: msg });
                    }
                }
            });
        }

        $(document).on("click", "#btnSavePolicyType", handleSavePolicyType);
        $(document).on("submit", "#formAddPolicyType", function (e) {
            e.preventDefault();
            handleSavePolicyType();
        });

        // Save Policy Sub Type via AJAX
        function handleSavePolicySubType() {
            var $form = $("#formAddPolicySubType");
            var $btn = $("#btnSavePolicySubType");
            var $spinner = $btn.find(".spinner-border");
            var typeId = $("#modal_sub_type_policy_type_id").val();
            var nameInput = $("#modal_sub_type_name").val().trim();

            var hasErr = false;
            if (!typeId) {
                $("#modal_sub_type_policy_type_id").addClass("is-invalid");
                $("#type_id-error").text("Please select a policy type.");
                hasErr = true;
            } else {
                $("#modal_sub_type_policy_type_id").removeClass("is-invalid");
                $("#type_id-error").text("");
            }

            if (!nameInput) {
                $("#modal_sub_type_name").addClass("is-invalid");
                $("#sub_type_name-error").text("Sub type name is required.");
                hasErr = true;
            } else {
                $("#modal_sub_type_name").removeClass("is-invalid");
                $("#sub_type_name-error").text("");
            }

            if (hasErr) return;

            $btn.prop("disabled", true);
            $spinner.removeClass("d-none");

            $.ajax({
                url: $form.attr("action") || "/policy-sub-type/store",
                type: "POST",
                data: $form.serialize(),
                dataType: "json",
                headers: getAuthHeaders(),
                success: function (res) {
                    $btn.prop("disabled", false);
                    $spinner.addClass("d-none");

                    if (res.status && res.id) {
                        var currentMainTypeId = $("#policy_type_id").val();

                        if (!currentMainTypeId) {
                            $("#policy_type_id").val(typeId);
                            fetchAndPopulateSubTypes(typeId, res.id);
                        } else if (String(currentMainTypeId) === String(typeId)) {
                            var resNameLower = (res.name || "").trim().toLowerCase();
                            var exists = false;
                            $("#policy_sub_type_id option").each(function () {
                                if (String(this.value) === String(res.id) || $(this).text().trim().toLowerCase() === resNameLower) {
                                    exists = true;
                                    this.value = res.id;
                                }
                            });
                            if (!exists) {
                                $("#policy_sub_type_id").append(new Option(res.name, res.id, true, true));
                            }
                            $("#policy_sub_type_id").val(res.id);
                        }

                        var modalEl = document.getElementById("modalAddPolicySubType");
                        var modal = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
                        modal.hide();
                        $form[0].reset();

                        if (typeof window.showAlert === "function") {
                            window.showAlert("success", res.message || "Policy sub type saved successfully.");
                        } else if (typeof Swal !== "undefined") {
                            Swal.fire({ icon: "success", title: res.message || "Policy sub type saved successfully.", timer: 1500, showConfirmButton: false });
                        }
                    }
                },
                error: function (xhr) {
                    $btn.prop("disabled", false);
                    $spinner.addClass("d-none");
                    var msg = xhr.responseJSON?.message || "Failed to add policy sub-type.";
                    if (xhr.status === 422 && xhr.responseJSON?.errors) {
                        var errs = xhr.responseJSON.errors;
                        if (errs.type_id) {
                            $("#modal_sub_type_policy_type_id").addClass("is-invalid");
                            $("#type_id-error").text(errs.type_id[0]);
                        }
                        if (errs.name) {
                            $("#modal_sub_type_name").addClass("is-invalid");
                            $("#sub_type_name-error").text(errs.name[0]);
                        }
                    } else if (typeof window.showAlert === "function") {
                        window.showAlert("error", msg);
                    } else if (typeof Swal !== "undefined") {
                        Swal.fire({ icon: "error", title: msg });
                    }
                }
            });
        }

        $(document).on("click", "#btnSavePolicySubType", handleSavePolicySubType);
        $(document).on("submit", "#formAddPolicySubType", function (e) {
            e.preventDefault();
            handleSavePolicySubType();
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initPolicyTypeModals);
    } else {
        initPolicyTypeModals();
    }
})();
</script>
@endpush
