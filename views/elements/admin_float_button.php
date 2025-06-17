<?php if (isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1): ?>
    <a href="views/admin/dashboard_admin.php" class="btn btn-primary btn-floating" style="
        position: fixed;
        bottom: 30px;
        right: 30px;
        border-radius: 50%;
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        z-index: 9999;
    ">
        <i class="fas fa-user-shield"></i>
    </a>
<?php endif; ?>
