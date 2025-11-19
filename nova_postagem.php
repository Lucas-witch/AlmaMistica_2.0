<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexao.php';

if (!has_role(['admin','editor'])) {
    header('Location: index.php?erro=nao_autorizado'); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php'); 
    exit;
}

// Captura todos os campos
$titulo = trim($_POST['titulo'] ?? '');
$conteudo = trim($_POST['conteudo'] ?? '');
$tema = trim($_POST['tema'] ?? '');
$autor = trim($_POST['autor'] ?? $_SESSION['usuario'] ?? $_SESSION['username'] ?? 'Anônimo');

// Campos de estilo
$titulo_cor = $_POST['titulo_cor'] ?? '#5e2d89';
$titulo_font = $_POST['titulo_font'] ?? 'VT323, monospace';
$titulo_size = $_POST['titulo_size'] ?? '28px';
$meta_cor = $_POST['meta_cor'] ?? '#6b2d86';
$meta_font = $_POST['meta_font'] ?? 'VT323, monospace';
$meta_size = $_POST['meta_size'] ?? '12px';
$font_family = $_POST['font_family'] ?? 'VT323, monospace'; // Adicionado
$font_size = $_POST['font_size'] ?? '16px'; // Adicionado

// Gera o CSS completo
$estilo_post = "
.post-title { color: {$titulo_cor}; font-family: {$titulo_font}; font-size: {$titulo_size}; margin-bottom:8px; }
.post-meta { color: {$meta_cor}; font-family: {$meta_font}; font-size: {$meta_size}; opacity:.9; }
.post-conteudo { font-family: {$font_family}; font-size: {$font_size}; line-height:1.6; }
.post-content p { color: inherit; }
.post-content a { color: #ff00dd; text-decoration: underline; }
.post-content mark { background-color: #ff94f3; color: #000; padding: 2px 4px; }
";

if ($titulo === '' || $conteudo === '') {
    header('Location: editor-post.php?erro=campos_obrigatorios');
    exit;
}

$sql = "INSERT INTO posts (titulo, conteudo, tema, autor, data, imagem_url, estilo_post)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) { 
    die('Erro prepare posts: ' . $conn->error); 
}

$imagem_url = '';
// Processa upload de imagem se houver
if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/img/posts/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
    $new_name = 'post_' . uniqid() . '.' . $ext;
    $dest = $upload_dir . $new_name;
    
    if (move_uploaded_file($_FILES['imagem']['tmp_name'], $dest)) {
        $imagem_url = 'img/posts/' . $new_name;
    }
}

$stmt->bind_param('ssssss', $titulo, $conteudo, $tema, $autor, $imagem_url, $estilo_post);
$ok = $stmt->execute();
if (!$ok) { 
    die('Erro execute posts: ' . $stmt->error); 
}

$last_id = $stmt->insert_id;
$stmt->close();

header('Location: post.php?id=' . $last_id . '&sucesso=1');
exit;
