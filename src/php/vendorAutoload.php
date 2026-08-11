<?php

function locateVendorAutoloadPath(): ?string
{
    $candidates = [
        __DIR__ . '/../../vendor/autoload.php',
        __DIR__ . '/../vendor/autoload.php',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return null;
}

function ensureVendorAutoload(): bool
{
    if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        return true;
    }

    $autoloadPath = locateVendorAutoloadPath();
    if ($autoloadPath === null) {
        return false;
    }

    require_once $autoloadPath;
    return true;
}

?>
