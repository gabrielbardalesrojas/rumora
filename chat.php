<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegant Chat System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        /* Custom scrollbar for better aesthetics */
        .sidebar-scroll, .messages-scroll {
            scrollbar-width: thin;
            scrollbar-color: #4b5563 #1f2937; /* thumb color track color */
        }
        .sidebar-scroll::-webkit-scrollbar, .messages-scroll::-webkit-scrollbar {
            width: 8px; /* For vertical scrollbars */
            height: 8px; /* For horizontal scrollbars */
        }
        .sidebar-scroll::-webkit-scrollbar-track, .messages-scroll::-webkit-scrollbar-track {
            background: #1f2937; /* Track color */
            border-radius: 10px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb, .messages-scroll::-webkit-scrollbar-thumb {
            background-color: #4b5563; /* Thumb color */
            border-radius: 10px;
            border: 2px solid #1f2937; /* Border around thumb */
        }
        .tab-content {
            display: none;
            /* Optional: Add smooth transition for content visibility */
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }
        .tab-content.active {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }
        .confession-card {
            transition: all 0.3s ease;
        }
        .confession-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.2);
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
        /* Body padding to prevent content from being hidden behind fixed elements */
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
    </style>
</head>
<body class="bg-gray-900 text-gray-100 antialiased">
    <!-- Header -->
    <header class="bg-gray-800 shadow-xl py-4 px-6 flex justify-between items-center fixed top-0 left-0 w-full z-50">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center shadow-md">
                <i class="fas fa-comment-dots text-xl text-white"></i>
            </div>
            <h1 class="text-xl font-bold text-white">Elegant Chat</h1>
        </div>
        <div class="flex items-center space-x-4">
            <button id="notifications" class="relative p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                <i class="fas fa-bell text-lg"></i>
                <span class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full animate-pulse"></span>
            </button>
            <button id="user-menu" class="flex items-center space-x-2 focus:outline-none p-2 rounded-lg hover:bg-gray-700 transition-colors duration-200">
                <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center shadow-inner text-white">
                    <i class="fas fa-user"></i>
                </div>
                <span class="hidden md:inline text-white font-medium">Usuario</span>
                <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:inline"></i>
            </button>
        </div>
    </header>

    <!-- Main Application Container -->
    <div class="flex chat-container-height relative">
        <!-- Desktop Sidebar (hidden on mobile) -->
        <aside class="hidden md:flex w-64 bg-gray-800 flex-col border-r border-gray-700 shadow-xl z-10">
            <!-- Desktop Tab Navigation -->
            <nav class="flex flex-col border-b border-gray-700 p-2">
                <button class="tab-btn active p-4 text-indigo-400 hover:bg-indigo-700/30 rounded-lg w-full flex items-center space-x-4 transition-all duration-200" data-tab="chats">
                    <i class="fas fa-comments text-xl"></i>
                    <span class="font-medium">Chats</span>
                </button>
                <button class="tab-btn p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200" data-tab="contacts">
                    <i class="fas fa-address-book text-xl"></i>
                    <span class="font-medium">Contactos</span>
                </button>
                <button class="tab-btn p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200" data-tab="confessions">
                    <i class="fas fa-heart text-xl"></i>
                    <span class="font-medium">Confesiones</span>
                </button>
                <button class="tab-btn p-4 text-gray-400 hover:bg-gray-700/50 rounded-lg w-full flex items-center space-x-4 transition-all duration-200" data-tab="settings">
                    <i class="fas fa-cog text-xl"></i>
                    <span class="font-medium">Ajustes</span>
                </button>
            </nav>
            <!-- Could add more fixed sidebar elements here if needed -->
            <div class="flex-1 overflow-y-auto sidebar-scroll">
                <!-- Additional content for sidebar can go here, e.g., favorites, groups -->
            </div>
        </aside>

        <!-- Main Content Area (displays selected tab content) -->
        <main class="flex-1 flex flex-col bg-gray-800 shadow-inner rounded-l-xl md:rounded-none overflow-hidden">
            <!-- Dynamic Chat Header (visible only for Chats tab) -->
            <div id="chat-header" class="border-b border-gray-700 p-4 flex items-center justify-between flex-shrink-0 bg-gray-800 z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center shadow-md">
                        <i class="fas fa-user text-white"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-white">Juan Pérez</h3>
                        <p class="text-xs text-gray-400">En línea</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                        <i class="fas fa-phone text-lg"></i>
                    </button>
                    <button class="p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                        <i class="fas fa-video text-lg"></i>
                    </button>
                    <button class="p-2 rounded-full text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200">
                        <i class="fas fa-ellipsis-v text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Tab Content Display Area -->
            <div id="main-content-display" class="flex-1 overflow-y-auto messages-scroll p-4 relative">
                <!-- Chats Tab Content -->
                <div id="chats" class="tab-content active h-full flex flex-col">
                    <div class="flex-1 overflow-y-auto messages-scroll space-y-4 pb-4">
                        <!-- Date separator -->
                        <div class="flex justify-center">
                            <span class="bg-gray-700 text-xs px-3 py-1 rounded-full text-gray-300 shadow-inner">Hoy</span>
                        </div>
                        
                        <!-- Received message -->
                        <div class="flex items-start space-x-2 max-w-[75%] md:max-w-md">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex-shrink-0 flex items-center justify-center shadow-md">
                                <i class="fas fa-user text-xs text-white"></i>
                            </div>
                            <div>
                                <div class="bg-gray-700 rounded-xl rounded-tl-none p-3 shadow-md text-white">
                                    <p>¡Hola! ¿Cómo estás?</p>
                                </div>
                                <span class="text-xs text-gray-400 mt-1 block">12:30 PM</span>
                            </div>
                        </div>
                        
                        <!-- Sent message -->
                        <div class="flex items-start space-x-2 max-w-[75%] md:max-w-md ml-auto justify-end">
                            <div>
                                <div class="bg-indigo-600 rounded-xl rounded-tr-none p-3 shadow-md text-white">
                                    <p>¡Hola Juan! Estoy bien, gracias por preguntar. ¿Y tú?</p>
                                </div>
                                <span class="text-xs text-gray-400 mt-1 block text-right">12:32 PM</span>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-indigo-500 flex-shrink-0 flex items-center justify-center shadow-md">
                                <i class="fas fa-user text-xs text-white"></i>
                            </div>
                        </div>
                        
                        <!-- Received message -->
                        <div class="flex items-start space-x-2 max-w-[75%] md:max-w-md">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex-shrink-0 flex items-center justify-center shadow-md">
                                <i class="fas fa-user text-xs text-white"></i>
                            </div>
                            <div>
                                <div class="bg-gray-700 rounded-xl rounded-tl-none p-3 shadow-md text-white">
                                    <p>¡También estoy bien! Oye, ¿vamos al cine este fin de semana?</p>
                                </div>
                                <span class="text-xs text-gray-400 mt-1 block">12:34 PM</span>
                            </div>
                        </div>
                        
                        <!-- Sent message -->
                        <div class="flex items-start space-x-2 max-w-[75%] md:max-w-md ml-auto justify-end">
                            <div>
                                <div class="bg-indigo-600 rounded-xl rounded-tr-none p-3 shadow-md text-white">
                                    <p>¡Claro que sí! ¿Qué película quieres ver?</p>
                                </div>
                                <span class="text-xs text-gray-400 mt-1 block text-right">12:35 PM</span>
                            </div>
                            <div class="w-8 h-8 rounded-full bg-indigo-500 flex-shrink-0 flex items-center justify-center shadow-md">
                                <i class="fas fa-user text-xs text-white"></i>
                            </div>
                        </div>
                        
                        <!-- Typing indicator -->
                        <div id="typing-indicator" class="flex items-start space-x-2 max-w-[75%] md:max-w-md">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex-shrink-0 flex items-center justify-center shadow-md">
                                <i class="fas fa-user text-xs text-white"></i>
                            </div>
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
                    </div>
                </div>

                <!-- Contacts Tab Content -->
                <div id="contacts" class="tab-content p-4 h-full overflow-y-auto">
                    <div class="px-2 mb-6">
                        <div class="relative">
                            <input type="text" placeholder="Buscar contacto..." class="w-full bg-gray-700 rounded-xl px-4 py-3 pl-12 text-sm text-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-inner">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="group px-4 py-3 rounded-xl flex items-center space-x-4 cursor-pointer bg-gray-700 hover:bg-indigo-700/40 transition-colors duration-200 shadow-md">
                            <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white text-lg shadow-inner">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-white">Juan Pérez</h3>
                                <p class="text-xs text-gray-400 group-hover:text-gray-200">En línea</p>
                            </div>
                        </div>
                        
                        <div class="group px-4 py-3 rounded-xl flex items-center space-x-4 cursor-pointer bg-gray-700 hover:bg-indigo-700/40 transition-colors duration-200 shadow-md">
                            <div class="w-12 h-12 rounded-full bg-pink-500 flex items-center justify-center text-white text-lg shadow-inner">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-white">María García</h3>
                                <p class="text-xs text-gray-400 group-hover:text-gray-200">Últ. vez hoy 15:30</p>
                            </div>
                        </div>
                        
                        <div class="group px-4 py-3 rounded-xl flex items-center space-x-4 cursor-pointer bg-gray-700 hover:bg-indigo-700/40 transition-colors duration-200 shadow-md">
                            <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white text-lg shadow-inner">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-white">Carlos López</h3>
                                <p class="text-xs text-gray-400 group-hover:text-gray-200">En línea</p>
                            </div>
                        </div>
                        
                        <div class="group px-4 py-3 rounded-xl flex items-center space-x-4 cursor-pointer bg-gray-700 hover:bg-indigo-700/40 transition-colors duration-200 shadow-md">
                            <div class="w-12 h-12 rounded-full bg-purple-500 flex items-center justify-center text-white text-lg shadow-inner">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-white">Ana Fernández</h3>
                                <p class="text-xs text-gray-400 group-hover:text-gray-200">En línea</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Confessions Tab Content -->
                <div id="confessions" class="tab-content p-4 h-full overflow-y-auto">
                    <div class="mb-6">
                        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 px-4 rounded-xl flex items-center justify-center space-x-2 transition-colors duration-200 shadow-lg">
                            <i class="fas fa-plus text-lg"></i>
                            <span class="font-semibold">Nueva confesión</span>
                        </button>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="confession-card bg-gray-700 rounded-xl p-4 cursor-pointer shadow-md border border-gray-600">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-indigo-400 font-medium">Anónimo</span>
                                <span class="text-xs text-gray-400">Hoy 12:30 PM</span>
                            </div>
                            <p class="text-base text-gray-200 mb-3">Me gusta alguien del grupo pero no me atrevo a decirlo...</p>
                            <div class="flex justify-end space-x-3">
                                <button class="text-gray-400 hover:text-indigo-400 text-lg transition-colors duration-200">
                                    <i class="far fa-heart"></i>
                                    <span class="ml-1 text-sm">24</span>
                                </button>
                                <button class="text-gray-400 hover:text-indigo-400 text-lg transition-colors duration-200">
                                    <i class="far fa-comment"></i>
                                    <span class="ml-1 text-sm">8</span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="confession-card bg-gray-700 rounded-xl p-4 cursor-pointer shadow-md border border-gray-600">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-indigo-400 font-medium">Anónimo</span>
                                <span class="text-xs text-gray-400">Ayer 20:15 PM</span>
                            </div>
                            <p class="text-base text-gray-200 mb-3">Extraño aquellos días cuando éramos más unidos y salíamos a todos lados.</p>
                            <div class="flex justify-end space-x-3">
                                <button class="text-red-400 text-lg transition-colors duration-200">
                                    <i class="fas fa-heart"></i>
                                    <span class="ml-1 text-sm">56</span>
                                </button>
                                <button class="text-gray-400 hover:text-indigo-400 text-lg transition-colors duration-200">
                                    <i class="far fa-comment"></i>
                                    <span class="ml-1 text-sm">15</span>
                                </button>
                            </div>
                        </div>

                        <div class="confession-card bg-gray-700 rounded-xl p-4 cursor-pointer shadow-md border border-gray-600">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm text-indigo-400 font-medium">Anónimo</span>
                                <span class="text-xs text-gray-400">Hace 3 días</span>
                            </div>
                            <p class="text-base text-gray-200 mb-3">A veces desearía poder volver al pasado y cambiar algunas decisiones que tomé.</p>
                            <div class="flex justify-end space-x-3">
                                <button class="text-gray-400 hover:text-indigo-400 text-lg transition-colors duration-200">
                                    <i class="far fa-heart"></i>
                                    <span class="ml-1 text-sm">18</span>
                                </button>
                                <button class="text-gray-400 hover:text-indigo-400 text-lg transition-colors duration-200">
                                    <i class="far fa-comment"></i>
                                    <span class="ml-1 text-sm">5</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Tab Content -->
                <div id="settings" class="tab-content p-4 h-full overflow-y-auto">
                    <div class="mb-8 flex flex-col items-center">
                        <div class="w-24 h-24 rounded-full bg-indigo-600 flex items-center justify-center mb-4 shadow-xl">
                            <i class="fas fa-user text-4xl text-white"></i>
                        </div>
                        <h3 class="font-bold text-xl text-white">Usuario</h3>
                        <p class="text-sm text-gray-400">usuario@ejemplo.com</p>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="setting-group bg-gray-700 rounded-xl p-4 shadow-md">
                            <h4 class="font-semibold mb-4 flex items-center text-white text-lg">
                                <i class="fas fa-user-edit mr-3 text-indigo-400 text-xl"></i>
                                Perfil
                            </h4>
                            <div class="space-y-4 pl-8">
                                <div>
                                    <label class="block text-sm text-gray-400 mb-2">Nombre</label>
                                    <input type="text" value="Usuario" class="w-full bg-gray-800 rounded-lg px-4 py-3 text-white text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-inner">
                                </div>
                                <div>
                                    <label class="block text-sm text-gray-400 mb-2">Estado</label>
                                    <input type="text" value="Disponible" class="w-full bg-gray-800 rounded-lg px-4 py-3 text-white text-base focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-inner">
                                </div>
                            </div>
                        </div>
                        
                        <div class="setting-group bg-gray-700 rounded-xl p-4 shadow-md">
                            <h4 class="font-semibold mb-4 flex items-center text-white text-lg">
                                <i class="fas fa-lock mr-3 text-indigo-400 text-xl"></i>
                                Privacidad
                            </h4>
                            <div class="space-y-4 pl-8">
                                <div class="flex items-center justify-between">
                                    <span class="text-base text-gray-200">Mostrar estado en línea</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-12 h-7 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600 shadow-inner"></div>
                                    </label>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-base text-gray-200">Permitir mensajes de desconocidos</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer">
                                        <div class="w-12 h-7 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600 shadow-inner"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="setting-group bg-gray-700 rounded-xl p-4 shadow-md">
                            <h4 class="font-semibold mb-4 flex items-center text-white text-lg">
                                <i class="fas fa-bell mr-3 text-indigo-400 text-xl"></i>
                                Notificaciones
                            </h4>
                            <div class="space-y-4 pl-8">
                                <div class="flex items-center justify-between">
                                    <span class="text-base text-gray-200">Sonido de notificaciones</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" class="sr-only peer" checked>
                                        <div class="w-12 h-7 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-indigo-600 shadow-inner"></div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button class="w-full bg-red-600 hover:bg-red-700 text-white py-3 px-4 rounded-xl flex items-center justify-center space-x-3 transition-colors duration-200 mt-6 shadow-lg">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                            <span class="font-semibold">Cerrar sesión</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Message Input (visible only for Chats tab) -->
            <div id="chat-input-area" class="border-t border-gray-700 p-4 flex-shrink-0 bg-gray-800 z-10">
                <div class="flex items-center space-x-3">
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

    <!-- Mobile Bottom Navigation (hidden on desktop) -->
    <nav class="fixed bottom-0 left-0 w-full bg-gray-800 border-t border-gray-700 md:hidden flex justify-around items-center h-16 z-50 shadow-2xl">
        <button class="tab-btn active text-indigo-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200" data-tab="chats">
            <i class="fas fa-comments text-xl"></i>
            <span class="text-xs mt-1">Chats</span>
        </button>
        <button class="tab-btn text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200" data-tab="contacts">
            <i class="fas fa-address-book text-xl"></i>
            <span class="text-xs mt-1">Contactos</span>
        </button>
        <button class="tab-btn text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200" data-tab="confessions">
            <i class="fas fa-heart text-xl"></i>
            <span class="text-xs mt-1">Confesiones</span>
        </button>
        <button class="tab-btn text-gray-400 flex flex-col items-center justify-center p-2 rounded-lg transition-colors duration-200" data-tab="settings">
            <i class="fas fa-cog text-xl"></i>
            <span class="text-xs mt-1">Ajustes</span>
        </button>
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabButtons = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            const chatHeader = document.getElementById('chat-header');
            const chatInputArea = document.getElementById('chat-input-area');
            const messagesContainer = document.querySelector('#chats .messages-scroll'); // Specific to the chat tab's scroll area

            // Function to show/hide sections based on active tab
            const updateLayoutForTab = (activeTabId) => {
                if (activeTabId === 'chats') {
                    chatHeader.style.display = 'flex'; // Show chat header
                    chatInputArea.style.display = 'flex'; // Show message input
                    messagesContainer.style.overflowY = 'auto'; // Ensure messages scroll
                    messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scroll to bottom when chat is active
                } else {
                    chatHeader.style.display = 'none'; // Hide chat header
                    chatInputArea.style.display = 'none'; // Hide message input
                    // For other tabs, ensure their scroll is managed by their own div or parent
                    tabContents.forEach(content => {
                        if (content.id === activeTabId) {
                            content.style.overflowY = 'auto'; // Make sure the active content can scroll if needed
                        } else {
                            content.style.overflowY = 'hidden'; // Hide scroll for inactive content
                        }
                    });
                }
            };

            // Tab switching functionality
            tabButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(b => {
                        b.classList.remove('active', 'text-indigo-400');
                        b.classList.add('text-gray-400');
                        // Remove specific desktop hover effect from previously active desktop button
                        if (!b.classList.contains('md:hidden')) { // Check if it's a desktop button
                            b.classList.remove('bg-indigo-700/30');
                            b.classList.add('hover:bg-gray-700/50');
                        }
                    });
                    
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked button
                    btn.classList.add('active', 'text-indigo-400');
                    btn.classList.remove('text-gray-400');
                    // Add specific desktop hover effect for the newly active desktop button
                    if (!btn.classList.contains('md:hidden')) { // Check if it's a desktop button
                        btn.classList.add('bg-indigo-700/30');
                        btn.classList.remove('hover:bg-gray-700/50');
                    }

                    // Show corresponding content
                    const tabId = btn.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');

                    // Update layout based on active tab
                    updateLayoutForTab(tabId);
                });
            });

            // Simulate typing indicator
            const typingIndicatorDiv = document.getElementById('typing-indicator');
            setTimeout(() => {
                if (typingIndicatorDiv) {
                    // Replace typing indicator with a message
                    typingIndicatorDiv.innerHTML = `
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex-shrink-0 flex items-center justify-center shadow-md">
                            <i class="fas fa-user text-xs text-white"></i>
                        </div>
                        <div>
                            <div class="bg-gray-700 rounded-xl rounded-tl-none p-3 shadow-md text-white">
                                <p>Estaba pensando en ver la nueva de Marvel...</p>
                            </div>
                            <span class="text-xs text-gray-400 mt-1 block">12:36 PM</span>
                        </div>
                    `;
                    messagesContainer.scrollTop = messagesContainer.scrollHeight; // Scroll to bottom
                }
            }, 2000);

            // Message sending functionality
            const messageInput = document.querySelector('.message-input');
            const sendButton = document.querySelector('.send-button');

            const sendMessage = () => {
                if (messageInput.value.trim() !== '') {
                    // Create new message element
                    const newMessage = document.createElement('div');
                    newMessage.className = 'flex items-start space-x-2 max-w-[75%] md:max-w-md ml-auto justify-end';
                    newMessage.innerHTML = `
                        <div>
                            <div class="bg-indigo-600 rounded-xl rounded-tr-none p-3 shadow-md text-white">
                                <p>${messageInput.value}</p>
                            </div>
                            <span class="text-xs text-gray-400 mt-1 block text-right">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-indigo-500 flex-shrink-0 flex items-center justify-center shadow-md">
                            <i class="fas fa-user text-xs text-white"></i>
                        </div>
                    `;
                    
                    // Append to messages container
                    messagesContainer.appendChild(newMessage);
                    
                    // Clear input
                    messageInput.value = '';
                    
                    // Scroll to bottom
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    
                    // Simulate typing indicator before reply
                    const newTypingIndicator = document.createElement('div');
                    newTypingIndicator.id = 'typing-indicator-reply'; // Unique ID
                    newTypingIndicator.className = 'flex items-start space-x-2 max-w-[75%] md:max-w-md';
                    newTypingIndicator.innerHTML = `
                        <div class="w-8 h-8 rounded-full bg-blue-500 flex-shrink-0 flex items-center justify-center shadow-md">
                            <i class="fas fa-user text-xs text-white"></i>
                        </div>
                        <div>
                            <div class="bg-gray-700 rounded-xl rounded-tl-none p-3 shadow-md w-24 flex justify-center items-center">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-gray-400 rounded-full dot"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full dot"></div>
                                    <div class="w-2 h-2 bg-gray-400 rounded-full dot"></div>
                                </div>
                            </div>
                        </div>
                    `;
                    messagesContainer.appendChild(newTypingIndicator);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;

                    // Simulate reply after 1 second
                    setTimeout(() => {
                        const replyMessageDiv = document.createElement('div');
                        replyMessageDiv.className = 'flex items-start space-x-2 max-w-[75%] md:max-w-md';
                        replyMessageDiv.innerHTML = `
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex-shrink-0 flex items-center justify-center shadow-md">
                                <i class="fas fa-user text-xs text-white"></i>
                            </div>
                            <div>
                                <div class="bg-gray-700 rounded-xl rounded-tl-none p-3 shadow-md text-white">
                                    <p>Suena bien! Vamos el sábado por la tarde.</p>
                                </div>
                                <span class="text-xs text-gray-400 mt-1 block">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                            </div>
                        `;
                        // Remove typing indicator before adding reply
                        if (newTypingIndicator) {
                            newTypingIndicator.remove();
                        }
                        messagesContainer.appendChild(replyMessageDiv);
                        messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    }, 1000);
                }
            };

            sendButton.addEventListener('click', sendMessage);

            // Allow sending message with Enter key
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });

            // Initialize layout for the default active tab (chats)
            updateLayoutForTab('chats');
        });
    </script>
</body>
</html>