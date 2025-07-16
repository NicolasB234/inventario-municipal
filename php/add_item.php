<?php
require_once 'db_connect.php';
require_once 'log_activity.php'; 
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

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
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (in_array($file['type'], $allowed_types) && $file['size'] < 5000000) { // Límite de 5MB
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
        // **INICIO DE LA CORRECCIÓN**
        // Mensaje genérico. La interfaz creará el mensaje detallado.
        $response['message'] = "Ítem '{$name}' añadido con éxito."; 
        // **FIN DE LA CORRECCIÓN**
        
        // Registramos la acción en el historial del servidor.
        $details = "El usuario '{$_SESSION['username']}' añadió el nuevo ítem '{$name}'.";
        log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_added', $details);
        
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