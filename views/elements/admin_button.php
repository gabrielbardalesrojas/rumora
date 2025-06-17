<?php if (isset($_SESSION['user_id']) && $_SESSION['is_admin'] == 1): ?>
    <div class="admin-panel-toggle">
        <a href="views/admin/dashboard.php" class="btn btn-primary btn-admin-panel">
            <i class="fas fa-tachometer-alt me-2"></i>Panel de Administración
        </a>
    </div>
    <style>
        .admin-panel-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }
        .btn-admin-panel {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            border-radius: 25px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-admin-panel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }
    </style>
<?php endif; ?>
