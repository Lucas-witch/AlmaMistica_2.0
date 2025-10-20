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
<?php
/* ÍNDICE: 
1-Início
2-Conexão/Session
3-Lógica Principal
4-Formulários/Ações
*/

// 1-Início
session_start();
date_default_timezone_set('America/Sao_Paulo');

// 2-Conexão/Session
include 'conexao.php'; // ajuste com seu arquivo de conexão
$usuario_id = $_SESSION['id'] ?? null;
$usuario_nome = $_SESSION['nome'] ?? 'Visitante';

// 3-Lógica Principal
// Função para formatar data
function formatarData($data) {
    return date("d-m-Y H:i", strtotime($data));
}

// Função para exibir mensagens
function exibirMensagens($conn, $usuario_id) {
    $sql = "SELECT c.*, u.nome, u.foto_perfil 
            FROM comentarios c 
            LEFT JOIN usuarios u ON c.usuario_id = u.id 
            ORDER BY c.data_envio ASC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $ehUsuario = $row['usuario_id'] == $usuario_id;
            $nome = htmlspecialchars($row['nome']);
            $mensagem = nl2br(htmlspecialchars($row['mensagem']));
            $data = formatarData($row['data_envio']);
            $foto = $row['foto_perfil'] ? $row['foto_perfil'] : 'imagens/perfil_padrao.png';
            
            echo '<div class="mensagem ' . ($ehUsuario ? 'usuario' : 'outro') . '">';
            echo '<img src="' . $foto . '" alt="Foto perfil">';
            echo '<div class="conteudo">';
            echo '<span class="nome">' . $nome . '</span>';
            echo '<p class="texto">' . $mensagem . '</p>';
            echo '<span class="data">' . $data . '</span>';
            echo '</div></div>';
        }
    }
}

// 4-Formulários/Ações
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mensagem'])) {
    $mensagem = trim($_POST['mensagem']);
    if (!empty($mensagem) && $usuario_id) {
        $stmt = $conn->prepare("INSERT INTO comentarios (usuario_id, mensagem, data_envio) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $usuario_id, $mensagem);
        $stmt->execute();
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Bate-papo Mágico</title>
<style>
body {
    font-family: Arial, sans-serif;
    background-color: #1b1b2f;
    color: #fff;
}
.chat-container {
    width: 90%;
    max-width: 700px;
    margin: 30px auto;
    background: #2e2e4d;
    padding: 15px;
    border-radius: 10px;
    overflow-y: auto;
    max-height: 600px;
}
.mensagem {
    display: flex;
    margin-bottom: 10px;
    opacity: 0;
    transform: translateY(-20px);
    animation: slideDown 0.5s forwards;
}
.mensagem.usuario {
    flex-direction: row-reverse;
}
.mensagem img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin: 0 10px;
}
.conteudo {
    max-width: 70%;
    padding: 10px;
    border-radius: 10px;
}
.mensagem.usuario .conteudo {
    background-color: #6c5ce7;
    text-align: right;
}
.mensagem.outro .conteudo {
    background-color: #00b894;
    text-align: left;
}
.nome {
    display: block;
    font-size: 0.85em;
    font-weight: bold;
    margin-bottom: 5px;
}
.texto {
    font-size: 1.1em;
    margin: 0;
}
.data {
    display: block;
    font-size: 0.7em;
    margin-top: 5px;
    color: #ccc;
}
@keyframes slideDown {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.form-mensagem {
    display: flex;
    margin-top: 15px;
}
.form-mensagem input[type="text"] {
    flex: 1;
    padding: 10px;
    border-radius: 5px 0 0 5px;
    border: none;
    font-size: 1em;
}
.form-mensagem button {
    padding: 10px 15px;
    border: none;
    background-color: #fd79a8;
    color: #fff;
    font-size: 1em;
    border-radius: 0 5px 5px 0;
    cursor: pointer;
}
.form-mensagem button:hover {
    background-color: #e84393;
}
</style>
</head>
<body>

<div class="chat-container">
    <?php exibirMensagens($conn, $usuario_id); ?>
</div>

<form class="form-mensagem" method="POST">
    <input type="text" name="mensagem" placeholder="Digite sua mensagem..." required>
    <button type="submit">Enviar</button>
</form>

</body>
</html>
