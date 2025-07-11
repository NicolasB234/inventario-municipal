<?php
require_once 'db_connect.php';
header('Content-Type: application/json');
session_start();

$response = ['success' => false, 'message' => ''];

// Este manejador de errores se asegura de que cualquier problema inesperado
// devuelva un error JSON en lugar de romper la conexión.
set_exception_handler(function($e) use (&$response, &$conn) {
    if ($conn && $conn->ping()) {
        $conn->rollback();
    }
    $response['message'] = 'Error fatal en el servidor: ' . $e->getMessage();
    http_response_code(500);
    echo json_encode($response);
    exit;
});

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Acceso no autorizado.';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

require_once 'org-structure-data.php';

function createAreaMap($structure) {
    $map = [];
    function traverse($nodes, $path, &$map) {
        foreach ($nodes as $node) {
            $currentPath = array_merge($path, [$node['name']]);
            $map[implode(' > ', $currentPath)] = $node['id'];
            if (!empty($node['children'])) {
                traverse($node['children'], $currentPath, $map);
            }
        }
    }
    traverse($structure, [], $map);
    return $map;
}
$areaMap = createAreaMap($orgStructure);

$json_data = file_get_contents('php://input');
if ($json_data === false) {
    $response['message'] = 'Error al leer los datos de entrada.';
    echo json_encode($response);
    exit;
}

$items = json_decode($json_data, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Error en el formato de datos (JSON): ' . json_last_error_msg();
    echo json_encode($response);
    exit;
}

if (empty($items) || !is_array($items)) {
    $response['message'] = 'No se recibieron ítems o el formato es incorrecto.';
    echo json_encode($response);
    exit;
}

$conn->begin_transaction();

try {
    $query = "INSERT INTO inventory_items (node_id, name, quantity, category, description, acquisitionDate, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);

    $items_added_count = 0;
    $errors = [];

    foreach ($items as $item) {
        $areaName = trim($item['area'] ?? '');
        $node_id = $areaMap[$areaName] ?? null;

        if (!$node_id) {
            $itemName = $item['nombre'] ?? 'desconocido';
            $errors[] = "Ítem '{$itemName}' omitido (Área '{$areaName}' no encontrada)";
            continue;
        }

        $name = $item['nombre'] ?? 'Sin Nombre';
        $quantity = (int)($item['cantidad'] ?? 1);
        $category = $item['categoria'] ?? 'Varios';
        $description = $item['descripcion'] ?? '';
        
        $date_str = $item['fechaadquisicion'] ?? null;
        // Se asegura de que no se inserte 'null' como texto en la base de datos
        $acquisitionDate = (!empty($date_str) && $date_str !== 'null') ? $date_str : null;
        
        $statusLabel = strtolower(trim($item['estado'] ?? 'Apto'));
        $statusMap = ['apto' => 'A', 'no apto' => 'N', 'recuperable' => 'R', 'de baja' => 'B'];
        $status = $statusMap[$statusLabel] ?? 'A';
        
        $stmt->bind_param("ssissss", $node_id, $name, $quantity, $category, $description, $acquisitionDate, $status);
        if(!$stmt->execute()){
             $errors[] = "Error en Base de Datos para '{$name}': " . $stmt->error;
        } else {
            $items_added_count++;
        }
    }

    $stmt->close();
    
    // Si hubo algún error, se cancela toda la importación para evitar datos parciales.
    if(count($errors) > 0){
        $conn->rollback();
        $response['success'] = false;
        $response['message'] = "La importación falló. Errores: " . implode('; ', $errors);
    } else {
        $conn->commit();
        $response['success'] = true;
        $response['message'] = $items_added_count . ' ítem(s) importado(s) correctamente.';
    }

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error durante la transacción: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>