<?php
// Simples cadastro de usuário com redirecionamento para perfil.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST["nome"]) || empty($_POST["senha"]) || empty($_POST["email"])) {
        echo '<div style="color:red;text-align:center;">Preencha todos os campos obrigatórios.</div>';
    } else {
        $nome = htmlspecialchars(trim($_POST["nome"]));
        $senha = password_hash(trim($_POST["senha"]), PASSWORD_DEFAULT);
        $email = htmlspecialchars(trim($_POST["email"]));
        $foto = 'img/icon_perfil.png';

        $conn = new mysqli("localhost", "root", "", "alma_db");
        if ($conn->connect_error) {
            echo '<div style="color:red;text-align:center;">Erro de conexão com o banco de dados.</div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, FotoPerfil) VALUES (?, ?, ?, ?)");
            if ($stmt === false) {
                echo '<div style="color:red;text-align:center;">Erro ao preparar a query.</div>';
                $conn->close();
            } else {
                $stmt->bind_param("ssss", $nome, $email, $senha, $foto);
                if ($stmt->execute()) {
                    session_start();
                    $_SESSION['usuario'] = $nome;
                    $_SESSION['foto'] = $foto;
                    $_SESSION['email'] = $email;
                    // Nenhum echo ou HTML antes do header!
                    header("Location: perfil.php");
                    exit;
                } else {
                    echo '<div style="color:red;text-align:center;">Erro ao cadastrar usuário.</div>';
                }
                $stmt->close();
                $conn->close();
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
    <!-- favicon padrão -->
    <link rel="icon" href="img/logo-nova.png" type="image/png">
    <link rel="apple-touch-icon" href="img/logo-nova.png">
    <link rel="shortcut icon" href="img/logo-nova.png" type="image/png">
</head>
<body>
    <div class="form-container">
        <h2>Crie sua conta</h2>
        <form method="post" action="cadastro.php">
            <label for="nome">Nome de usuário:</label><br>
            <input type="text" name="nome" id="nome" required><br><br>
            <label for="email">Digite o seu email:</label>
            <input type="email" name="email" id="email" required><br><br>
            <label for="senha">Crie uma senha mágica:</label><br>
            <input type="password" name="senha" id="senha" required><br><br>
            <input type="submit" value="Cadastrar" class="botao">
        </form>
        <p>Já tem conta? <a href="login.php">Entre nela</a></p>
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
</html>
</body>
</html>
</html>
