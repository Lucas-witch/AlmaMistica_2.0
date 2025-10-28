<?php
session_start();
require_once 'conexao.php'; // garante que $conn (mysqli) esteja disponível

// Verifica login
if (empty($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$usuario_session = $_SESSION['usuario'];

// Busca dados do usuário
$stmt = $conn->prepare("SELECT id, nome, email, senha, FotoPerfil, role FROM usuarios WHERE nome = ? LIMIT 1");
$stmt->bind_param("s", $usuario_session);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    echo '<p style="text-align:center;color:red;">Usuário não encontrado.</p>';
    $stmt->close();
    exit;
}

$stmt->bind_result($user_id, $nome_db, $email_db, $senha_hash_db, $foto_db, $role_db);
$stmt->fetch();
$stmt->close();

// Define foto (caminho)
$foto_path = 'img/icon_perfil.png';
if (!empty($foto_db) && (file_exists($foto_db) || file_exists(__DIR__ . '/' . $foto_db))) {
    $foto_path = $foto_db;
} elseif (!empty($_SESSION['foto'])) {
    $foto_path = $_SESSION['foto'];
}

// Máscara da senha
$senha_mask = str_repeat('•', 8);

// Mensagem feedback (vindas via GET ?msg=)
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
<link rel="icon" href="img/logo-nova.png" type="image/png">
</head>
<body>
<div class="perfil-container">
    <h2 id="titulo">Perfil de <?php echo htmlspecialchars($usuario_session, ENT_QUOTES, 'UTF-8'); ?></h2>

    <div class="perfil-box">
        <!-- FOTO DE PERFIL -->
        <div class="perfil-foto">
            <img id="foto-perfil" src="<?php echo htmlspecialchars($foto_path, ENT_QUOTES); ?>" alt="Foto de perfil">
            <form id="form-foto" method="post" action="alterarfoto.php" enctype="multipart/form-data" class="form-foto">
                <input id="nova-foto" type="file" name="foto" accept="image/*" class="input-foto" onchange="handleFileChange(this)" style="display:none;">
                <label for="nova-foto" class="botao-foto">Trocar foto</label>
            </form>
        </div>

        <!-- DADOS DO USUÁRIO -->
        <div class="perfil-dados">
            <p><strong>Nome de usuário:</strong> <?php echo htmlspecialchars($nome_db, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($email_db, ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>Papel:</strong> <?php echo htmlspecialchars($role_db ?: 'Usuário comum', ENT_QUOTES, 'UTF-8'); ?></p>

            <!-- Senha mascarada -->
            <div class="form-senha">
                <label for="senhaExib">Senha:</label>
                <input type="text" id="senhaExib" value="<?php echo $senha_mask; ?>" readonly>
                <button type="button" class="botao-senha" onclick="mostrarInfoSenha()">Mostrar/Ocultar</button>
            </div>
            <div id="senha-info-msg" style="display:none;color:#ff00dd;font-family:'VT323',monospace;margin-top:5px;">
                Por segurança, a senha armazenada não pode ser exibida.<br>
                Use o formulário abaixo para alterar sua senha.
            </div>

            <!-- Formulário alterar senha (action aponta para alterar-senha.php) -->
            <form method="post" action="alterar-senha.php" class="form-senha">
                <label for="senha_atual">Senha atual:</label>
                <input type="password" name="senha_atual" id="senha_atual" placeholder="Senha atual">
                <label for="senha_nova">Nova senha:</label>
                <input type="password" name="senha_nova" id="senha_nova" placeholder="Nova senha">
                <label for="senha_confirma">Confirmar nova:</label>
                <input type="password" name="senha_confirma" id="senha_confirma" placeholder="Confirme a nova senha">
                <div style="display:flex;gap:8px;margin-top:10px;">
                    <button type="button" class="botao-senha" onclick="toggleMostrarSenhaCampos()">Mostrar campos</button>
                    <button type="submit" class="botao-senha">Salvar senha</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MENSAGEM DE FEEDBACK -->
    <div class="perfil-info">
        <?php if ($msg): ?>
            <p style="color:lightgreen;"><?php echo $msg; ?></p>
        <?php endif; ?>
        <p>Bem-vindo(a), <?php echo htmlspecialchars($usuario_session, ENT_QUOTES, 'UTF-8'); ?>! 🌙</p>
        <p>Você está conectado à <strong>Alma Mística</strong>.</p>
    </div>

    <!-- LOGOUT / VOLTAR -->
    <form method="post" action="logout.php" style="margin-top:18px;">
        <button type="submit" class="botao-voltar">Logout</button>
    </form>
    <a href="index.php" class="botao-voltar">Voltar para o site</a>
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

<script>
function handleFileChange(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const maxSize = 2 * 1024 * 1024;
    if (file.size > maxSize) { alert('Máx 2MB'); input.value=''; return; }
    const reader = new FileReader();
    reader.onload = function(e){ document.getElementById('foto-perfil').src = e.target.result; };
    reader.readAsDataURL(file);
    input.form.submit();
}

function mostrarInfoSenha() {
    const msg = document.getElementById('senha-info-msg');
    msg.style.display = (msg.style.display === 'block') ? 'none' : 'block';
}

function toggleMostrarSenhaCampos() {
    ['senha_atual','senha_nova','senha_confirma'].forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.type = (el.type === 'password') ? 'text' : 'password';
    });
}
</script>
</body>
</html>
