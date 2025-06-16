<?php
session_start(); // Inicia la sesión PHP para manejar el estado del usuario

// Incluye el archivo de configuración de la base de datos
require_once 'config/database.php';

// Define la ruta del dashboard para la redirección de usuarios regulares
$dashboard_path = 'views/usuario/dashboard.php';
// Define la ruta del dashboard de admin para la redirección de administradores
$admin_dashboard_path = 'views/admin/dashboard_admin.php'; // ASEGÚRATE DE QUE ESTA RUTA EXISTA

// Incluye los controladores de registro e inicio de sesión
require_once 'controller/RegisterController.php';
require_once 'controller/LoginController.php';
require_once 'controller/AdminLoginController.php'; // Incluye el nuevo controlador de admin

// Mostrar mensajes de sesión (si existen)
$message = '';
$message_type = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']); // Eliminar el mensaje después de mostrarlo
    unset($_SESSION['message_type']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title data-lang-key="title">Bienvenido a RUMORA - Tu Comunidad Única</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* Establece la fuente 'Inter' para todo el cuerpo del documento */
        body {
            font-family: 'Inter', sans-serif;
            overflow-x: hidden; /* Evita el desplazamiento horizontal */
            background-color: #0d1117; /* Un fondo ligeramente más oscuro para contrastar con los elementos brillantes */
            color: #e0e6f0; /* Default text color for dark mode */
            transition: background-color 0.3s ease, color 0.3s ease; /* Transición para el modo oscuro/claro */
        }
        /* Estilos para el modo claro */
        body.light-mode {
            background-color: #f0f2f5 !important; /* Fondo más claro */
            color: #333 !important; /* Texto oscuro */
        }
        body.light-mode .bg-gray-800 { background-color: #ffffff !important; } /* Header, auth-section */
        body.light-mode .bg-gray-900 { background-color: #e2e8f0 !important; } /* Features section, footer */

        body.light-mode .text-gray-100 { color: #333 !important; }
        body.light-mode .text-gray-300 { color: #555 !important; }
        body.light-mode .text-gray-400 { color: #777 !important; }

        body.light-mode .feature-card-rumora {
            background-color: #ffffff !important;
            border-color: #e0e0e0 !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08) !important;
        }

        body.light-mode .hero-rumora-gradient {
            background: linear-gradient(135deg, #FFDDC1 0%, #FFEFD5 100%) !important;
            color: #333 !important;
        }
        body.light-mode .hero-rumora-gradient::before {
            background: radial-gradient(circle at top right, rgba(0,0,0,0.05) 0%, transparent 70%) !important;
        }

        body.light-mode .btn-secondary { border-color: #333 !important; color: #333 !important; }
        body.light-mode .btn-secondary:hover { background-color: #333 !important; color: #fff !important; }

        body.light-mode .input-rumora,
        body.light-mode .select-rumora {
            background-color: #e2e8f0 !important;
            border-color: #cbd5e1 !important;
            color: #333 !important;
        }

        body.light-mode .login-card, /* This class is not directly on modal-content, but applied to its inner forms */
        body.light-mode .register-card, /* Same here */
        body.light-mode .modal-content {
            background-color: #ffffff !important;
            border-color: #e0e0e0 !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08) !important;
        }

        body.light-mode .close-modal { color: #666 !important; }
        body.light-mode .close-modal:hover { color: #333 !important; }

        body.light-mode .floating-btn {
            background-color: #ffffff !important;
            color: #333 !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
            border-color: #e0e0e0 !important;
        }
        body.light-mode .floating-btn:hover {
            background-color: #e0e0e0 !important;
            color: #000 !important;
        }

        /* Ensure links within modals also change color */
        body.light-mode .text-orange-400 { color: #f97316 !important; } /* Revert to specific orange for links */
        body.light-mode .text-red-400 { color: #ef4444 !important; } /* Revert to specific red for links */
        body.light-mode .text-orange-400:hover { color: #ea580c !important; }
        body.light-mode .text-red-400:hover { color: #dc2626 !important; }
        body.light-mode .text-blue-600 { color: #2563eb !important; } /* For admin modal */
        body.light-mode .bg-blue-600 { background-color: #2563eb !important; }
        body.light-mode .bg-blue-700:hover { background-color: #1d4ed8 !important; }


        /* General Button Styles */
        .btn-rumora {
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55), box-shadow 0.3s ease-in-out, background-color 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
            position: relative; /* Needed for icon animation */
            overflow: hidden; /* For any potential overlay effects */
        }
        .btn-rumora:hover {
            transform: translateY(-8px); /* Más movimiento hacia arriba */
            box-shadow: 0 15px 30px rgba(0,0,0,0.3), 0 6px 12px rgba(0,0,0,0.15); /* Sombra más pronunciada */
        }
        .btn-rumora:active {
            transform: translateY(-3px); /* Menos movimiento al hacer clic */
            box-shadow: 0 7px 14px rgba(0,0,0,0.2), 0 3px 6px rgba(0,0,0,0.1);
        }

        /* Icon animation within buttons */
        .btn-rumora i {
            transition: transform 0.3s ease-in-out, color 0.3s ease;
        }
        .btn-rumora:hover i {
            transform: rotate(5deg) scale(1.1); /* Ligera rotación y escala al pasar el ratón */
        }
        .btn-rumora.btn-secondary:hover i {
            color: #FF6F61; /* Cambia el color del icono en el botón secundario */
        }

        /* Animación de pulso sutil para el botón principal */
        @keyframes pulse-subtle {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 4px 6px rgba(0,0,0,0.1), 0 1px 3px rgba(0,0,0,0.08);
            }
            50% {
                transform: scale(1.02);
                box-shadow: 0 8px 16px rgba(0,0,0,0.2), 0 2px 4px rgba(0,0,0,0.1);
            }
        }
        .animate-pulse-subtle {
            animation: pulse-subtle 2s infinite ease-in-out;
        }


        /* Estilo para las tarjetas de características (más redondeadas, sombras sutiles) */
        .feature-card-rumora {
            background-color: #1f2937; /* Fondo de tarjeta más oscuro para un look premium */
            border-radius: 1.5rem; /* Esquinas más redondeadas */
            box-shadow: 0 10px 25px rgba(0,0,0,0.2), 0 4px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .feature-card-rumora:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3), 0 6px 12px rgba(0,0,0,0.15);
        }

        /* Iconos de características con círculos coloridos */
        .feature-icon-circle {
            width: 4.5rem; /* Más grande */
            height: 4.5rem; /* Más grande */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem auto; /* Centrar y espaciar */
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2), 0 2px 6px rgba(0,0,0,0.15);
        }
        /* Colores específicos para los círculos de iconos (más vibrantes) */
        .icon-bg-red-vibrant { background-color: #EF4444; } /* Rojo vibrante */
        .icon-bg-orange-vibrant { background-color: #F97316; } /* Naranja vibrante */
        .icon-bg-fuchsia-vibrant { background-color: #D946EF; } /* Fucsia vibrante */
        .icon-bg-cyan-vibrant { background-color: #06B6D4; } /* Cian vibrante */
        .icon-bg-lime-vibrant { background-color: #84CC16; } /* Lima vibrante */
        .icon-bg-purple-vibrant { background-color: #A855F7; } /* Púrpura vibrante */


        /* Animaciones para elementos del hero section */
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-fade-in-scale {
            animation: fadeInScale 0.7s ease-out forwards;
        }
        .delay-100 { animation-delay: 0.1s; }
        .delay-200 { animation-delay: 0.2s; }
        .delay-300 { animation-delay: 0.3s; }
        .delay-400 { animation-delay: 0.4s; }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.75); /* Fondo oscuro translúcido */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; /* Asegura que esté por encima de todo */
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        .modal-overlay.open {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background-color: #1f2937; /* Fondo de la tarjeta de login/register */
            padding: 2rem; /* Reduced padding */
            border-radius: 1.5rem;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5), 0 6px 12px rgba(0, 0, 0, 0.25);
            max-width: 90%;
            width: 450px; /* Adjusted width for a slightly smaller feel */
            max-height: 90vh; /* Ensure modal doesn't exceed viewport height */
            overflow-y: auto; /* Enable scrolling if content overflows */
            transform: translateY(-20px) scale(0.95);
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55); /* Efecto bouncy */
            position: relative;
            border: 1px solid #2f3b4d;
        }
        @media (max-width: 640px) { /* Small screens */
            .modal-content {
                width: 95%;
                padding: 1.5rem; /* Even smaller padding on very small screens */
            }
        }
        .modal-overlay.open .modal-content {
            transform: translateY(0) scale(1);
        }
        .input-rumora, .select-rumora {
            background-color: #2a3447;
            border: 1px solid #3d4c62;
            color: #e0e6f0;
            padding: 0.75rem 1.25rem;
            border-radius: 0.75rem;
            transition: all 0.2s ease-in-out;
        }
        .input-rumora:focus, .select-rumora:focus {
            outline: none;
            border-color: #FF6F61; /* Color de acento de RUMORA */
            box-shadow: 0 0 0 3px rgba(255, 111, 97, 0.3);
        }
        /* Botones flotantes para modo y idioma */
        .floating-controls {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999; /* Just below modals */
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .floating-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #1f2937;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.25rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid #2f3b4d;
        }
        .floating-btn:hover {
            transform: scale(1.1);
            background-color: #3b82f6; /* Azul para resaltar */
        }
        body.light-mode .floating-btn {
            background-color: #ffffff !important;
            color: #333 !important;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1) !important;
            border-color: #e0e0e0 !important;
        }
        body.light-mode .floating-btn:hover {
            background-color: #e0e0e0 !important;
            color: #000 !important;
        }
    </style>
</head>
<body class="text-gray-100 antialiased">

    <?php if ($message): ?>
    <div id="session-message" class="fixed top-20 left-1/2 -translate-x-1/2 p-4 rounded-lg shadow-lg z-[1001] text-center
        <?php echo $message_type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
    <script>
        // Ocultar el mensaje después de unos segundos
        setTimeout(() => {
            const msg = document.getElementById('session-message');
            if (msg) {
                msg.style.opacity = '0';
                msg.style.transition = 'opacity 0.5s ease-out';
                setTimeout(() => msg.remove(), 500);
            }
        }, 5000); // 5 segundos
    </script>
    <?php endif; ?>

    <header class="bg-gray-800 py-5 shadow-lg">
        <div class="container mx-auto px-6 flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-indigo-600 flex items-center justify-center shadow-md">
                    <i class="fas fa-comment-dots text-2xl text-white"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-white" data-lang-key="appName">RUMORA</h1>
            </div>
            <nav class="hidden md:flex space-x-6">
                <a href="#" class="text-gray-300 hover:text-orange-400 font-medium transition-colors duration-200" data-lang-key="homeLink">Inicio</a>
                <a href="#features" class="text-gray-300 hover:text-orange-400 font-medium transition-colors duration-200" data-lang-key="featuresLink">Características</a>
                <a href="#auth-section" class="py-2 px-5 bg-orange-500 text-white rounded-full font-semibold hover:bg-orange-600 transition-colors duration-200 shadow-md btn-rumora" data-modal-target="login-modal" data-lang-key="accessBtn">Acceder</a>
            </nav>
            <button id="mobile-menu-button" class="md:hidden text-gray-300 hover:text-white focus:outline-none">
                <i class="fas fa-bars text-2xl"></i>
            </button>
        </div>
    </header>

    <div id="mobile-menu" class="hidden md:hidden bg-gray-800 py-4 shadow-xl">
        <div class="flex flex-col items-center space-y-4 px-6">
            <a href="#" class="text-gray-300 hover:text-orange-400 font-medium transition-colors duration-200 w-full text-center py-2" data-lang-key="homeLink">Inicio</a>
            <a href="#features" class="text-gray-300 hover:text-orange-400 font-medium transition-colors duration-200 w-full text-center py-2" data-lang-key="featuresLink">Características</a>
            <a href="#" class="w-full py-2 px-5 bg-orange-500 text-white rounded-full font-semibold hover:bg-orange-600 transition-colors duration-200 shadow-md text-center" data-modal-target="login-modal" data-lang-key="accessBtn">Acceder</a>
        </div>
    </div>

    <section class="hero-rumora-gradient py-20 md:py-32 text-center relative">
        <div class="container mx-auto px-6 relative z-10">
            <h2 class="text-4xl md:text-6xl lg:text-7xl font-extrabold leading-tight mb-6 animate-fade-in-scale" data-lang-key="heroTitle">
                ¡RUMORA: Conecta, Expresa, Descubre!
            </h2>
            <p class="text-lg md:text-xl max-w-4xl mx-auto mb-12 animate-fade-in-scale delay-200" data-lang-key="heroDescription">
                Regístrate de forma sencilla con tu número y contraseña. Te asignaremos un **avatar único** y un **nombre de usuario aleatorio** para que tu experiencia sea divertida desde el primer momento.
            </p>
            <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                <a href="#" class="py-4 px-10 bg-white text-orange-700 rounded-full text-xl font-bold hover:bg-gray-100 transition-all duration-300 shadow-xl btn-rumora animate-pulse-subtle animate-fade-in-scale delay-300" data-modal-target="register-modal" data-lang-key="joinRumoraBtn">
                    <i class="fas fa-user-plus mr-3"></i><span data-lang-key="joinRumoraBtnText">¡Únete a RUMORA!</span>
                </a>
                <a href="#" class="py-4 px-10 bg-transparent border-2 border-white text-white rounded-full text-xl font-bold hover:bg-white hover:text-orange-700 transition-all duration-300 shadow-xl btn-rumora btn-secondary animate-fade-in-scale delay-400" data-modal-target="login-modal" data-lang-key="alreadyRumoristaBtn">
                    <i class="fas fa-sign-in-alt mr-3"></i><span data-lang-key="alreadyRumoristaBtnText">Ya soy RUMORISTA</span>
                </a>
            </div>
        </div>
    </section>

    <section id="features" class="py-16 md:py-24 bg-gray-900">
        <div class="container mx-auto px-6 text-center">
            <h3 class="text-3xl md:text-4xl font-bold text-white mb-12" data-lang-key="featuresSectionTitle">Descubre las Funcionalidades de RUMORA</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="feature-card-rumora p-8 animate-fade-in-scale delay-100">
                    <div class="feature-icon-circle icon-bg-cyan-vibrant">
                        <i class="fas fa-comments text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-white mb-3" data-lang-key="feature1Title">Conexiones Instantáneas</h4>
                    <p class="text-gray-300" data-lang-key="feature1Description">Chatea en tiempo real con amigos, familiares o nuevas conexiones. Tus conversaciones, a tu manera.</p>
                </div>
                <div class="feature-card-rumora p-8 animate-fade-in-scale delay-200">
                    <div class="feature-icon-circle icon-bg-red-vibrant">
                        <i class="fas fa-map-marker-alt text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-white mb-3" data-lang-key="feature2Title">Encuentra Tu Vibe</h4>
                    <p class="text-gray-300" data-lang-key="feature2Description">Busca y conecta con personas de tu **departamento y provincia**, o explora otros lugares y filtra por **género**.</p>
                </div>
                <div class="feature-card-rumora p-8 animate-fade-in-scale delay-300">
                    <div class="feature-icon-circle icon-bg-fuchsia-vibrant">
                        <i class="fas fa-mask text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-white mb-3" data-lang-key="feature3Title">El Rincón de Confesiones</h4>
                    <p class="text-gray-300" data-lang-key="feature3Description">Un espacio seguro para compartir **confesiones anónimas** o leer las de la comunidad, organizadas por días.</p>
                </div>
                <div class="feature-card-rumora p-8 animate-fade-in-scale delay-400">
                    <div class="feature-icon-circle icon-bg-purple-vibrant">
                        <i class="fas fa-random text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-white mb-3" data-lang-key="feature4Title">Identidad Sorpresa</h4>
                    <p class="text-gray-300" data-lang-key="feature4Description">Al registrarte, tu **avatar y nombre de usuario aleatorios** te esperan para una experiencia fresca.</p>
                </div>
                <div class="feature-card-rumora p-8 animate-fade-in-scale delay-500">
                    <div class="feature-icon-circle icon-bg-lime-vibrant">
                        <i class="fas fa-shield-alt text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-white mb-3" data-lang-key="feature5Title">Tu Privacidad, Tu Control</h4>
                    <p class="text-gray-300" data-lang-key="feature5Description">Gestiona quién ve tu perfil, tu última conexión y ajusta las opciones de seguridad a tu medida.</p>
                </div>
                <div class="feature-card-rumora p-8 animate-fade-in-scale delay-600">
                    <div class="feature-icon-circle icon-bg-orange-vibrant">
                        <i class="fas fa-sliders-h text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-semibold text-white mb-3" data-lang-key="feature6Title">Personaliza Tu Perfil</h4>
                    <p class="text-gray-300" data-lang-key="feature6Description">Actualiza tu avatar, nombre de usuario y decide si quieres aparecer en las listas públicas de RUMORA.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="auth-section" class="bg-gray-800 py-16 md:py-24 text-center text-white shadow-xl">
        <div class="container mx-auto px-6">
            <h3 class="text-3xl md:text-4xl font-bold mb-8" data-lang-key="ctaTitle">¿Listo para Chismear en RUMORA?</h3>
            <p class="text-lg text-gray-300 max-w-2xl mx-auto mb-10" data-lang-key="ctaDescription">
                La comunidad de RUMORA te espera con los brazos abiertos. Únete hoy y descubre una nueva forma de conectar.
            </p>
            <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-6">
                <a href="#" class="py-4 px-10 bg-orange-500 text-white rounded-full text-xl font-bold hover:bg-orange-600 transition-all duration-300 shadow-xl btn-rumora" data-modal-target="register-modal" data-lang-key="joinRumoraBtn">
                    <i class="fas fa-hand-sparkles mr-3"></i><span data-lang-key="startRumoraBtnText">¡Empezar mi RUMORA!</span>
                </a>
                <a href="#" class="py-4 px-10 bg-gray-700 text-gray-200 rounded-full text-xl font-bold hover:bg-gray-600 transition-all duration-300 shadow-xl btn-rumora" data-modal-target="login-modal" data-lang-key="loginBtn">
                    <i class="fas fa-sign-in-alt mr-3"></i><span data-lang-key="loginBtnText">Ingresar</span>
                </a>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 py-10 border-t border-gray-700 text-gray-400 text-center text-sm">
        <div class="container mx-auto px-6">
            <p data-lang-key="footerCopyright">&copy; 2024 RUMORA. Todos los derechos reservados.</p>
            <div class="flex justify-center space-x-6 mt-4">
                <a href="#" class="hover:text-orange-400 transition-colors duration-200" data-lang-key="privacyLink">Privacidad</a>
                <a href="#" class="hover:text-orange-400 transition-colors duration-200" data-lang-key="termsLink">Términos</a>
                <a href="#" class="hover:text-orange-400 transition-colors duration-200" data-lang-key="helpLink">Ayuda</a>
            </div>
        </div>
    </footer>

    <div id="login-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <button class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors duration-200 close-modal">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 rounded-full bg-indigo-600 flex items-center justify-center shadow-lg mb-4">
                    <i class="fas fa-comment-dots text-3xl text-white"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-white mb-2" data-lang-key="loginTitle">Ingresa a RUMORA</h2>
                <p class="text-gray-400" data-lang-key="loginSubtitle">¡Nos alegra verte de nuevo!</p>
            </div>

            <form id="login-form" method="POST" action="" class="space-y-6">
                <input type="hidden" name="login_submit" value="1">
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="phoneLabel">Número de Teléfono</label>
                    <input type="text" id="phone" name="phone" placeholder="Ej. 987654321" class="w-full input-rumora" required pattern="9\d{8}" title="El número debe empezar con 9 y tener 9 dígitos">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="passwordLabel">Contraseña</label>
                    <input type="password" id="password" name="password" placeholder="Tu contraseña secreta" class="w-full input-rumora" required>
                </div>
                
                <button type="submit" class="w-full py-3 bg-orange-500 text-white rounded-xl font-bold hover:bg-orange-600 btn-rumora text-lg shadow-lg" data-lang-key="loginBtn">
                    <i class="fas fa-sign-in-alt mr-2"></i> <span data-lang-key="loginBtnText">Iniciar Sesión</span>
                </button>
            </form>

            <p class="text-center text-gray-400 text-sm mt-8" data-lang-key="noAccountText">
                ¿No tienes una cuenta? 
                <a href="#" class="text-orange-400 hover:text-orange-300 font-semibold transition-colors duration-200" data-switch-modal="register-modal" data-lang-key="registerHereLink">Regístrate aquí</a>
            </p>
        </div>
    </div>

    <div id="register-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <button class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors duration-200 close-modal">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 rounded-full bg-indigo-600 flex items-center justify-center shadow-lg mb-4">
                    <i class="fas fa-hand-sparkles text-3xl text-white"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-white mb-2" data-lang-key="registerTitle">Únete a RUMORA</h2>
                <p class="text-gray-400 text-center" data-lang-key="registerSubtitle">¡Crea tu cuenta y empieza a conectar!</p>
            </div>

            <form id="register-form" method="POST" action="" class="space-y-6">
                <input type="hidden" name="register_submit" value="1">
                <div>
                    <label for="reg-phone" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="phoneLabel">Número de Teléfono</label>
                    <input type="text" id="reg-phone" name="phone" placeholder="Ej. 987654321" class="w-full input-rumora" required pattern="9\d{8}" title="El número debe empezar con 9 y tener 9 dígitos">
                </div>
                <div>
                    <label for="reg-password" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="passwordLabel">Contraseña</label>
                    <input type="password" id="reg-password" name="password" placeholder="Mínimo 6 caracteres" class="w-full input-rumora" required minlength="6">
                </div>
                <div>
                    <label for="gender" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="genderLabel">Género</label>
                    <select id="gender" name="gender" class="w-full select-rumora" required>
                        <option value="" data-lang-key="selectGenderOption">Selecciona tu género</option>
                        <option value="male" data-lang-key="maleGender">Masculino</option>
                        <option value="female" data-lang-key="femaleGender">Femenino</option>
                        <option value="other" data-lang-key="otherGender">Otro</option>
                    </select>
                </div>

                <div class="flex items-center space-x-2">
                    <input type="checkbox" id="is-foreign" name="is_foreign" class="form-checkbox h-5 w-5 text-orange-500 rounded border-gray-300 focus:ring-orange-500">
                    <label for="is-foreign" class="text-sm font-medium text-gray-300" data-lang-key="notFromPeruCheckbox">No soy de Perú</label>
                </div>

                <div id="peru-location-fields">
                    <div>
                        <label for="department" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="departmentLabel">Departamento</label>
                        <select id="department" name="department" class="w-full select-rumora" required>
                            <option value="" data-lang-key="selectDepartmentOption">Selecciona tu departamento</option>
                            </select>
                    </div>
                    <div>
                        <label for="province" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="provinceLabel">Provincia</label>
                        <select id="province" name="province" class="w-full select-rumora" required>
                            <option value="" data-lang-key="selectProvinceOption">Selecciona tu provincia</option>
                            </select>
                    </div>
                </div>
                
                <button type="submit" class="w-full py-3 bg-red-500 text-white rounded-xl font-bold hover:bg-red-600 btn-rumora text-lg shadow-lg" data-lang-key="createAccountBtn">
                    <i class="fas fa-user-plus mr-2"></i> <span data-lang-key="createAccountBtnText">Crear Cuenta</span>
                </button>
            </form>

            <p class="text-center text-gray-400 text-sm mt-8" data-lang-key="alreadyAccountText">
                ¿Ya eres Rumorista? 
                <a href="#" class="text-red-400 hover:text-red-300 font-semibold transition-colors duration-200" data-switch-modal="login-modal" data-lang-key="loginHereLink">Inicia sesión aquí</a>
            </p>
        </div>
    </div>

    <div id="admin-login-modal" class="modal-overlay hidden">
        <div class="modal-content">
            <button class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors duration-200 close-modal">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="flex flex-col items-center mb-8">
                <div class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center shadow-lg mb-4">
                    <i class="fas fa-user-shield text-3xl text-white"></i>
                </div>
                <h2 class="text-3xl font-extrabold text-white mb-2" data-lang-key="adminLoginTitle">Acceso de Administrador</h2>
                <p class="text-gray-400" data-lang-key="adminLoginSubtitle">Solo para personal autorizado.</p>
            </div>

            <form id="admin-login-form" method="POST" action="" class="space-y-6">
                <input type="hidden" name="admin_login_submit" value="1">
                <div>
                    <label for="admin-email" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="adminEmailLabel">Correo Electrónico</label>
                    <input type="email" id="admin-email" name="admin_email" placeholder="admin@ejemplo.com" class="w-full input-rumora" required>
                </div>
                <div>
                    <label for="admin-password" class="block text-sm font-medium text-gray-300 mb-2" data-lang-key="adminPasswordLabel">Contraseña</label>
                    <input type="password" id="admin-password" name="admin_password" placeholder="Contraseña de Administrador" class="w-full input-rumora" required>
                </div>

                <button type="submit" class="w-full py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 btn-rumora text-lg shadow-lg" data-lang-key="adminLoginBtn">
                    <i class="fas fa-unlock-alt mr-2"></i> <span data-lang-key="adminLoginBtnText">Acceder como Administrador</span>
                </button>
            </form>
        </div>
    </div>


    <div class="floating-controls">
        <button id="theme-toggle" class="floating-btn" aria-label="Toggle theme">
            <i class="fas fa-moon" id="theme-icon"></i>
        </button>
        <button id="lang-toggle" class="floating-btn" aria-label="Toggle language">
            <i class="fas fa-globe"></i>
        </button>
    </div>


    <script>
        // JavaScript para alternar la visibilidad del menú móvil
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');

        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });

        // Opcional: Cerrar el menú móvil si se hace clic fuera de él (en ventanas grandes)
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) { // Cierra el menú si la pantalla es de escritorio
                mobileMenu.classList.add('hidden');
            }
        });

        // --- Lógica de Modales ---
        const loginModal = document.getElementById('login-modal');
        const registerModal = document.getElementById('register-modal');
        const adminLoginModal = document.getElementById('admin-login-modal'); // Get the new admin modal

        function openModal(modal) {
            modal.classList.remove('hidden');
            // For animation
            setTimeout(() => {
                modal.classList.add('open');
                // If the register modal is opened, ensure departments are populated
                if (modal.id === 'register-modal' && !isForeignCheckbox.checked) {
                    populateDepartments();
                }
            }, 10);
        }

        function closeModal(modal) {
            modal.classList.remove('open');
            // For animation
            setTimeout(() => modal.classList.add('hidden'), 300); // Coincide con la duración de la transición CSS
        }

        // Abrir modales al hacer clic en los botones de hero y auth-section
        document.querySelectorAll('[data-modal-target]').forEach(button => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                const targetId = button.getAttribute('data-modal-target');
                const targetModal = document.getElementById(targetId);
                if (targetModal) {
                    // Close other modals if open
                    if (targetModal.id === 'login-modal') {
                        if (registerModal.classList.contains('open')) closeModal(registerModal);
                        if (adminLoginModal.classList.contains('open')) closeModal(adminLoginModal);
                    } else if (targetModal.id === 'register-modal') {
                        if (loginModal.classList.contains('open')) closeModal(loginModal);
                        if (adminLoginModal.classList.contains('open')) closeModal(adminLoginModal);
                    }
                    openModal(targetModal);
                }
            });
        });

        // Cerrar modales al hacer clic en el botón de cierre
        document.querySelectorAll('.close-modal').forEach(button => {
            button.addEventListener('click', (event) => {
                const modal = event.target.closest('.modal-overlay');
                if (modal) {
                    closeModal(modal);
                }
            });
        });

        // Cerrar modal al hacer clic fuera del contenido del modal
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) { // Solo si se hace clic directamente en el overlay, no en el contenido
                    closeModal(overlay);
                }
            });
        });

        // Cambiar entre modales (ej. de login a register y viceversa)
        document.querySelectorAll('[data-switch-modal]').forEach(link => {
            link.addEventListener('click', (event) => {
                event.preventDefault();
                const currentModal = event.target.closest('.modal-overlay');
                const targetModalId = event.target.getAttribute('data-switch-modal');
                const targetModal = document.getElementById(targetModalId);

                if (currentModal) {
                    closeModal(currentModal);
                }
                if (targetModal) {
                    // Un pequeño retraso para que el modal actual tenga tiempo de cerrarse visualmente
                    setTimeout(() => openModal(targetModal), 300); 
                }
            });
        });

        // --- Keyboard Shortcut for Admin Modal ---
        document.addEventListener('keydown', (event) => {
            // Check for Ctrl (or Cmd on Mac) + Alt + A
            if ((event.ctrlKey || event.metaKey) && event.altKey && event.key.toLowerCase() === 'a') {
                event.preventDefault(); // Prevent default browser action (e.g., opening a new window/tab)
                if (adminLoginModal) {
                    // Close other modals if open
                    if (loginModal.classList.contains('open')) closeModal(loginModal);
                    if (registerModal.classList.contains('open')) closeModal(registerModal);
                    openModal(adminLoginModal);
                }
            }
        });


        // --- Lógica de Registro (con Departamentos/Provincias y Extranjero) ---
        const peruDepartments = {
            "Amazonas": ["Chachapoyas", "Bagua", "Bongará", "Condorcanqui", "Luya", "Rodríguez de Mendoza", "Utcubamba"],
            "Áncash": ["Huaraz", "Aija", "Antonio Raymondi", "Asunción", "Bolognesi", "Carhuaz", "Carlos Fermín Fitzcarrald", "Casma", "Corongo", "Huaylas", "Huarmey", "Huata", "Mariscal Luzuriaga", "Ocros", "Pallasca", "Pomabamba", "Recuay", "Santa", "Sihuas", "Yungay"],
            "Apurímac": ["Abancay", "Andahuaylas", "Antabamba", "Aymaraes", "Cotabambas", "Chincheros", "Grau"],
            "Arequipa": ["Arequipa", "Camaná", "Caravelí", "Castilla", "Caylloma", "Condesuyos", "Islay", "La Unión"],
            "Ayacucho": ["Huamanga", "Cangallo", "Huanca Sancos", "Huanta", "La Mar", "Lucanas", "Parinacochas", "Páucar del Sara Sara", "Sucre", "Víctor Fajardo", "Vilcas Huamán"],
            "Cajamarca": ["Cajamarca", "Cajabamba", "Celendín", "Chota", "Contumazá", "Cutervo", "Hualgayoc", "Jaén", "San Ignacio", "San Marcos", "San Miguel", "San Pablo", "Santa Cruz"],
            "Callao": ["Callao"],
            "Cusco": ["Cusco", "Acomayo", "Anta", "Calca", "Canas", "Canchis", "Chumbivilcas", "Espinar", "La Convención", "Paruro", "Paucartambo", "Quispicanchi", "Urumbamba"],
            "Huancavelica": ["Huancavelica", "Acobamba", "Angaraes", "Castrovirreyna", "Churcampa", "Huaytará", "Tayacaja"],
            "Huánuco": ["Huánuco", "Ambo", "Dos de Mayo", "Huacaybamba", "Huamalíes", "Lauricocha", "Marañón", "Pachitea", "Puerto Inca", "Leoncio Prado", "Yarowilca"],
            "Ica": ["Ica", "Chincha", "Nazca", "Palpa", "Pisco"],
            "Junín": ["Huancayo", "Concepción", "Chanchamayo", "Jauja", "Junín", "Satipo", "Tarma", "Yauli", "Chupaca"],
            "La Libertad": ["Trujillo", "Ascope", "Bolívar", "Chepén", "Gran Chimú", "Julcán", "Otuzco", "Pacasmayo", "Pataz", "Santiago de Chuco", "Sánchez Carrión", "Virú"],
            "Lambayeque": ["Chiclayo", "Ferñafe", "Lambayeque"],
            "Lima": ["Lima", "Barranca", "Cajatambo", "Canta", "Cañete", "Huaral", "Huarochirí", "Huaura", "Oyón", "Yauyos"],
            "Loreto": ["Maynas", "Alto Amazonas", "Loreto", "Mariscal Ramón Castilla", "Putumayo", "Requena", "Ucayali", "Datem del Marañón"],
            "Madre de Dios": ["Tambopata", "Manu", "Tahuamanu"],
            "Moquegua": ["Mariscal Nieto", "General Sánchez Cerro", "Ilo"],
            "Pasco": ["Pasco", "Daniel Alcides Carrión", "Oxapampa"],
            "Piura": ["Piura", "Ayabaca", "Huancabamba", "Morropón", "Paita", "Sullana", "Talara", "Sechura"],
            "Puno": ["Puno", "Azángaro", "Carabaya", "Chucuito", "El Collao", "Huancané", "Lampa", "Melgar", "San Antonio de Putina", "San Román", "Sandia", "Yunguyo"],
            "San Martín": ["Moyobamba", "Bellavista", "El Dorado", "Huallaga", "Lamas", "Mariscal Cáceres", "Picota", "Rioja", "San Martín", "Tocache"],
            "Tacna": ["Tacna", "Candara", "Jorge Basadre", "Tarata"],
            "Tumbes": ["Tumbes", "Contralmirante Villar", "Zarumilla"],
            "Ucayali": ["Coronel Portillo", "Atalaya", "Padre Abad", "Purús"]
        };

        const departmentSelect = document.getElementById('department');
        const provinceSelect = document.getElementById('province');
        const isForeignCheckbox = document.getElementById('is-foreign');
        const peruLocationFields = document.getElementById('peru-location-fields');

        // Populate departments on load
        function populateDepartments() {
            departmentSelect.innerHTML = `<option value="">${translations[currentLanguage].selectDepartmentOption}</option>`;
            for (const department in peruDepartments) {
                const option = document.createElement('option');
                option.value = department;
                option.textContent = department;
                departmentSelect.appendChild(option);
            }
        }

        // Populate provinces based on selected department
        departmentSelect.addEventListener('change', () => {
            const selectedDepartment = departmentSelect.value;
            provinceSelect.innerHTML = `<option value="">${translations[currentLanguage].selectProvinceOption}</option>`;
            if (selectedDepartment && peruDepartments[selectedDepartment]) {
                peruDepartments[selectedDepartment].forEach(province => {
                    const option = document.createElement('option');
                    option.value = province;
                    option.textContent = province;
                    provinceSelect.appendChild(option);
                });
            }
        });

        // Handle "No soy de Perú" checkbox
        isForeignCheckbox.addEventListener('change', () => {
            if (isForeignCheckbox.checked) {
                peruLocationFields.classList.add('hidden');
                departmentSelect.removeAttribute('required');
                provinceSelect.removeAttribute('required');
                departmentSelect.value = ''; // Clear selection
                provinceSelect.value = ''; // Clear selection
            } else {
                peruLocationFields.classList.remove('hidden');
                departmentSelect.setAttribute('required', 'required');
                provinceSelect.setAttribute('required', 'required');
                populateDepartments(); // Re-populate if returning to Peru fields
            }
        });

        // Initial population when script loads (for first time display if register modal is default open or similar)
        // or ensure it's called when the register modal opens.
        // It's already handled in openModal function for register-modal.

        // --- Lógica de Modo Claro/Oscuro ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');

        function toggleTheme() {
            document.body.classList.toggle('light-mode');
            if (document.body.classList.contains('light-mode')) {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
                localStorage.setItem('theme', 'light');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
                localStorage.setItem('theme', 'dark');
            }
        }

        themeToggleBtn.addEventListener('click', toggleTheme);

        // Load theme preference from localStorage
        // Ensure this runs after the DOM is ready but before the general JS setup if possible
        if (localStorage.getItem('theme') === 'light') {
            document.body.classList.add('light-mode');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
        }


        // --- Lógica de Traducción de Idioma ---
        const langToggleBtn = document.getElementById('lang-toggle');
        let currentLanguage = localStorage.getItem('language') || 'es'; // Default to Spanish

        const translations = {
            es: {
                title: "Bienvenido a RUMORA - Tu Comunidad Única",
                appName: "RUMORA",
                homeLink: "Inicio",
                featuresLink: "Características",
                accessBtn: "Acceder",
                heroTitle: "¡RUMORA: Conecta, Expresa, Descubre!",
                heroDescription: "Regístrate de forma sencilla con tu número y contraseña. Te asignaremos un **avatar único** y un **nombre de usuario aleatorio** para que tu experiencia sea divertida desde el primer momento.",
                joinRumoraBtn: "¡Únete a RUMORA!",
                joinRumoraBtnText: "¡Únete a RUMORA!", // Added for span inside button
                alreadyRumoristaBtn: "Ya soy RUMORISTA",
                alreadyRumoristaBtnText: "Ya soy RUMORISTA", // Added for span inside button
                featuresSectionTitle: "Descubre las Funcionalidades de RUMORA",
                feature1Title: "Conexiones Instantáneas",
                feature1Description: "Chatea en tiempo real con amigos, familiares o nuevas conexiones. Tus conversaciones, a tu manera.",
                feature2Title: "Encuentra Tu Vibe",
                feature2Description: "Busca y conecta con personas de tu **departamento y provincia**, o explora otros lugares y filtra por **género**.",
                feature3Title: "El Rincón de Confesiones",
                feature3Description: "Un espacio seguro para compartir **confesiones anónimas** o leer las de la comunidad, organizadas por días.",
                feature4Title: "Identidad Sorpresa",
                feature4Description: "Al registrarte, tu **avatar y nombre de usuario aleatorios** te esperan para una experiencia fresca.",
                feature5Title: "Tu Privacidad, Tu Control",
                feature5Description: "Gestiona quién ve tu perfil, tu última conexión y ajusta las opciones de seguridad a tu medida.",
                feature6Title: "Personaliza Tu Perfil",
                feature6Description: "Actualiza tu avatar, nombre de usuario y decide si quieres aparecer en las listas públicas de RUMORA.",
                ctaTitle: "¿Listo para Chismear en RUMORA?",
                ctaDescription: "La comunidad de RUMORA te espera con los brazos abiertos. Únete hoy y descubre una nueva forma de conectar.",
                startRumoraBtnText: "¡Empezar mi RUMORA!", // Added for span inside button
                loginBtn: "Ingresar",
                loginBtnText: "Ingresar", // Added for span inside button
                footerCopyright: "&copy; 2024 RUMORA. Todos los derechos reservados.",
                privacyLink: "Privacidad",
                termsLink: "Términos",
                helpLink: "Ayuda",
                loginTitle: "Ingresa a RUMORA",
                loginSubtitle: "¡Nos alegra verte de nuevo!",
                phoneLabel: "Número de Teléfono",
                phonePlaceholder: "Ej. 987654321", // Added for placeholder
                passwordLabel: "Contraseña",
                passwordPlaceholderLogin: "Tu contraseña secreta", // Added for placeholder
                noAccountText: "¿No tienes una cuenta?",
                registerHereLink: "Regístrate aquí",
                registerTitle: "Únete a RUMORA",
                registerSubtitle: "¡Crea tu cuenta y empieza a conectar!",
                passwordPlaceholderRegister: "Mínimo 6 caracteres", // Added for placeholder
                genderLabel: "Género",
                selectGenderOption: "Selecciona tu género",
                maleGender: "Masculino",
                femaleGender: "Femenino",
                otherGender: "Otro",
                notFromPeruCheckbox: "No soy de Perú",
                departmentLabel: "Departamento",
                selectDepartmentOption: "Selecciona tu departamento",
                provinceLabel: "Provincia",
                selectProvinceOption: "Selecciona tu provincia",
                createAccountBtn: "Crear Cuenta",
                createAccountBtnText: "Crear Cuenta", // Added for span inside button
                alreadyAccountText: "¿Ya eres Rumorista?",
                loginHereLink: "Inicia sesión aquí",
                // Admin Login Translations
                adminLoginTitle: "Acceso de Administrador",
                adminLoginSubtitle: "Solo para personal autorizado.",
                adminEmailLabel: "Correo Electrónico",
                adminPasswordLabel: "Contraseña",
                adminLoginBtn: "Acceder como Administrador",
                adminLoginBtnText: "Acceder como Administrador",
            },
            en: {
                title: "Welcome to RUMORA - Your Unique Community",
                appName: "RUMORA",
                homeLink: "Home",
                featuresLink: "Features",
                accessBtn: "Access",
                heroTitle: "RUMORA: Connect, Express, Discover!",
                heroDescription: "Register easily with your number and password. We'll assign you a **unique avatar** and a **random username** to make your experience fun from the start.",
                joinRumoraBtn: "Join RUMORA!",
                joinRumoraBtnText: "Join RUMORA!",
                alreadyRumoristaBtn: "Already a RUMORISTA",
                alreadyRumoristaBtnText: "Already a RUMORISTA",
                featuresSectionTitle: "Discover RUMORA's Features",
                feature1Title: "Instant Connections",
                feature1Description: "Chat in real-time with friends, family, or new connections. Your conversations, your way.",
                feature2Title: "Find Your Vibe",
                feature2Description: "Search and connect with people from your **department and province**, or explore other locations and filter by **gender**.",
                feature3Title: "The Confessions Corner",
                feature3Description: "A safe space to share **anonymous confessions** or read those from the community, organized by days.",
                feature4Title: "Surprise Identity",
                feature4Description: "Upon registration, your **random avatar and username** await you for a fresh experience.",
                feature5Title: "Your Privacy, Your Control",
                feature5Description: "Manage who sees your profile, your last online status, and adjust security options to your liking.",
                feature6Title: "Personalize Your Profile",
                feature6Description: "Update your avatar, username, and decide if you want to appear in RUMORA's public lists.",
                ctaTitle: "Ready to Gossip on RUMORA?",
                ctaDescription: "The RUMORA community welcomes you with open arms. Join today and discover a new way to connect.",
                startRumoraBtnText: "Start My RUMORA!",
                loginBtn: "Log In",
                loginBtnText: "Log In",
                footerCopyright: "&copy; 2024 RUMORA. All rights reserved.",
                privacyLink: "Privacy",
                termsLink: "Terms",
                helpLink: "Help",
                loginTitle: "Log In to RUMORA",
                loginSubtitle: "Glad to see you again!",
                phoneLabel: "Phone Number",
                phonePlaceholder: "E.g. 987654321",
                passwordLabel: "Password",
                passwordPlaceholderLogin: "Your secret password",
                noAccountText: "Don't have an account?",
                registerHereLink: "Register here",
                registerTitle: "Join RUMORA",
                registerSubtitle: "Create your account and start connecting!",
                passwordPlaceholderRegister: "Min 6 characters",
                genderLabel: "Gender",
                selectGenderOption: "Select your gender",
                maleGender: "Male",
                femaleGender: "Female",
                otherGender: "Other",
                notFromPeruCheckbox: "Not from Peru",
                departmentLabel: "Department",
                selectDepartmentOption: "Select your department",
                provinceLabel: "Province",
                selectProvinceOption: "Select your province",
                createAccountBtn: "Create Account",
                createAccountBtnText: "Create Account",
                alreadyAccountText: "Already a Rumorista?",
                loginHereLink: "Log in here",
                // Admin Login Translations
                adminLoginTitle: "Administrator Access",
                adminLoginSubtitle: "For authorized personnel only.",
                adminEmailLabel: "Email Address",
                adminPasswordLabel: "Password",
                adminLoginBtn: "Access as Administrator",
                adminLoginBtnText: "Access as Administrator",
            }
        };

        function updateLanguage(lang) {
            document.querySelectorAll('[data-lang-key]').forEach(element => {
                const key = element.getAttribute('data-lang-key');
                if (translations[lang] && translations[lang][key]) {
                    if (element.tagName === 'INPUT' && element.hasAttribute('placeholder')) {
                         // Specific handling for placeholders
                         if (key === 'phonePlaceholder' && element.id === 'phone') element.placeholder = translations[lang].phonePlaceholder;
                         else if (key === 'passwordPlaceholderLogin' && element.id === 'password') element.placeholder = translations[lang].passwordPlaceholderLogin;
                         else if (key === 'phonePlaceholder' && element.id === 'reg-phone') element.placeholder = translations[lang].phonePlaceholder;
                         else if (key === 'passwordPlaceholderRegister' && element.id === 'reg-password') element.placeholder = translations[lang].passwordPlaceholderRegister;
                         else if (key === 'adminEmailPlaceholder' && element.id === 'admin-email') element.placeholder = translations[lang].adminEmailPlaceholder;
                         else if (key === 'adminPasswordPlaceholder' && element.id === 'admin-password') element.placeholder = translations[lang].adminPasswordPlaceholder;
                         else element.placeholder = translations[lang][key]; // Fallback for other placeholders
                    } else if (element.tagName === 'SELECT') {
                        // For select options, update the specific option text, not the select itself
                        const options = element.querySelectorAll('option');
                        options.forEach(option => {
                            const optionKey = option.getAttribute('data-lang-key');
                            if (optionKey && translations[lang][optionKey]) {
                                option.textContent = translations[lang][optionKey];
                            }
                        });
                        // Also update the default "Select your..." option if it has a data-lang-key
                        const defaultOption = element.querySelector('option[value=""]');
                        if (defaultOption) { // Check if default option exists
                            // Re-evaluate default option text based on its original data-lang-key
                            const defaultOptionKey = defaultOption.getAttribute('data-lang-key');
                            if (defaultOptionKey && translations[lang][defaultOptionKey]) {
                                defaultOption.textContent = translations[lang][defaultOptionKey];
                            }
                        }
                    } else if (key === 'heroDescription' || key === 'feature2Description' || key === 'feature4Description') {
                        element.innerHTML = translations[lang][key];
                    } else {
                        element.textContent = translations[lang][key];
                    }
                }
            });
            // Re-populate department/province options to ensure their text is correct after language change
            const currentDepartmentValue = departmentSelect.value;
            const currentProvinceValue = provinceSelect.value;
            populateDepartments(); // This function now directly uses translations[currentLanguage]
            departmentSelect.value = currentDepartmentValue; // Set back the user's selection
            if (currentDepartmentValue) {
                // If a department was selected, populate its provinces again with the correct language
                const selectedDepartment = departmentSelect.value;
                provinceSelect.innerHTML = `<option value="">${translations[currentLanguage].selectProvinceOption}</option>`;
                if (selectedDepartment && peruDepartments[selectedDepartment]) {
                    peruDepartments[selectedDepartment].forEach(province => {
                        const option = document.createElement('option');
                        option.value = province;
                        option.textContent = province;
                        provinceSelect.appendChild(option);
                    });
                }
                provinceSelect.value = currentProvinceValue;
            }
            document.title = translations[lang].title;
            localStorage.setItem('language', lang);
        }

        langToggleBtn.addEventListener('click', () => {
            currentLanguage = currentLanguage === 'es' ? 'en' : 'es';
            updateLanguage(currentLanguage);
        });

        // Initialize language on load
        // This is called directly after definition to ensure initial state.
        updateLanguage(currentLanguage);
    </script>
</body>
</html>