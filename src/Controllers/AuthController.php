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

            if ($action === 'upload_avatar') {
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['avatar'];
                    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

                    if (!in_array($ext, $allowed, true) || !in_array($mime, $allowedMimes, true)) {
                        flash('danger', 'Invalid format! Only JPG, JPEG, PNG, and WEBP images are permitted.');
                    } elseif ($file['size'] > 2 * 1024 * 1024) {
                        flash('danger', 'File size exceeds 2MB limit! Please choose a smaller photo.');
                    } else {
                        $dir = 'assets/uploads/avatars';
                        $fullDir = BASE_PATH . '/' . $dir;
                        if (!is_dir($fullDir)) {
                            mkdir($fullDir, 0755, true);
                        }

                        $filename = 'avatar_' . $user['id'] . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $dest = $fullDir . '/' . $filename;

                        if (move_uploaded_file($file['tmp_name'], $dest)) {
                            if (!empty($user['avatar']) && file_exists(BASE_PATH . '/' . $user['avatar'])) {
                                @unlink(BASE_PATH . '/' . $user['avatar']);
                            }
                            $relPath = $dir . '/' . $filename;
                            Database::update('users', ['avatar' => $relPath], "id = ?", [$user['id']]);
                            Database::logActivity($user['id'], 'UPDATE_AVATAR', 'PROFILE', 'Updated profile picture');
                            flash('success', 'Profile photo updated successfully!');
                        } else {
                            flash('danger', 'Failed to save uploaded photo.');
                        }
                    }
                } else {
                    flash('danger', 'Please choose a valid photo to upload.');
                }
            } elseif ($action === 'remove_avatar') {
                if (!empty($user['avatar']) && file_exists(BASE_PATH . '/' . $user['avatar'])) {
                    @unlink(BASE_PATH . '/' . $user['avatar']);
                }
                Database::update('users', ['avatar' => null], "id = ?", [$user['id']]);
                Database::logActivity($user['id'], 'REMOVE_AVATAR', 'PROFILE', 'Removed profile picture');
                flash('success', 'Profile photo removed successfully.');
            } elseif ($action === 'change_password') {
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
