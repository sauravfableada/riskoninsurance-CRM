@extends('layouts.app')

@section('content')
    @php
        $mastersReadOnly = request()->routeIs('masters.*')
            && !request()->routeIs('masters.customers.*', 'masters.family_groups.*')
            && auth()->check()
            && !auth()->user()->isAdmin()
            && auth()->user()->hasMatrixPermission('view_masters');
    @endphp
    <div class="container-fluid px-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            @yield('masters_content')
        </div>
    </div>

    @if($mastersReadOnly)
        <style>
            .master-read-only-hidden {
                display: none !important;
            }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                function applyMastersReadOnlyMode() {
                    document.querySelectorAll('table').forEach(function (table) {
                        var actionIndexes = [];
                        table.querySelectorAll('thead tr').forEach(function (headerRow) {
                            Array.from(headerRow.children).forEach(function (cell, index) {
                                if (/^actions?$/i.test(cell.textContent.trim())) {
                                    actionIndexes.push(index);
                                    cell.classList.add('master-read-only-hidden');
                                }
                            });
                        });

                        actionIndexes = Array.from(new Set(actionIndexes));
                        table.querySelectorAll('tbody tr').forEach(function (row) {
                            actionIndexes.forEach(function (index) {
                                if (row.children[index]) {
                                    row.children[index].classList.add('master-read-only-hidden');
                                }
                            });
                        });
                    });

                    document.querySelectorAll('a, button').forEach(function (control) {
                        var label = control.textContent.trim();
                        var href = control.getAttribute('href') || '';
                        var id = control.id || '';
                        if (/^(add|create|new)\b/i.test(label) || /\/create(?:\/|$)/i.test(href) || /^add/i.test(id)) {
                            control.classList.add('master-read-only-hidden');
                        }
                    });

                    document.querySelectorAll('table input[type="checkbox"], table .form-switch, table [data-action="status"], table .status-toggle')
                        .forEach(function (control) {
                            control.classList.add('master-read-only-hidden');
                        });
                }

                applyMastersReadOnlyMode();
                new MutationObserver(applyMastersReadOnlyMode).observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });
        </script>
    @endif
@endsection