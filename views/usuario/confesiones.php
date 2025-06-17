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

// Fetch current user's full data
$stmt = $pdo->prepare("SELECT id, nombre_usuario, avatar, show_online_status, allow_stranger_messages FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$current_username = htmlspecialchars($current_user_data['nombre_usuario'] ?? 'Usuario RUMORA');
$current_user_avatar = htmlspecialchars($current_user_data['avatar'] ?? 'https://placehold.co/100x100/A855F7/ffffff?text=RU');


// Determine the date for which to display confessions
$current_display_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Validate the date format to prevent SQL injection or errors
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $current_display_date)) {
    $current_display_date = date('Y-m-d'); // Default to today if invalid date is provided
}

// Calculate previous and next day for navigation
$previous_day = date('Y-m-d', strtotime($current_display_date . ' -1 day'));
$next_day = date('Y-m-d', strtotime($current_display_date . ' +1 day'));
$is_today = ($current_display_date == date('Y-m-d'));


// Function to fetch confessions for a given date
// MODIFICACIÓN CLAVE AQUÍ: JOIN con la tabla 'users' para obtener nombre_usuario
function getConfessionsForDate($pdo, $date, $current_user_id) {
    $confessions = [];
    $stmt = $pdo->prepare("
        SELECT
            c.confession_id,
            c.content,
            c.created_at,
            c.likes,
            u.nombre_usuario, -- Añadido para obtener el nombre del usuario
            u.avatar,         -- Añadido para obtener el avatar del usuario (opcional)
            (SELECT COUNT(*) FROM confession_likes cl WHERE cl.confession_id = c.confession_id AND cl.user_id = ?) AS user_liked
        FROM
            confessions c
        LEFT JOIN
            users u ON c.user_id = u.id -- LEFT JOIN para manejar confesiones anónimas (user_id IS NULL)
        WHERE
            DATE(c.created_at) = ?
        ORDER BY
            c.created_at DESC
    ");
    $stmt->execute([$current_user_id, $date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get confessions for the current display date
$confessions_for_display = getConfessionsForDate($pdo, $current_display_date, $current_user_id);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RUMORA - Confesiones</title>
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

        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.7); /* Black w/ opacity */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex; /* Show flex when active */
        }
        .modal-content {
            background-color: #1f2937;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            transform: translateY(-50px);
            opacity: 0;
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
        }
        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
        .close-button {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-button:hover,
        .close-button:focus {
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 antialiased">
    <!-- Header -->
    <header class="bg-gray-800 shadow-xl py-4 px-6 flex justify-between items-center fixed top-0 left-0 w-full z-50">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-pink-600 flex items-center justify-center shadow-md">
                <i class="fas fa-heart text-xl text-white"></i>
            </div>
            <h1 class="text-xl font-bold text-white">Confesiones RUMORA</h1>
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

    <!-- Main Application Container -->
    <div class="flex app-container-height relative">
        <!-- Sidebar - Navigation -->
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
                <button class="p-4 text-pink-400 hover:bg-pink-700/30 rounded-lg w-full flex items-center space-x-4 transition-all duration-200 active" data-tab="confesiones">
                    <i class="fas fa-heart text-xl"></i>
                    <span class="font-medium">Confesiones</span>
                </button>
                <a href="ajustes.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-cog text-xl"></i>
                    <span class="font-medium">Ajustes</span>
                </a>
            </nav>
            <div class="flex-1 overflow-y-auto custom-scroll">
                <div class="p-4 text-center text-gray-400">
                    <p>Comparte tus pensamientos más profundos.</p>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main class="flex-1 flex flex-col bg-gray-800 shadow-inner rounded-l-xl md:rounded-none overflow-hidden main-content-area">
            <div class="border-b border-gray-700 p-4 flex items-center justify-between flex-shrink-0 bg-gray-800 z-10">
                <h3 class="font-bold text-white text-lg flex-1 text-center md:text-left">
                    Confesiones del
                    <?php
                        if ($current_display_date == date('Y-m-d')) {
                            echo 'Hoy, ' . date('d/m/Y');
                        } elseif ($current_display_date == date('Y-m-d', strtotime('-1 day'))) {
                            echo 'Ayer, ' . date('d/m/Y', strtotime('-1 day'));
                        } else {
                            echo date('d/m/Y', strtotime($current_display_date));
                        }
                    ?>
                </h3>
                <button id="post-confession-btn" class="bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 px-4 rounded-full shadow-lg transition duration-200">
                    <i class="fas fa-plus mr-2"></i>Nueva Confesión
                </button>
            </div>

            <div class="flex items-center justify-between p-4 bg-gray-700 border-b border-gray-600">
                <a href="confesiones.php?date=<?php echo $previous_day; ?>" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-full transition duration-200">
                    <i class="fas fa-chevron-left mr-2"></i>Día Anterior
                </a>
                <a href="confesiones.php?date=<?php echo $next_day; ?>" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-full transition duration-200 <?php echo $is_today ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                    Día Siguiente<i class="fas fa-chevron-right ml-2"></i>
                </a>
            </div>

            <div class="flex-1 overflow-y-auto custom-scroll p-4 space-y-6">
                <?php if (!empty($confessions_for_display)): ?>
                    <div class="grid gap-4">
                        <?php foreach ($confessions_for_display as $confession): ?>
                            <div class="bg-gray-700 rounded-lg p-4 shadow-md flex flex-col">
                                <p class="text-green-400 text-lg mb-3"><?php echo htmlspecialchars($confession['content']); ?></p>
                                <div class="flex justify-between items-center text-gray-400 text-sm mt-auto">
                                    <!-- MODIFICACIÓN CLAVE AQUÍ: Mostrar el nombre de usuario o "Anónimo" -->
                                    <span class="font-semibold text-pink-400">
                                        <?php echo htmlspecialchars($confession['nombre_usuario'] ?? 'Anónimo'); ?>
                                    </span>
                                    <span><?php echo date('H:i A', strtotime($confession['created_at'])); ?></span>
                                    <div class="flex items-center space-x-2">
                                        <button class="like-btn p-2 rounded-full hover:bg-gray-600 transition-colors duration-200 <?php echo $confession['user_liked'] ? 'text-blue-500' : 'text-gray-400'; ?>" data-confession-id="<?php echo $confession['confession_id']; ?>">
                                            <i class="fas fa-thumbs-up"></i>
                                        </button>
                                        <span class="like-count"><?php echo $confession['likes']; ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-center text-gray-400 p-4">No hay confesiones para este día.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Mobile Bottom Navigation -->
   <nav class="fixed bottom-0 left-0 w-full bg-gray-800 border-t border-gray-700 md:hidden flex justify-around items-center h-16 z-50 shadow-2xl">
    <a href="dashboard.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200 mobile-nav-item">
        <i class="fas fa-comments text-xl"></i>
        <span class="text-xs mt-1">Chats</span>
    </a>
    <a href="usuarios_cercanos.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200 mobile-nav-item">
        <i class="fas fa-street-view text-xl"></i>
        <span class="text-xs mt-1">Cercanos</span>
    </a>
    <a href="confesiones.php" class="text-pink-400 active flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200 mobile-nav-item">
        <i class="fas fa-heart text-xl"></i>
        <span class="text-xs mt-1">Confesiones</span>
    </a>
    <a href="ajustes.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200 mobile-nav-item">
        <i class="fas fa-cog text-xl"></i>
        <span class="text-xs mt-1">Ajustes</span>
    </a>
</nav>

    <!-- Post Confession Modal -->
    <div id="confession-modal" class="modal hidden">
        <div class="modal-content">
            <span class="close-button" id="close-confession-modal">&times;</span>
            <h2 class="text-xl font-bold text-white mb-4">Escribe tu Confesión</h2>
            <form id="confession-form" class="space-y-4">
                <textarea id="confession-content" rows="6" class="w-full bg-gray-700 rounded-lg p-3 text-white focus:outline-none focus:ring-2 focus:ring-pink-500 transition-shadow duration-200 shadow-inner" placeholder="Escribe tu confesión aquí... (máximo 500 caracteres)" maxlength="500"></textarea>
                <div class="flex justify-end">
                    <button type="submit" class="bg-pink-600 hover:bg-pink-700 text-white font-bold py-2 px-5 rounded-full shadow-lg transition duration-200">
                        Publicar Confesión
                    </button>
                </div>
            </form>
            <div id="confession-status-message" class="mt-4 text-center text-sm"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const postConfessionBtn = document.getElementById('post-confession-btn');
            const confessionModal = document.getElementById('confession-modal');
            const closeConfessionModalBtn = document.getElementById('close-confession-modal');
            const confessionForm = document.getElementById('confession-form');
            const confessionContentInput = document.getElementById('confession-content');
            const confessionStatusMessage = document.getElementById('confession-status-message');

            // User menu dropdown toggle (unchanged from previous versions)
            const userMenuBtn = document.getElementById('user-menu');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            if (userMenuBtn) {
                userMenuBtn.addEventListener('click', (event) => {
                    userMenuDropdown.classList.toggle('hidden');
                    event.stopPropagation();
                });
            }
            document.addEventListener('click', (event) => {
                if (userMenuDropdown && !userMenuBtn.contains(event.target) && !userMenuDropdown.contains(event.target)) {
                    userMenuDropdown.classList.add('hidden');
                }
            });


            // Show/Hide Confession Modal
            if (postConfessionBtn) {
                postConfessionBtn.addEventListener('click', () => {
                    confessionModal.classList.remove('hidden');
                    setTimeout(() => confessionModal.classList.add('show'), 10); // Add show class for animation
                });
            }
            if (closeConfessionModalBtn) {
                closeConfessionModalBtn.addEventListener('click', () => {
                    confessionModal.classList.remove('show');
                    setTimeout(() => confessionModal.classList.add('hidden'), 300); // Hide after animation
                    confessionStatusMessage.innerHTML = ''; // Clear status message
                    confessionContentInput.value = ''; // Clear input
                });
            }
            // Close modal if clicked outside content
            confessionModal.addEventListener('click', (event) => {
                if (event.target === confessionModal) {
                    confessionModal.classList.remove('show');
                    setTimeout(() => confessionModal.classList.add('hidden'), 300);
                    confessionStatusMessage.innerHTML = '';
                    confessionContentInput.value = '';
                }
            });

            // Handle Confession Submission
            if (confessionForm) {
                confessionForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const content = confessionContentInput.value.trim();

                    if (content.length === 0) {
                        confessionStatusMessage.className = 'mt-4 text-center text-sm text-red-400';
                        confessionStatusMessage.textContent = 'La confesión no puede estar vacía.';
                        return;
                    }
                    if (content.length > 500) {
                        confessionStatusMessage.className = 'mt-4 text-center text-sm text-red-400';
                        confessionStatusMessage.textContent = 'La confesión es demasiado larga. Máximo 500 caracteres.';
                        return;
                    }

                    confessionStatusMessage.className = 'mt-4 text-center text-sm text-blue-400';
                    confessionStatusMessage.textContent = 'Publicando confesión...';

                    try {
                        const response = await fetch('actions/post_confession.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `content=${encodeURIComponent(content)}`
                        });
                        const data = await response.json();

                        if (data.success) {
                            confessionStatusMessage.className = 'mt-4 text-center text-sm text-green-400';
                            confessionStatusMessage.textContent = '¡Confesión publicada exitosamente!';
                            confessionContentInput.value = ''; // Clear input
                            // Optionally, refresh confessions list or add new confession to the list dynamically
                            setTimeout(() => {
                                confessionModal.classList.remove('show');
                                confessionModal.classList.add('hidden');
                                window.location.reload(); // Reload to show new confession
                            }, 1500);
                        } else {
                            confessionStatusMessage.className = 'mt-4 text-center text-sm text-red-400';
                            confessionStatusMessage.textContent = 'Error al publicar confesión: ' + data.message;
                        }
                    } catch (error) {
                        console.error('Error posting confession:', error);
                        confessionStatusMessage.className = 'mt-4 text-center text-sm text-red-400';
                        confessionStatusMessage.textContent = 'Error de conexión al publicar confesión.';
                    }
                });
            }

            // Handle Like Button Clicks
            document.querySelectorAll('.like-btn').forEach(button => {
                button.addEventListener('click', async (e) => {
                    const confessionId = e.currentTarget.dataset.confessionId;
                    const likeCountSpan = e.currentTarget.nextElementSibling; // Get the sibling span for count

                    try {
                        const response = await fetch('actions/like_confession.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `confession_id=${confessionId}`
                        });
                        const data = await response.json();

                        if (data.success) {
                            likeCountSpan.textContent = data.new_likes;
                            if (data.action === 'liked') {
                                // Changed from text-pink-500 to text-blue-500 for thumbs-up to distinguish
                                e.currentTarget.classList.add('text-blue-500');
                                e.currentTarget.classList.remove('text-gray-400');
                            } else { // unliked
                                e.currentTarget.classList.remove('text-blue-500');
                                e.currentTarget.classList.add('text-gray-400');
                            }
                        } else {
                            console.error('Error al actualizar like:', data.message);
                            // Optionally, show a temporary message to the user
                        }
                    } catch (error) {
                        console.error('Error de conexión al dar like:', error);
                        // Optionally, show a temporary message to the user
                    }
                });
            });
        });
    </script>
</body>
</html>