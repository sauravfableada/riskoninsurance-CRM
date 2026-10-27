<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 py-3 px-4">
        <h5 class="fw-bold mb-0">Select Template</h5>
    </div>
    <div class="card-body px-4 pb-4" style="background:#e6f0fb;">
        <div class="d-flex overflow-auto py-3" id="templateScroller" style="gap: 1rem;">
            @foreach($templates as $tpl)
                <div class="flex-shrink-0 border rounded shadow-sm bg-white p-3 template-card"
                     data-template-id="{{ $tpl->id }}"
                     style="width: 320px; cursor:pointer;">
                    <div class="text-center fw-semibold mb-2">Template {{ $loop->iteration }}</div>
                    <div class="border rounded bg-light p-2" style="height: 420px; overflow:auto;">
                        {!! $tpl->content !!}
                    </div>
                </div>
            @endforeach
        </div>
        <input type="hidden" name="template_id" id="selectedTemplateId"
               value="{{ old('template_id', optional($record)->template_id) }}" form="emailMarketingForm">
        @error('template_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        <div class="mt-2 small text-muted">Scroll horizontally and click to select a template.</div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 px-4">
        <h5 class="fw-bold mb-0">Template Details</h5>
    </div>
    <form id="emailMarketingForm" method="POST" action="{{ $action }}" enctype="multipart/form-data">
        @csrf
        @if($method !== 'POST')
            @method($method)
        @endif
        <div class="card-body px-4 pb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Template Name <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           value="{{ old('name', optional($record)->name) }}"
                           class="form-control @error('name') is-invalid @enderror">
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4">
                    <label class="form-label">Template Status <span class="text-danger">*</span></label>
                    @php $statusVal = old('status', optional($record)->status ?? 'draft'); @endphp
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="draft" {{ $statusVal === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ $statusVal === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="archived" {{ $statusVal === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <div class="small text-muted">
                        Image URLs will replace placeholders like <code>[IMG1]</code>, <code>[IMG2]</code>, <code>[IMG3]</code>.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Image 1</label>
                    <input type="file" name="image_1"
                           class="form-control @error('image_1') is-invalid @enderror" onchange="previewImage(this, 'image1Preview')">
                    @error('image_1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @php
                        $img1 = optional($record)->image_1 ? asset('storage/' . str_replace('\\', '/', $record->image_1)) : null;
                    @endphp
                    @if($img1)
                        <div class="mt-2">
                            <img id="image1Preview" src="{{ $img1 }}" class="img-fluid rounded border" alt="Image 1 Preview">
                        </div>
                    @else
                        <img id="image1Preview" src="" class="img-fluid rounded border d-none" alt="Image 1 Preview">
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">Image 2</label>   
                    <input type="file" name="image_2"
                           class="form-control @error('image_2') is-invalid @enderror" onchange="previewImage(this, 'image2Preview')">
                    @error('image_2') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @php
                        $img2 = optional($record)->image_2 ? asset('storage/' . str_replace('\\', '/', $record->image_2)) : null;
                    @endphp
                    @if($img2)
                        <div class="mt-2">
                            <img id="image2Preview" src="{{ $img2 }}" class="img-fluid rounded border" alt="Image 2 Preview">
                        </div>
                    @else
                        <img id="image2Preview" src="" class="img-fluid rounded border d-none" alt="Image 2 Preview">
                    @endif
                </div>

                <div class="col-md-4">
                    <label class="form-label">Image 3</label>
                    <input type="file" name="image_3"
                           class="form-control @error('image_3') is-invalid @enderror" onchange="previewImage(this, 'image3Preview')">
                    @error('image_3') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @php
                        $img3 = optional($record)->image_3 ? asset('storage/' . str_replace('\\', '/', $record->image_3)) : null;
                    @endphp
                    @if($img3)
                        <div class="mt-2">
                            <img id="image3Preview" src="{{ $img3 }}" class="img-fluid rounded border" alt="Image 3 Preview">
                        </div>
                    @else
                        <img id="image3Preview" src="" class="img-fluid rounded border d-none" alt="Image 3 Preview">
                    @endif
                </div>
            </div>
        </div>
        <div class="card-footer bg-body-tertiary border-top px-4 py-3 d-flex justify-content-between">
            <div class="text-danger small" id="templateSelectError" style="display:none;">
                Please select a template from the list above.
            </div>
            <div>
                <a href="{{ route('marketing.email_marketing_templates.index') }}" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-dark-blue px-4 ms-2">
                    <i class="bi bi-save me-1"></i> {{ $submitLabel }}
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/js/email-marketing-templates.js' : '/assets/js/email-marketing-templates.js') }}"></script>
@endpush
