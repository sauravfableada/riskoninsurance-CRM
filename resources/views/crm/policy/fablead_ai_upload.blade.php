{{-- ===== FABLEAD AI POLICY UPLOAD: START — copy this complete view with FableadAiController.php and its routes ===== --}}
@extends('layouts.masters')
@section('page_title', 'Fablead AI — Upload Policy')
@section('masters_content')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<style>
/* ═══════════════════════════════════════════
   FABLEAD AI DESIGN SYSTEM
   ═══════════════════════════════════════════ */
   #policy_ai_form{
    overflow: scroll;
    height: calc(100vh - 130px);
    min-height: 628px;
   }
:root {
    --ai-navy:      #0f172a;
    --ai-navy-mid:  #1e293b;
    --ai-slate:     #334155;
    --ai-border:    #e2e8f0;
    --ai-bg:        #f8fafc;
    --ai-brown:     #7c3d2b;
    --ai-brown-lt:  #fef3ec;
    --ai-green:     #16a34a;
    --ai-green-lt:  #f0fdf4;
    --ai-amber:     #d97706;
    --ai-amber-lt:  #fffbeb;
    --ai-red:       #dc2626;
    --ai-red-lt:    #fef2f2;
    --ai-shadow:    0 1px 4px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.04);
    --ai-radius:    10px;
}

