<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate {{ $certificate->certificate_number }}</title>
    <style>
        html {
            -webkit-print-color-adjust: exact;
            background: #071523;
            color: #ffffff;
            font-family: Georgia, serif;
        }

        body {
            margin: 0;
            padding: 32px;
        }

        .frame {
            border: 4px solid #ffc107;
            min-height: 620px;
            padding: 42px;
            text-align: center;
        }

        .brand {
            color: #ffc107;
            font-family: Arial, sans-serif;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        h1 {
            font-size: 46px;
            font-weight: 400;
            letter-spacing: 0.08em;
            margin: 60px 0 20px;
            text-transform: uppercase;
        }

        .student {
            color: #ffc107;
            font-size: 42px;
            margin: 28px 0;
        }

        .course {
            font-size: 28px;
            margin: 20px auto;
            max-width: 820px;
        }

        .meta {
            color: #d0d5dd;
            font-family: Arial, sans-serif;
            font-size: 13px;
            margin-top: 70px;
        }

        .grid {
            display: table;
            width: 100%;
        }

        .column {
            display: table-cell;
            text-align: center;
            width: 33.3%;
        }
    </style>
</head>
<body>
    <div class="frame">
        <div class="brand">PowerX Training Center</div>
        <h1>Certificate of Completion</h1>
        <p>This certifies that</p>
        <div class="student">{{ $certificate->studentProfile->full_name }}</div>
        <p>has successfully completed</p>
        <div class="course">{{ $certificate->course->title }}</div>
        <p>with result: {{ strtoupper($certificate->result) }}</p>

        <div class="meta grid">
            <div class="column">
                Certificate No.<br>
                {{ $certificate->certificate_number }}
            </div>
            <div class="column">
                Issued<br>
                {{ $certificate->issued_at?->format('M d, Y') ?? '-' }}
            </div>
            <div class="column">
                Verify<br>
                {{ route('certificates.verify', ['token' => $certificate->verification_token]) }}
            </div>
        </div>
    </div>
</body>
</html>
