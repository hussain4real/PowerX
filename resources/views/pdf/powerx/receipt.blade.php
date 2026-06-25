<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receiptNumber }}</title>
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

        .row {
            border-bottom: 1px solid #eaecf0;
            padding: 10px 0;
        }

        .label {
            color: #667085;
            display: inline-block;
            width: 180px;
        }

        .amount {
            color: #071523;
            font-size: 28px;
            font-weight: 700;
        }

        .status {
            background: #e7f8ed;
            border-radius: 999px;
            color: #067647;
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    @php
        $offlinePayment = data_get($payment->metadata, 'offline_payment', []);
        $financeReview = data_get($payment->metadata, 'finance_review', []);
        $proof = $payment->getFirstMedia('payment-proofs');
    @endphp

    <div class="header">
        <div class="brand">PowerX Training Center</div>
        <h1>Payment Receipt</h1>
        <p>{{ $receiptNumber }} <span class="status">{{ $payment->status }}</span></p>
    </div>

    <div class="panel">
        <p class="amount">{{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}</p>
        <div class="row"><span class="label">Student</span>{{ $payment->studentProfile?->full_name ?? '-' }}</div>
        <div class="row"><span class="label">Company</span>{{ $payment->company?->name ?? '-' }}</div>
        <div class="row"><span class="label">Course</span>{{ $payment->enrollment?->course?->title ?? '-' }}</div>
        <div class="row"><span class="label">Package</span>{{ $payment->enrollment?->coursePackage?->name ?? '-' }}</div>
        <div class="row"><span class="label">Invoice</span>{{ $payment->invoice?->number ?? '-' }}</div>
        <div class="row"><span class="label">Method</span>{{ str_replace('_', ' ', $payment->method) }}</div>
        <div class="row"><span class="label">Reference</span>{{ $payment->reference ?? '-' }}</div>
        <div class="row"><span class="label">Payer</span>{{ data_get($offlinePayment, 'payer_name', $payment->studentProfile?->full_name ?? $payment->company?->name ?? '-') }}</div>
        <div class="row"><span class="label">Payer email</span>{{ data_get($offlinePayment, 'payer_email', '-') }}</div>
        <div class="row"><span class="label">Bank/deposit</span>{{ data_get($offlinePayment, 'bank_name', '-') }} / {{ data_get($offlinePayment, 'deposit_date', '-') }}</div>
        <div class="row"><span class="label">Paid at</span>{{ $payment->paid_at?->format('M d, Y H:i') ?? '-' }}</div>
        <div class="row"><span class="label">Approved by</span>{{ $payment->approvedBy?->name ?? '-' }}</div>
        <div class="row"><span class="label">Approved at</span>{{ $payment->approved_at?->format('M d, Y H:i') ?? '-' }}</div>
        <div class="row"><span class="label">Review status</span>{{ str_replace('_', ' ', (string) data_get($financeReview, 'status', $payment->status)) }}</div>
        <div class="row"><span class="label">Offline proof reference</span>{{ $proof?->uuid ?? 'payment-'.$payment->id }}</div>
    </div>
</body>
</html>
