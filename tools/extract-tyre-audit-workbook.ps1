param(
    [Parameter(Mandatory = $true)]
    [string] $InputPath,

    [Parameter(Mandatory = $true)]
    [string] $OutputPath
)

$ErrorActionPreference = 'Stop'

function Get-CellColumnIndex {
    param([Parameter(Mandatory = $true)][string] $Reference)

    $letters = ([regex]::Match($Reference, '^[A-Z]+')).Value
    $index = 0
    foreach ($letter in $letters.ToCharArray()) {
        $index = ($index * 26) + ([int] $letter - [int] [char] 'A') + 1
    }

    return $index
}

function Normalize-Plate {
    param([Parameter(Mandatory = $true)][string] $Plate)

    $normalized = $Plate.Trim().ToUpperInvariant() -replace '\s+', '-'
    $normalized = $normalized -replace '^ET-0*3-', 'ET-3-'
    $normalized = $normalized -replace '-+', '-'

    return $normalized
}

function Normalize-Brand {
    param([Parameter(Mandatory = $true)][string] $Brand)

    $normalized = ($Brand.Trim().ToUpperInvariant() -replace '[^A-Z0-9]', '')

    if ($normalized -match '^BLACKH?A?W?A?K$' -or $normalized -eq 'BLCKHAWK') {
        return 'Black Hawk'
    }
    if ($normalized -eq 'TRIANGLE') { return 'Triangle' }
    if ($normalized -eq 'DUPRO') { return 'DUPRO' }
    if ($normalized -eq 'GOODRIDE') { return 'Goodride' }
    if ($normalized -eq 'SAILUN') { return 'Sailun' }

    return $Brand.Trim()
}

function Parse-Date {
    param([Parameter(Mandatory = $true)][string[]] $Values)

    foreach ($value in $Values) {
        $match = [regex]::Match($value, '(?<!\d)(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})(?!\d)')
        if ($match.Success) {
            return '{0:D4}-{1:D2}-{2:D2}' -f [int] $match.Groups[3].Value, [int] $match.Groups[2].Value, [int] $match.Groups[1].Value
        }
    }

    return $null
}

$resolvedInput = (Resolve-Path -LiteralPath $InputPath).Path
$resolvedOutput = if ([System.IO.Path]::IsPathRooted($OutputPath)) {
    [System.IO.Path]::GetFullPath($OutputPath)
} else {
    [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $OutputPath))
}
$tempRoot = Join-Path ([System.IO.Path]::GetTempPath()) ('tms-tyre-audit-' + [guid]::NewGuid().ToString('N'))

New-Item -ItemType Directory -Path $tempRoot | Out-Null

