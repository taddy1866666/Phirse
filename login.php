<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phirse - Admin & Seller Login</title>
    <link rel="icon" type="image/png" href="uploads/images/Plogo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"],
        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
        }

        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
        }

        .role-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            color: #666;
        }

        .role-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: transparent;
            color: white;
        }

        .role-btn:hover {
            border-color: #667eea;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn-login:hover {
            transform: scale(1.02);
        }

        .error-message {
            color: #ef4444;
            font-size: 14px;
            margin-top: 10px;
            padding: 10px;
            background: #fee2e2;
            border-radius: 6px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Phirse</h1>
            <p>Admin & Seller Portal</p>
        </div>

        <div class="content">
            <form id="loginForm" method="POST" action="unified-auth.php">
                <div class="role-selector">
                    <button type="button" class="role-btn active" data-role="seller" onclick="selectRole('seller')">
                        <i class="fas fa-store"></i> Seller
                    </button>
                    <button type="button" class="role-btn" data-role="admin" onclick="selectRole('admin')">
                        <i class="fas fa-crown"></i> Admin
                    </button>
                </div>

                <div class="form-group">
                    <label for="user_id" id="label-user-id">Organization Name</label>
                    <input type="text" id="user_id" name="user_id" placeholder="Enter organization name" required>
                    <div class="help-text" id="help-text-seller">e.g., COMELEC, Finance Committee</div>
                    <div class="help-text" id="help-text-admin" style="display: none;">e.g., admin, ssc_head</div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="error-message show">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-login">Login</button>

                <div class="back-link">
                    <a href="/">← Back to Home</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectRole(role) {
            // Update button states
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelector(`[data-role="${role}"]`).classList.add('active');

            // Update label and placeholder
            const userIdInput = document.getElementById('user_id');
            const labelUserid = document.getElementById('label-user-id');
            const helpTextSeller = document.getElementById('help-text-seller');
            const helpTextAdmin = document.getElementById('help-text-admin');

            if (role === 'admin') {
                labelUserid.textContent = 'Admin Username';
                userIdInput.placeholder = 'Enter admin username';
                helpTextSeller.style.display = 'none';
                helpTextAdmin.style.display = 'block';
            } else {
                labelUserid.textContent = 'Organization Name';
                userIdInput.placeholder = 'Enter organization name';
                helpTextSeller.style.display = 'block';
                helpTextAdmin.style.display = 'none';
            }

            // Clear input
            userIdInput.value = '';
            userIdInput.focus();
        }
    </script>
</body>
</html>