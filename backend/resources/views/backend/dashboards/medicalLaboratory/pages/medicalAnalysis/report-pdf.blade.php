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
            text-align: center;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="header" style="text-align: left;">
        <h2>Medical Analysis Report</h2>
        <p><strong>Date:</strong> {{ $analysis->date }}</p>
        <p><strong>Patient ID:</strong> {{ $analysis->patient_id }}</p>
        <p><strong>Patient Name:</strong> {{ $analysis->patient->name }}</p>
        <p><strong>Doctor:</strong> {{ $analysis->doctor_name }}</p>
        <p><strong>Payment Status:</strong> {{ $analysis->payment }}</p>
        <p><strong>Total Cost:</strong> {{ $analysis->cost }} EGP</p>
    </div>

    <div class="section-title"></div>

    @php
    $groupedOptions = $analysis->labServiceOptions
    ->load('labService.category')
    ->groupBy(function ($item) {
    return $item->labService->category->category_name ?? '';
    });
    @endphp

    @foreach ($groupedOptions as $categoryName => $options)
    <div class="section-title">{{ $categoryName }}</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Service Name</th>
                <th>Value</th>
                <th>Unit</th>
                <th>Normal Range</th>
                <!-- <th>Price</th> -->
            </tr>
        </thead>
        <tbody>
            @foreach ($options as $index => $option)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $option->name }}</td>
                <td>{{ $option->value === 'undefined' ? '' : $option->value  }}</td>
                <td>{{ $option->unit }}</td>
                <td>{{ $option->normal_range }}</td>
                <!-- <td>{{ $option->price }}</td> -->
            </tr>
            @endforeach
        </tbody>
    </table>
    @endforeach

    @if ($analysis->report)
    <div class="section-title">Additional Notes / Report:</div>
    <p>{{ $analysis->report }}</p>
    @endif

</body>

</html>