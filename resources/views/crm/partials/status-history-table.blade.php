<div class="card shadow-sm border-0 mt-4">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Status Update History</h5>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">#</th>
                        <th>Status</th>
                        <th>Comment</th>
                        <th>Updated By</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody class="js-status-history-body">
                    @forelse($histories as $history)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @php
                                    $statusLabel = match (strtolower((string) $history->status)) {
                                        'ready_to_close' => 'Ready to Close',
                                        'won' => 'Closed Won',
                                        'lost' => 'Closed Lost',
                                        default => $history->status ? ucwords(str_replace('_', ' ', $history->status)) : '--',
                                    };
                                @endphp
                                {{ $statusLabel }}
                            </td>
                            <td>{{ $history->comment ?: '--' }}</td>
                            <td>{{ $history->updater?->name ?? 'System' }}</td>
                            <td>{{ $history->created_at?->timezone('Asia/Kolkata')->format('d M Y h:i A') ?? '--' }}</td>
                        </tr>
                    @empty
                        <tr class="js-status-history-empty">
                            <td colspan="5" class="text-center text-muted py-4">No status updates found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
