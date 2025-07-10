import { addNotification } from './script.js';
import { orgStructure } from './org-structure.js';

const API_URL = 'php/';

const categories = [
    'Informática', 'Electrónica', 'Mobiliario', 'Vehículos', 'Equipamiento', 
    'Herramientas', 'Materiales', 'Oficina', 'Otros electrónicos', 'Servicios', 
    'Mobiliario Depósito', 'Equipamiento Logístico', 'Equipamiento Oficina'
];

const statusOptions = [
    { value: 'A', label: 'Apto' },
    { value: 'N', label: 'No Apto' },
    { value: 'R', label: 'Recuperable' },
    { value: 'B', label: 'De Baja' }
];

// **FUNCIÓN CORREGIDA**
// Ahora mapea el ID del nodo directamente a su nombre específico.
function getAllNodesMap() {
    const nodesMap = new Map();
    function traverse(nodes) {
        nodes.forEach(node => {
            nodesMap.set(node.id, node.name); // Guarda solo el nombre del área, no la ruta completa.
            if (node.children && node.children.length > 0) {
                traverse(node.children);
            }
        });
    }
    traverse(orgStructure);
    return nodesMap;
}
const nodesMap = getAllNodesMap();


function exportToCSV(node, items) {
    if (items.length === 0) {
        alert('No hay datos en esta área para exportar.');
        return;
    }
    const headers = ['ID', 'Nombre', 'Cantidad', 'Categoría', 'Descripción', 'Fecha Adquisición', 'Estado', 'Area'];
    const csvRows = [];
    csvRows.push(headers.join(','));
    for (const item of items) {
        const values = [
            item.id,
            `"${item.name.replace(/"/g, '""')}"`,
            item.quantity,
            item.category,
            `"${(item.description || '').replace(/"/g, '""')}"`,
            item.acquisitionDate,
            statusOptions.find(s => s.value === item.status)?.label || item.status,
            nodesMap.get(item.node_id) || 'N/A'
        ];
        csvRows.push(values.join(','));
    }
    const csvString = csvRows.join('\n');
    const blob = new Blob([`\uFEFF${csvString}`], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.setAttribute('download', `inventario_${node.id || 'global'}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function parseCSV(csvString) {
    const rows = csvString.split('\n').filter(row => row.trim() !== '');
    const data = [];
    if (rows.length < 2) return data;

    const headers = rows.shift().toLowerCase().split(',').map(h => h.trim().replace(/"/g, '').normalize("NFD").replace(/[\u0300-\u036f]/g, ""));
    
    rows.forEach(row => {
        const values = row.split(',');
        if (values.length !== headers.length) return;

        const item = {};
        headers.forEach((header, index) => {
            item[header] = values[index] ? values[index].trim().replace(/"/g, '') : '';
        });
        data.push(item);
    });
    return data;
}

async function showTransferForm(node, items) {
    if (items.length === 0) {
        alert('No hay ítems en esta área para traspasar.');
        return;
    }

    if (document.getElementById('transfer-modal')) return;

    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'transfer-modal';

    let allNodes = [];
    function traverse(nodes, path) {
        nodes.forEach(n => {
            if(n.id !== node.id && ['departamento', 'coordinacion', 'area', 'direction'].includes(n.type)) {
                allNodes.push({id: n.id, name: [...path, n.name].join(' > ')});
            }
            if (n.children) traverse(n.children, [...path, n.name]);
        });
    }
    traverse(orgStructure, []);

    modalOverlay.innerHTML = `
        <div class="modal-content" style="width: 500px; cursor: default;" onclick="(e) => e.stopPropagation()">
            <button class="close-modal" id="cancel-transfer-btn-x">&times;</button>
            <h3>Traspaso de Ítem</h3>
            <form id="transfer-form">
                <div class="form-row">
                    <label>Área Origen:</label>
                    <input type="text" value="${node.name}" disabled>
                </div>
                <div class="form-row">
                    <label for="item-to-transfer">Ítem a Traspasar:</label>
                    <select id="item-to-transfer" name="itemId" required>
                        <option value="" disabled selected>Seleccione un ítem...</option>
                        ${items.map(item => `<option value="${item.id}">${item.name} (ID: ${item.id})</option>`).join('')}
                    </select>
                </div>
                <div class="form-row">
                    <label for="destination-area">Área de Destino:</label>
                    <select id="destination-area" name="destinationNodeId" required>
                        <option value="" disabled selected>Seleccione un destino...</option>
                        ${allNodes.map(n => `<option value="${n.id}">${n.name}</option>`).join('')}
                    </select>
                </div>
                <div class="form-row">
                    <label for="transfer-reason">Motivo del Traspaso:</label>
                    <textarea id="transfer-reason" name="reason" rows="3" placeholder="Especifique el motivo del traspaso..." required></textarea>
                </div>
                <div class="form-row" style="flex-direction: row; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="cancel-transfer-btn" class="reset-filters-btn">Cancelar</button>
                    <button type="submit" class="apply-filters-btn">Confirmar Traspaso</button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(modalOverlay);

    const closeModal = () => modalOverlay.remove();
    document.getElementById('cancel-transfer-btn').onclick = closeModal;
    document.getElementById('cancel-transfer-btn-x').onclick = closeModal;
    modalOverlay.onclick = (e) => {
        if(e.target === modalOverlay) closeModal();
    };

    document.getElementById('transfer-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        const currentUsername = sessionStorage.getItem('username');
        const itemName = e.target.querySelector('#item-to-transfer option:checked').text;
        const destinationName = e.target.querySelector('#destination-area option:checked').text;
        const reason = formData.get('reason');

        if (!confirm(`¿Está seguro de traspasar el ítem "${itemName}" al área "${destinationName}"?`)) {
            return;
        }

        try {
            const response = await fetch(`${API_URL}transfer_item.php`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                const notifMessage = `Ítem "${itemName}" traspasado de "${node.name}" a "${destinationName}". Motivo: ${reason}`;
                addNotification('item_transferred', notifMessage, currentUsername);
                alert('Traspaso realizado con éxito.');
                closeModal();
                displayInventory(node);
            } else {
                alert(`Error en el traspaso: ${result.message}`);
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            alert('Error de conexión al realizar el traspaso.');
        }
    });
}

async function showItemForm(node, item = null) {
    const modal = document.getElementById('modal-agregar-item');
    const form = document.getElementById('form-agregar-item');
    const modalTitle = modal.querySelector('h2');
    const imagePreview = document.getElementById('imagePreview');
    const imageInput = document.getElementById('form-itemImage');
    const isEditing = item !== null;

    form.reset();
    modalTitle.textContent = isEditing ? `Editar Ítem: ${item.name}` : 'Agregar Nuevo Ítem';
    
    form.querySelector('[name="id"]').value = isEditing ? item.id : '';
    form.querySelector('[name="node_id"]').value = node.id;
    form.querySelector('[name="existingImagePath"]').value = isEditing && item.imagePath ? item.imagePath : '';
    form.querySelector('[name="name"]').value = isEditing ? item.name : '';
    form.querySelector('[name="quantity"]').value = isEditing ? item.quantity : 1;
    form.querySelector('[name="description"]').value = isEditing ? item.description : '';
    form.querySelector('[name="acquisitionDate"]').value = isEditing ? item.acquisitionDate : '';

    const categorySelect = form.querySelector('[name="category"]');
    categorySelect.innerHTML = categories.map(cat => `<option value="${cat}" ${isEditing && item.category === cat ? 'selected' : ''}>${cat}</option>`).join('');

    const statusSelect = form.querySelector('[name="status"]');
    statusSelect.innerHTML = statusOptions.map(option => `<option value="${option.value}" ${isEditing && item.status === option.value ? 'selected' : ''}>${option.label}</option>`).join('');

    if (isEditing && item.imagePath) {
        imagePreview.src = item.imagePath;
        imagePreview.style.display = 'block';
    } else {
        imagePreview.style.display = 'none';
        imagePreview.src = '#';
    }
    
    const imageChangeHandler = () => {
        if (imageInput.files && imageInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            }
            reader.readAsDataURL(imageInput.files[0]);
        }
    };
    imageInput.addEventListener('change', imageChangeHandler);
    
    modal.style.display = 'flex';

    const closeModal = () => {
        modal.style.display = 'none';
        imageInput.removeEventListener('change', imageChangeHandler);
    };
    modal.querySelector('.close-modal').onclick = closeModal;
    modal.querySelector('#cancel-item-btn').onclick = closeModal;
    modal.onclick = (e) => {
        if (e.target === modal) closeModal();
    };

    form.onsubmit = async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const currentUsername = sessionStorage.getItem('username');
        const endpoint = isEditing ? 'update_item.php' : 'add_item.php';

        try {
            const response = await fetch(API_URL + endpoint, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                const action = isEditing ? 'item_edited' : 'item_added';
                const message = isEditing ? `Ítem "${formData.get('name')}" editado en "${node.name}".` : `Nuevo ítem "${formData.get('name')}" añadido a "${node.name}".`;
                addNotification(action, message, currentUsername);
                
                closeModal();
                displayInventory(node, sessionStorage.getItem('isAdmin') === 'true');
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error al guardar:', error);
            alert('Error de conexión al guardar el ítem.');
        }
    };
}

export async function displayInventory(node, isAdmin = false) {
    const contentTitle = document.getElementById('content-title');
    const tableView = document.getElementById('table-view');
    
    contentTitle.textContent = `Inventario de: ${node.name}`;
    tableView.innerHTML = '<p>Cargando inventario...</p>';

    try {
        const response = await fetch(`${API_URL}get_inventory.php?node_id=${node.id}`);
        const result = await response.json();
        
        if (result.success) {
            setupInventoryUI(node, result.data, isAdmin);
        } else {
            tableView.innerHTML = `<p>Error al cargar el inventario: ${result.message}</p>`;
        }
    } catch (error) {
        console.error('Error al obtener el inventario:', error);
        tableView.innerHTML = '<p>Error de conexión al cargar el inventario.</p>';
    }
}

function setupInventoryUI(node, items, isAdmin) {
    const tableView = document.getElementById('table-view');
    tableView.innerHTML = '';

    const controls = document.createElement('div');
    controls.className = 'inventory-controls';
    controls.innerHTML = `
        <button id="add-item-btn"><i class="fas fa-plus"></i> Agregar Item</button>
        <button id="transfer-item-btn"><i class="fas fa-random"></i> Traspasar Item</button>
        <button id="import-csv-btn"><i class="fas fa-file-import"></i> Importar CSV</button>
        <input type="file" id="csv-file-input" accept=".csv" style="display: none;" />
        <button id="export-csv-btn"><i class="fas fa-file-export"></i> Exportar CSV</button>
        <button id="toggle-filters-btn"><i class="fas fa-filter"></i> Mostrar Filtros</button>
    `;
    tableView.appendChild(controls);

    document.getElementById("add-item-btn").addEventListener('click', () => showItemForm(node));
    document.getElementById("transfer-item-btn").addEventListener('click', () => showTransferForm(node, items));
    document.getElementById("export-csv-btn").addEventListener('click', () => exportToCSV(node, items));

    const importCsvBtn = document.getElementById('import-csv-btn');
    const csvFileInput = document.getElementById('csv-file-input');
    importCsvBtn.addEventListener('click', () => csvFileInput.click());
    csvFileInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = async (e) => {
            const csvString = e.target.result;
            const parsedItems = parseCSV(csvString);

            if (parsedItems.length === 0) {
                alert('El archivo CSV está vacío o tiene un formato incorrecto.');
                event.target.value = null;
                return;
            }

            const itemsToImport = parsedItems.map(item => ({ ...item, node_id: node.id }));

            try {
                const response = await fetch(`${API_URL}bulk_import.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(itemsToImport)
                });
                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    addNotification('items_imported', result.message, sessionStorage.getItem('username'));
                    displayInventory(node, isAdmin);
                } else {
                    alert(`Error en la importación: ${result.message}`);
                }
            } catch (error) {
                console.error('Error de conexión al importar:', error);
                alert('Error de conexión. No se pudo completar la importación.');
            }
        };
        reader.readAsText(file, 'UTF-8');
        event.target.value = null;
    });
    
    const filterControls = document.createElement('div');
    filterControls.className = 'filter-controls-container';
    filterControls.innerHTML = `
        <div class="filter-row">
            <label for="filter-id">Buscar por ID:</label>
            <input type="text" id="filter-id" placeholder="ID del ítem">
        </div>
        <div class="filter-row">
            <label for="filter-name">Buscar por Nombre:</label>
            <input type="text" id="filter-name" placeholder="Nombre del ítem">
        </div>
        <div class="filter-row">
            <label for="filter-category">Categoría:</label>
            <select id="filter-category">
                <option value="">Todas</option>
                ${categories.map(cat => `<option value="${cat}">${cat}</option>`).join('')}
            </select>
        </div>
        <div class="filter-row">
            <label for="filter-status">Estado:</label>
            <select id="filter-status">
                <option value="">Todos</option>
                ${statusOptions.map(option => `<option value="${option.value}">${option.label}</option>`).join('')}
            </select>
        </div>
        <div class="filter-row">
            <label for="filter-date-from">Adquirido Desde:</label>
            <input type="date" id="filter-date-from">
        </div>
        <div class="filter-row">
            <label for="filter-date-to">Adquirido Hasta:</label>
            <input type="date" id="filter-date-to">
        </div>
        <div class="filter-actions">
            <button id="apply-filters-btn" class="apply-filters-btn">
                <i class="fas fa-check"></i> Aplicar Filtros
            </button>
            <button id="reset-filters-btn" class="reset-filters-btn">
                <i class="fas fa-undo"></i> Limpiar Filtros
            </button>
        </div>
    `;
    tableView.appendChild(filterControls);

    document.getElementById('toggle-filters-btn').addEventListener('click', () => {
        filterControls.classList.toggle('visible');
        const isVisible = filterControls.classList.contains('visible');
        const button = document.getElementById('toggle-filters-btn');
        button.innerHTML = isVisible 
            ? '<i class="fas fa-eye-slash"></i> Ocultar Filtros' 
            : '<i class="fas fa-filter"></i> Mostrar Filtros';
    });

    document.getElementById('apply-filters-btn').addEventListener('click', () => {
        const filterId = document.getElementById('filter-id').value.trim();
        const filterName = document.getElementById('filter-name').value.toLowerCase().trim();
        const filterCategory = document.getElementById('filter-category').value;
        const filterStatus = document.getElementById('filter-status').value;
        const filterDateFrom = document.getElementById('filter-date-from').value;
        const filterDateTo = document.getElementById('filter-date-to').value;

        const filteredItems = items.filter(item => {
            const itemDate = item.acquisitionDate ? new Date(item.acquisitionDate) : null;
            const fromDate = filterDateFrom ? new Date(filterDateFrom) : null;
            const toDate = filterDateTo ? new Date(filterDateTo) : null;
            if(itemDate) itemDate.setUTCHours(0, 0, 0, 0);
            if(fromDate) fromDate.setUTCHours(0, 0, 0, 0);
            if(toDate) toDate.setUTCHours(0, 0, 0, 0);
            
            return (!filterId || String(item.id).includes(filterId)) &&
                   (!filterName || item.name.toLowerCase().includes(filterName)) &&
                   (!filterCategory || item.category === filterCategory) &&
                   (!filterStatus || item.status === filterStatus) &&
                   (!fromDate || (itemDate && itemDate >= fromDate)) &&
                   (!toDate || (itemDate && itemDate <= toDate));
        });
        renderTable(node, filteredItems, isAdmin);
    });

    document.getElementById('reset-filters-btn').addEventListener('click', () => {
        filterControls.querySelectorAll('input, select').forEach(el => el.value = '');
        renderTable(node, items, isAdmin);
    });

    const tableContainer = document.createElement('div');
    tableContainer.id = 'table-container';
    tableView.appendChild(tableContainer);

    renderTable(node, items, isAdmin);
}

function renderTable(node, items, isAdmin) {
    const tableContainer = document.getElementById('table-container');
    if (!tableContainer) return;

    const isAllAreasView = isAdmin && node.id === '';
    
    if (items.length === 0) {
        const isAnyFilterActive = document.querySelector('.filter-controls-container.visible');
        tableContainer.innerHTML = isAnyFilterActive ? '<p>No se encontraron ítems que coincidan con los filtros seleccionados.</p>' : '<p>Este departamento aún no tiene ítems en su inventario.</p>';
        return;
    }

    const tableHTML = `
        <div class="table-responsive">
            <table id="inventory-table" class="inventory-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        ${isAllAreasView ? '<th>Área</th>' : ''}
                        <th>Imagen</th>
                        <th>Nombre</th>
                        <th>Cantidad</th>
                        <th>Categoría</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    ${items.map(item => `
                        <tr>
                            <td>${item.id}</td>
                            ${isAllAreasView ? `<td>${nodesMap.get(item.node_id) || 'N/A'}</td>` : ''}
                            <td>
                                <img src="${item.imagePath || 'placeholder.png'}" 
                                     alt="${item.name}" 
                                     class="inventory-thumbnail"
                                     style="width:50px; height:50px; object-fit:cover;">
                            </td>
                            <td>${item.name}</td>
                            <td>${item.quantity}</td>
                            <td>${item.category}</td>
                            <td>${item.description || ''}</td>
                            <td>${statusOptions.find(s => s.value === item.status)?.label || 'Desconocido'}</td>
                            <td class="actions">
                                <button class="edit-btn" data-item-id="${item.id}"><i class="fas fa-edit"></i></button>
                                <button class="delete-btn" data-item-id="${item.id}"><i class="fas fa-trash-alt"></i></button>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    tableContainer.innerHTML = tableHTML;

    tableContainer.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            const itemToEdit = items.find(i => i.id == button.dataset.itemId);
            showItemForm(node, itemToEdit);
        });
    });

    tableContainer.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const itemToDelete = items.find(i => i.id == button.dataset.itemId);
            if (confirm(`¿Está seguro de eliminar el ítem "${itemToDelete.name}"?`)) {
                const formData = new FormData();
                formData.append('id', itemToDelete.id);
                try {
                    const response = await fetch(API_URL + 'delete_item.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        addNotification('item_deleted', `Ítem "${itemToDelete.name}" eliminado del área "${node.name}".`, sessionStorage.getItem('username'));
                        displayInventory(node, isAdmin);
                    } else {
                        alert(`Error: ${result.message}`);
                    }
                } catch (error) {
                    alert('Error de conexión al eliminar.');
                }
            }
        });
    });

    tableContainer.querySelectorAll('.inventory-thumbnail').forEach(thumbnail => {
        thumbnail.addEventListener('click', (event) => {
            const src = event.target.src;
            if (src.endsWith('placeholder.png')) return;
            const modalOverlay = document.createElement('div');
            modalOverlay.className = 'modal-overlay';
            const modalImage = document.createElement('img');
            modalImage.className = 'image-modal-content';
            modalImage.src = src;
            const closeModalBtn = document.createElement('span');
            closeModalBtn.className = 'close-modal';
            closeModalBtn.innerHTML = '&times;';
            modalOverlay.append(modalImage, closeModalBtn);
            document.body.appendChild(modalOverlay);
            const close = () => modalOverlay.remove();
            closeModalBtn.onclick = close;
            modalOverlay.onclick = (e) => {
                if (e.target === modalOverlay) close();
            };
        });
    });
}