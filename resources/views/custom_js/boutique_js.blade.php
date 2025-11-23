<script>
$(document).ready(function () {

    $('#add_boutique').on('submit', function (e) {
        e.preventDefault();

        // Safe value getter — prevents undefined.trim() errors
        const safeVal = (selector) => {
            return $(selector).length ? ($(selector).val() || '').trim() : '';
        };

        let boutique_name = safeVal('#boutique_name');
        let address       = safeVal('#boutique_address');
        let monthly_rent  = safeVal('#monthly_rent');
        let rent_date     = safeVal('#rent_date');

        // --- Validations ---
        if (!boutique_name) {
            show_notification(
                'error',
                '<?= trans("messages.enter_boutique_name", [], session("locale")) ?>'
            );
            return;
        }

        if (!address) {
            show_notification(
                'error',
                '<?= trans("messages.enter_address", [], session("locale")) ?>'
            );
            return;
        }

        if (!monthly_rent) {
            show_notification(
                'error',
                '<?= trans("messages.enter_monthly_rent", [], session("locale")) ?>'
            );
            return;
        }

        if (!rent_date) {
            show_notification(
                'error',
                '<?= trans("messages.enter_rent_date", [], session("locale")) ?>'
            );
            return;
        }

        // --- Ajax Submit ---
        $.ajax({
            url: "{{ route('add_boutique') }}",
            type: "POST",
            data: $(this).serialize(),

            success: function (res) {
                if (res.success) {
                    show_notification('success', '<?= trans("messages.boutique_added_success", [], session("locale")) ?>' );
                    $('#add_boutique')[0].reset();
                } else {
                    show_notification(
                    'error','<?= trans("messages.something_wrong", [], session("locale")) ?>'
                        
                    );
                }
            },

            error: function () {
                show_notification(
                    '<?= trans("messages.server_error", [], session("locale")) ?>',
                    'error'
                );
            }
        });

    });

});


   $(document).ready(function() {

       function loadboutiques(page = 1) {
    $.get("{{ url('boutiques/list') }}?page=" + page, function(res) {

        // ---- Table Rows ----
        let rows = '';
        $.each(res.data, function(i, boutique) {
            rows += `
            <tr class="hover:bg-pink-50/50 transition-colors" data-id="${boutique.id}">
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${boutique.boutique_name_ar}</td>
                <td class="px-4 sm:px-6 py-5 text-[var(--text-primary)]">${boutique.boutique_code_ar}</td>
                <td class="px-4 sm:px-6 py-5 text-center">
                    <div class="flex items-center justify-center gap-4 sm:gap-6">
                        <button class="edit-btn icon-btn">
                            <span class="material-symbols-outlined">edit</span>
                        </button>
                        <button class="delete-btn icon-btn hover:text-red-500">
                            <span class="material-symbols-outlined">delete</span>
                        </button>
                    </div>
                </td>
            </tr>
              <tr class="border-t hover:bg-pink-50/60 transition" data-id="${boutique.id}">
            <td class="px-3 py-3">1</td>
            <td class="px-3 py-3 font-bold text-gray-800">  ${boutique.boutique_name_ar}</td>
            <td class="px-3 py-3 font-bold text-gray-800">  ${boutique.boutique_name_ar}</td>
            <td class="px-3 py-3 font-bold text-gray-800">  ${boutique.boutique_name_ar}</td>
            <td class="px-3 py-3 font-bold text-gray-800">  ${boutique.boutique_name_ar}</td>
            <td class="px-3 py-3 font-bold text-gray-800">  ${boutique.boutique_name_ar}</td>

            <td class="px-3 py-3 text-center">
              <div class="flex justify-center gap-3 text-xs sm:text-sm font-semibold">
                <button class="flex items-center gap-1 text-[var(--primary-color)] hover:underline">
                  <span class="material-symbols-outlined text-base">visibility</span> عرض
                </button>
                <button class="flex items-center gap-1 text-blue-600 hover:underline">
                  <span class="material-symbols-outlined text-base">edit</span> تعديل
                </button>
                <button class="flex items-center gap-1 text-red-600 hover:underline">
                  <span class="material-symbols-outlined text-base">delete</span> حذف
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
                <a href="{{ url('boutiques/list') }}?page=${i}">${i}</a>
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
        if (page) loadboutiques(page);
    }
});


        // Initial load
        loadboutiques();
        // initial table load



        $('#search_boutique').on('keyup', function() {
            let value = $(this).val().toLowerCase();

            $('tbody tr').filter(function() {
                $(this).toggle(
                    $(this).text().toLowerCase().indexOf(value) > -1
                );
            });
        }); 

   });
</script>
