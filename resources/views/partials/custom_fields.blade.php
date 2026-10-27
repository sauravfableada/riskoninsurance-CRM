@php
    $moduleName = class_basename($model ?? $module);
    $customFields = \App\Models\CustomField::where('module', $moduleName)->where('is_active', true)->orderBy('sort_order')->get();
@endphp

@if($customFields->count() > 0)
    <div class="col-12 mt-4">
        <h6 class="fw-bold text-primary text-uppercase small mb-3">Additional Information</h6>
        <div class="row g-3">
            @foreach($customFields as $field)
                @php
                    $value = isset($model) ? $model->getCustomFieldValue($field->name) : old('custom_fields.' . $field->name);
                    $fieldName = "custom_fields[{$field->name}]";
                @endphp
                <div class="col-md-6">
                    <label class="form-label fw-semibold">{{ $field->label }} @if($field->is_required)<span class="text-danger">*</span>@endif</label>
                    
                    @if($field->type === 'text')
                        <input type="text" name="{{ $fieldName }}" value="{{ $value }}" class="form-control" @required($field->is_required)>
                    @elseif($field->type === 'number')
                        <input type="number" name="{{ $fieldName }}" value="{{ $value }}" class="form-control" @required($field->is_required)>
                    @elseif($field->type === 'date')
                        <input type="date" name="{{ $fieldName }}" value="{{ $value }}" class="form-control" @required($field->is_required)>
                    @elseif($field->type === 'textarea')
                        <textarea name="{{ $fieldName }}" class="form-control" rows="2" @required($field->is_required)>{{ $value }}</textarea>
                    @elseif($field->type === 'select')
                        <select name="{{ $fieldName }}" class="form-select" @required($field->is_required)>
                            <option value="">-- Select --</option>
                            @foreach($field->options ?? [] as $option)
                                <option value="{{ $option }}" @selected($value == $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
