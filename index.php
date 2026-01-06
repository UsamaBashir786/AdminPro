<!-- 
Development Assistants

These files help with auto-redirect during testing:
- api/redirect.php → URL calculator
- index.php → Root redirector

Remove when deploying
Set up proper server configuration instead.

Thanks for making development easier, little files! ✨
-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting...</title>
    <style>
        body{font-family:Arial,sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#f0f2f5}.loader{text-align:center;padding:30px}.spinner{border:4px solid #f3f3f3;border-top:4px solid #3498db;border-radius:50%;width:40px;height:40px;animation:1s linear infinite spin;margin:0 auto 20px}@keyframes spin{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <h3>Loading Application...</h3>
        <p id="status">Getting redirect URL...</p>
    </div>

    <script>
        async function redirectToFrontend() {
            try {
                const response = await fetch('api/redirect.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('status').textContent = 'Redirecting...';
                    
                    setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 1000);
                } else {
                    throw new Error('API returned error');
                }
            } catch (error) {
                console.error('Redirect failed:', error);
                document.getElementById('status').textContent = 'Error: ' + error.message;
                document.getElementById('status').style.color = '#e74c3c';
                
                document.body.innerHTML += `
                    <div style="margin-top: 20px; text-align: center;">
                        <a href="frontend/index.php" style="color: #3498db; text-decoration: none;">
                            ↗️ Open directly
                        </a>
                    </div>
                `;
            }
        }
        
        window.addEventListener('DOMContentLoaded', redirectToFrontend);
    </script>
</body>
</html>