<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Advancells HRMS</title>
    <!-- Official Favicon -->
    <link rel="icon" type="image/png" href="<?= url('assets/img/fav.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= url('assets/img/fav.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('assets/img/fav.png') ?>">
    
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <style>
        body {
            background: #090d16;
            background-image: 
                radial-gradient(at 10% 15%, rgba(147, 32, 108, 0.25) 0px, transparent 55%),
                radial-gradient(at 90% 85%, rgba(13, 148, 136, 0.2) 0px, transparent 55%),
                radial-gradient(at 50% 50%, rgba(2, 132, 199, 0.12) 0px, transparent 65%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow-x: hidden;
        }

        .login-glow-orb {
            position: absolute;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(147, 32, 108, 0.15) 0%, transparent 70%);
            top: 10%;
            left: 15%;
            filter: blur(40px);
            pointer-events: none;
        }

        .login-wrapper {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 10;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border-radius: var(--radius-xl);
            padding: 44px 38px 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            animation: fadeInCard 0.4s ease-out;
        }

        @keyframes fadeInCard {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-brand {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo-container {
            display: inline-block;
            background: #ffffff;
            border-radius: var(--radius-lg);
            padding: 14px 22px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border: 1px solid #f1f5f9;
            margin-bottom: 16px;
            transition: transform 0.2s ease;
        }

        .brand-logo-container:hover {
            transform: scale(1.02);
        }

        .brand-logo-container img {
            max-height: 58px;
            width: auto;
            display: block;
        }

        .login-brand h1 {
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
        }

        .login-brand p {
            color: #64748b;
            font-size: 13.5px;
            font-weight: 500;
        }

        .demo-roles {
            margin-top: 26px;
            padding-top: 20px;
            border-top: 1px dashed #e2e8f0;
        }

        .demo-roles-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.06em;
            margin-bottom: 12px;
            text-align: center;
        }

        .demo-btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .demo-role-btn {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: var(--radius-md);
            padding: 9px 12px;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-main);
        }

        .demo-role-btn:hover {
            background: #fdf4ff;
            border-color: #f0abfc;
            color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(147, 32, 108, 0.12);
        }
    </style>
</head>
<body>

<div class="login-glow-orb"></div>

<div class="login-wrapper">
    <div class="login-card">
        <div class="login-brand">
            <div class="brand-logo-container">
                <img src="<?= url('assets/img/logo.png') ?>" alt="Advancells Group">
            </div>
            <h1>HRMS Portal Login</h1>
            <p>Enterprise Human Resource Management</p>
        </div>

        <!-- Flash alerts -->
        <?php foreach (get_flash() as $msg): ?>
            <div class="alert alert-<?= e($msg['type']) ?>" style="margin-bottom: 20px;">
                <span><?= e($msg['message']) ?></span>
            </div>
        <?php endforeach; ?>

        <form action="<?= url('login') ?>" method="POST" id="loginForm">
            <?= csrf_field() ?>

            <div class="form-group">
                <label class="form-label" for="email">Work Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="name@advancells.com" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Account Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; padding: 13px; font-size: 14.5px; margin-top: 6px;">
                Sign In to Portal →
            </button>
        </form>

        <div class="demo-roles">
            <div class="demo-roles-title">⚡ Quick Switch Demo Accounts</div>
            <div class="demo-btn-group">
                <button type="button" class="demo-role-btn" onclick="fillCredentials('admin@advancells.com', 'Admin@123')">
                    👑 <strong>Super Admin</strong>
                </button>
                <button type="button" class="demo-role-btn" onclick="fillCredentials('hr@advancells.com', 'Hr@123')">
                    💼 <strong>HR Admin</strong>
                </button>
                <button type="button" class="demo-role-btn" onclick="fillCredentials('manager@advancells.com', 'Manager@123')">
                    🔬 <strong>Team Manager</strong>
                </button>
                <button type="button" class="demo-role-btn" onclick="fillCredentials('employee@advancells.com', 'Emp@123')">
                    🧪 <strong>Employee (ESS)</strong>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
    document.getElementById('loginForm').submit();
}
</script>

</body>
</html>
