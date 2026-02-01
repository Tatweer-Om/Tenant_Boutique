<script>
    // Global variables to store state
    let currentPage = 1;

    // Load report data
    function loadReport(page = 1) {
        currentPage = page;
        var fromDate = $('#fromDate').val();
        var toDate = $('#toDate').val();
        var boutiqueId = $('#boutiqueSelect').val();
        
        // Show loading state
        $('#settlementsTableBody').html(`
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                    <div class="flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined animate-spin">refresh</span>
                    @if(session('locale') == 'ar')
                        جاري التحميل...
                    @else
                        Loading...
                    @endif
                    </div>
                </td>
            </tr>
        `);
        
        $.ajax({
            url: "{{ route('reports.settlement_profit.data') }}",
            type: "GET",
            data: { 
                from_date: fromDate,
                to_date: toDate,
                boutique_id: boutiqueId,
                page: page
            },
            success: function(res) {
                if (res.success && res.settlements && res.settlements.length > 0) {
                    let rows = '';
                    res.settlements.forEach(function(settlement) {
                        rows += `
                            <tr class="hover:bg-pink-50/50 transition-colors border-b">
                                <td class="px-4 py-4 font-semibold text-[var(--text-primary)] whitespace-nowrap">${settlement.operation_number}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${settlement.month}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] whitespace-nowrap">${settlement.boutique}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${settlement.number_of_items}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${formatNumber(settlement.sales)}</td>
                                <td class="px-4 py-4 text-[var(--text-primary)] text-center whitespace-nowrap">${formatNumber(settlement.profit)}</td>
                            </tr>
                        `;
                    });
                    $('#settlementsTableBody').html(rows);
                    
                    // Update summary cards
                    if (res.totals) {
                        $('#totalItems').text(res.totals.number_of_items || 0);
                        $('#totalSales').text(formatNumber(res.totals.sales));
                        $('#totalProfit').text(formatNumber(res.totals.profit));
                        $('#summaryCards').show();
                    }
                    
                    // Render pagination
                    if (res.last_page && res.last_page > 1) {
                        renderPagination(res.current_page || 1, res.last_page);
                    } else if (res.total && res.total > res.per_page) {
                        renderPagination(res.current_page || 1, Math.ceil(res.total / res.per_page));
                    } else {
                        $('#pagination').html('');
                    }
                } else {
                    $('#settlementsTableBody').html(`
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            @if(session('locale') == 'ar')
                                لا توجد بيانات
                            @else
                                No data found
                            @endif
                            </td>
                        </tr>
                    `);
                    $('#pagination').html('');
                    $('#summaryCards').hide();
                }
            },
            error: function() {
                $('#settlementsTableBody').html(`
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-red-500">
                        @if(session('locale') == 'ar')
                            خطأ في تحميل البيانات
                        @else
                            Error loading data
                        @endif
                        </td>
                    </tr>
                `);
                $('#pagination').html('');
                $('#summaryCards').hide();
            }
        });
    }

    // Format number with 3 decimal places
    function formatNumber(num) {
        return parseFloat(num || 0).toFixed(3);
    }

    // Render pagination
    function renderPagination(currentPage, lastPage) {
        if (!lastPage || lastPage <= 1) {
            $('#pagination').html('');
            return;
        }

        currentPage = parseInt(currentPage) || 1;
        lastPage = parseInt(lastPage) || 1;

        let pagination = '';
        
        // Previous button
        pagination += `
            <li class="flex">
                <a href="#" data-page="${currentPage - 1}"
                   class="px-3 py-1 border rounded-lg mx-1 text-sm transition
                          ${currentPage == 1 
                            ? 'opacity-40 cursor-not-allowed bg-gray-200 pointer-events-none' 
                            : 'bg-white hover:bg-gray-100 cursor-pointer'}"
                   ${currentPage == 1 ? 'tabindex="-1" aria-disabled="true"' : ''}>
                    &laquo;
                </a>
            </li>
        `;

        if (lastPage <= 10) {
            for (let i = 1; i <= lastPage; i++) {
                pagination += `
                    <li>
                        <a href="#" data-page="${i}"
                           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition cursor-pointer
                                  ${currentPage == i 
                                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                    : 'bg-white hover:bg-gray-100'}">
                            ${i}
                        </a>
                    </li>
                `;
            }
        } else {
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(lastPage, currentPage + 2);
            
            if (startPage <= 3) {
                startPage = 1;
                endPage = Math.min(5, lastPage);
            } else if (endPage >= lastPage - 2) {
                endPage = lastPage;
                startPage = Math.max(1, lastPage - 4);
            }

            if (startPage > 1) {
                pagination += `
                    <li>
                        <a href="#" data-page="1"
                           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition cursor-pointer
                                  ${currentPage == 1 
                                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                    : 'bg-white hover:bg-gray-100'}">
                            1
                        </a>
                    </li>
                `;
                
                if (startPage > 2) {
                    pagination += `
                        <li class="px-4 py-1 mx-1 text-sm text-gray-500 pointer-events-none">
                            ...
                        </li>
                    `;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                pagination += `
                    <li>
                        <a href="#" data-page="${i}"
                           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition cursor-pointer
                                  ${currentPage == i 
                                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                    : 'bg-white hover:bg-gray-100'}">
                            ${i}
                        </a>
                    </li>
                `;
            }

            if (endPage < lastPage) {
                if (endPage < lastPage - 1) {
                    pagination += `
                        <li class="px-4 py-1 mx-1 text-sm text-gray-500 pointer-events-none">
                            ...
                        </li>
                    `;
                }
                
                pagination += `
                    <li>
                        <a href="#" data-page="${lastPage}"
                           class="px-4 py-1 mx-1 text-sm font-medium border rounded-lg transition cursor-pointer
                                  ${currentPage == lastPage 
                                    ? 'bg-[var(--primary-color)] text-white border-[var(--primary-color)] shadow-md' 
                                    : 'bg-white hover:bg-gray-100'}">
                            ${lastPage}
                        </a>
                    </li>
                `;
            }
        }

        // Next button
        pagination += `
            <li class="flex">
                <a href="#" data-page="${currentPage + 1}"
                   class="px-3 py-1 border rounded-lg mx-1 text-sm transition
                          ${currentPage == lastPage 
                            ? 'opacity-40 cursor-not-allowed bg-gray-200 pointer-events-none' 
                            : 'bg-white hover:bg-gray-100 cursor-pointer'}"
                   ${currentPage == lastPage ? 'tabindex="-1" aria-disabled="true"' : ''}>
                    &raquo;
                </a>
            </li>
        `;

        $('#pagination').html(pagination);
    }

    $(document).ready(function() {
        // Set default dates to today if not already set
        var today = new Date().toISOString().split('T')[0];
        if (!$('#fromDate').val()) {
            $('#fromDate').val(today);
        }
        if (!$('#toDate').val()) {
            $('#toDate').val(today);
        }
        
        // Load report automatically on page load with current date
        loadReport(1);
        
        // Handle filter button click
        $('#filterBtn').on('click', function() {
            currentPage = 1;
            loadReport(currentPage);
        });

        // Handle Enter key on date inputs
        $('#fromDate, #toDate').on('keypress', function(e) {
            if (e.which === 13) {
                currentPage = 1;
                loadReport(currentPage);
            }
        });

        // Handle boutique selection change
        $('#boutiqueSelect').on('change', function() {
            currentPage = 1;
            loadReport(currentPage);
        });

        // Handle pagination click - use event delegation for dynamically rendered links
        $(document).off('click.reportPagination', '#pagination a[data-page]').on('click.reportPagination', '#pagination a[data-page]', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            let clickedElement = $(this);
            // Skip if link is disabled (prev/next on first/last page)
            if (clickedElement.hasClass('pointer-events-none') || clickedElement.hasClass('opacity-40') || clickedElement.attr('aria-disabled') === 'true') {
                return false;
            }
            
            let page = parseInt(clickedElement.data('page'), 10);
            if (!page || page <= 0) {
                return false;
            }
            
            currentPage = page;
            loadReport(page);
            return false;
        });

        // Export PDF
        $('#exportPdfBtn').on('click', function() {
            var fromDate = $('#fromDate').val();
            var toDate = $('#toDate').val();
            var boutiqueId = $('#boutiqueSelect').val();
            var params = '';
            if (fromDate) params += 'from_date=' + fromDate;
            if (toDate) {
                if (params) params += '&';
                params += 'to_date=' + toDate;
            }
            if (boutiqueId) {
                if (params) params += '&';
                params += 'boutique_id=' + boutiqueId;
            }
            var url = "{{ route('reports.settlement_profit.export_pdf') }}";
            if (params) url += '?' + params;
            window.open(url, '_blank');
        });

        // Export Excel
        $('#exportExcelBtn').on('click', function() {
            var fromDate = $('#fromDate').val();
            var toDate = $('#toDate').val();
            var boutiqueId = $('#boutiqueSelect').val();
            var params = '';
            if (fromDate) params += 'from_date=' + fromDate;
            if (toDate) {
                if (params) params += '&';
                params += 'to_date=' + toDate;
            }
            if (boutiqueId) {
                if (params) params += '&';
                params += 'boutique_id=' + boutiqueId;
            }
            var url = "{{ route('reports.settlement_profit.export_excel') }}";
            if (params) url += '?' + params;
            window.location.href = url;
        });
    });
</script>
