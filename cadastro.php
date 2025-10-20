<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty($_POST["nome"]) && !empty($_POST["senha"]) && !empty($_POST["email"])) {
        $nome = trim($_POST["nome"]);
        $senha = password_hash(trim($_POST["senha"]), PASSWORD_DEFAULT);
        $email = trim($_POST["email"]);
        $foto = 'img/icon_perfil.png';

        $conn = new mysqli("localhost", "root", "", "alma_db");
        if (!$conn->connect_error) {
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, FotoPerfil) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nome, $email, $senha, $foto);
            if ($stmt->execute()) {
                session_start();
                $_SESSION['usuario'] = $nome;
                $_SESSION['foto'] = $foto;
                $_SESSION['email'] = $email;
                header("Location: perfil.php");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
    <link rel="stylesheet" href="login-cadastro.css">
</head>
<body>
<div class="form-container">
    <h2>Crie sua conta</h2>
    <form method="post" action="cadastro.php">
        <label for="nome">Nome de usuário:</label><br>
        <input type="text" name="nome" id="nome" required><br><br>
        <label for="email">Seu email:</label><br>
        <input type="email" name="email" id="email" required><br><br>
        <label for="senha">Crie uma senha:</label><br>
        <input type="password" name="senha" id="senha" required><br><br>
        <input type="submit" value="Cadastrar" class="botao">
    </form>

    <div class="google-login">
        <p>ou cadastre-se com:</p>
        <a href="#" class="botao-google">🌐 Criar conta com Google</a>
    </div>

    <p>Já tem conta? <a href="login.php" class="botao-conta">Entre nela</a></p>
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
