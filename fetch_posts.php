<?php
// Endpoint AJAX para carregar mais posts (retorna HTML pronto)
session_start();
require_once __DIR__ . '/conexao.php';
require_once __DIR__ . '/auth.php';

// parâmetros
$offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
$limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 3;
$tema   = isset($_GET['tema']) ? trim($_GET['tema']) : '';
$busca  = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// Monta query conforme filtros
$params = [];
$sql = "SELECT id, titulo, autor, tema, data FROM posts";
$where = [];
if ($busca !== '') {
    $where[] = "(titulo LIKE ? OR conteudo LIKE ? OR tema LIKE ?)";
    $like = "%$busca%";
    $params[] = $like; $params[] = $like; $params[] = $like;
} elseif ($tema !== '') {
    $where[] = "tema = ?";
    $params[] = $tema;
}
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY data DESC LIMIT ? OFFSET ?";

// prepara statement dinâmico
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit;
}

// bind dinamico
$types = '';
$bind_values = [];
foreach ($params as $p) { $types .= 's'; $bind_values[] = $p; }
$types .= 'ii';
$bind_values[] = $limit;
$bind_values[] = $offset;

$refs = [];
foreach ($bind_values as $k => $v) $refs[$k] = &$bind_values[$k];
array_unshift($refs, $types);
call_user_func_array([$stmt, 'bind_param'], $refs);

$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    // sem mais posts -> retorna vazio
    exit;
}

// detectar roles (mesma lógica do index.php, simplificada)
$isAdmin = false; $isEditor = false;
if (function_exists('has_role')) {
    $isAdmin = has_role(['admin']);
    $isEditor = has_role(['editor','admin']);
} else {
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') $isAdmin = true;
        if (in_array($_SESSION['role'], ['editor','admin'], true)) $isEditor = true;
    }
}

// gera HTML para cada post
while ($post = $res->fetch_assoc()) {
    $id = (int)$post['id'];
    $titulo = htmlspecialchars($post['titulo'] ?: '(Sem título)', ENT_QUOTES);
    $autor  = htmlspecialchars($post['autor'] ?: '(Sem autor)', ENT_QUOTES);
    $tema_v = htmlspecialchars($post['tema'] ?: '(Sem tema)', ENT_QUOTES);
    $data_v = date('d/m/Y', strtotime($post['data']));
    echo '<div class="post">';
    echo "<h3><a href=\"post.php?id={$id}\">{$titulo}</a></h3>";
    echo "<p>Data: {$data_v} | Autor: {$autor} | Tema: ";
    if (!empty($post['tema'])) {
        echo "<a href=\"index.php?tema=" . urlencode($post['tema']) . "\" style=\"color:#ffd700;text-decoration:underline;\">{$tema_v}</a>";
    } else {
        echo '<span style="color:red;">(Sem tema)</span>';
    }
    echo "</p>";

    if ($isEditor) {
        echo '<a class="btn btn-edit" href="editar-conteudo-post.php?id=' . urlencode($post['id']) . '" style="display:inline-block;margin-right:6px;padding:6px 10px;background:#7c5cff;color:#fff;border-radius:6px;text-decoration:none;">Editar</a>';
    }
    if ($isAdmin) {
        echo '<form method="post" action="excluir_post.php" style="display:inline;margin-left:6px;" onsubmit="return confirm(\'Tem certeza que deseja excluir esta postagem?\');">';
        echo '<input type="hidden" name="id" value="' . htmlspecialchars($post['id'], ENT_QUOTES) . '">';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) . '">';
        echo '<button type="submit" class="botao" style="background:#d00;color:#fff;margin-left:8px;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;">Excluir</button>';
        echo '</form>';
    }

    echo '</div>';
}

$stmt->close();
$conn->close();
?>
