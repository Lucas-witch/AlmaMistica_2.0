<?php
// index.php - versão atualizada (CSRF, botões editar/excluir, compatibilidade auth)
if (session_status() === PHP_SESSION_NONE) session_start();

// Gera token CSRF por sessão (se ainda não existir)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}

// tenta incluir auth.php se existir (para has_role())
if (file_exists(__DIR__ . '/auth.php')) {
    require_once __DIR__ . '/auth.php';
}

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
$limit = 3; // exibir 3 posts por página/inicialmente
$offset = 0;

// Caso 1: Se há uma busca, ignora o filtro de tema e mostra resultados da pesquisa
if ($busca !== '') {
    $stmt = $conn->prepare("SELECT id, titulo, autor, tema, data FROM posts WHERE titulo LIKE ? OR conteudo LIKE ? OR tema LIKE ? ORDER BY data DESC LIMIT ? OFFSET ?");
    $like = "%$busca%";
    $stmt->bind_param("sssii", $like, $like, $like, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

// Caso 2: Se há um tema selecionado, filtra pelo tema
} elseif ($tema_selecionado && in_array($tema_selecionado, $temas)) {
    // retornar apenas $limit iniciais
    $stmt = $conn->prepare("SELECT id, titulo, autor, tema, data FROM posts WHERE tema = ? ORDER BY data DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $tema_selecionado, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

// Caso 3: Nenhum filtro, mostra últimas postagens (limitadas)
} else {
    $stmt = $conn->prepare("SELECT id, titulo, autor, tema, data FROM posts ORDER BY data DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
}

// Guarda os resultados no array $posts
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}

// Função utilitária: formata data para exibição
function formatarData($d) {
    if (empty($d) || $d === '0000-00-00 00:00:00') return '';
    $ts = strtotime($d);
    return ($ts === false) ? $d : date("d-m-Y H:i", $ts);
}

// Função para resolver avatar (usa FotoPerfil se existir, senão fallback)
function resolverAvatar($candidate) {
    $fallback = 'img/perfil_padrao.png';
    if (empty($candidate)) return $fallback;
    $candidate = trim($candidate);
    if (preg_match('#^https?://#i', $candidate)) return $candidate;
    if (file_exists(__DIR__ . '/' . $candidate)) return $candidate;
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($candidate, '/'))) return $candidate;
    return $fallback;
}

// --- ADICIONADO: alias/local wrapper para evitar função indefinida no template...
function resolverAvatarLocal($candidate) {
    return resolverAvatar($candidate ?? '');
}

// --- ADICIONADO: detectar se coluna usuario_id existe na tabela comentarios ---
$has_usuario_id = false;
$chk_col = $conn->query("SHOW COLUMNS FROM comentarios LIKE 'usuario_id'");
if ($chk_col && $chk_col->num_rows > 0) $has_usuario_id = true;

// --- ADICIONADO: busca comentários do "bate-papo" (apenas post_id IS NULL) ---
$comentarios_chat = [];
$limit = 10;

if ($has_usuario_id) {
    $sql = "SELECT c.id, c.mensagem, c.data, c.usuario_id, COALESCE(u.nome, c.nome) AS nome, u.FotoPerfil
            FROM comentarios c
            LEFT JOIN usuarios u ON c.usuario_id = u.id
            WHERE c.post_id IS NULL
            ORDER BY c.data DESC  /* Modificado: DESC em vez de ASC */
            LIMIT ?";
    $stmt_c = $conn->prepare($sql);
    if ($stmt_c) {
        $stmt_c->bind_param("i", $limit);
        if ($stmt_c->execute()) {
            $res = $stmt_c->get_result();
            while ($r = $res->fetch_assoc()) $comentarios_chat[] = $r;
        }
        $stmt_c->close();
    }
} else {
    $sql = "SELECT id, nome, mensagem, data 
            FROM comentarios
            WHERE post_id IS NULL
            ORDER BY data DESC  /* Modificado: removido subquery e ORDER BY ASC */
            LIMIT ?";
    $stmt_c = $conn->prepare($sql);
    if ($stmt_c) {
        $stmt_c->bind_param("i", $limit);
        if ($stmt_c->execute()) {
            $res = $stmt_c->get_result();
            while ($r = $res->fetch_assoc()) $comentarios_chat[] = $r;
        }
        $stmt_c->close();
    }
}

