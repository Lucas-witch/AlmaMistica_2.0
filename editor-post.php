<?php
// editor-post.php (modificado para usar auth.php)
require_once 'auth.php'; // garante sessão e funções de role-check
// exige que usuário seja admin ou editor para acessar o editor
if (!has_role(['admin', 'editor'])) {
    http_response_code(403);
    echo '<div style="color:red;text-align:center;">Acesso negado. Você precisa ser administrador ou editor.</div>';
    exit;
}

// resto do código do editor continua...
// (a partir daqui, mantenha o seu código existente que renderiza o editor)


 //Carrega posts para o modal de inserir link interno
$posts_for_links = [];
$sql = "SELECT id, titulo FROM posts ORDER BY data DESC LIMIT 200";
if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) $posts_for_links[] = $row;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Editor - Alma Mística</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="estilo.css">
    <link rel="stylesheet" href="editor-post.css">
    <link href="https://fonts.googleapis.com/css2?family=VT323&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="icon" href="img/logo-nova.png" type="image/png">
</head>
<body>
    <main class="editor-page">
        <h1 class="titulo-pagina">Nova Postagem</h1>

        <form id="editor-form" method="post" action="nova_postagem.php" enctype="multipart/form-data">
            <!-- 1 - campos básicos -->
            <section class="campo-basico">
                <div class="col">
                    <label for="titulo">Título</label>
                    <input id="titulo" name="titulo" type="text" placeholder="Digite o título">

                    <div class="seletor-titulo">
                        <label for="titulo-cor">Cor do título</label>
                        <input id="titulo-cor" type="color" value="#5e2d89" title="Cor do título">
                        <label for="titulo-font" style="margin-left:10px;">Fonte</label>
                        <select id="titulo-font" class="mini">
                            <option value="VT323, monospace">VT323</option>
                            <option value="'Press Start 2P', monospace">Press Start 2P</option>
                            <option value="'Minecraft', monospace">Minecraft</option>
                            <option value="Arial, sans-serif">Arial</option>
                        </select>
                        <label for="titulo-size" style="margin-left:6px;">Tamanho</label>
                        <select id="titulo-size" class="mini size-select">
                            <option value="20px">20</option>
                            <option value="24px">24</option>
                            <option value="28px" selected>28</option>
                            <option value="32px">32</option>
                            <option value="36px">36</option>
                        </select>
                    </div>
                </div>

                <div class="col">
                    <label for="tema">Tema</label>
                    <select id="tema" name="tema">
                        <option value="">-- Selecionar tema --</option>
                        <option value="Vida pessoal">Vida pessoal</option>
                        <option value="Bruxaria">Bruxaria</option>
                        <option value="Livros e poesias">Livros e poesias</option>
                        <option value="Assuntos aleatórios">Assuntos aleatórios</option>
                    </select>

                    <label for="autor">Autor</label>
                    <input id="autor" name="autor" type="text" value="<?= htmlspecialchars($_SESSION['usuario'] ?? 'Anônimo') ?>">

                    <div class="seletor-titulo">
                        <label for="meta-cor">Cor tema/autor</label>
                        <input id="meta-cor" type="color" value="#6b2d86" title="Cor tema/autor">
                        <label for="meta-font" style="margin-left:10px;">Fonte</label>
                        <select id="meta-font" class="mini">
                            <option value="VT323, monospace">VT323</option>
                            <option value="'Press Start 2P', monospace">Press Start 2P</option>
                            <option value="'Minecraft', monospace">Minecraft</option>
                            <option value="Arial, sans-serif">Arial</option>
                        </select>
                        <label for="meta-size" style="margin-left:6px;">Tamanho</label>
                        <select id="meta-size" class="mini size-select">
                            <option value="10px">10</option>
                            <option value="12px" selected>12</option>
                            <option value="14px">14</option>
                            <option value="16px">16</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- 2 - toolbar e editor WYSIWYG -->
            <section class="editor-section">
                <div class="editor-toolbar" role="toolbar" aria-label="Editor">
                    <button type="button" class="tool-btn" data-cmd="undo" title="Desfazer">↺</button>
                    <button type="button" class="tool-btn" data-cmd="redo" title="Refazer">↻</button>

                    <button type="button" class="tool-btn" data-cmd="bold" title="Negrito"><strong>B</strong></button>
                    <button type="button" class="tool-btn" data-cmd="italic" title="Itálico"><em>I</em></button>
                    <button type="button" class="tool-btn" data-cmd="underline" title="Sublinhado"><u>U</u></button>
                    <button type="button" class="tool-btn" data-cmd="strikeThrough" title="Riscado">S</button>

                    <button type="button" class="tool-btn" id="btn-highlight" title="Grifar">🖍</button>
                    <button type="button" class="tool-btn" id="btn-color" title="Cor do texto">A⃤</button>

                    <button type="button" class="tool-btn" data-cmd="insertUnorderedList" title="Lista">•</button>
                    <button type="button" class="tool-btn" data-cmd="insertOrderedList" title="Lista num">1.</button>
                    <button type="button" class="tool-btn" data-cmd="justifyLeft" title="Alinhar esquerda">⟸</button>
                    <button type="button" class="tool-btn" data-cmd="justifyCenter" title="Centralizar">⇺</button>
                    <button type="button" class="tool-btn" data-cmd="justifyRight" title="Alinhar direita">⟹</button>
                    <button type="button" class="tool-btn" data-cmd="justifyFull" title="Justificar">☰</button>

                    <button type="button" class="tool-btn" id="btn-link" title="Inserir link">🔗</button>

                    <label class="mini">Parágrafo/Heading</label>
                    <select id="heading-select" class="mini">
                        <option value="p">Parágrafo</option>
                        <option value="h2">Subtítulo (H2)</option>
                        <option value="h3">Subtítulo (H3)</option>
                    </select>

                    <label class="mini">Fonte</label>
                    <select id="font-family" class="mini">
                        <option value="VT323, monospace">VT323</option>
                        <option value="'Press Start 2P', monospace">Press Start 2P</option>
                        <option value="'Minecraft', monospace">Minecraft</option>
                        <option value="Arial, sans-serif">Arial</option>
                    </select>

                    <label class="mini">Tamanho</label>
                    <select id="font-size" class="mini size-select">
                        <option value="14px">14</option>
                        <option value="16px" selected>16</option>
                        <option value="18px">18</option>
                        <option value="20px">20</option>
                        <option value="22px">22</option>
                        <option value="26px">26</option>
                    </select>
                </div>

                <div id="conteudo-editor" class="editor-content" contenteditable="true" aria-label="Área de edição"></div>

                <div class="editor-actions" style="margin-top:10px;">
                    <label for="imagem">Imagem (opcional)</label>
                    <input type="file" name="imagem" id="imagem" accept="image/*">
                </div>
            </section>

            

            <!-- 4 - modal inserir link -->
            <div id="link-modal" class="link-modal" hidden>
                <div class="link-modal-inner">
                    <h3>Inserir link</h3>
                    <label><input type="radio" name="link-type" value="internal" checked> Link interno (selecione um post)</label>
                    <label><input type="radio" name="link-type" value="external"> Link externo (URL)</label>

                    <div id="link-internal">
                        <label for="internal-post-select">Escolher post:</label>
                        <select id="internal-post-select">
                            <option value="">-- selecionar --</option>
