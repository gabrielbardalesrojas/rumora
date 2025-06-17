<?php
session_start();

// Database connection (adjust credentials as needed)
require_once '../../config/database.php'; // Include the PDO database connection

// If the user is not logged in, redirect to index
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Update last_seen for the current user
$stmt_update_last_seen = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
$stmt_update_last_seen->execute([$current_user_id]);

// Fetch current user's full data
$stmt = $pdo->prepare("SELECT nombre_usuario, avatar, departamento, provincia, last_seen, show_online_status, allow_stranger_messages FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$current_username = htmlspecialchars($current_user_data['nombre_usuario'] ?? 'Usuario RUMORA');
$current_user_avatar = htmlspecialchars($current_user_data['avatar'] ?? 'https://placehold.co/100x100/A855F7/ffffff?text=RU');
$current_user_departamento = $current_user_data['departamento'];
$current_user_provincia = $current_user_data['provincia'];

$search_departamento = $_GET['departamento'] ?? $current_user_departamento;
$search_provincia = $_GET['provincia'] ?? $current_user_provincia;
$search_gender = $_GET['genero'] ?? '';

$nearby_users = [];
$sql = "SELECT id, nombre_usuario, avatar, last_seen, show_online_status, genero, departamento, provincia 
        FROM users 
        WHERE id != ? 
        AND is_public = TRUE";

$params = [$current_user_id];

if ($search_departamento && $search_provincia) {
    $sql .= " AND departamento = ? AND provincia = ?";
    $params[] = $search_departamento;
    $params[] = $search_provincia;
} else if ($search_departamento) {
     $sql .= " AND departamento = ?";
     $params[] = $search_departamento;
}

if ($search_gender && in_array($search_gender, ['male', 'female', 'other'])) {
    $sql .= " AND genero = ?";
    $params[] = $search_gender;
}

$sql .= " ORDER BY last_seen DESC LIMIT 20"; // Limit results for performance

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $is_online = (time() - strtotime($row['last_seen'])) < (5 * 60); // 5 minutes threshold
    $row['is_online'] = $is_online;
    $nearby_users[] = $row;
}

// Fetch distinct departments and provinces for search filters
$departamentos = [];
$stmt = $pdo->query("SELECT DISTINCT departamento FROM users ORDER BY departamento ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $departamentos[] = $row['departamento'];
}

$provincias = [];
if ($search_departamento) {
    $stmt = $pdo->prepare("SELECT DISTINCT provincia FROM users WHERE departamento = ? ORDER BY provincia ASC");
    $stmt->execute([$search_departamento]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $provincias[] = $row['provincia'];
    }
}


