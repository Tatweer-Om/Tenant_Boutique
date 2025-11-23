<script>
    $(document).ready(function() {

        // ---------------- Load Boutiques Function ----------------
        function loadBoutiques(page = 1, search = '') {
            $.get("{{ url('boutiques/list') }}", {
                page: page,
                search: search
            }, function(res) {

                // -------- Desktop Table Rows --------
                let tableRows = "";
                $.each(res.data, function(index, boutique) {
                    tableRows += `
                    <tr class="border-t hover:bg-pink-50/60 transition" data-id="${boutique.id}">
                        <td class="px-3 py-3">${index + 1}</td>
                        <td class="px-3 py-3 font-bold text-gray-800">${boutique.boutique_name ?? ''}</td>
                        <td class="px-3 py-3 text-gray-700">${boutique.shelf_no ?? '-'}</td>
                        <td class="px-3 py-3 font-semibold text-gray-800">${boutique.monthly_rent ?? '-'} ر.ع</td>
                        <td class="px-3 py-3">${boutique.rent_date ?? '-'}</td>
                        <td class="px-3 py-3 truncate max-w-[220px]" title="${boutique.boutique_address ?? ''}">
                            ${boutique.boutique_address ?? ''}
                        </td>
                        <td class="px-3 py-3 text-center">
                            <div class="flex justify-center gap-3 text-xs sm:text-sm font-semibold">
                                <button class="flex items-center gap-1 text-[var(--primary-color)] hover:underline viewBtn" data-id="${boutique.id}">
                                    <span class="material-symbols-outlined text-base">visibility</span> عرض
                                </button>
                          
                            <button class="flex items-center gap-1 text-blue-600 hover:underline editBtn"
                                    onclick="window.location.href='/edit_boutique/${boutique.id}'"
                                    data-id="${boutique.id}">
                                <span class="material-symbols-outlined text-base">edit</span> تعديل
                            </button>


                                                            <button class="flex items-center gap-1 text-red-600 hover:underline deleteBtn" data-id="${boutique.id}">
                                                                <span class="material-symbols-outlined text-base">delete</span> حذف
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>`;
                                            });
                                            $("#desktop_boutique_body").html(tableRows);

                                            // -------- Mobile Cards --------
                                            let mobileCards = '';
                                            $.each(res.data, function(index, boutique) {
                                                mobileCards += `
                                                <div class="bg-white border border-pink-100 rounded-2xl shadow-sm hover:shadow-md transition p-4 mb-4 md:hidden">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-bold text-gray-900 text-lg truncate">${boutique.boutique_name ?? ''}</h3>
                                    <span class="text-sm text-gray-500 font-semibold">${boutique.shelf_no ?? '#' + (index+1) + 'A'}</span>
                                </div>
                                <div class="space-y-1 text-sm text-gray-700 mt-2">
                                    <p><span class="font-semibold"><?= trans('messages.monthly_rent', [], session('locale')) ?>:</span> ${boutique.monthly_rent ?? '-'} ر.ع</p>
                                    <p><span class="font-semibold"><?= trans('messages.rent_date', [], session('locale')) ?>:</span> ${boutique.rent_date ?? '-'}</p>
                                    <p class="truncate"><span class="font-semibold"><?= trans('messages.address', [], session('locale')) ?>:</span> ${boutique.boutique_address ?? ''}</p>
                                </div>
                                <div class="flex justify-around border-t pt-3 mt-2 text-xs font-semibold">
                                    <button class="flex flex-col items-center gap-1 text-[var(--primary-color)] hover:scale-105 transition viewBtn" data-id="${boutique.id}">
                                        <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">visibility</span> <?= trans('messages.view', [], session('locale')) ?>
                                    </button>
                                <button class="flex flex-col items-center gap-1 text-blue-600 hover:scale-105 transition editBtn"
                                    onclick="window.location.href='/edit_boutique/${boutique.id}'">
                                <span class="material-symbols-outlined bg-blue-50 text-blue-600 p-2 rounded-full text-base">edit</span>
                                <?= trans('messages.edit', [], session('locale')) ?>
                            </button>
                                    <button class="flex flex-col items-center gap-1 text-red-600 hover:scale-105 transition deleteBtn" data-id="${boutique.id}">
                                        <span class="material-symbols-outlined bg-red-50 text-red-600 p-2 rounded-full text-base">delete</span> <?= trans('messages.delete', [], session('locale')) ?>
                                    </button>
                                </div>
                            </div>
                `;
                });
                $("#mobile_boutique_cards").html(mobileCards);

                // -------- Pagination --------
                let pagination = "";
                pagination += `<li class="px-3 py-1 rounded-full ${!res.prev_page_url ? "opacity-50 pointer-events-none" : "bg-gray-200 hover:bg-gray-300"}">
                                <a href="${res.prev_page_url || '#'}">&laquo;</a>
                           </li>`;
                for (let i = 1; i <= res.last_page; i++) {
                    pagination += `<li class="px-3 py-1 rounded-full ${res.current_page==i ? "bg-[var(--primary-color)] text-white" : "bg-gray-200 hover:bg-gray-300"}">
                                   <a href="{{ url('boutiques/list') }}?page=${i}">${i}</a>
                               </li>`;
                }
                pagination += `<li class="px-3 py-1 rounded-full ${!res.next_page_url ? "opacity-50 pointer-events-none" : "bg-gray-200 hover:bg-gray-300"}">
                               <a href="${res.next_page_url || '#'}">&raquo;</a>
                           </li>`;
                $("#pagination").html(pagination);
            });
        }

        // ---------------- Handle Pagination Clicks ----------------
        $(document).on("click", "#pagination a", function(e) {
            e.preventDefault();
            let href = $(this).attr("href");
            if (href && href !== "#") {
                let page = new URL(href).searchParams.get("page");
                let search = $("#search_boutique").val() || '';
                if (page) loadBoutiques(page, search);
            }
        });

        // ---------------- Search (button + input) ----------------
        function filterSearch() {
            let search = $("#search_boutique").val().toLowerCase();
            $("#desktop_boutique_body tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(search) > -1);
            });
            $("#mobile_boutique_cards > div").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(search) > -1);
            });
        }

        $("#search_button").on("click", filterSearch);
        $("#search_boutique").on("keyup", filterSearch);

        // ---------------- Initial Load ----------------
        loadBoutiques();
   $(document).on('click', '.deleteBtn', function() {
    let id = $(this).closest('tr').data('id'); // get boutique id

    Swal.fire({
        title: '<?= trans("messages.confirm_delete_title", [], session("locale")) ?>',
        text: '<?= trans("messages.confirm_delete_text", [], session("locale")) ?>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '<?= trans("messages.yes_delete", [], session("locale")) ?>',
        cancelButtonText: '<?= trans("messages.cancel", [], session("locale")) ?>'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '<?= url("boutique") ?>/' + id, // boutique delete route
                method: 'DELETE',
                data: {
                    _token: '<?= csrf_token() ?>'
                },
                success: function(data) {
                    loadBoutiques(); // reload table after delete
                    Swal.fire(
                        '<?= trans("messages.deleted_success", [], session("locale")) ?>',
                        '<?= trans("messages.deleted_success_text", [], session("locale")) ?>',
                        'success'
                    );
                },
                error: function() {
                    Swal.fire(
                        '<?= trans("messages.delete_error", [], session("locale")) ?>',
                        '<?= trans("messages.delete_error_text", [], session("locale")) ?>',
                        'error'
                    );
                }
            });
        }
    });
});
    });

 

</script>