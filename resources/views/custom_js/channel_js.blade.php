<script>
    $(document).ready(function() {

       function loadchannels(page = 1) {
    $.get("{{ url('channels/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, channel) {
            const statusForPos = channel.status_for_pos || 1; // Default to 1 if null/undefined
            const isActive = statusForPos == 1;
            const statusText = isActive ? '<?= trans("messages.active_for_pos", [], session("locale")) ?>' : '<?= trans("messages.inactive_for_pos", [], session("locale")) ?>';
            const statusClass = isActive ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-400 hover:bg-gray-500';
            const statusIcon = isActive ? 'check_circle' : 'cancel';
            
            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors" data-id="${channel.id}">
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${channel.channel_name_ar}</td>
                <td class="px-4 sm:px-6 py-5 text-center">
                    <div class="flex items-center justify-center gap-4 sm:gap-6">
                        <a href="{{ url('channel_profile') }}/${channel.id}" 
                           class="icon-btn" 
                           title="<?= trans('messages.view_profile', [], session('locale')) ?? 'View Profile' ?>">
                            <span class="material-symbols-outlined">person</span>
                        </a>
                        <button class="edit-btn icon-btn">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button class="status-toggle-btn px-4 py-2 rounded-full text-white text-sm font-medium transition-colors ${statusClass}" 
                                data-status="${statusForPos}" 
                                data-id="${channel.id}"
                                title="${statusText}">
                            <span class="material-symbols-outlined text-base align-middle mr-1">${statusIcon}</span>
                            ${statusText}
                        </button>
                    </div>
                </td>
            </tr>
            `;
        });
        $('tbody').html(rows);

        // ---- Pagination ----
        let pagination = '';

        // Previous
        pagination += `
        <li class="px-3 py-1 rounded-full ${!res.prev_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
            <a href="${res.prev_page_url ? res.prev_page_url : '#'}">&laquo;</a>
        </li>`;

        // Page numbers
        for (let i = 1; i <= res.last_page; i++) {
            pagination += `
            <li class="px-3 py-1 rounded-full ${res.current_page == i ? ' text-white' : 'bg-gray-200 hover:bg-gray-300'}">
                <a href="{{ url('channels/list') }}?page=${i}">${i}</a>
            </li>
            `;
        }

        // Next
        pagination += `
        <li class="px-3 py-1 rounded-full ${!res.next_page_url ? 'opacity-50 pointer-events-none' : 'bg-gray-200 hover:bg-gray-300'}">
            <a href="${res.next_page_url ? res.next_page_url : '#'}">&raquo;</a>
        </li>`;

        $('#pagination').html(pagination);
    });
}

// Handle pagination click
$(document).on('click', '#pagination a', function(e) {
    e.preventDefault();
    let href = $(this).attr('href');
    if (href && href !== '#') {
        let page = new URL(href).searchParams.get('page');
        if (page) loadchannels(page);
    }
});


        // Initial load
        loadchannels();
        // initial table load



        $('#search_channel').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        });
        // Add / Update channel
        $('#channel_form').submit(function(e) {
            e.preventDefault();
            let id = $('#channel_id').val();
            let channel_name_ar = $('#channel_name_ar').val().trim();
            let channel_name_en = $('#channel_name_en').val().trim();
         

            // Simple validation
            if (!channel_name_ar) {
                show_notification('error', '<?= trans("messages.enter_channel_name_ar", [], session("locale")) ?>');
                return;
            }
            if (!channel_name_en) {
                show_notification('error', '<?= trans("messages.enter_channel_name_en", [], session("locale")) ?>');
                return;
            }
        

            let url = id ? `{{ url('channels') }}/${id}` : "{{ url('channels') }}";

            // Serialize form data
            let data = $(this).serialize();
            if (id) data += '&_method=PUT'; 
            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function(res) {
                    // Reset Alpine.js state using custom event
                    window.dispatchEvent(new CustomEvent('close-modal'));
                    
                    // Reset form
                    $('#channel_form')[0].reset();
                    $('#channel_id').val('');
                    loadchannels();
                    show_notification(
                        'success',
                        id ?
                        '<?= trans("messages.updated_success", [], session("locale")) ?>' :
                        '<?= trans("messages.added_success", [], session("locale")) ?>'
                    );
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            show_notification('error', value[0]);
                        });
                    } else {
                        show_notification('error', '<?= trans("messages.generic_error", [], session("locale")) ?>');

                    }
                }
            });
        });


        // Close modal button
        $('#close_modal').click(function() {
            // Reset Alpine.js state using custom event
            window.dispatchEvent(new CustomEvent('close-modal'));
        });

        // Edit channel
        $(document).on('click', '.edit-btn', function() {
            let id = $(this).closest('tr').data('id');

            $.get("{{ url('channels') }}/" + id, function(channel) {
                $('#channel_id').val(channel.id);
                $('#channel_name_en').val(channel.channel_name_en);
                $('#channel_name_ar').val(channel.channel_name_ar);
                $('#channel_code_en').val(channel.channel_code_en);
                $('#channel_code_ar').val(channel.channel_code_ar);
                
                // Open modal using Alpine event
                window.dispatchEvent(new CustomEvent('open-modal'));
            }).fail(function() {
                show_notification('error', '<?= trans("messages.fetch_error", [], session("locale")) ?>');
            });
        });

        // Toggle channel status for POS
        $(document).on('click', '.status-toggle-btn', function() {
            let id = $(this).data('id');
            let currentStatus = $(this).data('status');
            let newStatus = currentStatus == 1 ? 2 : 1;
            let statusText = newStatus == 1 ? '<?= trans("messages.active_for_pos", [], session("locale")) ?>' : '<?= trans("messages.inactive_for_pos", [], session("locale")) ?>';
            let currentStatusText = currentStatus == 1 ? '<?= trans("messages.active_for_pos", [], session("locale")) ?>' : '<?= trans("messages.inactive_for_pos", [], session("locale")) ?>';
            let confirmTitle = newStatus == 1 ? '<?= trans("messages.confirm_activate_title", [], session("locale")) ?>' : '<?= trans("messages.confirm_deactivate_title", [], session("locale")) ?>';
            let confirmText = newStatus == 1 ? '<?= trans("messages.confirm_activate_text", [], session("locale")) ?>' : '<?= trans("messages.confirm_deactivate_text", [], session("locale")) ?>';

            Swal.fire({
                title: confirmTitle,
                text: confirmText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus == 1 ? '#10b981' : '#6b7280',
                cancelButtonColor: '#d33',
                confirmButtonText: '<?= trans("messages.yes_confirm", [], session("locale")) ?>',
                cancelButtonText: '<?= trans("messages.cancel", [], session("locale")) ?>'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '<?= url("channels") ?>/' + id + '/update-status',
                        method: 'POST',
                        data: {
                            _token: '<?= csrf_token() ?>',
                            status_for_pos: newStatus
                        },
                        success: function(data) {
                            loadchannels(); // reload table
                            Swal.fire(
                                '<?= trans("messages.status_updated", [], session("locale")) ?>',
                                '<?= trans("messages.status_changed_to", [], session("locale")) ?>: ' + statusText,
                                'success'
                            );
                        },
                        error: function(xhr) {
                            Swal.fire(
                                '<?= trans("messages.status_update_error", [], session("locale")) ?>',
                                '<?= trans("messages.status_update_error_text", [], session("locale")) ?>',
                                'error'
                            );
                        }
                    });
                }
            });
        });

    });
</script>