<?php
declare(strict_types=1);

/*
 * Y.A.K AI Governance Document Summariser
 * Local XAMPP setup:
 * 1. Copy config.php.example to config.php
 * 2. Put your NEW API key in config.php (never share it)
 * 3. Keep config.php protected by .htaccess
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $data): never {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['error' => 'Method not allowed.']);
}

$configFile = __DIR__ . '/config.php';
if (is_file($configFile)) {
    require_once $configFile;
}

$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
    respond(500, ['error' => 'API key not configured. Copy config.php.example to config.php and add your NEW OpenAI API key.']);
}

$input = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($input)) {
    respond(400, ['error' => 'Invalid JSON request.']);
}

$title = trim((string)($input['title'] ?? 'Governance document'));
$audience = trim((string)($input['audience'] ?? 'Young people and grassroots communities'));
$documentText = trim((string)($input['document_text'] ?? ''));

if ($documentText === '') {
    respond(400, ['error' => 'Document text is required.']);
}
if (strlen($documentText) > 120000) {
    respond(413, ['error' => 'Document text is too large for this starter version. Split it into sections.']);
}

$model = getenv('OPENAI_MODEL') ?: 'gpt-4.1-mini';
$prompt = "You are an impartial governance policy research assistant supporting Y.A.K in Kenya.\n"
    . "Summarise the following document for: {$audience}.\n"
    . "Document title: {$title}.\n\n"
    . "Return a structured summary with: 1) document overview, 2) objectives, "
    . "3) key provisions, 4) possible implications for citizens and young people, "
    . "5) financial or implementation implications only where supported, "
    . "6) public participation and accountability questions, and 7) a plain-language takeaway.\n\n"
    . "Use only information supported by the supplied text. Do not invent facts, figures, clauses, "
    . "dates or page references. Distinguish explicit provisions from analysis or possible effects. "
    . "State when information is missing or unclear. This is a draft requiring human review.\n\n"
    . "DOCUMENT TEXT:\n" . $documentText;

$payload = ['model' => $model, 'input' => $prompt];

$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_CONNECTTIMEOUT => 20,
    CURLOPT_TIMEOUT => 180
]);

$body = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($body === false) {
    error_log('OpenAI connection error: ' . $curlError);
    respond(502, ['error' => 'Could not connect to OpenAI. Check your internet connection and PHP cURL.']);
}

$data = json_decode($body, true);
if ($httpCode < 200 || $httpCode >= 300) {
    $apiMessage = $data['error']['message'] ?? 'OpenAI request failed.';
    error_log('OpenAI API error (' . $httpCode . '): ' . $apiMessage);
    respond(502, ['error' => 'OpenAI API error: ' . $apiMessage]);
}

$summary = '';
foreach (($data['output'] ?? []) as $item) {
    if (($item['type'] ?? '') === 'message') {
        foreach (($item['content'] ?? []) as $part) {
            if (($part['type'] ?? '') === 'output_text') {
                $summary .= ($summary === '' ? '' : "\n") . ($part['text'] ?? '');
            }
        }
    }
}

if ($summary === '') {
    respond(502, ['error' => 'OpenAI returned no summary text.']);
}

respond(200, ['summary' => $summary]);
?>
