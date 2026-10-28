@php
    $polId = $policy['id'];
    $polDoc = !empty($policy['document']) ? '1' : '0';
    $polStart = !empty($policy['start_date']) ? date("d-m-Y", strtotime($policy['start_date'])) : '--';
    $polEnd = !empty($policy['end_date']) ? date("d-m-Y", strtotime($policy['end_date'])) : '--';
@endphp
<tr data-id="{{ $polId }}" data-company="{{ esc($policy['company_name'] ?? '') }}"
    @if(isset($policyScope)) data-policy-scope="{{ $policyScope }}" @endif>
    <td class="font_size text-center">{{ $index }}</td>
    <td class="font_size policy-holder-name" title="{{ esc($policy['policy_holder_name']) }}">
        {{ esc($policy['policy_holder_name']) }}
    </td>
    @if(!empty($showAgentCol))
        <td class="font_size mobile-hide hover-full">{{ esc($policy['staff_name'] ?? '') }}</td>
    @endif
    <td class="font_size mobile-hide hover-full" title="{{ esc($policy['policy_type_name']) }}">
        {{ esc($policy['policy_type_name']) }}
    </td>
    @if(!empty($showCompanyCol))
        <td class="font_size mobile-hide hover-full" title="{{ esc($policy['company_name']) }}">
            {{ esc($policy['company_name']) }}
        </td>
    @endif
    <td class="font_size mobile-hide">{{ $polStart }}</td>
    <td class="font_size mobile-hide">{{ $polEnd }}</td>
    <td class="font_size mobile-hide">{{ esc($policy['premium'] ?? '') }}</td>
    @if(!empty($showCreatedByCol))
        <td class="font_size mobile-hide">{{ esc($policy['created_by_name'] ?? '--') }}</td>
    @endif
    <td class="font_size text-center">
        <div class="desktop-actions">
            <!-- View KYC Button -->
            <a href="javascript:void(0);"
                class="btn btn-secondary p-2 view-kyc-btn"
                data-policy-id="{{ $polId }}"
                title="View KYC Details">
                <i class="fa fa-id-card"></i>
            </a>

            <!-- Upload / Download Button -->
            <button type="button" class="btn btn-dark-blue p-2" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="{{ $polId }}" title="Upload / Download">
                <i class="fa fa-upload"></i>
            </button>

            <!-- Renewal Button -->
            <a href="{{ base_url('renew-policy/' . $polId) }}" class="btn btn-success p-2" title="Renew Policy">
                <i class="fa fa-refresh"></i>
            </a>

            <!-- View Document Button -->
            <a href="{{ base_url('policy-document-view/' . $polId) }}" target="_blank" rel="noopener noreferrer"
                class="btn btn-warning p-2 view-document-btn"
                data-policy-id="{{ $polId }}"
                data-has-doc="{{ $polDoc }}"
                title="View Policy Document">
                <i class="fa fa-file-pdf"></i>
            </a>

            <!-- View Button -->
            <a href="{{ base_url('view-policy/' . $polId) }}" class="btn btn-info p-2" title="View Policy">
                <i class="fa fa-eye"></i>
            </a>

            <!-- Commission Button -->
            <!-- <button type="button" class="btn btn-warning p-2 edit-policy-commission-btn" 
                data-policy-id="{{ $polId }}" 
                data-policy-number="{{ $policy['policy_number'] ?? $polId }}" 
                data-premium="{{ $policy['premium'] ?? 0 }}"
                title="Manage Commission (Pay-In & Pay-Out)">
                <i class="fa fa-percent"></i>
            </button> -->

            <!-- Edit Button -->
            <a href="{{ base_url('edit-policy/' . $polId) }}" class="btn btn-light p-2" title="Edit Policy">
                <i class="fa fa-edit"></i>
            </a>

            <!-- Delete Button -->
            <a href="javascript:void(0);" class="btn btn-danger p-2 delete-policy" data-id="{{ $polId }}" title="Delete Policy">
                <i class="fa fa-trash"></i>
            </a>
        </div>

        <button type="button" class="btn-user-expand mobile-only d-md-none" onclick="toggleMobileDetails({{ $polId }}, this)">
            <i class="fa-solid fa-plus"></i>
        </button>
    </td>
