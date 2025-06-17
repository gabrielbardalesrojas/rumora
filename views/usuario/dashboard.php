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
$stmt = $pdo->prepare("SELECT id, nombre_usuario, avatar, departamento, provincia, last_seen, show_online_status, allow_stranger_messages FROM users WHERE id = ?");
$stmt->execute([$current_user_id]);
$current_user_data = $stmt->fetch(PDO::FETCH_ASSOC);

$current_username = htmlspecialchars($current_user_data['nombre_usuario'] ?? 'Usuario RUMORA');
$current_user_avatar = htmlspecialchars($current_user_data['avatar'] ?? 'https://placehold.co/100x100/A855F7/ffffff?text=RU');


// LOGICA PARA OBTENER LA LISTA DE CHATS (incluye amigos y no amigos con mensajes)
$chat_users_list = [];
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.nombre_usuario,
        u.avatar,
        u.last_seen,
        u.show_online_status,
        (
            SELECT message_content
            FROM messages m_latest
            WHERE (m_latest.sender_id = u.id AND m_latest.receiver_id = ?) OR (m_latest.sender_id = ? AND m_latest.receiver_id = u.id)
            ORDER BY m_latest.timestamp DESC
            LIMIT 1
        ) AS last_message_content,
        (
            SELECT timestamp
            FROM messages m_latest
            WHERE (m_latest.sender_id = u.id AND m_latest.receiver_id = ?) OR (m_latest.sender_id = ? AND m_latest.receiver_id = u.id)
            ORDER BY m_latest.timestamp DESC
            LIMIT 1
        ) AS last_message_timestamp,
        (
            SELECT COUNT(*)
            FROM messages m_unread
            WHERE m_unread.sender_id = u.id AND m_unread.receiver_id = ? AND m_unread.is_read = 0
        ) AS unread_count
    FROM
        users u
    WHERE
        u.id != ? AND (
            EXISTS (SELECT 1 FROM messages m1 WHERE (m1.sender_id = u.id AND m1.receiver_id = ?) OR (m1.sender_id = ? AND m1.receiver_id = u.id))
        )
    ORDER BY last_message_timestamp DESC, u.nombre_usuario ASC
");
$stmt->execute([
    $current_user_id, $current_user_id, // For last_message_content
    $current_user_id, $current_user_id, // For last_message_timestamp
    $current_user_id, // For unread_count
    $current_user_id, // For u.id != ?
    $current_user_id, $current_user_id // For EXISTS conditions
]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Determine if user is online based on last_seen (e.g., within the last 5 minutes)
    $is_online = (time() - strtotime($row['last_seen'])) < (5 * 60); // 5 minutes threshold
    $row['is_online'] = $is_online;
    $chat_users_list[] = $row;
}

// LOGICA PARA OBTENER LA LISTA DE AMIGOS (o todos los demás usuarios para simular)
$friends_list = [];
$stmt_friends = $pdo->prepare("SELECT id, nombre_usuario, avatar, last_seen, show_online_status FROM users WHERE id != ? LIMIT 10");
$stmt_friends->execute([$current_user_id]);
while ($row = $stmt_friends->fetch(PDO::FETCH_ASSOC)) {
    $is_online = (time() - strtotime($row['last_seen'])) < (5 * 60);
    $row['is_online'] = $is_online;
    $friends_list[] = $row;
}


// Determine the selected chat user based on GET parameter
$selected_chat_user_id = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : null;
$selected_chat_username = '';
$selected_chat_user_status = '';
$selected_chat_user_avatar = '';
$messages = [];

// Flag to control main content visibility based on selected chat
$show_chat_view = false;

