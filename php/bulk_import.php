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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

// Cargar la estructura organizativa desde el archivo PHP.
require_once 'org-structure-data.php';

// **FUNCIÓN CORREGIDA**
// Ahora construye la ruta completa de cada área (ej: "Secretaría > Dirección > Dpto")
// y la usa como clave en el mapa, que es como probablemente está en tu CSV.
function createAreaMap($structure) {
    $map = [];
    function traverse($nodes, $path, &$map) {
        foreach ($nodes as $node) {
            $currentPath = array_merge($path, [$node['name']]);
            // Usamos la ruta completa como clave y el ID del nodo como valor.
            $map[implode(' > ', $currentPath)] = $node['id'];
            if (!empty($node['children'])) {
                traverse($node['children'], $currentPath, $map);
            }
        }
    }
    traverse($structure, [], $map);
    return $map;
}

// Crear el mapa de áreas con las rutas completas.
$areaMap = createAreaMap($orgStructure);

// Obtener el JSON enviado desde el frontend.
$json_data = file_get_contents('php://input');
$items = json_decode($json_data, true);

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
        // El frontend ya envía las claves en minúsculas y sin acentos.
        $areaNameFromCsv = trim($item['area'] ?? '');
        $node_id = $areaMap[$areaNameFromCsv] ?? null;

        if (!$node_id) {
            $itemName = $item['nombre'] ?? 'desconocido';
            $errors[] = "Ítem '{$itemName}' omitido (Área '{$areaNameFromCsv}' no encontrada).";
            continue;
        }

        $name = $item['nombre'] ?? 'Sin Nombre';
        $quantity = (int)($item['cantidad'] ?? 1);
        $category = $item['categoria'] ?? 'Varios';
        $description = $item['descripcion'] ?? '';
        $acquisitionDate = !empty($item['fechaadquisicion']) ? $item['fechaadquisicion'] : null;
        
        $statusLabel = strtolower(trim($item['estado'] ?? 'Apto'));
        $statusMap = ['apto' => 'A', 'no apto' => 'N', 'recuperable' => 'R', 'de baja' => 'B'];
        $status = $statusMap[$statusLabel] ?? 'A';

        if (empty($name)) {
            $errors[] = "Un ítem fue omitido por no tener nombre.";
            continue;
        }
        
        $stmt->bind_param("ssissss", $node_id, $name, $quantity, $category, $description, $acquisitionDate, $status);
        $stmt->execute();
        $items_added_count++;
    }

    $stmt->close();
    $conn->commit();

    $response['success'] = true;
    $message = $items_added_count . ' ítem(s) importado(s) correctamente.';
    if (!empty($errors)) {
        // Adjuntamos los errores al mensaje para un mejor diagnóstico.
        $message .= " Errores: " . implode(' ', $errors);
    }
    $response['message'] = $message;

} catch (Exception $e) {
    $conn->rollback();
    $response['message'] = 'Error durante la importación masiva: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>