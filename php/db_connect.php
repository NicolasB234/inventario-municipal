<?php
// db_connect.php
$servername = "localhost"; // O la IP de tu base de datos
$username = "root";        // Tu usuario de la base de datos
$password = "";            // Tu contraseña de la base de datos
$dbname = "inventario_municipal"; // El nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

// Opcional: Establecer el conjunto de caracteres a UTF-8
$conn->set_charset("utf8mb4");
?>