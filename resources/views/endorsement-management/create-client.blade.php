@extends('layouts.app')

@section('content')
<style>
  .endorsement-entry-wrap {
    padding-top: 0.75rem;
    padding-bottom: 1.25rem;
  }

  .ee-card {
    background: #fff;
    border-radius: 0 0 16px 16px;
    border: 0;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
    padding: 14px;
  }

  .ee-tabs {
    display: flex;
    align-items: center;
    gap: 0;
    background: #eef4ff;
    border-radius: 14px;
    padding: 10px;
    overflow-x: auto;
    white-space: nowrap;
    margin-bottom: 14px;
  }

  .ee-tab {
    border: 0;
    background: transparent;
    color: #1f2d3d;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 12px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 180px;
  }

  .ee-tab + .ee-tab {
    position: relative;
  }

  .ee-tab + .ee-tab:before {
    content: "";
    position: absolute;
    left: 0;
    top: 10px;
    bottom: 10px;
    width: 1px;
    background: rgba(27, 42, 74, 0.25);
  }

  .ee-tab.active {
    background: #1B2A4A;
    color: #fff;
    box-shadow: 0 8px 18px rgba(27, 42, 74, 0.2);
  }

  .ee-stepper {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    background: #eaf2ff;
    border-radius: 14px;
    overflow: hidden;
    margin-bottom: 14px;
  }

  .ee-step {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 14px;
    color: #2a3a4f;
    font-weight: 700;
    font-size: 13px;
    opacity: 0.85;
  }

  .ee-step .badge {
    width: 26px;
    height: 26px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    color: #1B2A4A;
    border: 2px solid #1B2A4A;
    font-weight: 800;
  }

  .ee-step.active {
    background: #cfe2ff;
    opacity: 1;
  }

  .ee-step.active .badge {
    background: #1B2A4A;
    color: #fff;
  }

  .ee-panel {
    display: none;
  }

  .ee-panel.active {
    display: block;
  }

  .ee-surface {
    background: #f7faff;
    border-radius: 16px;
    padding: 14px;
    min-height: 520px;
  }

  .ee-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 14px;
  }

  .ee-actions .btn {
    border-radius: 12px;
    padding: 9px 16px;
    font-weight: 700;
  }

  .table-custom th {
      background-color: #f8f9fa;
      font-weight: 700;
      color: #2a3a4f;
      white-space: nowrap;
  }
  
  .endorsement-header {
    background: #1B2A4A;
    padding: 8px 24px;
    border-radius: 12px 12px 0 0;
    color: #fff;
    box-shadow: 0 4px 20px rgba(27, 42, 74, 0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .endorsement-header .left { display: flex; align-items: center; gap: 16px; }
  
  .loading-spinner {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
  }
  .loading-spinner.active { display: flex; }
  .spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #003982;
    border-radius: 50%;
    animation: spin 1s linear infinite;
  }
  @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  }
</style>

