<?php

declare(strict_types=1);

return [
    'path' => 'triage',
    'middleware' => ['web'],
    'mailbox_address' => null,
    'reply_to_address' => null,
    'from_name' => (string) config('app.name'),
    'from_address' => (string) config('mail.from.address'),
    'user_model' => 'App\\Models\\User',
];
