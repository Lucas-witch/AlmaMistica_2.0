<?php
// post.php (modificado: usa has_role para identificar admin/editor)
require_once 'conexao.php';
require_once 'auth.php';

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

// Captura o ID do post pela URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die("Post inválido.");
}

// busca o post no banco
$stmt = $conn->prepare("SELECT id, titulo, conteudo, autor, tema, data, imagem_url, estilo_post FROM posts WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$post = $res->fetch_assoc();
if (!$post) {
    die("Post não encontrado.");
}

// Identificação do administrador/ediTOR pela sessão (mais confiável)
$isAdmin = has_role(['admin']);
$isEditor = has_role(['editor', 'admin']); // editores também podem editar

?>
<!-- A partir daqui continua seu HTML de exibição do post.
Use as flags $isAdmin / $isEditor para exibir botões de edição/exclusão:
<?php if ($isEditor): ?>
  <!-- botão editar -->
<?php endif; ?>
<?php if ($isAdmin): ?>
  <!-- botão excluir -->
<?php endif; ?>
-->

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
        <h1 id="title"><?= htmlspecialchars($post['titulo']) ?></h1>

        <div class="post-meta">
            <strong>Tema:</strong> <?= htmlspecialchars($post['tema']) ?> |
            <strong>Autor:</strong> <?= htmlspecialchars($post['autor']) ?> |
            <strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($post['data'])) ?>
        </div>

        <div class="conteudo-post ck-content">
            <?= $post['conteudo'] /* Mantém a formatação HTML salva pelo editor */ ?>
            <br>
            <br>
            <a href="index.php" class="botao-voltar">Voltar</a>
        </div>    
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
