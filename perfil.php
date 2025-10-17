<?php
/* ÍNDICE: 1-Início 2-Conexão/Session 3-Lógica Principal 4-Formulários/Ações 5-Rodapé */
session_start();
$usuario_nome = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
$usuario = [
    'nome' => '',
    'email' => '',
    'foto' => ''
];

// Processa upload da foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['nova-foto']) && $_FILES['nova-foto']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['nova-foto']['name'], PATHINFO_EXTENSION);
    $foto_nome = 'img/perfil_' . time() . '_' . rand(1000,9999) . '.' . $ext;
    move_uploaded_file($_FILES['nova-foto']['tmp_name'], $foto_nome);
    $_SESSION['foto'] = $foto_nome;

    // Salva no banco se existir campo 'foto' na tabela usuarios
    $conn = new mysqli("localhost", "root", "", "alma_db");
    if (!$conn->connect_error) {
        $stmt = $conn->prepare("UPDATE usuarios SET FotoPerfil = ? WHERE nome = ?");
        if ($stmt === false) {
            // Apenas retorna sem mostrar erro
            $conn->close();
            return;
        }
        $stmt->bind_param("ss", $foto_nome, $usuario_nome);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
    header("Location: perfil.php");
    exit;
}

if ($usuario_nome) {
    $conn = new mysqli("localhost", "root", "", "alma_db");
    if (!$conn->connect_error) {
        // Busca nome, email e foto do usuário
        $stmt = $conn->prepare("SELECT nome, email, FotoPerfil FROM usuarios WHERE nome = ?");
        if ($stmt === false) {
            // Apenas retorna sem mostrar erro
            $conn->close();
            return;
        }
        $stmt->bind_param("s", $usuario_nome);
        $stmt->execute();
        $stmt->bind_result($nome, $email, $FotoPerfil);
        if ($stmt->fetch()) {
            $usuario['nome'] = $nome;
            $usuario['email'] = $email;
            $usuario['foto'] = $FotoPerfil;
        }
        $stmt->close();
        $conn->close();
    }
}

// Se não houver foto definida, usa a padrão
if (empty($usuario['foto'])) {
    $usuario['foto'] = 'img/icon_perfil.png';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil de <?php echo htmlspecialchars($usuario['nome']); ?> | Alma Mística</title>
    <link rel="stylesheet" href="perfil.css">
    <link rel="stylesheet" href="estilo.css">
    <!-- favicon padrão -->
    <link rel="icon" href="img/logo-nova.png" type="image/png">
    <link rel="apple-touch-icon" href="img/logo-nova.png">
    <link rel="shortcut icon" href="img/logo-nova.png" type="image/png">
</head>
<body>
    <div class="perfil-container">
        <div class="container" style="max-width:600px;margin:40px auto;background:rgba(0,0,0,0.8);border-radius:16px;box-shadow:0 0 18px #A866BE;padding:30px 24px;border:2px double #794CA9;">
            <h2 style="font-family:'Press Start 2P',monospace;color:#f37bdf;text-shadow:2px 2px #491058;margin:0 0 18px 0;text-align:center;">
                Perfil de <?php echo htmlspecialchars($_SESSION['usuario']); ?>
            </h2>
            <div class="perfil-box">
                <div class="perfil-foto">
                    <img src="<?php echo $usuario['foto']; ?>" alt="Foto de perfil" id="foto-perfil">
                    <form method="post" enctype="multipart/form-data" class="form-foto">
                        <label for="nova-foto" class="botao-foto">Trocar foto</label>
                        <input type="file" name="nova-foto" id="nova-foto" accept="image/*" style="display:none;" onchange="this.form.submit();">
                    </form>
                </div>
                <div class="perfil-dados">
                    <p><strong>Nome de usuário:</strong> <?php echo htmlspecialchars($usuario['nome']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($usuario['email']); ?></p> <br>
                    <form method="post" class="form-senha">
                        <label for="nova-senha">Alterar senha:</label>
                        <input type="password" name="nova-senha" id="nova-senha" placeholder="Nova senha" autocomplete="new-password">
                        <button type="submit" class="botao-senha">Salvar senha</button>
                    </form>
                </div>
            </div>
            <div class="perfil-info">
                <p>Você receberá notificações por email quando houver novas postagens no site.</p>
            </div>
            <form method="post" action="logout.php" style="margin-top:18px;">
                <button type="submit" class="botao-voltar" style="background:#ff00dd;color:#fff;padding:10px 30px;border-radius:7px;font-family:'Press Start 2P',monospace;font-size:14px;">Logout</button>
            </form>
            <a href="index.php" class="botao-voltar">Voltar para o site</a>
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