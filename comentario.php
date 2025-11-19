<?php
// comentario.php — valida, grava e retorna ao post
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/conexao.php'; // $conn = mysqli

// Helpers
function safeRedirect(string $url): void {
    $url = preg_replace('/[\r\n]+/', '', trim($url));
    header('Location: ' . $url);
    exit;
}
function backToPost(int $pid, string $q): void {
    safeRedirect('post.php?id=' . max(0, $pid) . '&' . $q);
}

// Método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safeRedirect('index.php?erro=metodo_invalido');
}

// Entradas
$post_id = 0;
if (isset($_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];
} elseif (isset($_POST['id'])) { // fallback legado
    $post_id = (int)$_POST['id'];
}

$mensagem = trim((string)($_POST['mensagem'] ?? ($_POST['comentario'] ?? ($_POST['texto'] ?? ''))));
// Sanitização básica (se quiser permitir tags específicas, use strip_tags($mensagem, '<b><i>...') )
$mensagem = strip_tags($mensagem);
$mensagem = preg_replace('/[ \t]+/', ' ', $mensagem);
if (function_exists('mb_substr')) {
    $mensagem = mb_substr($mensagem, 0, 2000, 'UTF-8');
} else {
    $mensagem = substr($mensagem, 0, 2000);
}

if ($post_id <= 0 && $mensagem === '') {
    safeRedirect('index.php?erro=comentario_vazio');
}
if ($post_id > 0 && $mensagem === '') {
    backToPost($post_id, 'erro=comentario_vazio');
}

// Usuário logado
$usuario_id  = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? null;
$usuario_nome = $_SESSION['usuario'] ?? $_SESSION['username'] ?? '';
if (!$usuario_id) {
    safeRedirect('login.php?erro=precisa_logar');
}

// Se é comentário associado a post, verifica existência; se for chat geral (post_id <= 0) ignora essa checagem
if ($post_id > 0) {
    $chk = $conn->prepare("SELECT 1 FROM posts WHERE id = ? LIMIT 1");
    if (!$chk) { backToPost($post_id, 'erro=db_prepare'); }
    $chk->bind_param('i', $post_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        $chk->close();
        backToPost($post_id, 'erro=post_inexistente');
    }
    $chk->close();
}

// --- NOVO: detectar se coluna post_id permite NULL ---
$colAllowsNull = false;
$colChk = $conn->query("SHOW COLUMNS FROM comentarios LIKE 'post_id'");
if ($colChk && $colChk->num_rows > 0) {
    $colInfo = $colChk->fetch_assoc();
    if (isset($colInfo['Null']) && strtoupper($colInfo['Null']) === 'YES') {
        $colAllowsNull = true;
    }
}

// Se coluna NÃO permite NULL e estamos submetendo comentário geral (post_id <=0),
// tentamos alterar o esquema para permitir NULL (para evitar criar um post marcador).
if (!$colAllowsNull && $post_id <= 0) {
    // tenta alterar a coluna para aceitar NULL (se o usuário do MySQL tiver permissão)
    $alterSql = "ALTER TABLE comentarios MODIFY post_id INT NULL";
    if ($conn->query($alterSql) === true) {
        $colAllowsNull = true;
    } else {
        // não devemos criar posts marcadores; reporta erro legível
        safeRedirect('index.php?erro=esquema_postid_nao_nullable');
    }
}

// Insert (usa seu schema: comentarios com post_id, usuario_id, nome, mensagem, data)
// Lógica:
// - se $post_id > 0: insere com esse post_id (comentário de post)
// - se $post_id <= 0: insere post_id = NULL (se coluna permitir)
if ($post_id > 0) {
    $sql = "INSERT INTO comentarios (post_id, usuario_id, nome, mensagem, data)
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { backToPost($post_id, 'erro=db_prepare'); }
    $stmt->bind_param('iiss', $post_id, $usuario_id, $usuario_nome, $mensagem);
} else {
    if (!$colAllowsNull) {
        // caso extremo: não permitir NULL e ALTER falhou, bloqueamos por segurança
        safeRedirect('index.php?erro=impossivel_salvar_comentario_geral');
    }
    $sql = "INSERT INTO comentarios (post_id, usuario_id, nome, mensagem, data)
            VALUES (NULL, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { safeRedirect('index.php?erro=db_prepare'); }
    $stmt->bind_param('iss', $usuario_id, $usuario_nome, $mensagem);
}

if (!$stmt->execute()) {
    $stmt->close();
    if ($post_id > 0) backToPost($post_id, 'erro=db_exec');
    else safeRedirect('index.php?erro=db_exec');
}
$stmt->close();

// Redirecionamento: se foi comentário de post, volta para o post; se foi chat geral, para index
if ($post_id > 0) {
    backToPost($post_id, 'comentario=ok');
} else {
    safeRedirect('index.php?comentario=ok');
}
