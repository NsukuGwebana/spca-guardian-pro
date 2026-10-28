<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Signup Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=TASA+Explorer:wght@400..800&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'TASA Explorer', sans-serif;
            text-decoration: none;
            list-style: none;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(90deg, #e2e2e2, #134f5c);
        }

        .container {
            position: relative;
            width: 850px;
            height: 550px;
            background: #fff;
            margin: 20px;
            border-radius: 30px;
            box-shadow: 0 0 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        form {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 40px;
            height: 100%;
            justify-content: center;
            gap: 15px;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
            width: 50%;
            z-index: 2;
        }

        /* Initial states */
        .register-form-container {
            left: 0;
            transform: translateX(0);
        }
        
        .login-form-container {
            left: 50%;
            transform: translateX(0);
        }

        /* Active states */
        .container.active .login-form-container {
            transform: translateX(0);
        }

        .container.active .register-form-container {
            transform: translateX(-100%);
        }

        .input-group {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .input-box {
            position: relative;
            width: 100%;
        }

        .input-box input {
            width: 100%;
            padding: 13px 50px 13px 20px;
            background: #eee;
            border-radius: 8px;
            border: none;
            outline: none;
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .btn {
            width: 100%;
            height: 48px;
            background: #134f5c;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
        }

        .social-icons {
            display: flex;
            justify-content: center;
            margin: 0; 
        }

        .social-icons a {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
            border: 2px solid #ccc;
            border-radius: 20%;
            font-size: 24px;
            color: #333;
            margin: 0 8px;
            width: 40px;
            height: 40px;
        }

        .toggle-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: all 0.6s ease-in-out;
            border-radius: 150px 0 0 100px;
            z-index: 1000;
        }

        .container.active .toggle-container {
            transform: translateX(-100%);
            border-radius: 0 150px 100px 0;
        }

        .toggle {
            background: linear-gradient(to right, #134f5c, #134f5c);
            height: 100%;
            color: #fff;
            position: relative;
            left: -100%;
            width: 200%;
            transition: all 0.6s ease-in-out;
        }

        .container.active .toggle {
            transform: translateX(50%);
        }

        .toggle-panel {
            position: absolute;
            width: 50%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 0 30px;
            top: 0;
            transition: all 0.6s ease-in-out;
        }

        .toggle-left {
            transform: translateX(-200%);
        }

        .container.active .toggle-left {
            transform: translateX(0);
        }

        .toggle-right {
            right: 0;
            transform: translateX(0);
        }

        .container.active .toggle-right {
            transform: translateX(200%);
        }

        .toggle-panel .btn {
            width: 160px;
            height: 46px;
            background: transparent;
            border: 2px solid #fff;
        }

        /* Back button styles */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 2000;
            background: #134f5c;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: #0d3c46;
            transform: translateY(-2px);
        }

        .back-button i {
            font-size: 18px;
        }

        /* Error message styles */
        .error-container {
            background: #ffebee;
            color: #c62828;
            padding: 16px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 5px solid #c62828;
            text-align: left;
            display: flex;
            align-items: center;
            width: 100%;
            display: none;
        }

        .error-container i {
            margin-right: 10px;
            font-size: 20px;
        }

        .error-message {
            font-size: 14px;
        }

        .error-message p {
            margin: 5px 0;
            color: #c62828;
        }

        .login-link {
            color: #134f5c;
            font-weight: 500;
            margin-top: 10px;
        }

        .login-link:hover {
            text-decoration: underline;
        }
        
        /* Success message styles */
        .success-container {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 16px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 5px solid #2e7d32;
            text-align: left;
            display: flex;
            align-items: center;
            width: 100%;
            display: none;
        }
        
        .success-container i {
            margin-right: 10px;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <div class="container active" id="container">
        <!-- Back Button -->
        <a href="javascript:history.back()" class="back-button">
            <i class="fas fa-arrow-left"></i>
        </a>

        <!-- Register Form -->
        <div class="form-container register-form-container">
            <form action="createSystemUserSysDev.php" method="POST">
                <h1>Create Account</h1>
                
                <div class="input-group">
                    <div class="input-box"><input type="text" name="username" maxlength="35" size="20" placeholder="Username" required></div>

                    <div class="input-box"><input type="password" name="pword" maxlength="35" size="20" placeholder="Password" required></div>

                    <div class="input-box"><input type="text" name="firstname" maxlength="35" size="20" placeholder="Firstname" required></div>

                    <div class="input-box"><input type="text" name="surname" maxlength="35" size="20" placeholder="Surname" required></div>

                    <div class="input-box">
                       <select id="usrole" name="userrole" required style="width: 100%; padding: 13px 20px; background: #eee; border-radius: 8px; border: none; outline: none; font-size: 16px; color: #333; font-weight: 500;">
                           <option value="0" selected>Select role</option>
                           <option value="Administrator">Administrator</option>
                           <option value="Vet">Vet</option>
                           <option value="Volunteer">Volunteer</option>
                       </select>
                    </div>

                </div>
                <button class="btn">Register</button>
            </form>
        </div>

        <!-- Login Form -->
        <div class="form-container login-form-container">
            <form action="" method="POST" id="loginForm">
                <h1>Login</h1>
                
                <!-- Error message container -->
                <div class="error-container" id="errorContainer">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="error-message" id="errorMessage"></div>
                </div>
                
                <!-- Success message container -->
                <div class="success-container" id="successContainer">
                    <i class="fas fa-check-circle"></i>
                    <div class="success-message" id="successMessage"></div>
                </div>
             
                <div class="input-group">
                    <div class="input-box"><input type="text" id="username" name="username" maxlength="35" size="20" placeholder="Username" required></div>
                    <div class="input-box"><input type="password" id="userpassword" name="userpassword" maxlength="35" size="20" placeholder="Password" required></div>
                </div>
                <button class="btn">Login</button>
                <a href="ForgotPasswordform.html" class="login-link">Forgot your password</a>
            </form>
        </div>

        <!-- Toggle Panels -->
        <div class="toggle-container">
            <div class="toggle">
                <div class="toggle-panel toggle-left">
                    <h1>Hello, Friend!</h1>
                    <br>
                    <p>Register with your personal details to use all site features</p>
                    <br>
                    <button class="btn" id="register">Register</button>
                </div>
                <div class="toggle-panel toggle-right">
                    <h1>Welcome Back!</h1>
                    <br>
                    <p>Enter your personal details to use all site features</p>
                    <br>
                    <button class="btn" id="login">Login</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const container = document.getElementById('container');
        const registerBtn = document.getElementById('register');
        const loginBtn = document.getElementById('login');
        const errorContainer = document.getElementById('errorContainer');
        const errorMessage = document.getElementById('errorMessage');
        const successContainer = document.getElementById('successContainer');
        const successMessage = document.getElementById('successMessage');
        const loginForm = document.getElementById('loginForm');

        registerBtn.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.remove("active");
        });

        loginBtn.addEventListener('click', (e) => {
            e.preventDefault();
            container.classList.add("active");
        });

        // Function to show error message
        function showError(message) {
            errorMessage.innerHTML = message;
            errorContainer.style.display = 'flex';
            successContainer.style.display = 'none';
        }

        // Function to show success message
        function showSuccess(message) {
            successMessage.innerHTML = message;
            successContainer.style.display = 'flex';
            errorContainer.style.display = 'none';
        }

        // Handle form submission
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('userpassword').value;
            
            if (!username || !password) {
                showError('Please fill in all fields');
                return;
            }
            
            // For demo purposes, simulate login
            if (username === 'demo' && password === 'demo') {
                showSuccess('Login successful! Redirecting...');
                // In a real scenario, this would be handled by PHP
                setTimeout(() => {
                    window.location.href = 'dashboard.html';
                }, 1500);
            } else {
                showError('Login failed. Invalid username or password.');
            }
        });

        // Simulate PHP error for demonstration
        // In a real implementation, this would come from PHP
        setTimeout(() => {
            // showError('Login failed. Invalid username or password.');
        }, 500);
    </script>
    
    <?php
    // This PHP code would be in your 2login2Sysdev.php file
    // For demonstration, I'm including it here but it should be separated
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Simulate database connection and authentication
        $username = $_POST['username'] ?? '';
        $password = $_POST['userpassword'] ?? '';
        
        // This is just for demonstration - in reality you would query your database
        if ($username === 'admin' && $password === 'password') {
            // Successful login
            echo "<script>showSuccess('Login successful! Redirecting...');";
            echo "setTimeout(() => { window.location.href = 'dashboard.html'; }, 1500);</script>";
        } else {
            // Failed login
            echo "<script>showError('Login failed. Invalid username or password.');</script>";
        }
    }
    ?>
</body>
</html>

