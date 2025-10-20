<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["email"])) {
    $email = trim($_POST["email"]);
    echo "<div style='text-align:center;color:lightgreen;'>Se este email estiver cadastrado, enviaremos instruções para redefinir sua senha.</div>";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha | Alma Mística</title>
    <link rel="stylesheet" href="login-cadastro.css">
</head>
<body>
<div class="form-container">
    <h2>Recuperar senha</h2>
    <p>Digite o email cadastrado e enviaremos um link para redefinir sua senha.</p>
    <form method="post" action="recuperar-senha.php">
        <label for="email">Email:</label><br>
        <input type="email" name="email" id="email" required><br><br>
        <input type="submit" value="Enviar link" class="botao">
    </form>
    <p><a href="login.php">Voltar ao login</a></p>
</div>
<footer>
    <p>Desenvolvido por Lucas Alexandre e Maria de Lourdes - 2025</p>
</footer>
</body>
</html>
