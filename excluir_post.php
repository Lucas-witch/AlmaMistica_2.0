<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexao.php';

if (!has_role(['admin','editor'])) {
    http_response_code(403);
    die('Acesso negado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Requisição inválida');
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    die('ID inválido');
}

$stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
if (!$stmt) {
    die('Erro prepare: ' . $conn->error);
}
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
if (!$ok) {
    die('Erro ao excluir: ' . $stmt->error);
}
$stmt->close();
$conn->close();

header('Location: index.php?excluido=1');
exit;
