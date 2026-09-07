<?php
/**
 * Authentication & RBAC Access Control Guard
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Helpers.php';

class Auth {
    private static ?array $cachedUser = null;

    public static function check(): bool {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): ?string {
        return $_SESSION['user_role'] ?? null;
    }

    public static function employeeId(): ?int {
        $u = self::user();
        return $u['employee_id'] ?? null;
    }

    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }

        if (self::$cachedUser === null) {
            $sql = "SELECT u.id, u.name, u.email, u.role, u.status, u.avatar,
                           e.id AS employee_id, e.emp_code, e.first_name, e.last_name, e.phone,
                           e.department_id, e.designation_id, e.manager_id, e.date_of_joining,
                           d.name AS department_name, des.title AS designation_title
                    FROM users u
                    LEFT JOIN employees e ON u.id = e.user_id
                    LEFT JOIN departments d ON e.department_id = d.id
                    LEFT JOIN designations des ON e.designation_id = des.id
                    WHERE u.id = ? AND u.status = 'active'";
            self::$cachedUser = Database::fetchOne($sql, [self::id()]);
        }

        return self::$cachedUser;
    }

    public static function attempt(string $email, string $password): bool {
        $user = Database::fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        if ($user && password_verify($password, $user['password'])) {
            if (!headers_sent()) {
                session_regenerate_id(true);
            }
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            self::$cachedUser = null; // reset cache

            Database::logActivity($user['id'], 'LOGIN', 'AUTH', 'User logged in successfully');
            return true;
        }
        return false;
    }

    public static function logout(): void {
        if (self::check()) {
            Database::logActivity(self::id(), 'LOGOUT', 'AUTH', 'User logged out');
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        self::$cachedUser = null;
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            flash('warning', 'Please log in to access this page.');
            redirect('login');
        }
    }

    public static function hasRole(string|array $roles): bool {
        if (!self::check()) {
            return false;
        }
        $currentRole = self::role();
        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }
        return $currentRole === $roles;
    }

    public static function requireRole(string|array $roles): void {
        self::requireLogin();
        if (!self::hasRole($roles)) {
            flash('danger', 'Unauthorized access! You do not have permission to view that resource.');
            redirect('dashboard');
        }
    }

    public static function isSuperAdmin(): bool {
        return self::hasRole('super_admin');
    }

    public static function isHR(): bool {
        return self::hasRole(['super_admin', 'hr_admin']);
    }

    public static function isManager(): bool {
        return self::hasRole(['super_admin', 'hr_admin', 'manager']);
    }

    public static function isEmployee(): bool {
        return self::check();
    }
}
