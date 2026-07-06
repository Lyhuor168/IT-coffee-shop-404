<?php

return [
    'mail' => [
        // ...
    ],

    'aws' => [
        // ...
    ],

    // ត្រូវបន្ថែម block telegram នៅទីនេះ ↓
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

];