?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios Cercanos - RUMORA Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom scrollbar for better aesthetics */
        .sidebar-scroll, .main-content-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1f2937; /* thumb color track color */
        }
        .sidebar-scroll::-webkit-scrollbar, .main-content-scroll::-webkit-scrollbar {
            width: 8px; /* For vertical scrollbars */
            height: 8px; /* For horizontal scrollbars */
        }
        .sidebar-scroll::-webkit-scrollbar-track, .main-content-scroll::-webkit-scrollbar-track {
            background: #1f2937; /* Track color */
            border-radius: 10px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb, .main-content-scroll::-webkit-scrollbar-thumb {
            background-color: #4b5563; /* Thumb color */
            border-radius: 10px;
            border: 2px solid #1f2937; /* Border around thumb */
        }
        .tab-content {
            display: none;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        .tab-content.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        /* Responsive adjustments for fixed header and mobile nav */
        body {
            padding-top: 72px; /* Height of the header */
            padding-bottom: 64px; /* Height of the mobile bottom nav */
        }
        @media (min-width: 768px) { /* md breakpoint */
            body {
                padding-bottom: 0; /* No bottom padding on desktop */
            }
        }

        /* Ensure main container takes full height minus fixed elements */
        .main-container-height {
            height: calc(100vh - 72px - 64px); /* Full height minus header and mobile nav */
        }
        @media (min-width: 768px) {
            .main-container-height {
                height: calc(100vh - 72px); /* Full height minus header only on desktop */
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 antialiased">
    <header class="bg-gray-800 shadow-xl py-4 px-6 flex justify-between items-center fixed top-0 left-0 w-full z-50">
        <div class="flex items-center space-x-3">
            <button id="back-to-chats-btn" class="p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 <?php echo $show_chat_view ? '' : 'hidden'; ?>">
                <i class="fas fa-arrow-left text-lg"></i>
            </button>
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center shadow-md <?php echo $show_chat_view ? 'hidden md:flex' : 'flex'; ?>">
                <i class="fas fa-comment-dots text-xl text-white"></i>
            </div>
            <h1 class="text-xl font-bold text-white <?php echo $show_chat_view ? 'hidden md:block' : ''; ?>">RUMORA Chat</h1>
            <h1 class="text-xl font-bold text-white <?php echo $show_chat_view ? 'block md:hidden' : 'hidden'; ?>"><?php echo $selected_chat_username; ?></h1>
        </div>
        <div class="flex items-center space-x-4">
            <button id="notifications" class="relative p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                <i class="fas fa-bell text-lg"></i>
                <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></span>
            </button>
            <div class="relative">
                <button id="user-menu" class="flex items-center space-x-2 focus:outline-none p-2 rounded-lg hover:bg-gray-700 transition-colors duration-200">
                    <img src="<?php echo $current_user_avatar; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover shadow-inner">
                    <span class="hidden md:inline text-white font-medium"><?php echo $current_username; ?></span>
                    <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:inline"></i>
                </button>
                <div id="user-menu-dropdown" class="absolute right-0 mt-2 w-48 bg-gray-700 rounded-md shadow-lg py-1 z-20 hidden">
                    
                    <a href="ajustes.php" class="block px-4 py-2 text-sm text-white hover:bg-gray-600">Configuración</a>
                    <a href="../../logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-600">Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <div class="flex main-container-height relative">
        <aside class="hidden md:flex w-64 bg-gray-800 flex-col border-r border-gray-700 shadow-xl z-10">
            <nav class="flex flex-col border-b border-gray-700 p-2">
                <a href="dashboard.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-comments text-xl"></i>
                    <span class="font-medium">Chats</span>
                </a>
                <button class="tab-btn active p-4 text-indigo-400 hover:bg-indigo-700/30 rounded-lg w-full flex items-center space-x-4 transition-all duration-200" data-tab="nearby-users">
                    <i class="fas fa-street-view text-xl"></i>
                    <span class="font-medium">Usuarios Cercanos</span>
                </button>
                <a href="confesiones.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-heart text-xl"></i>
                    <span class="font-medium">Confesiones</span>
                </a>
                <a href="ajustes.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-cog text-xl"></i>
                    <span class="font-medium">Ajustes</span>
                </a>
            </nav>
            <div class="flex-1 overflow-y-auto sidebar-scroll">
                <div class="p-4 border-b border-gray-700">
                    <form method="GET" action="usuarios_cercanos.php" class="space-y-3">
                        <div>
                            <label for="departamento-desktop" class="block text-sm font-medium text-gray-300 mb-1">Departamento:</label>
                            <select id="departamento-desktop" name="departamento" class="block w-full bg-gray-700 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Todos</option>
                                <?php foreach ($departamentos as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($search_departamento == $dept) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="provincia-desktop" class="block text-sm font-medium text-gray-300 mb-1">Provincia:</label>
                            <select id="provincia-desktop" name="provincia" class="block w-full bg-gray-700 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Todas</option>
                                <?php foreach ($provincias as $prov): ?>
                                    <option value="<?php echo htmlspecialchars($prov); ?>" <?php echo ($search_provincia == $prov) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($prov); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="genero-desktop" class="block text-sm font-medium text-gray-300 mb-1">Género:</label>
                            <select id="genero-desktop" name="genero" class="block w-full bg-gray-700 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                <option value="">Cualquiera</option>
                                <option value="male" <?php echo ($search_gender == 'male') ? 'selected' : ''; ?>>Masculino</option>
                                <option value="female" <?php echo ($search_gender == 'female') ? 'selected' : ''; ?>>Femenino</option>
                                <option value="other" <?php echo ($search_gender == 'other') ? 'selected' : ''; ?>>Otro</option>
                            </select>
                        </div>
                        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">
                            Buscar
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col bg-gray-800 shadow-inner rounded-l-xl md:rounded-none overflow-hidden">
            <div class="border-b border-gray-700 p-4 flex items-center justify-between flex-shrink-0 bg-gray-800 z-10 md:hidden">
                <h3 class="font-bold text-white">Usuarios Cercanos</h3>
                <button id="filter-toggle-mobile" class="p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                    <i class="fas fa-filter text-lg"></i>
                </button>
            </div>

            <div id="mobile-filter-form" class="md:hidden p-4 border-b border-gray-700 bg-gray-800 hidden">
                <form method="GET" action="usuarios_cercanos.php" class="space-y-3">
                    <div>
                        <label for="departamento-mobile" class="block text-sm font-medium text-gray-300 mb-1">Departamento:</label>
                        <select id="departamento-mobile" name="departamento" class="block w-full bg-gray-700 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Todos</option>
                            <?php foreach ($departamentos as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($search_departamento == $dept) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="provincia-mobile" class="block text-sm font-medium text-gray-300 mb-1">Provincia:</label>
                        <select id="provincia-mobile" name="provincia" class="block w-full bg-gray-700 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Todas</option>
                            <?php foreach ($provincias as $prov): ?>
                                <option value="<?php echo htmlspecialchars($prov); ?>" <?php echo ($search_provincia == $prov) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prov); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="genero-mobile" class="block text-sm font-medium text-gray-300 mb-1">Género:</label>
                        <select id="genero-mobile" name="genero" class="block w-full bg-gray-700 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Cualquiera</option>
                            <option value="male" <?php echo ($search_gender == 'male') ? 'selected' : ''; ?>>Masculino</option>
                            <option value="female" <?php echo ($search_gender == 'female') ? 'selected' : ''; ?>>Femenino</option>
                            <option value="other" <?php echo ($search_gender == 'other') ? 'selected' : ''; ?>>Otro</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">
                        Aplicar Filtros
                    </button>
                </form>
            </div>

            <div id="nearby-users" class="tab-content active flex-1 overflow-y-auto main-content-scroll p-4 space-y-3">
                <?php if (!empty($nearby_users)): ?>
                    <?php foreach ($nearby_users as $user): ?>
                        <a href="dashboard.php?chat_with=<?php echo $user['id']; ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-700 transition-colors duration-200 cursor-pointer">
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="w-12 h-12 rounded-full object-cover shadow-md">
                            <div class="flex-1">
                                <h4 class="text-white font-semibold"><?php echo htmlspecialchars($user['nombre_usuario']); ?></h4>
                                <p class="text-xs text-gray-400">
                                    <?php echo htmlspecialchars($user['departamento']); ?>, <?php echo htmlspecialchars($user['provincia']); ?>
                                </p>
                                <p class="text-xs 
                                    <?php echo ($user['show_online_status'] && $user['is_online']) ? 'text-green-400' : 'text-gray-400'; ?>">
                                    <?php echo ($user['show_online_status'] && $user['is_online']) ? 'En línea' : 'Últ. vez ' . date('H:i', strtotime($user['last_seen'])); ?>
                                </p>
                            </div>
                            <button class="p-2 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white transition-colors duration-200">
                                <i class="fas fa-comment text-lg"></i>
                            </button>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-400 p-4">No se encontraron usuarios en esta ubicación o con estos filtros.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <nav class="fixed bottom-0 left-0 w-full bg-gray-800 border-t border-gray-700 md:hidden flex justify-around items-center h-16 z-50 shadow-2xl">
        <a href="dashboard.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-comments text-xl"></i>
            <span class="text-xs mt-1">Chats</span>
        </a>
        <button class="tab-btn active text-indigo-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200" data-tab="nearby-users">
            <i class="fas fa-street-view text-xl"></i>
            <span class="text-xs mt-1">Cercanos</span>
        </button>
        <a href="confesiones.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-heart text-xl"></i>
            <span class="text-xs mt-1">Confesiones</span>
        </a>
        <a href="ajustes.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-cog text-xl"></i>
            <span class="text-xs mt-1">Ajustes</span>
        </a>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterToggleMobile = document.getElementById('filter-toggle-mobile');
            const mobileFilterForm = document.getElementById('mobile-filter-form');
            const desktopDepartamentoSelect = document.getElementById('departamento-desktop');
            const desktopProvinciaSelect = document.getElementById('provincia-desktop');
            const mobileDepartamentoSelect = document.getElementById('departamento-mobile');
            const mobileProvinciaSelect = document.getElementById('provincia-mobile');

            if (filterToggleMobile) {
                filterToggleMobile.addEventListener('click', () => {
                    mobileFilterForm.classList.toggle('hidden');
                });
            }

            // Function to load provinces based on selected department
            const loadProvinces = async (departmentSelect, provinceSelect) => {
                const departamento = departmentSelect.value;
                provinceSelect.innerHTML = '<option value="">Todas</option>'; // Reset provinces

                if (departamento) {
                    try {
                        const response = await fetch(`actions/get_provinces.php?departamento=${encodeURIComponent(departamento)}`);
                        const provincias = await response.json();
                        provincias.forEach(prov => {
                            const option = document.createElement('option');
                            option.value = prov;
                            option.textContent = prov;
                            provinceSelect.appendChild(option);
                        });
                        // Re-select the previously selected province if it exists in the new list
                        const currentProvincia = "<?php echo $search_provincia; ?>";
                        if (currentProvincia) {
                            const optionToSelect = Array.from(provinceSelect.options).find(option => option.value === currentProvincia);
                            if (optionToSelect) {
                                optionToSelect.selected = true;
                            }
                        }
                    } catch (error) {
                        console.error('Error loading provinces:', error);
                    }
                }
            };

            // Event listeners for department select changes
            if (desktopDepartamentoSelect) {
                desktopDepartamentoSelect.addEventListener('change', () => loadProvinces(desktopDepartamentoSelect, desktopProvinciaSelect));
            }
            if (mobileDepartamentoSelect) {
                mobileDepartamentoSelect.addEventListener('change', () => loadProvinces(mobileDepartamentoSelect, mobileProvinciaSelect));
            }

            // Initial load of provinces based on current selection
            if (desktopDepartamentoSelect.value) {
                loadProvinces(desktopDepartamentoSelect, desktopProvinciaSelect);
            }
            if (mobileDepartamentoSelect.value) {
                loadProvinces(mobileDepartamentoSelect, mobileProvinciaSelect);
            }

            // User menu toggle
            const userMenuBtn = document.getElementById('user-menu');
            const userMenu = document.getElementById('user-menu-dropdown'); // Assuming you'll add this dropdown

            if (userMenuBtn) {
                userMenuBtn.addEventListener('click', (e) => {
                    // Prevent closing if clicking inside the menu itself
                    if (userMenu) {
                        userMenu.classList.toggle('hidden');
                    }
                });
            }

            // Close user menu if clicked outside
            document.addEventListener('click', (e) => {
                if (userMenu && !userMenuBtn.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>