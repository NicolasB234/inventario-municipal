<?php
require_once 'db_connect.php';
require_once 'log_activity.php'; 
require_once 'org-structure-data.php';
require_once 'utils.php';

header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => '', 'data' => null];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit;
}

$upload_dir = '../uploads/';
$imagePath = null;

if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
    $file = $_FILES['itemImage'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (in_array($file['type'], $allowed_types) && $file['size'] < 5000000) {
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

$node_id = $_POST['node_id'] ?? '';
$name = $_POST['name'] ?? '';
$quantity = $_POST['quantity'] ?? 1;
$category = $_POST['category'] ?? 'Varios';
$description = $_POST['description'] ?? '';
$acquisitionDate = !empty($_POST['acquisitionDate']) ? $_POST['acquisitionDate'] : null;
$status = $_POST['status'] ?? 'A';
$is_admin = $_SESSION['is_admin'] ?? false;

try {
    $stmt = $conn->prepare(
        "INSERT INTO inventory_items (node_id, name, quantity, category, description, acquisitionDate, status, imagePath) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssisssss", $node_id, $name, $quantity, $category, $description, $acquisitionDate, $status, $imagePath);
    
    if ($stmt->execute()) {
        $newItemId = $stmt->insert_id; // Obtenemos el ID del ítem recién insertado
        $response['success'] = true;
        $response['message'] = "Ítem '{$name}' añadido con éxito.";
        $response['data'] = [ // Devolvemos los datos del nuevo ítem
            'id' => $newItemId,
            'node_id' => $node_id,
            'name' => $name,
            'quantity' => $quantity,
            'category' => $category,
            'description' => $description,
            'acquisitionDate' => $acquisitionDate,
            'status' => $status,
            'imagePath' => $imagePath
        ];
        
        $areaName = getAreaNameById($orgStructure, $node_id) ?? 'un área desconocida';

        if ($is_admin) {
            $admin_details = "Admin '{$_SESSION['username']}' agregó el ítem '{$name}' al área '{$areaName}'.";
            log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_added_admin', $admin_details);
            $stmt_find_users = $conn->prepare("SELECT id FROM users WHERE area_id = ?");
            $stmt_find_users->bind_param("s", $node_id);
            $stmt_find_users->execute();
            $users_result = $stmt_find_users->get_result();
            while ($user = $users_result->fetch_assoc()) {
                $user_notification_details = "El administrador '{$_SESSION['username']}' agregó el ítem '{$name}' a tu área.";
                log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_added_by_admin', $user_notification_details, $user['id']);
            }
            $stmt_find_users->close();
        } else {
            $admin_details = "El usuario '{$_SESSION['username']}' agregó el ítem '{$name}' al área '{$areaName}'.";
            log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_added_by_user', $admin_details);
            $user_details = "Agregaste el ítem '{$name}' al área '{$areaName}'.";
            log_activity($conn, $_SESSION['user_id'], $_SESSION['username'], 'item_added_user_self', $user_details, $_SESSION['user_id']);
        }
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