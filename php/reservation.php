<?php
/**
 * ococas reservation handler
 * Receives form data from reserve.html and sends a notification email.
 * Configure $NOTIFY_EMAIL below with the restaurant's email address.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// ---- Configuration ----
define('NOTIFY_EMAIL', 'reservas@ococas.pt');   // restaurant inbox
define('FROM_EMAIL',   'noreply@ococas.pt');     // sender (must be valid on your cPanel domain)
define('SITE_NAME',    'ococas');

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

// ---- Build notification email (to restaurant) ----
$subject = "[{$ref}] New reservation — {$name} · {$location} · {$date} · {$slot}";

$body = "New reservation at " . SITE_NAME . "\n\n";
$body .= "Reference : {$ref}\n";
$body .= "Restaurant : {$location}\n";
$body .= "Date       : {$date}\n";
$body .= "Service    : {$service}\n";
$body .= "Time       : {$slot}\n";
$body .= "Guests     : {$guests}\n\n";
$body .= "Guest name  : {$name}\n";
$body .= "Email       : " . ($_POST['email'] ?? '') . "\n";
$body .= "Phone       : {$phone}\n";
$body .= "Occasion    : {$occasion}\n";
$body .= "Notes       : {$notes}\n";

$headers  = "From: " . SITE_NAME . " <" . FROM_EMAIL . ">\r\n";
$headers .= "Reply-To: {$name} <" . ($_POST['email'] ?? '') . ">\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

mail(NOTIFY_EMAIL, $subject, $body, $headers);

// ---- Confirmation email (to guest) ----
$confirmSubject = "Your reservation at ococas — {$ref}";
$confirmBody = "Dear {$name},\n\n";
$confirmBody .= "Your table at ococas is confirmed. Here are the details:\n\n";
$confirmBody .= "Reference  : {$ref}\n";
$confirmBody .= "Restaurant : {$location}\n";
$confirmBody .= "Date       : {$date}\n";
$confirmBody .= "Time       : {$slot}\n";
$confirmBody .= "Guests     : {$guests}\n\n";
$confirmBody .= "We hold your table for 15 minutes. If you need to cancel or change your reservation,\n";
$confirmBody .= "please call us at +351 21 100 0000 or reply to this email.\n\n";
$confirmBody .= "We look forward to welcoming you.\n\n";
$confirmBody .= "— The team at ococas\n";
$confirmBody .= "Rua da Escola Politécnica 27, Príncipe Real, Lisboa\n";

$confirmHeaders  = "From: " . SITE_NAME . " <" . FROM_EMAIL . ">\r\n";
$confirmHeaders .= "X-Mailer: PHP/" . phpversion();

mail($_POST['email'] ?? '', $confirmSubject, $confirmBody, $confirmHeaders);

echo json_encode(['ok' => true, 'ref' => $ref]);