<div class="container-fluid endorsement-entry-wrap mt-4">
  <form id="clientEndorsementForm" enctype="multipart/form-data">
    @csrf
  <div class="endorsement-header">
    <div class="left">
      <h6 class="mb-0 text-white me-2">Endorsement Management</h6>
    </div>
    <div class="align-items-center d-flex gap-3">
      <a class="btn btn-light mb-0" href="{{ route('endorsement.index') }}">
       <i class="fa fa-angle-left"></i> Back
      </a>
      <div class="subtitle text-white fw-semibold fs-6">Client Endorsement</div>
    </div>
  </div>

  <div class="ee-card">
    <div class="ee-tabs">
      <a class="ee-tab" href="{{ route('endorsement.create.general') }}">General Insurance</a>
      <a class="ee-tab" href="{{ route('endorsement.create') }}">Life Insurance</a>
      <a class="ee-tab active" href="javascript:void(0)">Client Endorsement</a>
    </div>

    <div class="ee-stepper" id="eeStepper">
      <div class="ee-step active" data-step="1">
        <span class="badge">1</span> Step 1
      </div>
      <div class="ee-step" data-step="2">
        <span class="badge">2</span> Step 2
      </div>
      <div class="ee-step" data-step="3">
        <span class="badge">3</span> Step 3
      </div>
    </div>

    <div class="ee-surface">
      <!-- Step 1 -->
      <div class="ee-panel active" id="eeStep1">
        <div class="row g-3 mb-4">
          <div class="col-md-6">
            <label class="form-label">Transaction Date<span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="transaction_date" value="{{ old('transaction_date', date('Y-m-d')) }}">
            @error('transaction_date') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Document Number<span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="document_number" value="{{ old('document_number', '06022651120') }}" placeholder="Document no">
            @error('document_number') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Family Group<span class="text-danger">*</span></label>
            <select class="form-select" id="family_group_id" name="family_group_id">
              <option value="" selected disabled>Search for family group</option>
            </select>
            @error('family_group_id') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
          <div class="col-md-6">
            <label class="form-label">Policy Holder Name<span class="text-danger">*</span></label>
            <select class="form-select" id="policy_holder_id" name="policy_holder_id" disabled>
              <option value="" selected disabled>Search for policy holder name</option>
            </select>
            @error('policy_holder_id') <span class="text-danger">{{ $message }}</span> @enderror
          </div>
        </div>

        <div class="table-responsive">
            <table class="table table-borderless table-custom">
                <thead>
                    <tr>
                        <th style="width: 15%;">Client Field Name</th>
                        <th style="width: 15%;">Existing Data</th>
                        <th style="width: 20%;">Transaction Type</th>
                        <th style="width: 15%;">To Be Endorse</th>
                        <th style="width: 15%;">Remarks</th>
                        <th style="width: 20%;">Select Endorse Effect</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $fields = [
                        'First Name' => ['required' => true, 'key' => 'first_name', 'type' => 'text'],
                        'Middle Name' => ['required' => false, 'key' => 'middle_name', 'type' => 'text'],
                        'Last Name' => ['required' => true, 'key' => 'last_name', 'type' => 'text'],
                        'DOB' => ['required' => true, 'key' => 'dob', 'type' => 'date'],
                        'Relation With Head' => ['required' => true, 'key' => 'relation_with_head', 'type' => 'select', 'options' => ['Self', 'Father', 'Mother', 'Spouse', 'Son', 'Daughter', 'Brother', 'Sister', 'Other']],
                        'Gender' => ['required' => true, 'key' => 'gender', 'type' => 'select', 'options' => ['Male', 'Female', 'Other']]
                    ];
                    @endphp
                    
                    @foreach($fields as $field => $opts)
                        @php
                        $required = $opts['required'];
                        $key = $opts['key'];
                        $type = $opts['type'] ?? 'text';
                        $options = $opts['options'] ?? [];
                        $reqStar = $required ? '<span class="text-danger">*</span>' : '';
                        @endphp
                    <tr>
                        <td><strong>{!! $field . $reqStar !!}</strong></td>
                        <td>
                            @if($type == 'select' && !empty($options))
                                <input type="text" class="form-control existing-data-cell" name="existing_{{ $key }}" data-field="{{ $key }}" value="{{ old('existing_'.$key) }}" list="existing_{{ $key }}_list" placeholder="Select or enter {{ strtolower($field) }}">
                                <datalist id="existing_{{ $key }}_list">
                                    @foreach($options as $opt)
                                    <option value="{{ $opt }}">
                                    @endforeach
                                </datalist>
                            @else
                                <input type="{{ $type }}" class="form-control existing-data-cell" name="existing_{{ $key }}" data-field="{{ $key }}" value="{{ old('existing_'.$key) }}" placeholder="Enter {{ strtolower($field) }}">
                            @endif
                            @error('existing_'.$key) <span class="text-danger">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            <select class="form-select transaction-type-field" name="{{ $key }}_transaction_type" data-target="{{ $key }}_to_be_endorse">
                                <option value="" selected disabled>Select transaction type</option>
                                <option value="change" {{ old($key.'_transaction_type') == 'change' ? 'selected' : '' }}>Change</option>
                                @if($key == 'middle_name')
                                <option value="add" {{ old($key.'_transaction_type') == 'add' ? 'selected' : '' }}>Add</option>
                                <option value="remove" {{ old($key.'_transaction_type') == 'remove' ? 'selected' : '' }}>Remove</option>
                                @endif
                            </select>
                            @error($key.'_transaction_type') <span class="text-danger">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            @if($key == 'dob')
                                <input type="date" class="form-control to-be-endorse" name="{{ $key }}_to_be_endorse" value="{{ old($key.'_to_be_endorse') }}" placeholder="Select dob" disabled>
                            @elseif($key == 'relation_with_head')
                                <select class="form-select to-be-endorse" name="{{ $key }}_to_be_endorse" disabled>
                                    <option value="">Search for relation</option>
                                    @foreach(['Self', 'Father', 'Mother', 'Spouse', 'Son', 'Daughter', 'Brother', 'Sister', 'Other'] as $opt)
                                        <option value="{{ $opt }}" {{ old($key.'_to_be_endorse') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @elseif($key == 'gender')
                                <select class="form-select to-be-endorse" name="{{ $key }}_to_be_endorse" disabled>
                                    <option value="">Search for gender</option>
                                    @foreach(['Male', 'Female', 'Other'] as $opt)
                                        <option value="{{ $opt }}" {{ old($key.'_to_be_endorse') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control to-be-endorse" name="{{ $key }}_to_be_endorse" value="{{ old($key.'_to_be_endorse') }}" placeholder="Enter {{ strtolower($field) }}" disabled>
                            @endif
                            @error($key.'_to_be_endorse') <span class="text-danger">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            <input type="text" class="form-control" name="{{ $key }}_remarks" value="{{ old($key.'_remarks') }}" placeholder="Enter remarks">
                            @error($key.'_remarks') <span class="text-danger">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            <select class="form-select" name="{{ $key }}_endorse_effect">
                                <option value="" selected disabled>Select endorse type</option>
                                <option value="effect_on_policy" {{ old($key.'_endorse_effect') == 'effect_on_policy' ? 'selected' : '' }}>Effect on policy</option>
                                <option value="view_only_in_endorsement" {{ old($key.'_endorse_effect') == 'view_only_in_endorsement' ? 'selected' : '' }}>View only in endorsement</option>
                            </select>
                            @error($key.'_endorse_effect') <span class="text-danger">{{ $message }}</span> @enderror
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ee-actions">
          <button type="button" class="btn btn-dark-blue" id="eeNext1">Next</button>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="ee-panel" id="eeStep2">
        <div class="table-responsive">
            <table class="table table-borderless table-custom">
                <thead>
                    <tr>
                        <th style="width: 5%;"></th>
                        <th style="width: 15%;"></th>
                        <th style="width: 10%;"></th>
                        <th style="width: 20%;"></th>
                        <th style="width: 20%;"></th>
                        <th style="width: 25%;"></th>
                        <th style="width: 5%;"></th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $step2Fields = [
                        ['label' => 'Address 1', 'required' => true, 'key' => 'address_1'],
                        ['label' => 'Address 2', 'required' => false, 'key' => 'address_2'],
                        ['label' => 'City', 'required' => true, 'key' => 'city'],
                        ['label' => 'Area', 'required' => false, 'key' => 'area'],
                        ['label' => 'Mobile Number', 'required' => false, 'key' => 'mobile_number'],
                        ['label' => 'Whatsapp Number', 'required' => false, 'key' => 'whatsapp_number'],
                        ['label' => 'Email', 'required' => false, 'key' => 'email'],
                        ['label' => 'Aadhaar Card', 'required' => false, 'key' => 'aadhaar_card'],
                        ['label' => 'Pan Card', 'required' => false, 'key' => 'pan_card'],
                        ['label' => 'GST Number', 'required' => false, 'key' => 'gst_number']
                    ];
                    @endphp
                    
                    @foreach($step2Fields as $field)
                        @php
                        $reqStar = $field['required'] ? '<span class="text-danger">*</span>' : '';
                        $key = $field['key'];
                        $label = strtolower($field['label']);
                        @endphp
                    <tr>
                        <td colspan="2"><strong>{!! $field['label'] . $reqStar !!}</strong></td>
                        <td><input type="text"  class="form-control existing-data-cell" name="existing_{{ $key }}" data-field="{{ $key }}" value="{{ old('existing_'.$key) }}" placeholder="Enter {{ $label }}"></td>
                        <td>
                             <div class="input-group">
                                <select class="form-select transaction-type-field" name="{{ $key }}_transaction_type" data-target="{{ $key }}_to_be_endorse">
                                    <option value="" selected disabled>Select transaction type</option>
                                    <option value="change" {{ old($key.'_transaction_type') == 'change' ? 'selected' : '' }}>Change</option>
                                    <option value="remove" {{ old($key.'_transaction_type') == 'remove' ? 'selected' : '' }}>Remove</option>
                                </select>
                             </div>
                        </td>
                        <td>
                            <input type="text" class="form-control to-be-endorse" id="{{ $key }}_to_be_endorse" name="{{ $key }}_to_be_endorse" value="{{ old($key.'_to_be_endorse') }}" placeholder="Enter {{ strtolower($field['label']) }}" disabled>
                        </td>
                        <td><input type="text" class="form-control" name="{{ $key }}_remarks" value="{{ old($key.'_remarks') }}" placeholder="Enter remarks"></td>
                        <td>
                            <div class="input-group">
                                <select class="form-select" name="{{ $key }}_endorse_effect" style="min-width: 150px;">
                                    <option value="">Select Endorse Effect</option>
                                    <option value="effect_on_policy" {{ old($key.'_endorse_effect') == 'effect_on_policy' ? 'selected' : '' }}>Effect on Policy</option>
                                    <option value="view_only_in_endorsement" {{ old($key.'_endorse_effect') == 'view_only_in_endorsement' ? 'selected' : '' }}>View Only in Endorsement</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ee-actions">
          <button type="button" class="btn crm-action-btn" id="eePrev2">Previous</button>
          <button type="button" class="btn btn-dark-blue" id="eeNext2">Next</button>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="ee-panel" id="eeStep3">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Select General Policy</label>
                 <div class="input-group">
                    <input type="text" class="form-control" name="general_policy_id" value="{{ old('general_policy_id') }}" placeholder="Search for general policy">
                    <button class="btn btn-outline-secondary mb-0 search-policy-btn" type="button" data-type="general">Search</button>
                 </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Select Life Policy</label>
                <input type="text" class="form-control" name="life_policy_id" value="{{ old('life_policy_id') }}" placeholder="Search for life policies">
            </div>
            <div class="col-md-3">
                 <label class="form-label">Endorsement Effective Date</label>
                 <input type="date" class="form-control" name="endorsement_effective_date" value="{{ old('endorsement_effective_date', date('Y-m-d')) }}">
            </div>
            <div class="col-md-3">
                 <label class="form-label">Reference No</label>
                 <input type="text" class="form-control" name="reference_no" value="{{ old('reference_no') }}" placeholder="Enter reference">
            </div>
            <div class="col-md-6">
                <label class="form-label">Upload Endorsement Letter (JPG, PNG, JPEG, Or PDF)</label>
                <div class="input-group">
                    <input type="file" class="form-control" name="endorsement_letter" accept=".jpg,.jpeg,.png,.pdf">
                </div>
            </div>
            <div class="col-12">
                <label class="form-label">Remark/Reason</label>
                <input type="text" class="form-control" name="remark_reason" value="{{ old('remark_reason') }}" placeholder="Enter remark/reason">
            </div>
        </div>

        <div class="ee-actions mt-4">
            <button type="button" class="btn crm-action-btn" id="eePrev3">Previous</button>
            <button type="button" class="btn btn-dark-blue" id="eeSaveBtn" style="background-color: #1B2A4A; border-color: #1B2A4A;">Save</button>
        </div>
      </div>
    </div>
  </div>
  </form>
</div>

<!-- Loading Spinner -->
<div class="loading-spinner" id="loadingSpinner">
  <div class="spinner"></div>
</div>

<script>
  (function() {
    const step1 = document.getElementById('eeStep1');
    const step2 = document.getElementById('eeStep2');
    const step3 = document.getElementById('eeStep3');
    const stepper = document.getElementById('eeStepper');
    const steps = stepper ? stepper.querySelectorAll('.ee-step') : [];

    function setStep(n) {
      if (!step1 || !step2 || !step3) return;
      
      step1.classList.toggle('active', n === 1);
      step2.classList.toggle('active', n === 2);
      step3.classList.toggle('active', n === 3);
      
      steps.forEach(s => {
          const stepNum = Number(s.getAttribute('data-step'));
          s.classList.toggle('active', stepNum === n);
      });
      
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    const next1 = document.getElementById('eeNext1');
    const prev2 = document.getElementById('eePrev2');
    const next2 = document.getElementById('eeNext2');
    const prev3 = document.getElementById('eePrev3');
    const saveBtn = document.getElementById('eeSaveBtn');

    if (next1) next1.addEventListener('click', () => setStep(2));
    if (prev2) prev2.addEventListener('click', () => setStep(1));
    if (next2) next2.addEventListener('click', () => setStep(3));
    if (prev3) prev3.addEventListener('click', () => setStep(2));

    document.querySelectorAll('.transaction-type-field').forEach(function(selectEl) {
        selectEl.addEventListener('change', function() {
            const targetId = this.getAttribute('data-target');
            const targetField = document.querySelector('[name="' + targetId + '"]') || document.getElementById(targetId);
            if (targetField && this.value) {
                targetField.disabled = false;
            }
        });
    });

    if (saveBtn) {
      saveBtn.addEventListener('click', function() {
        saveEndorsement();
      });
    }

    const baseUrl = '{{ route("endorsement.index") }}';
    const familyGroupSelect = document.getElementById('family_group_id');
    const policyHolderSelect = document.getElementById('policy_holder_id');

    function resetExistingData() {
      document.querySelectorAll('.existing-data-cell').forEach(function(input) {
        input.value = '';
      });
    }

    function setExistingData(data) {
      document.querySelectorAll('.existing-data-cell').forEach(function(input) {
        const field = input.getAttribute('data-field');
        let val = (data && data[field]) ? String(data[field]).trim() : '';
        
        // Special handling for date inputs
        if (input.type === 'date' && val) {
            // Ensure format is YYYY-MM-DD for date inputs
            if (val.includes('/')) {
                const parts = val.split('/');
                if (parts.length === 3) {
                    // Try to detect if it's DD/MM/YYYY or MM/DD/YYYY
                    // This is tricky, but usually it's YYYY-MM-DD in DB.
                    // If it's already YYYY-MM-DD, the includes('/') will be false.
                }
            }
        }
        
        input.value = val || '';
      });
    }

    if (familyGroupSelect) {
      fetch('{{ route("endorsement.getFamilyGroups") }}')
        .then(function(r) { return r.json(); })
        .then(function(groups) {
          var opts = '<option value="" selected disabled>Search for family group</option>';
          if (Array.isArray(groups)) {
            groups.forEach(function(g) {
              opts += '<option value="' + g.id + '">' + (g.group_name || '') + '</option>';
            });
          }
          familyGroupSelect.innerHTML = opts;
        });
    }

    if (familyGroupSelect && policyHolderSelect) {
      familyGroupSelect.addEventListener('change', function() {
        var groupId = this.value;
        policyHolderSelect.disabled = true;
        policyHolderSelect.innerHTML = '<option value="" selected disabled>Loading...</option>';
        resetExistingData();

        if (groupId) {
          fetch('{{ url("endorsement-management/get-holders") }}/' + groupId)
            .then(function(r) { return r.json(); })
            .then(function(holders) {
              var opts = '<option value="" selected disabled>Search for policy holder name</option>';
              if (Array.isArray(holders) && holders.length) {
                holders.forEach(function(h) {
                  opts += '<option value="' + h.id + '">' + (h.name || '') + '</option>';
                });
                policyHolderSelect.disabled = false;
              }
              policyHolderSelect.innerHTML = opts;
            });
        }
      });
    }

    if (policyHolderSelect) {
      policyHolderSelect.addEventListener('change', function() {
        var holderId = this.value;
        resetExistingData();
        if (!holderId) return;

        var formData = new FormData();
        formData.append('holder_id', holderId);
        formData.append('_token', '{{ csrf_token() }}');
        fetch('{{ route("endorsement.getHolderDetails") }}', {
          method: 'POST',
          body: formData
        })
          .then(function(r) { return r.json(); })
          .then(function(res) {
            if (res.status && res.data) setExistingData(res.data);
          });
      });
    }

    function saveEndorsement() {
      const form = document.getElementById('clientEndorsementForm');
      const formData = new FormData(form);
      
      const spinner = document.getElementById('loadingSpinner');
      if (spinner) spinner.classList.add('active');
      
      fetch('{{ route("endorsement.saveClient") }}', {
        method: 'POST',
        body: formData
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (spinner) spinner.classList.remove('active');
        if (res.status) {
          window.crmAlert('Endorsement saved successfully!');
          window.location.href = '{{ route("endorsement.index") }}';
        } else {
          window.crmAlert(res.message || 'Failed to save endorsement');
        }
      })
      .catch(function(error) {
        if (spinner) spinner.classList.remove('active');
        console.error('Error:', error);
        window.crmAlert('An error occurred while saving the endorsement');
      });
    }
  })();
</script>
@endsection
