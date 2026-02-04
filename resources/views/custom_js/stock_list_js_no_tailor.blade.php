<script>
    var currentStockPage = 1;
    const trans = {
        details: "{{ trans('messages.details', [], session('locale')) }}",
        enter_quantity: "{{ trans('messages.enter_quantity', [], session('locale')) }}",
        edit: "{{ trans('messages.edit', [], session('locale')) }}",
        delete: "{{ trans('messages.delete', [], session('locale')) }}",
        design: "{{ trans('messages.design', [], session('locale')) }}",
        category: "{{ trans('messages.category', [], session('locale')) }}",
        size: "{{ trans('messages.size', [], session('locale')) }}",
        color: "{{ trans('messages.color', [], session('locale')) }}",
        quantity: "{{ trans('messages.quantity', [], session('locale')) }}",
        failed_to_load_details: "{{ trans('messages.failed_to_load_details', [], session('locale')) }}",
        success_title: "{{ trans('messages.success_title', [], session('locale')) }}",
        error_title: "{{ trans('messages.error_title', [], session('locale')) }}",
        error_occurred: "{{ trans('messages.error_occurred', [], session('locale')) }}",
        error_saving: "{{ trans('messages.error_saving', [], session('locale')) }}",
        saving: "{{ trans('messages.saving', [], session('locale')) }}",
        please_enter_pull_notes: "{{ trans('messages.please_enter_pull_notes', [], session('locale')) }}",
        pieces: "{{ trans('messages.pieces', [], session('locale')) }}",
        abaya_image: "{{ trans('messages.abaya_image', [], session('locale')) }}"
    };

    function loadStock(page = 1) {
        currentStockPage = page || currentStockPage || 1;
        $.get("{{ url('stock/list') }}", { page: page })
            .done(function(res) {
                let tableRows = "";
                $.each(res.data, function(index, stock) {
                    let image = stock.images && stock.images.length ? stock.images[0].image_path : '';
                    let size = '-', color = '-', quantity = 0;
                    if (stock.color_sizes && stock.color_sizes.length > 0) {
                        const first = stock.color_sizes[0];
                        size = first.size ? (first.size.size_name_ar || first.size.size_name_en || '-') : '-';
                        color = first.color ? (first.color.color_name_ar || first.color.color_name_en || '-') : '-';
                        quantity = stock.color_sizes.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
                    }
                    const categoryName = stock.category ? stock.category.category_name : '-';
                    let quantityStatus = quantity === 0 ? 'out_of_stock' : (quantity <= 5 ? 'low' : 'available');
                    const formattedSalesPrice = stock.sales_price ? parseFloat(stock.sales_price).toFixed(2) + ' OMR' : '-';

                    tableRows += `<tr class="border-t hover:bg-pink-50/60 transition" data-id="${stock.id}" data-quantity-status="${quantityStatus}">
                        <td class="px-3 sm:px-4 md:px-6 py-3 text-center">
                            <div class="flex items-start justify-center gap-3">
                                <img src="${image}" class="w-12 h-16 object-cover rounded-md flex-shrink-0" />
                                <div class="flex flex-col items-start text-left min-w-0 flex-1">
                                    <span class="font-bold break-words">${stock.design_name ?? '-'}</span>
                                    ${categoryName !== '-' ? `<span class="text-sm text-gray-600">(${categoryName})</span>` : ''}
                                </div>
                            </div>
                        </td>
                        <td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap font-medium">${stock.abaya_code || '-'}</td>
                        <td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap">${color}</td>
                        <td class="px-3 sm:px-4 md:px-6 py-3 text-center font-bold whitespace-nowrap">${quantity}</td>
                        <td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap font-semibold text-[var(--primary-color)]">${formattedSalesPrice}</td>
                        <td class="px-3 sm:px-4 md:px-6 py-3 text-center whitespace-nowrap">
                            <div class="flex justify-center gap-2 text-[12px] font-semibold text-gray-700">
                                <button class="flex flex-col items-center gap-1 hover:text-purple-600 transition" onclick="openFullStockDetails(${stock.id})">
                                    <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>${trans.details}
                                </button>
                                <button class="openQuantityBtn flex flex-col items-center gap-1 hover:text-green-600 transition" onclick="openStockQuantity(${stock.id})">
                                    <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>${trans.enter_quantity}
                                </button>
                                <button class="flex flex-col items-center gap-1 hover:text-blue-600 transition" onclick="window.location.href='{{ url('edit_stock') }}/${stock.id}?page=' + (currentStockPage || 1)">
                                    <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>${trans.edit}
                                </button>
                                <button class="flex flex-col items-center gap-1 hover:text-red-600 transition delete-stock-btn">
                                    <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>${trans.delete}
                                </button>
                            </div>
                        </td>
                    </tr>`;
                });
                $("#desktop_stock_body").html(tableRows);

                let mobileCards = '';
                $.each(res.data, function(index, stock) {
                    let image = stock.images && stock.images.length ? stock.images[0].image_path : '';
                    let size = '-', color = '-', quantity = 0;
                    if (stock.color_sizes && stock.color_sizes.length > 0) {
                        const first = stock.color_sizes[0];
                        color = first.color ? (first.color.color_name_ar || first.color.color_name_en || '-') : '-';
                        quantity = stock.color_sizes.reduce((sum, item) => sum + (parseInt(item.qty) || 0), 0);
                    }
                    const categoryName = stock.category ? stock.category.category_name : '-';
                    let quantityStatusMobile = quantity === 0 ? 'out_of_stock' : (quantity <= 5 ? 'low' : 'available');
                    const formattedSalesPriceMobile = stock.sales_price ? parseFloat(stock.sales_price).toFixed(2) + ' OMR' : '-';

                    mobileCards += `<div class="bg-white rounded-xl shadow-sm border border-pink-100 p-4 flex flex-col gap-3" data-id="${stock.id}" data-quantity-status="${quantityStatusMobile}">
                        <div class="flex gap-4">
                            <div class="w-20 h-24 rounded-md overflow-hidden bg-gray-100 flex-shrink-0">
                                <img src="${image}" alt="${stock.abaya_code || trans.design}" class="w-full h-full object-cover" onerror="this.src='/images/placeholder.png'" />
                            </div>
                            <div class="flex-1 text-sm">
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="font-bold text-gray-900">${stock.abaya_code || '-'}</h3>
                                    <span class="text-[var(--primary-color)] font-semibold text-xs">${formattedSalesPriceMobile}</span>
                                </div>
                                <p class="text-gray-600 text-xs">${trans.design}: ${stock.design_name ?? '-'}</p>
                                ${categoryName !== '-' ? `<p class="text-gray-600 text-xs">${trans.category}: ${categoryName}</p>` : ''}
                                <p class="text-gray-600 text-xs">${trans.color}: ${color}</p>
                                <p class="text-gray-600 text-xs font-semibold">${trans.quantity}: ${quantity}</p>
                            </div>
                        </div>
                        <div class="mt-4 border-t pt-3">
                            <div class="flex justify-around text-xs font-semibold text-gray-600">
                                <button class="flex flex-col items-center gap-1 hover:text-[var(--primary-color)] transition" onclick="openFullStockDetails(${stock.id})">
                                    <span class="material-symbols-outlined bg-pink-50 text-[var(--primary-color)] p-2 rounded-full text-base">info</span>${trans.details}
                                </button>
                                <button class="openQuantityBtn flex flex-col items-center gap-1 hover:text-green-600 transition" onclick="openStockQuantity(${stock.id})">
                                    <span class="material-symbols-outlined bg-green-50 text-green-600 p-2 rounded-full text-base">add</span>${trans.enter_quantity}
                                </button>
                                <button class="flex flex-col items-center gap-1 hover:text-blue-500 transition" onclick="window.location.href='{{ url('edit_stock') }}/${stock.id}?page=' + (currentStockPage || 1)">
                                    <span class="material-symbols-outlined bg-blue-50 text-blue-500 p-2 rounded-full text-base">edit</span>${trans.edit}
                                </button>
                                <button class="flex flex-col items-center gap-1 hover:text-red-500 transition delete-stock-btn-mobile" data-stock-id="${stock.id}">
                                    <span class="material-symbols-outlined bg-red-50 text-red-500 p-2 rounded-full text-base">delete</span>${trans.delete}
                                </button>
                            </div>
                        </div>
                    </div>`;
                });
                $("#mobile_stock_cards").html(mobileCards);

                let pagination = "";
                const cur = res.current_page;
                const last = res.last_page;
                const windowSize = 2;
                const btn = (href, label, active, disabled) => {
                    const base = "inline-flex items-center justify-center min-w-[2.25rem] px-2 py-1.5 text-sm font-medium border rounded-lg transition shrink-0 ";
                    const activeCls = active ? "bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md" : "bg-white hover:bg-gray-100 border-gray-200";
                    const disCls = disabled ? "opacity-40 pointer-events-none bg-gray-200 border-gray-200" : "";
                    return `<li class="shrink-0"><a href="${disabled ? '#' : (href || '#')}" class="${base} ${disabled ? disCls : activeCls}">${label}</a></li>`;
                };
                pagination += btn(res.prev_page_url, "&laquo; Prev", false, !res.prev_page_url);
                if (last <= 7) {
                    for (let i = 1; i <= last; i++) {
                        pagination += btn("{{ url('stock/list') }}?page=" + i, i, cur === i, false);
                    }
                } else {
                    const start = Math.max(1, cur - windowSize);
                    const end = Math.min(last, cur + windowSize);
                    if (cur > windowSize + 2) { pagination += btn("{{ url('stock/list') }}?page=1", "1", cur === 1, false); pagination += '<li class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</li>'; }
                    for (let i = start; i <= end; i++) { pagination += btn("{{ url('stock/list') }}?page=" + i, i, cur === i, false); }
                    if (cur < last - windowSize - 1) { pagination += '<li class="shrink-0 px-1 py-1.5 text-gray-400 text-sm">...</li>'; pagination += btn("{{ url('stock/list') }}?page=" + last, last, cur === last, false); }
                }
                pagination += btn(res.next_page_url, "Next &raquo;", false, !res.next_page_url);
                $("#stock_pagination").html(pagination);

                if (typeof applyFilters === 'function') setTimeout(applyFilters, 100);
            })
            .always(function() { $("#stock_pagination_loader").hide(); });
    }

    function applyFilters() {
        let search = $("#stock_search").val().toLowerCase();
        let filterValue = $("#stock_filter").val();
        $("#desktop_stock_body tr").each(function() {
            let $row = $(this);
            let matchesSearch = search === '' || $row.text().toLowerCase().indexOf(search) > -1;
            let matchesFilter = filterValue === 'all' || ($row.data('quantity-status') || '') === filterValue;
            $row.toggle(matchesSearch && matchesFilter);
        });
        $("#mobile_stock_cards > div").each(function() {
            let $card = $(this);
            let matchesSearch = search === '' || $card.text().toLowerCase().indexOf(search) > -1;
            let matchesFilter = filterValue === 'all' || ($card.data('quantity-status') || '') === filterValue;
            $card.toggle(matchesSearch && matchesFilter);
        });
    }

    function stockDetails() {
        return {
            loading: false,
            showDetails: false,
            stock: null,
            openStockDetails(id) {
                this.loading = true; this.showDetails = true; this.stock = null;
                $.ajax({ url: '{{ url("stock_detail") }}', method: 'GET', data: { id: id },
                    success: (response) => {
                        if (response) {
                            this.stock = response;
                            $('#abaya_code').text(this.stock.abaya_code || '-');
                            $('#design_name').text(this.stock.design_name || '-');
                            $('#barcode').text(this.stock.barcode || '-');
                            $('#description').text(this.stock.abaya_notes || '-');
                            $('#size_color_container').html(this.stock.size_color_html || '-');
                            $('#color_container').html(this.stock.color || '-');
                            $('#status').text(this.stock.status || 'Available');
                            $('#stock_main_image').attr('src', this.stock.image_path || '/images/placeholder.png');
                        }
                        this.loading = false;
                    },
                    error: (err) => { console.error('Error:', err); alert(trans.failed_to_load_details); this.loading = false; this.showDetails = false; }
                });
            }
        };
    }

    function openStockQuantity(stockId) {
        const mainElement = document.querySelector('main[x-data]');
        if (!mainElement || !mainElement._x_dataStack || !mainElement._x_dataStack[0]) return;
        let alpineData = mainElement._x_dataStack[0];
        $('#stock_id').val(stockId);
        $('#save_qty')[0].reset();
        $('#stock_id').val(stockId);
        alpineData.actionType = 'add';
        var submitBtn = $('#save_qty').find('button[type="submit"]');
        submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
        alpineData.showQuantity = true;

        $.ajax({
            url: '{{ url("get_stock_quantity") }}',
            method: 'GET',
            data: { id: stockId },
            success: function(response) {
                if (response) {
                    $('#sizecont').html(response.sizes_html || '');
                    $('#colorsize_container').html(response.size_color_html || '');
                    $('#colorcont').html(response.color || '');
                    setTimeout(() => {
                        if (alpineData && typeof alpineData.updateQuantityInputs === 'function') alpineData.updateQuantityInputs();
                    }, 100);
                }
            },
            error: function(err) {
                console.error('Error:', err);
                alert(trans.failed_to_load_details);
                alpineData.showQuantity = false;
            }
        });
    }

    $(document).on('click', '.delete-stock-btn', function() { deleteStock($(this).closest('tr').data('id')); });
    $(document).on('click', '.delete-stock-btn-mobile', function() { deleteStock($(this).data('stock-id')); });

    function deleteStock(id) {
        Swal.fire({
            title: '{{ trans("messages.confirm_delete_title", [], session("locale")) }}',
            text: '{{ trans("messages.confirm_delete_text", [], session("locale")) }}',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: '{{ trans("messages.yes_delete", [], session("locale")) }}',
            cancelButtonText: '{{ trans("messages.cancel", [], session("locale")) }}'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ url("delete_stock") }}/' + id,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(data) {
                        loadStock(currentStockPage);
                        Swal.fire('{{ trans("messages.deleted_success", [], session("locale")) }}', '{{ trans("messages.deleted_success_text", [], session("locale")) }}', 'success');
                    },
                    error: function() {
                        Swal.fire('{{ trans("messages.delete_error", [], session("locale")) }}', '{{ trans("messages.delete_error_text", [], session("locale")) }}', 'error');
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        $(document).on("click", "#stock_pagination a", function(e) {
            e.preventDefault();
            let href = $(this).attr("href");
            if (href && href !== "#") {
                let page = new URL(href, window.location.origin).searchParams.get("page");
                if (page) { $("#stock_pagination_loader").show(); loadStock(page); }
            }
        });
        $("#stock_search").on("keyup", applyFilters);
        $("#stock_filter").on("change", applyFilters);
        var startPage = 1;
        var urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('page')) {
            var p = parseInt(urlParams.get('page'), 10);
            if (!isNaN(p) && p >= 1) startPage = p;
        }
        loadStock(startPage);
    });

    $(document).ready(function() {
        $('#save_qty').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            const mainElement = document.querySelector('main[x-data]');
            if (!mainElement || !mainElement._x_dataStack || !mainElement._x_dataStack[0]) return false;
            const alpineData = mainElement._x_dataStack[0];
            const actionType = alpineData.actionType || $('input[name="qtyType"]:checked').val();
            const pullReason = $('#pull_reason').val() ? $('#pull_reason').val().trim() : '';

            if (actionType === 'pull' && pullReason === '') {
                show_notification('error', '{{ trans("messages.please_enter_pull_notes", [], session("locale")) ?: "Please enter pull notes" }}');
                return false;
            }

            let hasQuantity = false;
            let validationError = null;
            $('#colorsize_container input[name="size_color_qty[]"], #sizecont input[name="size_qty[]"], #colorcont input[name="color_qty[]"]').each(function() {
                const $input = $(this);
                const value = parseFloat($input.val());
                if (!isNaN(value) && $input.val() !== '') {
                    hasQuantity = true;
                    if (actionType === 'pull') {
                        const availableQty = parseFloat($input.data('available-qty')) || 0;
                        if (value > availableQty) { validationError = '{{ trans("messages.pull_quantity_exceeds_available", [], session("locale")) ?: "Pull quantity cannot exceed available" }}'; return false; }
                        if (value <= 0) { validationError = '{{ trans("messages.pull_quantity_must_be_positive", [], session("locale")) ?: "Pull quantity must be greater than 0" }}'; return false; }
                    }
                }
            });
            if (validationError) { show_notification('error', validationError); return false; }
            if (!hasQuantity) { show_notification('error', '{{ trans("messages.please_enter_quantity", [], session("locale")) ?: "Please enter at least one quantity" }}'); return false; }

            submitBtn.prop('disabled', true).html('<span class="material-symbols-outlined align-middle me-2 text-sm animate-spin">hourglass_empty</span>' + trans.saving);
            var formData = form.serialize() + '&stock_id=' + $('#stock_id').val() + '&qtyType=' + actionType;

            $.ajax({
                url: "{{ route('add_quantity') }}",
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.status === "success") {
                        submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
                        Swal.fire({ icon: 'success', title: trans.success_title, text: response.message, timer: 2000, showConfirmButton: false });
                        if (typeof loadStock === 'function') loadStock(currentStockPage);
                        setTimeout(() => { alpineData.showQuantity = false; }, 500);
                    } else {
                        Swal.fire({ icon: 'error', title: trans.error_title, text: response.message || trans.error_occurred });
                        submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
                    }
                },
                error: function(xhr) {
                    var errorMsg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : trans.error_saving;
                    Swal.fire({ icon: 'error', title: trans.error_title, text: errorMsg });
                    submitBtn.prop('disabled', false).html('<span class="material-symbols-outlined align-middle me-2 text-sm">check</span>{{ trans("messages.save_operation", [], session("locale")) }}');
                }
            });
            return false;
        });
    });

    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    function openFullStockDetails(stockId) {
        const mainElement = document.querySelector('main[x-data]');
        if (!mainElement || !mainElement._x_dataStack || !mainElement._x_dataStack[0]) return;
        let alpineData = mainElement._x_dataStack[0];
        $('#full_modal_abaya_code, #full_abaya_code, #full_design_name, #full_barcode, #full_description, #full_cost_price, #full_sales_price, #full_tailor_charges, #full_tailor_names').text('-');
        $('#full_total_quantity').text('0');
        $('#full_stock_images_container, #full_size_color_container').html('');
        alpineData.showFullDetails = true;
        alpineData.fullDetailsLoading = true;

        $.ajax({
            url: '{{ url("get_full_stock_details") }}',
            method: 'GET',
            data: { id: stockId },
            success: function(response) {
                if (response) {
                    $('#full_abaya_code').text(response.abaya_code || '-');
                    $('#full_modal_abaya_code').text(response.abaya_code || '...');
                    $('#full_design_name').text(response.design_name || '-');
                    $('#full_barcode').text(response.barcode || '-');
                    $('#full_description').text(response.abaya_notes || '-');
                    $('#full_cost_price').text(response.cost_price ? parseFloat(response.cost_price).toFixed(2) : '0.00');
                    $('#full_sales_price').text(response.sales_price ? parseFloat(response.sales_price).toFixed(2) : '0.00');
                    $('#full_tailor_charges').text(response.tailor_charges ? parseFloat(response.tailor_charges).toFixed(2) : '0.00');
                    $('#full_tailor_names').text('-');
                    $('#full_total_quantity').text(response.total_quantity || 0);
                    if (response.images && response.images.length > 0) {
                        let imagesHtml = '';
                        response.images.forEach((imagePath, index) => {
                            imagesHtml += `<div class="rounded-xl overflow-hidden shadow-sm"><div class="relative" style="height: 200px; overflow: hidden;"><img src="${imagePath}" class="w-full h-full object-cover" alt="${trans.abaya_image} ${index + 1}" onerror="this.src='/images/placeholder.png'"></div></div>`;
                        });
                        $('#full_stock_images_container').html(imagesHtml);
                    } else {
                        $('#full_stock_images_container').html('<p class="text-gray-500 text-center">{{ trans("messages.no_images_available", [], session("locale")) }}</p>');
                    }
                    if (response.color_size_details && response.color_size_details.length > 0) {
                        let colorSizeHtml = '';
                        response.color_size_details.forEach(item => {
                            colorSizeHtml += `<div class="bg-white rounded-xl shadow-sm p-4 border border-gray-100"><div class="flex justify-between items-start mb-2"><div><h6 class="font-bold text-gray-900 mb-1">${item.size_name}</h6><div class="flex items-center gap-2"><div class="rounded-full border-2 border-gray-300" style="width: 24px; height: 24px; background-color: ${item.color_code};"></div><span class="text-gray-600 text-sm">${item.color_name}</span></div></div></div><div class="text-right mt-3"><span class="bg-[var(--primary-color)] text-white rounded-full px-4 py-2 text-sm font-semibold">${item.quantity} ${trans.pieces}</span></div></div>`;
                        });
                        $('#full_size_color_container').html(colorSizeHtml);
                    } else {
                        $('#full_size_color_container').html('<p class="text-gray-500 text-center">{{ trans("messages.no_data_available", [], session("locale")) }}</p>');
                    }
                }
                alpineData.fullDetailsLoading = false;
            },
            error: function(err) {
                console.error('Error:', err);
                alert('{{ trans("messages.error_loading_details", [], session("locale")) }}');
                alpineData.fullDetailsLoading = false;
            }
        });
    }
</script>
