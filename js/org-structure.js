export const orgStructure = [
    {
        id: 'intendencia',
        name: 'Intendencia Municipal',
        type: 'intendencia',
        children: [
            {
                id: 'viceintendencia',
                name: 'Viceintendencia Municipal',
                type: 'viceintendencia',
                children: [
                    {
                        id: 'area-salud',
                        name: 'Área de Salud',
                        type: 'area',
                        children: [
                            {
                                id: 'dir-atencion-primaria',
                                name: 'Despacho Médico Principal',
                                type: 'direction',
                                children: [
                                    { id: 'dpto-centros-salud', name: 'Atención Primaria', type: 'departamento', children: [] },
                                    { id: 'dpto-prog-preventivos', name: 'Promoción y Prevención', type: 'departamento', children: [] }
                                ]
                            }
                        ]
                    }
                ]
            },
            {
                id: 'sec-gob',
                name: 'Secretaría de Gobierno',
                type: 'secretariat',
                children: [
                    {
                        id: 'dir-legales',
                        name: 'Dirección General de Asuntos Legales',
                        type: 'direction',
                        children: [
                            { id: 'dpto-dictamenes', name: 'Departamento de Dictámenes', type: 'departamento', children: [] }
                        ]
                    },
                    {
                        id: 'dir-transito',
                        name: 'Dirección de Tránsito',
                        type: 'direction',
                        children: [
                            { id: 'dpto-licencias', name: 'Departamento de Licencias', type: 'departamento', children: [] },
                            { id: 'dpto-inspectores', name: 'Departamento de Inspectores', type: 'departamento', children: [] }
                        ]
                    },
                    {
                        id: 'dir-higiene',
                        name: 'Dirección de Higiene Urbana',
                        type: 'direction',
                        children: [
                            { id: 'dpto-recoleccion', name: 'Departamento de Recolección', type: 'departamento', children: [] },
                            { id: 'dpto-barrido', name: 'Departamento de Barrido', type: 'departamento', children: [] }
                        ]
                    },
                    {
                        id: 'dir-desarrollo-social',
                        name: 'Dirección de Desarrollo Social',
                        type: 'direction',
                        children: [
                            { id: 'dpto-programas', name: 'Departamento de Programas Sociales', type: 'departamento', children: [] },
                            { id: 'dpto-comedores', name: 'Departamento de Comedores Comunitarios', type: 'departamento', children: [] }
                        ]
                    }
                ]
            },
            {
                id: 'sec-hacienda',
                name: 'Secretaría de Hacienda y Finanzas',
                type: 'secretariat',
                children: [
                    {
                        id: 'dir-contaduria',
                        name: 'Dirección de Contaduría',
                        type: 'direction',
                        children: [
                            { id: 'dpto-presupuesto', name: 'Departamento de Presupuesto', type: 'departamento', children: [] },
                            { id: 'dpto-tesoreria', name: 'Departamento de Tesorería', type: 'departamento', children: [] }
                        ]
                    },
                    {
                        id: 'dir-recaudacion',
                        name: 'Dirección de Recaudación',
                        type: 'direction',
                        children: [
                            { id: 'dpto-tasas', name: 'Departamento de Tasas y Derechos', type: 'departamento', children: [] },
                            { id: 'dpto-patentes', name: 'Departamento de Patentes y Rodados', type: 'departamento', children: [] }
                        ]
                    }
                ]
            },
            {
                id: 'sec-obras-publicas',
                name: 'Secretaría de Obras y Servicios Públicos',
                type: 'secretariat',
                children: [
                    {
                        id: 'dir-obras',
                        name: 'Dirección de Obras Públicas',
                        type: 'direction',
                        children: [
                            { id: 'dpto-proyectos', name: 'Departamento de Proyectos', type: 'departamento', children: [] },
                            { id: 'dpto-infraestructura', name: 'Departamento de Infraestructura', type: 'departamento', children: [] }
                        ]
                    },
                    {
                        id: 'dir-servicios',
                        name: 'Dirección de Servicios Públicos',
                        type: 'direction',
                        children: [
                            { id: 'dpto-alumbrado', name: 'Departamento de Alumbrado', type: 'departamento', children: [] },
                            { id: 'dpto-espacios-verdes', name: 'Departamento de Espacios Verdes', type: 'departamento', children: [] }
                        ]
                    },
                    {
                        id: 'dir-maestranza',
                        name: 'Dirección de Maestranza',
                        type: 'direction',
                        children: [
                            { id: 'dpto-mantenimiento', name: 'Departamento de Mantenimiento', type: 'departamento', children: [] },
                            { id: 'dpto-almacenes', name: 'Departamento de Almacenes', type: 'departamento', children: [] }
                        ]
                    },
                    { id: 'dpto-prevencion', name: 'Departamento de Prevención y Seguridad', type: 'departamento', children: [] }
                ]
            },
            {
                id: 'sec-cultura',
                name: 'Secretaría de Cultura y Educación',
                type: 'secretariat',
                children: [
                    {
                        id: 'dir-cultura',
                        name: 'Dirección de Cultura',
                        type: 'direction',
                        children: [
                            { id: 'dpto-eventos', name: 'Departamento de Eventos', type: 'departamento', children: [] },
                            { id: 'dpto-patrimonio', name: 'Departamento de Patrimonio', type: 'departamento', children: [] }
                        ]
                    },
                    {
                        id: 'dir-educacion',
                        name: 'Dirección de Educación',
                        type: 'direction',
                        children: [
                            { id: 'dpto-programas-educativos', name: 'Departamento de Programas Educativos', type: 'departamento', children: [] },
                            { id: 'dpto-capacitacion', name: 'Departamento de Capacitación', type: 'departamento', children: [] }
                        ]
                    }
                ]
            },
            {
                id: 'asesoria-legal',
                name: 'Asesoría Legal',
                type: 'advisory',
                children: []
            },
            {
                id: 'auditoria-interna',
                name: 'Auditoría Interna',
                type: 'auditory',
                children: []
            },
            {
                id: 'dir-administracion',
                name: 'Dirección de Administración',
                type: 'direction',
                children: []
            },
            {
                id: 'dir-personal',
                name: 'Dirección de Personal',
                type: 'direction',
                children: []
            },
            {
                id: 'dir-secretaria-privada',
                name: 'Dirección de Secretaría Privada',
                type: 'direction',
                children: []
            },
            {
                id: 'dir-prensa-y-coordinacion',
                name: 'Dirección de Prensa y Coordinación',
                type: 'direction',
                children: []
            },
            {
                id: 'dir-accion-social',
                name: 'Dirección de Acción Social',
                type: 'direction',
                children: [
                    {
                        id: 'subsubsec-accion-social',
                        name: 'Subsecretaría de Acción Social',
                        type: 'subsecretariat',
                        children: [
                            { id: 'coord-cic', name: 'Coordinación del CIC', type: 'coordinacion', children: [] },
                            { id: 'coord-refugio', name: 'Coordinación del Refugio', type: 'coordinacion', children: [] },
                            { id: 'coord-cim', name: 'Coordinación del CIM', type: 'coordinacion', children: [] }
                        ]
                    }
                ]
            }
        ]
    }
];