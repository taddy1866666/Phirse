<?php
session_start();
require_once 'db/config.php';

$pageTitle = 'Login';
include 'includes/header.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirectWithMessage('index.php', 'You are already logged in!', 'info');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_number = trim($_POST['student_number']);
    $password = $_POST['password'];

    if (empty($student_number) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        try {
            // Check students table first
            $stmt = $conn->prepare("SELECT id, student_number, student_name, password FROM students WHERE student_number = ?");
            $stmt->bind_param("s", $student_number);
            $stmt->execute();
            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            if ($student && password_verify($password, $student['password'])) {
                // Student login successful
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_name'] = $student['student_name'];
                $_SESSION['student_number'] = $student['student_number'];
                $_SESSION['role'] = 'student';

                // Log activity
                logActivity('login', 'Student logged in successfully');

                redirectWithMessage('index.php', 'Welcome back, ' . $student['student_name'] . '!', 'success');
            } else {
                $error = 'Invalid student number or password';
            }

            $stmt->close();
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - PHIRSE</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease-out;
        }

        .loading-content {
            text-align: center;
            color: white;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto 20px;
        }

        .loading-text {
            font-size: 1.2rem;
            font-weight: 500;
            letter-spacing: 1px;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }

        .loading-overlay.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        :root {
            --primary-color: #667eea;
            --primary-dark: #764ba2;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --bg-primary: #F8FAFC;
            --bg-secondary: #F1F5F9;
            --error-color: #EF4444;
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 24px;
            padding: 48px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
            animation: containerFadeIn 1s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        @keyframes containerFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-text {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 3.5rem;
            font-weight: 800;
            text-decoration: none;
            display: inline-block;
            letter-spacing: -1px;
            animation: fadeInDown 0.8s ease-out;
        }

        .login-title {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-top: 8px;
            margin-bottom: 32px;
            opacity: 0.9;
            animation: fadeInUp 0.8s ease-out 0.2s both;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }

        .input-icon {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-icon input {
            width: 100%;
            padding: 16px 48px 16px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 12px;
            font-size: 16px;
            color: var(--text-primary);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: var(--bg-secondary);
            outline: none;
            animation: inputFadeIn 0.6s ease-out forwards;
        }

        .input-icon i {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94A3B8;
            font-size: 18px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            animation: iconFadeIn 0.6s ease-out forwards;
        }
        
        #password-toggle {
            cursor: pointer;
            pointer-events: auto;
        }

        #password-toggle:hover {
            color: var(--primary-color);
        }

        #password-toggle::after {
            content: attr(title);
            position: absolute;
            bottom: -30px;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.2s ease;
            pointer-events: none;
            white-space: nowrap;
        }

        #password-toggle:hover::after {
            opacity: 1;
        }

        /* Password toggle icon styling */
        #password-toggle {
            cursor: pointer;
            pointer-events: auto;
        }

        #password-toggle:hover {
            color: var(--primary-color);
        }

        .input-icon input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            background-color: white;
        }

        .input-icon input:focus + i {
            color: var(--primary-color);
            transform: translateY(-50%) scale(1.1);
        }

        @keyframes inputFadeIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes iconFadeIn {
            from {
                opacity: 0;
                transform: translate(10px, -50%);
            }
            to {
                opacity: 1;
                transform: translate(0, -50%);
            }
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        .input-icon {
            position: relative;
        }

        .input-icon i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
            transition: color 0.3s ease;
            pointer-events: none;
        }

        .input-icon input {
            padding-left: 48px;
            width: 100%;
        }

        .login-btn {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
            animation: buttonFadeIn 0.8s ease-out 0.4s both;
        }

        .login-btn .btn-spinner {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .login-btn.loading {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            opacity: 0.8;
            cursor: not-allowed;
            transform: scale(0.98);
        }

        .login-btn.loading .btn-text,
        .login-btn.loading .btn-icon {
            opacity: 0;
        }

        .login-btn.loading .btn-spinner {
            display: block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35);
            filter: brightness(1.05);
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn:active {
            transform: translateY(1px);
            filter: brightness(0.95);
        }

        .login-btn i {
            transition: transform 0.3s ease;
        }

        .login-btn:hover i {
            transform: translateX(3px);
        }

        @keyframes buttonFadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }


        .back-home {
            text-align: center;
            margin-top: 24px;
        }

        .back-home a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .back-home a:hover {
            color: var(--primary-color);
            background-color: var(--bg-secondary);
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        .alert i {
            font-size: 18px;
        }

        .alert.error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
            border: 1px solid #fcc;
            color: #c33;
        }

        .alert.success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            margin-top: 12px;
            z-index: 2;
        }

        .password-toggle:hover {
            color: #333;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .login-container {
                padding: 35px 25px;
            }

            .logo-text {
                font-size: 2.2rem;
            }

            .login-title {
                font-size: 1.3rem;
            }

            .form-group input {
                padding: 14px 18px;
                font-size: 15px;
            }

            .login-btn {
                padding: 14px;
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0;
            }

            .login-container {
                padding: 24px 20px;
                margin: 0;
                border-radius: 16px;
                width: 100%;
                max-width: 360px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .logo-text {
                font-size: 2.5rem;
                margin-bottom: 8px;
            }

            .login-title {
                font-size: 1.1rem;
                margin-bottom: 24px;
                color: var(--text-secondary);
            }

            .form-group {
                margin-bottom: 20px;
            }

            .form-group label {
                font-size: 14px;
                margin-bottom: 8px;
                display: block;
                color: var(--text-secondary);
            }

            .input-icon {
                position: relative;
                margin-bottom: 4px;
            }

            .input-icon input {
                width: 100%;
                padding: 14px 40px;
                font-size: 15px;
                border: 1px solid #E2E8F0;
                border-radius: 12px;
                background-color: #F8FAFC;
            }

            .input-icon i {
                position: absolute;
                top: 50%;
                transform: translateY(-50%);
                color: var(--text-secondary);
                font-size: 16px;
            }

            .input-icon i#password-toggle:hover {
                color: var(--primary-color);
            }

            .input-icon i#password-toggle {
                cursor: pointer;
                padding: 8px;
                z-index: 1;
            }

            .login-btn {
                width: 100%;
                padding: 14px;
                font-size: 15px;
                margin-top: 8px;
                border-radius: 12px;
            }

            .alert {
                padding: 12px;
                font-size: 13px;
                margin-bottom: 16px;
            }

            .back-home {
                margin-top: 20px;
            }

            .back-home a {
                font-size: 14px;
                color: var(--text-secondary);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
        }

        @media (max-width: 360px) {
            .login-container {
                padding: 25px 15px;
            }

            .logo-text {
                font-size: 1.75rem;
            }

            .login-title {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading PHIRSE...</div>
        </div>
    </div>
    <div class="login-container">
        <div class="logo-section">
            <a href="index.php" class="logo-text">PHIRSE</a>
            <div class="login-title">Student Portal</div>
        </div>

        <?php if ($error): ?>
            <div class="alert error">
                <i class="fas fa-circle-exclamation"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="student_number">Student Number</label>
                <div class="input-icon">
                    <input type="text" name="student_number" id="student_number" required
                           value="<?php echo htmlspecialchars($_POST['student_number'] ?? ''); ?>"
                           placeholder="Enter your student number">
                    <i class="fas fa-user"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <input type="password" name="password" id="password" required placeholder="Enter your password">
                    <i class="fas fa-lock" style="left: 14px;"></i>
                    <i class="fas fa-eye" id="password-toggle" style="right: 14px; left: auto; cursor: pointer;" onclick="togglePassword()"></i>
                </div>
            </div>

            <button type="submit" class="login-btn" id="loginButton">
                <span class="btn-text"><i class="fas fa-sign-in-alt"></i> Login</span>
                <div class="btn-spinner"></div>
            </button>
        </form>


        <div class="back-home">
            <a href="index.php">
                <i class="fas fa-arrow-left"></i>
                Back to Home
            </a>
        </div>
    </div>

    <script>
        // Initial page load animation
        window.addEventListener('load', function() {
            setTimeout(function() {
                document.querySelector('.loading-overlay').classList.add('fade-out');
                setTimeout(function() {
                    document.querySelector('.loading-overlay').style.display = 'none';
                }, 500);
            }, 1000);
        });

        // Login form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const loginBtn = document.getElementById('loginButton');
            const studentNumber = document.getElementById('student_number').value;
            const password = document.getElementById('password').value;

            if (studentNumber && password) {
                loginBtn.classList.add('loading');
            }
        });

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-lock');
                toggleIcon.classList.add('fa-lock-open');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-lock-open');
                toggleIcon.classList.add('fa-lock');
            }
        }

        // Auto-focus first input
        document.getElementById('student_number').focus();
    </script>
</body>
</html>