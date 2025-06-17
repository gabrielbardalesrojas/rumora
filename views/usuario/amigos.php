<?php
session_start();

require_once '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];

$stmt_update_last_seen = $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?");
$stmt_update_last_seen->execute([$current_user_id]);

$stmt = $pdo->prepare("SELECT nombre_usuario, avatar FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$current_username = htmlspecialchars($current_user_data['nombre_usuario'] ?? 'Usuario RUMORA');
$current_user_avatar = htmlspecialchars($current_user_data['avatar'] ?? 'https://placehold.co/100x100/A855F7/ffffff?text=RU');


// Fetch current user's friends
$friends = [];
$stmt_friends = $pdo->prepare("
    SELECT
        u.id,
        u.nombre_usuario,
        u.avatar,
        u.last_seen,
        u.show_online_status,
        (SELECT COUNT(*) FROM messages WHERE receiver_id = :current_user_id AND sender_id = u.id AND is_read = FALSE) AS unread_messages_count
    FROM user_friends uf
    JOIN users u ON uf.friend_id = u.id
    WHERE uf.user_id = :current_user_id
    ORDER BY u.nombre_usuario ASC
");
$stmt_friends->execute([':current_user_id' => $current_user_id]);
while ($row = $stmt_friends->fetch(PDO::FETCH_ASSOC)) {
    $is_online = (time() - strtotime($row['last_seen'])) < (5 * 60); // 5 minutes threshold
    $row['is_online'] = $is_online;
    $friends[] = $row;
}

// Logic for searching other users to add as friends
$search_query = $_GET['search_query'] ?? '';
$search_results = [];

if (!empty($search_query)) {
    $stmt_search = $pdo->prepare("
        SELECT
            id,
            nombre_usuario,
            avatar,
            last_seen,
            show_online_status,
            CASE WHEN EXISTS (SELECT 1 FROM user_friends WHERE user_id = :current_user_id AND friend_id = u.id) THEN TRUE ELSE FALSE END AS is_friend,
            CASE WHEN EXISTS (SELECT 1 FROM user_blocks WHERE blocker_id = :current_user_id AND blocked_id = u.id) THEN TRUE ELSE FALSE END AS is_blocked
        FROM users u
        WHERE (nombre_usuario LIKE ? OR numero LIKE ?)
        AND id != :current_user_id
        AND is_public = TRUE
        LIMIT 20
    ");
    $search_param = '%' . $search_query . '%';
    $stmt_search->execute([
        ':current_user_id' => $current_user_id,
        ':current_user_id' => $current_user_id,
        $search_param,
        $search_param
    ]);
    while ($row = $stmt_search->fetch(PDO::FETCH_ASSOC)) {
        $is_online = (time() - strtotime($row['last_seen'])) < (5 * 60);
        $row['is_online'] = $is_online;
        $search_results[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amigos - RUMORA Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            padding-top: 72px; /* Height of the header */
            padding-bottom: 64px; /* Height of the mobile bottom nav */
        }
        @media (min-width: 768px) {
            body { padding-bottom: 0; }
        }
        .main-container-height {
            height: calc(100vh - 72px - 64px);
        }
        @media (min-width: 768px) {
            .main-container-height {
                height: calc(100vh - 72px);
            }
        }
        .sidebar-scroll, .main-content-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1f2937;
        }
        .sidebar-scroll::-webkit-scrollbar, .main-content-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .sidebar-scroll::-webkit-scrollbar-track, .main-content-scroll::-webkit-scrollbar-track {
            background: #1f2937;
            border-radius: 10px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb, .main-content-scroll::-webkit-scrollbar-thumb {
            background-color: #4b5563;
            border-radius: 10px;
            border: 2px solid #1f2937;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 antialiased">
    <header class="bg-gray-800 shadow-xl py-4 px-6 flex justify-between items-center fixed top-0 left-0 w-full z-50">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center shadow-md">
                <i class="fas fa-user-friends text-xl text-white"></i>
            </div>
            <h1 class="text-xl font-bold text-white">Amigos</h1>
        </div>
        <div class="flex items-center space-x-4">
            <button id="notifications" class="relative p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                <i class="fas fa-bell text-lg"></i>
                <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></span>
            </button>
            <button id="user-menu" class="flex items-center space-x-2 focus:outline-none p-2 rounded-lg hover:bg-gray-700 transition-colors duration-200">
                <img src="<?php echo $current_user_avatar; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover shadow-inner">
                <span class="hidden md:inline text-white font-medium"><?php echo $current_username; ?></span>
                <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:inline"></i>
            </button>
            <div id="user-menu-dropdown" class="absolute right-6 top-16 mt-2 w-48 bg-gray-700 rounded-md shadow-lg py-1 z-20 hidden">
                <a href="ajustes.php" class="block px-4 py-2 text-sm text-white hover:bg-gray-600">Ajustes</a>
                <a href="../../logout.php" class="block px-4 py-2 text-sm text-red-400 hover:bg-gray-600">Cerrar Sesión</a>
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
                <a href="usuarios_cercanos.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-street-view text-xl"></i>
                    <span class="font-medium">Usuarios Cercanos</span>
                </a>
                <a href="confesiones.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-heart text-xl"></i>
                    <span class="font-medium">Confesiones</span>
                </a>
                <button class="p-4 text-indigo-400 hover:bg-indigo-700/30 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-user-friends text-xl"></i>
                    <span class="font-medium">Amigos</span>
                </button>
                <a href="ajustes.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-cog text-xl"></i>
                    <span class="font-medium">Ajustes</span>
                </a>
            </nav>
            <div class="flex-1 overflow-y-auto sidebar-scroll">
                <div class="p-4 border-b border-gray-700">
                    <form method="GET" action="amigos.php">
                        <label for="friend-search" class="block text-sm font-medium text-gray-300 mb-1">Buscar usuarios:</label>
                        <div class="flex space-x-2">
                            <input type="text" id="friend-search" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Buscar por nombre o número..." class="flex-1 bg-gray-700 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-md transition-colors duration-200">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="p-2 space-y-2">
                    <h3 class="text-lg font-semibold text-white px-2 mt-2">Mis Amigos</h3>
                    <?php if (!empty($friends)): ?>
                        <?php foreach ($friends as $friend): ?>
                            <a href="dashboard.php?chat_with=<?php echo $friend['id']; ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-700 transition-colors duration-200 cursor-pointer">
                                <div class="relative">
                                    <img src="<?php echo htmlspecialchars($friend['avatar']); ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover">
                                    <?php if ($friend['show_online_status'] && $friend['is_online']): ?>
                                        <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-800"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-white font-semibold"><?php echo htmlspecialchars($friend['nombre_usuario']); ?></h4>
                                    <p class="text-xs
                                        <?php echo ($friend['show_online_status'] && $friend['is_online']) ? 'text-green-400' : 'text-gray-400'; ?>">
                                        <?php echo ($friend['show_online_status'] && $friend['is_online']) ? 'En línea' : 'Últ. vez ' . date('H:i', strtotime($friend['last_seen'])); ?>
                                    </p>
                                </div>
                                <?php if ($friend['unread_messages_count'] > 0): ?>
                                    <span class="bg-indigo-600 text-white text-xs font-bold px-2 py-1 rounded-full">
                                        <?php echo $friend['unread_messages_count']; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-gray-400 p-4">Aún no tienes amigos.</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex flex-col bg-gray-800 shadow-inner rounded-l-xl md:rounded-none overflow-hidden main-content-scroll p-4">
            <h2 class="text-xl font-bold text-white mb-4">Resultados de Búsqueda</h2>
            <?php if (!empty($search_query) && !empty($search_results)): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($search_results as $user): ?>
                        <div class="bg-gray-700 rounded-lg p-4 flex items-center space-x-3 shadow-md">
                            <div class="relative">
                                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="w-12 h-12 rounded-full object-cover">
                                <?php if ($user['show_online_status'] && $user['is_online']): ?>
                                    <span class="absolute bottom-0 right-0 w-3 h-3 bg-green-500 rounded-full border-2 border-gray-700"></span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-white font-semibold"><?php echo htmlspecialchars($user['nombre_usuario']); ?></h4>
                                <p class="text-xs text-gray-400">
                                    <?php echo ($user['show_online_status'] && $user['is_online']) ? 'En línea' : 'Últ. vez ' . date('H:i', strtotime($user['last_seen'])); ?>
                                </p>
                            </div>
                            <?php if ($user['is_blocked']): ?>
                                <button class="bg-red-600 text-white px-3 py-1 rounded-md text-sm cursor-not-allowed" disabled>Bloqueado</button>
                            <?php elseif ($user['is_friend']): ?>
                                <button onclick="removeFriend(<?php echo $user['id']; ?>)" class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded-md text-sm transition-colors duration-200">
                                    <i class="fas fa-user-minus mr-1"></i> Amigo
                                </button>
                            <?php else: ?>
                                <button onclick="addFriend(<?php echo $user['id']; ?>)" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-md text-sm transition-colors duration-200">
                                    <i class="fas fa-user-plus mr-1"></i> Agregar
                                </button>
                            <?php endif; ?>
                            <a href="dashboard.php?chat_with=<?php echo $user['id']; ?>" class="p-2 rounded-full bg-blue-600 hover:bg-blue-700 text-white transition-colors duration-200" title="Iniciar Chat">
                                <i class="fas fa-comment text-lg"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php elseif (!empty($search_query) && empty($search_results)): ?>
                <p class="text-center text-gray-400 p-4">No se encontraron usuarios con ese nombre o número.</p>
            <?php else: ?>
                <p class="text-center text-gray-400 p-4">Usa la barra de búsqueda para encontrar nuevos amigos.</p>
            <?php endif; ?>
        </main>
    </div>

    <nav class="fixed bottom-0 left-0 w-full bg-gray-800 border-t border-gray-700 md:hidden flex justify-around items-center h-16 z-50 shadow-2xl">
        <a href="dashboard.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-comments text-xl"></i>
            <span class="text-xs mt-1">Chats</span>
        </a>
        <a href="usuarios_cercanos.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-street-view text-xl"></i>
            <span class="text-xs mt-1">Cercanos</span>
        </a>
        <a href="confesiones.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-heart text-xl"></i>
            <span class="text-xs mt-1">Confesiones</span>
        </a>
        <button class="active text-indigo-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-user-friends text-xl"></i>
            <span class="text-xs mt-1">Amigos</span>
        </button>
        <a href="ajustes.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-cog text-xl"></i>
            <span class="text-xs mt-1">Ajustes</span>
        </a>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // User menu toggle
            const userMenuBtn = document.getElementById('user-menu');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');

            if (userMenuBtn) {
                userMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation(); // Prevent document click from closing it immediately
                    userMenuDropdown.classList.toggle('hidden');
                });
            }

            // Close user menu if clicked outside
            document.addEventListener('click', (e) => {
                if (userMenuDropdown && !userMenuBtn.contains(e.target) && !userMenuDropdown.contains(e.target)) {
                    userMenuDropdown.classList.add('hidden');
                }
            });

            // Functions for adding/removing friends
            window.addFriend = async (friendId) => {
                try {
                    const response = await fetch('actions/add_friend.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `friend_id=${friendId}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        alert('Usuario agregado a amigos.');
                        window.location.reload(); // Reload to reflect changes
                    } else {
                        alert('Error al agregar amigo: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error adding friend:', error);
                    alert('Error de conexión al agregar amigo.');
                }
            };

            window.removeFriend = async (friendId) => {
                if (confirm('¿Estás seguro de que quieres eliminar a este usuario de tus amigos?')) {
                    try {
                        const response = await fetch('actions/remove_friend.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `friend_id=${friendId}`
                        });
                        const data = await response.json();
                        if (data.success) {
                            alert('Usuario eliminado de amigos.');
                            window.location.reload(); // Reload to reflect changes
                        } else {
                            alert('Error al eliminar amigo: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error removing friend:', error);
                        alert('Error de conexión al eliminar amigo.');
                    }
                }
            };
        });
    </script>
</body>
</html>