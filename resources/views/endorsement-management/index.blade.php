@extends('layouts.app')

@section('content')
<style>
  .endorsement-page {
    padding-top: 0.75rem;
    padding-bottom: 1.25rem;
  }

  .endorsement-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    background: #1B2A4A;
    border-radius: 16px 16px 0 0;
  }

  .endorsement-title {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .endorsement-tabs {
    display: flex;
    align-items: center;
    gap: 0;
    background: #eef4ff;
    border-radius: 14px;
    padding: 10px;
    overflow-x: auto;
    white-space: nowrap;
    margin-bottom: 16px;
  }

  .endorsement-tab {
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

  .endorsement-tab + .endorsement-tab {
    position: relative;
  }

  .endorsement-tab + .endorsement-tab:before {
    content: "";
    position: absolute;
    left: 0;
    top: 10px;
    bottom: 10px;
    width: 1px;
    background: rgba(27, 42, 74, 0.25);
  }

  .endorsement-tab.active {
    background: #1B2A4A;
    color: #fff;
    box-shadow: 0 8px 18px rgba(27, 42, 74, 0.2);
  }

  .endorsement-card {
    background: #fff;
    border-radius: 0 0 16px 16px;
    border: 0;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
  }

  .endorsement-surface {
    background: #f4f8ff;
    border-radius: 16px;
    padding: 16px;
    min-height: 520px;
  }

  .endorsement-toolbar {
    display: flex;
    align-items: end;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 12px;
  }

  .endorsement-toolbar .left {
    display: grid;
    grid-template-columns: 200px 200px 200px 110px;
    gap: 10px;
    align-items: end;
  }

  @media (max-width: 992px) {
    .endorsement-toolbar .left {
      grid-template-columns: 1fr;
      width: 100%;
    }
  }

  .endorsement-icon-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0;
    color: #fff;
  }

  .endorsement-icon-btn.blue {
    background: #003982;
  }

  .endorsement-icon-btn.red {
    background: #ff5c5c;
  }

  .dt-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin: 8px 0 6px;
  }

  .dt-controls .searchbox {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .endorsement-table {
    background: transparent;
    margin: 0;
  }

  .endorsement-table thead th {
    font-size: 12px;
    font-weight: 700;
    color: #2a3a4f;
    border-bottom: 0;
    vertical-align: bottom;
    white-space: nowrap;
  }

  .endorsement-table thead .col-filter input {
    height: 26px;
    font-size: 12px;
    border-radius: 8px;
  }

  .endorsement-table tbody td {
    font-size: 13px;
    border-top: 1px solid rgba(0, 0, 0, 0.04);
  }

  .dt-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 10px;
    color: #6b7785;
    font-size: 12px;
  }

  .dt-pagination .btn {
    padding: 6px 10px;
    font-size: 12px;
    border-radius: 10px;
  }

  .coming-soon {
    display: grid;
    place-items: center;
    min-height: 520px;
    text-align: center;
    padding: 24px;
  }

  .coming-soon img {
    max-width: 320px;
    width: 100%;
    height: auto;
    opacity: 0.95;
  }

  .coming-soon h5 {
    margin-top: 10px;
    margin-bottom: 0;
    font-weight: 700;
    color: #3b4a5a;
  }
</style>

