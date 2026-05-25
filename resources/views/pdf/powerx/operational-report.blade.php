<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $report['title'] }}</title>
    <style>
        html {
            -webkit-print-color-adjust: exact;
            color: #0b2034;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
        }

        body {
            margin: 0;
        }

        .header {
            background: #071523;
            color: #ffffff;
            padding: 28px;
        }

        .eyebrow {
            color: #ffc107;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        h1 {
            font-size: 28px;
            margin: 8px 0 0;
        }

        h2 {
            color: #071523;
            font-size: 16px;
            margin: 0 0 6px;
        }

        .meta {
            color: rgba(255, 255, 255, 0.72);
            margin-top: 10px;
        }

        .section {
            page-break-inside: avoid;
            padding: 22px 28px 0;
        }

        .description {
            color: #475569;
            margin: 0 0 12px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th {
            background: #ffc107;
            color: #071523;
            font-size: 9px;
            text-align: left;
            text-transform: uppercase;
        }

        th,
        td {
            border: 1px solid #dbe3ea;
            padding: 7px;
            vertical-align: top;
        }

        td {
            color: #102235;
        }

        .empty {
            border: 1px dashed #dbe3ea;
            color: #64748b;
            padding: 12px;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="eyebrow">PowerX management export</div>
        <h1>{{ $report['title'] }}</h1>
        <div class="meta">Team: {{ $report['team'] }} | Generated: {{ $report['generatedAt'] }}</div>
    </header>

    @foreach ($report['sections'] as $section)
        <section class="section">
            <h2>{{ $section['title'] }}</h2>
            <p class="description">{{ $section['description'] }}</p>

            @if (count($section['rows']) === 0)
                <div class="empty">No records available for this report section.</div>
            @else
                <table>
                    <thead>
                        <tr>
                            @foreach ($section['columns'] as $column)
                                <th>{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($section['rows'] as $row)
                            <tr>
                                @foreach ($row as $value)
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endforeach
</body>
</html>
