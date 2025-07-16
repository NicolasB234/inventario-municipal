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

function getShortNameNodesMap() {
    const map = new Map();
    function traverse(nodes) {
        nodes.forEach(node => {
            map.set(node.id, node.name);
            if (node.children && node.children.length > 0) {
                traverse(node.children);
            }
        });
    }
    traverse(orgStructure);
    return map;
}
const nodesMap = getShortNameNodesMap();

function getFullPathNodesMap() {
    const map = new Map();
    function traverse(nodes, path = []) {
        nodes.forEach(node => {
            const currentPath = [...path, node.name];
            map.set(node.id, currentPath.join(' > '));
            if (node.children && node.children.length > 0) {
                traverse(node.children, currentPath);
            }
        });
    }
    traverse(orgStructure);
    return map;
}

let currentFormSubmitHandler = null;

export function showItemForm(node, item = null) {
    const modal = document.getElementById('modal-agregar-item');
    const form = document.getElementById('form-agregar-item');
    if (!modal || !form) return;

    const isEditing = item !== null;
    form.reset();

    modal.querySelector('h2').textContent = isEditing ? `Editar Ítem: ${item.name}` : 'Agregar Nuevo Ítem';
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

    const imagePreview = document.getElementById('imagePreview');
    const imageInput = document.getElementById('form-itemImage');
    if(imageInput && imagePreview){
        imageInput.onchange = () => {
            if (imageInput.files && imageInput.files[0]) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                };
                reader.readAsDataURL(imageInput.files[0]);
            }
        };

        if (isEditing && item.imagePath) {
            imagePreview.src = item.imagePath;
            imagePreview.style.display = 'block';
        } else {
            imagePreview.style.display = 'none';
            imagePreview.src = '#';
        }
    }
    
    if (currentFormSubmitHandler) {
        form.removeEventListener('submit', currentFormSubmitHandler);
    }

    currentFormSubmitHandler = async (event) => {
        event.preventDefault();
        const formData = new FormData(form);
        const endpoint = isEditing ? 'update_item.php' : 'add_item.php';
        const isAdmin = sessionStorage.getItem('isAdmin') === 'true';
        
        try {
            const response = await fetch(API_URL + endpoint, { method: 'POST', body: formData });
            const result = await response.json();
            
            alert(result.message);

            if (result.success) {
                addNotification(result.message);
                
                closeItemForm();
                if (isAdmin || !isEditing) {
                    displayInventory(node, isAdmin);
                }
            }
        } catch (error) {
            console.error('Error al guardar:', error);
            alert('Error de conexión al guardar el ítem.');
        }
    };
    form.addEventListener('submit', currentFormSubmitHandler);
    modal.style.display = 'flex';
}

export function closeItemForm() {
    const modal = document.getElementById('modal-agregar-item');
    if (modal) {
        modal.style.display = 'none';
    }
}

export function setupModalClosers() {
    const modal = document.getElementById('modal-agregar-item');
    if (modal) {
        modal.querySelector('.close-modal').addEventListener('click', closeItemForm);
        modal.querySelector('#cancel-item-btn').addEventListener('click', closeItemForm);
        modal.addEventListener('click', (e) => { if (e.target === modal) closeItemForm(); });
    }
}

