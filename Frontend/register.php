<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Oracle Monitor</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
    <link rel="stylesheet" href="style/style.css"> <!-- Reusing login page style -->
    <style>
        .form h2 {
            color: #fff;
            font-weight: 500;
            text-align: center;
            letter-spacing: 0.1em;
            margin-bottom: 20px;
        }
        .form .button .boton { /* Ensure button styling is consistent */
            background: #45f3ff;
            border: none;
            outline: none;
            padding: 9px 25px;
            width: 100px;
            margin-top: 10px;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
        }
        #messageArea {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 4px;
        }
        #messageArea.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        #messageArea.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="form">
            <img class="imagen" src="img/logo.png" style="margin-bottom: 10px;">
            <h2>Create Account</h2>

            <div id="messageArea" style="display:none;"></div>

            <form id="registrationForm" method="POST" action="../server/register_handler.php">
                <div class="inputbox">
                    <input type="text" required="required" name="username" autocomplete="username">
                    <span>Username</span>
                    <i></i>
                </div>

                <div class="inputbox">
                    <input type="email" required="required" name="email" autocomplete="email">
                    <span>Email</span>
                    <i></i>
                </div>

                <div class="inputbox">
                    <input type="password" required="required" name="password" id="password" autocomplete="new-password">
                    <span>Password</span>
                    <i></i>
                </div>

                <div class="inputbox">
                    <input type="password" required="required" name="confirm_password" id="confirm_password" autocomplete="new-password">
                    <span>Confirm Password</span>
                    <i></i>
                </div>

                <div class="links">
                    <a href="index.html">Already have an account? Login</a>
                </div>

                <div class="button" style="text-align: center;">
                    <button type="submit" class="boton">Register</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Basic client-side validation for password confirmation
        const form = document.getElementById('registrationForm');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const messageArea = document.getElementById('messageArea');

        form.addEventListener('submit', function(event) {
            messageArea.style.display = 'none';
            messageArea.textContent = '';
            messageArea.className = '';


            if (password.value !== confirmPassword.value) {
                event.preventDefault(); // Stop form submission
                messageArea.textContent = 'Passwords do not match!';
                messageArea.className = 'error';
                messageArea.style.display = 'block';
                confirmPassword.focus();
                return false;
            }
            // Simple password complexity check (example: at least 6 chars)
            if (password.value.length < 6) {
                event.preventDefault();
                messageArea.textContent = 'Password must be at least 6 characters long.';
                messageArea.className = 'error';
                messageArea.style.display = 'block';
                password.focus();
                return false;
            }
        });

        // Display messages from URL query params (e.g., after server-side redirect)
        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            const message = params.get('message');
            const type = params.get('type');

            if (message && type) {
                messageArea.textContent = decodeURIComponent(message);
                messageArea.className = type === 'success' ? 'success' : 'error';
                messageArea.style.display = 'block';
                // Clean URL
                if (window.history.replaceState) {
                    const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                    window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
                }
            }
        });
    </script>
</body>
</html>
