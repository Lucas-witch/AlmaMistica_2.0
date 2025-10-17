<?php
/* ÍNDICE: 1-Início 2-Conexão/Session 3-Lógica Principal 4-Formulários/Ações 5-Rodapé */
// ÍNDICE DE SEÇÕES (comentario.php)
// 1 - Sessão e inclusão de conexão
// 2 - Validação do método POST e inserção no banco de dados (tabela 'comentarios')
// 3 - Redirecionamento de volta para a página principal (âncora #batepapo)

session_start();
include 'conexao.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["mensagem"])) {
    if (isset($_SESSION['usuario'])) {
        $nome = htmlspecialchars(trim($_SESSION['usuario']));
    } else if (!empty($_POST["nome"])) {
        $nome = htmlspecialchars(trim($_POST["nome"]));
    } else {
        $nome = "Anônimo";
    }

    $mensagem = trim($_POST["mensagem"]);
    // Limita o tamanho da mensagem para evitar problemas (ajuste se desejar)
    if (mb_strlen($mensagem) > 500) {
        $mensagem = mb_substr($mensagem, 0, 500);
    }

    // Insere no banco de dados usando prepared statements
    $stmt = $conn->prepare("INSERT INTO comentarios (nome, mensagem) VALUES (?, ?)"); 
    if ($stmt) {
        $stmt->bind_param("ss", $nome, $mensagem);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php#batepapo");
exit;
?>
