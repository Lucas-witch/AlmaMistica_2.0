<?php
// excluir_post.php (modificado para usar auth.php)

require_once 'auth.php';
require_role('admin'); // somente admin pode excluir posts

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo '<div style="color:red;text-align:center;">Requisição inválida.</div>';
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo '<div style="color:red;text-align:center;">ID inválido.</div>';
    exit;
}
$id = intval($_POST['id']);

require_once 'conexao.php'; // abre $conn (mysqli)
if ($conn->connect_error) {
    die('<div style="color:red;text-align:center;">Erro ao conectar ao banco.</div>');
}

$stmt = $conn->prepare("DELETE FROM posts WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: index.php");
exit;
?>