if ($selected_chat_user_id) {
    // Fetch information about the selected chat user
    $stmt = $pdo->prepare("SELECT nombre_usuario, avatar, last_seen, show_online_status FROM users WHERE id = ?");
    $stmt->execute([$selected_chat_user_id]);
    if ($user_info = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $selected_chat_username = htmlspecialchars($user_info['nombre_usuario']);
        $selected_chat_user_avatar = htmlspecialchars($user_info['avatar']);
        
        $is_online_chat_user = (time() - strtotime($user_info['last_seen'])) < (5 * 60); // 5 minutes threshold
        if ($user_info['show_online_status'] && $is_online_chat_user) {
            $selected_chat_user_status = 'En línea';
        } else {
            $selected_chat_user_status = 'Últ. vez ' . date('H:i', strtotime($user_info['last_seen']));
        }
    }

    // Fetch messages between current user and selected chat user
    $stmt = $pdo->prepare("SELECT sender_id, message_content, timestamp FROM messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY timestamp ASC");
    $stmt->execute([$current_user_id, $selected_chat_user_id, $selected_chat_user_id, $current_user_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $messages[] = $row;
    }

    // Mark messages as read when entering a chat
    $stmt_mark_read = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt_mark_read->execute([$selected_chat_user_id, $current_user_id]);

    $show_chat_view = true; // Set flag to true if a chat is selected
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de RUMORA - Chat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            overflow: hidden; /* Prevent body scroll, allow inner elements to scroll */
        }
        /* Custom scrollbar for better aesthetics */
        .sidebar-scroll, .messages-scroll, .friends-modal-list {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1f2937; /* thumb color track color */
        }
        .sidebar-scroll::-webkit-scrollbar, .messages-scroll::-webkit-scrollbar, .friends-modal-list::-webkit-scrollbar {
            width: 8px; /* For vertical scrollbars */
            height: 8px; /* For horizontal scrollbars */
        }
        .sidebar-scroll::-webkit-scrollbar-track, .messages-scroll::-webkit-scrollbar-track, .friends-modal-list::-webkit-scrollbar-track {
            background: #1f2937; /* Track color */
            border-radius: 10px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb, .messages-scroll::-webkit-scrollbar-thumb, .friends-modal-list::-webkit-scrollbar-thumb {
            background-color: #4b5563; /* Thumb color */
            border-radius: 10px;
            border: 2px solid #1f2937; /* Border around thumb */
        }
        /* Typing indicator animation */
        .dot {
            animation: bounce 0.6s infinite alternate;
        }
        .dot:nth-child(2) {
            animation-delay: 0.1s;
        }
        .dot:nth-child(3) {
            animation-delay: 0.2s;
        }
        @keyframes bounce {
            from { transform: translateY(0); }
            to { transform: translateY(-3px); }
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

        /* Ensure chat container takes full height minus fixed elements */
        .chat-container-height {
            height: calc(100vh - 72px - 64px); /* Full height minus header and mobile nav */
        }
        @media (min-width: 768px) {
            .chat-container-height {
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
        /* By default, on mobile, the chat list is visible */
        .chat-list-container { /* Refers to the <aside> element */
            width: 100%; /* Full width on mobile */
            position: absolute; /* Allows it to slide over main content */
            left: 0;
            top: 0;
            bottom: 0;
            transition: transform 0.3s ease-in-out;
        }
        .chat-view-container { /* Refers to the <main> element */
            width: 100%; /* Full width on mobile */
            position: absolute; /* Allows it to slide over sidebar */
            left: 0;
            top: 0;
            bottom: 0;
            transition: transform 0.3s ease-in-out;
            transform: translateX(100%); /* Hidden by default on mobile */
        }

        /* State when a chat is selected on mobile */
        body.chat-selected-mobile .chat-list-container {
            transform: translateX(-100%); /* Hide chat list by sliding left */
        }
        body.chat-selected-mobile .chat-view-container {
            transform: translateX(0); /* Show chat view by sliding in from right */
        }

        /* Desktop overrides - ensure desktop layout is maintained */
        @media (min-width: 768px) {
            .chat-list-container {
                width: 64px; /* Default desktop sidebar width */
                position: relative; /* Not absolute on desktop */
                transform: translateX(0); /* Always visible */
                display: flex; /* Ensure it's displayed as flex column */
            }
             .chat-list-container.md\:w-64 { /* Reapply desktop width */
                width: 16rem; /* 64 * 0.25 = 16rem */
            }
            .chat-view-container {
                width: calc(100% - 16rem); /* Main content takes remaining width */
                position: relative; /* Not absolute on desktop */
                transform: translateX(0); /* Always visible on desktop */
                display: flex; /* Ensure it's displayed as flex column */
            }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 antialiased <?php echo $show_chat_view ? 'chat-selected-mobile' : ''; ?>">
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

    <div class="flex chat-container-height relative overflow-hidden"> <aside class="flex-col bg-gray-800 border-r border-gray-700 shadow-xl z-10 chat-list-container <?php echo $show_chat_view ? 'hidden md:flex' : 'flex'; ?> md:w-64">
            <nav class="flex flex-col border-b border-gray-700 p-2 hidden md:flex">
                <button class="tab-btn active p-4 text-indigo-400 hover:bg-indigo-700/30 rounded-lg w-full flex items-center space-x-4 transition-all duration-200" data-tab="chats">
                    <i class="fas fa-comments text-xl"></i>
                    <span class="font-medium">Chats</span>
                </button>
                <a href="usuarios_cercanos.php" class="p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200">
                    <i class="fas fa-street-view text-xl"></i>
                    <span class="font-medium">Usuarios Cercanos</span>
                </a>
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
                <div class="p-2 space-y-2">
                    <?php if (!empty($chat_users_list)): ?>
                        <?php foreach ($chat_users_list as $user): ?>
                            <a href="?chat_with=<?php echo $user['id']; ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-700 transition-colors duration-200 cursor-pointer <?php echo ($selected_chat_user_id == $user['id']) ? 'bg-gray-700' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover">
                                <div class="flex-1">
                                    <h4 class="text-white font-semibold"><?php echo htmlspecialchars($user['nombre_usuario']); ?></h4>
                                    <p class="text-xs text-gray-400 truncate">
                                        <?php
                                            if ($user['last_message_content']) {
                                                echo htmlspecialchars($user['last_message_content']);
                                            } else {
                                                echo ($user['show_online_status'] && $user['is_online']) ? '<span class="text-green-400">En línea</span>' : 'Últ. vez ' . date('H:i', strtotime($user['last_seen']));
                                            }
                                        ?>
                                    </p>
                                </div>
                                <?php if ($user['unread_count'] > 0): ?>
                                    <span class="ml-auto bg-indigo-600 text-white text-xs font-bold px-2 py-1 rounded-full"><?php echo $user['unread_count']; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-gray-400 p-4">No hay chats recientes. ¡Empieza uno!</p>
                    <?php endif; ?>
                </div>
            </div>
        </aside>

        <main class="flex-1 flex-col bg-gray-800 shadow-inner rounded-l-xl md:rounded-none overflow-hidden chat-view-container <?php echo $show_chat_view ? 'flex' : 'hidden md:flex'; ?>">
            <div id="chat-header" class="border-b border-gray-700 p-4 flex items-center justify-between flex-shrink-0 bg-gray-800 z-10 <?php echo $selected_chat_user_id ? '' : 'hidden'; ?> md:block">
                <?php if ($selected_chat_user_id): ?>
                    <div class="flex items-center space-x-3">
                        <img src="<?php echo $selected_chat_user_avatar; ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover shadow-md">
                        <div>
                            <h3 class="font-bold text-white"><?php echo $selected_chat_username; ?></h3>
                            <p class="text-xs text-gray-400"><?php echo $selected_chat_user_status; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        
                        <div class="relative">
                             <button id="chat-options-btn" class="p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                                <i class="fas fa-ellipsis-v text-lg"></i>
                            </button>
                            <div id="chat-options-menu" class="absolute right-0 mt-2 w-48 bg-gray-700 rounded-md shadow-lg py-1 z-20 hidden">
                                <button class="block w-full text-left px-4 py-2 text-sm text-white hover:bg-gray-600" onclick="blockUser(<?php echo $selected_chat_user_id; ?>)">Bloquear Usuario</button>
                                <button class="block w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-gray-600" onclick="deleteChat(<?php echo $selected_chat_user_id; ?>)">Eliminar Chat</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="messages-display-area" class="flex-1 overflow-y-auto messages-scroll p-4 relative">
                <?php if ($selected_chat_user_id): ?>
                    <div class="flex-1 overflow-y-auto messages-scroll space-y-4 pb-4">
                        <?php if (!empty($messages)): ?>
                            <?php
                            $current_date = '';
                            foreach ($messages as $message):
                                $message_date = date('Y-m-d', strtotime($message['timestamp']));
                                if ($message_date != $current_date) {
                                    echo '<div class="flex justify-center"><span class="bg-gray-700 text-xs px-3 py-1 rounded-full text-gray-300 shadow-inner">' . ($message_date == date('Y-m-d') ? 'Hoy' : ($message_date == date('Y-m-d', strtotime('-1 day')) ? 'Ayer' : date('d/m/Y', strtotime($message['timestamp'])))) . '</span></div>';
                                    $current_date = $message_date;
                                }
                            ?>
                                <?php if ($message['sender_id'] == $current_user_id): ?>
                                    <div class="flex items-start space-x-2 max-w-[75%] md:max-w-md ml-auto justify-end">
                                        <div>
                                            <div class="bg-indigo-600 rounded-xl rounded-tr-none p-3 shadow-md text-white">
                                                <p><?php echo htmlspecialchars($message['message_content']); ?></p>
                                            </div>
                                            <span class="text-xs text-gray-400 mt-1 block text-right"><?php echo date('H:i A', strtotime($message['timestamp'])); ?></span>
                                        </div>
                                        <img src="<?php echo $current_user_avatar; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover flex-shrink-0 shadow-md">
                                    </div>
                                <?php else: ?>
                                    <div class="flex items-start space-x-2 max-w-[75%] md:max-w-md">
                                        <img src="<?php echo $selected_chat_user_avatar; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover flex-shrink-0 shadow-md">
                                        <div>
                                            <div class="bg-gray-700 rounded-xl rounded-tl-none p-3 shadow-md text-white">
                                                <p><?php echo htmlspecialchars($message['message_content']); ?></p>
                                            </div>
                                            <span class="text-xs text-gray-400 mt-1 block"><?php echo date('H:i A', strtotime($message['timestamp'])); ?></span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <div id="typing-indicator" class="flex items-start space-x-2 max-w-[75%] md:max-w-md hidden">
                                <img src="<?php echo $selected_chat_user_avatar; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover flex-shrink-0 shadow-md">
                                <div>
                                    <div class="bg-gray-700 rounded-xl rounded-tl-none p-3 shadow-md w-24 flex justify-center items-center">
                                        <div class="flex space-x-1">
                                            <div class="w-2 h-2 bg-gray-400 rounded-full dot"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full dot"></div>
                                            <div class="w-2 h-2 bg-gray-400 rounded-full dot"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-gray-400 p-4">¡Empieza una conversación con <?php echo $selected_chat_username; ?>!</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="flex flex-col items-center justify-center h-full text-gray-400 text-center px-4">
                        <i class="fas fa-comment-alt text-6xl text-indigo-500 mb-4"></i>
                        <h2 class="text-2xl font-bold mb-2">¡Bienvenido a RUMORA Chat!</h2>
                        <p class="text-lg">Selecciona un chat de la lista de la izquierda (o usa el botón de Amigos) para empezar a conversar.</p>
                        <p class="text-sm mt-2">O explora "Usuarios Cercanos" para encontrar nuevos amigos.</p>
                    </div>
                <?php endif; ?>
            </div>

            <div id="chat-input-area" class="border-t border-gray-700 p-4 flex-shrink-0 bg-gray-800 z-10 <?php echo $selected_chat_user_id ? 'flex' : 'hidden'; ?>">
                <div class="flex items-center space-x-3 flex-1">
                    <button class="p-3 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200 shadow-md">
                        <i class="fas fa-paperclip text-lg"></i>
                    </button>
                    <input type="text" placeholder="Escribe un mensaje..." class="message-input flex-1 bg-gray-700 rounded-full px-5 py-3 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-shadow duration-200 shadow-inner">
                    <button class="p-3 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white transition-colors duration-200 shadow-lg send-button">
                        <i class="fas fa-paper-plane text-lg"></i>
                    </button>
                </div>
            </div>
        </main>
    </div>

    <button id="fab-friends" class="fixed bottom-20 right-6 w-14 h-14 bg-green-500 rounded-full flex items-center justify-center text-white text-2xl shadow-lg hover:bg-green-600 transition-colors duration-200 z-40 <?php echo $selected_chat_user_id ? 'hidden' : ''; ?>">
        <i class="fas fa-user-plus"></i>
    </button>

    <div id="friends-modal" class="fixed inset-0 flex items-center justify-center modal-overlay hidden">
        <div class="bg-gray-800 rounded-lg shadow-xl w-11/12 md:w-1/3 h-3/4 flex flex-col p-6">
            <div class="flex justify-between items-center border-b border-gray-700 pb-4 mb-4">
                <h3 class="text-2xl font-bold text-white">Tus Amigos</h3>
                <button id="close-friends-modal" class="text-gray-400 hover:text-white text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto friends-modal-list space-y-3">
                <?php if (!empty($friends_list)): ?>
                    <?php foreach ($friends_list as $friend): ?>
                        <a href="?chat_with=<?php echo $friend['id']; ?>" class="flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-700 transition-colors duration-200 cursor-pointer">
                            <img src="<?php echo htmlspecialchars($friend['avatar']); ?>" alt="Avatar" class="w-10 h-10 rounded-full object-cover">
                            <div class="flex-1">
                                <h4 class="text-white font-semibold"><?php echo htmlspecialchars($friend['nombre_usuario']); ?></h4>
                                <p class="text-xs <?php echo ($friend['show_online_status'] && $friend['is_online']) ? 'text-green-400' : 'text-gray-400'; ?>">
                                    <?php echo ($friend['show_online_status'] && $friend['is_online']) ? 'En línea' : 'Últ. vez ' . date('H:i', strtotime($friend['last_seen'])); ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-gray-400 p-4">No tienes amigos todavía.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <nav class="fixed bottom-0 left-0 w-full bg-gray-800 border-t border-gray-700 md:hidden flex justify-around items-center h-16 z-50 shadow-2xl">
        <a href="dashboard.php" class="tab-btn <?php echo !$show_chat_view ? 'active text-indigo-400' : 'text-gray-400'; ?> flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
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
        <a href="ajustes.php" class="text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200">
            <i class="fas fa-cog text-xl"></i>
            <span class="text-xs mt-1">Ajustes</span>
        </a>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;
            const messagesDisplayArea = document.getElementById('messages-display-area'); // Changed from messagesContainer
            const messageInput = document.querySelector('.message-input');
            const sendButton = document.querySelector('.send-button');
            const chatOptionsBtn = document.getElementById('chat-options-btn');
            const chatOptionsMenu = document.getElementById('chat-options-menu');
            const typingIndicatorDiv = document.getElementById('typing-indicator');
            const userMenuBtn = document.getElementById('user-menu');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            const fabFriends = document.getElementById('fab-friends');
            const friendsModal = document.getElementById('friends-modal');
            const closeFriendsModal = document.getElementById('close-friends-modal');
            const backToChatsBtn = document.getElementById('back-to-chats-btn');

            const chatListContainer = document.querySelector('.chat-list-container');
            const chatViewContainer = document.querySelector('.chat-view-container');

            const selectedChatUserId = <?php echo json_encode($selected_chat_user_id); ?>;

            // Scroll to bottom of messages only if a chat is selected
            if (selectedChatUserId && messagesDisplayArea) {
                messagesDisplayArea.scrollTop = messagesDisplayArea.scrollHeight;
            }

            // User menu dropdown toggle
            if (userMenuBtn) {
                userMenuBtn.addEventListener('click', (event) => {
                    userMenuDropdown.classList.toggle('hidden');
                    event.stopPropagation(); // Prevent document click from closing it immediately
                });
            }

            // Chat options menu toggle (only if a chat is selected)
            if (chatOptionsBtn) {
                chatOptionsBtn.addEventListener('click', (event) => {
                    chatOptionsMenu.classList.toggle('hidden');
                    event.stopPropagation(); // Prevent document click from closing it immediately
                });
            }

            // Hide all dropdown menus if clicked outside
            document.addEventListener('click', (event) => {
                if (userMenuDropdown && !userMenuBtn.contains(event.target) && !userMenuDropdown.contains(event.target)) {
                    userMenuDropdown.classList.add('hidden');
                }
                if (chatOptionsMenu && chatOptionsBtn && !chatOptionsBtn.contains(event.target) && !chatOptionsMenu.contains(event.target)) {
                    chatOptionsMenu.classList.add('hidden');
                }
            });

            // Floating Action Button for Friends
            if (fabFriends) {
                fabFriends.addEventListener('click', () => {
                    friendsModal.classList.remove('hidden');
                });
            }

            if (closeFriendsModal) {
                closeFriendsModal.addEventListener('click', () => {
                    friendsModal.classList.add('hidden');
                });
            }

            // Close modal if clicked outside (on the overlay)
            if (friendsModal) {
                friendsModal.addEventListener('click', (event) => {
                    if (event.target === friendsModal) {
                        friendsModal.classList.add('hidden');
                    }
                });
            }

            // Back button for chat view (mobile and desktop)
            if (backToChatsBtn) {
                backToChatsBtn.addEventListener('click', () => {
                    // This reloads the page without the 'chat_with' parameter, effectively going back to the chat list view
                    window.location.href = 'dashboard.php';
                });
            }

            // Function to send messages
            const sendMessage = async () => {
                const messageContent = messageInput.value.trim();
                const receiverId = selectedChatUserId;

                if (messageContent === '' || !receiverId) {
                    return;
                }

                // Add message to display instantly
                const newMessageDiv = document.createElement('div');
                newMessageDiv.className = 'flex items-start space-x-2 max-w-[75%] md:max-w-md ml-auto justify-end';
                newMessageDiv.innerHTML = `
                    <div>
                        <div class="bg-indigo-600 rounded-xl rounded-tr-none p-3 shadow-md text-white">
                            <p>${messageContent}</p>
                        </div>
                        <span class="text-xs text-gray-400 mt-1 block text-right">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                    <img src="<?php echo $current_user_avatar; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover flex-shrink-0 shadow-md">
                `;
                messagesDisplayArea.appendChild(newMessageDiv);
                messagesDisplayArea.scrollTop = messagesDisplayArea.scrollHeight;
                messageInput.value = ''; // Clear input

                // Show typing indicator
                if (typingIndicatorDiv) {
                    typingIndicatorDiv.classList.remove('hidden');
                    messagesDisplayArea.scrollTop = messagesDisplayArea.scrollHeight;
                }

                try {
                    const response = await fetch('actions/send_message.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `receiver_id=${receiverId}&message_content=${encodeURIComponent(messageContent)}`
                    });
                    const data = await response.json();
                    if (data.success) {
                        console.log('Message sent successfully');
                        // Simulate a reply after a short delay
                        setTimeout(() => {
                            if (typingIndicatorDiv) {
                                typingIndicatorDiv.classList.add('hidden');
                            }
                            const replyMessageDiv = document.createElement('div');
                            replyMessageDiv.className = 'flex items-start space-x-2 max-w-[75%] md:max-w-md';
                            replyMessageDiv.innerHTML = `
                                <img src="<?php echo $selected_chat_user_avatar; ?>" alt="Avatar" class="w-8 h-8 rounded-full object-cover flex-shrink-0 shadow-md">
                                <div>
                                    <div class="bg-gray-700 rounded-xl rounded-tl-none p-3 shadow-md text-white">
                                        <p></p>
                                    </div>
                                    <span class="text-xs text-gray-400 mt-1 block">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                </div>
                            `;
                            messagesDisplayArea.appendChild(replyMessageDiv);
                            messagesDisplayArea.scrollTop = messagesDisplayArea.scrollHeight;
                        }, 1500);
                    } else {
                        console.error('Failed to send message:', data.message);
                         if (typingIndicatorDiv) {
                            typingIndicatorDiv.classList.add('hidden');
                        }
                        alert('Error al enviar mensaje: ' + data.message);
                    }
                } catch (error) {
                    console.error('Error sending message:', error);
                     if (typingIndicatorDiv) {
                        typingIndicatorDiv.classList.add('hidden');
                    }
                    alert('Error de conexión al enviar mensaje.');
                }
            };

            // Only attach event listeners if a chat is selected
            if (selectedChatUserId) {
                if (sendButton) {
                    sendButton.addEventListener('click', sendMessage);
                }
                if (messageInput) {
                    messageInput.addEventListener('keypress', (e) => {
                        if (e.key === 'Enter') {
                            sendMessage();
                        }
                    });
                }
            }

            // Functions for chat options
            window.blockUser = async (userId) => {
                if (confirm('¿Estás seguro de que quieres bloquear a este usuario? No podrás enviarle ni recibir mensajes de él.')) {
                    try {
                        const response = await fetch('actions/block_user.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `blocked_id=${userId}`
                        });
                        const data = await response.json();
                        if (data.success) {
                            alert('Usuario bloqueado exitosamente.');
                            window.location.href = 'dashboard.php'; // Redirect to clear chat view
                        } else {
                            alert('Error al bloquear usuario: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error blocking user:', error);
                        alert('Error de conexión al bloquear usuario.');
                    }
                }
            };

            window.deleteChat = async (userId) => {
                if (confirm('¿Estás seguro de que quieres eliminar este chat? Se borrarán todos los mensajes.')) {
                    try {
                        const response = await fetch('actions/delete_chat.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `chat_with_id=${userId}`
                        });
                        const data = await response.json();
                        if (data.success) {
                            alert('Chat eliminado exitosamente.');
                            window.location.href = 'dashboard.php'; // Redirect to clear chat view
                        } else {
                            alert('Error al eliminar chat: ' + data.message);
                        }
                    } catch (error) {
                        console.error('Error deleting chat:', error);
                        alert('Error de conexión al eliminar chat.');
                    }
                }
            };
        });
    </script>
</body>
</html>