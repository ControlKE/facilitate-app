<?php

require_once __DIR__ . '/vendorAutoload.php';

const MESSAGE_ROUTING_PERMISSION_KEY = 'inbox.email_routing';
const MESSAGE_CATEGORY_GENERAL_ENQUIRIES = 'general_enquiries';
const MESSAGE_CATEGORY_COMPLAINTS = 'complaints';
const MESSAGE_CATEGORY_CARE_THANKS = 'care_thanks';
const MESSAGE_CATEGORY_JOB_APPLICATIONS = 'job_applications';
const MESSAGE_ROUTING_DEFAULT_RECIPIENT = 'steve@facilitatecareservices.co.uk';
const MESSAGE_ROUTING_LEGACY_RECIPIENT = 'steve@facilitatecare.co.uk';
const MESSAGE_ROUTING_DEFAULT_SENDER = 'steve@facilitatecareservices.co.uk';
const MESSAGE_ROUTING_DEFAULT_SMTP_HOST = 'send.one.com';
const MESSAGE_ROUTING_DEFAULT_SMTP_PORT = 465;
const MESSAGE_ROUTING_DEFAULT_SMTP_SECURE = 'ssl';
const MESSAGE_ROUTING_DEFAULT_SMTP_USER = 'steve@facilitatecareservices.co.uk';
const MESSAGE_ROUTING_DEFAULT_SMTP_PASS = '';

function messageRoutingCatalog(): array
{
    return [
        MESSAGE_CATEGORY_GENERAL_ENQUIRIES => [
            'label' => 'General Enquiries',
            'subject' => 'Website General Enquiry',
        ],
        MESSAGE_CATEGORY_COMPLAINTS => [
            'label' => 'Complaints',
            'subject' => 'Website Complaint',
        ],
        MESSAGE_CATEGORY_CARE_THANKS => [
            'label' => 'Thank a Caregiver',
            'subject' => 'Website Caregiver Thank You',
        ],
        MESSAGE_CATEGORY_JOB_APPLICATIONS => [
            'label' => 'Job Applications',
            'subject' => 'Website Job Application',
        ],
    ];
}

function normalizeMessageCategoryKey($value): string
{
    $normalized = strtolower(trim((string) $value));
    $map = [
        'general_enquiries' => MESSAGE_CATEGORY_GENERAL_ENQUIRIES,
        'general-enquiries' => MESSAGE_CATEGORY_GENERAL_ENQUIRIES,
        'general enquiries' => MESSAGE_CATEGORY_GENERAL_ENQUIRIES,
        'contact' => MESSAGE_CATEGORY_GENERAL_ENQUIRIES,
        'contact_form' => MESSAGE_CATEGORY_GENERAL_ENQUIRIES,
        'contactform' => MESSAGE_CATEGORY_GENERAL_ENQUIRIES,
        'complaints' => MESSAGE_CATEGORY_COMPLAINTS,
        'complaint' => MESSAGE_CATEGORY_COMPLAINTS,
        'care_thanks' => MESSAGE_CATEGORY_CARE_THANKS,
        'care-thanks' => MESSAGE_CATEGORY_CARE_THANKS,
        'care thanks' => MESSAGE_CATEGORY_CARE_THANKS,
        'thanks' => MESSAGE_CATEGORY_CARE_THANKS,
        'carerthanks' => MESSAGE_CATEGORY_CARE_THANKS,
        'carer_thanks' => MESSAGE_CATEGORY_CARE_THANKS,
        'job_applications' => MESSAGE_CATEGORY_JOB_APPLICATIONS,
        'job-applications' => MESSAGE_CATEGORY_JOB_APPLICATIONS,
        'job applications' => MESSAGE_CATEGORY_JOB_APPLICATIONS,
        'jobapplication' => MESSAGE_CATEGORY_JOB_APPLICATIONS,
        'jobapplications' => MESSAGE_CATEGORY_JOB_APPLICATIONS,
        'jobs' => MESSAGE_CATEGORY_JOB_APPLICATIONS,
    ];

    return $map[$normalized] ?? '';
}

function defaultMessageRoutingRecipient(): string
{
    if (function_exists('envValue')) {
        return trim((string) envValue('MESSAGE_ROUTING_DEFAULT_RECIPIENTS', envValue('MAIL_DEFAULT_RECIPIENT', MESSAGE_ROUTING_DEFAULT_RECIPIENT)));
    }

    return MESSAGE_ROUTING_DEFAULT_RECIPIENT;
}

