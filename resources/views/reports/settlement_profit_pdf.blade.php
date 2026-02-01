<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale') === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('messages.settlement_profit_report', [], session('locale')) ?: 'Settlement Profit Report' }}</title>
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
        <h1>{{ trans('messages.settlement_profit_report', [], session('locale')) ?: 'Settlement Profit Report' }}</h1>
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
            <span class="summary-label">@if(session('locale') == 'ar') عدد العناصر @else Number of Items @endif:</span>
            <span class="summary-value">{{ $totals['number_of_items'] }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">@if(session('locale') == 'ar') إجمالي المبيعات @else Total Sales @endif:</span>
            <span class="summary-value">{{ number_format($totals['sales'], 3) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">@if(session('locale') == 'ar') الربح @else Profit @endif:</span>
            <span class="summary-value">{{ number_format($totals['profit'], 3) }}</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">@if(session('locale') == 'ar') عدد التسويات @else Settlements Count @endif:</span>
            <span class="summary-value">{{ $totals['count'] }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>@if(session('locale') == 'ar') رقم العملية @else Operation Number @endif</th>
                <th>@if(session('locale') == 'ar') الشهر @else Month @endif</th>
                <th>@if(session('locale') == 'ar') البوتيك @else Boutique @endif</th>
                <th>@if(session('locale') == 'ar') عدد العناصر @else Number of Items @endif</th>
                <th>@if(session('locale') == 'ar') المبيعات (ر.ع) @else Sales (OMR) @endif</th>
                <th>@if(session('locale') == 'ar') الربح @else Profit @endif</th>
            </tr>
        </thead>
        <tbody>
            @foreach($formattedSettlements as $settlement)
            <tr>
                <td>{{ $settlement['operation_number'] }}</td>
                <td>{{ $settlement['month'] }}</td>
                <td>{{ $settlement['boutique'] }}</td>
                <td>{{ $settlement['number_of_items'] }}</td>
                <td>{{ number_format($settlement['sales'], 3) }}</td>
                <td>{{ number_format($settlement['profit'], 3) }}</td>
            </tr>
            @endforeach
            <tr class="totals-row">
                <td><strong>@if(session('locale') == 'ar') الإجمالي @else Total @endif</strong></td>
                <td colspan="2"></td>
                <td><strong>{{ $totals['number_of_items'] }}</strong></td>
                <td><strong>{{ number_format($totals['sales'], 3) }}</strong></td>
                <td><strong>{{ number_format($totals['profit'], 3) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>{{ trans('messages.generated_on', [], session('locale')) ?: 'Generated on' }}: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
