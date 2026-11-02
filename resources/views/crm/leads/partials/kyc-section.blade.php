{{-- KYC Details Section --}}
<div class="card border rounded-4 mt-4 shadow-none lead-kyc-card">
    <div class="card-header bg-white py-3 px-3 px-md-4 border-bottom">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h5 class="card-title mb-1 fw-bold fs-6 text-dark d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check text-primary"></i> KYC Details
                </h5>
                <p class="text-muted small mb-0">Upload and manage identity proof, address proof, and photographs/signature.</p>
            </div>
            @if(isset($kyc) && $kyc)
                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill">
                    <i class="bi bi-check-circle me-1"></i> KYC Uploaded
                </span>
            @endif
        </div>
    </div>

    <div class="card-body p-3 p-md-4">
        <!-- Tabs Header -->
        <ul class="nav nav-tabs nav-tabs-bordered mb-4" id="leadKycTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="lead-idproof-tab" data-bs-toggle="tab" data-bs-target="#lead-idproof" type="button" role="tab" aria-selected="true">
                    <i class="bi bi-person-vcard me-1"></i> ID Proof
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="lead-address-tab" data-bs-toggle="tab" data-bs-target="#lead-address" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-geo-alt me-1"></i> Address Proof
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="lead-photosign-tab" data-bs-toggle="tab" data-bs-target="#lead-photosign" type="button" role="tab" aria-selected="false">
                    <i class="bi bi-camera me-1"></i> Photo &amp; Signature
                </button>
            </li>
        </ul>

        @php
            $publicPath = env('PUBLIC_PATH') ? rtrim(env('PUBLIC_PATH'), '/') . '/' : '';
            $kycBaseUrl = asset($publicPath . 'uploads/kyc') . '/';

            $getFilesArray = function($val) use ($kycBaseUrl) {
                if (empty($val)) return [];
                $list = is_string($val) && str_starts_with(trim($val), '[') ? json_decode($val, true) : (is_array($val) ? $val : [$val]);
                if (!is_array($list)) $list = [$val];
                $result = [];
                foreach ($list as $file) {
                    if (empty($file) || !is_string($file)) continue;
                    $clean = basename($file);
                    $result[] = [
                        'name' => $clean,
                        'url' => str_starts_with($file, 'http') ? $file : $kycBaseUrl . $clean,
                        'ext' => strtolower(pathinfo($clean, PATHINFO_EXTENSION) ?: 'jpg'),
                    ];
                }
                return $result;
            };

            $aadhaarFiles = isset($kyc) ? $getFilesArray($kyc->aadhaar_card) : [];
            $panFiles = isset($kyc) ? $getFilesArray($kyc->pan_card) : [];
            $dlFiles = isset($kyc) ? $getFilesArray($kyc->driving_licence) : [];
            $addrAadhaarFiles = isset($kyc) ? $getFilesArray($kyc->addr_aadhaar) : [];
            $addrDlFiles = isset($kyc) ? $getFilesArray($kyc->addr_driving_licence) : [];
            $photoFiles = isset($kyc) ? $getFilesArray($kyc->photo) : [];
            $signFiles = isset($kyc) ? $getFilesArray($kyc->signature) : [];
        @endphp

        <!-- Tabs Content -->
        <div class="tab-content" id="leadKycTabsContent">

            <!-- ================= ID PROOF TAB ================= -->
            <div class="tab-pane fade show active" id="lead-idproof" role="tabpanel" aria-labelledby="lead-idproof-tab">
                <h6 class="fw-bold mb-3 text-dark">ID Proof</h6>

                <div class="row g-3">
                    <!-- Aadhaar Card Column -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Aadhaar Card Upload Type</label>
                        <select class="form-select mb-2" id="lead_aadhaar_type_select">
                            <option value="single">Single File</option>
                            <option value="front_back">Front &amp; Back Files</option>
                        </select>

                        <!-- Single Upload Div -->
                        <div id="lead_aadhaar_single_div">
                            <input type="file" name="aadhaar_card[]" class="form-control lead-kyc-file-input" id="lead_aadhaar_single_input" accept=".jpg,.jpeg,.png,.pdf,.gif,.jfif,.webp">
                        </div>

                        <!-- Front & Back Upload Div -->
                        <div id="lead_aadhaar_front_back_div" style="display: none;">
                            <div class="mb-2">
                                <small class="text-muted d-block mb-1">Front Side</small>
                                <input type="file" name="aadhaar_card[]" class="form-control lead-kyc-file-input lead-aadhaar-multi-input" id="lead_aadhaar_front_input" accept=".jpg,.jpeg,.png,.pdf,.gif,.jfif,.webp" disabled>
                            </div>
                            <div>
                                <small class="text-muted d-block mb-1">Back Side</small>
                                <input type="file" name="aadhaar_card[]" class="form-control lead-kyc-file-input lead-aadhaar-multi-input" id="lead_aadhaar_back_input" accept=".jpg,.jpeg,.png,.pdf,.gif,.jfif,.webp" disabled>
                            </div>
                        </div>

                        @if(count($aadhaarFiles) > 0)
                            <div class="mt-2 p-2 bg-light rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <small class="text-muted fw-bold">Existing Document(s):</small>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-primary" onclick="showLeadKycGallery('Aadhaar Card', {{ json_encode(array_column($aadhaarFiles, 'url')) }})">
                                        <i class="bi bi-eye"></i> View All
                                    </button>
                                </div>
                                @foreach($aadhaarFiles as $af)
                                    <div class="d-flex align-items-center justify-content-between small text-truncate py-1 border-bottom-dashed">
                                        <span class="text-truncate" title="{{ $af['name'] }}">
                                            <i class="bi {{ $af['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                            {{ $af['name'] }}
                                        </span>
                                        <div class="d-flex gap-1 ms-2">
                                            <a href="{{ $af['url'] }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0 px-1" title="Open"><i class="bi bi-box-arrow-up-right"></i></a>
                                            <a href="{{ $af['url'] }}" download="{{ $af['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1" title="Download"><i class="bi bi-download"></i></a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- PAN Card Column -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">PAN Card</label>
                        <input type="file" name="pan_card[]" class="form-control lead-kyc-file-input" multiple accept=".jpg,.jpeg,.png,.pdf,.gif,.jfif,.webp">

                        @if(count($panFiles) > 0)
                            <div class="mt-2 p-2 bg-light rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <small class="text-muted fw-bold">Existing Document(s):</small>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-primary" onclick="showLeadKycGallery('PAN Card', {{ json_encode(array_column($panFiles, 'url')) }})">
                                        <i class="bi bi-eye"></i> View All
                                    </button>
                                </div>
                                @foreach($panFiles as $pf)
                                    <div class="d-flex align-items-center justify-content-between small text-truncate py-1">
                                        <span class="text-truncate" title="{{ $pf['name'] }}">
                                            <i class="bi {{ $pf['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                            {{ $pf['name'] }}
                                        </span>
                                        <div class="d-flex gap-1 ms-2">
                                            <a href="{{ $pf['url'] }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0 px-1"><i class="bi bi-box-arrow-up-right"></i></a>
                                            <a href="{{ $pf['url'] }}" download="{{ $pf['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Driving Licence Column -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Driving Licence</label>
                        <input type="file" name="driving_licence[]" class="form-control lead-kyc-file-input" multiple accept=".jpg,.jpeg,.png,.pdf,.gif,.jfif,.webp">

                        @if(count($dlFiles) > 0)
                            <div class="mt-2 p-2 bg-light rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <small class="text-muted fw-bold">Existing Document(s):</small>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-primary" onclick="showLeadKycGallery('Driving Licence', {{ json_encode(array_column($dlFiles, 'url')) }})">
                                        <i class="bi bi-eye"></i> View All
                                    </button>
                                </div>
                                @foreach($dlFiles as $df)
                                    <div class="d-flex align-items-center justify-content-between small text-truncate py-1">
                                        <span class="text-truncate" title="{{ $df['name'] }}">
                                            <i class="bi {{ $df['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                            {{ $df['name'] }}
                                        </span>
                                        <div class="d-flex gap-1 ms-2">
                                            <a href="{{ $df['url'] }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0 px-1"><i class="bi bi-box-arrow-up-right"></i></a>
                                            <a href="{{ $df['url'] }}" download="{{ $df['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ================= ADDRESS PROOF TAB ================= -->
            <div class="tab-pane fade" id="lead-address" role="tabpanel" aria-labelledby="lead-address-tab">
                <h6 class="fw-bold mb-3 text-dark">Address Proof</h6>

                <div class="row g-3">
                    <!-- Address Aadhaar -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Aadhaar Card</label>
                        <input type="file" name="addr_aadhaar[]" class="form-control lead-kyc-file-input" multiple accept=".jpg,.jpeg,.png,.pdf,.gif,.jfif,.webp">

                        @if(count($addrAadhaarFiles) > 0)
                            <div class="mt-2 p-2 bg-light rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <small class="text-muted fw-bold">Existing Document(s):</small>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-primary" onclick="showLeadKycGallery('Address Aadhaar', {{ json_encode(array_column($addrAadhaarFiles, 'url')) }})">
                                        <i class="bi bi-eye"></i> View All
                                    </button>
                                </div>
                                @foreach($addrAadhaarFiles as $af)
                                    <div class="d-flex align-items-center justify-content-between small text-truncate py-1">
                                        <span class="text-truncate" title="{{ $af['name'] }}">
                                            <i class="bi {{ $af['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                            {{ $af['name'] }}
                                        </span>
                                        <div class="d-flex gap-1 ms-2">
                                            <a href="{{ $af['url'] }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0 px-1"><i class="bi bi-box-arrow-up-right"></i></a>
                                            <a href="{{ $af['url'] }}" download="{{ $af['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <!-- Address Driving Licence -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Driving Licence</label>
                        <input type="file" name="addr_driving_licence[]" class="form-control lead-kyc-file-input" multiple accept=".jpg,.jpeg,.png,.pdf,.gif,.jfif,.webp">

                        @if(count($addrDlFiles) > 0)
                            <div class="mt-2 p-2 bg-light rounded-3 border">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <small class="text-muted fw-bold">Existing Document(s):</small>
                                    <button type="button" class="btn btn-sm btn-link p-0 text-primary" onclick="showLeadKycGallery('Address Driving Licence', {{ json_encode(array_column($addrDlFiles, 'url')) }})">
                                        <i class="bi bi-eye"></i> View All
                                    </button>
                                </div>
                                @foreach($addrDlFiles as $df)
                                    <div class="d-flex align-items-center justify-content-between small text-truncate py-1">
                                        <span class="text-truncate" title="{{ $df['name'] }}">
                                            <i class="bi {{ $df['ext'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-success' }} me-1"></i>
                                            {{ $df['name'] }}
                                        </span>
                                        <div class="d-flex gap-1 ms-2">
                                            <a href="{{ $df['url'] }}" target="_blank" class="btn btn-outline-secondary btn-xs py-0 px-1"><i class="bi bi-box-arrow-up-right"></i></a>
                                            <a href="{{ $df['url'] }}" download="{{ $df['name'] }}" class="btn btn-outline-success btn-xs py-0 px-1"><i class="bi bi-download"></i></a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- ================= PHOTO & SIGNATURE TAB ================= -->
            <div class="tab-pane fade" id="lead-photosign" role="tabpanel" aria-labelledby="lead-photosign-tab">
                <h6 class="fw-bold mb-3 text-dark">Photo &amp; Signature</h6>

                <div class="row g-3">
                    <!-- Passport Size Photo -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Passport Size Photo</label>
                        <input type="file" name="photo[]" class="form-control lead-kyc-file-input" accept=".jpg,.jpeg,.png,.gif,.jfif,.webp">

                        @if(count($photoFiles) > 0)
                            <div class="mt-2 p-2 bg-light rounded-3 border d-flex align-items-center gap-3">
                                <img src="{{ $photoFiles[0]['url'] }}" alt="Photo Preview" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                <div class="flex-grow-1 text-truncate">
                                    <span class="fw-semibold small d-block text-truncate">{{ $photoFiles[0]['name'] }}</span>
                                    <small class="text-muted">Current Photo</small>
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showLeadKycGallery('Passport Photo', ['{{ $photoFiles[0]['url'] }}'])"><i class="bi bi-eye"></i></button>
                                    <a href="{{ $photoFiles[0]['url'] }}" download="{{ $photoFiles[0]['name'] }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Signature -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Signature</label>
                        <input type="file" name="signature[]" class="form-control lead-kyc-file-input" accept=".jpg,.jpeg,.png,.gif,.jfif,.webp">

                        @if(count($signFiles) > 0)
                            <div class="mt-2 p-2 bg-light rounded-3 border d-flex align-items-center gap-3">
                                <img src="{{ $signFiles[0]['url'] }}" alt="Signature Preview" class="rounded border bg-white p-1" style="width: 70px; height: 40px; object-fit: contain;">
                                <div class="flex-grow-1 text-truncate">
                                    <span class="fw-semibold small d-block text-truncate">{{ $signFiles[0]['name'] }}</span>
                                    <small class="text-muted">Current Signature</small>
                                </div>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="showLeadKycGallery('Signature', ['{{ $signFiles[0]['url'] }}'])"><i class="bi bi-eye"></i></button>
                                    <a href="{{ $signFiles[0]['url'] }}" download="{{ $signFiles[0]['name'] }}" class="btn btn-outline-success btn-sm"><i class="bi bi-download"></i></a>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Document Viewer Modal --}}
