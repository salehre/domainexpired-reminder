<?php

namespace App\Services;

use App\Mail\ExpiryDigestMail;
use App\Support\Jalali;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

class ExpiryReminderService
{
    /*
     * WHMCS: domains have a real `expirydate`; hosting has no expiry column,
     * its renewal date `nextduedate` is used. Only explicit columns are
     * selected - the hosting `username` / `password` columns are never read.
     */
    private const SOURCES = [
        'domain' => [
            'table' => 'tbldomains', 'alias' => 'd', 'date' => 'expirydate',
            'status_col' => 'status', 'extra' => [],
        ],
        'hosting' => [
            'table' => 'tblhosting', 'alias' => 'h', 'date' => 'nextduedate',
            'status_col' => 'domainstatus', 'extra' => ['packageid', 'billingcycle'],
        ],
    ];

    private function query(string $kind): Builder
    {
        $s = self::SOURCES[$kind];
        $a = $s['alias'];

        $select = ["$a.id", "$a.userid", "$a.domain as name", "$a.{$s['date']} as expires_on"];
        foreach ($s['extra'] as $col) {
            $select[] = "$a.$col";
        }

        $q = DB::connection('whmcs')->table("{$s['table']} as $a")->where("$a.{$s['status_col']}", 'Active');

        if (config('expiry.include_client_info')) {
            $q->leftJoin('tblclients as c', 'c.id', '=', "$a.userid");
            array_push(
                $select,
                'c.firstname', 'c.lastname', 'c.companyname',
                'c.email as client_email', 'c.phonenumber as client_phone'
            );
        }

        return $q->select($select)->orderBy("$a.{$s['date']}")->orderBy("$a.id");
    }

    private function decorate(array $row, string $kind, string $today): array
    {
        $expires = (string) $row['expires_on'];
        $row['type'] = $kind;
        $row['days_left'] = (int) round(
            (strtotime($expires . ' 00:00:00 UTC') - strtotime($today . ' 00:00:00 UTC')) / 86400
        );
        $row['expires_jalali'] = Jalali::fromDateString($expires);
        // A renewal changes the date, which produces a new key => a new reminder.
        $row['key'] = "$kind:{$row['id']}:$expires";

        return $row;
    }

    /** Read-only look at what is expiring within $days days. */
    public function collect(?int $days = null): array
    {
        $days ??= (int) config('expiry.days');
        $now = CarbonImmutable::now(config('expiry.timezone'));
        $today = $now->toDateString();
        $to = $now->addDays($days)->toDateString();

        $upcoming = [];
        $overdue = [];

        foreach (self::SOURCES as $kind => $s) {
            $col = "{$s['alias']}.{$s['date']}";

            foreach ($this->query($kind)->whereBetween($col, [$today, $to])->get() as $row) {
                $upcoming[] = $this->decorate((array) $row, $kind, $today);
            }

            $late = $this->query($kind)->where($col, '<', $today)->where($col, '>', '2000-01-01')->get();
            foreach ($late as $row) {
                $overdue[] = $this->decorate((array) $row, $kind, $today);
            }
        }

        $byDate = fn (array $a, array $b) => strcmp($a['expires_on'], $b['expires_on']);
        usort($upcoming, $byDate);
        usort($overdue, $byDate);

        return compact('today', 'to', 'days', 'upcoming', 'overdue');
    }

    /**
     * Collects, drops what was already emailed, and sends ONE digest email.
     * The whole window (today .. today+days) is checked, so a day the cron
     * did not run is caught up on the next run instead of being missed.
     */
    public function run(?int $days = null, bool $dryRun = false, bool $force = false): array
    {
        $data = $this->collect($days);

        // Read first, even with --force: fails BEFORE any email if the log table is missing.
        $known = $this->sentKeys();
        $sent = $force ? [] : $known;

        $fresh = fn (array $rows) => array_values(array_filter($rows, fn ($r) => !isset($sent[$r['key']])));
        $upcoming = $fresh($data['upcoming']);
        $overdue = config('expiry.include_overdue') ? $fresh($data['overdue']) : [];

        if (!$upcoming && !$overdue) {
            return ['emailed' => false, 'reason' => 'nothing new to report', 'today' => $data['today']];
        }

        if ($dryRun) {
            return ['emailed' => false, 'dry_run' => true, 'today' => $data['today'],
                'upcoming' => $upcoming, 'overdue' => $overdue];
        }

        $to = config('expiry.mail_to');
        if (!$to) {
            throw new RuntimeException('REMINDER_MAIL_TO is not set in .env');
        }

        Mail::to($to)->send(new ExpiryDigestMail($data['days'], $upcoming, $overdue));
        $this->markSent(array_merge($upcoming, $overdue));

        return ['emailed' => true, 'count' => count($upcoming) + count($overdue), 'today' => $data['today'],
            'upcoming' => $upcoming, 'overdue' => $overdue];
    }

    // "Already sent" log lives in the app's own (default) database.
    // The WHMCS database is only ever read, through the 'whmcs' connection.

    /** @return array<string,true> keys like "domain:53:2026-11-28" */
    private function sentKeys(): array
    {
        $keys = [];
        foreach (DB::table('sent_reminders')->get(['item_type', 'item_id', 'expires_on']) as $row) {
            $keys["{$row->item_type}:{$row->item_id}:" . substr((string) $row->expires_on, 0, 10)] = true;
        }

        return $keys;
    }

    private function markSent(array $items): void
    {
        $now = now();
        $rows = array_map(fn ($r) => [
            'item_type' => $r['type'],
            'item_id' => $r['id'],
            'expires_on' => $r['expires_on'],
            'name' => $r['name'],
            'user_id' => $r['userid'],
            'sent_at' => $now,
        ], $items);

        DB::table('sent_reminders')->insertOrIgnore($rows);
    }
}
