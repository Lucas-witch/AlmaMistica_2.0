<?php
// comentario.php
require_once __DIR__ . '/auth.php';       // garante session_start e populacao de $_SESSION
require_once __DIR__ . '/conexao.php';    // garante $conn

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método inválido');
}

$post_id = intval($_POST['post_id'] ?? 0);
$mensagem = trim($_POST['mensagem'] ?? '');
$nome = trim($_POST['nome'] ?? '');

if ($post_id <= 0 || $mensagem === '') {
    header('Location: post.php?id=' . $post_id . '&erro=comentario_vazio');
    exit;
}

// prepara campos
$usuario_id = null;
$is_admin = 0;
if (!empty($_SESSION['user_id'])) {
    $usuario_id = intval($_SESSION['user_id']);

    // prefira usar role vindo da sessão
    $role = $_SESSION['role'] ?? '';
    if (in_array($role, ['admin','administrator','root'], true)) {
        $is_admin = 1;
    }
}

// se nome não foi enviado e usuário logado, usa nome da sessão
if ($nome === '' && !empty($_SESSION['user_name'])) {
    $nome = $_SESSION['user_name'];
}
if ($nome === '') $nome = 'Visitante';

$sql = "INSERT INTO comentarios (usuario_id, nome, mensagem, data, is_admin)
        VALUES (?, ?, ?, NOW(), ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    error_log('Erro prepare comentario: ' . $conn->error);
    header('Location: post.php?id=' . $post_id . '&erro=db');
    exit;
}
$stmt->bind_param('issi', $usuario_id, $nome, $mensagem, $is_admin);
$ok = $stmt->execute();
if (!$ok) {
    error_log('Erro execute comentario: ' . $stmt->error);
    header('Location: post.php?id=' . $post_id . '&erro=db_exec');
    exit;
}
$stmt->close();

// redireciona de volta
header('Location: post.php?id=' . $post_id . '&comentario=ok');
exit;