<div class="modal fade" id="leadKycGalleryModal" tabindex="-1" aria-labelledby="leadKycGalleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-semibold fs-6" id="leadKycGalleryModalLabel">Document Viewer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <div id="leadKycGalleryContainer"></div>
            </div>
            <div class="modal-footer border-top py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    .lead-kyc-card .nav-tabs-bordered {
        border-bottom: 2px solid #e2e8f0;
    }
    .lead-kyc-card .nav-tabs-bordered .nav-link {
        margin-bottom: -2px;
        border: none;
        border-bottom: 2px solid transparent;
        color: #64748b;
        padding: 0.6rem 1.25rem;
    }
    .lead-kyc-card .nav-tabs-bordered .nav-link.active {
        color: #0f172a;
        border-bottom-color: #0f172a;
        background: transparent;
    }
    .kyc-file-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;
    }
    .kyc-preview-item {
        display: flex;
        align-items: center;
        gap: 7px;
        max-width: 100%;
        padding: 4px 8px;
        border: 1px solid #dce2ea;
        border-radius: 7px;
        background: #f8fafc;
        color: #52657d;
        font-size: 12px;
    }
    .kyc-preview-item img {
        width: 38px;
        height: 38px;
        border-radius: 4px;
        object-fit: cover;
    }
    .kyc-preview-name {
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .border-bottom-dashed {
        border-bottom: 1px dashed #e2e8f0;
    }
</style>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Toggle Aadhaar Single vs Front/Back
        const aadhaarTypeSelect = document.getElementById('lead_aadhaar_type_select');
        const aadhaarSingleDiv = document.getElementById('lead_aadhaar_single_div');
        const aadhaarFrontBackDiv = document.getElementById('lead_aadhaar_front_back_div');
        const aadhaarSingleInput = document.getElementById('lead_aadhaar_single_input');
        const aadhaarMultiInputs = document.querySelectorAll('.lead-aadhaar-multi-input');

        if (aadhaarTypeSelect) {
            aadhaarTypeSelect.addEventListener('change', function() {
                if (this.value === 'front_back') {
                    if (aadhaarSingleDiv) aadhaarSingleDiv.style.display = 'none';
                    if (aadhaarSingleInput) aadhaarSingleInput.disabled = true;
                    if (aadhaarFrontBackDiv) aadhaarFrontBackDiv.style.display = 'block';
                    aadhaarMultiInputs.forEach(i => i.disabled = false);
                } else {
                    if (aadhaarFrontBackDiv) aadhaarFrontBackDiv.style.display = 'none';
                    aadhaarMultiInputs.forEach(i => i.disabled = true);
                    if (aadhaarSingleDiv) aadhaarSingleDiv.style.display = 'block';
                    if (aadhaarSingleInput) aadhaarSingleInput.disabled = false;
                }
            });
        }

        // Live file selection preview
        const fileInputs = document.querySelectorAll('.lead-kyc-file-input');
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const parentCol = this.closest('.col-md-4, .col-md-6') || this.parentElement;
                let existingPreview = parentCol.querySelector('.kyc-file-preview');
                if (existingPreview) existingPreview.remove();

                if (!this.files || !this.files.length) return;

                const previewDiv = document.createElement('div');
                previewDiv.className = 'kyc-file-preview';

                Array.from(this.files).forEach(file => {
                    const item = document.createElement('div');
                    item.className = 'kyc-preview-item';

                    if (file.type && file.type.startsWith('image/')) {
                        const img = document.createElement('img');
                        img.src = URL.createObjectURL(file);
                        img.alt = file.name;
                        item.appendChild(img);
                    } else {
                        const icon = document.createElement('i');
                        icon.className = 'bi bi-file-earmark-pdf text-danger fs-5';
                        item.appendChild(icon);
                    }

                    const nameSpan = document.createElement('span');
                    nameSpan.className = 'kyc-preview-name';
                    nameSpan.textContent = file.name;
                    item.appendChild(nameSpan);

                    previewDiv.appendChild(item);
                });

                this.insertAdjacentElement('afterend', previewDiv);
            });
        });
    });

    // Gallery viewer function
    function showLeadKycGallery(title, files) {
        const modal = new bootstrap.Modal(document.getElementById('leadKycGalleryModal'));
        const label = document.getElementById('leadKycGalleryModalLabel');
        const container = document.getElementById('leadKycGalleryContainer');

        label.textContent = title + ' Viewer';
        container.innerHTML = '';

        if (!files || !files.length) {
            container.innerHTML = '<p class="text-muted">No files available to display.</p>';
            modal.show();
            return;
        }

        files.forEach(fileUrl => {
            const fileName = fileUrl.split('/').pop();
            const ext = fileName.split('.').pop().toLowerCase();
            const isImg = ['jpg', 'jpeg', 'png', 'gif', 'jfif', 'webp', 'svg'].includes(ext);

            const fileCard = document.createElement('div');
            fileCard.className = 'mb-4 pb-3 border-bottom';

            if (isImg) {
                fileCard.innerHTML = `
                    <h6 class="text-truncate fw-semibold mb-2">${fileName}</h6>
                    <img src="${fileUrl}" class="img-fluid rounded border mb-2 shadow-sm" style="max-height: 420px; object-fit: contain;">
                    <div class="mt-2">
                        <a href="${fileUrl}" target="_blank" class="btn btn-dark-blue btn-sm px-3">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Open Original
                        </a>
                        <a href="${fileUrl}" download="${fileName}" class="btn btn-success btn-sm px-3 ms-2">
                            <i class="bi bi-download me-1"></i> Download
                        </a>
                    </div>
                `;
            } else {
                fileCard.innerHTML = `
                    <div class="p-4 bg-light rounded-3 border mb-2">
                        <i class="bi bi-file-earmark-pdf text-danger display-4 mb-2 d-block"></i>
                        <h6 class="fw-semibold text-truncate mb-3">${fileName}</h6>
                        <a href="${fileUrl}" target="_blank" class="btn btn-dark-blue btn-sm px-3">
                            <i class="bi bi-eye me-1"></i> View PDF
                        </a>
                        <a href="${fileUrl}" download="${fileName}" class="btn btn-success btn-sm px-3 ms-2">
                            <i class="bi bi-download me-1"></i> Download PDF
                        </a>
                    </div>
                `;
            }
            container.appendChild(fileCard);
        });

        modal.show();
    }
</script>
