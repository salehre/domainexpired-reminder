<?php

return [
    // Look-ahead window: items expiring from today up to today + days
    'days' => (int) env('REMINDER_DAYS', 10),

    'timezone' => env('REMINDER_TIMEZONE', 'Asia/Tehran'),

    // Where the digest email is sent (you)
    'mail_to' => env('REMINDER_MAIL_TO'),

    // Needs the WHMCS tblclients table in the same database
    'include_client_info' => filter_var(env('REMINDER_INCLUDE_CLIENT_INFO', false), FILTER_VALIDATE_BOOLEAN),

    // Also list items still "Active" whose date has already passed
    'include_overdue' => filter_var(env('REMINDER_INCLUDE_OVERDUE', false), FILTER_VALIDATE_BOOLEAN),

    // The HTTP API is disabled unless a key is set
    'api_key' => env('REMINDER_API_KEY'),
];
