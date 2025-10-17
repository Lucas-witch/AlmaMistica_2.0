<?php
/* ÍNDICE: 1-Início 2-Conexão/Session 3-Lógica Principal 4-Formulários/Ações 5-Rodapé */
session_start();
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["nome"]) && !empty($_POST["senha"])) {
    $nome = htmlspecialchars(trim($_POST["nome"]));
    $senha = trim($_POST["senha"]);
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : '';

    // Conexão com o banco
    $conn = new mysqli("localhost", "root", "", "alma_db");
    if ($conn->connect_error) {
        // Apenas retorna sem mostrar erro
        return;
    } else {
        $stmt = $conn->prepare("SELECT senha, FotoPerfil, email FROM usuarios WHERE nome = ?");
        $stmt->bind_param("s", $nome);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($senha_hash, $foto, $db_email);
            $stmt->fetch();
            // Verificação extra para admin
            if ($nome === 'dev_Lucas369' && $email === 'lucasbarros31102008@gmail.com' && $senha === '@Rulu31102008') {
                $_SESSION['usuario'] = $nome;
                $_SESSION['foto'] = $foto;
                $_SESSION['email'] = $db_email;
                header("Location: perfil.php");
                exit;
            } elseif (password_verify($senha, $senha_hash)) {
                $_SESSION['usuario'] = $nome;
                $_SESSION['foto'] = $foto;
                $_SESSION['email'] = $db_email;
                header("Location: perfil.php");
                exit;
            }
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="login-cadastro.css">
    <!-- favicon padrão -->
    <link rel="icon" href="img/logo-nova.png" type="image/png">
    <link rel="apple-touch-icon" href="img/logo-nova.png">
    <link rel="shortcut icon" href="img/logo-nova.png" type="image/png">
</head>
<body>
    <div class="form-container">
        <h2>Entrar na sua conta</h2>
        <form method="post" action="login.php">
            <label for="nome">Nome de usuário:</label><br>
            <input type="text" name="nome" id="nome" required><br><br>
            <label for="email">Email:</label><br>
            <input type="email" name="email" id="email" required><br><br>
            <label for="senha">Senha:</label><br>
            <input type="password" name="senha" id="senha" required><br><br>
            <input type="submit" value="Entrar" class="botao">
        </form>
        <p>Não tem conta? <a href="cadastro.php">Cadastre-se</a></p>
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
