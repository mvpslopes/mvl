<?php
header('Content-Type: application/json; charset=utf-8');

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'message' => 'Método não permitido.']);
  exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['name'], $data['email'], $data['message'])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Dados inválidos.']);
  exit;
}

$name = trim($data['name']);
$email = trim($data['email']);
$message = trim($data['message']);

if ($name === '' || $email === '' || $message === '') {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'Todos os campos são obrigatórios.']);
  exit;
}

$to = 'contato@mvlopes.com.br';
$subject = 'Novo contato pelo site MVLopes';

$body = "Você recebeu uma nova mensagem pelo site.\n\n";
$body .= "Nome: {$name}\n";
$body .= "E-mail: {$email}\n\n";
$body .= "Mensagem:\n{$message}\n";

$headers = "From: {$name} <{$email}>\r\n";
$headers .= "Reply-To: {$email}\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = @mail($to, $subject, $body, $headers);

if ($sent) {
  echo json_encode(['ok' => true, 'message' => 'Mensagem enviada com sucesso.']);
} else {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Não foi possível enviar a mensagem. Tente novamente mais tarde.']);
}


