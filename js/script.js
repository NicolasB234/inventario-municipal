import { orgStructure } from './org-structure.js';
import { displayInventory, setupModalClosers } from './inventory-functions.js';

const PHP_BASE_URL = 'php/';

// Esta función ahora SOLO se encarga de la parte visual de la campanita.
export function addNotification(message) {
    // Almacenamos el mensaje en una lista temporal solo para mostrarlo en el panel.
    const localNotifications = JSON.parse(localStorage.getItem('appNotifications')) || [];
    const newNotification = {
        id: Date.now(),
        message: message,
        timestamp: new Date().toISOString(),
    };
    localNotifications.unshift(newNotification);
    if (localNotifications.length > 20) {
        localNotifications.length = 20;
    }
    localStorage.setItem('appNotifications', JSON.stringify(localNotifications));

    // Aumentamos el contador de "no leídas" para el efecto visual.
    let unreadCount = parseInt(localStorage.getItem('unreadNotifications') || '0');
    unreadCount++;
    localStorage.setItem('unreadNotifications', unreadCount);
    
    updateNotificationCounter();

    const notificationBellButton = document.getElementById('notification-bell-btn');
    if (notificationBellButton) {
        notificationBellButton.classList.add('new-notification');
        setTimeout(() => {
            notificationBellButton.classList.remove('new-notification');
        }, 500);
    }
}

function updateNotificationCounter() {
    const notificationBellButton = document.getElementById('notification-bell-btn');
    const notificationCounterElement = document.getElementById('notification-counter');
    const unreadCount = parseInt(localStorage.getItem('unreadNotifications') || '0');

    if (!notificationBellButton || !notificationCounterElement) {
        return;
    }

    if (unreadCount > 0) {
        notificationCounterElement.textContent = unreadCount;
        notificationCounterElement.style.display = 'block';
        notificationBellButton.classList.add('has-notifications');
    } else {
        notificationCounterElement.style.display = 'none';
        notificationBellButton.classList.remove('has-notifications');
    }
}

// --- INICIO DE LA CORRECCIÓN ---
let lastNotificationId = 0; 
let lastAdminRequestCount = 0;

async function fetchUpdates() {
    try {
        const response = await fetch(`${PHP_BASE_URL}get_updates.php?last_id=${lastNotificationId}`);
        const data = await response.json();
        const isAdmin = sessionStorage.getItem('isAdmin') === 'true';

        // 1. Si el servidor nos envía notificaciones nuevas.
        if (data.new_notifications && data.new_notifications.length > 0) {
            data.new_notifications.forEach(notif => {
                addNotification(notif.details); // Añadimos la notificación a la campanita.
                lastNotificationId = Math.max(lastNotificationId, notif.id);
            });
        }

        // 2. Si el servidor nos dice que refresquemos la vista del inventario.
        if (data.refresh_inventory) {
            const selectedNodeElement = document.querySelector('#org-nav .node-content.selected');
            if (selectedNodeElement) {
                const nodeId = selectedNodeElement.parentElement.dataset.nodeId;
                const nodeName = selectedNodeElement.textContent.trim();
                setTimeout(() => {
                    displayInventory({ id: nodeId, name: nodeName }, isAdmin);
                }, 500);
            }
        }
        
        // 3. Si somos admin, actualizamos el número de la campanita con las solicitudes pendientes.
        if (isAdmin) {
             const notificationCounterElement = document.getElementById('notification-counter');
             if (notificationCounterElement) {
                if (data.pending_admin_requests > lastAdminRequestCount) {
                    addNotification("Ha recibido una nueva solicitud pendiente de revisión.");
                }
                lastAdminRequestCount = data.pending_admin_requests;

                if (lastAdminRequestCount > 0) {
                    notificationCounterElement.textContent = lastAdminRequestCount;
                    notificationCounterElement.style.display = 'block';
                } else {
                    notificationCounterElement.style.display = 'none';
                }
             }
        }

    } catch (error) {
        console.error('Error en el polling de actualizaciones:', error);
    }
}

function startUpdatePolling() {
    fetchUpdates(); // Verificamos al cargar la página.
    setInterval(fetchUpdates, 15000); // Y luego cada 15 segundos.
}
// --- FIN DE LA CORRECCIÓN ---