</tr>

<!-- Mobile Details Row -->
<tr class="details-row d-md-none border-0" id="details-{{ $polId }}" data-id="{{ $polId }}" style="display: none;">
    <td colspan="8" class="p-0 border">
        <div class="details-content">
            <div class="row g-3">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="expand-label"><i class="fa-solid fa-user"></i> Agent :</div>
                    <div class="expand-value">{{ esc($policy['staff_name'] ?? '--') }}</div>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="expand-label"><i class="fa-solid fa-file-shield"></i> Policy Type :</div>
                    <div class="expand-value">{{ esc($policy['policy_type_name'] ?? '--') }}</div>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="expand-label"><i class="fa-solid fa-calendar-check"></i> Start Date :</div>
                    <div class="expand-value">{{ $polStart }}</div>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="expand-label"><i class="fa-solid fa-calendar-xmark"></i> End Date :</div>
                    <div class="expand-value">{{ $polEnd }}</div>
                </div>
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="expand-label"><i class="fa-solid fa-indian-rupee-sign"></i> Premium :</div>
                    <div class="expand-value">{{ esc($policy['premium'] ?? '--') }}</div>
                </div>
                @if(!empty($showCreatedByCol))
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <div class="expand-label"><i class="fa-solid fa-user-pen"></i> Created By :</div>
                    <div class="expand-value">{{ esc($policy['created_by_name'] ?? '--') }}</div>
                </div>
                @endif
                <div class="col-12 d-flex justify-content-between align-items-center pt-3 mt-3 border-top">
                    <div class="expand-label"><i class="fa-solid fa-gear"></i> Actions :</div>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="javascript:void(0);"
                            class="btn crm-action-btn btn-sm view-kyc-btn"
                            data-policy-id="{{ $polId }}"
                            title="View KYC Details">
                            <i class="bi bi-person-vcard"></i>
                        </a>
                        <button type="button" class="btn crm-action-btn btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDownloadModal" data-policy-id="{{ $polId }}" title="Upload / Download">
                            <i class="bi bi-upload"></i>
                        </button>
                        <a href="{{ base_url('renew-policy/' . $polId) }}" class="btn crm-action-btn btn-sm" title="Renew Policy">
                            <i class="bi bi-arrow-repeat"></i>
                        </a>
                        <a href="{{ base_url('policy-document-view/' . $polId) }}" target="_blank" rel="noopener noreferrer"
                            class="btn crm-action-btn btn-sm view-document-btn"
                            data-policy-id="{{ $polId }}"
                            data-has-doc="{{ $polDoc }}"
                            title="View Policy Document">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <a href="{{ base_url('view-policy/' . $polId) }}" class="btn crm-action-btn btn-sm" title="View Policy">
                            <i class="bi bi-eye"></i>
                        </a>
                        <button type="button" class="btn crm-action-btn btn-sm edit-policy-commission-btn" 
                            data-policy-id="{{ $polId }}" 
                            data-policy-number="{{ $policy['policy_number'] ?? $polId }}" 
                            data-premium="{{ $policy['premium'] ?? 0 }}"
                            title="Manage Commission">
                            <i class="bi bi-percent"></i>
                        </button>
                        <a href="{{ base_url('edit-policy/' . $polId) }}" class="btn crm-action-btn btn-sm" title="Edit Policy">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="javascript:void(0);" class="btn crm-action-btn btn-sm text-danger delete-policy" data-id="{{ $polId }}" title="Delete Policy">
                            <i class="bi bi-trash text-danger"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </td>
</tr>
