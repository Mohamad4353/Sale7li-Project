<?php

/*
 * English counterpart of lang/ar/validation.php. Only the app-specific `custom`
 * message is defined here; every other validation string falls through to
 * Laravel's built-in English translations (they are merged, not replaced).
 *
 * Without this file, `__('validation.custom.phone.regex')` — used by
 * RegisterController and UpdateWorkerProfileRequest for the phone-format rule —
 * leaked the raw key "validation.custom.phone.regex" to English-locale users on
 * a failed phone check, because the framework's default `custom` block is empty.
 */

return [
    'custom' => [
        'phone' => [
            'regex' => 'Enter a valid phone number using digits only, optionally starting with a +.',
        ],
    ],
];
