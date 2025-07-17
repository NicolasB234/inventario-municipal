<?php
// php/utils.php

/**
 * Busca de forma recursiva el nombre de un nodo (área) a partir de su ID.
 *
 * @param array $nodes La estructura del organigrama donde buscar.
 * @param string $id El ID del nodo a encontrar.
 * @return string|null El nombre del nodo o null si no se encuentra.
 */
function getAreaNameById($nodes, $id) {
    foreach ($nodes as $node) {
        if ($node['id'] === $id) {
            return $node['name'];
        }
        if (!empty($node['children'])) {
            $found = getAreaNameById($node['children'], $id);
            if ($found) {
                return $found;
            }
        }
    }
    return null;
}

/**
 * --- INICIO DE LA CORRECCIÓN ---
 * Crea un mapa asociativo de 'Nombre Completo del Área' => 'ID del Área'.
 * Esencial para la importación masiva desde archivos XLSX.
 *
 * @param array $structure La estructura completa del organigrama.
 * @return array El mapa generado.
 */
function createAreaMap($structure) {
    $map = [];
    $traverse = function ($nodes, $path, &$map) use (&$traverse) {
        foreach ($nodes as $node) {
            $currentPath = array_merge($path, [$node['name']]);
            $map[implode(' > ', $currentPath)] = $node['id'];
            if (!empty($node['children'])) {
                $traverse($node['children'], $currentPath, $map);
            }
        }
    };
    $traverse($structure, [], $map);
    return $map;
}
// --- FIN DE LA CORRECCIÓN ---
?>