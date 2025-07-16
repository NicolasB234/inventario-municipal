<?php
require_once 'db_connect.php';
// Incluimos el archivo que contiene la nueva función de log.
require_once 'log_activity.php'; 
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];
$is_admin = $_SESSION['is_admin'] ?? false;

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit;
}

$id = $_POST['id'] ?? 0;
if (!$id) {
    $response['message'] = 'ID de ítem no válido.';
    echo json_encode($response);
    exit;
}

// --- LÓGICA DE MANEJO DE IMAGEN ---
$upload_dir = '../uploads/';
$imagePath = $_POST['existingImagePath'] ?? null; 

// Obtener la ruta de la imagen actual desde la BD para poder borrarla si se reemplaza
$stmt = $conn->prepare("SELECT imagePath FROM inventory_items WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$currentItem = $result->fetch_assoc();
$oldImagePath = $currentItem['imagePath'] ?? null;
$stmt->close();

// Comprobar si se ha subido un archivo nuevo
if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
    $file = $_FILES['itemImage'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (in_array($file['type'], $allowed_types) && $file['size'] < 5000000) { // 5MB limit
        $filename = uniqid() . '_' . basename($file['name']);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $imagePath = 'uploads/' . $filename;
            // Borramos la imagen antigua si la nueva se subió correctamente
            if ($oldImagePath && file_exists('../' . $oldImagePath)) {
                unlink('../' . $oldImagePath);
            }
        } else {
            $response['message'] = 'Error al mover el nuevo archivo de imagen.';
            echo json_encode($response);
            exit;
        }
    } else {
        $response['message'] = 'El nuevo archivo de imagen no es válido o es demasiado grande.';
        echo json_encode($response);
        exit;
    }
}

// Recopilar todos los datos del formulario
$action_data = [
    'id' => $id,
    'name' => $_POST['name'],
    'quantity' => $_POST['quantity'],
    'category' => $_POST['category'],
    'description' => $_POST['description'],
    'acquisitionDate' => $_POST['acquisitionDate'],
    'status' => $_POST['status'],
    'imagePath' => $imagePath // Usamos la ruta de la imagen (nueva o la antigua)
];


if ($is_admin) {
    // El admin actualiza directamente en la base de datos
    try {
        $stmt_update = $conn->prepare(
            "UPDATE inventory_items SET name = ?, quantity = ?, category = ?, description = ?, acquisitionDate = ?, status = ?, imagePath = ? WHERE id = ?"
        );
        $stmt_update->bind_param(
            "sisssssi",
            $action_data['name'], $action_data['quantity'], $action_data['category'],
            $action_data['description'], $action_data['acquisitionDate'], $action_data['status'],
            $action_data['imagePath'], $id
        );

        if ($stmt_update->execute()) {
            $response['success'] = true;
            $response['message'] = 'Ítem actualizado correctamente.';
            
            // Registramos la acción del admin en el historial.
            $details = "Admin '{$_SESSION['username']}' actualizó directamente el ítem '{$action_data['name']}'.";
            log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_edited_admin', $details);
        } else {
            $response['message'] = 'Error al actualizar el ítem en la base de datos.';
        }
        $stmt_update->close();
    } catch (Exception $e) {
        $response['message'] = 'Error de servidor: ' . $e->getMessage();
    }
} else {
    // El usuario normal crea una solicitud de edición
    $json_data = json_encode($action_data);
    $stmt_request = $conn->prepare(
        "INSERT INTO pending_actions (user_id, username, action_type, item_id, action_data) VALUES (?, ?, 'edit', ?, ?)"
    );
    $stmt_request->bind_param("isis", $_SESSION['user_id'], $_SESSION['username'], $id, $json_data);

    if ($stmt_request->execute()) {
        $response['success'] = true;
        $response['message'] = 'Solicitud de edición enviada para aprobación.';

        // Registramos la solicitud del usuario en el historial.
        $details = "Usuario '{$_SESSION['username']}' solicitó editar el ítem '{$action_data['name']}'.";
        log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'request_edit', $details);
    } else {
        $response['message'] = 'Error al enviar la solicitud de edición.';
    }
    $stmt_request->close();
}

$conn->close();
echo json_encode($response);
?>