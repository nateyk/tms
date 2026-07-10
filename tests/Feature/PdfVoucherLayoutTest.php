<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PdfVoucherLayoutTest extends TestCase
{
    public function test_pdf_layout_renders_company_logo_when_available(): void
    {
        $html = Blade::render(
            <<<'BLADE'
@extends('pdf.layout')
@section('document_title', 'Tyre Movement Voucher')
@section('content')
    <p>Voucher body</p>
@endsection
BLADE,
            [
                'company' => 'Menkem International Business PLC',
                'companyLogoDataUri' => 'data:image/svg+xml;base64,PHN2Zy8+',
                'printedAt' => '15 Jun 2026 12:32',
            ]
        );

        $this->assertStringContainsString('class="company-logo"', $html);
        $this->assertStringContainsString('src="data:image/svg+xml;base64,PHN2Zy8+"', $html);
        $this->assertStringContainsString('alt="Menkem International Business PLC logo"', $html);
    }
}
