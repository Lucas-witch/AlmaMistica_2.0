<?php
/* perfil.php - versão corrigida mantendo o estilo original */
session_start();
require_once 'conexao.php'; // espera $conn (mysqli)

// Se não está logado, volta ao login
if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario_session = $_SESSION['usuario'];

// busca dados do usuário (inclui hash da senha apenas para verificar em alterar_senha.php; NÃO vamos exibir hash)
$stmt = $conn->prepare("SELECT id, nome, email, senha, FotoPerfil, role FROM usuarios WHERE nome = ? LIMIT 1");
$stmt->bind_param("s", $usuario_session);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // usuário não encontrado
    echo '<p style="text-align:center;color:red;">Usuário não encontrado.</p>';
    exit;
}
$stmt->bind_result($user_id, $nome_db, $email_db, $senha_hash_db, $foto_db, $role_db);
$stmt->fetch();
$stmt->close();

// determina foto (fallback para padrão)
$foto_path = 'img/icon_perfil.png';
if (!empty($foto_db)) {
    // tenta usar caminho tal qual salvo; se não existir, mantém padrão
    if (file_exists($foto_db)) {
        $foto_path = $foto_db;
    } elseif (file_exists(__DIR__ . '/' . $foto_db)) {
        $foto_path = $foto_db;
    }
}

// máscara fixa para exibir no perfil (não revela hash)
$senha_mask = str_repeat('•', 8);

// mensagens de feedback (recebidas por querystring de alterar_senha.php)
$msg = '';
if (!empty($_GET['msg'])) {
    $msg = htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Perfil de <?php echo htmlspecialchars($usuario_session, ENT_QUOTES, 'UTF-8'); ?> | Alma Mística</title>
    <link rel="stylesheet" href="perfil.css">
    <link rel="stylesheet" href="estilo.css">
    <link rel="icon" href="img/logo-nova.png" type="image/png">
</head>
<body>
    <div class="perfil-container">
        <h2 style="font-family:'Press Start 2P',monospace;color:#f37bdf;text-shadow:2px 2px #491058;margin:0 0 18px 0;text-align:center;">
                Perfil de <?php echo htmlspecialchars($usuario_session, ENT_QUOTES, 'UTF-8'); ?>
            </h2>
        <div class="container" style="max-width:600px;margin:40px auto;background:rgba(0,0,0,0.8);border-radius:16px;box-shadow:0 0 18px #A866BE;padding:30px 24px;border:2px double #794CA9;">
            <div class="perfil-box">
                <div class="perfil-foto">
                    <img src="<?php echo htmlspecialchars($foto_path, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de perfil" id="foto-perfil">
                    <form method="post" enctype="multipart/form-data" action="alterar_foto.php" style="margin-top:10px;">
                        <label for="nova-foto" class="botao-foto">Trocar foto</label>
                        <input id="nova-foto" type="file" name="nova-foto" accept="image/*" style="display:none;" onchange="this.form.submit();">
                        <noscript><button type="submit" class="botao-foto">Alterar Foto</button></noscript>
                    </form>
                </div>

                <div class="perfil-dados">
                    <p><strong>Nome de usuário:</strong> <?php echo htmlspecialchars($nome_db, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($email_db, ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Papel:</strong> <?php echo htmlspecialchars($role_db ?: 'Usuário comum', ENT_QUOTES, 'UTF-8'); ?></p>

                    <!-- Exibição da senha mascarada (não revela o hash) -->
                    <div class="form-senha" style="margin-top:12px;">
                        <label for="senhaExib" style="font-size:15px;color:#ff00dd;font-family:'Press Start 2P',monospace;">Senha:</label>
                        <input type="text" id="senhaExib" value="<?php echo $senha_mask; ?>" readonly style="padding:6px;border-radius:5px;border:2px solid #794CA9;background:#f1b0fe22;color:#794CA9;width:160px;margin-left:8px;">
                        <button type="button" class="botao-senha" onclick="mostrarInfoSenha()" style="margin-left:8px;">Mostrar/Ocultar</button>
                    </div>

                    <div id="senha-info-msg" style="display:none;margin-top:8px;color:#b2f4e5;font-family:'VT323',monospace;">
                        Por segurança, a senha armazenada não pode ser exibida.<br>
                        Use o formulário abaixo para alterar sua senha.
                    </div>

                    <!-- Formulário de alterar senha (separado) -->
                    <form method="post" action="alterar_senha.php" class="form-senha" style="margin-top:12px;flex-direction:column;align-items:flex-start;">
                        <label for="senha_atual" style="font-size:15px;color:#ff00dd;font-family:'Press Start 2P',monospace;">Senha atual:</label>
                        <input type="password" name="senha_atual" id="senha_atual" placeholder="Senha atual" style="padding:6px;border-radius:5px;border:2px solid #794CA9;background:#f1b0fe22;color:#794CA9;width:220px;">
                        <label for="senha_nova" style="font-size:15px;color:#ff00dd;font-family:'Press Start 2P',monospace;margin-top:8px;">Nova senha:</label>
                        <input type="password" name="senha_nova" id="senha_nova" placeholder="Nova senha" style="padding:6px;border-radius:5px;border:2px solid #794CA9;background:#f1b0fe22;color:#794CA9;width:220px;">
                        <label for="senha_confirma" style="font-size:15px;color:#ff00dd;font-family:'Press Start 2P',monospace;margin-top:8px;">Confirmar nova:</label>
                        <input type="password" name="senha_confirma" id="senha_confirma" placeholder="Confirme a nova senha" style="padding:6px;border-radius:5px;border:2px solid #794CA9;background:#f1b0fe22;color:#794CA9;width:220px;">
                        <div style="display:flex;gap:8px;margin-top:10px;">
                            <button type="button" class="botao-senha" onclick="toggleMostrarSenhaCampos()">Mostrar campos</button>
                            <button type="submit" class="botao-senha">Salvar senha</button>
                        </div>
                    </form>

                </div>
            </div>

            <div class="perfil-info">
                <?php if ($msg): ?>
                    <p style="color:lightgreen;text-align:center;"><?php echo $msg; ?></p>
                <?php endif; ?>
                <p style="text-align:center;color:#b2f4e5;font-family:'VT323',monospace;">Bem-vindo(a), <?php echo htmlspecialchars($usuario_session, ENT_QUOTES, 'UTF-8'); ?>! 🌙</p>
                <p style="text-align:center;color:#b2f4e5;font-family:'VT323',monospace;">Você está conectado à <strong>Alma Mística</strong>.</p>
            </div>

            <form method="post" action="logout.php" style="margin-top:18px;">
                <button type="submit" class="botao-voltar" style="background:#ff00dd;color:#fff;padding:10px 30px;border-radius:7px;font-family:'Press Start 2P',monospace;font-size:14px;">Logout</button>
            </form>
            <a href="index.php" class="botao-voltar" style="display:inline-block;margin-top:10px;">Voltar para o site</a>
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
        <p style="text-align:center;">Desenvolvido por Lucas Alexandre e Maria de Lourdes - 2025</p>
        <p style="text-align:center;">Este site é uma prática de programação e não tem fins comerciais, apenas divulgação de estudos sérios sobre religião e espiritualidade.</p>
    </footer>

<script>
function mostrarInfoSenha() {
    const msg = document.getElementById('senha-info-msg');
    msg.style.display = (msg.style.display === 'block') ? 'none' : 'block';
}
function toggleMostrarSenhaCampos() {
    const campos = ['senha_atual','senha_nova','senha_confirma'];
    campos.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.type = (el.type === 'password') ? 'text' : 'password';
    });
}
</script>
</body>
</html>