try {
    & tar -xf $resolvedInput -C $tempRoot
    if ($LASTEXITCODE -ne 0) {
        throw "Unable to extract workbook: $resolvedInput"
    }

    [xml] $sharedXml = Get-Content -LiteralPath (Join-Path $tempRoot 'xl\sharedStrings.xml')
    $sharedNs = New-Object System.Xml.XmlNamespaceManager($sharedXml.NameTable)
    $sharedNs.AddNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main')
    $sharedStrings = @(
        $sharedXml.SelectNodes('//m:si', $sharedNs) | ForEach-Object {
            ($_.SelectNodes('.//m:t', $sharedNs) | ForEach-Object { $_.InnerText }) -join ''
        }
    )

    [xml] $workbookXml = Get-Content -LiteralPath (Join-Path $tempRoot 'xl\workbook.xml')
    $workbookNs = New-Object System.Xml.XmlNamespaceManager($workbookXml.NameTable)
    $workbookNs.AddNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main')

    [xml] $relationshipXml = Get-Content -LiteralPath (Join-Path $tempRoot 'xl\_rels\workbook.xml.rels')
    $relationshipNs = New-Object System.Xml.XmlNamespaceManager($relationshipXml.NameTable)
    $relationshipNs.AddNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships')
    $relationships = @{}
    foreach ($relationship in $relationshipXml.SelectNodes('//r:Relationship', $relationshipNs)) {
        $relationships[$relationship.Id] = $relationship.Target
    }

    $fleets = @()
    foreach ($sheet in $workbookXml.SelectNodes('//m:sheets/m:sheet', $workbookNs)) {
        $relationshipId = $sheet.GetAttribute('id', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships')
        $sheetTarget = $relationships[$relationshipId] -replace '/', '\'
        $sheetPath = Join-Path (Join-Path $tempRoot 'xl') $sheetTarget

        [xml] $sheetXml = Get-Content -LiteralPath $sheetPath
        $sheetNs = New-Object System.Xml.XmlNamespaceManager($sheetXml.NameTable)
        $sheetNs.AddNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main')

        $cells = @{}
        foreach ($cell in $sheetXml.SelectNodes('//m:sheetData/m:row/m:c', $sheetNs)) {
            $valueNode = $cell.SelectSingleNode('./m:v', $sheetNs)
            $inlineNode = $cell.SelectSingleNode('./m:is', $sheetNs)
            $value = if ($cell.t -eq 's' -and $null -ne $valueNode) {
                $sharedStrings[[int] $valueNode.InnerText]
            } elseif ($cell.t -eq 'inlineStr' -and $null -ne $inlineNode) {
                ($inlineNode.SelectNodes('.//m:t', $sheetNs) | ForEach-Object { $_.InnerText }) -join ''
            } elseif ($null -ne $valueNode) {
                $valueNode.InnerText
            } else {
                ''
            }

            if (-not [string]::IsNullOrWhiteSpace($value)) {
                $row = [int] ([regex]::Match($cell.r, '\d+$')).Value
                $column = Get-CellColumnIndex -Reference $cell.r
                $cells["$row,$column"] = $value.Trim()
            }
        }

        $headerEntry = $cells.GetEnumerator() | Where-Object { $_.Value -match '^Tyre Position' } | Select-Object -First 1
        if ($null -eq $headerEntry) {
            throw "Tyre Position header not found on sheet $($sheet.name)."
        }

        $headerParts = $headerEntry.Key -split ','
        $headerRow = [int] $headerParts[0]
        $positionColumn = [int] $headerParts[1]
        $brandColumn = $positionColumn + 1
        $serialColumn = $positionColumn + 2
        $percentageColumns = @()
        $remarkColumn = $null

        for ($column = $serialColumn + 1; $column -le $serialColumn + 4; $column++) {
            $headerValue = [string] $cells["$headerRow,$column"]
            if ($headerValue -match '(?i)percentage|condition') {
                $percentageColumns += $column
            }
            if ($headerValue -match '(?i)remark') {
                $remarkColumn = $column
                break
            }
        }

        if ($percentageColumns.Count -eq 0) {
            throw "Percentage column not found on sheet $($sheet.name)."
        }

        $plateEntry = $cells.GetEnumerator() | Where-Object { $_.Value -match '(?i)^License Plate' } | Select-Object -First 1
        $plateParts = $plateEntry.Key -split ','
        $plateValue = [string] $cells["$([int] $plateParts[0]),$([int] $plateParts[1] + 1)"]
        $plateValue = $plateValue -replace '(?i)\s*KM Reading:.*$', ''
        $plates = @($plateValue -split '/' | ForEach-Object { Normalize-Plate -Plate $_ })
        if ($plates.Count -ne 2) {
            throw "Expected two plates on sheet $($sheet.name), found: $plateValue"
        }

        $kmEntry = $cells.GetEnumerator() | Where-Object { $_.Value -match '(?i)KM Reading' } | Select-Object -First 1
        $kmParts = $kmEntry.Key -split ','
        $kmRow = [int] $kmParts[0]
        $kmColumn = [int] $kmParts[1]
        $kmValue = ''
        for ($column = $kmColumn + 1; $column -le $kmColumn + 4; $column++) {
            $candidate = [string] $cells["$kmRow,$column"]
            if ($candidate -match '\d') {
                $kmValue = $candidate
                break
            }
        }
        $km = [int] (($kmValue -replace '[^0-9]', ''))
        $values = @($cells.Values | ForEach-Object { [string] $_ })
        $auditDate = Parse-Date -Values $values

        $rows = @()
        for ($row = $headerRow + 1; $row -le $headerRow + 26; $row++) {
            $position = ([string] $cells["$row,$positionColumn"]).Trim().ToUpperInvariant()
            if ($position -notmatch '^[A-X]$') {
                continue
            }

            $brand = ([string] $cells["$row,$brandColumn"]).Trim()
            $serial = ([string] $cells["$row,$serialColumn"]).Trim()
            $percentages = @()
            foreach ($percentageColumn in $percentageColumns) {
                $rawPercentage = ([string] $cells["$row,$percentageColumn"]).Trim()
                $percentages += if ([string]::IsNullOrWhiteSpace($rawPercentage)) { $null } else { $rawPercentage }
            }
            $remark = if ($null -ne $remarkColumn) { ([string] $cells["$row,$remarkColumn"]).Trim() } else { '' }

            $rows += [ordered] @{
                position = $position
                brand = if ($brand) { $brand } else { $null }
                serial = if ($serial) { $serial } else { $null }
                percentages = $percentages
                remark = if ($remark) { $remark } else { $null }
            }
        }

        $tyres = @()
        $emptyPositions = @()
        foreach ($row in $rows) {
            if (-not $row.serial) {
                $emptyPositions += $row.position
                continue
            }

            $percentageRaw = @($row.percentages | Where-Object { -not [string]::IsNullOrWhiteSpace($_) } | Select-Object -Last 1)
            if ($percentageRaw.Count -eq 0) {
                throw "Missing percentage for serial $($row.serial) on sheet $($sheet.name), position $($row.position)."
            }

            $percentageDigits = ([string] $percentageRaw[0]) -replace '[^0-9.]', ''
            if ([string]::IsNullOrWhiteSpace($percentageDigits)) {
                throw "Invalid percentage '$($percentageRaw[0])' for serial $($row.serial) on sheet $($sheet.name)."
            }

            $tyres += [ordered] @{
                position = $row.position
                brand = Normalize-Brand -Brand $row.brand
                serial = $row.serial.Trim().ToUpperInvariant()
                percentage = [decimal] $percentageDigits
                remark = $row.remark
            }
        }

        $fleets += [ordered] @{
            sheet = $sheet.name
            power_plate = $plates[0]
            trailer_plate = $plates[1]
            odometer = $km
            audit_date = $auditDate
            tyres = $tyres
            empty_positions = $emptyPositions
        }
    }

    $allSerials = @($fleets | ForEach-Object { $_.tyres | ForEach-Object { $_.serial } })
    $duplicateSerials = @($allSerials | Group-Object | Where-Object { $_.Count -gt 1 } | ForEach-Object { $_.Name })
    if ($duplicateSerials.Count -gt 0) {
        throw 'Duplicate tyre serials found: ' + ($duplicateSerials -join ', ')
    }

    $allPlates = @($fleets | ForEach-Object { $_.power_plate; $_.trailer_plate })
    $duplicatePlates = @($allPlates | Group-Object | Where-Object { $_.Count -gt 1 } | ForEach-Object { $_.Name })
    if ($duplicatePlates.Count -gt 0) {
        throw 'Duplicate vehicle plates found: ' + ($duplicatePlates -join ', ')
    }

    $manifest = [ordered] @{
        source = [System.IO.Path]::GetFileName($resolvedInput)
        fleet_count = $fleets.Count
        tyre_count = $allSerials.Count
        fleets = $fleets
    }

    $outputDirectory = Split-Path -Parent $resolvedOutput
    if (-not (Test-Path -LiteralPath $outputDirectory)) {
        New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null
    }

    $json = $manifest | ConvertTo-Json -Depth 8
    [System.IO.File]::WriteAllText($resolvedOutput, $json, (New-Object System.Text.UTF8Encoding($false)))
    Write-Output "Extracted $($fleets.Count) fleet audit sheets and $($allSerials.Count) tyres to $resolvedOutput"
} finally {
    if (Test-Path -LiteralPath $tempRoot) {
        Remove-Item -LiteralPath $tempRoot -Recurse -Force
    }
}
