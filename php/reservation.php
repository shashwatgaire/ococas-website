<?php
/**
 * ococas reservation handler
 * Receives form data from reserve.html and sends a notification email.
 * Configure NOTIFY_EMAILS below with the restaurant's notification addresses.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ---- Configuration ----
define('NOTIFY_EMAILS', ['reservations@ococas.com', 'shashwatgaire0@gmail.com', 'ococas1@gmail.com']); // restaurant inbox + owner copies
define('FROM_EMAIL',   'noreply@ococas.pt');     // sender (must be valid on your cPanel domain)
define('SITE_NAME',    'ococas');
define('CALL_THRESHOLD', 4); // parties larger than this are called to confirm instead of auto-accepted

// ---- Only accept POST ----
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// ---- Sanitise input ----
function clean(string $v): string {
    return htmlspecialchars(trim(strip_tags($v)), ENT_QUOTES, 'UTF-8');
}

$name     = clean($_POST['name']     ?? '');
$email    = clean($_POST['email']    ?? '');
$phone    = clean($_POST['phone']    ?? '');
$location = clean($_POST['location'] ?? '');
$date     = clean($_POST['date']     ?? '');
$service  = clean($_POST['service']  ?? '');
$slot     = clean($_POST['slot']     ?? '');
$guests   = (int) ($_POST['guests']  ?? 0);
$occasion = clean($_POST['occasion'] ?? '');
$notes    = clean($_POST['notes']    ?? '');
$ref      = clean($_POST['ref']      ?? '');

// Basic validation
if (!$name || !filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) || !$phone || !$date || !$slot) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
    exit;
}

$needsCall = $guests > CALL_THRESHOLD;

// ---- Build notification email (to restaurant + owner) ----
$subjectTag = $needsCall ? 'CALL REQUIRED' : $ref;
$subject = "[{$subjectTag}] New reservation — {$name} · {$location} · {$date} · {$slot}";

$body = "New reservation at " . SITE_NAME . "\n\n";
$body .= "Reference : {$ref}\n";
$body .= "Restaurant : {$location}\n";
$body .= "Date       : {$date}\n";
$body .= "Service    : {$service}\n";
$body .= "Time       : {$slot}\n";
$body .= "Guests     : {$guests}\n";
$body .= "Status     : " . ($needsCall ? "NEEDS PHONE CONFIRMATION (party over " . CALL_THRESHOLD . ")" : "Auto-accepted") . "\n\n";
$body .= "Guest name  : {$name}\n";
$body .= "Email       : " . ($_POST['email'] ?? '') . "\n";
$body .= "Phone       : {$phone}\n";
$body .= "Occasion    : {$occasion}\n";
$body .= "Notes       : {$notes}\n";

$headers  = "From: " . SITE_NAME . " <" . FROM_EMAIL . ">\r\n";
$headers .= "Reply-To: {$name} <" . ($_POST['email'] ?? '') . ">\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

foreach (NOTIFY_EMAILS as $notifyAddress) {
    mail($notifyAddress, $subject, $body, $headers);
}

// ---- Confirmation email (to guest) ----
if ($needsCall) {
    $confirmSubject = "Your reservation request at ococas — {$ref}";
    $confirmBody = "Dear {$name},\n\n";
    $confirmBody .= "Thank you for your reservation request. Here are the details:\n\n";
    $confirmBody .= "Reference  : {$ref}\n";
    $confirmBody .= "Restaurant : {$location}\n";
    $confirmBody .= "Date       : {$date}\n";
    $confirmBody .= "Time       : {$slot}\n";
    $confirmBody .= "Guests     : {$guests}\n\n";
    $confirmBody .= "As your party is larger than " . CALL_THRESHOLD . ", our team will call you at {$phone} shortly to confirm your table.\n\n";
    $confirmBody .= "We look forward to welcoming you.\n\n";
    $confirmBody .= "— The team at ococas\n";
    $confirmBody .= "R. dos Correeiros 177, 1100-571 Lisboa, Portugal\n";
} else {
    $confirmSubject = "Your reservation at ococas — {$ref}";
    $confirmBody = "Dear {$name},\n\n";
    $confirmBody .= "Your table at ococas is confirmed. Here are the details:\n\n";
    $confirmBody .= "Reference  : {$ref}\n";
    $confirmBody .= "Restaurant : {$location}\n";
    $confirmBody .= "Date       : {$date}\n";
    $confirmBody .= "Time       : {$slot}\n";
    $confirmBody .= "Guests     : {$guests}\n\n";
    $confirmBody .= "We hold your table for 15 minutes. If you need to cancel or change your reservation,\n";
    $confirmBody .= "please call us at +351 920 038 770 or reply to this email.\n\n";
    $confirmBody .= "We look forward to welcoming you.\n\n";
    $confirmBody .= "— The team at ococas\n";
    $confirmBody .= "R. dos Correeiros 177, 1100-571 Lisboa, Portugal\n";
}

$confirmHeaders  = "From: " . SITE_NAME . " <" . FROM_EMAIL . ">\r\n";
$confirmHeaders .= "X-Mailer: PHP/" . phpversion();

mail($_POST['email'] ?? '', $confirmSubject, $confirmBody, $confirmHeaders);

echo json_encode(['ok' => true, 'ref' => $ref]);