// Remover o array_reverse - não é mais necessário pois o ORDER BY DESC já traz na ordem correta
//$comentarios_chat = array_reverse($comentarios_chat);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Alma Mística</title>
    <link rel="stylesheet" href="estilo.css">
    <link rel="icon" href="img/logo-nova.png" type="image/png">
    <link rel="apple-touch-icon" href="img/logo-nova.png">
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
                        <input type="text" name="busca" placeholder="Pesquisar post..." class="input-pesquisa" value="<?php echo htmlspecialchars($busca, ENT_QUOTES); ?>">
                        <button type="submit" class="botao-pesquisa"><img width="15" height="15" class="pesquisar" src="https://img.icons8.com/android/24/search.png" alt="search"/></button>
                    </form>
                    <br>
                    <?php if (isset($_SESSION['usuario'])): ?>
                    <div class="quadro" style="margin-bottom:18px;">
                        <h2>Configurações</h2>
                        <ul>
                            <li><a href="perfil.php" style="font-family:'Minecraft',monospace;font-size:16px;">Perfil</a></li>
                            <li><a href="sobre.html" style="font-family:'Minecraft',monospace;font-size:16px;">Sobre a página</a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                    <div class="quadro">
                        <h2>Assuntos do Blog</h2>
                        <ul>
                            <?php foreach ($temas as $tema): ?>
                                <li>
                                    <a href="index.php?tema=<?php echo urlencode($tema); ?>" <?php if ($tema_selecionado === $tema): ?>style="font-weight:bold;color:#ffd700;"<?php endif; ?>>
                                        <?php echo htmlspecialchars($tema); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <!-- fim esquerda -->
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
                // define $isAdmin / $isEditor de forma robusta (usa has_role() se disponível)
                $isAdmin = false;
                $isEditor = false;
                if (function_exists('has_role')) {
                    $isAdmin = has_role(['admin']);
                    $isEditor = has_role(['editor', 'admin']);
                } else {
                    // fallback: se não houver role na sessão, e houver id do usuário, buscar no banco
                    if (isset($_SESSION['id']) && is_numeric($_SESSION['id'])) {
                        $uid = (int) $_SESSION['id'];
                        $q = $conn->prepare("SELECT role, nivel FROM usuarios WHERE id = ? LIMIT 1");
                        if ($q) {
                            $q->bind_param("i", $uid);
                            $q->execute();
                            $q->bind_result($role_from_db, $nivel_db);
                            if ($q->fetch()) {
                                if ($role_from_db === 'admin' || (is_numeric($nivel_db) && intval($nivel_db) >= 10)) $isAdmin = true;
                                if ($role_from_db === 'editor' || $role_from_db === 'admin' || (is_numeric($nivel_db) && intval($nivel_db) >= 5)) $isEditor = true;
                            }
                            $q->close();
                        }
                    } else {
                        // fallback direto por sessão role
                        if (isset($_SESSION['role'])) {
                            if ($_SESSION['role'] === 'admin') $isAdmin = true;
                            if (in_array($_SESSION['role'], ['editor','admin'], true)) $isEditor = true;
                        }
                    }
                }

                if ($isAdmin) {
                    echo '<a href="editor-post.php" class="botao" style="margin-bottom:15px;display:inline-block;">Nova Postagem</a>';
                }
                ?>

                <!-- container para posts (inicialmente com $posts carregados) -->
                <div id="posts-container">
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <h3><a href="post.php?id=<?php echo (int)$post['id']; ?>"><?php echo !empty($post['titulo']) ? htmlspecialchars($post['titulo']) : '<span style="color:red;">(Sem título)</span>'; ?></a></h3>
                        <p>
                            Data: <?php echo date('d/m/Y', strtotime($post['data'])); ?> |
                            Autor: <?php echo !empty($post['autor']) ? htmlspecialchars($post['autor']) : '<span style="color:red;">(Sem autor)</span>'; ?> |
                            Tema:
                            <?php if (!empty($post['tema'])): ?>
                                <a href="index.php?tema=<?php echo urlencode($post['tema']); ?>" style="color:#ffd700;text-decoration:underline;"><?php echo htmlspecialchars($post['tema']); ?></a>
                            <?php else: ?>
                                <span style="color:red;">(Sem tema)</span>
                            <?php endif; ?>
                        </p>

                        <?php
                        // Controles (Editar / Excluir) — Editar: editor/admin; Excluir: apenas admin
                        if ($isEditor) {
                            echo '<a class="btn btn-edit" href="editar-conteudo-post.php?id=' . urlencode($post['id']) . '" style="display:inline-block;margin-right:6px;padding:6px 10px;background:#7c5cff;color:#fff;border-radius:6px;text-decoration:none;">Editar</a>';
                        }

                        if ($isAdmin) {
                            echo '<form method="post" action="excluir_post.php" style="display:inline;margin-left:6px;" onsubmit="return confirm(\'Tem certeza que deseja excluir esta postagem?\');">';
                            echo '<input type="hidden" name="id" value="' . htmlspecialchars($post['id'], ENT_QUOTES) . '">';
                            echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES) . '">';
                            echo '<button type="submit" class="botao" style="background:#d00;color:#fff;margin-left:8px;border:none;padding:6px 10px;border-radius:6px;cursor:pointer;">Excluir</button>';
                            echo '</form>';
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
                </div> <!-- /#posts-container -->

                <!-- Loader / sentinel para infinite scroll -->
                <div id="load-sentinel" style="text-align:center;padding:12px;color:#fff;">
                    <button id="load-more-btn" style="display:none;background:#ff00dd;border:none;color:#fff;padding:8px 12px;border-radius:6px;cursor:pointer;">Carregar mais</button>
                    <div id="loading-indicator" style="display:none;color:#fff;">Carregando...</div>
                </div>

                <?php if (empty($posts)): ?>
                    <p id="no-posts-msg" style="color:#fff;">Nenhuma postagem encontrada</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Coluna Direita -->
        <div>
            <?php if (!isset($_SESSION['usuario'])): ?>
                <!-- caderno-visitas (quando não logado) -->
                <div class="caderno-visitas">
                    <img src="img/download.png" alt="livrinho">
                    <h3>Livro da Besta</h3>
                    <p>Entre ou crie sua conta para acessar conteúdos mágicos! Torne-se uma alma mística também!</p>
                    <div style="margin-bottom:10px; color:#794CA9; font-size:16px;">
                        <?php echo $total_cadastros; ?> pessoas já assinaram o seu nome, assine você também.
                    </div>
                    <div class="container-login-cadastro">
                        <a href="login.php"><input type="submit" value="Login" class="botao-login"></a> <br><br>
                        <a href="cadastro.php"><input type="submit" value="Cadastro" class="botao-cadastro"></a> <br><br>
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
                        if (!empty($comentarios_chat)) {
                            foreach ($comentarios_chat as $c) {
                                $nomeC = htmlspecialchars($c['nome'] ?? 'Anônimo', ENT_QUOTES, 'UTF-8');
                                $msgC  = nl2br(htmlspecialchars($c['mensagem'] ?? '', ENT_QUOTES, 'UTF-8'));
                                $dataC = !empty($c['data']) ? date('d/m H:i', strtotime($c['data'])) : '';
                                $fotoPerfilRaw = $c['FotoPerfil'] ?? ($c['foto'] ?? '');
                                $avatar = resolverAvatarLocal($fotoPerfilRaw);
                                echo '<div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:8px;background-color:#eeeeeed6;padding:5px;border-radius:8px;">';
                                echo '<img src="' . htmlspecialchars($avatar, ENT_QUOTES) . '" alt="avatar" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">';
                                echo '<div style="flex:1;">';
                                echo '<div><span class="comentario-nome">' . $nomeC . '</span> <span class="comentario-data">' . $dataC . '</span></div>';
                                echo '<div class="comentario-msg" style="margin-top:6px;">' . $msgC . '</div>';
                                echo '</div></div>';
                            }
                        } else {
                            echo '<div style="color:#666;text-align:center;padding:6px;">Nenhuma mensagem ainda.</div>';
                        }
                        ?>
                    </div>

                    <form class="comentario-form-horizontal" method="post" action="comentario.php" style="width:100%;display:flex;flex-direction:column;align-items:center;gap:7px;">
                        <input type="hidden" name="post_id" value="0">
                        <textarea name="mensagem" placeholder="Mensagem..." maxlength="250" required style="width:80%;margin:0 auto;padding:6px;border:2px solid #ff00dd;border-radius:5px;font-family:'VT323',monospace;background:#f1b0fe22;font-size:14px;min-height:28px;resize:vertical;display:block;text-align:center;"></textarea>
                        <input type="submit" value="Enviar" style="background-color:#794CA9;color:#fff;border:none;border-radius:5px;padding:5px 18px;font-size:13px;font-family:'Press Start 2P',monospace;cursor:pointer;transition:background 0.3s;display:block;margin:0 auto;">
                    </form>
                </div>

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

