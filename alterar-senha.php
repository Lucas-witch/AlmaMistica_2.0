<?php
// alterar_senha.php
session_start();
require_once 'conexao.php';

if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: perfil.php');
    exit;
}

$usuario = $_SESSION['usuario'];
$senha_atual = $_POST['senha_atual'] ?? '';
$senha_nova = $_POST['senha_nova'] ?? '';
$senha_confirma = $_POST['senha_confirma'] ?? '';

// validações básicas
if (empty($senha_atual) || empty($senha_nova) || empty($senha_confirma)) {
    header('Location: perfil.php?msg=' . urlencode('Preencha todos os campos.'));
    exit;
}
if ($senha_nova !== $senha_confirma) {
    header('Location: perfil.php?msg=' . urlencode('A nova senha e a confirmação não coincidem.'));
    exit;
}
if (strlen($senha_nova) < 6) {
    header('Location: perfil.php?msg=' . urlencode('A nova senha deve ter ao menos 6 caracteres.'));
    exit;
}

// busca hash atual do banco
$stmt = $conn->prepare("SELECT senha FROM usuarios WHERE nome = ? LIMIT 1");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    header('Location: perfil.php?msg=' . urlencode('Usuário não encontrado.'));
    exit;
}
$stmt->bind_result($senha_hash_db);
$stmt->fetch();
$stmt->close();

// verifica senha atual
if (!password_verify($senha_atual, $senha_hash_db)) {
    header('Location: perfil.php?msg=' . urlencode('Senha atual incorreta.'));
    exit;
}

// atualiza com novo hash
$novo_hash = password_hash($senha_nova, PASSWORD_DEFAULT);
$upd = $conn->prepare("UPDATE usuarios SET senha = ? WHERE nome = ?");
$upd->bind_param("ss", $novo_hash, $usuario);
if ($upd->execute()) {
    $upd->close();
    header('Location: perfil.php?msg=' . urlencode('Senha atualizada com sucesso.'));
    exit;
} else {
    $upd->close();
    header('Location: perfil.php?msg=' . urlencode('Erro ao atualizar a senha.'));
    exit;
}
