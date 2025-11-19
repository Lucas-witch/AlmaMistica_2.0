<?php
// post.php — exibe o post, lista comentários (com nome/foto) e mostra o formulário para novo comentário
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/conexao.php'; // $conn = mysqli

// Utilitários
function goHome(string $code = 'post_invalido'): void {
    header('Location: index.php?erro=' . rawurlencode($code));
    exit;
}

// 1) Captura do ID (GET) com fallback para POST (quando volta do comentário)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0 && isset($_POST['post_id'])) {
    $id = (int)$_POST['post_id'];
}
if ($id <= 0) {
    goHome('post_invalido');
}

// 2) Carrega o post (colunas reais do seu schema)
$sqlPost = "SELECT id, titulo, conteudo, tema, autor, data, imagem_url, estilo_post
            FROM posts WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sqlPost);
if (!$stmt) { goHome('db_prepare'); }
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$post = $res->fetch_assoc();
$stmt->close();

if (!$post) {
    goHome('post_nao_encontrado');
}

// 3) Mensagens de feedback
$erro = $_GET['erro'] ?? null;
$ok   = $_GET['comentario'] ?? null;

// 4) Comentários: JOIN com usuarios p/ trazer nome e FotoPerfil
$sqlCom = "SELECT
             c.id,
             c.mensagem,
             c.data AS created_at,
             u.nome AS autor_nome,
             COALESCE(u.FotoPerfil, 'img/icon_perfil.png') AS autor_foto
           FROM comentarios c
           JOIN usuarios u ON u.id = c.usuario_id
           WHERE c.post_id = ?
           ORDER BY c.data DESC";
$stmtCom = $conn->prepare($sqlCom);
$stmtCom->bind_param('i', $id);
$stmtCom->execute();
$comentarios = $stmtCom->get_result();
$stmtCom->close();

// 5) Usuário logado?
$usuario_id_logado = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($post['titulo']) ?> - Alma Mística</title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="post.css?v=<?= filemtime('post.css') ?>">

    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="post.css?v=<?= filemtime('post.css') ?>">

    <?php
    // Carrega CSS específico do post (arquivo posts/post_ID.css) com cache-bust
    $cssFile = "posts/post_{$id}.css";
    if (file_exists($cssFile)) {
        echo "\n<link rel=\"stylesheet\" href=\"{$cssFile}?v=" . filemtime($cssFile) . "\">\n";
    }

    // SEMPRE aplica o estilo salvo pelo editor, por último (tem prioridade)
    if (!empty($post["estilo_post"])) {
        echo "\n<style>\n" . $post["estilo_post"] . "\n</style>\n";
    }
    ?>

    <!-- favicon padrão -->
    <link rel="icon" href="img/logo-nova.png" type="image/png">
    <link rel="apple-touch-icon" href="img/logo-nova.png">
    <link rel="shortcut icon" href="img/logo-nova.png" type="image/png">
</head>
    <body>
    <div class="post">
        <div class="post-container">
            <!-- Mudar de id="title" para class="post-title" para usar o estilo dinâmico -->
            <h1 class="post-title"><?= htmlspecialchars($post['titulo']) ?></h1>

            <div class="post-meta">
                <strong>Tema:</strong> <?= htmlspecialchars($post['tema']) ?> |
                <strong>Autor:</strong> <?= htmlspecialchars($post['autor']) ?> |
                <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($post['data'])) ?>
            </div>

            <!-- Usar a classe correta para o conteúdo -->
            <div class="post-conteudo">
                <?= $post['conteudo'] ?>
            </div>
            <br>
            <a href="index.php" class="botao-voltar">Voltar ao site</a>    
        </div>
    </div>
       

    <footer>
        <div class="footer-redes">
            <a href="https://instagram.com/almamistica_ficticio" target="_blank" class="icone-social" title="Instagram Alma Mística">
                <img src="img/icon instagram.jpg" alt="Instagram" class="icon-externo">
            </a>
            <a href="https://youtube.com/almamistica_ficticio" target="_blank" class="icone-social" title="YouTube Alma Mística">
                <img src="img/Icon Youtube.jpg" alt="YouTube" class="icon-externo">
            </a>
            <a href="https://tiktok.com/@almamistica_ficticio" target="_blank" class="icone-social" title="TikTok Alma Mística">
                <img src="img/Icon Tik Tok.jpg" alt="TikTok" class="icon-externo">
            </a>
        </div>
        <p>Desenvolvido por Lucas Alexandre e Maria de Lourdes - 2025</p>
        <p>Este site é uma prática de programação e não tem fins comerciais, apenas divulgação de estudos sérios sobre religião e espiritualidade.</p>
    </footer>
</body>
</html>

