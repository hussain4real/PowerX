<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $documentTitle }} {{ $invoice->number }}</title>
    <style>
        html {
            -webkit-print-color-adjust: exact;
            color: #101828;
            font-family: Arial, sans-serif;
            font-size: 13px;
        }

        body {
            margin: 0;
        }

        .header {
            background: #071523;
            color: #ffffff;
            padding: 28px;
        }

        .brand {
            color: #ffc107;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .panel {
            border: 1px solid #d0d5dd;
            border-radius: 12px;
            margin-top: 20px;
            padding: 18px;
        }

        .grid {
            display: table;
            width: 100%;
        }

        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        table {
            border-collapse: collapse;
            margin-top: 18px;
            width: 100%;
        }

        th {
            background: #f2f4f7;
            color: #344054;
            font-size: 11px;
            letter-spacing: 0.06em;
            padding: 10px;
            text-align: left;
            text-transform: uppercase;
        }

        td {
            border-bottom: 1px solid #eaecf0;
            padding: 10px;
        }

        .right {
            text-align: right;
        }

        .status {
            background: #fff8dd;
            border-radius: 999px;
            color: #7a4b00;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            text-transform: uppercase;
        }

        .total {
            color: #071523;
            font-size: 22px;
            font-weight: 700;
        }

        .muted {
            color: #667085;
        }
    </style>
</head>
<body>
    @php
        $lineItems = $invoice->metadata['line_items'] ?? [];
        $student = $invoice->studentProfile;
        $company = $invoice->company;
    @endphp

    <div class="header">
        <div class="brand">PowerX Training Center</div>
        <h1>{{ $documentTitle }}</h1>
        <p>{{ $invoice->number }} <span class="status">{{ $invoice->status }}</span></p>
    </div>

    <div class="panel grid">
        <div class="column">
            <strong>Bill to</strong>
            <p>
                {{ $student?->full_name ?? 'Student profile pending' }}<br>
                <span class="muted">{{ $student?->email }}</span><br>
                @if ($company)
                    {{ $company->name }}
                @endif
            </p>
        </div>
        <div class="column right">
            <strong>Issued</strong>
            <p>{{ $invoice->issued_at?->format('M d, Y') ?? '-' }}</p>
            <strong>Due</strong>
            <p>{{ $invoice->due_at?->format('M d, Y') ?? '-' }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lineItems as $item)
                <tr>
                    <td>{{ $item['description'] ?? 'Training service' }}</td>
                    <td class="right">{{ $invoice->currency }} {{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td>{{ $invoice->enrollment?->course?->title ?? 'Training service' }}</td>
                    <td class="right">{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="panel">
        <table>
            <tr>
                <td>Subtotal</td>
                <td class="right">{{ $invoice->currency }} {{ number_format((float) $invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td class="right">- {{ $invoice->currency }} {{ number_format((float) $invoice->discount_total, 2) }}</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td class="right">{{ $invoice->currency }} {{ number_format((float) $invoice->tax_total, 2) }}</td>
            </tr>
            <tr>
                <td class="total">Total</td>
                <td class="right total">{{ $invoice->currency }} {{ number_format((float) $invoice->total, 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
