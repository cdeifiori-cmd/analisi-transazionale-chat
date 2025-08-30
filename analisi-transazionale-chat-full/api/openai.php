<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header('Content-Type: application/json; charset=utf-8');

$apiKey = getenv('OPENAI_API_KEY');
if (!$apiKey) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'OPENAI_API_KEY non impostata']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$messages = $input['messages'] ?? null;
if (!$messages || !is_array($messages)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Payload non valido']);
  exit;
}

$payload = [
  'model' => 'gpt-4o-mini',
  'messages' => $messages,
  'temperature' => 0.7,
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . $apiKey,
    'Content-Type: application/json'
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 60
]);

$response = curl_exec($ch);
$errno = curl_errno($ch);
$err   = curl_error($ch);
curl_close($ch);

if ($errno) {
  http_response_code(502);
  echo json_encode(['ok' => false, 'error' => 'Errore cURL: '.$err]);
  exit;
}

$data = json_decode($response, true);
$text = $data['choices'][0]['message']['content'] ?? null;

if (!$text) {
  http_response_code(500);
  $apiErr = $data['error']['message'] ?? 'Risposta inattesa da OpenAI';
  echo json_encode(['ok' => false, 'error' => $apiErr]);
  exit;
}

echo json_encode(['ok' => true, 'text' => $text]);
?>