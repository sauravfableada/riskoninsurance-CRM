@extends('layouts.app')

@section('content')
<style>
  .endorsement-create-wrap {
    padding-top: 0.75rem;
    padding-bottom: 1.25rem;
  }

  .endorsement-create-card {
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
    background: rgba(1, 58, 131, 0.25);
  }

  .ee-tab.active {
    background: #003982;
    color: #fff;
    box-shadow: 0 8px 18px rgba(13, 110, 253, 0.2);
  }

  .endorsement-create-surface {
    background: #f4f8ff;
    border-radius: 16px;
    padding: 16px;
    min-height: 520px;
    display: grid;
    place-items: center;
    text-align: center;
  }

  .endorsement-create-surface img {
    max-width: 320px;
    width: 100%;
    height: auto;
  }

  .endorsement-header {
    background: #013A83;
    padding: 8px 24px;
    border-radius: 12px 12px 0 0;
    color: #fff;
    box-shadow: 0 4px 20px rgba(13, 110, 253, 0.2);
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .endorsement-header .left { display: flex; align-items: center; gap: 16px; }
  .endorsement-header .title { font-size: 1.1rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; margin: 0; }
  .endorsement-header .subtitle { font-size: 0.9rem; font-weight: 500; opacity: 0.9; }
</style>

<div class="container-fluid endorsement-create-wrap mt-4">
  <div class="endorsement-header">
    <div class="left">
      <h6 class="mb-0 text-white text-capitalize me-2">Endorsement Management</h6>
    </div>
    <div class="align-items-center d-flex gap-3">
      <a class="btn btn-light mb-0" href="{{ route('endorsement.index') }}">
       <i class="fa fa-angle-left"></i> Back
      </a>
      <div class="subtitle text-white fw-semibold fs-6">Make New Endorsement</div>
    </div>
  </div>

  <div class="endorsement-create-card">
    <div class="ee-tabs">
      <a class="ee-tab" href="{{ route('endorsement.create.general') }}">General Endorsement</a>
      <a class="ee-tab active" href="javascript:void(0)">Life Endorsement</a>
      <a class="ee-tab" href="{{ route('endorsement.create.client') }}">Client Endorsement</a>
    </div>

    <div class="endorsement-create-surface">
      <div>
        <img src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/img/illustrations/icon-documentation.svg' : '/assets/img/illustrations/icon-documentation.svg') }}" alt="Coming soon">
        <h5 class="mt-3 mb-0" style="font-weight:700; color:#3b4a5a;">Coming Soon...!</h5>
      </div>
    </div>
  </div>
</div>
@endsection