<?php foreach ($posts_for_links as $p): ?>
                            <option value="post.php?id=<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['titulo']) ?></option>
<?php endforeach; ?>
                        </select>
                        <label for="internal-text">Texto visível (opcional):</label>
                        <input id="internal-text" type="text" placeholder="Texto do link (opcional)">
                    </div>

                    <div id="link-external" style="display:none;">
                        <label for="external-url">URL (https://...)</label>
                        <input id="external-url" type="text" placeholder="https://">
                        <label for="external-text">Texto visível (opcional):</label>
                        <input id="external-text" type="text" placeholder="Texto do link (opcional)">
                    </div>

                    <div style="margin-top:8px;">
                        <button type="button" id="apply-link">Inserir</button>
                        <button type="button" id="cancel-link">Cancelar</button>
                    </div>
                </div>
            </div>

            <!-- 5 - hidden fields -->
            <input type="hidden" name="conteudo" id="conteudo-hidden">
            <input type="hidden" name="estilo_post" id="estilo_post">
            <br><br><br>
            <div class="form-submit" style="margin-top:14px;">
                <button type="submit" class="botao-publicar">Publicar</button>
                <a href="index.php" class="botao-cancel">Voltar ao site</a>
            </div>
        </form>

        <!-- preview -->
        <section class="preview-section" style="margin-top:18px;">
            <h2>Pré-visualização (ao vivo)</h2>
            <div id="preview-wrapper">
                <style id="preview-style"></style>
                <article class="post-preview">
                    <h3 id="preview-titulo" class="post-title">Título de exemplo</h3>
                    <div id="preview-conteudo" class="post-conteudo"></div>
                    <div class="post-meta"><span id="preview-autor">Autor</span> • <span id="preview-data">Hoje</span></div>
                </article>
            </div>
        </section>
    </main>

    <script src="editor-post.js"></script>
</body>
</html>
