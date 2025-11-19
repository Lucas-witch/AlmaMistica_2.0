<?php
session_start();
require_once 'conexao.php';

// Se usuário já está logado, redireciona para index
if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha_raw = trim($_POST['senha'] ?? '');

    if ($nome === '' || $email === '' || $senha_raw === '') {
        $erro = "Preencha todos os campos.";
    } else {
        // verifica duplicatas
        $check = $conn->prepare("SELECT id FROM usuarios WHERE nome = ? OR email = ? LIMIT 1");
        $check->bind_param("ss", $nome, $email);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $erro = "Nome de usuário ou email já existe.";
            $check->close();
        } else {
            $check->close();
            $senha_hash = password_hash($senha_raw, PASSWORD_DEFAULT);
            $foto = 'img/icon_perfil.png';
            $role = 'user';
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, FotoPerfil, role) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("sssss", $nome, $email, $senha_hash, $foto, $role);
                if ($stmt->execute()) {
                    $new_id = $conn->insert_id;
                    $_SESSION['id'] = (int)$new_id;
                    $_SESSION['usuario'] = $nome;
                    $_SESSION['email'] = $email;
                    $_SESSION['foto'] = $foto;
                    $_SESSION['role'] = $role;
                    header("Location: perfil.php");
                    exit;
                } else {
                    $erro = "Erro ao cadastrar.";
                }
                $stmt->close();
            } else {
                $erro = "Erro interno (DB).";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Cadastro</title>
    <link rel="stylesheet" href="login-cadastro.css">
</head>
<body>
<div class="form-container">
    <h2>Criar Conta</h2>

    <?php if (!empty($erro)): ?>
        <p style="color:red;text-align:center;"><?php echo htmlspecialchars($erro); ?></p>
    <?php endif; ?>

    <!-- Formulário de cadastro -->
    <form method="post" action="cadastro.php">
        <label>Nome de usuário:</label><br><input type="text" name="nome" required id="nome"><br><br>
        <label>Seu email:</label><br><input type="email" name="email" required id="email"><br><br>
        <label>Crie uma senha:</label><br><input type="password" name="senha" required id="senha"><br><br>
        <input type="submit" value="Cadastrar" class="botao">
    </form>

    <!-- Mensagem para usuário que já possui conta (estilo idêntico ao login.php) -->
    <p style="text-align:center;margin-top:15px;">Já possui uma conta? <a href="login.php" style="color:#ff00dd;font-weight:bold;">Faça login aqui</a></p>
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

        <div style="text-align:center; margin-top:8px; font-family: 'VT323', monospace; color: #fff;">
            <p>Desenvolvido por Lucas Alexandre e Maria de Lourdes - 2025</p>
            <p style="font-size:13px;">Este site é uma prática de programação e não tem fins comerciais, apenas divulgação de estudos sérios sobre religião e espiritualidade.</p>
        </div>
    </footer>  
</body>
</html>
