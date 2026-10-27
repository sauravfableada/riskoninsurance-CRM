@extends('layouts.app')

@section('page_title', 'Configuration - Form Builder')

@section('content')
<div class="container-fluid">
    {{-- Consistent CRM Header Section --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 fw-bold text-dark">Form Builder: <span class="text-indigo">{{ $module }}s</span></h1>
            <p class="text-muted small mb-0">Drag field types into the workspace and configure them all at once.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('settings.custom-fields.index', ['module' => $module]) }}" class="btn btn-outline-secondary px-4">
                <i class="bi bi-arrow-left me-1"></i> Back
            </a>
            <button type="submit" form="bulkFieldForm" class="btn btn-indigo btn-lg px-5 text-white fw-bold shadow-sm">
                <i class="bi bi-save2 me-2"></i> Save All Fields
            </button>
        </div>
    </div>

    <div class="row g-4">
        {{-- Left Side: Field Palette --}}
        <div class="col-lg-3">
            <div class="sticky-top" style="top: 20px;">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <div class="palette-icon me-3 bg-indigo">
                                <i class="bi bi-grid-3x3-gap-fill text-white"></i>
                            </div>
                            <h6 class="fw-bold m-0 text-dark">Field Palette</h6>
                        </div>
                        
                        <div id="fieldPalette" class="d-flex flex-column gap-2">
                            <div class="field-palette-item" data-type="text">
                                <i class="bi bi-fonts me-2 text-primary"></i>
                                <span class="fw-medium small">Single Line Text</span>
                                <i class="bi bi-grip-vertical ms-auto text-muted"></i>
                            </div>

                            <div class="field-palette-item" data-type="textarea">
                                <i class="bi bi-text-paragraph me-2 text-success"></i>
                                <span class="fw-medium small">Multi-line Paragraph</span>
                                <i class="bi bi-grip-vertical ms-auto text-muted"></i>
                            </div>

                            <div class="field-palette-item" data-type="number">
                                <i class="bi bi-hash me-2 text-warning"></i>
                                <span class="fw-medium small">Number Input</span>
                                <i class="bi bi-grip-vertical ms-auto text-muted"></i>
                            </div>

                            <div class="field-palette-item" data-type="select">
                                <i class="bi bi-caret-down-square me-2 text-info"></i>
                                <span class="fw-medium small">Dropdown Menu</span>
                                <i class="bi bi-grip-vertical ms-auto text-muted"></i>
                            </div>

                            <div class="field-palette-item" data-type="date">
                                <i class="bi bi-calendar-date me-2 text-danger"></i>
                                <span class="fw-medium small">Date Selector</span>
                                <i class="bi bi-grip-vertical ms-auto text-muted"></i>
                            </div>
                        </div>

                        <div class="mt-4 p-3 bg-light rounded-3 border-dashed-muted">
                            <p class="extra-small text-muted mb-0 text-center">
                                <i class="bi bi-mouse me-1"></i> Drag to add fields
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-indigo-subtle border-0 rounded-4 p-4 shadow-sm">
                    <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb me-2"></i>Quick Tip</h6>
                    <p class="small mb-0 opacity-75">You can drag multiple fields into the workspace to build your form faster!</p>
                </div>
            </div>
        </div>

        {{-- Right Side: Workspace --}}
        <div class="col-lg-9">
            <form action="{{ route('settings.custom-fields.store') }}" method="POST" id="bulkFieldForm">
                @csrf
                <input type="hidden" name="module" value="{{ $module }}">
                
                <div id="workspaceContainer" class="workspace-area">
                    {{-- Target for Sortable --}}
                    <div id="fieldWorkspace" class="d-flex flex-column gap-3 min-vh-50">
                        {{-- Empty State (Now inside the target) --}}
                        <div id="emptyWorkspaceState" class="text-center py-5 my-auto">
                            <div class="display-1 text-muted opacity-25 mb-3">
                                <i class="bi bi-plus-square-dotted"></i>
                            </div>
                            <h4 class="text-muted fw-bold">Your Workspace is Empty</h4>
                            <p class="text-muted">Drag fields from the palette here to start building.</p>
                        </div>
                    </div>
                </div>

                {{-- Bulk Action Bottom Bar --}}
                <div id="saveBar" class="mt-4 pb-5" style="display: none;">
                    <div class="card bg-indigo text-white border-0 shadow-lg rounded-4 overflow-hidden">
                        <div class="card-body p-4 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-1">Ready to go?</h5>
                                <p class="mb-0 small opacity-75">Review your <span id="fieldCountBadge" class="badge bg-white text-indigo px-2">0</span> fields and save the form.</p>
                            </div>
                            <button type="submit" class="btn btn-light btn-lg px-5 fw-bold text-indigo">
                                <i class="bi bi-check2-circle me-2"></i>Save All Changes
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Template for new field cards --}}
<template id="fieldCardTemplate">
    <div class="card field-card shadow-sm border-0 animate__animated animate__fadeInUp">
        <div class="card-body p-4">
            <div class="d-flex align-items-start justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <div class="type-badge me-3">
                        <i class="bi bi-fonts text-white"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark type-label">Single Line Text</h6>
                        <span class="extra-small text-muted text-uppercase fw-bold type-key">TEXT</span>
                    </div>
                </div>
                <button type="button" class="btn btn-sm crm-action-btn border-0 rounded-pill remove-field">
                    <i class="bi bi-trash text-danger-fill"></i>
                </button>
            </div>

            <div class="row g-3">
                <input type="hidden" name="fields[INDEX][type]" class="field-type-input">
                
                <div class="col-md-6">
                    <label class="form-label extra-small fw-bold text-muted">FIELD TITLE</label>
                    <input type="text" name="fields[INDEX][label]" class="form-control form-control-lg field-label-input" placeholder="e.g. Passport Number" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label extra-small fw-bold text-muted">FIELD ID (INTERNAL REFERENCE)</label>
                    <input type="text" name="fields[INDEX][name]" class="form-control form-control-lg field-name-input" placeholder="e.g. passport_number" required>
                </div>

                <div class="col-md-12 options-group" style="display: none;">
                    <label class="form-label extra-small fw-bold text-muted">DROPDOWN OPTIONS</label>
                    <textarea name="fields[INDEX][options]" class="form-control" rows="2" placeholder="Option A, Option B, Option C"></textarea>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" name="fields[INDEX][is_required]" value="1" id="req_INDEX">
                        <label class="form-check-label small fw-bold" for="req_INDEX">Required field?</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-check form-switch pt-2">
                        <input class="form-check-input" type="checkbox" name="fields[INDEX][is_active]" value="1" id="act_INDEX" checked>
                        <label class="form-check-label small fw-bold" for="act_INDEX">Field is active</label>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="d-flex align-items-center">
                        <label class="form-label small fw-bold text-muted mb-0 me-2">Order:</label>
                        <input type="number" name="fields[INDEX][sort_order]" class="form-control form-control-sm w-50 sort-order-input" value="0">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer bg-light border-0 py-2 d-flex align-items-center justify-content-center drag-handle" style="cursor: grab;">
            <i class="bi bi-three-dots text-muted opacity-50"></i>
            <span class="ms-2 extra-small text-muted fw-bold">DRAG TO REORDER</span>
        </div>
    </div>
