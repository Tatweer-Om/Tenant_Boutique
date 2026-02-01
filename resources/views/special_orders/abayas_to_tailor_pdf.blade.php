<!DOCTYPE html>
<html lang="{{ session('locale', 'ar') }}" dir="{{ session('locale') === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('messages.abayas_to_send_to_tailor', [], session('locale')) }}</title>
    <style>
        @media print {
            @page {
                margin: 1.5cm;
                size: A4 landscape;
            }
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', 'Helvetica', 'Segoe UI', sans-serif;
            font-size: 11px;
            color: #2d3748;
            background: #f7fafc;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            max-width: 95%;
            width: 100%;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 8px;
            padding: 30px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 25px;
            background: linear-gradient(135deg, #4a5568 0%, #718096 100%);
            color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 0 0 15px 0;
            font-size: 26px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .header-info {
            margin-top: 18px;
            font-size: 12px;
            opacity: 0.95;
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .header-info span {
            padding: 6px 12px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 4px;
            backdrop-filter: blur(10px);
        }
        .header-info strong {
            font-weight: 600;
            margin-left: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            overflow: hidden;
        }
        thead {
            background: linear-gradient(135deg, #4a5568 0%, #718096 100%);
            color: #ffffff;
        }
        th {
            padding: 14px 10px;
            text-align: center;
            font-weight: 600;
            font-size: 11px;
            border: 1px solid #4a5568;
            letter-spacing: 0.3px;
        }
        td {
            padding: 12px 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            font-size: 10px;
            color: #2d3748;
        }
        tbody tr:nth-child(even) {
            background-color: #f7fafc;
        }
        tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }
        tbody tr {
            transition: background-color 0.2s;
        }
        .quantity-cell {
            font-weight: 700;
            color: #2d3748;
            background-color: #e2e8f0 !important;
            font-size: 11px;
        }
        .summary {
            margin-top: 25px;
            padding: 18px;
            background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
            color: #ffffff;
            border-radius: 6px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }
        .footer {
            margin-top: 25px;
            padding: 15px;
            text-align: center;
            background: #edf2f7;
            border-radius: 6px;
            font-size: 10px;
            color: #718096;
            border-top: 2px solid #cbd5e0;
        }
        .footer p {
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ trans('messages.abayas_to_send_to_tailor', [], session('locale')) ?: 'Abayas to Send to Tailor' }}</h1>
            <div class="header-info">
                <span><strong>{{ trans('messages.tailor', [], session('locale')) }}:</strong> {{ $tailorNamesStr }}</span>
                <span><strong>{{ trans('messages.date', [], session('locale')) }}:</strong> {{ $currentDate }}</span>
                <span><strong>{{ trans('messages.time', [], session('locale')) ?: 'Time' }}:</strong> {{ $currentTime }}</span>
            </div>
            <div class="header-info" style="margin-top: 12px;">
                <span><strong>{{ trans('messages.total_items', [], session('locale')) }}:</strong> {{ count($formattedItems) }}</span>
                <span><strong>{{ trans('messages.total_quantity', [], session('locale')) }}:</strong> {{ $totalQuantity }}</span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 8%;">{{ trans('messages.order_number', [], session('locale')) }}</th>
                    <th style="width: 10%;">{{ trans('messages.customer', [], session('locale')) }}</th>
                    <th style="width: 12%;">{{ trans('messages.abaya', [], session('locale')) }}</th>
                    <th style="width: 8%;">{{ trans('messages.code', [], session('locale')) }}</th>
                    <th style="width: 10%;">{{ trans('messages.tailor', [], session('locale')) }}</th>
                    <th style="width: 6%;">{{ trans('messages.quantity', [], session('locale')) }}</th>
                    <th style="width: 7%;">{{ trans('messages.color', [], session('locale')) }}</th>
                    <th style="width: 6%;">{{ trans('messages.size', [], session('locale')) }}</th>
                    <th style="width: 7%;">{{ trans('messages.abaya_length', [], session('locale')) }}</th>
                    <th style="width: 7%;">{{ trans('messages.bust_one_side', [], session('locale')) }}</th>
                    <th style="width: 7%;">{{ trans('messages.sleeves_length', [], session('locale')) }}</th>
                    <th style="width: 6%;">{{ trans('messages.buttons', [], session('locale')) }}</th>
                    <th style="width: 8%;">{{ trans('messages.order_date', [], session('locale')) }}</th>
                    <th style="width: 8%;">{{ trans('messages.notes', [], session('locale')) }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($formattedItems as $item)
                <tr>
                    <td>{{ $item['order_no'] }}</td>
                    <td>{{ $item['customer'] }}</td>
                    <td>{{ $item['abaya_name'] }}</td>
                    <td>{{ $item['abaya_code'] }}</td>
                    <td>{{ $item['tailor'] }}</td>
                    <td class="quantity-cell">{{ $item['quantity'] }}</td>
                    <td>{{ $item['color'] }}</td>
                    <td>{{ $item['size'] }}</td>
                    <td>{{ $item['length'] }}</td>
                    <td>{{ $item['bust'] }}</td>
                    <td>{{ $item['sleeves'] }}</td>
                    <td>{{ $item['buttons'] }}</td>
                    <td>{{ $item['order_date'] }}</td>
                    <td>{{ $item['notes'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="summary">
            {{ trans('messages.total', [], session('locale')) }}: {{ trans('messages.total_quantity', [], session('locale')) }} = {{ $totalQuantity }} | {{ trans('messages.total_items', [], session('locale')) }} = {{ count($formattedItems) }}
        </div>

        <div class="footer">
            <p>{{ trans('messages.generated_on', [], session('locale')) ?: 'Generated on' }}: {{ date('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
