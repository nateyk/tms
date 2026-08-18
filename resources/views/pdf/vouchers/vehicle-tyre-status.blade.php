<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Tyre Status - {{ $vehicle->vehicle_code }}</title>
    <style>
        @page { margin: 18px 22px; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 8px; line-height: 1.25; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .header { border-bottom: 2px solid #243b72; padding-bottom: 8px; }
        .header td { border: 0; padding: 0; vertical-align: middle; }
        .logo { max-height: 39px; max-width: 170px; }
        .title { text-align: right; }
        .title h1 { font-size: 17px; margin: 0 0 2px; }
        .title p { color: #667085; font-size: 8px; margin: 0; }
        .meta { margin-top: 8px; table-layout: fixed; }
        .meta td { background: #f6f8fb; border: 1px solid #d8dee9; padding: 5px 7px; vertical-align: top; }
        .meta-label { color: #667085; display: block; font-size: 6.8px; text-transform: uppercase; }
        .meta-value { display: block; font-size: 9px; font-weight: bold; margin-top: 1px; }
        .content { margin-top: 8px; table-layout: fixed; }
        .content > tbody > tr > td { border: 0; padding: 0; vertical-align: top; }
        .map-column { padding-right: 10px !important; width: 29%; }
        .register-column { border-left: 1px solid #d8dee9 !important; padding-left: 10px !important; width: 71%; }
        .section-title { font-size: 10px; font-weight: bold; margin: 0 0 5px; }
        .section-subtitle { color: #667085; font-size: 7px; margin: -3px 0 5px; }
        .rig { background: #f8fafc; border: 1px solid #ccd6e3; border-radius: 8px; height: 358px; padding: 10px 6px; position: relative; }
        .chassis { background: #eef3f8; border: 2px solid #b5c3d4; border-radius: 15px; bottom: 12px; left: 33%; position: absolute; top: 12px; width: 34%; }
        .cab { background: #dce5ef; border: 2px solid #94a5ba; border-radius: 10px 10px 5px 5px; height: 48px; left: 36%; position: absolute; top: 18px; width: 28%; }
        .center-line { border-left: 1px dashed #c5cfdb; bottom: 18px; left: 50%; position: absolute; top: 70px; }
        .axle { left: 16%; position: absolute; width: 68%; }
        .axle-line { border-top: 3px solid #91a2b7; left: 8%; position: absolute; top: 14px; width: 84%; }
        .axle-dot { background: #64748b; border-radius: 50%; height: 7px; left: 49%; position: absolute; top: 11px; width: 7px; }
        .axle-1 { top: 55px; } .axle-2 { top: 101px; } .axle-3 { top: 147px; }
        .axle-4 { top: 213px; } .axle-5 { top: 265px; } .axle-6 { top: 311px; }
        .wheel { background: #fff; border: 1.2px solid #cbd5e1; border-radius: 5px; height: 29px; padding-top: 4px; position: absolute; text-align: center; top: 0; width: 27px; z-index: 2; }
        .wheel strong { display: block; font-size: 8.5px; }
        .wheel small { display: block; font-size: 6px; margin-top: 1px; }
        .wheel-left-outer { left: -9px; } .wheel-left-inner { left: 20px; }
        .wheel-right-inner { right: 20px; } .wheel-right-outer { right: -9px; }
        .wheel-single-left { left: 2px; } .wheel-single-right { right: 2px; }
        .wheel.good { background: #ecfdf3; border-color: #62cf91; }
        .wheel.watch { background: #fffbeb; border-color: #f6c453; }
        .wheel.low { background: #fff4e8; border-color: #f59e5b; }
        .wheel.critical { background: #fff0f0; border-color: #ef7b7b; }
        .wheel.no-baseline { background: #f8fafc; border-color: #8ea0b8; }
        .wheel.empty { border-style: dashed; color: #94a3b8; }
        .spare { background: #fff; border: 1px dashed #b8c4d2; border-radius: 5px; height: 27px; left: 39%; padding-top: 4px; position: absolute; text-align: center; width: 22%; z-index: 3; }
        .spare strong { font-size: 8px; } .spare small { display: block; font-size: 5.5px; }
        .spare-w { top: 183px; } .spare-x { top: 235px; }
        .summary { margin-top: 7px; table-layout: fixed; }
        .summary td { border: 1px solid #d8dee9; padding: 4px 3px; text-align: center; }
        .summary strong { display: block; font-size: 10px; }
        .summary span { color: #667085; font-size: 6px; text-transform: uppercase; }
        .legend { color: #526071; font-size: 6.6px; margin-top: 5px; text-align: center; }
        .dot { border: 1px solid transparent; display: inline-block; height: 7px; margin: 0 2px 0 5px; width: 7px; }
        .dot.good { background: #dcfce7; border-color: #62cf91; } .dot.watch { background: #fef3c7; border-color: #f6c453; }
        .dot.low { background: #ffedd5; border-color: #f59e5b; } .dot.critical { background: #fee2e2; border-color: #ef7b7b; }
        .dot.no-baseline { background: #f1f5f9; border-color: #8ea0b8; }
        .register { font-size: 7.1px; table-layout: fixed; }
        .register th { background: #243b72; border: 1px solid #243b72; color: #fff; font-size: 6.2px; padding: 4px 3px; text-align: left; text-transform: uppercase; }
        .register td { border: 1px solid #d8dee9; height: 14px; padding: 2.5px 3px; vertical-align: middle; }
        .register tbody tr:nth-child(even) td { background: #f8fafc; }
        .register .empty-row td { color: #98a2b3; }
        .position { font-size: 8px; font-weight: bold; text-align: center; }
        .serial { font-weight: bold; } .number { text-align: right; white-space: nowrap; }
        .status-text { font-size: 6.5px; font-weight: bold; white-space: nowrap; }
        .status-text.good { color: #147a42; } .status-text.watch { color: #9a6700; }
        .status-text.low { color: #b54708; } .status-text.critical { color: #b42318; }
        .status-text.no-baseline, .status-text.empty { color: #667085; }
        .footer { border-top: 1px solid #d8dee9; color: #667085; font-size: 6.5px; margin-top: 6px; padding-top: 4px; }
        .footer .right { float: right; }
    </style>
</head>
<body>
    @php
        $rowByPosition = $rows->keyBy('position');
        $wheel = fn (string $position) => $rowByPosition->get($position);
        $percent = fn ($value) => $value === null ? '-' : number_format((float) $value, 1).'%';
        $km = fn ($value) => $value === null ? '-' : number_format((int) $value);
    @endphp

    <table class="header"><tr>
        <td>@if(! empty($companyLogoDataUri))<img class="logo" src="{{ $companyLogoDataUri }}" alt="Menkem logo">@else<strong>{{ $company }}</strong>@endif</td>
        <td class="title"><h1>Vehicle Tyre Status Report</h1><p>Mounted tyre identity, KM usage, audit condition and effective remaining life</p></td>
    </tr></table>

    <table class="meta"><tr>
        <td><span class="meta-label">Power Unit</span><span class="meta-value">{{ $vehicle->displayCodeWithPlate() }}</span></td>
        <td><span class="meta-label">Attached Trailer</span><span class="meta-value">{{ $attachedTrailer?->displayCodeWithPlate() ?? 'Not attached' }}</span></td>
        <td><span class="meta-label">Vehicle Type</span><span class="meta-value">{{ $vehicle->vehicleType?->name ?? '-' }}</span></td>
        <td><span class="meta-label">Current Odometer</span><span class="meta-value">{{ $latestOdometer === null ? 'Not recorded' : number_format($latestOdometer).' KM' }}</span></td>
        <td><span class="meta-label">Report Date</span><span class="meta-value">{{ $printedAt }}</span></td>
    </tr></table>

    <table class="content"><tr>
        <td class="map-column">
            <div class="section-title">Tyre Position Overview</div>
            <div class="section-subtitle">A-J power unit, K-X trailer / rear group</div>
            <div class="rig">
                <div class="chassis"></div><div class="cab"></div><div class="center-line"></div>
                @foreach([
                    ['class' => 'axle-1', 'positions' => ['A', null, null, 'B'], 'single' => true],
                    ['class' => 'axle-2', 'positions' => ['C', 'D', 'E', 'F'], 'single' => false],
                    ['class' => 'axle-3', 'positions' => ['G', 'H', 'I', 'J'], 'single' => false],
                    ['class' => 'axle-4', 'positions' => ['K', 'L', 'M', 'N'], 'single' => false],
                    ['class' => 'axle-5', 'positions' => ['O', 'P', 'Q', 'R'], 'single' => false],
                    ['class' => 'axle-6', 'positions' => ['S', 'T', 'U', 'V'], 'single' => false],
                ] as $axle)
                    <div class="axle {{ $axle['class'] }}"><div class="axle-line"></div><div class="axle-dot"></div>
                        @foreach($axle['positions'] as $index => $position)
                            @if($position)
                                @php
                                    $item = $wheel($position);
                                    $positionClass = $axle['single'] ? ($index === 0 ? 'wheel-single-left' : 'wheel-single-right') : ['wheel-left-outer', 'wheel-left-inner', 'wheel-right-inner', 'wheel-right-outer'][$index];
                                @endphp
                                <div class="wheel {{ $positionClass }} {{ $item['status_key'] }}"><strong>{{ $position }}</strong><small>{{ $item['is_empty'] ? 'Empty' : $percent($item['effective_percentage']) }}</small></div>
                            @endif
                        @endforeach
                    </div>
                @endforeach
                @foreach(['W' => 'spare-w', 'X' => 'spare-x'] as $position => $class)
                    @php($item = $wheel($position))
                    <div class="spare {{ $class }} {{ $item['status_key'] }}"><strong>{{ $position }}</strong><small>{{ $item['is_empty'] ? 'Empty spare' : $percent($item['effective_percentage']).' spare' }}</small></div>
                @endforeach
            </div>
            <table class="summary"><tr>
                <td><strong>{{ $summary['mounted'] }}</strong><span>Mounted</span></td><td><strong>{{ $summary['empty'] }}</strong><span>Empty</span></td>
                <td><strong>{{ $summary['good'] }}</strong><span>Good</span></td><td><strong>{{ $summary['attention'] }}</strong><span>Attention</span></td>
                <td><strong>{{ $summary['no_baseline'] }}</strong><span>No Base</span></td>
            </tr></table>
            <div class="legend"><span class="dot good"></span>Good <span class="dot watch"></span>Watch <span class="dot low"></span>Low <span class="dot critical"></span>End of Life <span class="dot no-baseline"></span>No Baseline</div>
        </td>
        <td class="register-column">
            <div class="section-title">Mounted Tyre Register</div>
            <div class="section-subtitle">Effective remaining uses the latest condition audit when available; otherwise it uses calculated KM life.</div>
            <table class="register">
                <colgroup><col style="width: 5%"><col style="width: 17%"><col style="width: 10%"><col style="width: 8%"><col style="width: 7%"><col style="width: 7%"><col style="width: 7%"><col style="width: 8%"><col style="width: 9%"><col style="width: 12%"><col style="width: 10%"></colgroup>
                <thead><tr><th>Pos</th><th>Serial Number</th><th>Tyre Code</th><th>Brand</th><th>Base</th><th>Calc</th><th>Audit</th><th>Effective</th><th>Used KM</th><th>Status</th><th>Unit</th></tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="{{ $row['is_empty'] ? 'empty-row' : '' }}">
                            <td class="position">{{ $row['position'] }}</td><td class="serial">{{ $row['serial_number'] ?? 'Empty position' }}</td>
                            <td>{{ $row['tyre_code'] ?? '-' }}</td><td>{{ $row['brand'] ?? '-' }}</td>
                            <td class="number">{{ $percent($row['baseline_percentage']) }}</td><td class="number">{{ $percent($row['calculated_percentage']) }}</td>
                            <td class="number">{{ $percent($row['audited_percentage']) }}</td><td class="number"><strong>{{ $percent($row['effective_percentage']) }}</strong></td>
                            <td class="number">{{ $km($row['used_km']) }}</td><td><span class="status-text {{ $row['status_key'] }}">{{ $row['status'] }}</span></td><td>{{ $row['unit'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </td>
    </tr></table>

    <div class="footer">Status thresholds: Good 60-100%, Watch 30-59.9%, Low 10-29.9%, End of Life below 10%. Empty positions are excluded from mounted totals.<span class="right">Generated by Menkem TMS</span></div>
</body>
</html>