function showTransferForm(node, items) {
    if (items.length === 0) {
        alert('No hay ítems en esta área para traspasar.');
        return;
    }
    if (document.getElementById('transfer-modal')) return;

    const modalOverlay = document.createElement('div');
    modalOverlay.className = 'modal-overlay';
    modalOverlay.id = 'transfer-modal';

    const fullPathMap = getFullPathNodesMap();
    let allNodes = [];
    for (const [id, name] of fullPathMap.entries()) {
        if (id !== node.id) {
            allNodes.push({ id, name });
        }
    }
    allNodes.sort((a, b) => a.name.localeCompare(b.name));

    modalOverlay.innerHTML = `
        <div class="modal-content" style="width: 500px; cursor: default;">
            <button class="close-modal" id="cancel-transfer-btn-x">&times;</button>
            <h3>Traspaso de Ítem</h3>
            <form id="transfer-form">
                <div class="form-row"><label>Área Origen:</label><input type="text" value="${node.name}" disabled></div>
                <div class="form-row"><label for="item-to-transfer">Ítem a Traspasar:</label><select id="item-to-transfer" name="itemId" required>
                    <option value="" disabled selected>Seleccione un ítem...</option>
                    ${items.map(item => `<option value="${item.id}">${item.name} (ID: ${item.id})</option>`).join('')}
                </select></div>
                <div class="form-row"><label for="destination-area">Área de Destino:</label><select id="destination-area" name="destinationNodeId" required>
                    <option value="" disabled selected>Seleccione un destino...</option>
                    ${allNodes.map(n => `<option value="${n.id}">${n.name}</option>`).join('')}
                </select></div>
                <div class="form-row"><label for="transfer-reason">Motivo del Traspaso:</label><textarea id="transfer-reason" name="reason" rows="3" placeholder="Especifique el motivo del traspaso..." required></textarea></div>
                <div class="form-row" style="flex-direction: row; justify-content: flex-end; gap: 10px;">
                    <button type="button" id="cancel-transfer-btn" class="button">Cancelar</button>
                    <button type="submit" class="button">Confirmar Traspaso</button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(modalOverlay);

    const closeModal = () => modalOverlay.remove();
    document.getElementById('cancel-transfer-btn').onclick = closeModal;
    document.getElementById('cancel-transfer-btn-x').onclick = closeModal;
    modalOverlay.onclick = (e) => { if (e.target === modalOverlay) closeModal(); };

    document.getElementById('transfer-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        if (!confirm(`¿Está seguro de que desea solicitar el traspaso de este ítem?`)) return;

        const isAdmin = sessionStorage.getItem('isAdmin') === 'true';

        try {
            const response = await fetch(`${API_URL}transfer_item.php`, { method: 'POST', body: formData });
            const result = await response.json();
            
            alert(result.message);

            if (result.success) {
                addNotification(result.message);
                closeModal();
                if (isAdmin) {
                    displayInventory(node, isAdmin);
                }
            }
        } catch (error) {
            console.error('Error de conexión:', error);
            alert('Error de conexión al realizar el traspaso.');
        }
    });
}

function exportToXLSX(node, items) {
    if (items.length === 0) {
        alert('No hay datos en esta área para exportar.');
        return;
    }
    const fullPathMap = getFullPathNodesMap();
    
    const dataToExport = items.map(item => ({
        nombre: item.name,
        cantidad: item.quantity,
        categoria: item.category,
        descripcion: item.description || '',
        fechaadquisicion: item.acquisitionDate,
        estado: statusOptions.find(s => s.value === item.status)?.label || item.status,
        area: fullPathMap.get(item.node_id) || 'N/A'
    }));

    const worksheet = XLSX.utils.json_to_sheet(dataToExport);
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, "Inventario");
    XLSX.writeFile(workbook, `inventario_${node.id || 'global'}.xlsx`);
}

export async function displayInventory(node, isAdmin = false) {
    const tableView = document.getElementById('table-view');
    const galleryView = document.getElementById('gallery-view');
    
    tableView.innerHTML = '<p>Cargando inventario...</p>';
    if (galleryView) {
        const galleryContainer = galleryView.querySelector('#gallery-container');
        if (galleryContainer) galleryContainer.innerHTML = '<p>Cargando galería...</p>';
    }

    try {
        const response = await fetch(`${API_URL}get_inventory.php?node_id=${node.id}`);
        const result = await response.json();
        
        if (result.success) {
            setupInventoryUI(node, result.data, isAdmin);
            renderTable(node, result.data, isAdmin);
            if (galleryView) renderGallery(result.data, isAdmin); 
        } else {
            const errorMessage = `<p>Error al cargar el inventario: ${result.message}</p>`;
            tableView.innerHTML = errorMessage;
            if (galleryView) galleryView.querySelector('#gallery-container').innerHTML = errorMessage;
            document.getElementById('global-controls-container').innerHTML = '';
        }
    } catch (error) {
        console.error('Error al obtener el inventario:', error);
        const connErrorMessage = '<p>Error de conexión al cargar el inventario.</p>';
        tableView.innerHTML = connErrorMessage;
        if (galleryView) galleryView.querySelector('#gallery-container').innerHTML = connErrorMessage;
    }
}

function setupInventoryUI(node, items, isAdmin) {
    const controlsContainer = document.getElementById('global-controls-container');
    if (!controlsContainer) return;

    controlsContainer.innerHTML = `
        <div class="inventory-controls">
            <button id="add-item-btn"><i class="fas fa-plus"></i> Agregar Item</button>
            <button id="transfer-item-btn"><i class="fas fa-random"></i> Traspasar Item</button>
            <button id="import-xlsx-btn"><i class="fas fa-file-import"></i> Importar XLSX</button>
            <input type="file" id="xlsx-file-input" accept=".xlsx, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" style="display: none;" />
            <button id="export-xlsx-btn"><i class="fas fa-file-export"></i> Exportar XLSX</button>
            <button id="export-docx-btn"><i class="fas fa-file-word"></i> Exportar DOCX</button>
            <button id="toggle-filters-btn"><i class="fas fa-filter"></i> Mostrar Filtros</button>
        </div>
        <div class="filter-controls-container">
            <div class="filter-row"><label for="filter-id">Buscar por ID:</label><input type="text" id="filter-id" placeholder="ID del ítem"></div>
            <div class="filter-row"><label for="filter-name">Buscar por Nombre:</label><input type="text" id="filter-name" placeholder="Nombre del ítem"></div>
            <div class="filter-row"><label for="filter-category">Categoría:</label><select id="filter-category"><option value="">Todas</option>${categories.map(cat => `<option value="${cat}">${cat}</option>`).join('')}</select></div>
            <div class="filter-row"><label for="filter-status">Estado:</label><select id="filter-status"><option value="">Todos</option>${statusOptions.map(option => `<option value="${option.value}">${option.label}</option>`).join('')}</select></div>
            <div class="filter-row"><label for="filter-date-from">Adquirido Desde:</label><input type="date" id="filter-date-from"></div>
            <div class="filter-row"><label for="filter-date-to">Adquirido Hasta:</label><input type="date" id="filter-date-to"></div>
            <div class="filter-actions">
                <button id="apply-filters-btn" class="button"><i class="fas fa-check"></i> Aplicar</button>
                <button id="reset-filters-btn" class="button reset-filters-btn"><i class="fas fa-undo"></i> Limpiar</button>
            </div>
        </div>
    `;

    document.getElementById("add-item-btn").addEventListener('click', () => showItemForm(node, null));
    document.getElementById("transfer-item-btn").addEventListener('click', () => showTransferForm(node, items));
    document.getElementById("export-xlsx-btn").addEventListener('click', () => exportToXLSX(node, items));
    
    const filterControls = controlsContainer.querySelector('.filter-controls-container');
    document.getElementById('toggle-filters-btn').addEventListener('click', () => {
        filterControls.classList.toggle('visible');
        const button = document.getElementById('toggle-filters-btn');
        button.innerHTML = filterControls.classList.contains('visible') 
            ? '<i class="fas fa-eye-slash"></i> Ocultar Filtros' 
            : '<i class="fas fa-filter"></i> Mostrar Filtros';
    });
    
    document.getElementById('apply-filters-btn').addEventListener('click', () => {
        const filters = {
            id: document.getElementById('filter-id').value.trim(),
            name: document.getElementById('filter-name').value.toLowerCase().trim(),
            category: document.getElementById('filter-category').value,
            status: document.getElementById('filter-status').value,
            dateFrom: document.getElementById('filter-date-from').value,
            dateTo: document.getElementById('filter-date-to').value,
        };
        const filteredItems = items.filter(item => {
            const itemDate = item.acquisitionDate ? new Date(item.acquisitionDate) : null;
            const fromDate = filters.dateFrom ? new Date(filters.dateFrom) : null;
            const toDate = filters.dateTo ? new Date(filters.dateTo) : null;
            if(itemDate) itemDate.setUTCHours(0, 0, 0, 0);
            if(fromDate) fromDate.setUTCHours(0, 0, 0, 0);
            if(toDate) toDate.setUTCHours(0, 0, 0, 0);
            
            return (!filters.id || String(item.id).includes(filters.id)) &&
                   (!filters.name || item.name.toLowerCase().includes(filters.name)) &&
                   (!filters.category || item.category === filters.category) &&
                   (!filters.status || item.status === filters.status) &&
                   (!fromDate || (itemDate && itemDate >= fromDate)) &&
                   (!toDate || (itemDate && itemDate <= toDate));
        });
        renderTable(node, filteredItems, isAdmin);
        renderGallery(filteredItems, isAdmin);
    });

    document.getElementById('reset-filters-btn').addEventListener('click', () => {
        filterControls.querySelectorAll('input, select').forEach(el => el.value = '');
        renderTable(node, items, isAdmin);
        renderGallery(items, isAdmin);
    });

    // --- INICIO DE LA CORRECCIÓN ---
    const importBtn = document.getElementById('import-xlsx-btn');
    const fileInput = document.getElementById('xlsx-file-input');
    importBtn.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array', cellDates: true });
                const firstSheetName = workbook.SheetNames[0];
                const worksheet = workbook.Sheets[firstSheetName];
                const json = XLSX.utils.sheet_to_json(worksheet);
                if (json.length === 0) {
                    alert('El archivo XLSX está vacío.');
                    return;
                }
                
                const mappedJson = json.filter(row => Object.keys(row).length > 0).map(row => {
                    const newRow = {};
                    for (const key in row) {
                        const lowerKey = key.toLowerCase().trim().replace(/\s+/g, '');
                        if (lowerKey.startsWith('nombre')) newRow.nombre = row[key];
                        else if (lowerKey.startsWith('cantidad')) newRow.cantidad = row[key];
                        else if (lowerKey.startsWith('categor')) newRow.categoria = row[key];
                        else if (lowerKey.startsWith('descripci')) newRow.descripcion = row[key];
                        else if (lowerKey.startsWith('fecha')) {
                            if (row[key] instanceof Date && !isNaN(row[key])) {
                                const date = row[key];
                                const tzoffset = date.getTimezoneOffset() * 60000;
                                newRow.fechaadquisicion = (new Date(date - tzoffset)).toISOString().slice(0, 10);
                            } else {
                                newRow.fechaadquisicion = null;
                            }
                        }
                        else if (lowerKey.startsWith('estado')) newRow.estado = row[key];
                        else if (lowerKey.startsWith('area')) newRow.area = row[key];
                    }
                    return newRow;
                });

                const response = await fetch(`${API_URL}bulk_import.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(mappedJson)
                });

                if (!response.ok) {
                    const errorResult = await response.json().catch(() => ({ message: response.statusText }));
                    throw new Error(errorResult.message || 'Error desconocido del servidor.');
                }

                const result = await response.json();
                
                alert(result.message);

                if (result.success) {
                    addNotification(result.message);
                    if (isAdmin) {
                        displayInventory(node, isAdmin);
                    }
                }
            } catch (error) {
                console.error('Error durante la importación:', error);
                alert(`Error al importar los datos: ${error.message}`);
            }
        };
        reader.readAsArrayBuffer(file);
        fileInput.value = '';
    });
    // --- FIN DE LA CORRECCIÓN ---
}

