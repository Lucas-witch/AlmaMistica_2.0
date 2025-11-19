<?php
session_start();
require_once __DIR__ . '/conexao.php';

// Se usuário já está logado, redireciona para index
if (isset($_SESSION['usuario']) && !empty($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$erro = '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["nome"]) && !empty($_POST["senha"])) {
    $nome = trim($_POST["nome"]);
    $senha = trim($_POST["senha"]);

    // VERIFICAÇÃO PRELIMINAR: $conn válido
    if (!isset($conn) || !($conn instanceof mysqli)) {
        error_log("login.php: conexao ausente ou invalida. Var \$conn: " . var_export(isset($conn) ? $conn : null, true));
        $erro = "Erro interno (DB).";
    } else {
        $sql = "SELECT id, nome, senha, FotoPerfil, email, role FROM usuarios WHERE nome = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            // log do erro real do MySQL para /apache/logs/error.log
            error_log("login.php: falha prepare SQL. Erro mysqli: " . $conn->error . " --- SQL: " . $sql);
            $erro = "Erro interno (DB).";
        } else {
            $stmt->bind_param("s", $nome);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($id, $nome_db, $senha_hash, $foto_db, $email_db, $role_db);
                $stmt->fetch();

                if (password_verify($senha, $senha_hash)) {
                    session_regenerate_id(true);
                    $_SESSION['id'] = (int)$id;
                    $_SESSION['user_id'] = (int)$id;
                    $_SESSION['usuario_id'] = (int)$id;
                    $_SESSION['usuario'] = $nome_db;
                    $_SESSION['username'] = $nome_db;
                    $_SESSION['email'] = $email_db;
                    $_SESSION['foto'] = $foto_db ?: 'img/icon_perfil.png';
                    // Normaliza role: trim, lowercase e fallback para 'user' se vazio
                    $role_normalized = isset($role_db) ? trim((string)$role_db) : '';
                    $role_normalized = ($role_normalized !== '') ? strtolower($role_normalized) : 'user';
                    $_SESSION['role'] = $role_normalized;

                    header("Location: perfil.php");
                    exit;
                } else {
                    $erro = "Usuário ou senha inválidos.";
                }
            } else {
                $erro = "Usuário ou senha inválidos.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8"><title>Login</title><link rel="stylesheet" href="login-cadastro.css">
</head>
<body>
<div class="form-container">
    <h2>Entrar na sua conta</h2>
    <?php if (!empty($erro)): ?><p style="color:red;text-align:center;"><?php echo htmlspecialchars($erro); ?></p><?php endif; ?>
    
    <!-- Formulário de login -->
    <form method="post" action="login.php">
        <label for="nome">Usuário:</label><br>
        <input type="text" name="nome" id="nome" required><br><br>
        <label for="senha">Senha:</label><br>
        <input type="password" name="senha" id="senha" required><br><br>
        <input type="submit" value="Entrar" class="botao">
    </form>

    <!-- Link para cadastro (movido para cima do botão Google) -->
    <p style="text-align:center;margin-top:12px;margin-bottom:10px;">Não tem conta? <a href="cadastro.php" style="color:#ff00dd;font-weight:bold;">Cadastre-se</a></p>

    

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
