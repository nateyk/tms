<?php

namespace App\Filament\Pages;

use App\Services\TyreReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PendingApprovals extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Pending Approvals';

    protected static ?string $title = 'Pending Approvals';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'Approvals & Reports';

    protected string $view = 'filament.pages.pending-approvals';

    public function getViewData(): array
    {
        return app(TyreReportService::class)->pendingApprovals();
    }
}
