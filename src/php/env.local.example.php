<?php

// Template for src/php/env.local.php.
// Copy this file to env.local.php and fill in real values; env.local.php is
// gitignored. Anything set as a real environment variable (Apache SetEnv,
// system env) takes precedence over this file.

return [
    // Remote MySQL
    'DB_REMOTE_HOST' => 'your-mysql-host',
    'DB_REMOTE_USER' => 'your-mysql-user',
    'DB_REMOTE_PASS' => 'your-mysql-password',
    'DB_REMOTE_NAME' => 'your-database-name',
    'DB_REMOTE_PORT' => '3306',

    // Local MySQL (WAMP)
    'DB_LOCAL_HOST' => 'localhost',
    'DB_LOCAL_USER' => 'root',
    'DB_LOCAL_PASS' => '',
    'DB_LOCAL_NAME' => 'facilitate',
    'DB_LOCAL_PORT' => '3306',

    // Outbound SMTP
    'MAIL_SMTP_HOST' => 'smtp.example.com',
    'MAIL_SMTP_PORT' => '465',
    'MAIL_SMTP_SECURE' => 'ssl',
    'MAIL_SMTP_USER' => 'you@example.com',
    'MAIL_SMTP_PASS' => 'your-smtp-password',
];