<script>
(function(){
    const limit = <?php echo (int)$limit; ?>;
    let offset = <?php echo count($posts); ?>;
    const tema = <?php echo json_encode($tema_selecionado); ?>;
    const busca = <?php echo json_encode($busca); ?>;
    const sentinel = document.getElementById('load-sentinel');
    const postsContainer = document.getElementById('posts-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    const loadMoreBtn = document.getElementById('load-more-btn');

    let loading = false;
    async function loadMore() {
        if (loading) return;
        loading = true;
        loadingIndicator.style.display = '';
        try {
            const params = new URLSearchParams({ offset: offset, limit: limit });
            if (tema) params.set('tema', tema);
            if (busca) params.set('busca', busca);
            const res = await fetch('fetch_posts.php?' + params.toString(), { credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const html = await res.text();
            if (!html || html.trim() === '') {
                // sem mais posts, remove observer / botão
                observer.disconnect();
                loadMoreBtn.style.display = 'none';
                loadingIndicator.style.display = 'none';
                loading = false;
                return;
            }
            // append html
            const temp = document.createElement('div');
            temp.innerHTML = html;
            // append children
            while (temp.firstChild) postsContainer.appendChild(temp.firstChild);
            // atualiza offset
            offset += limit;
            loadingIndicator.style.display = 'none';
            loading = false;
        } catch (err) {
            console.error('Erro fetch posts:', err);
            loadingIndicator.style.display = 'none';
            loadMoreBtn.style.display = '';
            loading = false;
        }
    }

    // botão fallback
    loadMoreBtn.addEventListener('click', loadMore);

    // IntersectionObserver para auto-load
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadMore();
            }
        });
    }, { rootMargin: '200px' });

    observer.observe(sentinel);
})();
</script>

<?php $conn->close(); ?>
