<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Advancells HRMS</title>
    <link rel="icon" type="image/png" href="<?= url('assets/img/fav.png') ?>">
    <link rel="shortcut icon" type="image/png" href="<?= url('assets/img/fav.png') ?>">
    <link rel="apple-touch-icon" href="<?= url('assets/img/fav.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <style>
        :root {
            --brand-plum: #a4247a;
            --brand-plum-deep: #7d1b5d;
            --brand-teal: #5aa89f;
            --brand-teal-dark: #3d8a82;
            --brand-cyan: #269fc8;
            --brand-dark: #0b1220;
            --ink: #0f172a;
            --muted: #64748b;
            --line: #e2e8f0;
            --radius-xl: 24px;
            --font-heading: "Plus Jakarta Sans", Inter, system-ui, sans-serif;
            --font-main: Inter, system-ui, sans-serif;
        }

        * { box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            margin: 0;
            font-family: var(--font-main);
            color: var(--ink);
            background: #05080c;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background-image:
                radial-gradient(at 12% 10%, rgba(164, 36, 122, 0.34) 0px, transparent 52%),
                radial-gradient(at 88% 88%, rgba(90, 168, 159, 0.28) 0px, transparent 52%),
                radial-gradient(at 50% 45%, rgba(38, 159, 200, 0.16) 0px, transparent 62%);
            pointer-events: none;
            z-index: 0;
        }

        .grid-overlay {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.035) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse at center, black 18%, transparent 78%);
            pointer-events: none;
            z-index: 1;
        }

        .login-glow-orb,
        .login-glow-orb-2 {
            position: absolute;
            border-radius: 50%;
            filter: blur(56px);
            pointer-events: none;
            z-index: 1;
            animation: drift 14s ease-in-out infinite;
        }

        .login-glow-orb {
            width: 460px;
            height: 460px;
            background: radial-gradient(circle, rgba(164, 36, 122, 0.28) 0%, transparent 70%);
            top: -8%;
            left: 2%;
        }

        .login-glow-orb-2 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(90, 168, 159, 0.22) 0%, transparent 70%);
            bottom: -10%;
            right: 0;
            animation-delay: -6s;
        }

        @keyframes drift {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(18px, -14px) scale(1.06); }
        }

        .login-wrapper {
            width: 100%;
            max-width: 980px;
            position: relative;
            z-index: 10;
        }

        .login-shell {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(28px);
            -webkit-backdrop-filter: blur(28px);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: 0 30px 60px -18px rgba(4, 7, 7, 0.55), 0 0 0 1px rgba(255, 255, 255, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.4);
            animation: fadeInCard 0.45s ease-out;
            min-height: 560px;
        }

        @keyframes fadeInCard {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .brand-pane {
            position: relative;
            padding: 40px 36px;
            color: #fff;
            background:
                linear-gradient(165deg, rgba(7, 10, 16, 0.28), rgba(7, 10, 16, 0.55)),
                linear-gradient(160deg, #1a0d18 0%, #0b1220 55%, #0a1a1c 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .brand-pane::before {
            content: "";
            position: absolute;
            inset: auto -40px -60px auto;
            width: 280px;
            height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(164, 36, 122, 0.45), transparent 70%);
            pointer-events: none;
        }

        .brand-pane::after {
            content: "";
            position: absolute;
            top: -60px;
            left: -20px;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.28), transparent 70%);
            pointer-events: none;
        }

        .brand-pane-inner { position: relative; z-index: 2; }

        .brand-logo-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            margin-bottom: 28px;
        }

        .brand-logo-container img {
            max-height: 110px;
            width: auto;
            display: block;
        }

        .brand-pane h1 {
            font-family: var(--font-heading);
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.04em;
            line-height: 1.15;
            margin: 0 0 12px;
        }

        .brand-pane p.lead {
            margin: 0;
            color: rgba(255,255,255,0.74);
            font-size: 14.5px;
            line-height: 1.55;
            max-width: 360px;
        }

        .brand-points {
            list-style: none;
            margin: 28px 0 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }

        .brand-points li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13.5px;
            color: rgba(255,255,255,0.86);
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: linear-gradient(90deg, var(--brand-plum), var(--brand-teal));
            flex-shrink: 0;
        }

        .brand-foot {
            position: relative;
            z-index: 2;
            font-size: 12px;
            color: rgba(255,255,255,0.55);
            margin-top: 32px;
        }

        .form-pane {
            padding: 36px 34px 28px;
            background: #fff;
        }

        .form-pane h2 {
            font-family: var(--font-heading);
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.03em;
            margin: 0 0 4px;
            color: var(--brand-dark);
        }

        .form-pane .sub {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 13.5px;
        }

        .alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-size: 13.5px;
            line-height: 1.45;
            border: 1px solid transparent;
        }

        .alert-success { background: #ecfdf5; border-color: #a7f3d0; color: #065f46; }
        .alert-error, .alert-danger { background: #fef2f2; border-color: #fecaca; color: #991b1b; }
        .alert-warning { background: #fffbeb; border-color: #fde68a; color: #92400e; }
        .alert-info { background: #eff6ff; border-color: #bfdbfe; color: #1e40af; }

        .form-group { margin-bottom: 16px; }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 7px;
        }

        .input-shell { position: relative; }

        .input-shell .field-icon {
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            color: #94a3b8;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            height: 48px;
            border: 1px solid #dbe3ee;
            border-radius: 12px;
            background: #f8fafc;
            padding: 0 44px 0 42px;
            font-size: 14.5px;
            font-family: var(--font-main);
            color: var(--ink);
            outline: none;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .form-control::placeholder { color: #94a3b8; }
        .form-control:hover { border-color: #c5d0de; background: #fff; }
        .form-control:focus {
            background: #fff;
            border-color: var(--brand-plum);
            box-shadow: 0 0 0 4px rgba(164, 36, 122, 0.12);
        }

        .toggle-pass {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            border: 0;
            background: transparent;
            color: #64748b;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-pass:hover { background: #eef2f7; color: var(--ink); }

        .btn-primary {
            width: 100%;
            height: 50px;
            margin-top: 6px;
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            color: #fff;
            font-family: var(--font-heading);
            font-size: 15px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--brand-plum) 0%, var(--brand-plum-deep) 100%);
            box-shadow: 0 10px 22px rgba(164, 36, 122, 0.28);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.16s ease, box-shadow 0.16s ease, filter 0.16s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
            box-shadow: 0 14px 26px rgba(164, 36, 122, 0.34);
        }

        .btn-primary.is-loading { pointer-events: none; opacity: 0.88; }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.35);
            border-top-color: #fff;
            border-radius: 50%;
            display: none;
            animation: spin 0.7s linear infinite;
        }

        .btn-primary.is-loading .spinner { display: block; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .demo-roles {
            margin-top: 22px;
            padding-top: 16px;
            border-top: 1px dashed var(--line);
        }

        .demo-roles-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: 0.08em;
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
            border-radius: 12px;
            padding: 10px 11px;
            font-size: 12.5px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all 0.18s ease;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: var(--font-main);
        }

        .demo-role-btn span.role-ico {
            width: 28px;
            height: 28px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .demo-role-btn small {
            display: block;
            font-size: 10.5px;
            font-weight: 500;
            color: #94a3b8;
            margin-top: 1px;
        }

        .role-admin span.role-ico { background: #fdf2f8; }
        .role-hr span.role-ico { background: #f0fdfa; }
        .role-mgr span.role-ico { background: #f0f9ff; }
        .role-emp span.role-ico { background: #f1f5f9; }

        .demo-role-btn.role-admin:hover {
            background: #fdf2f8; border-color: #f0abfc; color: var(--brand-plum);
            transform: translateY(-2px); box-shadow: 0 6px 14px rgba(164, 36, 122, 0.14);
        }
        .demo-role-btn.role-hr:hover {
            background: #f0fdfa; border-color: #99f6e4; color: var(--brand-teal-dark);
            transform: translateY(-2px); box-shadow: 0 6px 14px rgba(90, 168, 159, 0.14);
        }
        .demo-role-btn.role-mgr:hover {
            background: #f0f9ff; border-color: #bae6fd; color: var(--brand-cyan);
            transform: translateY(-2px); box-shadow: 0 6px 14px rgba(38, 159, 200, 0.14);
        }
        .demo-role-btn.role-emp:hover {
            background: #f8fafc; border-color: #cbd5e1; color: var(--brand-dark);
            transform: translateY(-2px); box-shadow: 0 6px 14px rgba(4, 7, 7, 0.1);
        }

        .secure-note {
            margin-top: 16px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            color: #94a3b8;
            font-size: 11.5px;
            text-align: center;
        }

        .secure-line {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .copy-line {
            color: #94a3b8;
            letter-spacing: 0.01em;
        }

        @media (max-width: 860px) {
            .login-shell {
                grid-template-columns: 1fr;
                min-height: 0;
            }
            .brand-pane { padding: 28px 24px 22px; }
            .brand-pane h1 { font-size: 24px; }
            .brand-points { display: none; }
            .form-pane { padding: 26px 22px 22px; }
        }

        @media (max-width: 480px) {
            .demo-btn-group { grid-template-columns: 1fr; }
            body { padding: 12px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .login-glow-orb, .login-glow-orb-2, .login-shell { animation: none; }
        }
    </style>
</head>
<body>

<div class="grid-overlay"></div>
<div class="login-glow-orb"></div>
<div class="login-glow-orb-2"></div>

<div class="login-wrapper">
    <div class="login-shell">
        <aside class="brand-pane">
            <div class="brand-pane-inner">
                <div class="brand-logo-container">
                    <img src="<?= url('assets/img/logo.png') ?>" alt="Advancells Group">
                </div>
                <h1>HRMS Portal</h1>
                <p class="lead">Enterprise Human Resource Management for Advancells Group — attendance, payroll, and people ops in one place.</p>
                <ul class="brand-points">
                    <li><span class="dot"></span> Role-based access for Admin, HR, Managers & ESS</li>
                    <li><span class="dot"></span> Secure session with encrypted credentials</li>
                    <li><span class="dot"></span> Quick switch demo accounts for walkthroughs</li>
                </ul>
            </div>
            <div class="brand-foot">© 2026 Advancells. All rights reserved.</div>
        </aside>

        <section class="form-pane">
            <h2>Sign in</h2>
            <p class="sub">Use your work email to continue</p>

            <?php foreach (get_flash() as $msg): ?>
                <div class="alert alert-<?= e($msg['type']) ?>">
                    <span><?= e($msg['message']) ?></span>
                </div>
            <?php endforeach; ?>

            <form action="<?= url('login') ?>" method="POST" id="loginForm">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="email">Work Email Address</label>
                    <div class="input-shell">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.7"/>
                            <path d="m5 7 7 6 7-6" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <input type="email" name="email" id="email" class="form-control" placeholder="name@advancells.com" autocomplete="username" required autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Account Password</label>
                    <div class="input-shell">
                        <svg class="field-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.7"/>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                        </svg>
                        <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" autocomplete="current-password" required>
                        <button type="button" class="toggle-pass" id="togglePass" aria-label="Show password">
                            <svg id="eyeOpen" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M2.5 12s3.5-7 9.5-7 9.5 7 9.5 7-3.5 7-9.5 7-9.5-7-9.5-7Z" stroke="currentColor" stroke-width="1.7"/>
                                <circle cx="12" cy="12" r="2.6" stroke="currentColor" stroke-width="1.7"/>
                            </svg>
                            <svg id="eyeClosed" width="18" height="18" viewBox="0 0 24 24" fill="none" style="display:none;">
                                <path d="M3 3l18 18M10.6 10.6A2.6 2.6 0 0 0 12 14.6M7.1 7.3C4.7 8.8 3 12 3 12s3.5 7 9.5 7c1.8 0 3.4-.5 4.8-1.3M16.8 16.2C19.1 14.7 21 12 21 12s-3.5-7-9.5-7c-.7 0-1.4.1-2 .2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-copy">Sign In to Portal</span>
                </button>
            </form>

            <?php if (defined('APP_ENV') && APP_ENV === 'development'): ?>
            <div class="demo-roles">
                <div class="demo-roles-title">Quick Switch Demo Accounts</div>
                <div class="demo-btn-group">
                    <button type="button" class="demo-role-btn role-admin" onclick="fillCredentials('admin@advancells.com', 'Admin@123')">
                        <span class="role-ico">👑</span>
                        <span>Super Admin<small>Full control</small></span>
                    </button>
                    <button type="button" class="demo-role-btn role-hr" onclick="fillCredentials('hr@advancells.com', 'Hr@123')">
                        <span class="role-ico">💼</span>
                        <span>HR Admin<small>People ops</small></span>
                    </button>
                    <button type="button" class="demo-role-btn role-mgr" onclick="fillCredentials('manager@advancells.com', 'Manager@123')">
                        <span class="role-ico">🔬</span>
                        <span>Team Manager<small>Team view</small></span>
                    </button>
                    <button type="button" class="demo-role-btn role-emp" onclick="fillCredentials('employee@advancells.com', 'Emp@123')">
                        <span class="role-ico">🧪</span>
                        <span>Employee (ESS)<small>Self service</small></span>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="secure-note">
                <span class="secure-line">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <rect x="5" y="11" width="14" height="9" rx="2" stroke="currentColor" stroke-width="1.8"/>
                        <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                    Encrypted session · Authorized personnel only
                </span>
                <span class="copy-line">© 2026 Advancells. All rights reserved.</span>
            </div>
        </section>
    </div>
</div>

<script>
function fillCredentials(email, pass) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = pass;
    document.getElementById('loginForm').submit();
}

(function () {
    var toggle = document.getElementById('togglePass');
    var input = document.getElementById('password');
    var open = document.getElementById('eyeOpen');
    var closed = document.getElementById('eyeClosed');
    if (toggle && input) {
        toggle.addEventListener('click', function () {
            var shown = input.type === 'text';
            input.type = shown ? 'password' : 'text';
            open.style.display = shown ? 'block' : 'none';
            closed.style.display = shown ? 'none' : 'block';
            toggle.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
        });
    }

    var form = document.getElementById('loginForm');
    var btn = document.getElementById('submitBtn');
    if (form && btn) {
        form.addEventListener('submit', function () {
            btn.classList.add('is-loading');
        });
    }
})();
</script>

</body>
</html>