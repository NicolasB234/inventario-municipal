<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit;
}

$id = $_POST['id'] ?? 0;

if ($id > 0) {
    // Primero, obtener la ruta de la imagen para borrar el archivo
    $stmt = $conn->prepare("SELECT imagePath FROM inventory_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['imagePath']) && file_exists('../' . $row['imagePath'])) {
            unlink('../' . $row['imagePath']); // Borrar el archivo de imagen
        }
    }
    $stmt->close();

    // Ahora, borrar el registro de la base de datos
    $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Ítem eliminado.';
    } else {
        $response['message'] = 'Error al eliminar.';
    }
    $stmt->close();

} else {
    $response['message'] = 'ID de ítem no válido.';
}

$conn->close();
echo json_encode($response);
?>