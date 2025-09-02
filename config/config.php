<?php
$host = "localhost";
$dbname = "jztecc30_envios";
$user = "jztecc30_envios";
$pass = "Imperio72##";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass); // trocado $pdo por $conn
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>


