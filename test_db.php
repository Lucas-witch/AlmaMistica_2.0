<?php
require_once __DIR__ . '/conexao.php'; // ajuste se necessário

// Verifica se $conn existe e é mysqli
if (!isset($conn) || !($conn instanceof mysqli)) {
    echo "ERRO: variavel \$conn não existe ou não é mysqli.<br>";
    // tenta mostrar info do mysqli_connect:
    if (function_exists('mysqli_connect_error')) {
        echo "mysqli_connect_error: " . mysqli_connect_error() . "<br>";
    }
    exit;
}

// Tenta definir modo de relatório (útil em dev)
mysqli_report(MYSQLI_REPORT_OFF);

echo "Conexão OK.<br>Host: " . $conn->host_info . "<br>Banco: " . $conn->query("select database()")->fetch_row()[0] . "<br>";

// Verifica se tabela usuarios existe
$res = $conn->query("SHOW TABLES LIKE 'usuarios'");
if ($res && $res->num_rows > 0) {
    echo "Tabela 'usuarios' encontrada.<br>";
    // Mostrar colunas essenciais
    $cols = $conn->query("SHOW COLUMNS FROM usuarios");
    echo "<pre>Colunas:\n";
    while ($c = $cols->fetch_assoc()) {
        echo $c['Field'] . "  (" . $c['Type'] . ")\n";
    }
    echo "</pre>";
} else {
    echo "Tabela 'usuarios' NÃO encontrada no banco atual.<br>";
}
?>
