<?php
class AdminController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->checkAdmin();
    }

    private function checkAdmin() {
        session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }

        $stmt = $this->db->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_admin']) {
            header('Location: /perfil');
            exit();
        }
    }

    public function dashboard() {
        // Obtener estadísticas
        $stats = [
            'total_usuarios' => $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'usuarios_activos' => $this->db->query("SELECT COUNT(*) FROM users WHERE last_seen >= NOW() - INTERVAL 1 DAY")->fetchColumn(),
            'nuevos_usuarios' => $this->db->query("SELECT COUNT(*) FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY")->fetchColumn()
        ];

        include_once 'views/admin/dashboard.php';
    }

    public function usuarios() {
        $search = $_GET['search'] ?? '';
        $estado = $_GET['estado'] ?? 'todos';
        
        $query = "SELECT * FROM users WHERE 1=1";
        $params = [];
        
        if ($search) {
            $query .= " AND (nombre_usuario LIKE ? OR numero LIKE ? OR departamento LIKE ? OR provincia LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, array_fill(0, 4, $searchTerm));
        }
        
        if ($estado !== 'todos') {
            $query .= " AND is_active = ?";
            $params[] = ($estado === 'activos') ? 1 : 0;
        }
        
        $query .= " ORDER BY created_at DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $usuarios = $stmt->fetchAll();

        include_once 'views/admin/usuarios.php';
    }

    public function toggleUsuario($id) {
        $stmt = $this->db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        
        header('Location: /admin/usuarios');
        exit();
    }
}
?>
