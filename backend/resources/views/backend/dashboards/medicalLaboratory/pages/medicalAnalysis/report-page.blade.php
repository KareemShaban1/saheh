<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Medical Analysis Report</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px;
            text-align: left;
        }

        .header {
            text-align: left;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Medical Analysis Report</h2>
        <p><strong>Date:</strong> {{ $analysis->date }}</p>
        <p><strong>Patient ID:</strong> {{ $analysis->patient_id }}</p>
        <p><strong>Patient Name:</strong> {{ $analysis->patient->name }}</p>
        <p><strong>Doctor:</strong> {{ $analysis->doctor_name }}</p>
        <p><strong>Payment Status:</strong> {{ $analysis->payment }}</p>
        <p><strong>Total Cost:</strong> {{ $analysis->cost }} EGP</p>
    </div>

    @php
        $groupedOptions = $analysis->labServiceOptions
            ->load('labService.category')
            ->groupBy(fn($item) => $item->labService->category->category_name ?? 'General');
    @endphp

    @foreach ($groupedOptions as $categoryName => $options)
        {{-- Start of Category Page --}}
        <div class="section-title">{{ $categoryName }}</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Service Name</th>
                    <th>Value</th>
                    <th>Unit</th>
                    <th>Normal Range</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($options as $index => $option)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $option->name }}</td>
                        <td>{{ $option->value === 'undefined' ? '' : $option->value }}</td>
                        <td>{{ $option->unit }}</td>
                        <td>{{ $option->normal_range }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Add page break after each category except the last --}}
        @if (!$loop->last)
            <pagebreak></pagebreak>
        @endif
    @endforeach

    @if ($analysis->report)
        <pagebreak></pagebreak>
        <div class="section-title">Additional Notes / Report:</div>
        <p>{{ $analysis->report }}</p>
    @endif

</body>
</html>
