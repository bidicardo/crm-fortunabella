<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'method']);
}

$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    respond(500, ['ok' => false, 'error' => 'config']);
}
$config = require $configFile;

$raw = file_get_contents('php://input', false, null, 0, 20000);
$data = json_decode((string) $raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

function field(array $data, string $key, int $max): string
{
    $value = isset($data[$key]) && is_string($data[$key]) ? trim($data[$key]) : '';
    return mb_substr($value, 0, $max);
}

// Honeypot: bots fill every field. Pretend success so they don't retry.
if (field($data, 'company', 200) !== '') {
    respond(200, ['ok' => true]);
}

$name = field($data, 'name', 80);
$phone = field($data, 'phone', 40);
$email = field($data, 'email', 120);
$social = field($data, 'social', 120);
$details = field($data, 'details', 1500);
$consent = !empty($data['consent']);

$phoneDigits = preg_replace('/\D/', '', $phone);
if (mb_strlen($name) < 2 || strlen((string) $phoneDigits) !== 11 || !$consent) {
    respond(422, ['ok' => false, 'error' => 'validation']);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(422, ['ok' => false, 'error' => 'validation']);
}

// One request per IP per RATE_SECONDS.
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = sys_get_temp_dir() . '/fb_rate_' . hash('sha256', $ip);
$rateSeconds = (int) ($config['rate_seconds'] ?? 60);
if (is_file($rateFile) && time() - (int) filemtime($rateFile) < $rateSeconds) {
    respond(429, ['ok' => false, 'error' => 'rate']);
}

$esc = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');

$lines = [
    '<b>Новая заявка с сайта Fortuna Bella</b>',
    '',
    '<b>Имя:</b> ' . $esc($name),
    '<b>Телефон:</b> ' . $esc($phone),
];
if ($email !== '') {
    $lines[] = '<b>Email:</b> ' . $esc($email);
}
if ($social !== '') {
    $lines[] = '<b>Соцсеть:</b> ' . $esc($social);
}
if ($details !== '') {
    $lines[] = '';
    $lines[] = '<b>Что планируют:</b>';
    $lines[] = $esc($details);
}

// chat_id in config.php may be a single id (int/string) or an array of ids —
// the request goes out to every one of them, and as long as at least one
// accepts it, the submission counts as sent (see respond() below).
$chatIds = is_array($config['chat_id']) ? $config['chat_id'] : [$config['chat_id']];

$text = implode("\n", $lines);
$url = 'https://api.telegram.org/bot' . $config['bot_token'] . '/sendMessage';

$delivered = false;
foreach ($chatIds as $chatId) {
    $payload = http_build_query([
        'chat_id' => $chatId,
        'text' => $text,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => 'true',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = is_string($response) ? json_decode($response, true) : null;
    if ($status === 200 && !empty($result['ok'])) {
        $delivered = true;
    } else {
        error_log('Fortuna Bella: Telegram send failed for chat_id ' . $chatId . ', HTTP ' . $status . ' ' . substr((string) $response, 0, 300));
    }
}

if (!$delivered) {
    respond(502, ['ok' => false, 'error' => 'telegram']);
}

touch($rateFile);
respond(200, ['ok' => true]);
