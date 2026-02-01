<script>
    let currentPendingPage = 1;
    let currentHistoryPage = 1;
    let accountsData = [];
    let pendingFromDate = '';
    let pendingToDate = '';

    // Get Alpine.js data
    function getAlpineData() {
        const mainElement = document.querySelector('main[x-data]');
        if (!mainElement || !mainElement._x_dataStack || !mainElement._x_dataStack[0]) {
            return null;
        }
        return mainElement._x_dataStack[0];
    }

    // Load accounts
    function loadAccounts() {
        $.ajax({
            url: '{{ route("tailor_payments.accounts") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    accountsData = response.accounts;
                    populateAccountDropdown(response.accounts);
                }
            },
            error: function(xhr) {
                console.error('Error loading accounts:', xhr);
            }
        });
    }

    // Populate account dropdown
    function populateAccountDropdown(accounts) {
        const select = $('#account_id');
        select.find('option:not(:first)').remove();
        
        accounts.forEach(function(account) {
            const balance = parseFloat(account.balance).toFixed(3);
            const displayText = `${account.name}${account.branch ? ' (' + account.branch + ')' : ''} - ${balance}`;
            select.append(`
                <option value="${account.id}" 
                        data-balance="${account.balance}"
                        data-name="${account.name}">
                    ${displayText}
                </option>
            `);
        });
    }

    // Update account balance info when account is selected
    function updateAccountBalanceInfo() {
        const accountId = $('#account_id').val();
        const account = accountsData.find(a => a.id == accountId);
        
        if (account) {
            const balance = parseFloat(account.balance).toFixed(3);
            const balanceClass = account.balance >= 0 ? 'text-green-600' : 'text-red-600';
            const totalAmount = parseFloat($('#total_amount_display').val()) || 0;
            
            let html = `<span class="${balanceClass} font-semibold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">account_balance</span>
                {{ trans('messages.available_balance', [], session('locale')) ?: 'Available Balance' }}: ${balance}
            </span>`;
            
            // Check if balance is sufficient
            if (totalAmount > 0) {
                if (account.balance < totalAmount) {
                    html += `
                        <span class="text-red-600 block mt-1 flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">warning</span>
                            Insufficient balance! Required: ${totalAmount.toFixed(3)}
                        </span>
                    `;
                } else {
                    const remaining = account.balance - totalAmount;
                    html += `
                        <span class="text-blue-600 block mt-1 text-xs">
                            After payment: ${remaining.toFixed(3)} will remain
                        </span>
                    `;
                }
            }
            
            $('#account_balance_info').html(html);
        } else {
            $('#account_balance_info').html('');
        }
    }

    // Load pending payments
    function loadPendingPayments(page = 1) {
        currentPendingPage = page;
        const alpineData = getAlpineData();
        if (alpineData) {
            alpineData.loading = true;
        }

        $.ajax({
            url: '{{ route("tailor_payments.pending") }}',
            method: 'GET',
            data: { 
                page: page,
                from_date: pendingFromDate || '',
                to_date: pendingToDate || ''
            },
            success: function(response) {
                if (response.success) {
                    renderPendingPayments(response.data);
                    renderPendingPagination(response);
                    updateTotalAmount();
                } else {
                    show_notification('error', response.message || 'Error loading pending payments');
                }
                if (alpineData) {
                    alpineData.loading = false;
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                show_notification('error', 'Error loading pending payments');
                if (alpineData) {
                    alpineData.loading = false;
                }
            }
        });
    }

    // Render pending payments table - each row is an individual entry
    function renderPendingPayments(data) {
        const tbody = $('#pending_payments_body');
        if (data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="10" class="px-4 py-8 text-center text-gray-500">
                        {{ trans('messages.no_pending_payments', [], session('locale')) ?: 'No pending payments' }}
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        data.forEach(function(item) {
            // Each item is now an individual entry (stock history or special order item)
            const itemKey = item.id; // Use unique ID from backend
            const sourceClass = item.type === 'stock' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';
            const sourceIcon = item.type === 'stock' ? 'inventory_2' : 'shopping_bag';
            
            html += `
                <tr class="border-t hover:bg-pink-50/60 transition">
                    <td class="px-4 py-3 text-center">
                        <input type="checkbox" 
                               class="pending-checkbox w-4 h-4 text-[var(--primary-color)] rounded"
                               data-key="${itemKey}"
                               data-id="${itemKey}"
                               data-stock-history-id="${item.stock_history_id || ''}"
                               data-special-order-item-id="${item.special_order_item_id || ''}"
                               data-type="${item.type}"
                               data-abaya-code="${item.abaya_code}"
                               data-tailor-id="${item.tailor_id}"
                               data-quantity="${item.quantity}"
                               data-unit-charge="${item.unit_charge}"
                               data-total-charge="${item.total_charge}"
                               onchange="toggleItemSelection(this)">
                    </td>
                    <td class="px-4 py-3 text-center font-semibold text-indigo-600">${item.order_no || '—'}</td>
                    <td class="px-4 py-3 text-center">${item.abaya_name || item.abaya_code || '—'}</td>
                    <td class="px-4 py-3 text-center">${item.tailor_name || '—'}</td>
                    <td class="px-4 py-3 text-center font-bold">${item.quantity || 0}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${sourceClass} flex items-center justify-center gap-1 w-fit mx-auto">
                            <span class="material-symbols-outlined text-xs">${sourceIcon}</span>
                            ${item.type === 'stock' ? 'Stock' : 'Special Order'}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">${parseFloat(item.unit_charge || 0).toFixed(3)}</td>
                    <td class="px-4 py-3 text-center font-bold text-green-600">${parseFloat(item.total_charge || 0).toFixed(3)}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex flex-col items-center gap-1">
                            <span class="px-2 py-1 rounded-full text-xs font-semibold ${sourceClass}">
                                ${item.source || (item.type === 'stock' ? 'Stock Addition' : 'Special Order')}
                            </span>
                            ${item.date ? `<span class="text-xs text-gray-500">${item.date}</span>` : ''}
                        </div>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="showDetailsModalFromRow(this)" 
                                data-item='${JSON.stringify(item)}'
                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold flex items-center gap-1 mx-auto transition">
                            <span class="material-symbols-outlined text-base">visibility</span>
                            {{ trans('messages.details', [], session('locale')) ?: 'Details' }}
                        </button>
                    </td>
                </tr>
            `;
        });
        tbody.html(html);
    }

    // Render pagination
    function renderPendingPagination(response) {
        const pagination = $('#pending_pagination');
        let html = '<div class="flex justify-center items-center gap-2">';

        // Previous
        html += `
            <button onclick="loadPendingPayments(${response.current_page - 1})"
                    ${response.current_page === 1 ? 'disabled' : ''}
                    class="px-3 py-1 rounded-lg border ${response.current_page === 1 ? 'opacity-40 cursor-not-allowed bg-gray-200' : 'bg-white hover:bg-gray-100'}">
                &laquo;
            </button>
        `;

        // Page numbers
        for (let i = 1; i <= response.last_page; i++) {
            html += `
                <button onclick="loadPendingPayments(${i})"
                        class="px-4 py-1 rounded-lg border ${response.current_page === i ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white hover:bg-gray-100'}">
                    ${i}
                </button>
            `;
        }

        // Next
        html += `
            <button onclick="loadPendingPayments(${response.current_page + 1})"
                    ${response.current_page === response.last_page ? 'disabled' : ''}
                    class="px-3 py-1 rounded-lg border ${response.current_page === response.last_page ? 'opacity-40 cursor-not-allowed bg-gray-200' : 'bg-white hover:bg-gray-100'}">
                &raquo;
            </button>
        `;

        html += '</div>';
        pagination.html(html);
    }

    // Toggle item selection - now handles individual entries
    function toggleItemSelection(checkbox) {
        const alpineData = getAlpineData();
        if (!alpineData) return;

        const itemId = checkbox.dataset.id; // Use unique ID
        const item = {
            id: itemId,
            stock_history_id: checkbox.dataset.stockHistoryId || null,
            special_order_item_id: checkbox.dataset.specialOrderItemId || null,
            type: checkbox.dataset.type,
            abaya_code: checkbox.dataset.abayaCode,
            tailor_id: parseInt(checkbox.dataset.tailorId),
            quantity: parseInt(checkbox.dataset.quantity),
            unit_charge: parseFloat(checkbox.dataset.unitCharge),
            total_charge: parseFloat(checkbox.dataset.totalCharge)
        };

        if (checkbox.checked) {
            // Check if already exists (by unique ID)
            const exists = alpineData.selectedItems.find(i => i.id === itemId);
            if (!exists) {
                alpineData.selectedItems.push(item);
            }
        } else {
            // Remove by unique ID
            alpineData.selectedItems = alpineData.selectedItems.filter(i => i.id !== itemId);
        }

        updateTotalAmount();
    }

    // Toggle all pending items
    function toggleAllPending(checked) {
        $('.pending-checkbox').each(function() {
            this.checked = checked;
            toggleItemSelection(this);
        });
    }

    // Update total amount display
    function updateTotalAmount() {
        const alpineData = getAlpineData();
        if (!alpineData) return;

        const total = alpineData.selectedItems.reduce((sum, item) => sum + item.total_charge, 0);
        $('#total_amount_display').val(total.toFixed(3));
        
        // Update account balance info if account is selected
        updateAccountBalanceInfo();
    }

    // Load payment history
    function loadPaymentHistory(page = 1) {
        currentHistoryPage = page;
        const alpineData = getAlpineData();
        if (alpineData) {
            alpineData.loading = true;
        }

        $.ajax({
            url: '{{ route("tailor_payments.history") }}',
            method: 'GET',
            data: { page: page },
            success: function(response) {
                if (response.success) {
                    renderPaymentHistory(response.data);
                    renderHistoryPagination(response);
                } else {
                    show_notification('error', response.message || 'Error loading payment history');
                }
                if (alpineData) {
                    alpineData.loading = false;
                }
            },
            error: function(xhr) {
                console.error('Error:', xhr);
                show_notification('error', 'Error loading payment history');
                if (alpineData) {
                    alpineData.loading = false;
                }
            }
        });
    }

    // Render payment history
    function renderPaymentHistory(data) {
        const tbody = $('#payment_history_body');
        if (data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="13" class="px-4 py-8 text-center text-gray-500">
                        {{ trans('messages.no_payment_history', [], session('locale')) ?: 'No payment history' }}
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        data.forEach(function(payment) {
            // If payment has multiple items, create a row for each item
            if (payment.items && payment.items.length > 0) {
                payment.items.forEach(function(item, itemIndex) {
                    const isFirstRow = itemIndex === 0;
                    const rowspan = payment.items.length;
                    const sourcesText = item.sources && item.sources.length > 0 ? [...new Set(item.sources)].join(', ') : 'N/A';
                    const sourceClass = sourcesText.includes('Stock Addition') ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700';
                    
                    html += `
                        <tr class="border-t hover:bg-pink-50/60 transition">
                            ${isFirstRow ? `
                                <td class="px-4 py-3 text-center align-middle" rowspan="${rowspan}">
                                    <span class="font-semibold text-gray-800">${payment.payment_date}</span>
                                </td>
                                <td class="px-4 py-3 text-center align-middle" rowspan="${rowspan}">
                                    <span class="text-gray-700">${payment.account_name || payment.payment_method || 'N/A'}</span>
                                </td>
                                <td class="px-4 py-3 text-center align-middle" rowspan="${rowspan}">
                                    <span class="font-bold text-green-600 text-lg">${parseFloat(payment.total_amount).toFixed(3)}</span>
                                </td>
                            ` : ''}
                            <td class="px-4 py-3 text-center">${item.tailor_name || 'N/A'}</td>
                            <td class="px-4 py-3 text-center font-semibold">${item.abaya_code || 'N/A'}</td>
                            <td class="px-4 py-3 text-center">${item.abaya_name || item.abaya_code || 'N/A'}</td>
                            <td class="px-4 py-3 text-center font-bold">${item.total_quantity || 0}</td>
                            <td class="px-4 py-3 text-center">${item.unit_charge ? parseFloat(item.unit_charge).toFixed(3) : '0.000'}</td>
                            <td class="px-4 py-3 text-center font-bold text-green-600">${parseFloat(item.total_charge || 0).toFixed(3)}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold ${sourceClass}">
                                    ${sourcesText}
                                </span>
                            </td>
                            ${isFirstRow ? `
                                <td class="px-4 py-3 text-center text-sm text-gray-600 align-middle" rowspan="${rowspan}">
                                    ${payment.notes ? payment.notes.substring(0, 50) + (payment.notes.length > 50 ? '...' : '') : '-'}
                                </td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600 align-middle" rowspan="${rowspan}">
                                    ${payment.added_by || 'N/A'}
                                </td>
                            ` : ''}
                            <td class="px-4 py-3 text-center">
                                <button onclick="showDetailsModalFromRow(this)" 
                                        data-item='${JSON.stringify(item)}'
                                        class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold flex items-center gap-1 mx-auto transition">
                                    <span class="material-symbols-outlined text-base">visibility</span>
                                    {{ trans('messages.details', [], session('locale')) ?: 'Details' }}
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                // If no items, show payment info only
                html += `
                    <tr class="border-t hover:bg-pink-50/60 transition">
                        <td class="px-4 py-3 text-center">
                            <span class="font-semibold text-gray-800">${payment.payment_date}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="text-gray-700">${payment.account_name || payment.payment_method || 'N/A'}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="font-bold text-green-600 text-lg">${parseFloat(payment.total_amount).toFixed(3)}</span>
                        </td>
                        <td class="px-4 py-3 text-center" colspan="7">-</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600">${payment.notes ? payment.notes.substring(0, 50) + (payment.notes.length > 50 ? '...' : '') : '-'}</td>
                        <td class="px-4 py-3 text-center text-sm text-gray-600">${payment.added_by || 'N/A'}</td>
                        <td class="px-4 py-3 text-center">-</td>
                    </tr>
                `;
            }
        });

        tbody.html(html);
    }

    // Render history pagination
    function renderHistoryPagination(response) {
        const pagination = $('#history_pagination');
        let html = '<div class="flex justify-center items-center gap-2">';

        // Previous
        html += `
            <button onclick="loadPaymentHistory(${response.current_page - 1})"
                    ${response.current_page === 1 ? 'disabled' : ''}
                    class="px-3 py-1 rounded-lg border ${response.current_page === 1 ? 'opacity-40 cursor-not-allowed bg-gray-200' : 'bg-white hover:bg-gray-100'}">
                &laquo;
            </button>
        `;

        // Page numbers
        for (let i = 1; i <= response.last_page; i++) {
            html += `
                <button onclick="loadPaymentHistory(${i})"
                        class="px-4 py-1 rounded-lg border ${response.current_page === i ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)]' : 'bg-white hover:bg-gray-100'}">
                    ${i}
                </button>
            `;
        }

        // Next
        html += `
            <button onclick="loadPaymentHistory(${response.current_page + 1})"
                    ${response.current_page === response.last_page ? 'disabled' : ''}
                    class="px-3 py-1 rounded-lg border ${response.current_page === response.last_page ? 'opacity-40 cursor-not-allowed bg-gray-200' : 'bg-white hover:bg-gray-100'}">
                &raquo;
            </button>
        `;

        html += '</div>';
        pagination.html(html);
    }

    // Process payment
    $(document).ready(function() {
        // Load initial data
        loadPendingPayments();
        loadPaymentHistory();
        loadAccounts();
        
        // Watch for account selection change
        $(document).on('change', '#account_id', function() {
            updateAccountBalanceInfo();
        });

        // Handle payment form submission
        $('#payment_form').on('submit', function(e) {
            e.preventDefault();

            const alpineData = getAlpineData();
            if (!alpineData || alpineData.selectedItems.length === 0) {
                show_notification('error', '{{ trans("messages.please_select_items", [], session("locale")) ?: "Please select items to pay" }}');
                return;
            }

            // Validate account is selected
            const accountId = $('#account_id').val();
            if (!accountId) {
                show_notification('error', '{{ trans("messages.please_select_account", [], session("locale")) ?: "Please select an account" }}');
                $('#account_id').focus();
                return;
            }

            // Validate account balance
            const totalAmount = parseFloat($('#total_amount_display').val()) || 0;
            const selectedAccount = accountsData.find(a => a.id == accountId);
            if (selectedAccount && selectedAccount.balance < totalAmount) {
                show_notification('error', 'Insufficient balance in selected account. Available: ' + parseFloat(selectedAccount.balance).toFixed(3) + ', Required: ' + totalAmount.toFixed(3));
                return;
            }

            const formData = {
                _token: '{{ csrf_token() }}',
                payment_date: $('#payment_date').val(),
                payment_method: 'account', // Changed to account since we're using account
                account_id: accountId,
                notes: $('#payment_notes').val(),
                selected_items: alpineData.selectedItems
            };

            $.ajax({
                url: '{{ route("tailor_payments.process") }}',
                method: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        show_notification('success', response.message || 'Payment processed successfully');
                        alpineData.selectedItems = [];
                        $('#account_id').val('').trigger('change');
                        $('#payment_notes').val('');
                        loadPendingPayments(currentPendingPage);
                        loadPaymentHistory(1);
                        loadAccounts(); // Reload accounts to update balances
                    } else {
                        show_notification('error', response.message || 'Error processing payment');
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    const message = xhr.responseJSON?.message || 'Error processing payment';
                    show_notification('error', message);
                }
            });
        });

        // Pending payments date filter
        $(document).on('click', '#pending_filter_btn', function() {
            pendingFromDate = ($('#pending_from_date').val() || '').trim();
            pendingToDate = ($('#pending_to_date').val() || '').trim();

            const alpineData = getAlpineData();
            if (alpineData) {
                alpineData.selectedItems = [];
            }

            // Reset total + reload page 1 with filters
            updateTotalAmount();
            loadPendingPayments(1);
        });

        $(document).on('click', '#pending_reset_btn', function() {
            pendingFromDate = '';
            pendingToDate = '';
            $('#pending_from_date').val('');
            $('#pending_to_date').val('');

            const alpineData = getAlpineData();
            if (alpineData) {
                alpineData.selectedItems = [];
            }

            updateTotalAmount();
            loadPendingPayments(1);
        });

        // Enter key triggers filter
        $(document).on('keydown', '#pending_from_date, #pending_to_date', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                $('#pending_filter_btn').trigger('click');
            }
        });

        // Watch for tab changes
        const mainElement = document.querySelector('main[x-data]');
        if (mainElement) {
            const observer = new MutationObserver(function() {
                const alpineData = getAlpineData();
                if (alpineData && alpineData.activeTab === 'pending') {
                    loadPendingPayments(currentPendingPage);
                } else if (alpineData && alpineData.activeTab === 'history') {
                    loadPaymentHistory(currentHistoryPage);
                }
            });

            observer.observe(mainElement, {
                attributes: true,
                attributeFilter: ['x-data']
            });
        }
    });

    // Show details modal from button click
    function showDetailsModalFromRow(button) {
        const itemData = button.getAttribute('data-item');
        if (!itemData) return;
        
        try {
            const item = JSON.parse(itemData);
            showDetailsModal(item);
        } catch (e) {
            console.error('Error parsing item data:', e);
        }
    }

    // Show details modal
    function showDetailsModal(item) {
        // Populate modal with item details
        document.getElementById('detail_order_no').textContent = item.order_no || '—';
        document.getElementById('detail_abaya_name').textContent = item.abaya_name || item.abaya_code || '—';
        document.getElementById('detail_abaya_code').textContent = item.abaya_code || '—';
        document.getElementById('detail_quantity').textContent = item.quantity || 0;
        document.getElementById('detail_tailor').textContent = item.tailor_name || '—';
        document.getElementById('detail_customer_name').textContent = item.customer_name || '—';
        document.getElementById('detail_customer_phone').textContent = item.customer_phone || '—';
        document.getElementById('detail_length').textContent = item.length || '—';
        document.getElementById('detail_bust').textContent = item.bust || '—';
        document.getElementById('detail_sleeves').textContent = item.sleeves || '—';
        document.getElementById('detail_buttons').textContent = item.buttons ? '{{ trans('messages.yes', [], session('locale')) ?: 'Yes' }}' : '{{ trans('messages.no', [], session('locale')) ?: 'No' }}';
        document.getElementById('detail_unit_charge').textContent = parseFloat(item.unit_charge || 0).toFixed(3);
        document.getElementById('detail_total_charge').textContent = parseFloat(item.total_charge || 0).toFixed(3);
        document.getElementById('detail_notes').textContent = item.notes || '—';
        
        // Set image
        const imageElement = document.getElementById('detail_abaya_image');
        if (item.abaya_image) {
            imageElement.src = item.abaya_image;
            imageElement.style.display = 'block';
        } else {
            imageElement.src = '{{ asset('images/default_abaya.jpg') }}';
            imageElement.style.display = 'block';
        }
        
        // Show modal
        document.getElementById('details_modal').classList.remove('hidden');
    }

    // Close details modal
    function closeDetailsModal() {
        document.getElementById('details_modal').classList.add('hidden');
    }

    // Close modal when clicking outside
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('details_modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeDetailsModal();
                }
            });
        }
    });
</script>
