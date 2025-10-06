<?php
// ...existing code...
<?php
// Configurações SMTP — substitua pelos seus dados
const SMTP_HOST = 'smtp.exemplo.com';
const SMTP_PORT = 587; // 465 para SSL, 587 para TLS
const SMTP_USER = 'usuario@exemplo.com';
const SMTP_PASS = 'sua_senha_smtp';
const FROM_EMAIL = 'no-reply@seudominio.com';
const FROM_NAME  = 'Site - Pedro Rudi';
const TO_EMAIL   = 'seu-email@exemplo.com';
const TO_NAME    = 'Pedro Gabriel';

// Autoload do Composer (ajuste caminho se necessário)
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

// Recupera e sanitiza
$nome = isset($_POST['nome']) ? trim(strip_tags($_POST['nome'])) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$mensagem = isset($_POST['mensagem']) ? trim($_POST['mensagem']) : '';

// Limites
$nome = mb_substr($nome, 0, 255);
$mensagem = mb_substr($mensagem, 0, 8000);

// Validações básicas
if ($nome === '' || $email === '' || $mensagem === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /?status=error&msg=invalid');
    exit;
}

// Previna header injection
if (preg_match("/[\r\n]/", $nome) || preg_match("/[\r\n]/", $email)) {
    header('Location: /?status=error&msg=invalid');
    exit;
}

// Monta corpo
$body  = "Você recebeu uma nova mensagem do formulário de contato.\n\n";
$body .= "Nome: {$nome}\n";
$body .= "Email: {$email}\n";
$body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido') . "\n";
$body .= "User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido') . "\n\n";
$body .= "Mensagem:\n{$mensagem}\n";

try {
    $mail = new PHPMailer(true);
    // SMTP
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // ou PHPMailer::ENCRYPTION_SMTPS
    $mail->Port       = SMTP_PORT;

    // Remetente / Destinatário
    $mail->setFrom(FROM_EMAIL, FROM_NAME);
    $mail->addAddress(TO_EMAIL, TO_NAME);
    $mail->addReplyTo($email, $nome);

    // Conteúdo
    $mail->isHTML(false);
    $mail->Subject = "Mensagem do site — {$nome}";
    $mail->Body    = $body;

    $mail->send();
    header('Location: /?status=success');
    exit;
} catch (Exception $e) {
    // Para debug local, remova em produção
    error_log('Mail error: ' . $mail->ErrorInfo);
    header('Location: /?status=error&msg=mail_failed');
    exit;
}
?>
// ...existing code...