function renderTable(node, items, isAdmin) {
    const tableContainer = document.getElementById('table-view');
    if (!tableContainer) return;
    tableContainer.innerHTML = '';
    
    const isAllAreasView = isAdmin && node.id === '';
    
    if (items.length === 0) {
        tableContainer.innerHTML = '<p>No hay ítems en esta área o no coinciden con los filtros.</p>';
        return;
    }

    tableContainer.innerHTML = `
        <div class="table-responsive">
            <table id="inventory-table" class="inventory-table">
                <thead><tr>
                    <th>ID</th>
                    ${isAllAreasView ? '<th>Área</th>' : ''}
                    <th>Nombre</th>
                    <th>Cantidad</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th>Fecha de Adquisición</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr></thead>
                <tbody>
                    ${items.map(item => `
                        <tr>
                            <td>${item.id}</td>
                            ${isAllAreasView ? `<td>${nodesMap.get(item.node_id) || 'N/A'}</td>` : ''}
                            <td>${item.name}</td>
                            <td>${item.quantity}</td>
                            <td>${item.category}</td>
                            <td>${item.description || ''}</td>
                            <td>${item.acquisitionDate || 'No especificada'}</td>
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

    tableContainer.querySelectorAll('.edit-btn').forEach(button => {
        button.addEventListener('click', () => {
            const itemToEdit = items.find(i => i.id == button.dataset.itemId);
            showItemForm(node, itemToEdit);
        });
    });

    tableContainer.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const itemToDelete = items.find(i => i.id == button.dataset.itemId);
            if (confirm(`¿Está seguro de que desea solicitar la eliminación del ítem "${itemToDelete.name}"?`)) {
                try {
                    const formData = new FormData();
                    formData.append('id', itemToDelete.id);
                    const response = await fetch(API_URL + 'delete_item.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    
                    alert(result.message);

                    if (result.success) {
                        addNotification(result.message);
                        if (isAdmin) {
                            displayInventory(node, isAdmin);
                        }
                    }
                } catch (error) {
                    alert('Error de conexión al eliminar.');
                }
            }
        });
    });
}

function renderGallery(items, isAdmin) {
    const galleryContainer = document.getElementById('gallery-container');
    if (!galleryContainer) return;
    galleryContainer.innerHTML = '';

    if (items.length === 0) {
        galleryContainer.innerHTML = '<p>No hay ítems para mostrar en esta área.</p>';
        return;
    }
    
    const itemsWithImages = items.filter(item => item.imagePath);

    if (itemsWithImages.length === 0) {
        galleryContainer.innerHTML = '<p>Ningún ítem en esta vista tiene una imagen asociada.</p>';
        return;
    }

    itemsWithImages.forEach(item => {
        const card = document.createElement('div');
        card.className = 'gallery-card';
        card.innerHTML = `
            <div class="gallery-card-img-container">
                <img src="${item.imagePath}" alt="${item.name}" class="gallery-card-img">
            </div>
            <div class="gallery-card-title">${item.name}</div>
        `;
        galleryContainer.appendChild(card);
    });
}