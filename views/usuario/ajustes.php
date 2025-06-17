<?php
session_start();

// Database connection
require_once '../../config/database.php';

// Set the default timezone to Peru (America/Lima)
date_default_timezone_set('America/Lima');

// If the user is not logged in, redirect to index
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

// Update last_seen for the current user
$stmt_update_last_seen = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
$stmt_update_last_seen->execute([$current_user_id]);

// Fetch current user's full data including privacy settings
$stmt = $pdo->prepare("SELECT id, nombre_usuario, avatar, genero, departamento, provincia, is_public, show_online_status, allow_stranger_messages FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$current_username = htmlspecialchars($current_user_data['nombre_usuario'] ?? 'Usuario RUMORA');
$current_user_avatar = htmlspecialchars($current_user_data['avatar'] ?? 'https://placehold.co/100x100/A855F7/ffffff?text=RU');
$current_user_gender = htmlspecialchars($current_user_data['genero'] ?? 'No especificado');
$current_user_department = htmlspecialchars($current_user_data['departamento'] ?? 'No especificado');
$current_user_province = htmlspecialchars($current_user_data['provincia'] ?? 'No especificado');

// Privacy settings
$is_public = $current_user_data['is_public'] ? 'checked' : '';
$show_online_status = $current_user_data['show_online_status'] ? 'checked' : '';
$allow_stranger_messages = $current_user_data['allow_stranger_messages'] ? 'checked' : '';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RUMORA - Ajustes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow: hidden; /* Prevent body scroll, allow inner elements to scroll */
        }
        /* Custom scrollbar for better aesthetics */
        .sidebar-scroll, .main-content-scroll, .friends-modal-list {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1f2937; /* thumb color track color */
        }
        .sidebar-scroll::-webkit-scrollbar, .main-content-scroll::-webkit-scrollbar, .friends-modal-list::-webkit-scrollbar {
            width: 8px; /* For vertical scrollbars */
            height: 8px; /* For horizontal scrollbars */
        }
        .sidebar-scroll::-webkit-scrollbar-track, .main-content-scroll::-webkit-scrollbar-track, .friends-modal-list::-webkit-scrollbar-track {
            background: #1f2937; /* Track color */
            border-radius: 10px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb, .main-content-scroll::-webkit-scrollbar-thumb, .friends-modal-list::-webkit-scrollbar-thumb {
            background-color: #4b5563; /* Thumb color */
            border-radius: 10px;
            border: 2px solid #1f2937; /* Border around thumb */
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
        .app-container-height {
            height: calc(100vh - 72px - 64px); /* Full height minus header and mobile nav */
        }
        @media (min-width: 768px) {
            .app-container-height {
                height: calc(100vh - 72px); /* Full height minus header only on desktop */
            }
        }

        /* Overlay for modal */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(5px);
            z-index: 1000;
        }

        /* Mobile specific view management */
        @media (max-width: 767px) { /* On mobile devices (below md) */
            .main-content-area {
                width: 100%; /* Take full width on mobile */
                border-radius: 0; /* No rounded corners on mobile full-screen */
            }
            .sidebar-mobile-hidden {
                display: none;
            }
            /* Ensure mobile bottom navigation active state is visually distinct */
            .mobile-nav-item.active {
                color: #ec4899; /* pink-500 for active state, or the color you prefer */
            }
            .mobile-nav-item:not(.active) {
                 color: #9ca3af; /* gray-400 for inactive state */
            }
        }

        /* Custom toggle switch styles */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #4B5563; /* gray-600 */
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #EC4899; /* pink-500 */
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #EC4899;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(24px);
            -ms-transform: translateX(24px);
            transform: translateX(24px);
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 antialiased">
    <header class="bg-gray-800 shadow-xl py-4 px-6 flex justify-between items-center fixed top-0 left-0 w-full z-50">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center shadow-md">
                <i class="fas fa-cog text-xl text-white"></i>
            </div>
            <h1 class="text-xl font-bold text-white">Ajustes RUMORA</h1>
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

    <div class="flex app-container-height relative">
        <aside class="w-64 bg-gray-800 flex-col border-r border-gray-700 shadow-xl z-10 hidden md:flex sidebar-mobile-hidden">
            <nav class="flex flex-col border-b border-gray-700 p-2">
                <a href="dashboard.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-comments text-xl"></i>
                    <span class="font-medium">Chats</span>
                </a>
                <a href="usuarios_cercanos.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-street-view text-xl"></i>
                    <span class="font-medium">Usuarios Cercanos</span>
                </a>
                <a href="confesiones.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-heart text-xl"></i>
                    <span class="font-medium">Confesiones</span>
                </a>
                <a href="ajustes.php" class="p-4 text-pink-400 hover:bg-pink-700/30 rounded-lg w-full flex items-center space-x-4 transition-all duration-200 active">
                    <i class="fas fa-cog text-xl"></i>
                    <span class="font-medium">Ajustes</span>
                </a>
            </nav>
            <div class="flex-1 overflow-y-auto sidebar-scroll">
                </div>
        </aside>

        <main class="flex-1 flex flex-col bg-gray-800 shadow-inner rounded-l-xl md:rounded-none overflow-hidden main-content-area">
            <div class="flex-1 overflow-y-auto main-content-scroll p-4 md:p-6">
                <h2 class="text-2xl font-bold text-white mb-6">Configuración de la Cuenta</h2>

                <div class="bg-gray-700 p-6 rounded-lg shadow-lg mb-8">
                    <h3 class="text-xl font-semibold text-white mb-4 flex items-center"><i class="fas fa-user-circle mr-3 text-blue-400"></i> Mi Perfil</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-center space-x-4">
                            <img src="<?php echo $current_user_avatar; ?>" alt="Avatar" class="w-16 h-16 rounded-full object-cover border-2 border-pink-500 shadow-md">
                            <div>
                                <p class="text-lg font-medium text-white"><?php echo $current_username; ?></p>
                                <p class="text-sm text-gray-400"><?php echo ucfirst($current_user_gender); ?></p>
                            </div>
                        </div>
                        <div class="flex flex-col justify-center">
                            <p class="text-sm text-gray-300">Departamento: <span class="font-medium text-white"><?php echo $current_user_department; ?></span></p>
                            <p class="text-sm text-gray-300">Provincia: <span class="font-medium text-white"><?php echo $current_user_province; ?></span></p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-4">Para cambiar tu avatar o nombre de usuario, contacta a soporte.</p>
                </div>

                <div class="bg-gray-700 p-6 rounded-lg shadow-lg">
                    <h3 class="text-xl font-semibold text-white mb-4 flex items-center"><i class="fas fa-shield-alt mr-3 text-green-400"></i> Privacidad</h3>

                    <div class="flex items-center justify-between py-3 border-b border-gray-600">
                        <label for="show_online_status" class="text-white text-base">Mostrar estado en línea</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="show_online_status" data-setting="show_online_status" <?php echo $show_online_status; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-gray-600">
                        <label for="allow_stranger_messages" class="text-white text-base">Permitir mensajes de desconocidos</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="allow_stranger_messages" data-setting="allow_stranger_messages" <?php echo $allow_stranger_messages; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3">
                        <label for="is_public" class="text-white text-base">Hacer mi perfil público</label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="is_public" data-setting="is_public" <?php echo $is_public; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div id="settings-message" class="mt-4 text-center text-sm font-medium hidden"></div>

            </div>
        </main>
    </div>

    <nav class="fixed bottom-0 left-0 w-full bg-gray-800 border-t border-gray-700 md:hidden flex justify-around items-center h-16 z-50 shadow-2xl">
        <a href="dashboard.php" class="mobile-nav-item text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-comments text-xl"></i>
            <span class="text-xs mt-1">Chats</span>
        </a>
        <a href="usuarios_cercanos.php" class="mobile-nav-item text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-street-view text-xl"></i>
            <span class="text-xs mt-1">Cercanos</span>
        </a>
        <a href="confesiones.php" class="mobile-nav-item text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-heart text-xl"></i>
            <span class="text-xs mt-1">Confesiones</span>
        </a>
        <a href="ajustes.php" class="mobile-nav-item active text-pink-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-cog text-xl"></i>
            <span class="text-xs mt-1">Ajustes</span>
        </a>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const userMenuBtn = document.getElementById('user-menu');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            const settingsMessageDiv = document.getElementById('settings-message');

            // User menu dropdown toggle
            if (userMenuBtn) {
                userMenuBtn.addEventListener('click', (event) => {
                    userMenuDropdown.classList.toggle('hidden');
                    event.stopPropagation();
                });
            }

            // Hide dropdown menu if clicked outside
            document.addEventListener('click', (event) => {
                if (userMenuDropdown && !userMenuBtn.contains(event.target) && !userMenuDropdown.contains(event.target)) {
                    userMenuDropdown.classList.add('hidden');
                }
            });

            // Handle privacy setting toggles
            document.querySelectorAll('.toggle-switch input').forEach(toggle => {
                toggle.addEventListener('change', async (event) => {
                    const settingName = event.target.dataset.setting;
                    const settingValue = event.target.checked ? 1 : 0; // Convert boolean to 1 or 0 for database

                    try {
                        const response = await fetch('actions/update_user_setting.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `setting_name=${encodeURIComponent(settingName)}&setting_value=${settingValue}`
                        });
                        const data = await response.json();

                        if (data.success) {
                            settingsMessageDiv.classList.remove('hidden', 'text-red-500');
                            settingsMessageDiv.classList.add('text-green-500');
                            settingsMessageDiv.textContent = 'Configuración actualizada con éxito.';
                            setTimeout(() => { settingsMessageDiv.classList.add('hidden'); }, 3000); // Hide after 3 seconds
                        } else {
                            settingsMessageDiv.classList.remove('hidden', 'text-green-500');
                            settingsMessageDiv.classList.add('text-red-500');
                            settingsMessageDiv.textContent = 'Error al actualizar configuración: ' + data.message;
                            setTimeout(() => { settingsMessageDiv.classList.add('hidden'); }, 5000);
                        }
                    } catch (error) {
                        console.error('Error actualizando setting:', error);
                        settingsMessageDiv.classList.remove('hidden', 'text-green-500');
                        settingsMessageDiv.classList.add('text-red-500');
                        settingsMessageDiv.textContent = 'Error de conexión al actualizar configuración.';
                        setTimeout(() => { settingsMessageDiv.classList.add('hidden'); }, 5000);
                    }
                });
            });
        });
    </script>
</body>
</html>