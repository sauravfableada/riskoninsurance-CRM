document.addEventListener('DOMContentLoaded', function () {
    loadEmailTemplates();
});

function loadEmailTemplates() {
    var tbody = document.querySelector('#email-templates-table-body');
    if (!tbody) return;

    fetch('/masters/default-email-templates-list')
        .then(function (res) { return res.json(); })
        .then(function (data) {
            tbody.innerHTML = '';

            if (!data || !data.length) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No email templates found.</td></tr>';
                return;
            }

            data.forEach(function (tpl) {
                var tr = document.createElement('tr');
                var createdAt = tpl.created_at ? tpl.created_at : '';
                var createdBy = tpl.creator_name ? tpl.creator_name : '';

                tr.innerHTML = '' +
                    '<td class="ps-4 fw-semibold">' + tpl.name + '</td>' +
                    '<td>' + createdBy + '</td>' +
                    '<td>' + createdAt + '</td>' +
                    '<td class="pe-4 text-end">' +
                        '<a href="/masters/default-email-templates/' + tpl.id + '" class="btn btn-sm btn-outline-secondary">' +
                            '<i class="bi bi-eye"></i>' +
                        '</a> ' +
                        '<a href="/masters/default-email-templates/' + tpl.id + '/edit" class="btn btn-sm crm-action-btn">' +
                            '<i class="bi bi-pencil"></i>' +
                        '</a> ' +
                        '<button type="button" class="btn btn-sm crm-action-btn btn-email-template-delete" data-url="/masters/default-email-templates/' + tpl.id + '">' +
                            '<i class="bi bi-trash text-danger"></i>' +
                        '</button>' +
                    '</td>';

                tbody.appendChild(tr);
            });

            attachEmailTemplateDeleteHandlers();
        })
        .catch(function () {
            tbody.innerHTML = '<tr><td colspan="3" class="text-center text-danger py-4">Failed to load email templates.</td></tr>';
        });
}

function attachEmailTemplateDeleteHandlers() {
    document.querySelectorAll('.btn-email-template-delete').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var url = this.getAttribute('data-url');
            if (!url) return;

            window.showDeleteConfirm('Are you sure you want to delete this email template?').then(function (result) {
                if (!result.isConfirmed) return;

                var form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                var token = document.querySelector('meta[name="csrf-token"]');
                if (token) {
                    var inputToken = document.createElement('input');
                    inputToken.type = 'hidden';
                    inputToken.name = '_token';
                    inputToken.value = token.getAttribute('content');
                    form.appendChild(inputToken);
                }

                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                document.body.appendChild(form);
                form.submit();
            });
        });
    });
}
