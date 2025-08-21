<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Email Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file contains settings for admin email notifications
    | related to product synchronization.
    |
    */

    'admin_email' => env('ADMIN_EMAIL', 'hammam111998@gmail.com'),
    
    'admin_name' => env('ADMIN_NAME', 'Admin'),
    
    'company_name' => env('COMPANY_NAME', 'Product Sync System'),
    
    'email_subject_prefix' => env('EMAIL_SUBJECT_PREFIX', '🔄'),
    
    'enable_sync_emails' => env('ENABLE_SYNC_EMAILS', true),
    
    'email_template' => env('EMAIL_TEMPLATE', 'emails.sync-logs'),
    
    'send_on_success' => env('SEND_ON_SUCCESS', true),
    
    'send_on_failure' => env('SEND_ON_FAILURE', true),
    
    'include_batch_details' => env('INCLUDE_BATCH_DETAILS', true),
    
    'email_frequency' => env('EMAIL_FREQUENCY', 'immediate'), // immediate, daily, weekly
    
    'timezone' => env('TIMEZONE', 'UTC'),
];
