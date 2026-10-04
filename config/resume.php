<?php

/*
|--------------------------------------------------------------------------
| Private resume page (/resume)
|--------------------------------------------------------------------------
|
| The resume content never lives in the repository. It is read at request
| time from resume.json in storage/app/private (git-ignored), which
| `php artisan resume:import` builds from resume-master.md.
|
*/

return [

    'source' => env('RESUME_SOURCE_PATH', storage_path('app/private/resume-master.md')),

    'path' => env('RESUME_JSON_PATH', storage_path('app/private/resume.json')),

    // bcrypt hash of the access password. Generate with:
    //   php artisan tinker --execute="echo Hash::make('...');"
    'password_hash' => env('RESUME_PASSWORD_HASH'),

    // When true the locked page prints the password under the field.
    'show_password_hint' => (bool) env('RESUME_SHOW_PASSWORD_HINT', true),

    // The hint text itself. Showing a hint needs the plain value somewhere,
    // so it lives in the server .env only, never in the repo.
    'password_hint' => env('RESUME_PASSWORD_HINT'),

    'unlock_minutes' => (int) env('RESUME_UNLOCK_MINUTES', 720),

    'contact_email' => env('RESUME_CONTACT_EMAIL', 'me@mfaruk.com'),

];
