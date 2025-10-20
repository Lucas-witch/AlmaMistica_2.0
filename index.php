<?php
/* ÍNDICE: 1-Início 2-Conexão/Session 3-Lógica Principal 4-Formulários/Ações 5-Rodapé */
session_start();

// Lista de temas fixos
$temas = ['Vida pessoal', 'Bruxaria', 'Livros e poesias', 'Assuntos aleatórios'];
$tema_selecionado = isset($_GET['tema']) ? $_GET['tema'] : '';
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// Conexão com o banco de dados
$conn = new mysqli('localhost', 'root', '', 'alma_db');
if ($conn->connect_error) {
    die('<div style="color:red;text-align:center;">Erro ao conectar ao banco: ' . htmlspecialchars($conn->connect_error) . '</div>');
}

// Contagem de visitantes
$sql_visitas = "SELECT COUNT(*) AS total FROM visitas";
$result_visitas = $conn->query($sql_visitas);
$total_cadastros = ($result_visitas && $result_visitas->num_rows > 0) ? $result_visitas->fetch_assoc()['total'] : 0;

// Usuários online
$sql_online = "SELECT COUNT(*) AS online FROM online_users WHERE last_activity > (NOW() - INTERVAL 5 MINUTE)";
$result_online = $conn->query($sql_online);
$usuarios_online = ($result_online && $result_online->num_rows > 0) ? $result_online->fetch_assoc()['online'] : 0;

// --- POSTS ---
$posts = [];

// Caso 1: Se há uma busca, ignora o filtro de tema e mostra resultados da pesquisa
if ($busca !== '') {
    $stmt = $conn->prepare("SELECT id, titulo, autor, tema, data FROM posts WHERE titulo LIKE ? OR conteudo LIKE ? OR tema LIKE ? ORDER BY data DESC");
    $like = "%$busca%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();

// Caso 2: Se há um tema selecionado, filtra pelo tema
} elseif ($tema_selecionado && in_array($tema_selecionado, $temas)) {
    $stmt = $conn->prepare("SELECT id, titulo, autor, tema, data FROM posts WHERE tema = ? ORDER BY data DESC LIMIT 5");
    $stmt->bind_param("s", $tema_selecionado);
    $stmt->execute();
    $result = $stmt->get_result();

// Caso 3: Nenhum filtro, mostra últimas postagens
} else {
    $result = $conn->query("SELECT id, titulo, autor, tema, data FROM posts ORDER BY data DESC LIMIT 5");
}

// Guarda os resultados no array $posts
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Alma Mística</title>
    <link rel="stylesheet" href="estilo.css">
    <!-- favicon padrão -->
    <link rel="icon" href="img/logo-nova.png" type="image/png">

    <!-- ícone para iPhone/iPad -->
    <link rel="apple-touch-icon" href="img/logo-nova.png">

    <!-- ícone para Android/Chrome -->
    <link rel="shortcut icon" href="img/logo-nova.png" type="image/png">
