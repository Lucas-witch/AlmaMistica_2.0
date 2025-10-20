<?php
session_start();
require_once 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["nome"]) && !empty($_POST["senha"])) {
    $nome = trim($_POST["nome"]);
    $senha = trim($_POST["senha"]);

    $stmt = $conn->prepare("SELECT id, senha, FotoPerfil, email, role FROM usuarios WHERE nome = ?");
    $stmt->bind_param("s", $nome);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $senha_hash, $foto, $email, $role);
        $stmt->fetch();

        if (password_verify($senha, $senha_hash)) {
            session_regenerate_id(true);
            $_SESSION['usuario'] = $nome;
            $_SESSION['foto'] = $foto;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role ?? 'user';
            header("Location: perfil.php");
            exit;
        }
    }
    $erro = "Usuário ou senha inválidos.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="login-cadastro.css">
</head>
<body>
<div class="form-container">
    <h2>Entrar na sua conta</h2>
    <?php if (!empty($erro)) echo "<p style='color:red;text-align:center;'>$erro</p>"; ?>
    <form method="post" action="login.php">
        <label for="nome">Usuário:</label><br>
        <input type="text" name="nome" id="nome" required><br><br>
        <label for="senha">Senha:</label><br>
        <input type="password" name="senha" id="senha" required><br><br>
        <input type="submit" value="Entrar" class="botao">
    </form>

    <div class="google-login">
        <p>ou entre com:</p>
        <a href="#" class="botao-google">🌐 Entrar com Google</a>
    </div>

    <p><a href="recuperar-senha.php" class="botao-esquecer">Esqueceu sua senha?</a></p>
    <p>Não tem conta? <a href="cadastro.php" id="cadastre">Cadastre-se</a></p>
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
