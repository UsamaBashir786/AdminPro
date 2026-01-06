<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Product Management System</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">
                <i data-lucide="lock-keyhole" width="24" height="24" style="color: hsl(var(--primary-foreground));"></i>
            </div>
            <h1>Welcome Back</h1>
            <p class="subtitle">Sign in to your account to continue</p>
        </div>
        
        <div id="message" class="message"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label for="username">
                    <i data-lucide="user" width="14" height="14"></i>
                    Username (Press ctrl + shift + s)
                </label>
                <div class="input-wrapper">
                    <i data-lucide="user" width="16" height="16" class="input-icon"></i>
                    <input type="text" id="username" name="username" required autocomplete="username" placeholder="Enter your username">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i data-lucide="lock" width="14" height="14"></i>
                    Password
                </label>
                <div class="input-wrapper">
                    <i data-lucide="lock" width="16" height="16" class="input-icon"></i>
                    <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                </div>
            </div>
            
            <button type="submit" id="loginBtn">
                <i data-lucide="log-in" width="16" height="16"></i>
                <span>Sign In</span>
            </button>
        </form>
        
        <!-- Hidden by default, shows on click -->
        <div class="credentials-hint" id="credHint">
            <i data-lucide="info" width="14" height="14"></i>
            Click for test credentials
        </div>
        
        <div class="credentials-box" id="credBox">
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Username</th>
                        <th>Password</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Super Admin</td>
                        <td><code>super</code></td>
                        <td><code>super</code></td>
                    </tr>
                    <tr>
                        <td>Admin</td>
                        <td><code>admin</code></td>
                        <td><code>admin</code></td>
                    </tr>
                </tbody>
            </table>
            <p style="margin-top: 0.75rem; font-style: italic; text-align: center;">
                These are test accounts for demonstration
            </p>
        </div>
        
        <div class="links">
            <p>Don't have an account? <a href="signup.php">
                <i data-lucide="user-plus" width="14" height="14"></i>
                Sign up
            </a></p>
            <p><a href="index.php">
                <i data-lucide="arrow-left" width="14" height="14"></i>
                Back to Home
            </a></p>
        </div>
    </div>

    <script>
        const API_BASE = '../backend';
        
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
            
            // Toggle credentials visibility
            document.getElementById('credHint').addEventListener('click', function() {
                const credBox = document.getElementById('credBox');
                const isVisible = credBox.style.display === 'block';
                credBox.style.display = isVisible ? 'none' : 'block';
                this.innerHTML = isVisible 
                    ? '<i data-lucide="info" width="14" height="14"></i> Click for test credentials'
                    : '<i data-lucide="eye-off" width="14" height="14"></i> Hide credentials';
                lucide.createIcons();
            });
            
            // Auto-focus username field
            document.getElementById('username').focus();
        });
        
        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            const messageEl = document.getElementById('message');
            const loginBtn = document.getElementById('loginBtn');
            
            if (!username || !password) {
                showMessage('Please fill in all fields', 'error');
                return;
            }
            
            // Update button to loading state
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i data-lucide="loader-2" width="16" height="16" style="animation: spin 1s linear infinite;"></i><span>Signing in...</span>';
            lucide.createIcons();
            
            try {
                const response = await fetch(`${API_BASE}/login.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showMessage('Login successful! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    showMessage(data.message || 'Invalid username or password', 'error');
                    // Reset button
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<i data-lucide="log-in" width="16" height="16"></i><span>Sign In</span>';
                    lucide.createIcons();
                }
            } catch (error) {
                showMessage('Network error. Please check your connection.', 'error');
                // Reset button
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i data-lucide="log-in" width="16" height="16"></i><span>Sign In</span>';
                lucide.createIcons();
            }
        });
        
        function showMessage(text, type) {
            const messageEl = document.getElementById('message');
            const icon = type === 'error' ? 'alert-circle' : 'check-circle';
            messageEl.innerHTML = `<i data-lucide="${icon}" width="16" height="16"></i><span>${text}</span>`;
            messageEl.className = `message ${type}`;
            messageEl.style.display = 'flex';
            lucide.createIcons();
            
            // Auto-hide success messages
            if (type === 'success') {
                setTimeout(() => {
                    messageEl.style.display = 'none';
                }, 3000);
            }
        }
        
        // Check if already logged in
        (async () => {
            try {
                const response = await fetch('../api/user.php?action=session');
                const data = await response.json();
                if (data.logged_in) {
                    window.location.href = 'index.php';
                }
            } catch (error) {
                // Not logged in, stay on login page
            }
        })();
        
        // Quick fill for testing (optional)
        // Press Ctrl+Shift+S to fill super admin
        // Press Ctrl+Shift+A to fill admin
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey) {
                if (e.key === 'S' || e.key === 's') {
                    document.getElementById('username').value = 'super';
                    document.getElementById('password').value = 'super';
                    showMessage('Super admin credentials filled', 'success');
                } else if (e.key === 'A' || e.key === 'a') {
                    document.getElementById('username').value = 'admin';
                    document.getElementById('password').value = 'admin';
                    showMessage('Admin credentials filled', 'success');
                }
            }
        });
    </script>
</body>
</html>