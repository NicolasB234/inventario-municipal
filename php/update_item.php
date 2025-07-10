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
if (!$id) {
    $response['message'] = 'ID de ítem no válido.';
    echo json_encode($response);
    exit;
}

// --- LÓGICA DE MANEJO DE IMAGEN ---
$upload_dir = '../uploads/';
$imagePath = $_POST['existingImagePath'] ?? null; // Asumimos que mantenemos la imagen existente

// 1. Obtener la ruta de la imagen actual desde la BD para poder borrarla si se reemplaza
$stmt = $conn->prepare("SELECT imagePath FROM inventory_items WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$currentItem = $result->fetch_assoc();
$oldImagePath = $currentItem['imagePath'] ?? null;
$stmt->close();


// 2. Comprobar si se ha subido un archivo nuevo
if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
    $file = $_FILES['itemImage'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (in_array($file['type'], $allowed_types) && $file['size'] < 5000000) { // 5MB limit
        // Crear un nombre de archivo único
        $filename = uniqid() . '_' . basename($file['name']);
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            // Si la subida es exitosa, esta es la nueva ruta
            $imagePath = 'uploads/' . $filename;

            // Y si había una imagen antigua, la borramos del servidor
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
// Si no se subió un archivo nuevo, $imagePath conservará el valor de la imagen anterior.

// --- LÓGICA DE ACTUALIZACIÓN EN LA BASE DE DATOS ---
try {
    $stmt = $conn->prepare(
        "UPDATE inventory_items SET 
            name = ?, 
            quantity = ?, 
            category = ?, 
            description = ?, 
            acquisitionDate = ?, 
            status = ?, 
            imagePath = ? 
        WHERE id = ?"
    );
    
    $stmt->bind_param(
        "sisssssi",
        $_POST['name'],
        $_POST['quantity'],
        $_POST['category'],
        $_POST['description'],
        $_POST['acquisitionDate'],
        $_POST['status'],
        $imagePath, // Usamos la ruta de la imagen (nueva o la antigua si no se cambió)
        $id
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Ítem actualizado correctamente.';
    } else {
        $response['message'] = 'Error al actualizar el ítem en la base de datos.';
    }
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error de servidor: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>