async function handleLogin(username, password) {
    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);
    try {
        const response = await fetch(`${PHP_BASE_URL}login.php`, { method: 'POST', body: formData });
        return await response.json();
    } catch (error) {
        console.error('Error en handleLogin:', error);
        return { success: false, message: 'Error de conexión.' };
    }
}

async function handleRegister(username, password, areaId) {
    const formData = new FormData();
    formData.append('username', username);
    formData.append('password', password);
    formData.append('area_id', areaId);
    try {
        const response = await fetch(`${PHP_BASE_URL}register.php`, { method: 'POST', body: formData });
        return await response.json();
    } catch (error) {
        console.error('Error en handleRegister:', error);
        return { success: false, message: 'Error de conexión.' };
    }
}

async function checkLoginStatusFromServer() {
    try {
        const response = await fetch(`${PHP_BASE_URL}check_session.php`);
        return await response.json();
    } catch (error) {
        console.error('Error en checkLoginStatusFromServer:', error);
        return { loggedIn: false };
    }
}

function getAllInventoryNodes(structure) {
    let nodes = [];
    function traverse(arr, path = []) {
        arr.forEach(item => {
            const currentPath = [...path, item.name];
            if (['departamento', 'coordinacion', 'area', 'direction'].includes(item.type)) {
                nodes.push({ id: item.id, name: currentPath.join(' > ') });
            }
            if (item.children) {
                traverse(item.children, currentPath);
            }
        });
    }
    traverse(structure);
    return nodes;
}

function getAreaNameById(structure, id) {
    for (const node of structure) {
        if (node.id === id) return node.name;
        if (node.children) {
            const found = getAreaNameById(node.children, id);
            if (found) return found;
        }
    }
    return null;
}

function findNodeWithParents(id, structure, path = []) {
    for (const node of structure) {
        if (node.id === id) return { node, path: [...path, node.id] };
        if (node.children) {
            const result = findNodeWithParents(id, node.children, [...path, node.id]);
            if (result) return result;
        }
    }
    return null;
}

function filterOrgStructureForUser(nodes, targetId) {
    return nodes.map(node => {
        if (node.id === targetId) return node;
        if (node.children) {
            const filteredChildren = filterOrgStructureForUser(node.children, targetId);
            if (filteredChildren.length > 0) return { ...node, children: filteredChildren };
        }
        return null;
    }).filter(Boolean);
}

function toggleNotifPanel() {
    const notificationBellButton = document.getElementById('notification-bell-btn');
    const existingPanel = document.querySelector('.notifications-panel');
    if (existingPanel) {
        existingPanel.remove();
        return;
    }

    const notifPanel = document.createElement('div');
    notifPanel.className = 'notifications-panel';
    const localNotifications = JSON.parse(localStorage.getItem('appNotifications')) || [];
    let notificationsHtml = '<h3>Actividad Reciente</h3><div class="notifications-list">';
    if (localNotifications.length === 0) {
        notificationsHtml += '<p>No hay notificaciones recientes.</p>';
    } else {
        localNotifications.forEach(notif => {
            const date = new Date(notif.timestamp).toLocaleString('es-AR', { dateStyle: 'short', timeStyle: 'short' });
            notificationsHtml += `<p><strong>[${date}]</strong> ${notif.message}</p>`;
        });
    }
    notificationsHtml += '</div><a href="notificaciones.html" class="view-all-notifs">Ver todo el historial</a>';
    notifPanel.innerHTML = notificationsHtml;
    document.body.appendChild(notifPanel);

    localStorage.setItem('unreadNotifications', '0');
    updateNotificationCounter();

    setTimeout(() => {
        const closePanel = (e) => {
            if (!notifPanel.contains(e.target) && e.target !== notificationBellButton && !notificationBellButton.contains(e.target)) {
                notifPanel.remove();
                document.removeEventListener('click', closePanel);
            }
        };
        document.addEventListener('click', closePanel);
    }, 0);
}


