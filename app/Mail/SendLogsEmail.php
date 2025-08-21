<?php

namespace App\Mail;

use App\Models\SyncLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendLogsEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $syncLog;
    public $batchStatus;

    public function __construct(SyncLog $syncLog, ?array $batchStatus = null)
    {
        $this->syncLog = $syncLog;
        $this->batchStatus = $batchStatus;
    }

    public function envelope(): Envelope
    {
        $type = ucfirst(str_replace('_', ' ', $this->syncLog->sync_type));
        
        if ($this->syncLog->status === 'failed') {
            $subject = "{$type} Failed - {$this->syncLog->total_products_fetched} Products";
        } else {
            $subject = "{$type} Completed - {$this->syncLog->total_products_fetched} Products";
        }

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address'),
            to: config('mail-settings.admin_email', 'hammam111998@gmail.com'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: config('mail-settings.email_template', 'emails.sync-logs'),
            with: [
                'syncLog' => $this->syncLog,
                'batchStatus' => $this->batchStatus,
                'adminName' => config('mail-settings.admin_name', 'Admin'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