</head>
<body>
    <header>
        <img src="img/logo-nova.png" alt="Logo Alma Mística" class="logo-centro">
        <h1>Alma Mística</h1>
        <h3 id="bem-vinde">Sejam bem-vindes ao meu primeiro website...</h3>
    </header>

    <div class="layout-3-colunas">
        <!-- Coluna Esquerda -->
        <div>
            <div class="nav-logo-box">
                <div>
                    <form method="get" action="index.php" class="form-pesquisa">
                        <input type="text" name="busca" placeholder="Pesquisar post..." class="input-pesquisa">
                         <button type="submit" class="botao-pesquisa"><img width="15" height="15" class="pesquisar" src="https://img.icons8.com/android/24/search.png" alt="search"/><link></button>
                    </form>
                    <br>
                    <?php
                    if (isset($_SESSION['usuario'])): ?>
                    <div class="quadro" style="margin-bottom:18px;">
                        <h2>Configurações</h2>
                        <ul>
                            <li>
                                <a href="perfil.php" style="font-family:'Minecraft',monospace;font-size:16px;">
                                    Perfil
                                </a>
                            </li>
                            <li>
                                <a href="sobre.html" style="font-family:'Minecraft',monospace;font-size:16px;">
                                    Sobre a página
                                </a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <div class="quadro">
                        <h2>Assuntos do Blog</h2>
                        <ul>
                            <?php foreach ($temas as $tema): ?>
                                <li>
                                    <a href="index.php?tema=<?php echo urlencode($tema); ?>"
                                       <?php if ($tema_selecionado === $tema): ?>style="font-weight:bold;color:#ffd700;"<?php endif; ?>>
                                        <?php echo $tema; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php if (!isset($_SESSION['usuario'])): ?>
            <div class="quadro-batepapo-lateral">
                <h3>Bate-papo Místico</h3>
                <div style="text-align:right; font-size:14px; color:#794CA9; margin-bottom:6px;">
                    <?php echo $usuarios_online; ?> pessoas online
                </div>
                <div class="comentarios-lista-lateral">
                    <?php
                    // Busca os 5 comentários mais recentes do banco
                    $sql = "SELECT nome, mensagem, data FROM comentarios ORDER BY data DESC LIMIT 5";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div class="comentario-item-lateral">';
                            echo '<img src="img/icon_perfil.png" alt="Foto de perfil" class="icon-externo" style="width:28px;height:28px;margin-right:6px;vertical-align:middle;">';
                            echo '<span class="comentario-nome-lateral">' . htmlspecialchars($row['nome']) . '</span> <span class="comentario-data-lateral">(' . $row['data'] . ')</span><br>';
                            echo '<span class="comentario-msg-lateral">' . nl2br(htmlspecialchars($row['mensagem'])) . '</span>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="comentario-vazio-lateral">Seja o primeiro a comentar!</p>';
                    }
                    ?>
                    
                </div>
                <form class="comentario-form-lateral" method="post" action="comentario.php">
                    <textarea name="mensagem" placeholder="Mensagem..." maxlength="100" required class="comentario-textarea-lateral"></textarea>
                    <input type="submit" value="Enviar" class="botao-comentario-lateral">
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Coluna Central -->
        <div>
            <div class="quadro">
                <h2>
                    <?php
                    echo $tema_selecionado && in_array($tema_selecionado, $temas)
                        ? 'Postagens de ' . htmlspecialchars($tema_selecionado)
                        : 'Últimas Postagens';
                    ?>
                </h2>
                <?php
                $isAdmin = (
                    (isset($_SESSION['usuario']) && $_SESSION['usuario'] === 'eu-sou-gay589') ||
                    (isset($_SESSION['email'])   && $_SESSION['email']   === 'leo2008@gmail.com')
                );

                if ($isAdmin) {
                    echo '<a href="editor-post.php" class="botao" style="margin-bottom:15px;display:inline-block;">Nova Postagem</a>';
                }
                ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <h3>
                            <a href="post.php?id=<?php echo $post['id']; ?>">
                                <?php echo !empty($post['titulo']) ? htmlspecialchars($post['titulo']) : '<span style="color:red;">(Sem título)</span>'; ?>
                            </a>
                        </h3>
                        <p>
                            Data: <?php echo date('d/m/Y', strtotime($post['data'])); ?> |
                            Autor: <?php echo !empty($post['autor']) ? htmlspecialchars($post['autor']) : '<span style="color:red;">(Sem autor)</span>'; ?> |
                            Tema: 
                            <?php if (!empty($post['tema'])): ?>
                                <a href="index.php?tema=<?php echo urlencode($post['tema']); ?>" style="color:#ffd700;text-decoration:underline;">
                                    <?php echo htmlspecialchars($post['tema']); ?>
                                </a>
                            <?php else: ?>
                                <span style="color:red;">(Sem tema)</span>
                            <?php endif; ?>
                        </p>
                        <?php
                        // Botão de excluir para admin
                        if (isset($_SESSION['usuario']) && isset($_SESSION['email']) &&
                            $_SESSION['usuario'] === 'eu-sou-gay589' &&
                            $_SESSION['email'] === 'leo2008@gmail.com') {
                            echo '<form method="post" action="excluir_post.php" style="display:inline;">';
                            echo '<input type="hidden" name="id" value="' . $post['id'] . '">';
                            echo '<button type="submit" class="botao" style="background:#d00;color:#fff;margin-left:8px;">Excluir</button>';
                            echo '</form>';
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($posts)): ?>
                    <p style="color:#fff;">Nenhuma postagem encontrada</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna Direita -->
        <div>
            <?php if (!isset($_SESSION['usuario'])): ?>
            <div class="caderno-visitas">
                <img src="img/download.png" alt="livrinho">
                <h3>Livro da Besta</h3>
                <p>Entre ou crie sua conta para acessar conteúdos mágicos! Torne-se uma alma mística também!</p>
                <div style="margin-bottom:10px; color:#794CA9; font-size:16px;">
                    <?php echo $total_cadastros; ?> pessoas já assinaram o seu nome, assine você também.
                </div>
                <div class="container-login-cadastro">
                    <a href="login.php">
                        <input type="submit" value="Login" class="botao-login">
                    </a> <br><br>
                    <a href="cadastro.php">
                        <input type="submit" value="Cadastro" class="botao-cadastro">
                    </a> <br><br>
                </div>
            </div>
            <?php else: ?>
            <!-- Bate-papo horizontal ocupando toda a área -->
            <div class="quadro-batepapo-horizontal" style="background:#e0faff;border:2px solid #794CA9;border-radius:10px;box-shadow:0 0 8px #ff00dd44;padding:18px 18px;min-height:220px;display:flex;flex-direction:column;align-items:center;justify-content:flex-start;">
                <h3 style="color:#794CA9;font-family:'Press Start 2P',monospace;font-size:20px;margin-bottom:12px;text-align:center;">Bate-papo Místico</h3>
                <div style="width:100%;text-align:right; font-size:15px; color:#794CA9; margin-bottom:8px;">
                    <?php echo $usuarios_online; ?> pessoas online
                </div>
                <div class="comentarios-lista-horizontal" style="width:100%;max-height:140px;overflow-y:auto;background:#fff;border-radius:8px;padding:10px;margin-bottom:10px;">
                    <?php
                    // Busca os 10 comentários mais recentes do banco
                    $sql = "SELECT nome, mensagem, data FROM comentarios ORDER BY data DESC LIMIT 10";
                    $result = $conn->query($sql);
                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<div style="display:flex;align-items:center;margin-bottom:8px;padding:6px 0;border-bottom:1px dashed #b2f4e5;">';
                            echo '<img src="img/icon_perfil.png" alt="Foto de perfil" style="width:32px;height:32px;margin-right:10px;border-radius:50%;box-shadow:0 0 4px #ff00dd99;">';
                            echo '<div>';
                            echo '<span style="color:#ff00dd;font-family:\'Press Start 2P\',monospace;font-size:15px;">' . htmlspecialchars($row['nome']) . '</span> ';
                            echo '<span style="color:#755ECE;font-size:13px;font-family:\'Minecraft\',monospace;">(' . $row['data'] . ')</span><br>';
                            echo '<span style="color:#794CA9;font-size:15px;font-family:\'VT323\',monospace;">' . nl2br(htmlspecialchars($row['mensagem'])) . '</span>';
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p style="color:#794CA9;font-family:\'VT323\',monospace;text-align:center;margin:6px 0;">Seja o primeiro a comentar!</p>';
                    }
                    ?>
                </div>
                <form class="comentario-form-horizontal" method="post" action="comentario.php" style="width:100%;display:flex;flex-direction:column;align-items:center;gap:7px;">
                    <textarea name="mensagem" placeholder="Mensagem..." maxlength="100" required style="width:80%;margin:0 auto;padding:6px;border:2px solid #ff00dd;border-radius:5px;font-family:'VT323',monospace;background:#f1b0fe22;font-size:14px;min-height:28px;resize:vertical;display:block;text-align:center;"></textarea>
                    <input type="submit" value="Enviar" style="background-color:#794CA9;color:#fff;border:none;border-radius:5px;padding:5px 18px;font-size:13px;font-family:'Press Start 2P',monospace;cursor:pointer;transition:background 0.3s;display:block;margin:0 auto;">
                </form>
            </div>
            <!-- Botão de logout menor, fora da caixa do bate-papo -->
            <form method="post" action="logout.php" style="width:100%;margin-top:8px;text-align:center;">
                <button type="submit" style="background:none;border:none;color:#ff00dd;font-size:13px;font-family:'Press Start 2P',monospace;cursor:pointer;">Logout</button>
            </form>
            <?php endif; ?>
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
<?php $conn->close(); ?>