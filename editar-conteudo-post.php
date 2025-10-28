<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexao.php';

if (!has_role(['admin','editor'])) {
    http_response_code(403); exit('Acesso negado');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$id = intval($_POST['id'] ?? 0);
$titulo = trim($_POST['titulo'] ?? '');
$conteudo = trim($_POST['conteudo'] ?? '');

if ($id <= 0 || $titulo === '' || $conteudo === '') {
    header('Location: index.php?erro=campos');
    exit;
}

$stmt = $conn->prepare("UPDATE posts SET titulo = ?, conteudo = ?, atualizado_em = NOW() WHERE id = ?");
if (!$stmt) { die('Erro prepare: ' . $conn->error); }
$stmt->bind_param('ssi', $titulo, $conteudo, $id);
if (!$stmt->execute()) { die('Erro ao atualizar: ' . $stmt->error); }
$stmt->close();
$conn->close();

header('Location: post.php?id=' . $id . '&editado=1');
exit;