function normalizeRecipientAlias(string $email): string
{
    $normalized = strtolower(trim($email));
    if ($normalized === MESSAGE_ROUTING_LEGACY_RECIPIENT) {
        return MESSAGE_ROUTING_DEFAULT_RECIPIENT;
    }

    return $normalized;
}

function defaultMessageRoutingSettings(): array
{
    $defaults = [];
    foreach (messageRoutingCatalog() as $categoryKey => $meta) {
        $defaults[$categoryKey] = [
            'categoryKey' => $categoryKey,
            'label' => $meta['label'],
            'recipients' => defaultMessageRoutingRecipient(),
            'updatedAt' => null,
            'updatedByAccountId' => null,
        ];
    }

    return $defaults;
}

function ensureMessageRoutingSettingsSchema(mysqli $conn): void
{
    $createSql = <<<SQL
CREATE TABLE IF NOT EXISTS `message_routing_settings` (
    `category_key` VARCHAR(80) NOT NULL,
    `recipient_emails` TEXT NOT NULL,
    `updated_by_account_id` INT UNSIGNED NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`category_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

    if (!$conn->query($createSql)) {
        throw new RuntimeException('Failed to initialize message routing settings table.');
    }
}

function parseRecipientTokens($value): array
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return [];
    }

    $tokens = preg_split('/[\r\n,;]+/', $raw) ?: [];
    $clean = [];
    foreach ($tokens as $token) {
        $email = trim((string) $token);
        if ($email === '') {
            continue;
        }
        $lower = normalizeRecipientAlias($email);
        if (!in_array($lower, $clean, true)) {
            $clean[] = $lower;
        }
    }

    return $clean;
}

function normalizeRecipientString($value): string
{
    $tokens = parseRecipientTokens($value);
    return implode(', ', $tokens);
}

function validateMessageRoutingInput(array $input): array
{
    $normalized = [];
    $errors = [];

    foreach (messageRoutingCatalog() as $categoryKey => $meta) {
        $rawValue = $input[$categoryKey] ?? '';
        $tokens = parseRecipientTokens($rawValue);
        $invalid = [];

        foreach ($tokens as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalid[] = $email;
            }
        }

        if (!empty($invalid)) {
            $errors[$categoryKey] = sprintf('Invalid email address%s: %s', count($invalid) > 1 ? 'es' : '', implode(', ', $invalid));
            continue;
        }

        $normalized[$categoryKey] = implode(', ', $tokens);
    }

    return [
        'normalized' => $normalized,
        'errors' => $errors,
    ];
}

function getMessageRoutingSettings(mysqli $conn): array
{
    ensureMessageRoutingSettingsSchema($conn);
    $settings = defaultMessageRoutingSettings();

    $result = $conn->query('SELECT category_key, recipient_emails, updated_by_account_id, updated_at FROM message_routing_settings');
    if ($result === false) {
        throw new RuntimeException('Failed to load message routing settings.');
    }

    while ($row = $result->fetch_assoc()) {
        $categoryKey = normalizeMessageCategoryKey($row['category_key'] ?? '');
        if ($categoryKey === '' || !isset($settings[$categoryKey])) {
            continue;
        }

        $settings[$categoryKey]['recipients'] = normalizeRecipientString($row['recipient_emails'] ?? '');
        $settings[$categoryKey]['updatedAt'] = (string) ($row['updated_at'] ?? '');
        $settings[$categoryKey]['updatedByAccountId'] = isset($row['updated_by_account_id']) ? (int) $row['updated_by_account_id'] : null;
    }

    return $settings;
}

function saveMessageRoutingSettings(mysqli $conn, array $input, int $updatedByAccountId = 0): array
{
    ensureMessageRoutingSettingsSchema($conn);
    $validated = validateMessageRoutingInput($input);
    if (!empty($validated['errors'])) {
        throw new InvalidArgumentException('One or more routing email addresses are invalid.');
    }

    $stmt = $conn->prepare('INSERT INTO message_routing_settings (category_key, recipient_emails, updated_by_account_id) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE recipient_emails = VALUES(recipient_emails), updated_by_account_id = VALUES(updated_by_account_id), updated_at = CURRENT_TIMESTAMP');
    if ($stmt === false) {
        throw new RuntimeException('Failed to prepare message routing save query.');
    }

    foreach (messageRoutingCatalog() as $categoryKey => $meta) {
        $recipients = $validated['normalized'][$categoryKey] ?? '';
        $actorId = $updatedByAccountId > 0 ? $updatedByAccountId : 0;
        $stmt->bind_param('ssi', $categoryKey, $recipients, $actorId);
        if (!$stmt->execute()) {
            $stmt->close();
            throw new RuntimeException('Failed to persist message routing settings.');
        }
    }

    $stmt->close();
    return getMessageRoutingSettings($conn);
}

function notificationFromAddress(): string
{
    $candidates = [];

    if (function_exists('envValue')) {
        $candidates[] = trim((string) envValue('MESSAGE_ROUTING_FROM_ADDRESS', ''));
        $candidates[] = trim((string) envValue('MAIL_FROM_ADDRESS', ''));
        $candidates[] = trim((string) envValue('SMTP_FROM_ADDRESS', ''));
        $candidates[] = trim((string) envValue('MAIL_SMTP_USER', ''));
        $candidates[] = trim((string) envValue('SMTP_USER', ''));
    }

    $candidates[] = MESSAGE_ROUTING_DEFAULT_SENDER;

    foreach ($candidates as $candidate) {
        if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
            return strtolower($candidate);
        }
    }

    return MESSAGE_ROUTING_DEFAULT_SENDER;
}

function notificationFromName(): string
{
    if (function_exists('envValue')) {
        $configured = trim((string) envValue('MAIL_FROM_NAME', 'Facilitate Care Services Website'));
        if ($configured !== '') {
            return $configured;
        }
    }

    return 'Facilitate Care Services Website';
}

function formatHeaderDisplayName(string $value): string
{
    $sanitized = preg_replace('/[\r\n]+/', ' ', $value);
    return trim((string) $sanitized);
}

function messageRoutingSmtpHost(): string
{
    if (!function_exists('envValue')) {
        return MESSAGE_ROUTING_DEFAULT_SMTP_HOST;
    }

    return trim((string) envValue('MAIL_SMTP_HOST', envValue('SMTP_HOST', MESSAGE_ROUTING_DEFAULT_SMTP_HOST)));
}

function messageRoutingSmtpPort(): int
{
    if (!function_exists('envInt')) {
        return MESSAGE_ROUTING_DEFAULT_SMTP_PORT;
    }

    return envInt('MAIL_SMTP_PORT', envInt('SMTP_PORT', MESSAGE_ROUTING_DEFAULT_SMTP_PORT));
}

function messageRoutingSmtpUsername(): string
{
    if (!function_exists('envValue')) {
        return MESSAGE_ROUTING_DEFAULT_SMTP_USER;
    }

    return trim((string) envValue('MAIL_SMTP_USER', envValue('SMTP_USER', MESSAGE_ROUTING_DEFAULT_SMTP_USER)));
}

function messageRoutingSmtpPassword(): string
{
    if (!function_exists('envValue')) {
        return MESSAGE_ROUTING_DEFAULT_SMTP_PASS;
    }

    return (string) envValue('MAIL_SMTP_PASS', envValue('SMTP_PASSWORD', MESSAGE_ROUTING_DEFAULT_SMTP_PASS));
}

function messageRoutingSmtpSecure(): string
{
    if (!function_exists('envValue')) {
        return MESSAGE_ROUTING_DEFAULT_SMTP_SECURE;
    }

    return strtolower(trim((string) envValue('MAIL_SMTP_SECURE', MESSAGE_ROUTING_DEFAULT_SMTP_SECURE)));
}

function logMessageRoutingFailure(string $categoryKey, array $recipients, string $transport, string $error): void
{
    $detail = trim($error);
    if ($detail === '') {
        return;
    }

    $message = sprintf(
        'Message routing notification failed [%s] via %s to %s: %s',
        $categoryKey,
        $transport !== '' ? $transport : 'unknown',
        !empty($recipients) ? implode(', ', $recipients) : '(no recipients)',
        $detail
    );

    error_log($message);
}

function sendPlainTextMessage(array $recipients, string $subject, string $body, ?string $replyToEmail = null, ?string $replyToName = null): array
{
    if (empty($recipients)) {
        return [
            'sent' => false,
            'transport' => 'none',
            'error' => 'No recipient email address is configured for this message category.',
        ];
    }

    $subject = trim($subject);
    $body = trim($body);
    $fromAddress = notificationFromAddress();
    $fromName = notificationFromName();

    if (messageRoutingSmtpHost() !== '' && ensureVendorAutoload() && class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = messageRoutingSmtpHost();

            $smtpPort = messageRoutingSmtpPort();
            $smtpUser = messageRoutingSmtpUsername();
            $smtpPass = messageRoutingSmtpPassword();
            $smtpSecure = messageRoutingSmtpSecure();

            if ($smtpUser !== '' || $smtpPass !== '') {
                $mail->SMTPAuth = true;
                $mail->Username = $smtpUser;
                $mail->Password = $smtpPass;
            } else {
                $mail->SMTPAuth = false;
            }

            if ($smtpSecure === 'tls') {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($smtpSecure === 'none' || $smtpSecure === '') {
                $mail->SMTPSecure = false;
            } else {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            }

            $mail->Port = $smtpPort;
            $mail->CharSet = 'utf-8';
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            $mail->setFrom($fromAddress, $fromName);

            foreach ($recipients as $recipient) {
                $mail->addAddress($recipient);
            }

            if ($replyToEmail !== null && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyToEmail, $replyToName !== null ? formatHeaderDisplayName($replyToName) : '');
            }

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();

            return [
                'sent' => true,
                'transport' => 'smtp',
                'error' => '',
            ];
        } catch (Throwable $exception) {
            return [
                'sent' => false,
                'transport' => 'smtp',
                'error' => $exception->getMessage(),
            ];
        }
    }

    if (!function_exists('mail')) {
        return [
            'sent' => false,
            'transport' => 'none',
            'error' => 'PHP mail() is not available on this server.',
        ];
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . formatHeaderDisplayName($fromName) . ' <' . $fromAddress . '>',
        'X-Mailer: PHP/' . PHP_VERSION,
    ];

    if ($replyToEmail !== null && filter_var($replyToEmail, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . formatHeaderDisplayName($replyToName !== null ? $replyToName : $replyToEmail) . ' <' . $replyToEmail . '>';
    }

    $to = implode(', ', $recipients);
    $headersString = implode("\r\n", $headers);
    $returnPath = $fromAddress;

    if (stripos(PHP_OS, 'WIN') === 0) {
        $sent = @mail($to, $subject, $body, $headersString);
    } else {
        $sent = @mail($to, $subject, $body, $headersString, '-f' . $returnPath);
    }

    return [
        'sent' => (bool) $sent,
        'transport' => 'mail',
        'error' => $sent ? '' : 'PHP mail() returned false.',
    ];
}

function buildNotificationSummaryLines(array $fields): array
{
    $lines = [];
    foreach ($fields as $label => $value) {
        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            continue;
        }

        $lines[] = $label . ': ' . $stringValue;
    }

    return $lines;
}

function buildCategoryNotificationContent(string $categoryKey, array $payload): array
{
    $catalog = messageRoutingCatalog();
    $meta = $catalog[$categoryKey] ?? [
        'label' => 'Website Message',
        'subject' => 'Website Message',
    ];

    $submittedAt = trim((string) ($payload['submittedAt'] ?? ''));
    if ($submittedAt === '') {
        $submittedAt = date('Y-m-d H:i:s');
    }

    $fields = ['Submitted At' => $submittedAt];

    if ($categoryKey === MESSAGE_CATEGORY_GENERAL_ENQUIRIES) {
        $fields += [
            'Name' => $payload['name'] ?? '',
            'Email' => $payload['email'] ?? '',
            'Phone' => $payload['phone'] ?? '',
        ];
    } elseif ($categoryKey === MESSAGE_CATEGORY_COMPLAINTS) {
        $fields += [
            'Title' => $payload['title'] ?? '',
            'First Name' => $payload['firstName'] ?? '',
            'Last Name' => $payload['lastName'] ?? '',
            'Email' => $payload['email'] ?? '',
            'Phone' => $payload['phone'] ?? '',
        ];
    } elseif ($categoryKey === MESSAGE_CATEGORY_CARE_THANKS) {
        $fields += [
            'Title' => $payload['title'] ?? '',
            'First Name' => $payload['firstName'] ?? '',
            'Last Name' => $payload['lastName'] ?? '',
            'Email' => $payload['email'] ?? '',
            'Phone' => $payload['phone'] ?? '',
            'Caregiver Name(s)' => $payload['caregiver'] ?? '',
        ];
    } elseif ($categoryKey === MESSAGE_CATEGORY_JOB_APPLICATIONS) {
        $fields += [
            'Title' => $payload['title'] ?? '',
            'Full Name' => $payload['fullName'] ?? '',
            'Email' => $payload['email'] ?? '',
            'Phone' => $payload['phone'] ?? '',
            'Job Type' => $payload['jobType'] ?? '',
            'Domiciliary Experience' => $payload['hasDomiciliaryExperience'] ?? '',
            'Experience Duration' => $payload['experienceDuration'] ?? '',
            'Driver License' => $payload['hasDriverLicense'] ?? '',
            'License Type' => $payload['licenseType'] ?? '',
            'International License Expiry' => $payload['internationalLicenseExpiry'] ?? '',
            'UK License Type' => $payload['ukLicenseType'] ?? '',
            'City' => $payload['city'] ?? '',
            'Residence Area' => $payload['residenceArea'] ?? '',
            'Residence Duration' => $payload['residenceDuration'] ?? '',
        ];
    }

    $message = trim((string) ($payload['message'] ?? ''));
    $bodyLines = [
        $meta['label'] . ' notification from the website.',
        '',
    ];
    $bodyLines = array_merge($bodyLines, buildNotificationSummaryLines($fields));

    if ($message !== '') {
        $bodyLines[] = '';
        $bodyLines[] = 'Message:';
        $bodyLines[] = $message;
    }

    $replyToEmail = trim((string) ($payload['replyToEmail'] ?? ''));
    if ($replyToEmail === '') {
        $replyToEmail = trim((string) ($payload['email'] ?? ''));
    }

    $replyToName = trim((string) ($payload['replyToName'] ?? ''));
    if ($replyToName === '') {
        $replyToName = trim((string) ($payload['fullName'] ?? $payload['name'] ?? ''));
        if ($replyToName === '') {
            $firstName = trim((string) ($payload['firstName'] ?? ''));
            $lastName = trim((string) ($payload['lastName'] ?? ''));
            $replyToName = trim($firstName . ' ' . $lastName);
        }
    }

    return [
        'subject' => $meta['subject'],
        'body' => implode("\n", $bodyLines),
        'replyToEmail' => $replyToEmail,
        'replyToName' => $replyToName,
    ];
}

function dispatchCategoryNotification(mysqli $conn, string $categoryKey, array $payload): array
{
    $normalizedCategory = normalizeMessageCategoryKey($categoryKey);
    if ($normalizedCategory === '') {
        return [
            'sent' => false,
            'transport' => 'none',
            'error' => 'Unknown message category.',
            'recipients' => [],
        ];
    }

    $settings = getMessageRoutingSettings($conn);
    $recipients = parseRecipientTokens($settings[$normalizedCategory]['recipients'] ?? '');
    if (empty($recipients)) {
        return [
            'sent' => false,
            'transport' => 'none',
            'error' => 'No recipient email address is configured for this message category.',
            'recipients' => [],
        ];
    }

    $mailPayload = buildCategoryNotificationContent($normalizedCategory, $payload);
    $result = sendPlainTextMessage(
        $recipients,
        $mailPayload['subject'],
        $mailPayload['body'],
        $mailPayload['replyToEmail'] !== '' ? $mailPayload['replyToEmail'] : null,
        $mailPayload['replyToName'] !== '' ? $mailPayload['replyToName'] : null
    );

    if (empty($result['sent'])) {
        logMessageRoutingFailure(
            $normalizedCategory,
            $recipients,
            (string) ($result['transport'] ?? ''),
            (string) ($result['error'] ?? '')
        );
    }

    $result['recipients'] = $recipients;
    return $result;
}

function safeDispatchCategoryNotification(mysqli $conn, string $categoryKey, array $payload): array
{
    try {
        return dispatchCategoryNotification($conn, $categoryKey, $payload);
    } catch (Throwable $exception) {
        logMessageRoutingFailure(
            normalizeMessageCategoryKey($categoryKey),
            [],
            'none',
            $exception->getMessage()
        );
        return [
            'sent' => false,
            'transport' => 'none',
            'error' => $exception->getMessage(),
            'recipients' => [],
        ];
    }
}

?>
