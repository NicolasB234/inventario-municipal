<?php
// php/org-structure-data.php

$orgStructure = [
    [
        'id' => 'intendente',
        'name' => 'Intendencia',
        'type' => 'intendencia',
        'children' => [
            [
                'id' => 'viceintendente',
                'name' => 'Viceintendencia',
                'type' => 'viceintendencia',
                'children' => [
                    [
                        'id' => 'sec-hacienda-obras-publicas-vice',
                        'name' => 'Secretaría de Hacienda y Obras Públicas',
                        'type' => 'secretaria',
                        'children' => [
                            ['id' => 'sub-servicios-publicos', 'name' => 'Subdirección de Servicios Públicos', 'type' => 'subdireccion', 'children' => []],
                            ['id' => 'sub-barrido-limpieza', 'name' => 'Subdirección de Barrido y Limpieza', 'type' => 'subdireccion', 'children' => []],
                            ['id' => 'coord-recibos', 'name' => 'Coordinación de Recibos', 'type' => 'coordinacion', 'children' => []],
                        ]
                    ],
                    [
                        'id' => 'coord-ejecucion-fiscal',
                        'name' => 'Coordinación de Ejecución Fiscal',
                        'type' => 'coordinacion',
                        'children' => [
                            ['id' => 'sec-legales', 'name' => 'Secretaría de Legales', 'type' => 'secretaria', 'children' => []],
                        ]
                    ],
                    [
                        'id' => 'area-salud',
                        'name' => 'Área de Salud',
                        'type' => 'area',
                        'children' => [
                            ['id' => 'dir-personal-salud', 'name' => 'Dirección de Personal', 'type' => 'direccion', 'children' => []],
                            ['id' => 'dir-protocolo-salud', 'name' => 'Dirección de Ceremonial y Protocolo', 'type' => 'direccion', 'children' => []],
                            ['id' => 'dir-club-dia', 'name' => 'Dirección Club de Día', 'type' => 'direccion', 'children' => []],
                            ['id' => 'sub-promocion-salud', 'name' => 'Subdirección de Promoción y Prevención de la Salud', 'type' => 'subdireccion', 'children' => []],
                            ['id' => 'coord-salud', 'name' => 'Coordinación de Salud', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'dir-accion-social-salud', 'name' => 'Dirección de Acción Social', 'type' => 'direccion', 'children' => []],
                            ['id' => 'sub-accion-social-salud', 'name' => 'Subdirección de Acción Social', 'type' => 'subdireccion', 'children' => []],
                            ['id' => 'dir-prensa-salud', 'name' => 'Dirección de Prensa y Coordinación', 'type' => 'direccion', 'children' => []],
                            ['id' => 'dir-asesoramiento-letrado', 'name' => 'Dirección de Asesoramiento Letrado', 'type' => 'direccion', 'children' => []],
                        ]
                    ],
                    [
                        'id' => 'subsec-accion-social-vice',
                        'name' => 'Subsecretaría de Acción Social',
                        'type' => 'subsecretaria',
                        'children' => [
                            ['id' => 'sub-accion-social-fermin', 'name' => 'Subdirección de Acción Social', 'type' => 'subdireccion', 'children' => []],
                            ['id' => 'coord-cim', 'name' => 'Coordinación Área CIM', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-cic', 'name' => 'Coordinación del CIC', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-refugio', 'name' => 'Coordinación del Refugio', 'type' => 'coordinacion', 'children' => []],
                        ]
                    ],
                ]
            ],
            [
                'id' => 'sec-gobierno',
                'name' => 'Secretaría de Gobierno y Coordinación',
                'type' => 'secretaria',
                'children' => [
                    ['id' => 'celda-central-ac', 'name' => 'Celda Central A/C', 'type' => 'celda', 'children' => []],
                    ['id' => 'dir-bromatologia', 'name' => 'Dirección de Bromatología', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-empleo-produccion', 'name' => 'Dirección de Empleo y Producción', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-administrativa', 'name' => 'Dirección Administrativa', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-gestion-provincial', 'name' => 'Dirección de Gestión Provincial', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-cultura-turismo', 'name' => 'Dirección de Cultura y Turismo', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-transito', 'name' => 'Dirección de Tránsito y Seguridad Vial', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-deportes', 'name' => 'Dirección de Deportes y Recreación', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-minoridad-familia', 'name' => 'Dirección de Minoridad y Familia', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-familia', 'name' => 'Dirección de Familia', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-recursos-naturales', 'name' => 'Dirección de Recursos Naturales', 'type' => 'direccion', 'children' => []],
                    ['id' => 'coord-genero', 'name' => 'Coordinación de Área de Género', 'type' => 'coordinacion', 'children' => []],
                    ['id' => 'coord-discapacidad', 'name' => 'Coordinación de Área de Discapacidad', 'type' => 'coordinacion', 'children' => []],
                    ['id' => 'coord-licencias-conducir', 'name' => 'Coordinación de Licencias de Conducir', 'type' => 'coordinacion', 'children' => []],
                    ['id' => 'coord-inspectores', 'name' => 'Coordinación de Inspectores de Conducir', 'type' => 'coordinacion', 'children' => []],
                ]
            ],
            [
                'id' => 'sec-privada',
                'name' => 'Secretaría Privada',
                'type' => 'secretaria',
                'children' => [
                    ['id' => 'sub-compras', 'name' => 'Subdirección Compras', 'type' => 'subdireccion', 'children' => []],
                    ['id' => 'coord-tesoreria', 'name' => 'Coordinación de Tesorería', 'type' => 'coordinacion', 'children' => []],
                    ['id' => 'coord-contaduria-privada', 'name' => 'Coordinación de Contaduría y Presupuesto Púbico', 'type' => 'coordinacion', 'children' => []],
                    ['id' => 'dir-contable-privada', 'name' => 'Dirección Contable y de Presupuesto Público', 'type' => 'direccion', 'children' => []],
                    ['id' => 'dir-despacho-entradas', 'name' => 'Dirección de Despacho y Mesa de Entradas', 'type' => 'direccion', 'children' => []],
                    ['id' => 'coord-gestion-administrativa', 'name' => 'Coordinación de Gestión Administrativa', 'type' => 'coordinacion', 'children' => []],
                    ['id' => 'sub-protocolo-privada', 'name' => 'Subdirección de Ceremonial y Protocolo', 'type' => 'subdireccion', 'children' => []],
                    ['id' => 'sub-informatica', 'name' => 'Subdirección de Informática', 'type' => 'subdireccion', 'children' => []],
                    ['id' => 'sub-sistemas', 'name' => 'Subdirección de Sistemas', 'type' => 'subdireccion', 'children' => []],
                    ['id' => 'sub-control-interno', 'name' => 'Subdirección de Control Interno', 'type' => 'subdireccion', 'children' => []],
                ]
            ],
            [
                'id' => 'sec-produccion',
                'name' => 'Secretaría de Producción',
                'type' => 'secretaria',
                'children' => [
                    ['id' => 'sub-valor-agregado', 'name' => 'Subdirección de Valor Agregado', 'type' => 'subdireccion', 'children' => []],
                ]
            ],
            [
                'id' => 'sec-obras-publicas-intendente',
                'name' => 'Secretaría de Obras Públicas',
                'type' => 'secretaria',
                'children' => [
                    ['id' => 'dir-taller-municipal', 'name' => 'Dirección de Taller Municipal', 'type' => 'direccion', 'children' => []],
                    ['id' => 'sub-saneamiento-urbano', 'name' => 'Subdirección de Saneamiento Urbano', 'type' => 'subdireccion', 'children' => []],
                    ['id' => 'dir-obras-privadas', 'name' => 'Dirección de Obras Privadas', 'type' => 'direccion', 'children' => []],
                    [
                        'id' => 'sub-parques-paseos',
                        'name' => 'Subdirección de Parque y Paseos',
                        'type' => 'subdireccion',
                        'children' => [
                            ['id' => 'coord-desmalezamiento', 'name' => 'Coordinación de Desmalezamiento', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-mantenimiento-verdes', 'name' => 'Coordinación de Mantenimiento de Espacios Verdes', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-patios-obras', 'name' => 'Coordinación de Centros de Patios Paiv y Obras Menores', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-infra-eventos', 'name' => 'Coordinación de Infraestructura de Eventos', 'type' => 'coordinacion', 'children' => []],
                        ]
                    ],
                    [
                        'id' => 'sub-parques-plazas',
                        'name' => 'Subdirección de Parque y Plazas',
                        'type' => 'subdireccion',
                        'children' => [
                            ['id' => 'coord-paseos', 'name' => 'Coordinación de Paseos', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-juegos-parques', 'name' => 'Coordinación de Juegos en Parques y Plazas', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-forestacion', 'name' => 'Coordinación de Forestación', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-riego-vivero', 'name' => 'Coordinación de Riego, Vivero e Invernáculo', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-fiscal-romero', 'name' => 'Coordinación Fiscal', 'type' => 'coordinacion', 'children' => []],
                            ['id' => 'coord-fiscal-parque-acuatico', 'name' => 'Coordinación Fiscal A/C Parque Acuático', 'type' => 'coordinacion', 'children' => []],
                        ]
                    ],
                ]
            ],
        ]
    ]
];
?>