@extends('layouts.app')

@section('page_title', 'All Notifications')

@section('content')
    <div class="container-fluid p-0">
        <div class="card border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white border-bottom-0 py-3 px-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-0">Notifications</h4>
                        <p class="text-muted small mb-0">Your recent alerts and system messages.</p>
                    </div>
                    <div class="notification-actions">
                        <button type="button" class="notification-action-btn notification-action-read" id="markAllReadBtn"
                            @disabled($unreadCount < 1)>
                            <span class="notification-action-icon"><i class="fa-solid fa-check-double"></i></span>
                            Mark All Read
                        </button>
                        <button type="button" class="notification-action-btn notification-action-delete" id="deleteAllNotificationsBtn"
                            @disabled($totalCount < 1)>
                            <span class="notification-action-icon"><i class="fa-solid fa-trash-can"></i></span>
                            Delete All
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="notification-list">
                    @forelse($notifications as $notification)
                        <div class="notification-row align-items-center @if(!$notification->is_read) unread @endif">
                            <span class="notification-avatar">
                                <i class="bi bi-bell"></i>
                            </span>
                            <div class="d-flex flex-grow-1 justify-content-between align-items-center">
                                <div class="notification-message">
                                    {{ $notification->notification_text }}
                                </div>
                                <div class="d-flex flex-wrap align-items-center">
                                    <div class="notification-time">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </div>
                            @if(!$notification->is_read)
                            <div class="btn-group">
                                <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <button class="dropdown-item mark-as-read del_notif fw-semibold" type="button"
                                        data-id="{{ $notification->id }}">
                                        <i class="fa-solid fa-check-double me-2"></i>Mark as Read
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted py-5">
                            No notifications found.
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer bg-white border-top-0 py-3">
                <div class="d-flex justify-content-end">
                    {{ $notifications->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .notification-list {
            display: flex;
            flex-direction: column;
        }

        .notification-list .notification-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border-bottom: 1px solid var(--crm-border);
            transition: background .15s ease;
        }

        .notification-list .notification-row:hover {
            background: #F8FAFC;
        }

        .notification-list .notification-row.unread {
            background-color: rgba(59, 91, 219, 0.05);
        }

        .notification-list .notification-row.unread:hover {
            background-color: rgba(59, 91, 219, 0.08);
        }

        .notification-list .notification-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(59, 91, 219, .1);
            color: var(--crm-accent);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .95rem;
            flex-shrink: 0;
        }

        .notification-list .notification-message {
            font-size: .95rem;
            line-height: 1.4;
            color: var(--crm-text-body);
        }

        .notification-list .notification-time {
            font-size: .8rem;
            color: var(--crm-text-muted);
        }

        .notification-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .notification-action-btn {
            border: 0;
            border-radius: 999px;
            color: #fff;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .8rem;
            font-weight: 700;
            min-height: 36px;
            padding: 6px 14px 6px 6px;
            transition: transform .15s ease, box-shadow .15s ease, opacity .15s ease;
        }

        .notification-action-btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }

        .notification-action-btn:disabled {
            cursor: not-allowed;
            opacity: .55;
        }

        .notification-action-read {
            background: #0f2d4f;
            box-shadow: 0 8px 16px rgba(15, 45, 79, .22);
        }

        .notification-action-delete {
            background: #dc3545;
            box-shadow: 0 8px 16px rgba(220, 53, 69, .24);
        }

        .notification-action-icon {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .notification-action-read .notification-action-icon {
            color: #0f2d4f;
        }

        .notification-action-delete .notification-action-icon {
            color: #dc3545;
        }

        @media (max-width: 575.98px) {
            .card-header .d-flex.justify-content-between {
                align-items: flex-start !important;
                flex-direction: column;
                gap: 12px;
            }

            .notification-actions {
                justify-content: flex-start;
                width: 100%;
            }
        }

    </style>
@endpush

@push('scripts')
    <script>
        const notificationHeaders = {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        };

        function showNotificationAlert(options) {
            if (typeof Swal === 'undefined') {
                window.crmAlert(options.text || options.title || 'Notification action completed.');
                return Promise.resolve({ isConfirmed: true });
            }

            return Swal.fire(options);
        }

        async function runNotificationAction(url, method, successTitle, fallbackMessage) {
            const response = await fetch(url, {
                method,
                headers: notificationHeaders
            });
            const data = await response.json();

            if (!response.ok || !data.success) {
                throw new Error(data.message || fallbackMessage);
            }

            await showNotificationAlert({
                icon: 'success',
                title: successTitle,
                text: data.message || fallbackMessage,
                timer: 1600,
                showConfirmButton: false
            });

            location.reload();
        }

        document.querySelectorAll('.mark-as-read').forEach(button => {
            button.addEventListener('click', function () {
                const id = this.getAttribute('data-id');
                runNotificationAction(`/notifications/${id}/read`, 'PATCH', 'Marked as read', 'Error marking notification as read')
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationAlert({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'An error occurred while updating the notification.'
                        });
                    });
            });
        });

        document.getElementById('markAllReadBtn')?.addEventListener('click', function () {
            showNotificationAlert({
                icon: 'question',
                title: 'Mark all as read?',
                text: 'All unread notifications will be marked as read.',
                showCancelButton: true,
                confirmButtonText: 'Yes, mark all',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#0f2d4f'
            }).then(result => {
                if (!result.isConfirmed) return;

                runNotificationAction('{{ route('notifications.read-all') }}', 'PATCH', 'Marked all as read', 'Unable to mark notifications as read')
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationAlert({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Unable to mark notifications as read.'
                        });
                    });
            });
        });

        document.getElementById('deleteAllNotificationsBtn')?.addEventListener('click', function () {
            showNotificationAlert({
                icon: 'warning',
                title: 'Delete all notifications?',
                text: 'This will permanently remove all your notifications.',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete all',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#dc3545'
            }).then(result => {
                if (!result.isConfirmed) return;

                runNotificationAction('{{ route('notifications.delete-all') }}', 'DELETE', 'Deleted', 'Unable to delete notifications')
                    .catch(error => {
                        console.error('Error:', error);
                        showNotificationAlert({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Unable to delete notifications.'
                        });
                    });
            });
        });
    </script>
@endpush
