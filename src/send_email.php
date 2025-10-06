<?php
// Configurar: altere para o email que deve receber as mensagens do formulário
$to = 'pedrorudireis@gmail.com'; // <<< substitua por seu email
$fromDomain = 'seudominio.com'; // <<< substitua pelo seu domínio (usado no From)

// Verifica se foi submetido via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

// Recupera e sanitiza campos
$nome = isset($_POST['nome']) ? trim(strip_tags($_POST['nome'])) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';

// Limites simples
$nome = mb_substr($nome, 0, 255);
$mensagem = mb_substr($mensagem, 0, 5000);

// Validações
if ($nome === '' || $email === '' || $mensagem === '') {
    header('Location: /?status=error&msg=empty');
    exit;
}

// Evita header injection (nova linha em nome/email)
if (preg_match("/[\r\n]/", $nome) || preg_match("/[\r\n]/", $email)) {
    header('Location: /?status=error&msg=invalid');
    exit;
}

// Valida email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /?status=error&msg=invalid_email');
    exit;
}

// Monta assunto e corpo
$subject = "Mensagem do site de $nome";
$subjectEncoded = '=?UTF-8?B?' . base64_encode($subject) . '?=';

$body  = "Você recebeu uma nova mensagem do formulário de contato.\n\n";
$body .= "Nome: $nome\n";
$body .= "Email: $email\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido') . "\n";
$body .= "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido') . "\n\n";
$body .= "Mensagem:\n$mensagem\n";

// Cabeçalhos
$headers  = "From: Website <no-reply@" . $fromDomain . ">\r\n";
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

// Envia email
$sent = mail($to, $subjectEncoded, $body, $headers);

if ($sent) {
    // Redireciona para a página inicial com status
    header('Location: /?status=success');
    exit;
} else {
    header('Location: /?status=error&msg=mail_failed');
    exit;
}
?>
