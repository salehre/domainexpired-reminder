<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class TestReminderMail extends Command
{
    protected $signature = 'reminders:test-mail';

    protected $description = 'Send a plain test email to REMINDER_MAIL_TO to verify the SMTP settings';

    public function handle(): int
    {
        $to = config('expiry.mail_to');
        if (!$to) {
            $this->error('REMINDER_MAIL_TO is not set in .env');

            return self::FAILURE;
        }

        try {
            Mail::raw(
                'Test email from Expiry Reminder. If you can read this, SMTP is configured correctly.',
                fn ($message) => $message->to($to)->subject('Expiry Reminder: test email')
            );
        } catch (Throwable $e) {
            $this->error('Sending failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Test email sent to {$to}. Check the inbox (and spam folder).");

        return self::SUCCESS;
    }
}