/* Page chrome */
.ai-page-wrap        { background: var(--ai-bg); min-height: 100vh; padding: 0; }
.ai-page-header      { background: #fff; border-bottom: 1px solid var(--ai-border); padding: 22px 32px; }
.ai-badge-label      { display: inline-flex; align-items: center; gap: 6px; background: var(--ai-brown-lt); color: var(--ai-brown); font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; border-radius: 20px; padding: 3px 12px; border: 1px solid #f0d5c4; }
.ai-page-title       { font-size: 1.55rem; font-weight: 700; color: var(--ai-navy); line-height: 1.2; }
.ai-page-sub         { color: #64748b; font-size: .9rem; }

/* Step tracker */
.ai-steps            { display: flex; align-items: center; gap: 0; padding: 18px 32px; background: #fff; border-bottom: 1px solid var(--ai-border); }
.ai-step             { display: flex; align-items: center; gap: 8px; font-size: .82rem; font-weight: 600; color: #94a3b8; }
.ai-step-dot         { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .72rem; font-weight: 700; border: 2px solid #cbd5e1; background: #fff; color: #94a3b8; }
.ai-step.active .ai-step-dot   { background: var(--ai-navy); border-color: var(--ai-navy); color: #fff; }
.ai-step.active      { color: var(--ai-navy); }
.ai-step.done .ai-step-dot     { background: var(--ai-green); border-color: var(--ai-green); color: #fff; }
.ai-step.done        { color: var(--ai-green); }
.ai-step-line        { flex: 1; height: 2px; background: #e2e8f0; margin: 0 8px; min-width: 32px; }
.ai-step-line.done   { background: var(--ai-green); }

/* Content wrapper */
.ai-content          { padding: 28px 32px; }

/* Upload card */
.ai-upload-card      { background: #fff; border: 1px solid var(--ai-border); border-radius: var(--ai-radius); box-shadow: var(--ai-shadow); max-width: 680px; margin: 0 auto; }
.ai-drop-zone        { border: 2px dashed #cbd5e1; border-radius: 8px; padding: 48px 24px; text-align: center; cursor: pointer; transition: all .2s; background: var(--ai-bg); margin: 24px; }
.ai-drop-zone:hover, .ai-drop-zone.drag-over { border-color: var(--ai-navy); background: #f1f5f9; }
.ai-drop-icon        { font-size: 2.5rem; color: #94a3b8; margin-bottom: 16px; }
.ai-drop-title       { font-size: 1.05rem; font-weight: 600; color: var(--ai-navy); margin-bottom: 6px; }
.ai-drop-sub         { font-size: .85rem; color: #64748b; }
.ai-review-notice   { color: var(--ai-amber); font-size: .8rem; font-weight: 600; }
.ai-file-list        { padding: 0 24px 20px; }
.ai-file-item        { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border: 1px solid var(--ai-border); border-radius: 8px; margin-bottom: 8px; background: #fff; }
.ai-file-icon        { font-size: 1.5rem; color: #dc2626; flex-shrink: 0; }
.ai-file-meta        { flex: 1; min-width: 0; }
.ai-file-name        { font-weight: 600; font-size: .88rem; color: var(--ai-navy); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ai-file-size        { font-size: .77rem; color: #64748b; }
.ai-file-status      { font-size: .77rem; font-weight: 600; color: var(--ai-green); }

/* Buttons */
.btn-ai-primary   { background: var(--ai-navy); color: #fff; border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: .88rem; letter-spacing: .01em; }
.btn-ai-primary:hover { background: var(--ai-navy-mid); color: #fff; }
.btn-ai-outline   { background: #fff; color: var(--ai-navy); border: 1px solid var(--ai-border); border-radius: 8px; padding: 10px 24px; font-weight: 600; font-size: .88rem; }
.btn-ai-outline:hover { background: var(--ai-bg); }
.btn-ai-save      { background: var(--ai-navy); color: #fff; border: none; border-radius: 8px; padding: 10px 28px; font-weight: 700; font-size: .9rem; }
.btn-ai-save:hover { background: var(--ai-navy-mid); color: #fff; }

/* Processing */
.ai-processing-card  { background: #fff; border: 1px solid var(--ai-border); border-radius: var(--ai-radius); box-shadow: var(--ai-shadow); max-width: 560px; margin: 0 auto; padding: 40px 32px; text-align: center; }
.ai-spinner          { width: 56px; height: 56px; border: 4px solid #e2e8f0; border-top-color: var(--ai-navy); border-radius: 50%; animation: ai-spin .8s linear infinite; margin: 0 auto 24px; }
@keyframes ai-spin { to { transform: rotate(360deg); } }
.ai-stage-list       { text-align: left; margin: 24px 0; }
.ai-stage-item       { display: flex; align-items: center; gap: 10px; padding: 7px 0; font-size: .87rem; color: #64748b; border-bottom: 1px solid var(--ai-border); }
.ai-stage-item:last-child { border-bottom: none; }
.ai-stage-item.active { color: var(--ai-navy); font-weight: 600; }
.ai-stage-item.done   { color: var(--ai-green); }
.ai-stage-dot        { width: 8px; height: 8px; border-radius: 50%; background: #cbd5e1; flex-shrink: 0; }
.ai-stage-item.active .ai-stage-dot { background: var(--ai-navy); }
.ai-stage-item.done .ai-stage-dot   { background: var(--ai-green); }
.ai-progress-bar     { height: 4px; background: #e2e8f0; border-radius: 4px; overflow: hidden; margin-top: 20px; }
.ai-progress-fill    { height: 100%; background: var(--ai-navy); border-radius: 4px; animation: ai-progress 2s ease-in-out infinite; }
@keyframes ai-progress { 0%{width:0%} 50%{width:70%} 100%{width:100%} }

/* Review layout */
.ai-review-wrap      { display: grid; grid-template-columns: 55fr 45fr; gap: 20px; align-items: start; }
@media (max-width: 992px) { .ai-review-wrap { grid-template-columns: 1fr; } }

/* Form panel */
.ai-form-panel       { background: #fff; border: 1px solid var(--ai-border); border-radius: var(--ai-radius); box-shadow: var(--ai-shadow); }
.ai-form-header      { padding: 16px 20px; border-bottom: 1px solid var(--ai-border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.ai-form-header-title { font-weight: 700; color: var(--ai-navy); font-size: 1rem; }
.ai-form-body        { padding: 20px; }
.ai-section-title    { font-size: .78rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #64748b; border-bottom: 1px solid var(--ai-border); padding-bottom: 8px; margin: 20px 0 14px; }
.ai-section-title:first-child { margin-top: 0; }

/* Field confidence indicator */
.ai-field-wrap       { position: relative; margin-bottom: 14px; }
.ai-field-label      { font-size: .78rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
.ai-conf-badge       { font-size: .7rem; font-weight: 700; border-radius: 10px; padding: 1px 7px; cursor: pointer; }
.ai-conf-high        { background: var(--ai-green-lt); color: var(--ai-green); border: 1px solid #bbf7d0; }
.ai-conf-mid         { background: var(--ai-amber-lt); color: var(--ai-amber); border: 1px solid #fde68a; }
.ai-conf-low         { background: var(--ai-red-lt); color: var(--ai-red); border: 1px solid #fecaca; }
.ai-conf-none        { background: #f1f5f9; color: #94a3b8; border: 1px solid #e2e8f0; }

/* Form controls */
.ai-form-panel .form-control,
.ai-form-panel .select2-container .select2-selection--single {
    border: 1px solid var(--ai-border); border-radius: 7px; height: 40px; font-size: .87rem; background: #f8fafc;
}
.ai-form-panel .form-control:focus { border-color: var(--ai-navy); box-shadow: 0 0 0 3px rgba(15,23,42,.1); background: #fff; }
.ai-form-panel .select2-container .select2-selection--single .select2-selection__arrow { height: 38px !important; }
.ai-form-panel .select2-container .select2-selection--single .select2-selection__rendered { line-height: 38px !important; font-size: .87rem; color: #374151; }
.ai-form-panel textarea.form-control { height: auto; }

/* AI-filled field highlight */
.ai-filled { border-color: #93c5fd !important; background: #eff6ff !important; }
.ai-filled:focus { border-color: var(--ai-navy) !important; background: #fff !important; }

/* Small Add Icon Button next to dropdowns */
.btn-ai-add {
    width: 38px;
    min-width: 38px;
    height: 38px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 7px;
    font-size: 13px;
    flex-shrink: 0;
    background: var(--ai-navy);
    color: #fff;
    border: none;
    transition: all 0.2s ease;
}
.btn-ai-add:hover {
    background: var(--ai-navy-mid);
    color: #fff;
}

/* PDF panel */
.ai-pdf-panel        { background: #fff; border: 1px solid var(--ai-border); border-radius: var(--ai-radius); box-shadow: var(--ai-shadow); position: sticky; top: 20px; }
.ai-pdf-header       { padding: 14px 16px; border-bottom: 1px solid var(--ai-border); display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.ai-pdf-header-title { font-weight: 700; color: var(--ai-navy); font-size: .9rem; display: flex; align-items: center; gap: 8px; }
.ai-pdf-controls     { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.ai-pdf-btn          { background: var(--ai-bg); border: 1px solid var(--ai-border); border-radius: 6px; padding: 5px 10px; font-size: .78rem; cursor: pointer; color: var(--ai-navy); }
.ai-pdf-btn:hover    { background: #e2e8f0; }
.ai-pdf-page         { font-size: .78rem; font-weight: 600; color: var(--ai-slate); }
.ai-pdf-frame        { width: 100%; height: 620px; border: none; border-radius: 0 0 var(--ai-radius) var(--ai-radius); }
@media (max-width: 992px) { .ai-pdf-frame { height: 400px; } }

/* Bottom bar */
.ai-bottom-bar       { background: #fff; border: 1px solid var(--ai-border); border-radius: var(--ai-radius); box-shadow: var(--ai-shadow); padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 20px; }
.ai-save-info        { font-size: .85rem; color: var(--ai-slate); text-align: center; flex: 1; }

/* Stats bar */
.ai-stats-bar        { display: flex; gap: 16px; padding: 14px 20px; border-bottom: 1px solid var(--ai-border); flex-wrap: wrap; }
.ai-stat             { display: flex; flex-direction: column; gap: 2px; }
.ai-stat-label       { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; }
.ai-stat-value       { font-size: .95rem; font-weight: 700; color: var(--ai-navy); }
.ai-stat-value.green { color: var(--ai-green); }
.ai-stat-value.amber { color: var(--ai-amber); }
.ai-stat-value.red   { color: var(--ai-red); }

/* Source popover */
.ai-source-popover   { background: var(--ai-navy); color: #fff; font-size: .77rem; border-radius: 8px; padding: 10px 14px; max-width: 260px; line-height: 1.5; }

/* Success screen */
.ai-success-wrap     { max-width: 480px; margin: 0 auto; text-align: center; padding: 60px 20px; }
.ai-success-icon     { width: 72px; height: 72px; background: var(--ai-green-lt); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
.ai-success-icon i   { font-size: 2rem; color: var(--ai-green); }

/* Error screen */
.ai-error-wrap       { max-width: 480px; margin: 0 auto; text-align: center; padding: 60px 20px; }
.ai-error-icon       { width: 72px; height: 72px; background: var(--ai-red-lt); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }

/* Dark mode */
html[data-theme="dark"] .ai-page-wrap,
html[data-theme="dark"] .ai-upload-card,
html[data-theme="dark"] .ai-processing-card,
html[data-theme="dark"] .ai-form-panel,
html[data-theme="dark"] .ai-pdf-panel,
html[data-theme="dark"] .ai-bottom-bar,
html[data-theme="dark"] .ai-page-header,
html[data-theme="dark"] .ai-steps      { background: #1e293b !important; border-color: #334155 !important; }
html[data-theme="dark"] .ai-drop-zone  { background: #0f172a; border-color: #475569; }
html[data-theme="dark"] .ai-page-title,
html[data-theme="dark"] .ai-form-header-title,
html[data-theme="dark"] .ai-step.active,
html[data-theme="dark"] .ai-stat-value { color: #f1f5f9 !important; }
html[data-theme="dark"] .ai-page-sub,
html[data-theme="dark"] .ai-drop-sub,
html[data-theme="dark"] .ai-file-size,
html[data-theme="dark"] .ai-stage-item { color: #94a3b8; }
html[data-theme="dark"] .ai-form-panel .form-control { background: #0f172a !important; border-color: #334155 !important; color: #f1f5f9 !important; }
html[data-theme="dark"] .ai-form-panel .select2-container .select2-selection--single { background: #0f172a !important; border-color: #334155 !important; }
html[data-theme="dark"] .ai-form-panel .select2-container .select2-selection--single .select2-selection__rendered { color: #f1f5f9 !important; }
html[data-theme="dark"] .ai-filled { background: #1e3a5f !important; border-color: #3b82f6 !important; }
html[data-theme="dark"] .ai-file-item { background: #0f172a; border-color: #334155; }
html[data-theme="dark"] .ai-section-title { border-color: #334155; }
html[data-theme="dark"] .ai-pdf-btn { background: #334155; border-color: #475569; color: #f1f5f9; }
html[data-theme="dark"] .ai-stats-bar { border-color: #334155; }
</style>

<div class="ai-page-wrap">

    {{-- ── PAGE HEADER ──────────────────────────────────────────────────── --}}
    <div class="ai-page-header">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="ai-badge-label mb-2"><i class="fas fa-magic"></i> Fablead AI</div>
                <h1 class="ai-page-title mb-1">Upload policy documents. Fablead AI does the rest.</h1>
                <p class="ai-page-sub mb-0">Extract policy information automatically, review the results, and save verified data to your insurance CRM.</p>
            </div>
            <a href="{{ route('policies.legacy.index') }}" class="btn-ai-outline btn d-flex align-items-center gap-2">
                <i class="fas fa-arrow-left"></i> Back to Policies
            </a>
        </div>
    </div>

    {{-- ── STEP TRACKER ─────────────────────────────────────────────────── --}}
    <div class="ai-steps" id="ai_steps">
        <div class="ai-step active" id="step_upload">
            <div class="ai-step-dot">1</div><span>Upload</span>
        </div>
        <div class="ai-step-line" id="line_1"></div>
        <div class="ai-step" id="step_extract">
            <div class="ai-step-dot">2</div><span>Extract</span>
        </div>
        <div class="ai-step-line" id="line_2"></div>
        <div class="ai-step" id="step_review">
            <div class="ai-step-dot">3</div><span>Review</span>
        </div>
        <div class="ai-step-line" id="line_3"></div>
        <div class="ai-step" id="step_save">
            <div class="ai-step-dot">4</div><span>Save</span>
        </div>
    </div>

    <div class="ai-content">

        {{-- ═══ STATE 1: UPLOAD ═══════════════════════════════════════════════ --}}
        <div id="view_upload">
            <div class="ai-upload-card">
                <div class="p-5 pb-0 text-center">
                    <h5 class="fw-bold" style="color:var(--ai-navy)">Upload a policy document</h5>
                    <p class="ai-review-notice mb-2" role="note">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        Please review all PDF-parsed details before saving the policy.
                    </p>
                    <p class="ai-page-sub">PDF only — Health, Motor, Fire, Burglary, Marine and more</p>
                </div>

                <div class="ai-drop-zone" id="drop_zone">
                    <div class="ai-drop-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="ai-drop-title">Drag &amp; drop your PDF here</div>
                    <div class="ai-drop-sub mb-3">or click to browse</div>
                    <label for="pdf_file_input" class="btn-ai-outline btn" style="cursor:pointer;">
                        <i class="fas fa-folder-open me-2"></i> Browse File
                    </label>
                    <input type="file" id="pdf_file_input" accept=".pdf" multiple style="display:none">
                    <div class="ai-drop-sub mt-3" style="font-size:.75rem">Max 20 MB per file &bull; PDF format only</div>
                </div>

                <div class="ai-file-list" id="file_list" style="display:none"></div>

                <div class="p-4 pt-0 text-center" id="upload_actions" style="display:none">
                    <button class="btn-ai-primary btn w-100 py-3" id="btn_analyze" style="font-size:1rem">
                        <i class="fas fa-magic me-2"></i> Analyze with Fablead AI
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ STATE 2: PROCESSING ════════════════════════════════════════════ --}}
        <div id="view_processing" style="display:none">
            <div class="ai-processing-card">
                <div class="ai-spinner"></div>
                <h5 class="fw-bold mb-2" style="color:var(--ai-navy)">Fablead AI is analyzing your policy</h5>
                <p class="ai-page-sub mb-0">Please wait — reading and extracting document data…</p>
                <p class="ai-page-sub mt-1" id="processing_filename" style="font-weight:600"></p>

                <div class="ai-stage-list">
                    <div class="ai-stage-item active" id="stage_1"><span class="ai-stage-dot"></span> Reading document</div>
                    <div class="ai-stage-item" id="stage_2"><span class="ai-stage-dot"></span> Detecting insurance company</div>
                    <div class="ai-stage-item" id="stage_3"><span class="ai-stage-dot"></span> Identifying policy type</div>
                    <div class="ai-stage-item" id="stage_4"><span class="ai-stage-dot"></span> Extracting policy details</div>
                    <div class="ai-stage-item" id="stage_5"><span class="ai-stage-dot"></span> Validating information</div>
                    <div class="ai-stage-item" id="stage_6"><span class="ai-stage-dot"></span> Preparing review</div>
                </div>
                <div class="ai-progress-bar"><div class="ai-progress-fill"></div></div>
            </div>
        </div>

        {{-- ═══ STATE 3: REVIEW ════════════════════════════════════════════════ --}}
        <div id="view_review" style="display:none">

            {{-- Sub-header --}}
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-0" style="color:var(--ai-navy)">Review extracted data</h5>
                    <p class="ai-page-sub mb-0">Correct any mistakes then save. Skip files you don't want to import.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="ai-badge-label"><i class="fas fa-check-circle"></i> Fablead AI Extracted</span>
                    <span class="ai-conf-badge ai-conf-high" id="overall_conf_badge">96% confidence</span>
                </div>
            </div>

            {{-- Stats bar --}}
            <div class="ai-form-panel mb-3">
                <div class="ai-stats-bar">
                    <div class="ai-stat"><div class="ai-stat-label">Policy Type</div><div class="ai-stat-value" id="stat_type">—</div></div>
                    <div class="ai-stat"><div class="ai-stat-label">Insurance Co.</div><div class="ai-stat-value" id="stat_company">—</div></div>
                    <div class="ai-stat"><div class="ai-stat-label">Fields Extracted</div><div class="ai-stat-value green" id="stat_extracted">0</div></div>
                    <div class="ai-stat"><div class="ai-stat-label">High Confidence</div><div class="ai-stat-value green" id="stat_high">0</div></div>
                    <div class="ai-stat"><div class="ai-stat-label">Needs Review</div><div class="ai-stat-value amber" id="stat_mid">0</div></div>
                    <div class="ai-stat"><div class="ai-stat-label">Missing</div><div class="ai-stat-value red" id="stat_missing">0</div></div>
                    <div class="ai-stat"><div class="ai-stat-label">Total Premium</div><div class="ai-stat-value" id="stat_premium">—</div></div>
                </div>
            </div>

            <div class="ai-review-wrap">

                {{-- ── LEFT: CRM FORM ──────────────────────────────────────── --}}
                <div class="ai-form-panel">
                    <div class="ai-form-header">
                        <div class="ai-form-header-title"><i class="fas fa-clipboard-list me-2" style="color:var(--ai-brown)"></i> AI Extracted Data</div>
                    </div>

                    <form id="policy_ai_form" action="javascript:void(0);" method="POST" onsubmit="return false;">
                        @csrf
                        <input type="hidden" name="base_url" id="base_url" value="{{ url('/') }}/">
                        <input type="hidden" name="upload_id" id="upload_id_input">

                        <div class="ai-form-body">

                            {{-- SARVAA AI VALIDATION ALERT --}}
                            <div id="ai_validation_alert" class="alert mb-4" style="display:none; background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-magic" style="color: #e11d48; margin-right: 8px;"></i>
                                    <strong style="color: #be123c;" id="ai_validation_title">FableadAI flagged fields for your review</strong>
                                </div>
                                <p style="color: #be123c; font-size: 0.9rem; margin-bottom: 8px;">Fields highlighted in red below need your attention:</p>
                                <ul id="ai_validation_list" style="color: #be123c; font-size: 0.85rem; padding-left: 20px; margin-bottom: 0;">
                                    <!-- Errors injected via JS -->
                                </ul>
                            </div>

                            {{-- POLICY DETAILS --}}
                            <div class="ai-section-title">Policy Details</div>
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <?php $s = session(); ?>
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">SELF / SUB AGENT</div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="staff_id" id="staff_id">
                                                    <option value="">-- Select Staff --</option>
                                                    <?php foreach ($staffs as $staff): ?>
                                                        <option value="<?= $staff['id'] ?>" <?= $staff['id'] == Auth::id() ? 'selected' : '' ?>><?= $staff['name'] ?? 'Unknown' ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalStaff" title="Add Sub Agent / Staff">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">POLICY NUMBER <span class="text-danger">*</span></div>
                                        <input type="text" class="form-control" name="policy_number" id="policy_number" placeholder="Extracted from PDF" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">FAMILY GROUP <span class="text-muted">(Optional)</span></div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="family_group_id" id="family_group_id">
                                                    <option value="">-- Select Family Group --</option>
                                                    <?php foreach ($familyGroups as $fg): ?>
                                                        <option value="<?= $fg['id'] ?>"><?= $fg['group_name'] ?? 'Unnamed' ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalFamilyGroup" title="Add Family Group">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">POLICY HOLDER NAME <span class="text-danger">*</span></div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="user_id" id="user_id" required>
                                                    <option value="">-- Select Holder --</option>
                                                    <?php foreach ($customers as $c): ?>
                                                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalPolicyHolder" title="Add Policy Holder">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">CUSTOMER EMAIL <span class="ai-conf-badge ai-conf-none" id="conf_customer_email"></span></div>
                                        <input type="email" class="form-control" name="customer_email" id="customer_email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">CUSTOMER PHONE <span class="ai-conf-badge ai-conf-none" id="conf_customer_phone"></span></div>
                                        <input type="text" class="form-control" name="customer_phone" id="customer_phone">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">ADDRESS <span class="ai-conf-badge ai-conf-none" id="conf_customer_address"></span></div>
                                        <input type="text" class="form-control" name="customer_address" id="customer_address">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_policy_type_id">
                                        <div class="ai-field-label">POLICY TYPE <span class="text-danger">*</span> <span class="ai-conf-badge ai-conf-none" id="conf_policy_type"></span></div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="policy_type_id" id="policy_type_id" required>
                                                    <option value="">-- Select Policy Type --</option>
                                                    <?php foreach ($policyTypes as $pt): ?>
                                                        <option value="<?= $pt['id'] ?>"><?= $pt['name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalPolicyType" title="Add Policy Type">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">POLICY SUB TYPE <span class="text-muted">(Optional)</span></div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="sub_type_id" id="sub_type_id">
                                                    <option value="">-- Select Sub Type --</option>
                                                    <?php foreach ($subTypes as $st): ?>
                                                        <option value="<?= $st['id'] ?>"><?= $st['name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalPolicySubType" title="Add Policy Sub Type">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">PRODUCT NAME <span class="ai-conf-badge ai-conf-none" id="conf_product_name"></span></div>
                                        <input type="text" class="form-control" name="product_name" id="product_name">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">REFERENCE <span class="ai-conf-badge ai-conf-none" id="conf_reference"></span></div>
                                        <input type="text" class="form-control" name="reference" id="reference" placeholder="Who referred this — pick or type">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">BUSINESS <span class="ai-conf-badge ai-conf-none" id="conf_business_type"></span></div>
                                        <select class="form-control select2-ai" name="business_type" id="business_type">
                                            <option value="">Auto-detect</option>
                                            <option value="New">New</option>
                                            <option value="Renewal">Renewal</option>
                                            <option value="Rollover">Rollover</option>
                                            <option value="Endorsement">Endorsement</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">COMMISSION (%) <span class="ai-conf-badge ai-conf-none" id="conf_commission_percentage"></span></div>
                                        <input type="number" step="0.01" class="form-control" name="commission_percentage" id="commission_percentage" placeholder="e.g. 10">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">SEGMENT</div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="segment_id" id="segment_id">
                                                    <option value="">-- Select Segment --</option>
                                                    <?php foreach ($segments as $seg): ?>
                                                        <option value="<?= $seg['id'] ?>"><?= $seg['name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalSegment" title="Add Segment">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_company_id">
                                        <div class="ai-field-label">INSURANCE COMPANY <span class="text-danger">*</span> <span class="ai-conf-badge ai-conf-none" id="conf_company"></span></div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="company_id" id="company_id" required>
                                                    <option value="">-- Select Company --</option>
                                                    <?php foreach ($companies as $c): ?>
                                                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalInsuranceCompany" title="Add Insurance Company">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">BRANCH</div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="branch_id" id="branch_id">
                                                    <option value="">-- Select Branch --</option>
                                                    <?php foreach ($branches as $b): ?>
                                                        <option value="<?= $b['id'] ?>"><?= $b['name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalBranch" title="Add Branch">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            {{-- BROKER / AGENT DETAILS --}}
                            <div class="ai-section-title mt-4">Broker / Agent Details</div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">MAP TO A SAVED BROKER</div>
                                        <div class="d-flex gap-2 align-items-center">
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <select class="form-control select2-ai" name="broker_id" id="broker_id">
                                                    <option value="">-- Select Broker --</option>
                                                    <?php foreach ($brokers as $br): ?>
                                                        <option value="<?= $br['id'] ?>"><?= $br['broker_name'] ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-dark-blue btn-ai-add" data-bs-toggle="modal" data-bs-target="#modalBroker" title="Add Broker">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">BROKER / AGENT NAME <span class="ai-conf-badge ai-conf-none" id="conf_broker_name"></span></div>
                                        <input type="text" class="form-control" name="agent_name" id="agent_name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">AGENT CODE <span class="ai-conf-badge ai-conf-none" id="conf_agent_code"></span></div>
                                        <input type="text" class="form-control" name="agent_code" id="agent_code">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">AGENT ID <span class="ai-conf-badge ai-conf-none" id="conf_agent_id"></span></div>
                                        <input type="text" class="form-control" name="agent_id" id="agent_id">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">BROKER CONTACT <span class="ai-conf-badge ai-conf-none" id="conf_broker_contact"></span></div>
                                        <input type="text" class="form-control" name="broker_contact" id="broker_contact">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="ai-field-wrap">
                                        <div class="ai-field-label">INSURER CLAIM EMAIL <span class="ai-conf-badge ai-conf-none" id="conf_insurer_claim_email"></span></div>
                                        <input type="email" class="form-control" name="insurer_claim_email" id="insurer_claim_email">
                                    </div>
                                </div>
                            </div>

                            {{-- POLICY DATES --}}
                            <div class="ai-section-title">Policy Dates</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_start_date">
                                        <div class="ai-field-label">START DATE <span class="ai-conf-badge ai-conf-none" id="conf_start_date"></span></div>
                                        <input type="date" class="form-control" name="start_date" id="start_date">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_end_date">
                                        <div class="ai-field-label">END DATE <span class="ai-conf-badge ai-conf-none" id="conf_end_date"></span></div>
                                        <input type="date" class="form-control" name="end_date" id="end_date">
                                    </div>
                                </div>
                            </div>

                            {{-- VEHICLE DETAILS --}}
                            <div id="vehicle_section" style="display:none">
                                <div class="ai-section-title">Vehicle Details</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_vehicle_reg_no">
                                            <div class="ai-field-label">REGISTRATION NUMBER <span class="text-danger">*</span> <span class="ai-conf-badge ai-conf-none" id="conf_vehicle_reg_no"></span></div>
                                            <input type="text" class="form-control" name="vehicle_reg_no" id="vehicle_reg_no">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_chassis_no">
                                            <div class="ai-field-label">CHASSIS NUMBER <span class="text-danger">*</span> <span class="ai-conf-badge ai-conf-none" id="conf_chassis_no"></span></div>
                                            <input type="text" class="form-control" name="chassis_no" id="chassis_no">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_engine_no">
                                            <div class="ai-field-label">ENGINE NUMBER <span class="ai-conf-badge ai-conf-none" id="conf_engine_no"></span></div>
                                            <input type="text" class="form-control" name="engine_no" id="engine_no">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_vehicle_make">
                                            <div class="ai-field-label">MAKE <span class="ai-conf-badge ai-conf-none" id="conf_vehicle_make"></span></div>
                                            <input type="text" class="form-control" name="vehicle_make" id="vehicle_make">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_vehicle_model">
                                            <div class="ai-field-label">MODEL <span class="ai-conf-badge ai-conf-none" id="conf_vehicle_model"></span></div>
                                            <input type="text" class="form-control" name="vehicle_model" id="vehicle_model">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_variant">
                                            <div class="ai-field-label">VARIANT <span class="ai-conf-badge ai-conf-none" id="conf_variant"></span></div>
                                            <input type="text" class="form-control" name="variant" id="variant">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_year_of_manufacture">
                                            <div class="ai-field-label">YEAR OF MANUFACTURE <span class="ai-conf-badge ai-conf-none" id="conf_year_of_manufacture"></span></div>
                                            <input type="number" class="form-control" name="year_of_manufacture" id="year_of_manufacture">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_fuel_type">
                                            <div class="ai-field-label">FUEL TYPE <span class="ai-conf-badge ai-conf-none" id="conf_fuel_type"></span></div>
                                            <select class="form-control" name="fuel_type" id="fuel_type">
                                                <option value="">-- Select --</option>
                                                <option>Petrol</option><option>Diesel</option>
                                                <option>Electric</option><option>CNG</option>
                                                <option>Hybrid</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_vehicle_class">
                                            <div class="ai-field-label">VEHICLE CLASS <span class="ai-conf-badge ai-conf-none" id="conf_vehicle_class"></span></div>
                                            <input type="text" class="form-control" name="vehicle_class" id="vehicle_class" placeholder="Enter vehicle class">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_seating_capacity">
                                            <div class="ai-field-label">SEATING CAPACITY <span class="ai-conf-badge ai-conf-none" id="conf_seating_capacity"></span></div>
                                            <input type="number" class="form-control" name="seating_capacity" id="seating_capacity">
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_vehicle_color">
                                            <div class="ai-field-label">COLOR <span class="ai-conf-badge ai-conf-none" id="conf_vehicle_color"></span></div>
                                            <input type="text" class="form-control" name="vehicle_color" id="vehicle_color" placeholder="Enter color">
                                        </div>
                                    </div>
                                </div>

                                <div class="ai-section-title mt-4">Coverage Details</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_vehicle_idv">
                                            <div class="ai-field-label">IDV (INSURED DECLARED VALUE ₹) <span class="ai-conf-badge ai-conf-none" id="conf_vehicle_idv"></span></div>
                                            <input type="number" step="0.01" class="form-control" name="vehicle_idv" id="vehicle_idv">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_ncb">
                                            <div class="ai-field-label">NCB % <span class="ai-conf-badge ai-conf-none" id="conf_ncb"></span></div>
                                            <input type="text" class="form-control" name="ncb" id="ncb">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_coverage_type">
                                            <div class="ai-field-label">COVERAGE TYPE <span class="ai-conf-badge ai-conf-none" id="conf_coverage_type"></span></div>
                                            <select class="form-control" name="coverage_type" id="coverage_type">
                                                <option value="">-- Select --</option>
                                                <option value="Comprehensive">Comprehensive</option>
                                                <option value="Third Party">Third Party</option>
                                                <option value="Own Damage">Own Damage</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_tp_end_date">
                                            <div class="ai-field-label">TP END DATE <span class="ai-conf-badge ai-conf-none" id="conf_tp_end_date"></span></div>
                                            <input type="date" class="form-control" name="tp_end_date" id="tp_end_date">
                                        </div>
                                    </div>
                                </div>

                                <div class="ai-section-title mt-4">Hypothecation (If Financed)</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_financier_name">
                                            <div class="ai-field-label">FINANCIER NAME <span class="ai-conf-badge ai-conf-none" id="conf_financier_name"></span></div>
                                            <input type="text" class="form-control" name="financier_name" id="financier_name" placeholder="NA">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_financier_branch">
                                            <div class="ai-field-label">BRANCH <span class="ai-conf-badge ai-conf-none" id="conf_financier_branch"></span></div>
                                            <input type="text" class="form-control" name="financier_branch" id="financier_branch" placeholder="Enter branch">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_agreement_number">
                                            <div class="ai-field-label">AGREEMENT NUMBER <span class="ai-conf-badge ai-conf-none" id="conf_agreement_number"></span></div>
                                            <input type="text" class="form-control" name="agreement_number" id="agreement_number" placeholder="Enter agreement number">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- HEALTH / POLICY DETAILS --}}
                            <div id="health_section" style="display:none">
                                <div class="ai-section-title">Health / Policy Details</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_sum_assured">
                                            <div class="ai-field-label">SUM ASSURED <span class="ai-conf-badge ai-conf-none" id="conf_sum_assured"></span></div>
                                            <input type="number" step="0.01" class="form-control" name="sum_assured" id="sum_assured">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_waiting_period">
                                            <div class="ai-field-label">WAITING PERIOD <span class="ai-conf-badge ai-conf-none" id="conf_waiting_period"></span></div>
                                            <input type="text" class="form-control" name="waiting_period" id="waiting_period">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_room_rent_limit">
                                            <div class="ai-field-label">ROOM RENT LIMIT <span class="ai-conf-badge ai-conf-none" id="conf_room_rent_limit"></span></div>
                                            <input type="text" class="form-control" name="room_rent_limit" id="room_rent_limit">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_deductible">
                                            <div class="ai-field-label">DEDUCTIBLE / CO-PAYMENT <span class="ai-conf-badge ai-conf-none" id="conf_deductible"></span></div>
                                            <input type="text" class="form-control" name="deductible" id="deductible">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="ai-field-wrap" id="wrap_maturity_date">
                                            <div class="ai-field-label">MATURITY DATE <span class="ai-conf-badge ai-conf-none" id="conf_maturity_date"></span></div>
                                            <input type="date" class="form-control" name="maturity_date" id="maturity_date">
                                        </div>
                                    </div>
                                </div>

                                <div class="ai-section-title mt-4 d-flex justify-content-between align-items-center">
                                    <span>Covered Members</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addCoveredMember()">
                                        <i class="fas fa-plus"></i> Add Member
                                    </button>
                                </div>
                                <div id="covered_members_container" class="mb-3">
                                    <!-- Dynamic members added here -->
                                </div>
                                <input type="hidden" name="covered_members" id="covered_members_input">
                            </div>

                            {{-- PREMIUM BREAKDOWN --}}
                            <div class="ai-section-title">Premium Breakdown</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_premium_own_damage">
                                        <div class="ai-field-label">TOTAL OWN DAMAGE (A) <span class="ai-conf-badge ai-conf-none" id="conf_premium_own_damage"></span> <span id="note_premium_own_damage" style="font-size: 11px; float: right; font-weight: normal;"></span></div>
                                        <input type="number" step="0.01" class="form-control" name="premium_own_damage" id="premium_own_damage">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_premium_liability">
                                        <div class="ai-field-label">TOTAL LIABILITY (B) <span class="ai-conf-badge ai-conf-none" id="conf_premium_liability"></span> <span id="note_premium_liability" style="font-size: 11px; float: right; font-weight: normal;"></span></div>
                                        <input type="number" step="0.01" class="form-control" name="premium_liability" id="premium_liability">
                                        <a href="#" class="small text-muted text-decoration-none mt-1 d-block" onclick="addCoveredAddon({cover_name: 'PA (Personal Accident) cover'}); return false;">+ Add PA (Personal Accident) cover</a>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_addons_premium">
                                        <div class="ai-field-label">ADD-ONS (WITHIN OD) <span class="ai-conf-badge ai-conf-none" id="conf_addons_premium"></span> <span id="note_addons_premium" style="font-size: 11px; float: right; font-weight: normal;"></span></div>
                                        <input type="number" step="0.01" class="form-control" name="addons_premium" id="addons_premium">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_net_premium">
                                        <div class="ai-field-label">NET PREMIUM (A + B) <span class="ai-conf-badge ai-conf-none" id="conf_net_premium"></span> <span id="note_net_premium" style="font-size: 11px; float: right; font-weight: normal;"></span></div>
                                        <input type="number" step="0.01" class="form-control" name="net_premium" id="net_premium">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_tax_amount">
                                        <div class="ai-field-label">GST / TAX <span class="text-muted">(Optional)</span> <span class="ai-conf-badge ai-conf-none" id="conf_tax_amount"></span> <span id="note_tax_amount" style="font-size: 11px; float: right; font-weight: normal;"></span></div>
                                        <input type="number" step="0.01" min="0" class="form-control" name="tax_amount" id="tax_amount">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_premium">
                                        <div class="ai-field-label">TOTAL (INCL. GST) <span class="text-danger">*</span> <span class="ai-conf-badge ai-conf-none" id="conf_premium"></span> <span id="note_premium" style="font-size: 11px; float: right; font-weight: normal;"></span></div>
                                        <input type="number" step="0.01" class="form-control" name="premium" id="premium" required>
                                    </div>
                                    <input type="hidden" name="amount" id="amount"> <!-- Kept for backward compatibility if needed by js -->
                                </div>
                            </div>

                            {{-- ADD-ON COVERS --}}
                            <div id="addon_covers_section" style="display:none">
                                <div class="ai-section-title mt-4 d-flex justify-content-between align-items-center">
                                    <span>Add-on Covers</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addCoveredAddon()">
                                        <i class="fas fa-plus"></i> Add
                                    </button>
                                </div>
                                <div id="addon_covers_container" class="mb-3">
                                    <!-- Dynamic addons added here -->
                                </div>
                                <input type="hidden" name="addon_covers" id="addon_covers_input">
                            </div>

                            {{-- PAYMENT & OTHERS --}}
                            <div class="ai-section-title mt-4">Payment &amp; Others</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="ai-field-wrap" id="wrap_payment_mode">
                                        <div class="ai-field-label">PAYMENT TYPE <span class="ai-conf-badge ai-conf-none" id="conf_payment_mode"></span></div>
                                        <select class="form-control select2-ai" name="payment_mode" id="payment_mode">
                                            <option value="">-- Select --</option>
                                            <option>Cash</option><option>Online</option>
                                            <option>Bank</option><option>Card</option><option>Cheque</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="ai-field-wrap" id="wrap_payment_date">
                                        <div class="ai-field-label">PAYMENT DATE <span class="ai-conf-badge ai-conf-none" id="conf_payment_date"></span></div>
                                        <input type="date" class="form-control" name="payment_date" id="payment_date">
                                    </div>
                                </div>
                            </div>

                            {{-- NOMINEE --}}
                            <div class="ai-section-title">Nominee</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_nominee_name">
                                        <div class="ai-field-label">NOMINEE NAME <span class="ai-conf-badge ai-conf-none" id="conf_nominee_name"></span></div>
                                        <input type="text" class="form-control" name="nominee_name" id="nominee_name">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="ai-field-wrap" id="wrap_nominee_contact">
                                        <div class="ai-field-label">NOMINEE CONTACT <span class="ai-conf-badge ai-conf-none" id="conf_nominee_contact"></span></div>
                                        <input type="text" class="form-control" name="nominee_contact" id="nominee_contact">
                                    </div>
                                </div>
                            </div>

                            <div class="ai-section-title">Remarks</div>
                            <div class="ai-field-wrap">
                                <textarea class="form-control" name="remarks" id="remarks" rows="2" placeholder="Additional notes or AI remarks…"></textarea>
                            </div>

                        </div>{{-- /form-body --}}
                    </form>
                </div>{{-- /form-panel --}}

                {{-- ── RIGHT: PDF VIEWER ───────────────────────────────────── --}}
                <div class="ai-pdf-panel">
                    <div class="ai-pdf-header">
                        <div class="ai-pdf-header-title"><i class="fas fa-file-pdf" style="color:#dc2626"></i> Original Policy Document</div>
                        <div class="ai-pdf-controls">
                            <span class="ai-pdf-page" id="pdf_page_info">Page 1 / 1</span>
                            <a id="btn_pdf_download" href="#" target="_blank" class="ai-pdf-btn" title="Open in new tab"><i class="fas fa-external-link-alt"></i></a>
                        </div>
                    </div>
                    <iframe id="pdf_preview_frame" class="ai-pdf-frame" title="Policy Document"></iframe>
                </div>{{-- /pdf-panel --}}

            </div>{{-- /review-wrap --}}

            {{-- ── BOTTOM BAR ──────────────────────────────────────────────── --}}
            <div class="ai-bottom-bar" id="bottom_bar">
                <button type="button" class="btn-ai-outline btn" onclick="resetToUpload()">
                    <i class="fas fa-redo me-1"></i> Start Over
                </button>
                <div class="ai-save-info">
                    <i class="fas fa-file-alt me-1"></i> <span id="save_count_label">1 file will be saved</span>
                </div>
                <button type="button" class="btn-ai-save btn" id="btn_save_policy">
                    <i class="fas fa-database me-2"></i> Save to Database
                </button>
            </div>

        </div>{{-- /view_review --}}

        {{-- ═══ STATE 4: SUCCESS ═══════════════════════════════════════════════ --}}
        <div id="view_success" style="display:none">
            <div class="ai-success-wrap">
                <div class="ai-success-icon"><i class="fas fa-check"></i></div>
                <h4 class="fw-bold mb-2" style="color:var(--ai-navy)">Policy successfully saved!</h4>
                <p class="ai-page-sub mb-3">The policy data has been added to your insurance CRM database.</p>
                <div class="bg-white border rounded p-3 text-start mb-4" style="max-width:320px;margin:0 auto">
                    <div class="small text-muted">Policy Number</div>
                    <div class="fw-bold" id="success_policy_number">—</div>
                    <div class="small text-muted mt-2">Insurance Company</div>
                    <div class="fw-bold" id="success_company">—</div>
                    <div class="small text-muted mt-2">Policy Type</div>
                    <div class="fw-bold" id="success_type">—</div>
                </div>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a id="btn_view_policy" href="#" class="btn-ai-primary btn">
                        <i class="fas fa-eye me-2"></i> View Policy
                    </a>
                    <button type="button" class="btn-ai-outline btn" onclick="resetToUpload()">
                        <i class="fas fa-upload me-2"></i> Upload Another
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ STATE 5: ERROR ═════════════════════════════════════════════════ --}}
        <div id="view_error" style="display:none">
            <div class="ai-error-wrap">
                <div class="ai-error-icon"><i class="fas fa-exclamation-triangle fa-2x" style="color:var(--ai-red)"></i></div>
                <h5 class="fw-bold mb-2" style="color:var(--ai-navy)" id="error_title">Extraction Failed</h5>
                <p class="ai-page-sub mb-4" id="error_message">An error occurred while analyzing the document.</p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <button type="button" class="btn-ai-primary btn" onclick="resetToUpload()">
                        <i class="fas fa-redo me-2"></i> Try Again
                    </button>
                    <a href="{{ route('policies.legacy.create') }}" class="btn-ai-outline btn">
                        <i class="fas fa-pencil-alt me-2"></i> Add Manually
                    </a>
                </div>
            </div>
        </div>

    </div>{{-- /ai-content --}}
</div>{{-- /ai-page-wrap --}}

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SAVE CONFIRMATION MODAL --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="confirmSaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" style="color:var(--ai-navy)">Save extracted policy data?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-3">
                <p class="text-muted mb-0">Fablead AI extracted data will be added to your insurance database. Please verify all fields before saving.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn-ai-outline btn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn-ai-save btn" id="btn_confirm_save">
                    <i class="fas fa-database me-2"></i> Save Policy
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Source Popover template --}}
<div id="source_popover_template" style="display:none">
    <div class="ai-source-popover">
        <div style="font-weight:700;margin-bottom:4px">AI Source</div>
        <div style="color:#94a3b8;font-size:.7rem;margin-bottom:4px">Extracted text:</div>
        <div id="popover_source_text" style="font-style:italic;margin-bottom:8px"></div>
        <div style="color:#94a3b8;font-size:.7rem">Confidence: <span id="popover_conf"></span>%</div>
    </div>
</div>

{{-- ===================== ADD MASTER MODALS ===================== --}}

{{-- Modal: Add Family Group --}}
<div class="modal fade" id="modalFamilyGroup" tabindex="-1" aria-labelledby="modalFamilyGroupLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalFamilyGroupLabel">Add Family Group</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formFamilyGroup" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Family Group Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fg_group_name" name="group_name" placeholder="Enter group name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Policy Holder <span class="text-danger">*</span></label>
                            <select class="form-control" id="fg_holder_id" name="policy_holder_id" required>
                                <option value="">Select Policy Holder</option>
                                @if(isset($customers) && count($customers) > 0)
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                    <h6 class="fw-bold mt-2">Members</h6>
                    <div id="fg_members_container">
                        <div class="fg-member-row d-flex gap-2 mb-2 align-items-center">
                            <input type="text" class="form-control" name="member_name[]" placeholder="Member Name">
                            <input type="text" class="form-control" name="member_relation[]" placeholder="Relation">
                            <input type="text" class="form-control" name="member_contact[]" placeholder="Contact">
                            <input type="date" class="form-control" name="member_dob[]" placeholder="DOB" max="{{ \Carbon\Carbon::yesterday()->format('Y-m-d') }}">
                            <input type="date" class="form-control" name="member_anniversary[]" placeholder="Anniversary">
                            <button type="button" class="btn btn-link text-danger p-0 fg-remove-member"><i class="fas fa-trash text-danger"></i></button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mt-1" id="fg_add_member">Add More Members</button>
                    <div class="mt-3">
                        <label class="form-label fw-bold">Remarks</label>
                        <textarea class="form-control" name="remarks" id="fg_remarks" rows="2" placeholder="Any remarks"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formFamilyGroup', '#modalFamilyGroup', 'family-groups/store')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add Policy Holder (Customer) --}}
<div class="modal fade" id="modalPolicyHolder" tabindex="-1" aria-labelledby="modalPolicyHolderLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalPolicyHolderLabel">Add Policy Holder</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPolicyHolder" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" placeholder="Full name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" class="form-control" name="email" placeholder="Email address">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Phone</label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="country_code" value="+91" style="width:85px" readonly>
                                <input type="tel" class="form-control" name="phone" placeholder="Phone number" maxlength="15">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">WhatsApp</label>
                            <div class="input-group">
                                <input type="text" class="form-control flex-grow-0" name="whatsapp_country_code" value="+91" style="width:85px" readonly>
                                <input type="tel" class="form-control" name="whatsapp" placeholder="WhatsApp number" maxlength="15">
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Company Name</label>
                            <input type="text" class="form-control" name="company_name" placeholder="Company name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Address</label>
                            <input type="text" class="form-control" name="address" placeholder="Address">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formPolicyHolder', '#modalPolicyHolder', 'save-customer')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add Policy Type --}}
<div class="modal fade" id="modalPolicyType" tabindex="-1" aria-labelledby="modalPolicyTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalPolicyTypeLabel">Add Policy Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPolicyType" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Policy Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="policy_type_name" placeholder="Enter policy type name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="policy_description" rows="2" placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formPolicyType', '#modalPolicyType', 'policy-type/store')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add Policy Sub Type --}}
<div class="modal fade" id="modalPolicySubType" tabindex="-1" aria-labelledby="modalPolicySubTypeLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalPolicySubTypeLabel">Add Policy Sub Type</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPolicySubType" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Policy Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="type_id" id="pst_type_id" required>
                            <option value="">-- Select Policy Type --</option>
                            @if(isset($policyTypes) && count($policyTypes) > 0)
                                @foreach($policyTypes as $pt)
                                    <option value="{{ $pt->id }}">{{ $pt->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Sub Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Enter sub type name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formPolicySubType', '#modalPolicySubType', 'policy-sub-type/store')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add Segment --}}
<div class="modal fade" id="modalSegment" tabindex="-1" aria-labelledby="modalSegmentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalSegmentLabel">Add Segment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSegment" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Policy Type <span class="text-danger">*</span></label>
                        <select class="form-control" name="insurance_type_id" id="seg_type_id" required>
                            <option value="">-- Select Policy Type --</option>
                            @if(isset($policyTypes) && count($policyTypes) > 0)
                                @foreach($policyTypes as $pt)
                                    <option value="{{ $pt->id }}">{{ $pt->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Segment Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Enter segment name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formSegment', '#modalSegment', 'store-segment')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add Insurance Company --}}
<div class="modal fade" id="modalInsuranceCompany" tabindex="-1" aria-labelledby="modalInsuranceCompanyLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalInsuranceCompanyLabel">Add Insurance Company</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formInsuranceCompany" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Company Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Enter company name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Description (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formInsuranceCompany', '#modalInsuranceCompany', 'store-company')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add Branch --}}
<div class="modal fade" id="modalBranch" tabindex="-1" aria-labelledby="modalBranchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalBranchLabel">Add Branch</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formBranch" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Insurance Company <span class="text-danger">*</span></label>
                        <select class="form-control" name="company_id" id="branch_company_id" required>
                            <option value="">-- Select Company --</option>
                            @if(isset($companies) && count($companies) > 0)
                                @foreach($companies as $cmp)
                                    <option value="{{ $cmp->id }}">{{ $cmp->name }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="branch_name" placeholder="Enter branch name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formBranch', '#modalBranch', 'branch/store')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add Broker --}}
<div class="modal fade" id="modalBroker" tabindex="-1" aria-labelledby="modalBrokerLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalBrokerLabel">Add Broker</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formBroker" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Broker Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="broker_name" placeholder="Enter broker name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formBroker', '#modalBroker', 'api/masters/brokers')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal: Add Staff / Sub Agent --}}
<div class="modal fade" id="modalStaff" tabindex="-1" aria-labelledby="modalStaffLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0a2540;">
                <h5 class="modal-title text-white fw-bold" id="modalStaffLabel">Add Sub Agent / Staff</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formStaff" action="javascript:void(0);" method="POST" onsubmit="return false;">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" placeholder="Enter full name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control flex-grow-0" name="country_code" value="+91" style="width:85px" readonly>
                            <input type="tel" class="form-control" name="phone" placeholder="10-digit phone" maxlength="15" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" value="Staff@123" placeholder="Enter password" required>
                        <div class="form-text text-muted">Default: Staff@123</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-dark-blue btn-submit-modal" onclick="submitModalAjax('#formStaff', '#modalStaff', 'api/users')">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════ --}}
{{-- SCRIPTS --}}
{{-- ══════════════════════════════════════════════════════════════════════════ --}}
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script>
/* ═════════════════════════════════════════════════════════════════
   Fablead AI Frontend Engine
   ═════════════════════════════════════════════════════════════════ */

var AI_UPLOAD_URL   = "{{ route('fablead.ai.upload') }}";
var AI_EXTRACT_URL  = "{{ route('fablead.ai.extract') }}";
var AI_SAVE_URL     = "{{ route('fablead.ai.save') }}";
var AI_PDF_URL      = "{{ route('fablead.ai.pdf') }}";
var CSRF_TOKEN      = "{{ csrf_token() }}";
var BASE_URL        = "{{ url('/') }}/";

var selectedFiles   = [];
var currentUploadId = null;
var extractedData   = null;
var currentFileIndex= 0;

// ── Toastr config ──────────────────────────────────────────────────────────
if (typeof toastr !== 'undefined') {
    toastr.options = { positionClass: 'toast-top-right', timeOut: 4000, progressBar: true };
}

// ════════════════════════════════════════════════════════════════════════
// GLOBAL MODAL HELPERS (AVAILABLE IMMEDIATELY BEFORE ANY CLICKS)
// ════════════════════════════════════════════════════════════════════════

window.appendAndSelect = function(selectId, id, label, extraAttrs) {
    var $sel = $(selectId);
    $sel.find('option[value="' + id + '"]').remove();
    var $opt = $('<option>', { value: id, text: label });
    if (extraAttrs) {
        $.each(extraAttrs, function (attr, val) {
            $opt.attr('data-' + attr, val);
        });
    }
    $sel.append($opt);
    $sel.val(id);
    $sel.trigger('change');
    if ($sel.data('select2')) {
        $sel.trigger('change.select2');
    }
};

window.populateSelect = function(selectId, url, valueFn, labelFn, placeholder, selectedVal) {
    var $sel = $(selectId);
    $.getJSON(url, function (data) {
        if (!data || !data.length) return;
        var existingVal = selectedVal || $sel.val();
        $sel.html('<option value="">' + (placeholder || '-- Select --') + '</option>');
        $.each(data, function (i, row) {
            var val = valueFn(row);
            var isSel = (existingVal && existingVal == val) ? ' selected' : '';
            $sel.append('<option value="' + val + '"' + isSel + '>' + labelFn(row) + '</option>');
        });
        if (existingVal) { $sel.val(existingVal); }
        if ($sel.data('select2')) { $sel.trigger('change.select2'); }
    });
};

window.submitModalAjax = function (formId, modalId, url) {
    var $form = $(formId);
    var formEl = document.querySelector(formId);
    if (!formEl) return false;

    if (formEl.checkValidity && !formEl.checkValidity()) {
        formEl.reportValidity();
        return false;
    }

    var $modal = $(modalId);
    var $btn = $modal.find('.btn-submit-modal');
    var origHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Saving...');

    var endpoint = (typeof BASE_URL !== 'undefined' ? BASE_URL : '{{ url("/") }}/').replace(/\/+$/, '') + '/' + url.replace(/^\/+/, '');
    var token = (typeof CSRF_TOKEN !== 'undefined' && CSRF_TOKEN) ? CSRF_TOKEN : '{{ csrf_token() }}';

    $.ajax({
        url: endpoint,
        type: 'POST',
        data: $form.serialize(),
        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
        dataType: 'json',
        success: function (res) {
            $btn.prop('disabled', false).html(origHtml);
            var ok = res.status === 'success' || res.status === true || res.success === true || (res.id && !res.error);
            if (ok) {
                if (typeof toastr !== 'undefined') {
                    toastr.success(res.message || 'Saved successfully.');
                }

                if (modalId === '#modalFamilyGroup') {
                    appendAndSelect('#family_group_id', res.id, res.group_name || $form.find('[name="group_name"]').val(), { 'holder-id': res.holder_id || '' });
                } else if (modalId === '#modalPolicyHolder') {
                    var label = res.name || (res.data ? res.data.name : '') || $form.find('[name="name"]').val();
                    appendAndSelect('#user_id', res.id, label);
                    $('#fg_holder_id').append('<option value="' + res.id + '">' + label + '</option>');
                    if (res.email && !$('#customer_email').val()) { $('#customer_email').val(res.email); }
                    if (res.phone && !$('#customer_phone').val()) { $('#customer_phone').val(res.phone); }
                    if (res.address && !$('#customer_address').val()) { $('#customer_address').val(res.address); }
                } else if (modalId === '#modalPolicyType') {
                    var label = res.name || $form.find('[name="policy_type_name"]').val();
                    appendAndSelect('#policy_type_id', res.id, label);
                    $('#pst_type_id').append('<option value="' + res.id + '">' + label + '</option>');
                    $('#seg_type_id').append('<option value="' + res.id + '">' + label + '</option>');
                } else if (modalId === '#modalPolicySubType') {
                    var label = res.name || $form.find('[name="name"]').val();
                    var selectedType = $('#policy_type_id').val();
                    var typeId = res.type_id || res.insurance_policy_types_id || $form.find('[name="type_id"]').val();
                    if (!selectedType || selectedType == typeId) {
                        appendAndSelect('#sub_type_id', res.id, label);
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.info('Sub type saved. Select the matching policy type to see it in the list.');
                        }
                    }
                } else if (modalId === '#modalSegment') {
                    var label = res.name || $form.find('[name="name"]').val();
                    var selectedType = $('#policy_type_id').val();
                    var typeId = res.insurance_type_id || $form.find('[name="insurance_type_id"]').val();
                    if (!selectedType || selectedType == typeId) {
                        appendAndSelect('#segment_id', res.id, label);
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.info('Segment saved. Select the matching policy type to see it in the list.');
                        }
                    }
                } else if (modalId === '#modalInsuranceCompany') {
                    var label = res.name || $form.find('[name="name"]').val();
                    appendAndSelect('#company_id', res.id, label);
                    $('#branch_company_id').append('<option value="' + res.id + '">' + label + '</option>');
                } else if (modalId === '#modalBranch') {
                    var label = res.name || $form.find('[name="branch_name"]').val();
                    var selectedCompany = $('#company_id').val();
                    var compId = res.company_id || $form.find('[name="company_id"]').val();
                    if (!selectedCompany || selectedCompany == compId) {
                        appendAndSelect('#branch_id', res.id, label);
                    }
                } else if (modalId === '#modalBroker') {
                    var broker = res.data || res;
                    var label = broker.broker_name || broker.name || $form.find('[name="broker_name"]').val();
                    appendAndSelect('#broker_id', broker.id, label);
                } else if (modalId === '#modalStaff') {
                    var user = res.data || res.user || res;
                    var label = user.name || $form.find('[name="name"]').val();
                    appendAndSelect('#staff_id', user.id, label);
                }

                var modalEl = document.querySelector(modalId);
                if (modalEl) {
                    var bsModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    bsModal.hide();
                }
                formEl.reset();
            } else {
                if (typeof toastr !== 'undefined') {
                    toastr.error(res.message || 'Failed. Please try again.');
                }
            }
        },
        error: function (xhr) {
            $btn.prop('disabled', false).html(origHtml);
            var msg = 'Failed to save.';
            try {
                var parsed = JSON.parse(xhr.responseText);
                msg = parsed.message || (parsed.errors ? Object.values(parsed.errors).flat().join('<br>') : msg);
            } catch (e) {}
            if (typeof toastr !== 'undefined') {
                toastr.error(msg);
            } else {
                alert(msg);
            }
        }
    });

    return false;
};

// ── Init Select2 & Event Handlers ─────────────────────────────────────────
$(document).ready(function() {
    $('.select2-ai').select2({ width: '100%' });

    // Family Group is optional and does not control the policy holder.

    // Policy type → Sub types + Segments
    $('#policy_type_id').on('change', function() {
        var ptId = $(this).val();
        var $sub  = $('#sub_type_id');
        var $seg  = $('#segment_id');
        if (!ptId) {
            $sub.html('<option value="">-- Select Sub Type --</option>');
            $seg.html('<option value="">-- Select Segment --</option>');
            return;
        }
        $sub.html('<option value="">Loading…</option>');
        $seg.html('<option value="">Loading…</option>');

        var typeName = $('#policy_type_id option:selected').text().toLowerCase();
        if (typeName.includes('motor') || typeName.includes('vehicle')) {
            $('#vehicle_section').show();
            $('#health_section').hide();
        } else if (typeName.includes('health') || typeName.includes('life') || typeName.includes('fire') || typeName.includes('marine')) {
            $('#vehicle_section').hide();
            $('#health_section').show();
        } else {
            $('#vehicle_section').hide();
            $('#health_section').hide();
        }

        $.get(BASE_URL + 'policy-sub-types/' + ptId, function(data) {
            $sub.html('<option value="">-- Select Sub Type --</option>');
            $.each(data, function(i, s) {
                $sub.append('<option value="' + s.id + '">' + s.name + '</option>');
            });
            $sub.trigger('change.select2');
        });
        $.get(BASE_URL + 'segments-by-type/' + ptId, function(data) {
            $seg.html('<option value="">-- Select Segment --</option>');
            $.each(data, function(i, s) {
                $seg.append('<option value="' + s.id + '">' + s.name + '</option>');
            });
            $seg.trigger('change.select2');
        });
    });

    // Company → Branches
    $('#company_id').on('change', function() {
        var cId = $(this).val();
        var $br = $('#branch_id');
        if (!cId) { $br.html('<option value="">-- Select Branch --</option>'); return; }
        $br.html('<option value="">Loading…</option>');
        $.get(BASE_URL.replace(/\/+$/, '') + '/get-branches/' + cId, function(data) {
            $br.html('<option value="">-- Select Branch --</option>');
            $.each(data, function(i, b) {
                $br.append('<option value="' + b.id + '">' + b.name + '</option>');
            });
            $br.trigger('change.select2');
        }).fail(function() {
            $br.html('<option value="">-- Select Branch --</option>');
        });
    });

    // Auto-calculate total premium if manually editing
    function recalcTotal() {
        var p = parseFloat($('#premium').val()) || 0;
        $('#amount').val(p.toFixed(2));
    }
    $('#premium').on('input', recalcTotal);

    // ════════════════════════════════════════════════════════════════════════
    // MODAL EVENT LISTENERS & AUTO-SELECTORS
    // ════════════════════════════════════════════════════════════════════════

    $('#modalFamilyGroup').on('show.bs.modal', function () {
        var currentUId = $('#user_id').val();
        if (currentUId) {
            $('#fg_holder_id').val(currentUId);
        }
    });

    $('#fg_add_member').on('click', function () {
        $('#fg_members_container').append(
            '<div class="fg-member-row d-flex gap-2 mb-2 align-items-center">' +
            '<input type="text" class="form-control" name="member_name[]" placeholder="Member Name">' +
            '<input type="text" class="form-control" name="member_relation[]" placeholder="Relation">' +
            '<input type="text" class="form-control" name="member_contact[]" placeholder="Contact">' +
            '<input type="date" class="form-control" name="member_dob[]">' +
            '<input type="date" class="form-control" name="member_anniversary[]">' +
            '<button type="button" class="btn btn-link text-danger p-0 fg-remove-member"><i class="fas fa-trash text-danger"></i></button>' +
            '</div>'
        );
    });
    $(document).on('click', '.fg-remove-member', function () {
        if ($('.fg-member-row').length > 1) $(this).closest('.fg-member-row').remove();
    });

    $('#modalPolicySubType').on('show.bs.modal', function () {
        var currentPtId = $('#policy_type_id').val();
        if (currentPtId) {
            $('#pst_type_id').val(currentPtId);
        }
    });

    $('#modalSegment').on('show.bs.modal', function () {
        var currentPtId = $('#policy_type_id').val();
        if (currentPtId) {
            $('#seg_type_id').val(currentPtId);
        }
    });

    $('#modalBranch').on('show.bs.modal', function () {
        var currentCId = $('#company_id').val();
        if (currentCId) {
            $('#branch_company_id').val(currentCId);
        }
    });

    // Universal form submit blocker (Prevents any form from causing page reloads)
    $(document).on('submit', 'form', function (e) {
        e.preventDefault();
        return false;
    });

    // Enter key handling: In modals, trigger the modal submit button; in main form, prevent reload
    $(document).on('keydown', '.modal form input', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            var $btn = $(this).closest('.modal').find('.btn-submit-modal');
            if ($btn.length) { $btn.click(); }
            return false;
        }
    });

    $(document).on('keydown', '#policy_ai_form input', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            return false;
        }
    });
});

// ── Drag & Drop ────────────────────────────────────────────────────────────
var dropZone = document.getElementById('drop_zone');
dropZone.addEventListener('click', function() { document.getElementById('pdf_file_input').click(); });
dropZone.addEventListener('dragover', function(e) { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', function() { dropZone.classList.remove('drag-over'); });
dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    handleFiles(Array.from(e.dataTransfer.files));
});
document.getElementById('pdf_file_input').addEventListener('change', function() {
    handleFiles(Array.from(this.files));
});

function handleFiles(files) {
    var valid = files.filter(f => f.type === 'application/pdf' && f.size <= 20971520);
    if (!valid.length) { toastr.error('Please upload valid PDF files (max 20MB each).'); return; }
    selectedFiles = valid;
    renderFileList();
}

function renderFileList() {
    var list = document.getElementById('file_list');
    list.innerHTML = '';
    selectedFiles.forEach(function(f, i) {
        list.innerHTML += '<div class="ai-file-item">' +
            '<div class="ai-file-icon"><i class="fas fa-file-pdf"></i></div>' +
            '<div class="ai-file-meta">' +
                '<div class="ai-file-name">' + f.name + '</div>' +
                '<div class="ai-file-size">' + (f.size/1024/1024).toFixed(2) + ' MB</div>' +
            '</div>' +
            '<div class="ai-file-status">Ready for AI extraction</div>' +
        '</div>';
    });
    list.style.display = 'block';
    document.getElementById('upload_actions').style.display = 'block';
}

// ── ANALYZE BUTTON ─────────────────────────────────────────────────────────
document.getElementById('btn_analyze').addEventListener('click', function() {
    if (!selectedFiles.length) { toastr.warning('Please select a PDF first.'); return; }
    currentFileIndex = 0;
    processNextFile();
});

function processNextFile() {
    if (currentFileIndex >= selectedFiles.length) return;
    var file = selectedFiles[currentFileIndex];
    document.getElementById('processing_filename').textContent = file.name;

    showState('processing');
    animateStages();

    // Step 1: Upload
    var fd = new FormData();
    fd.append('pdf', file);
    fd.append('_token', CSRF_TOKEN);

    fetch(AI_UPLOAD_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(function(res) {
            if (!res.status) throw new Error(res.message || 'Upload failed');
            currentUploadId = res.upload_id;
            document.getElementById('upload_id_input').value = currentUploadId;

            // Step 2: Extract
            return fetch(AI_EXTRACT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN },
                body: JSON.stringify({ upload_id: currentUploadId })
            });
        })
        .then(r => r.json())
        .then(function(res) {
            if (!res.status) {
                showError(res.message || 'AI extraction failed.', res.code);
                return;
            }
            extractedData = res.data;
            fillForm(extractedData);
            showPdf();
            showState('review');
            toastr.success('Data extracted successfully!', 'Fablead AI');
        })
        .catch(function(err) {
            showError(err.message || 'An unexpected error occurred.');
        });
}

// ── Fill form from AI response ─────────────────────────────────────────────
function fillForm(data) {
    var f = data.fields || {};
    var r = data.resolved || {};

    // Simple text fields
    var textMap = {
        'policy_number':     f.policy_number,
        'vehicle_reg_no':    f.vehicle_reg_no,
        'vehicle_make':      f.vehicle_make,
        'vehicle_model':     f.vehicle_model,
        'variant':           f.variant,
        'engine_no':         f.engine_no,
        'chassis_no':        f.chassis_no,
        'cc':                f.engine_cc,
        'year_of_manufacture': f.year_of_manufacture,
        'vehicle_class':     f.vehicle_class,
        'seating_capacity':  f.seating_capacity,
        'vehicle_color':     f.vehicle_color,
        'coverage_type':     f.coverage_type,
        'tp_end_date':       f.tp_end_date,
        'financier_name':    f.financier_name,
        'financier_branch':  f.financier_branch,
        'agreement_number':  f.agreement_number,
        'vehicle_idv':       f.vehicle_idv,
        'gvw':               f.gvw,
        'ncb':               f.ncb,
        'sum_assured':       f.sum_assured,
        'premium':           (f.total_premium && f.total_premium.value) ? f.total_premium : f.premium_amount,
        'tax_amount':        f.tax_amount,
        'amount':            (f.total_premium && f.total_premium.value) ? f.total_premium : f.premium_amount,
        'premium_own_damage': f.premium_own_damage,
        'premium_liability':  f.premium_liability,
        'addons_premium':     f.addons_premium,
        'net_premium':        f.net_premium,
        'start_date':        f.start_date,
        'end_date':          f.end_date,
        'payment_date':      f.payment_date,
        'nominee_name':      f.nominee_name,
        'nominee_contact':   f.nominee_contact,
        'customer_email':    f.customer_email,
        'customer_phone':    f.customer_phone,
        'customer_address':  f.customer_address,
        'agent_name':        f.broker_name,
        'agent_code':        f.agent_code,
        'agent_id':          f.agent_id,
        'broker_contact':    f.broker_contact,
        'insurer_claim_email': f.insurer_claim_email,
        'waiting_period':    f.waiting_period,
        'room_rent_limit':   f.room_rent_limit,
        'deductible':        f.deductible,
        'maturity_date':     f.maturity_date,
        'product_name':      f.product_name,
        'reference':         f.reference,
        'commission_percentage': f.commission_percentage,
    };

    Object.keys(textMap).forEach(function(id) {
        var field = textMap[id];
        if (field && field.value !== null && field.value !== '') {
            var el = document.getElementById(id);
            if (el) {
                el.value = field.value;
                el.classList.add('ai-filled');
                showConfBadge(id, field.confidence || 0, field.source_text || '');
            }
        }
    });

    // Covered Members
    $('#covered_members_container').empty();
    if (data.covered_members && data.covered_members.length > 0) {
        data.covered_members.forEach(function(m) {
            if (m.name) addCoveredMember(m);
        });
    }

    // Addon Covers
    $('#addon_covers_container').empty();
    if (data.addon_covers && data.addon_covers.length > 0) {
        data.addon_covers.forEach(function(a) {
            if (a.cover_name) addCoveredAddon(a);
        });
    }

    // Fuel type select
    if (f.fuel_type && f.fuel_type.value) {
        setSelectValue('fuel_type', f.fuel_type.value);
        showConfBadge('fuel_type', f.fuel_type.confidence, f.fuel_type.source_text);
    }
    
    // Coverage type select
    if (f.coverage_type && f.coverage_type.value) {
        setSelectValue('coverage_type', f.coverage_type.value);
        showConfBadge('coverage_type', f.coverage_type.confidence, f.coverage_type.source_text);
    }
    
    // Business type select
    if (f.business_type && f.business_type.value) {
        setSelectValue('business_type', f.business_type.value);
        showConfBadge('business_type', f.business_type.confidence, f.business_type.source_text);
    }

    // Payment mode select
    if (f.payment_mode && f.payment_mode.value) {
        setSelectValue('payment_mode', f.payment_mode.value);
        showConfBadge('payment_mode', f.payment_mode.confidence, f.payment_mode.source_text);
    }

    // Show the resolved or newly created customer immediately in the preview.
    if (r.user_id) {
        var holderName = r.customer_name || (f.holder_name ? f.holder_name.value : '') || 'Scanned Customer';
        if ($('#user_id option[value="' + r.user_id + '"]').length === 0) {
            $('#user_id').append(new Option(holderName, r.user_id, true, true));
        }
        $('#user_id').val(r.user_id).trigger('change.select2');
    }

    // A matched Family Group may be shown, but it is never required.
    if (r.family_group_id) {
        $('#family_group_id').val(r.family_group_id).trigger('change.select2');
    }

    // Resolved dropdown: company
    if (r.company_id) {
        $('#company_id').val(r.company_id).trigger('change');
        showConfBadge('company_id', (f.insurance_company ? f.insurance_company.confidence : 0), r.company_name || '');
        if (r.branch_id) {
            setTimeout(function() {
                $('#branch_id').val(r.branch_id).trigger('change.select2');
            }, 1200);
        }
    }

    // Resolved dropdown: policy type
    if (r.policy_type_id) {
        $('#policy_type_id').val(r.policy_type_id).trigger('change');
        showConfBadge('policy_type_id', (f.policy_type ? f.policy_type.confidence : 0), r.policy_type_name || '');
        // sub type resolved after cascade loads
        if (r.sub_type_id) {
            setTimeout(function() {
                $('#sub_type_id').val(r.sub_type_id).trigger('change.select2');
            }, 1200);
        }
    }

    // Show vehicle or health section based on detected type
    var type = (data.policy_type_detected || '').toLowerCase();
    if (type === 'motor') {
        document.getElementById('vehicle_section').style.display = '';
        document.getElementById('addon_covers_section').style.display = '';
        document.getElementById('health_section').style.display = 'none';
    } else if (['health','fire','marine','burglary','life','travel','personal accident'].includes(type)) {
        document.getElementById('vehicle_section').style.display = 'none';
        document.getElementById('addon_covers_section').style.display = 'none';
        document.getElementById('health_section').style.display = '';
    }

    // Update stats bar
    updateStats(data);

    // Run FableadAI Validations
    runValidations(data);
}

function showConfBadge(fieldId, confidence, sourceText) {
    var badgeEl = document.getElementById('conf_' + fieldId);
    if (!badgeEl) return;
    var cls, label;
    if (confidence >= 95) { cls = 'ai-conf-high'; label = '✓ ' + confidence + '%'; }
    else if (confidence >= 80) { cls = 'ai-conf-mid'; label = '~ ' + confidence + '%'; }
    else if (confidence > 0)  { cls = 'ai-conf-low'; label = '! ' + confidence + '%'; }
    else { cls = 'ai-conf-none'; label = 'Needs Review'; }
    badgeEl.className = 'ai-conf-badge ' + cls;
    badgeEl.textContent = label;
    if (sourceText) {
        badgeEl.title = 'Source: ' + sourceText;
    }
}

function updateStats(data) {
    var f = data.fields || {};
    var r = data.resolved || {};
    var extracted = 0, high = 0, mid = 0, missing = 0;
    Object.values(f).forEach(function(fld) {
        if (!fld || typeof fld !== 'object') return;
        if (fld.value !== null && fld.value !== '') {
            extracted++;
            if (fld.confidence >= 95) high++;
            else if (fld.confidence >= 80) mid++;
        } else {
            missing++;
        }
    });

    document.getElementById('stat_type').textContent = data.policy_type_detected || '—';
    document.getElementById('stat_company').textContent = r.company_name || (f.insurance_company ? f.insurance_company.value : null) || '—';
    document.getElementById('stat_extracted').textContent = extracted;
    document.getElementById('stat_high').textContent = high;
    document.getElementById('stat_mid').textContent = mid;
    document.getElementById('stat_missing').textContent = missing;

    var total = f.total_premium ? f.total_premium.value : null;
    document.getElementById('stat_premium').textContent = total ? '₹' + parseFloat(total).toLocaleString('en-IN') : '—';

    var overallConf = data.confidence_overall || 0;
    var badge = document.getElementById('overall_conf_badge');
    badge.textContent = overallConf + '% confidence';
    if (overallConf >= 95) badge.className = 'ai-conf-badge ai-conf-high';
    else if (overallConf >= 80) badge.className = 'ai-conf-badge ai-conf-mid';
    else badge.className = 'ai-conf-badge ai-conf-low';
}

function runValidations(data) {
    var errors = [];
    var isMotor = ((data.policy_type_detected || '').toLowerCase() === 'motor');
    
    // Clear inline notes first
    $('#note_premium_own_damage, #note_premium_liability, #note_addons_premium, #note_net_premium, #note_tax_amount, #note_premium').text('').removeClass('text-danger text-warning text-success text-secondary text-muted');

    if (isMotor) {
        var od = parseFloat($('#premium_own_damage').val()) || 0;
        var tp = parseFloat($('#premium_liability').val()) || 0;
        var addons = parseFloat($('#addons_premium').val()) || 0;
        var net = parseFloat($('#net_premium').val()) || 0;
        var gst = parseFloat($('#tax_amount').val()) || 0;
        var total = parseFloat($('#premium').val()) || 0;

        // Note: Od missing check
        if (!od) {
            $('#note_premium_own_damage').text('missing').addClass('text-warning');
        }

        // Net Premium Check
        var calculatedNet = od + tp + addons;
        if (Math.abs(calculatedNet - net) > 2 && net > 0) {
            // Check if maybe Addons are already inside OD
            if (Math.abs((od + tp) - net) <= 2) {
                $('#note_net_premium').text('OD + Liability = ₹' + net).addClass('text-muted');
                $('#note_addons_premium').text('already in OD — not added again').addClass('text-muted');
            } else {
                errors.push(`OD (₹${od}) + TP (₹${tp}) + Add-ons (₹${addons}) = ₹${calculatedNet}, but net premium extracted as ₹${net} — please verify the premium schedule.`);
                $('#net_premium').addClass('is-invalid border-danger');
            }
        } else if (net > 0) {
            $('#note_net_premium').text('✓ looks right').addClass('text-success');
        }

        // Total Premium Check
        var calculatedTotal = net + gst;
        if (Math.abs(calculatedTotal - total) > 2 && total > 0 && net > 0) {
            errors.push(`Net Premium (₹${net}) + GST (₹${gst}) = ₹${calculatedTotal}, but Total Premium extracted as ₹${total}.`);
            $('#premium').addClass('is-invalid border-danger');
        } else if (total > 0 && net > 0) {
            $('#note_premium').text('✓ looks right').addClass('text-success');
        }
    }

    // Masked Data Check
    var phone = $('#customer_phone').val() || '';
    if (phone.includes('*') || phone.toLowerCase().includes('x')) {
        errors.push(`The customer's mobile number is masked on this policy. It's optional — you can save now and add the full number later from the customer's profile.`);
        $('#customer_phone').addClass('is-invalid border-warning');
    }

    var email = $('#customer_email').val() || '';
    if (email.includes('*') || email.toLowerCase().includes('x')) {
        errors.push(`The customer's email is masked on this policy. It's optional — you can save now and add the full email later from the customer's profile.`);
        $('#customer_email').addClass('is-invalid border-warning');
    }

    // Missing Required Fields Check
    $('.fill-required-badge').remove();
    $('#vehicle_reg_no, #chassis_no').removeClass('is-invalid border-warning').css('background-color', '');
    
    var requiredMotorFields = ['vehicle_reg_no', 'chassis_no'];
    if (isMotor) {
        requiredMotorFields.forEach(function(fieldId) {
            var val = $('#' + fieldId).val();
            if (!val || val.trim() === '') {
                var $label = $('#wrap_' + fieldId + ' .ai-field-label');
                $label.addClass('d-flex justify-content-between align-items-center');
                $label.append('<span class="badge bg-warning text-dark fill-required-badge" style="font-size: 10px; border: 1px solid #d97706; color: #b45309 !important; background-color: #fef3c7 !important;">FILL REQUIRED</span>');
                $('#' + fieldId).addClass('is-invalid border-warning').css('background-color', '#fffbeb');
            }
        });
    }

    // Display Validation Alert Box
    var $alertBox = $('#ai_validation_alert');
    var $alertList = $('#ai_validation_list');
    $alertList.empty();

    if (errors.length > 0) {
        $('#ai_validation_title').text(`FableadAI flagged ${errors.length} fields for your review`);
        errors.forEach(function(err) {
            $alertList.append(`<li>${err}</li>`);
        });
        $alertBox.show();
    } else {
        $alertBox.hide();
    }
}

function setSelectValue(id, value) {
    var el = document.getElementById(id);
    if (!el || !value) return;
    var searchStr = String(value).trim().toLowerCase();
    for (var i = 0; i < el.options.length; i++) {
        var optVal = String(el.options[i].value || '').trim().toLowerCase();
        var optText = String(el.options[i].text || '').trim().toLowerCase();
        if (optVal === searchStr || optText === searchStr) {
            el.selectedIndex = i;
            // Also trigger change event for select2 if applicable
            $(el).trigger('change');
            break;
        }
    }
}

// ── PDF Viewer ─────────────────────────────────────────────────────────────
function showPdf() {
    var url = AI_PDF_URL + '?upload_id=' + currentUploadId;
    document.getElementById('pdf_preview_frame').src = url;
    document.getElementById('btn_pdf_download').href = url;
}

// ── Stage animation ────────────────────────────────────────────────────────
function animateStages() {
    var stages = ['stage_1','stage_2','stage_3','stage_4','stage_5','stage_6'];
    stages.forEach(function(s) {
        document.getElementById(s).className = 'ai-stage-item';
    });
    var idx = 0;
    function next() {
        if (idx > 0) document.getElementById(stages[idx-1]).className = 'ai-stage-item done';
        if (idx < stages.length) {
            document.getElementById(stages[idx]).className = 'ai-stage-item active';
            idx++;
            setTimeout(next, 1000);
        }
    }
    next();
}

// ── State management ───────────────────────────────────────────────────────
function showState(state) {
    ['upload','processing','review','success','error'].forEach(function(s) {
        var el = document.getElementById('view_' + s);
        if (el) el.style.display = s === state ? '' : 'none';
    });

    var stepMap = { upload: 0, processing: 1, review: 2, success: 3, error: 0 };
    var steps = ['step_upload','step_extract','step_review','step_save'];
    var lines = ['line_1','line_2','line_3'];
    var active = stepMap[state] || 0;

    steps.forEach(function(s, i) {
        var el = document.getElementById(s);
        el.className = 'ai-step';
        if (i < active) el.classList.add('done');
        else if (i === active) el.classList.add('active');
    });
    lines.forEach(function(l, i) {
        var el = document.getElementById(l);
        el.className = 'ai-step-line' + (i < active ? ' done' : '');
    });
}

function showError(msg, code) {
    document.getElementById('error_message').textContent = msg;
    var titleMap = {
        'UNREADABLE_PDF':  'Unable to Read PDF',
        'AI_ERROR':        'AI Service Unavailable',
    };
    document.getElementById('error_title').textContent = titleMap[code] || 'Extraction Failed';
    showState('error');
}

function resetToUpload() {
    selectedFiles = [];
    currentUploadId = null;
    extractedData = null;
    document.getElementById('file_list').innerHTML = '';
    document.getElementById('file_list').style.display = 'none';
    document.getElementById('upload_actions').style.display = 'none';
    document.getElementById('pdf_file_input').value = '';
    showState('upload');
}

// ── SAVE ──────────────────────────────────────────────────────────────────
document.getElementById('btn_save_policy').addEventListener('click', function() {
    var modal = new bootstrap.Modal(document.getElementById('confirmSaveModal'));
    modal.show();
});

document.getElementById('btn_confirm_save').addEventListener('click', function() {
    var modal = bootstrap.Modal.getInstance(document.getElementById('confirmSaveModal'));
    if (modal) modal.hide();

    var form = document.getElementById('policy_ai_form');
    var fd = new FormData(form);
    fd.set('upload_id', currentUploadId);

    document.getElementById('btn_confirm_save').disabled = true;
    document.getElementById('btn_confirm_save').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Saving…';

    fetch(AI_SAVE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF_TOKEN },
        body: fd,
    })
    .then(r => r.json())
    .then(function(res) {
        document.getElementById('btn_confirm_save').disabled = false;
        document.getElementById('btn_confirm_save').innerHTML = '<i class="fas fa-database me-2"></i> Save Policy';

        if (res.status) {
            document.getElementById('success_policy_number').textContent = res.policy_number || '—';
            document.getElementById('success_company').textContent = $('#company_id option:selected').text() || '—';
            document.getElementById('success_type').textContent = $('#policy_type_id option:selected').text() || '—';
            var viewUrl = BASE_URL.replace(/\/+$/, '') + '/view-policy/' + res.policy_id;
            document.getElementById('btn_view_policy').href = viewUrl;
            showState('success');
        } else {
            toastr.error(res.message || 'Failed to save policy.');
        }
    })
    .catch(function(err) {
        document.getElementById('btn_confirm_save').disabled = false;
        document.getElementById('btn_confirm_save').innerHTML = '<i class="fas fa-database me-2"></i> Save Policy';
        toastr.error('Network error while saving. Please try again.');
    });
});

// ── Covered Members Logic ──────────────────────────────────────────────────
var memberCount = 0;
function addCoveredMember(member = null) {
    memberCount++;
    var mName = member ? (member.name || '') : '';
    var mRel = member ? (member.relation || '') : '';
    var mDob = member ? (member.dob || '') : '';
    var mGen = member ? (member.gender || '') : '';
    
    var html = `
    <div class="card mb-2 bg-light border-0" id="member_row_${memberCount}">
        <div class="card-body p-2">
            <div class="row g-2">
                <div class="col-md-5">
                    <label class="small text-muted mb-1">Name</label>
                    <input type="text" class="form-control member-name" value="${mName}">
                </div>
                <div class="col-md-3">
                    <label class="small text-muted mb-1">Relation</label>
                    <input type="text" class="form-control member-rel" value="${mRel}">
                </div>
                <div class="col-md-3">
                    <label class="small text-muted mb-1">DOB</label>
                    <input type="date" class="form-control member-dob" value="${mDob}">
                </div>
                <div class="col-md-1 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="$('#member_row_${memberCount}').remove(); updateMembersInput();">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
                <div class="col-md-3 mt-1">
                    <label class="small text-muted mb-1">Gender</label>
                    <select class="form-control member-gen">
                        <option value="">--</option>
                        <option value="Male" ${mGen.toLowerCase()==='male'?'selected':''}>Male</option>
                        <option value="Female" ${mGen.toLowerCase()==='female'?'selected':''}>Female</option>
                        <option value="Other" ${mGen.toLowerCase()==='other'?'selected':''}>Other</option>
                    </select>
                </div>
            </div>
        </div>
    </div>`;
    $('#covered_members_container').append(html);
    updateMembersInput();
    
    // Bind inputs to update hidden input
    $(`#member_row_${memberCount} input, #member_row_${memberCount} select`).on('change input', updateMembersInput);
}

function updateMembersInput() {
    var members = [];
    $('#covered_members_container .card').each(function() {
        var $row = $(this);
        var name = $row.find('.member-name').val();
        if (name) {
            members.push({
                name: name,
                relation: $row.find('.member-rel').val(),
                dob: $row.find('.member-dob').val(),
                gender: $row.find('.member-gen').val()
            });
        }
    });
    $('#covered_members_input').val(JSON.stringify(members));
}

// ── Addon Covers Logic ─────────────────────────────────────────────────────
var addonCount = 0;
function addCoveredAddon(addon = null) {
    addonCount++;
    var aName = addon ? (addon.cover_name || '') : '';
    var aPremium = addon ? (addon.premium_amount || '') : '';
    
    var html = `
    <div class="card mb-2 bg-light border-0" id="addon_row_${addonCount}">
        <div class="card-body p-2">
            <div class="row g-2">
                <div class="col-md-7">
                    <label class="small text-muted mb-1">Add-on Cover Name</label>
                    <input type="text" class="form-control addon-name" value="${aName}">
                </div>
                <div class="col-md-4">
                    <label class="small text-muted mb-1">Premium Amount</label>
                    <input type="number" step="0.01" class="form-control addon-premium" value="${aPremium}">
                </div>
                <div class="col-md-1 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="$('#addon_row_${addonCount}').remove(); updateAddonsInput();">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>`;
    $('#addon_covers_container').append(html);
    updateAddonsInput();
    
    // Bind inputs to update hidden input
    $(`#addon_row_${addonCount} input`).on('change input', updateAddonsInput);
}

function updateAddonsInput() {
    var addons = [];
    $('#addon_covers_container .card').each(function() {
        var $row = $(this);
        var name = $row.find('.addon-name').val();
        if (name) {
            addons.push({
                cover_name: name,
                premium_amount: $row.find('.addon-premium').val()
            });
        }
    });
    $('#addon_covers_input').val(JSON.stringify(addons));
}
</script>

@endsection
{{-- ===== FABLEAD AI POLICY UPLOAD: END ===== --}}
