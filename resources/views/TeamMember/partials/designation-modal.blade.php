<style>
    #designation + .select2-container,
    .select2-container.team-designation-container { width: 100% !important; display: block !important; }
    #designation + .select2-container .select2-selection--single,
    .team-designation-container .select2-selection--single {
        height: 46px !important;
        min-height: 46px !important;
        border: 1px solid #dee2e6;
        border-radius: .375rem;
        display: flex;
        align-items: center;
    }
    #designation + .select2-container .select2-selection__rendered,
    .team-designation-container .select2-selection__rendered { padding: 0 2.25rem 0 .75rem !important; line-height: 44px !important; }
    #designation + .select2-container .select2-selection__arrow,
    .team-designation-container .select2-selection__arrow { height: 44px !important; top: 0 !important; }
    .team-designation-container.select2-container--focus .select2-selection--single,
    .team-designation-container.select2-container--open .select2-selection--single {
        border-color: #86b7fe;
        box-shadow: 0 0 0 .25rem rgba(13,110,253,.25);
    }
</style>
<script>
function initDesignationSelector() {
    const $select = $('#designation');
    $select.select2({
        placeholder: 'Search or create designation',
        allowClear: true,
        tags: true,
        width: '100%',
        containerCssClass: 'team-designation-container',
        createTag: function(params) {
            const term = $.trim(params.term);
            if (!term) return null;
            const exists = Array.from($select[0].options).some(option => option.text.trim().toLowerCase() === term.toLowerCase());
            return exists ? null : {id: term, text: 'Create "' + term + '"', designationName: term, newTag: true};
        }
    });

    $select.on('select2:select', function(event) {
        const item = event.params.data;
        if (!item.newTag) return;
        const name = item.designationName;
        $.ajax({
            url: @json(route('masters.team_member_designations.store')),
            type: 'POST',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), 'Accept': 'application/json'},
            data: {name: name},
            success: function(res) {
                $select.find('option').filter(function() { return this.value === item.id; }).remove();
                $select.append(new Option(res.data.name, res.data.name, true, true)).trigger('change');
                window.showAlert('success', res.message || 'Designation created');
            },
            error: function(xhr) {
                $select.find('option').filter(function() { return this.value === item.id; }).remove();
                $select.val(null).trigger('change');
                window.showAlert('error', xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || {})[0]?.[0] || 'Unable to create designation.');
            }
        });
    });
}
</script>
