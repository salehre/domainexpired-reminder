<?php

namespace App\Console\Commands;

use App\Services\ExpiryReminderService;
use Illuminate\Console\Command;
use Throwable;

class SendExpiryReminders extends Command
{
    protected $signature = 'reminders:send
                            {--days= : Days ahead to look (default: REMINDER_DAYS)}
                            {--dry-run : Show what would be emailed, send nothing}
                            {--force : Ignore the "already sent" list}';

    protected $description = 'Email a digest of active domains/hosting that are about to expire';

    public function handle(ExpiryReminderService $service): int
    {
        $days = $this->option('days') !== null ? (int) $this->option('days') : null;

        try {
            $result = $service->run($days, (bool) $this->option('dry-run'), (bool) $this->option('force'));
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $rows = array_merge($result['upcoming'] ?? [], $result['overdue'] ?? []);
        if ($rows) {
            $this->table(
                ['Type', 'Name', 'Expires', 'Jalali', 'Days left', 'User ID'],
                array_map(fn ($r) => [$r['type'], $r['name'], $r['expires_on'], $r['expires_jalali'], $r['days_left'], $r['userid']], $rows)
            );
        }

        if (!empty($result['emailed'])) {
            $this->info("Email sent ({$result['count']} item(s)).");
        } elseif (!empty($result['dry_run'])) {
            $this->warn('Dry run: no email was sent.');
        } else {
            $this->line($result['reason'] ?? 'Nothing to do.');
        }

        return self::SUCCESS;
    }
}
