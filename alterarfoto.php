<?php
// alterarfoto.php
session_start();
require_once 'conexao.php'; // $conn

if (empty($_SESSION['id']) || empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['foto'];
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    if (!in_array($file['type'], $allowed)) {
        $msg = 'Tipo de arquivo não permitido.';
        header('Location: perfil.php?msg=' . urlencode($msg));
        exit;
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $msg = 'Arquivo muito grande. Máx 2MB.';
        header('Location: perfil.php?msg=' . urlencode($msg));
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $novoNome = 'img/uploads/' . uniqid('avatar_') . '.' . $ext;

    if (!is_dir(__DIR__ . '/img/uploads')) mkdir(__DIR__ . '/img/uploads', 0755, true);

    if (move_uploaded_file($file['tmp_name'], __DIR__ . '/' . $novoNome)) {
        // atualiza banco
        $upd = $conn->prepare("UPDATE usuarios SET FotoPerfil = ? WHERE id = ?");
        if ($upd) {
            $upd->bind_param("si", $novoNome, $userId);
            if ($upd->execute()) {
                // atualiza sessão
                $_SESSION['foto'] = $novoNome;
                $upd->close();
                header('Location: perfil.php?msg=' . urlencode('Foto atualizada.'));
                exit;
            } else {
                $upd->close();
                unlink(__DIR__ . '/' . $novoNome);
                header('Location: perfil.php?msg=' . urlencode('Erro ao salvar no banco.'));
                exit;
            }
        } else {
            unlink(__DIR__ . '/' . $novoNome);
            header('Location: perfil.php?msg=' . urlencode('Erro interno.'));
            exit;
        }
    } else {
        header('Location: perfil.php?msg=' . urlencode('Falha no upload.'));
        exit;
    }
}

// se não for post válido, redireciona
header('Location: perfil.php');
exit;