</template>

<style>
    .text-indigo { color: #1a237e !important; }
    .bg-indigo { background: #1a237e !important; }
    .alert-indigo-subtle { background: #e8eaf6; color: #1a237e; }
    .btn-indigo { background: #1a237e !important; border-color: #1a237e !important; }
    
    .palette-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .field-palette-item {
        padding: 0.75rem 1rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        cursor: grab;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .field-palette-item:hover {
        border-color: #1a237e;
        transform: translateX(5px);
        background: #f8fafc;
    }

    .workspace-area {
        min-height: 500px;
        background: #f1f5f9;
        border: 3px dashed #cbd5e1;
        border-radius: 2rem;
        padding: 2rem;
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
    }
    .workspace-area.drag-active {
        background: #e2e8f0;
        border-color: #1a237e;
    }

    .field-card {
        border-radius: 1.25rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .field-card:hover { transform: scale(1.01); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important; }

    .type-badge {
        width: 44px;
        height: 44px;
        background: #1a237e;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .extra-small { font-size: 0.7rem; letter-spacing: 0.5px; }
    .min-vh-50 { min-height: 450px; flex-grow: 1; }
    
    .animate__fadeInUp { animation-duration: 0.4s; }

    /* Sortable.js helper classes */
    .sortable-ghost { opacity: 0.4; background: #e2e8f0 !important; }
    .sortable-drag { opacity: 0.9; }
</style>

@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure Sortable is actually loaded
        if (typeof Sortable === 'undefined') {
            console.error('Sortable.js failed to load from CDN. Falling back to click-only mode.');
            window.crmAlert('Notice: Interface might be slower due to external library loading issues. You can still click field types to add them.');
        }

        const palette = document.getElementById('fieldPalette');
        const workspace = document.getElementById('fieldWorkspace');
        const container = document.getElementById('workspaceContainer');
        const emptyState = document.getElementById('emptyWorkspaceState');
        const saveBar = document.getElementById('saveBar');
        const countBadge = document.getElementById('fieldCountBadge');
        let fieldIndex = 0;

        // Palette setup
        if (typeof Sortable !== 'undefined') {
            Sortable.create(palette, {
                group: {
                    name: 'fields',
                    pull: 'clone',
                    put: false
                },
                sort: false,
                animation: 150,
                onStart: function() { container.classList.add('drag-active'); },
                onEnd: function() { container.classList.remove('drag-active'); }
            });

            // Workspace setup
            Sortable.create(workspace, {
                group: 'fields',
                animation: 150,
                handle: '.drag-handle',
                draggable: '.field-card', // Only cards are draggable, not empty state
                onAdd: function (evt) {
                    const type = evt.item.dataset.type;
                    addFieldToWorkspace(type);
                    evt.item.remove(); // Remove the clone placeholder
                },
                onUpdate: function() {
                    reorderIndices();
                }
            });
        }

        // Manual adding logic
        document.querySelectorAll('.field-palette-item').forEach(item => {
            item.addEventListener('click', function() {
                addFieldToWorkspace(this.dataset.type);
                this.animate([{ transform: 'scale(0.95)' }, { transform: 'scale(1)' }], 200);
            });
        });

        function addFieldToWorkspace(type) {
            const template = document.getElementById('fieldCardTemplate').content.cloneNode(true);
            const card = template.querySelector('.field-card');
            
            // Set type data
            const typeInfo = {
                'text': { icon: 'bi-fonts', label: 'Single Line Text', color: '#0a2540' },
                'textarea': { icon: 'bi-text-paragraph', label: 'Multi-line Paragraph', color: '#198754' },
                'number': { icon: 'bi-hash', label: 'Number Input', color: '#ffc107' },
                'select': { icon: 'bi-caret-down-square', label: 'Dropdown Menu', color: '#0dcaf0' },
                'date': { icon: 'bi-calendar-date', label: 'Date Selector', color: '#dc3545' }
            };

            const info = typeInfo[type] || typeInfo['text'];
            card.querySelector('.type-label').textContent = info.label;
            card.querySelector('.type-key').textContent = type.toUpperCase();
            card.querySelector('.type-badge i').className = `bi ${info.icon} text-white`;
            card.querySelector('.type-badge').style.backgroundColor = info.color;
            card.querySelector('.field-type-input').value = type;

            if (type === 'select') card.querySelector('.options-group').style.display = 'block';

            // Add to workspace
            const index = fieldIndex++;
            card.innerHTML = card.innerHTML.replace(/INDEX/g, index);
            workspace.appendChild(card);
            
            updateUIState();
            setupFieldEvents(card);
            
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        function setupFieldEvents(card) {
            const labelInput = card.querySelector('.field-label-input');
            const nameInput = card.querySelector('.field-name-input');
            
            labelInput.addEventListener('input', function() {
                if (!nameInput.dataset.edited) {
                    nameInput.value = this.value.toLowerCase().replace(/[^\w\s-]/g, '').replace(/\s+/g, '_').replace(/-+/g, '_');
                }
            });

            nameInput.addEventListener('input', function() { this.dataset.edited = true; });

            card.querySelector('.remove-field').addEventListener('click', function() {
                card.classList.add('animate__fadeOutRight');
                setTimeout(() => { card.remove(); updateUIState(); }, 400);
            });
        }

        function updateUIState() {
            const cards = workspace.querySelectorAll('.field-card');
            const count = cards.length;
            emptyState.style.display = count > 0 ? 'none' : 'block';
            saveBar.style.display = count > 0 ? 'block' : 'none';
            countBadge.textContent = count;
            
            if (count > 0) {
                container.style.borderStyle = 'solid';
                container.style.borderColor = '#e2e8f0';
                container.style.background = '#f8fafc';
            } else {
                container.style.borderStyle = 'dashed';
                container.style.borderColor = '#cbd5e1';
                container.style.background = '#f1f5f9';
            }
        }

        function reorderIndices() {
            workspace.querySelectorAll('.field-card').forEach((card, i) => {
                card.querySelector('.sort-order-input').value = i;
            });
        }
    });
</script>
@endpush
@endsection
