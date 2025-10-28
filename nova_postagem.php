<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexao.php';

if (!has_role(['admin','editor'])) {
    header('Location: index.php?erro=nao_autorizado'); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); exit;
}

$titulo = trim($_POST['titulo'] ?? '');
$conteudo = trim($_POST['conteudo'] ?? '');
$tema = trim($_POST['tema'] ?? '');
$autor_name = $_SESSION['user_name'] ?? 'Anônimo';
$autor_id = $_SESSION['user_id'] ?? null;

if ($titulo === '' || $conteudo === '') {
    header('Location: criar_post.php?erro=campos');
    exit;
}

$sql = "INSERT INTO posts (titulo, conteudo, tema, autor, autor_id, data, imagem_url, estilo_post, publicado)
        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, 1)";
$stmt = $conn->prepare($sql);
if (!$stmt) { die('Erro prepare posts: ' . $conn->error); }
$imagem_url = $_POST['imagem_url'] ?? '';
$estilo_post = $_POST['estilo_post'] ?? '';
$stmt->bind_param('ssssisss', $titulo, $conteudo, $tema, $autor_name, $autor_id, $imagem_url, $estilo_post);
$ok = $stmt->execute();
if (!$ok) { die('Erro execute posts: ' . $stmt->error); }
$last_id = $stmt->insert_id;
$stmt->close();

header('Location: post.php?id=' . $last_id . '&sucesso=1');
exit;
