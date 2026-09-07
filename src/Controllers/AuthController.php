<?php
/**
 * Auth Controller
 */

require_once __DIR__ . '/../Auth.php';
require_once __DIR__ . '/../Helpers.php';
require_once __DIR__ . '/../Models/Employee.php';

class AuthController {
    public function login(): void {
        if (Auth::check()) {
            redirect('dashboard');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('login');
            }

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                flash('danger', 'Please provide both email address and password.');
                redirect('login');
            }

            if (Auth::attempt($email, $password)) {
                flash('success', 'Welcome back, ' . htmlspecialchars($_SESSION['user_name']) . '!');
                redirect('dashboard');
            } else {
                flash('danger', 'Invalid email or password. Please try again.');
                redirect('login');
            }
        }

        require_once BASE_PATH . '/views/auth/login.php';
    }

    public function logout(): void {
        Auth::logout();
        flash('info', 'You have been logged out securely.');
        redirect('login');
    }

    public function profile(): void {
        Auth::requireLogin();
        $user = Auth::user();
        $employee = Employee::findByUserId($user['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!validate_csrf()) {
                redirect('profile');
            }

            $action = $_POST['action'] ?? 'update_profile';

            if ($action === 'change_password') {
                $currentPass = $_POST['current_password'] ?? '';
                $newPass = $_POST['new_password'] ?? '';
                $confirmPass = $_POST['confirm_password'] ?? '';

                $dbUser = Database::fetchOne("SELECT password FROM users WHERE id = ?", [$user['id']]);
                if (!password_verify($currentPass, $dbUser['password'])) {
                    flash('danger', 'Current password entered is incorrect!');
                } elseif (strlen($newPass) < 6) {
                    flash('danger', 'New password must be at least 6 characters long!');
                } elseif ($newPass !== $confirmPass) {
                    flash('danger', 'New password and confirmation do not match!');
                } else {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    Database::update('users', ['password' => $newHash], "id = ?", [$user['id']]);
                    flash('success', 'Password updated successfully!');
                }
            } else {
                // Update contact details
                if ($employee) {
                    Database::update('employees', [
                        'phone' => trim($_POST['phone'] ?? ''),
                        'address' => trim($_POST['address'] ?? ''),
                        'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
                        'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? '')
                    ], "id = ?", [$employee['id']]);
                    flash('success', 'Profile contact details updated successfully!');
                }
            }
            redirect('profile');
        }

        require_once BASE_PATH . '/views/auth/profile.php';
    }
}
