<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Monthly Attendance Report</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: DejaVu Sans, Arial, sans-serif;
        }

        body {
            font-size: 12px;
            color: #111827;
            margin: 0;
            padding: 24px;
        }

        h1 {
            font-size: 20px;
            margin-bottom: 4px;
        }

        h2 {
            font-size: 14px;
            margin: 16px 0 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #374151;
        }

        p {
            margin: 0 0 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        th,
        td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #f3f4f6;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
        }

        .meta-table th,
        .meta-table td {
            border: none;
            padding: 2px 0;
        }

        .meta-table th {
            background-color: transparent;
            font-size: 12px;
            text-transform: none;
            letter-spacing: normal;
            color: #6b7280;
            width: 80px;
        }

        .pill {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .pill-present {
            background-color: #d1fae5;
            color: #065f46;
        }

        .pill-absent {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .pill-late {
            background-color: #fef3c7;
            color: #92400e;
        }

        .section {
            page-break-inside: avoid;
        }

        .records-table {
            font-size: 11px;
        }

        .records-table th,
        .records-table td {
            padding: 4px 6px;
        }
    </style>
</head>

<body>
    @php($metadata = $table['metadata'] ?? [])
    @php($summary = $table['summary']['rows'] ?? [])
    @php($dailyTotals = $table['daily_totals']['rows'] ?? [])
    @php($recordHeaders = $table['records']['headers'] ?? [])
    @php($recordRows = $table['records']['rows'] ?? [])

    <header>
        <h1>Monthly Attendance Report</h1>
        <p>Generated at {{ $generatedAt->toDayDateTimeString() }}</p>
        <table class="meta-table">
            <tbody>
                @foreach ($metadata as $row)
                    <tr>
                        <th>{{ $row[0] ?? '' }}</th>
                        <td>{{ $row[1] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </header>

    <section class="section">
        <h2>Summary</h2>
        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($summary as $row)
                    <tr>
                        <td>
                            @php($label = strtolower($row[0] ?? ''))
                            <span class="pill pill-{{ $label }}">{{ $row[0] ?? '' }}</span>
                        </td>
                        <td>{{ $row[1] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No summary data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Daily Totals</h2>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailyTotals as $row)
                    <tr>
                        <td>{{ $row[0] ?? '' }}</td>
                        <td>{{ $row[1] ?? 0 }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2">No attendance captured this month.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>

    <section class="section">
        <h2>Detailed Records</h2>
        <table class="records-table">
            <thead>
                <tr>
                    @foreach ($recordHeaders as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($recordRows as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell ?: '-' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($recordHeaders) }}">No detailed attendance records for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</body>

</html>