// --- LÓGICA PRINCIPAL AL CARGAR LA PÁGINA ---
document.addEventListener('DOMContentLoaded', async () => {

    if (document.getElementById('login-section')) {
        const loginStatus = await checkLoginStatusFromServer();
        if (loginStatus.loggedIn) {
            window.location.href = 'index.html';
            return;
        }
        
        const loginForm = document.getElementById('login-section');
        const registerForm = document.getElementById('register-section');
        const loginMessage = document.getElementById('login-message');
        const registerMessage = document.getElementById('register-message');
        
        document.querySelectorAll('.toggle-form').forEach(button => {
            button.addEventListener('click', () => {
                loginForm.classList.toggle('active');
                registerForm.classList.toggle('active');
                loginMessage.textContent = '';
                registerMessage.textContent = '';
            });
        });

        document.getElementById('login-btn').addEventListener('click', async () => {
            const username = document.getElementById('login-username').value.trim();
            const password = document.getElementById('login-password').value.trim();
            if (!username || !password) {
                loginMessage.textContent = 'Por favor, introduce usuario y contraseña.';
                return;
            }
            const result = await handleLogin(username, password);
            if (result.success) {
                sessionStorage.setItem('userAreaId', result.areaId);
                sessionStorage.setItem('username', username);
                sessionStorage.setItem('isAdmin', result.isAdmin || false);
                window.location.href = 'index.html';
            } else {
                loginMessage.textContent = result.message || 'Error al iniciar sesión.';
            }
        });
        
        const searchInput = document.getElementById('area-search-input');
        const searchResults = document.getElementById('area-search-results');
        const hiddenAreaIdInput = document.getElementById('register-area-id');
        const allAreas = getAllInventoryNodes(orgStructure);

        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            searchResults.innerHTML = '';
            hiddenAreaIdInput.value = '';
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            const filteredAreas = allAreas.filter(area => area.name.toLowerCase().includes(query));
            if (filteredAreas.length > 0) {
                filteredAreas.forEach(area => {
                    const item = document.createElement('div');
                    item.className = 'result-item';
                    item.textContent = area.name;
                    item.addEventListener('click', () => {
                        searchInput.value = area.name;
                        hiddenAreaIdInput.value = area.id;
                        searchResults.style.display = 'none';
                    });
                    searchResults.appendChild(item);
                });
                searchResults.style.display = 'block';
            } else {
                searchResults.style.display = 'none';
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-container')) {
                searchResults.style.display = 'none';
            }
        });
        
        document.getElementById('register-btn').addEventListener('click', async () => {
            const username = document.getElementById('register-username').value.trim();
            const password = document.getElementById('register-password').value.trim();
            const areaId = hiddenAreaIdInput.value;
            if (!username || !password || !areaId) {
                registerMessage.textContent = 'Por favor, rellene todos los campos (incluyendo el área).';
                return;
            }
            const result = await handleRegister(username, password, areaId);
            registerMessage.textContent = result.message;
            registerMessage.style.color = result.success ? '#2ecc71' : '#e74c3c';
            if (result.success) {
                setTimeout(() => {
                    loginForm.classList.add('active');
                    registerForm.classList.remove('active');
                }, 2000);
            }
        });

    } else if (document.getElementById('inventory-section')) {
        const loginStatus = await checkLoginStatusFromServer();
        if (!loginStatus.loggedIn) {
            window.location.href = 'login.html';
            return;
        }
        
        setupModalClosers();
        
        const notificationBellButton = document.getElementById('notification-bell-btn');
        if (notificationBellButton) {
            notificationBellButton.addEventListener('click', toggleNotifPanel);
        }
        
        startUpdatePolling(); // Iniciamos el polling aquí.
        
        const viewButtons = document.querySelectorAll('.view-btn');
        const viewSections = document.querySelectorAll('.view-section');

        function switchView(viewId) {
            viewSections.forEach(section => section.classList.remove('active'));
            viewButtons.forEach(button => button.classList.remove('active'));
            const activeSection = document.getElementById(viewId);
            if (activeSection) activeSection.classList.add('active');
            const activeButton = document.getElementById(`show-${viewId}-btn`);
            if (activeButton) activeButton.classList.add('active');
        }

        viewButtons.forEach(button => {
            button.addEventListener('click', () => {
                const viewId = button.id.replace('show-', '').replace('-btn', '');
                switchView(viewId);
            });
        });

        const userAreaId = sessionStorage.getItem('userAreaId');
        const currentUsername = sessionStorage.getItem('username');
        const isAdmin = sessionStorage.getItem('isAdmin') === 'true';
        const orgNav = document.getElementById('org-nav');
        const contentTitle = document.getElementById('content-title');
        let selectedNodeElement = null;

        function buildOrgTree(nodes, parentElement) {
            const ul = document.createElement('ul');
            nodes.forEach(node => {
                const li = document.createElement('li');
                li.dataset.nodeId = node.id;
                const nodeContent = document.createElement('div');
                nodeContent.className = 'node-content';
                const toggle = document.createElement('span');
                toggle.className = 'toggle';
                if (node.children && node.children.length > 0) {
                    toggle.textContent = '▸';
                    toggle.onclick = (e) => { e.stopPropagation(); toggleNode(li); };
                } else {
                    toggle.textContent = ' ';
                }
                const nodeNameSpan = document.createElement('span');
                nodeNameSpan.textContent = node.name;
                nodeContent.append(toggle, nodeNameSpan);
                nodeContent.onclick = () => selectNode(li, node);
                li.appendChild(nodeContent);
                if (node.children) {
                    buildOrgTree(node.children, li);
                }
                ul.appendChild(li);
            });
            parentElement.appendChild(ul);
        }

        function toggleNode(liElement) {
            liElement.classList.toggle('expanded');
            const toggle = liElement.querySelector('.toggle');
            if (toggle) {
                toggle.textContent = liElement.classList.contains('expanded') ? '▾' : '▸';
            }
        }

        function expandParents(nodePath) {
            if (!nodePath) return;
            nodePath.forEach(id => {
                const liElement = orgNav.querySelector(`li[data-node-id="${id}"]`);
                if (liElement && !liElement.classList.contains('expanded')) {
                    toggleNode(liElement);
                }
            });
        }

        const sidebarToggleBtn = document.getElementById('sidebar-toggle-btn');
        const sidebarOverlay = document.getElementById('sidebar-overlay');
        const body = document.body;

        const openSidebar = () => body.classList.add('sidebar-open');
        const closeSidebar = () => body.classList.remove('sidebar-open');
        
        if (sidebarToggleBtn) sidebarToggleBtn.addEventListener('click', openSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', closeSidebar);

        function selectNode(liElement, node) {
            if (selectedNodeElement) {
                selectedNodeElement.classList.remove('selected');
            }

            if (liElement) {
                 liElement.querySelector('.node-content').classList.add('selected');
                 selectedNodeElement = liElement.querySelector('.node-content');
            } else { 
                const allAreasButton = document.getElementById('all-areas-btn');
                if (allAreasButton) {
                    allAreasButton.classList.add('selected');
                    selectedNodeElement = allAreasButton;
                }
            }
            
            closeSidebar();
            displayInventory(node, isAdmin);
        }
        
        function setupHeaderButtons() {
            const container = document.getElementById('header-right-container');
            if (!container) return;

            const dynamicButtons = container.querySelectorAll('.header-btn, #requests-btn');
            dynamicButtons.forEach(btn => btn.remove());
            
            const historyButton = document.createElement('button');
            historyButton.id = 'history-btn';
            historyButton.innerHTML = '<i class="fas fa-history"></i>';
            historyButton.title = "Historial";
            historyButton.className = "header-btn";
            historyButton.onclick = () => { window.location.href = 'notificaciones.html'; };
            
            const logoutButton = document.createElement('button');
            logoutButton.id = 'logout-btn';
            logoutButton.innerHTML = '<i class="fas fa-sign-out-alt"></i>';
            logoutButton.title = "Cerrar Sesión";
            logoutButton.className = "header-btn";
            logoutButton.addEventListener('click', () => { window.location.href = `${PHP_BASE_URL}logout.php`; });
            
            container.prepend(logoutButton);
            container.prepend(historyButton);

            if (isAdmin) {
                const requestsButton = document.createElement('button');
                requestsButton.id = 'requests-btn';
                requestsButton.innerHTML = '<i class="fas fa-inbox"></i>';
                requestsButton.title = "Solicitudes Pendientes";
                requestsButton.className = "header-btn";
                requestsButton.onclick = () => { window.location.href = 'solicitudes.html'; };
                container.prepend(requestsButton);
            }
        }
        
        function init() {
            orgNav.innerHTML = '';
            
            let welcomeMessage = `Bienvenido, ${currentUsername}`;
            if (userAreaId && userAreaId !== 'null') { 
                const areaName = getAreaNameById(orgStructure, userAreaId);
                if (areaName) {
                    welcomeMessage += ` del área ${areaName}`;
                }
            }
            contentTitle.textContent = welcomeMessage;

            setupHeaderButtons();

            const searchContainer = document.querySelector('.sidebar-search-container');
            if (isAdmin) {
                if (searchContainer) searchContainer.style.display = 'block';

                const allAreasButton = document.createElement('div');
                allAreasButton.id = 'all-areas-btn';
                allAreasButton.className = 'node-content';
                allAreasButton.innerHTML = `<i class="fas fa-globe" style="margin-right: 10px;"></i> Todas las Áreas`;
                allAreasButton.onclick = () => selectNode(null, { id: '', name: 'Todas las Áreas' });
                orgNav.appendChild(allAreasButton);

                buildOrgTree(orgStructure, orgNav);
                selectNode(null, { id: '', name: 'Todas las Áreas' });
            } else {
                if (searchContainer) searchContainer.style.display = 'none';

                const initialNodeData = findNodeWithParents(userAreaId, orgStructure);
                const userSpecificStructure = filterOrgStructureForUser(orgStructure, userAreaId);
                
                if (!userAreaId || (userSpecificStructure.length === 0 && userAreaId)) {
                    contentTitle.textContent = userAreaId ? 'Error de Configuración' : `Bienvenido, ${currentUsername}`;
                    const tableView = document.getElementById('table-view');
                    tableView.innerHTML = userAreaId ? 
                        `<p class="error-message"><b>Error:</b> Su área asignada ('${userAreaId}') no se encontró.</p>` :
                        '<p class="error-message">No tiene un área asignada. Contacte a un administrador.</p>';
                } else {
                    buildOrgTree(userSpecificStructure, orgNav);
                    if (initialNodeData && initialNodeData.node) {
                        const nodeElement = orgNav.querySelector(`li[data-node-id="${initialNodeData.node.id}"]`);
                        if (nodeElement) {
                           selectNode(nodeElement, initialNodeData.node);
                           expandParents(initialNodeData.path);
                        }
                    }
                }
            }
        }

        init();

        const chatbotContainer = document.getElementById('chatbot-container');
        const chatbotToggleBtn = document.getElementById('chatbot-toggle-btn');
        const closeChatbotBtn = document.getElementById('close-chatbot-btn');

        if (chatbotToggleBtn && chatbotContainer && closeChatbotBtn) {
            const toggleChatbot = () => chatbotContainer.classList.toggle('active');
            chatbotToggleBtn.addEventListener('click', toggleChatbot);
            closeChatbotBtn.addEventListener('click', toggleChatbot);
        }

        const orgSearchInput = document.getElementById('org-search-input');
        if (orgSearchInput) {
            orgSearchInput.addEventListener('input', () => {
                const searchTerm = orgSearchInput.value.toLowerCase().trim();
                const allNodes = orgNav.querySelectorAll('#org-nav li');

                allNodes.forEach(li => {
                    const nodeNameElement = li.querySelector('.node-content > span:last-of-type');
                    const nodeName = nodeNameElement ? nodeNameElement.textContent.toLowerCase() : '';
                    if (nodeName.includes(searchTerm)) {
                        li.style.display = "";
                        let parent = li.parentElement.closest('li');
                        while(parent) {
                            parent.style.display = "";
                            parent.classList.add('expanded');
                            const toggle = parent.querySelector('.toggle');
                            if(toggle) toggle.textContent = '▾';
                            parent = parent.parentElement.closest('li');
                        }
                    } else {
                        li.style.display = "none";
                    }
                });
                if(!searchTerm) {
                     allNodes.forEach(li => {
                        li.style.display = "";
                        li.classList.remove('expanded');
                        const toggle = li.querySelector('.toggle');
                        if(toggle && toggle.textContent === '▾') toggle.textContent = '▸';
                     });
                }
            });
        }
    }
});