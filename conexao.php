<?php
// conexao.php
$servername = "localhost";  // geralmente localhost
$username = "root";         // coloque seu usuário do MySQL
$password = "";             // coloque sua senha, se tiver
$dbname = "alma_db";   // nome do banco de dados (ajuste se diferente)

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>
