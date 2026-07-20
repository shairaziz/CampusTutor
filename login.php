<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'dbconnect.php'; 

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $result = $conn->query("SELECT * FROM User WHERE email='$email' AND password='$password'");
    
    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "❌ Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CampusTutor - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', 'Poppins', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            background-image: url('img/background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }

        /* Blur overlay - ONLY ADDED THIS */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: inherit;
            filter: blur(4px);
            z-index: -1;
        }

        /* Top Left Branding */
        .branding {
            position: absolute;
            top: 80px;
            left: 60px;
            z-index: 2;
        }

        .branding h1 {
            font-size: 58px;
            font-weight: 700;
            background: linear-gradient(135deg, #f3e8f7 0%, #ffffff 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: -0.5px;
            margin-bottom: 5px;
		    -webkit-text-stroke: 1px #6a1b9a;
        }

        .branding p {
            font-size: 19px;
            color: #f8f9fa;
            font-weight: 500;
            letter-spacing: 1px;
        }

        /* Quick Login - Top Right */
        .quick-login {
            position: absolute;
            top: 20px;
            right: 60px;
            z-index: 2;
            background: transparent;
            border-radius: 20px;
            padding: 10px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            text-align: center;
        }

        .quick-login p {
            font-size: 11px;
            color: #7a5a8a;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .quick-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }

        .quick-buttons a {
            background: #f3e8f7;
            color: #6a1b9a;
            padding: 6px 14px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.2s;
            cursor: pointer;
        }

        .quick-buttons a:hover {
            background: #9c27b0;
            color: white;
        }

        /* Login Card - Centered */
        .login-container {
            position: absolute;
            top: 30%;
			bottom: 70%
            left: 50%;
			right: 50%
            transform: translate(-50%, -50%);
            z-index: 2;
            width: 100%;
            display: flex;
            justify-content: center;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 28px;
            padding: 45px 40px;
            width: 420px;
            max-width: 90%;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(156, 39, 176, 0.2);
        }

        .login-card h2 {
            text-align: center;
            font-size: 28px;
            font-weight: 600;
            background: linear-gradient(135deg, #6a1b9a 0%, #9c27b0 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 30px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4a2a5a;
            margin-bottom: 6px;
            letter-spacing: 0.5px;
        }

        .input-group input {
            width: 100%;
            padding: 14px 16px;
            font-size: 15px;
            border: 2px solid #e8ddf2;
            border-radius: 14px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        .input-group input:focus {
            outline: none;
            border-color: #9c27b0;
            box-shadow: 0 0 0 3px rgba(156, 39, 176, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6a1b9a 0%, #9c27b0 100%);
            border: none;
            border-radius: 14px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            font-family: inherit;
        }

        .login-btn:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(106, 27, 154, 0.3);
        }

        .error-message {
            background: #fdecea;
            border-left: 4px solid #dc3545;
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #c0392b;
            font-size: 13px;
        }

        @media (max-width: 768px) {
            .branding { top: 20px; left: 20px; }
            .branding h1 { font-size: 35px; }
            .branding p { font-size: 12px; }
            .quick-login { top: 20px; right: 20px; padding: 6px 12px; }
            .quick-buttons a { font-size: 9px; padding: 4px 8px; }
            .login-card { padding: 25px 20px; }
        }
    </style>
</head>
<body>

    <!-- Top Left Branding -->
    <div class="branding">
        <h1>CampusTutor : A Peer Tutoring System</h1>
        <p></p>
    </div>

    <!-- Quick Login - Top Right -->
    <div class="quick-login">
        
        <div class="quick-buttons">
            <a onclick="fillEmail('student')">student</a>
            <a onclick="fillEmail('tutor')">student-tutor</a>
            <a onclick="fillEmail('professor')">professor</a>
            <a onclick="fillEmail('admin')">admin</a>
        </div>
    </div>

    <!-- Centered Login Card -->
    <div class="login-container">
        <div class="login-card">
            <h2>🎓 CampusTutor</h2>

            <?php if($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label>Email</label>
                    <input type="email" name="email" id="email" placeholder="student@university.edu" required>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="login-btn">Sign In →</button>
            </form>
        </div>
    </div>

    <script>
        function fillEmail(role) {
            var emailField = document.getElementById('email');
            var currentEmail = emailField.value;
            var baseEmail = currentEmail.split('@')[0];
            
            if (baseEmail === '' || currentEmail.includes('@')) {
                emailField.value = '@' + role + '.com';
            } else {
                emailField.value = baseEmail + '@' + role + '.com';
            }
        }
    </script>
</body>
</html>