<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale') === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('messages.special_orders_income_report', [], session('locale')) ?: 'Special Orders Income Report' }}</title>
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .date-range {
            margin: 10px 0;
            font-size: 14px;
            color: #666;
        }
        .summary {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 5px 0;
            border-bottom: 1px solid #ddd;
        }
        .summary-label {
            font-weight: bold;
            color: #333;
        }
        .summary-value {
            color: #8B5CF6;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: right;
        }
        th {
            background-color: #8B5CF6;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .totals-row {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ trans('messages.special_orders_income_report', [], session('locale')) ?: 'Special Orders Income Report' }}</h1>
        <div style="background-color: #E3F2FD; border-left: 4px solid #2196F3; padding: 10px; margin: 10px 0; border-radius: 4px;">
            <p style="margin: 0; color: #1565C0; font-weight: bold;">
                @if(session('locale') == 'ar')
                    هذه هي الطلبات التي تم تسليمها للعملاء
                @else
                    These are the orders delivered to customers
                @endif
            </p>
        </div>
        <div class="date-range">
            @if($fromDate || $toDate)
                @if(session('locale') == 'ar')
                    الفترة: {{ $fromDate ?: 'البداية' }} - {{ $toDate ?: 'النهاية' }}
                @else
                    Period: {{ $fromDate ?: 'Start' }} - {{ $toDate ?: 'End' }}
                @endif
            @else
                @if(session('locale') == 'ar')
                    جميع الفترات
                @else
                    All Periods
                @endif
            @endif
        </div>
        <p>{{ trans('messages.date', [], session('locale')) ?: 'Date' }}: {{ date('Y-m-d H:i') }}</p>
    </div>

    <div class="summary">
        <div class="summary-row">
            <span class="summary-label">@if(session('locale') == 'ar') إجمالي المبلغ @else Total Amount @endif:</span>
            <span class="summary-value">{{ number_format($totals['total_amount'], 3) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">@if(session('locale') == 'ar') المبلغ المدفوع @else Paid Amount @endif:</span>
            <span class="summary-value">{{ number_format($totals['paid_amount'], 3) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">@if(session('locale') == 'ar') الربح @else Profit @endif:</span>
            <span class="summary-value">{{ number_format($totals['profit'], 3) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">@if(session('locale') == 'ar') عدد الطلبات @else Orders Count @endif:</span>
            <span class="summary-value">{{ $totals['count'] }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ trans('messages.order_number', [], session('locale')) ?: 'Order Number' }}</th>
                <th>@if(session('locale') == 'ar') اسم العميل @else Customer Name @endif</th>
                <th>@if(session('locale') == 'ar') رقم العميل @else Customer Phone @endif</th>
                <th>@if(session('locale') == 'ar') المصدر @else Source @endif</th>
                <th>@if(session('locale') == 'ar') المبلغ الإجمالي @else Total Amount @endif</th>
                <th>@if(session('locale') == 'ar') المبلغ المدفوع @else Paid Amount @endif</th>
                <th>@if(session('locale') == 'ar') رسوم التوصيل @else Delivery Charges @endif</th>
                <th>@if(session('locale') == 'ar') الربح @else Profit @endif</th>
                <th>@if(session('locale') == 'ar') تاريخ الإنشاء @else Created At @endif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($formattedOrders as $order)
            <tr>
                <td>{{ $order['order_no'] }}</td>
                <td>{{ $order['customer_name'] }}</td>
                <td>{{ $order['customer_phone'] }}</td>
                <td>{{ $order['source_label'] ?? ($order['source'] === 'whatsapp' ? (trans('messages.whatsapp', [], session('locale', 'en')) ?: 'WhatsApp') : (trans('messages.direct', [], session('locale', 'en')) ?: 'Direct')) }}</td>
                <td>{{ number_format($order['total_amount'], 3) }}</td>
                <td>{{ number_format($order['paid_amount'], 3) }}</td>
                <td>{{ number_format($order['delivery_charges'] ?? 0, 3) }}</td>
                <td>{{ number_format($order['profit'], 3) }}</td>
                <td>{{ $order['created_at'] }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td><strong>@if(session('locale') == 'ar') الإجمالي @else Total @endif</strong></td>
                <td colspan="3"></td>
                <td><strong>{{ number_format($totals['total_amount'], 3) }}</strong></td>
                <td><strong>{{ number_format($totals['paid_amount'], 3) }}</strong></td>
                <td><strong>{{ number_format($totals['delivery_charges'] ?? 0, 3) }}</strong></td>
                <td><strong>{{ number_format($totals['profit'], 3) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>{{ trans('messages.generated_on', [], session('locale')) ?: 'Generated on' }}: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
