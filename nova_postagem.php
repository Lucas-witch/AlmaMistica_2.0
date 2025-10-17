<?php
// nova_postagem.php
// Processa o form de nova postagem, salva no banco e arquivos em posts/, e redireciona para index.php

// IMPORTANTE: conexao.php precisa definir $conn (mysqli) e NÃO enviar nenhum output.
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Acesso direto: volta ao index
    header('Location: index.php');
    exit;
}

// Faz sanitize básico das entradas
$titulo      = trim($_POST['titulo'] ?? '');
$conteudo    = trim($_POST['conteudo'] ?? '');
$tema        = trim($_POST['tema'] ?? '');
$autor       = trim($_POST['autor'] ?? 'Anônimo');
$imagem_url  = trim($_POST['imagem_url'] ?? '');
$estilo_post = trim($_POST['estilo_post'] ?? '');

// Se faltar dados mínimos, volta com erro simples
if ($titulo === '' || $conteudo === '' || $tema === '') {
    // redireciona com flag de erro
    header('Location: index.php?post_error=1');
    exit;
}

// ======== TENTAR SALVAR NO BANCO (preferível) ========
$salvo_com_sucesso = false;
$last_id = null;

if (isset($conn) && $conn instanceof mysqli) {
    $stmt = $conn->prepare("
        INSERT INTO posts (titulo, conteudo, tema, autor, data, imagem_url, estilo_post)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)
    ");

    if ($stmt) {
        $stmt->bind_param("ssssss", $titulo, $conteudo, $tema, $autor, $imagem_url, $estilo_post);
        if ($stmt->execute()) {
            $last_id = $conn->insert_id;
            $salvo_com_sucesso = true;
        } else {
            // erro ao executar (vai cair para fallback)
            $salvo_com_sucesso = false;
        }
        $stmt->close();
    } else {
        // falha no prepare -> fallback adiante
        $salvo_com_sucesso = false;
    }
}

// ======== SE NÃO SALVOU NO BANCO, FAZER FALBACK EM ARQUIVOS LOCAIS ========
if (!$salvo_com_sucesso) {
    $dir = __DIR__ . '/posts';
    if (!is_dir($dir)) {
        // tentar criar diretório
        if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
            // não conseguiu criar a pasta -> redireciona com erro
            header('Location: index.php?post_error=1');
            exit;
        }
    }

    // gerar um filename seguro baseado em timestamp
    $timestamp = time();
    $safe_title = preg_replace('/[^a-z0-9-_]/i', '_', mb_strimwidth($titulo, 0, 60));
    $last_id = $timestamp . '_' . $safe_title; // identificador alternativo
    $html_file = $dir . '/post_' . $last_id . '.html';
    $css_file  = $dir . '/post_' . $last_id . '.css';

    // Conteúdo HTML mínimo (pode ser lido pelo index.php depois)
    $html_content = "<article class='post'>\n";
    $html_content .= "<h2 class='post-title'>" . htmlspecialchars($titulo) . "</h2>\n";
    $html_content .= "<div class='post-meta'>Autor: " . htmlspecialchars($autor) . " | Tema: " . htmlspecialchars($tema) . "</div>\n";
    $html_content .= "<div class='post-conteudo'>\n" . $conteudo . "\n</div>\n";
    $html_content .= "</article>\n";

    if (file_put_contents($html_file, $html_content) === false) {
        header('Location: index.php?post_error=1');
        exit;
    }

    // salva CSS se houver
    if ($estilo_post !== '') {
        file_put_contents($css_file, $estilo_post);
    }

    // sinaliza sucesso do fallback
    $salvo_com_sucesso = true;
}

// ======== SE CHEGOU AQUI: SALVOU (no DB ou em arquivos) ========
if ($salvo_com_sucesso) {
    // redireciona para index. Opcional: passar id ou flag para mostrar mensagem
    // Se usar DB e quiser mostrar o post, pode passar ?sucesso=1&id=...
    $params = 'sucesso=1';
    if ($last_id !== null) {
        // usar urlencode por segurança
        $params .= '&post=' . urlencode($last_id);
    }
    header('Location: index.php?' . $params);
    exit;
} else {
    // caso improvável: erro final
    header('Location: index.php?post_error=1');
    exit;
}
