<?php
// AJAX Loader with better UX
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading App...</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f5f5f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .loader {
            text-align: center;
            padding: 40px;
        }
        .loader i {
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        #app-content {
            width: 100%;
            height: 100%;
        }
    </style>
</head>
<body>
    <div id="app-content">
        <!-- Loading screen -->
        <div class="loader" id="loader">
            <i data-lucide="loader-2" width="48" height="48"></i>
            <h3>Loading Application...</h3>
        </div>
    </div>

    <script>
        // Initialize icons first
        lucide.createIcons();
        
        // Load the frontend
        async function loadApp() {
            try {
                const response = await fetch('frontend/index.html');
                const html = await response.text();
                
                // Replace entire content
                document.getElementById('app-content').innerHTML = html;
                
                // Re-run Lucide icons
                if (window.lucide) {
                    setTimeout(() => lucide.createIcons(), 100);
                }
                
                // Update browser history (optional)
                window.history.replaceState({}, 'Product System', '/');
                
            } catch (error) {
                console.error('Failed to load app:', error);
                document.getElementById('app-content').innerHTML = `
                    <div class="loader">
                        <i data-lucide="alert-circle" width="48" height="48"></i>
                        <h3>Failed to load application</h3>
                        <p>${error.message}</p>
                        <button onclick="location.reload()" style="margin-top:20px; padding:10px 20px; background:#007bff; color:white; border:none; border-radius:5px; cursor:pointer;">
                            Retry
                        </button>
                    </div>
                `;
                lucide.createIcons();
            }
        }
        
        // Start loading
        loadApp();
    </script>
</body>
</html>