<div class="container-fluid endorsement-page mt-4">
  <div class="endorsement-topbar px-4 py-2">
    <div class="endorsement-title">
      <div>
        <h6 class="mb-0 text-white">Endorsement Management</h6>
      </div>
    </div>

    @php
      $createUrl = route('endorsement.create');
      if ($activeTab === 'general') {
        $createUrl = route('endorsement.create.general');
      } elseif ($activeTab === 'client') {
        $createUrl = route('endorsement.create.client');
      }
    @endphp
    <a href="{{ $createUrl }}" class="btn mb-0 px-2 px-md-4" style="border-radius:12px; background-color: #FFFFFF; color: #1B2A4A; border: 1px solid #FFFFFF;">
     <i class="fas fa-plus"></i> Make New Endorsement
    </a>
  </div>

  <div class="endorsement-card p-3">
    <div class="endorsement-tabs">
      <a class="endorsement-tab" href="{{ route('endorsement.create.general') }}">
        General Insurance
      </a>
      <a class="endorsement-tab" href="{{ route('endorsement.create') }}">
        Life Insurance
      </a>
      <a class="endorsement-tab active" href="{{ route('endorsement.create.client') }}">
        Client Endorsement
      </a>
    </div>

    @if ($activeTab === 'life')
      <div class="endorsement-surface">
        <div class="coming-soon">
          <div>
            <img src="{{ (env('PUBLIC_PATH') ? '/' . trim(env('PUBLIC_PATH'), '/') . '/assets/img/illustrations/icon-documentation.svg' : '/assets/img/illustrations/icon-documentation.svg') }}" alt="Coming soon">
            <h5>Coming Soon...!</h5>
          </div>
        </div>
      </div>
    @else
      <div class="endorsement-surface">
        <div class="endorsement-toolbar">
          <div class="left">
            <div>
              <label class="form-label mb-1">Select Date Type</label>
              <select class="form-select">
                <option selected>Transaction Date</option>
                <option>Effective Date</option>
                <option>Created Date</option>
              </select>
            </div>
            <div>
              <label class="form-label mb-1">From Date</label>
              <input type="date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
            <div>
              <label class="form-label mb-1">End Date</label>
              <input type="date" class="form-control" placeholder="End date">
            </div>
            <div>
              <label class="form-label mb-1 d-none d-lg-block">&nbsp;</label>
              <button type="button" class="btn w-100 mb-0" style="border-radius:12px; background-color: #1B2A4A; color: #FFFFFF;">Clear</button>
            </div>
          </div>
        </div>

        <div class="dt-controls">
          <div class="d-flex align-items-center gap-2 small">
            <span>Show</span>
            <select class="form-select form-select-sm" style="width:80px;">
              <option selected>10</option>
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
            <span>entries</span>
          </div>

          <div class="searchbox">
            <span class="small">Search:</span>
            <input type="text" class="form-control form-control-sm" style="width:190px;">
          </div>
        </div>

        <div class="table-responsive">
          <table class="table endorsement-table">
            <thead>
              <tr>
                <th>Action</th>
                <th>Transaction Date</th>
                <th>Family Group</th>
                <th>Client Name</th>
                <th>General Policies</th>
                <th>Life Policies</th>
                <th>Effective Date</th>
                <th>Reference No</th>
                <th>Remark</th>
                <th>Created By</th>
                <th>Modified By</th>
              </tr>
              <tr class="col-filter">
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
                <th></th>
                <th></th>
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
                <th><input class="form-control form-control-sm" placeholder="Search"></th>
              </tr>
            </thead>
            <tbody>
              @if (!empty($endorsements))
                @foreach ($endorsements as $row)
                  <tr>
                    <td>
                      <a href="{{ route('endorsement.view', $row['id']) }}" class="endorsement-icon-btn blue" title="View">
                        <i class="fa-solid fa-eye"></i>
                      </a>
                    </td>
                    <td>{{ isset($row['transaction_date']) ? date('d-m-Y', strtotime($row['transaction_date'])) : '' }}</td>
                    <td>{{ $row['family_group_name'] ?? '--' }}</td>
                    <td>{{ $row['policy_holder_name'] ?? '--' }}</td>
                    <td>
                      @if ($activeTab === 'general')
                        {{ $row['policy_number'] ?? '--' }}
                      @elseif ($activeTab === 'client')
                        {{ $row['general_policy_number'] ?? '--' }}
                      @else
                        -
                      @endif
                    </td>
                    <td>
                      @if ($activeTab === 'client')
                        {{ $row['life_policy_number'] ?? '--' }}
                      @else
                        -
                      @endif
                    </td>
                    <td>{{ !empty($row['endorsement_effective_date']) ? date('d-m-Y', strtotime($row['endorsement_effective_date'])) : '--' }}</td>
                    <td>{{ $row['reference_no'] ?? '--' }}</td>
                    <td>{{ $row['remark_reason'] ?? '--' }}</td>
                    <td>{{ isset($userMap[$row['created_by']]) ? $userMap[$row['created_by']] : '--' }}</td>
                    <td>{{ isset($userMap[$row['updated_by']]) ? $userMap[$row['updated_by']] : '--' }}</td>
                  </tr>
                @endforeach
              @else
                <tr>
                  <td colspan="11" class="text-center py-4" style="color:#6b7785;">
                    No data available in table
                  </td>
                </tr>
              @endif
            </tbody>
          </table>
        </div>

        <div class="dt-footer">
          <div>Showing 0 to 0 of 0 entries</div>
          <div class="dt-pagination">
            <button class="btn btn-light" type="button" disabled>Previous</button>
            <button class="btn btn-light" type="button" disabled>Next</button>
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
@endsection
