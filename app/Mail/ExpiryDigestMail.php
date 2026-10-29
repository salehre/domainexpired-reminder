<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ExpiryDigestMail extends Mailable
{
    public function __construct(
        public int $days,
        public array $upcoming,
        public array $overdue,
    ) {
    }

    public function envelope(): Envelope
    {
        $n = count($this->upcoming) + count($this->overdue);

        return new Envelope(subject: "یادآوری انقضا: {$n} مورد (تا {$this->days} روز آینده)");
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.expiry-digest',
            with: [
                'days' => $this->days,
                'sections' => [
                    ['title' => 'در آستانه انقضا', 'rows' => $this->upcoming],
                    ['title' => 'هنوز فعال ولی تاریخ‌شان گذشته', 'rows' => $this->overdue],
                ],
            ],
        );
    }
}
