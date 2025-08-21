<?php

namespace App\Services;

use App\Models\SyncLog;
use App\Mail\SendLogsEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailService
{
    public function sendSyncLogEmail(SyncLog $syncLog, ?array $batchStatus = null): bool
    {
        try {
            $adminEmail = config('mail-settings.admin_email', 'hammam111998@gmail.com');

            Mail::to($adminEmail)->send(new SendLogsEmail($syncLog, $batchStatus));
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}
