<?php

namespace App\Console\Commands;

use App\Enums\VoucherStatus;
use App\Models\TyreDisposal;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class CompleteApprovedDisposalsCommand extends Command
{
    protected $signature = 'tms:complete-approved-disposals
                            {--dry-run : Show what would be completed without actually completing}
                            {--limit= : Maximum number of disposals to complete}';

    protected $description = 'Complete all approved tyre disposals (useful for bulk operations)';

    public function handle(ApprovalService $approval): int
    {
        $admin = User::query()->where('email', 'admin@menkem.com')->first();
        if (! $admin) {
            $this->error('Admin user not found. Run php artisan migrate:fresh --seed');

            return self::FAILURE;
        }

        Auth::login($admin);

        $query = TyreDisposal::query()
            ->where('status', VoucherStatus::Approved);

        $limit = $this->option('limit');
        if ($limit) {
            $query->limit((int) $limit);
        }

        $disposals = $query->get();

        if ($disposals->isEmpty()) {
            $this->info('No approved disposals found to complete.');

            return self::SUCCESS;
        }

        $this->info("Found {$disposals->count()} approved disposal(s).");

        if ($this->option('dry-run')) {
            $this->table(
                ['Disposal No', 'Tyre Code', 'Reason', 'Status'],
                $disposals->map(fn ($d) => [
                    $d->disposal_no,
                    $d->tyre?->tyre_code ?? 'N/A',
                    $d->disposal_reason->value,
                    $d->status->value,
                ])
            );

            $this->warn('Dry run mode - no disposals were completed.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Complete {$disposals->count()} disposal(s)?", true)) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $completed = 0;
        $failed = 0;

        foreach ($disposals as $disposal) {
            try {
                $approval->completeDisposal($disposal);
                $this->line("  ✓ {$disposal->disposal_no} ({$disposal->tyre?->tyre_code})");
                $completed++;
            } catch (\Exception $e) {
                $this->error("  ✗ {$disposal->disposal_no}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Completed: {$completed}");
        if ($failed > 0) {
            $this->error("Failed: {$failed}");
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
