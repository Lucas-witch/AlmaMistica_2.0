<?php
session_start();
$adminUsers = [
    'dev_Lucas369' => 'lucasbarros31102008@gmail.com',
    'eu-sou-gay589' => 'leo2008@gmail.com'
];

if (!isset($_SESSION['usuario']) || !isset($_SESSION['email']) ||
    !array_key_exists($_SESSION['usuario'], $adminUsers) ||
    $adminUsers[$_SESSION['usuario']] !== $_SESSION['email']) {
    echo '<div style="color:red;text-align:center;">Acesso negado.</div>';
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo '<div style="color:red;text-align:center;">ID inválido.</div>';
    exit;
}
$id = intval($_POST['id']);
$conn = new mysqli('localhost', 'root', '', 'alma_db');
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
