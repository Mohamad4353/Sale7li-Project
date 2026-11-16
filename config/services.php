<?php

return [
    // Advisory-only AI Service Assistant (category suggestion + description
    // rewrite). Isolated under its own key so it never collides with a future,
    // unrelated integration. No key here = the feature is simply unavailable
    // (AiCategorySuggestionService fails closed; the booking flow is unaffected).
    'ai_assistant' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],
];
