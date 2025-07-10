<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

// Verificar si el usuario está logueado
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit;
}

// Directorio de subida
$upload_dir = '../uploads/';
$imagePath = null;

// Manejo de la subida de imagen
if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
    $file = $_FILES['itemImage'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (in_array($file['type'], $allowed_types) && $file['size'] < 5000000) { // Límite de 5MB
        // Crear un nombre de archivo único
        $filename = uniqid() . '_' . basename($file['name']);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $imagePath = 'uploads/' . $filename;
        } else {
            $response['message'] = 'Error al mover el archivo subido.';
            echo json_encode($response);
            exit;
        }
    } else {
        $response['message'] = 'Archivo no válido o demasiado grande.';
        echo json_encode($response);
        exit;
    }
}

// Recoger datos del POST
$node_id = $_POST['node_id'] ?? '';
$name = $_POST['name'] ?? '';
$quantity = $_POST['quantity'] ?? 1;
$category = $_POST['category'] ?? 'Varios';
$description = $_POST['description'] ?? '';
$acquisitionDate = $_POST['acquisitionDate'] ?? null;
$status = $_POST['status'] ?? 'A';

try {
    $stmt = $conn->prepare(
        "INSERT INTO inventory_items (node_id, name, quantity, category, description, acquisitionDate, status, imagePath) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssisssss", $node_id, $name, $quantity, $category, $description, $acquisitionDate, $status, $imagePath);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Ítem añadido correctamente.';
        $response['newItemId'] = $conn->insert_id; // Devolver el ID del nuevo ítem
    } else {
        $response['message'] = 'Error al añadir el ítem en la base de datos.';
    }
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error de servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>