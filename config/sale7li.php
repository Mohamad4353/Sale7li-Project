<?php

/*
 * SALE7LI application settings that are operational rather than framework-level.
 * Kept out of code so operators can tune them per environment.
 */
return [
    // Where "contact support" points. Set SUPPORT_EMAIL in the environment.
    'support_email' => env('SUPPORT_EMAIL', 'support@sale7li.example'),

    // SALE7LI's own WhatsApp line for "contact support on WhatsApp". Digits only
    // (with or without a leading +); the button is hidden when this is blank.
    'support_whatsapp' => env('SUPPORT_WHATSAPP', ''),

    // A still-unassigned request older than this many hours is flagged "stale"
    // in the admin queue so operators can act before the customer is left waiting.
    'stale_request_hours' => (int) env('STALE_REQUEST_HOURS', 24),

    // Manual WHISH payment. No API integration — the customer transfers to this
    // account and submits the WHISH reference for an admin to verify. Keep the
    // real account details in .env, never in source.
    'whish' => [
        'account_name' => env('WHISH_ACCOUNT_NAME', ''),
        'account_number' => env('WHISH_ACCOUNT_NUMBER', ''),
        'instructions' => env('WHISH_INSTRUCTIONS', ''),
    ],
];
