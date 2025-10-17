<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST["nome"])) {
    $nome = htmlspecialchars(trim($_POST["nome"]));
    $data = date("Y-m-d H:i:s");

    // Conexão com o banco
    $conn = new mysqli("localhost", "root", "", "alma_db");
    if ($conn->connect_error) {
        // Apenas retorna sem mostrar erro
        return;
    }

    // Insere visita
    $stmt = $conn->prepare("INSERT INTO visitas (nome, data) VALUES (?, ?)");
    $stmt->bind_param("ss", $nome, $data);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    // Redireciona para index.php
    header("Location: index.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>