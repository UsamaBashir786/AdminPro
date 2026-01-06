<?php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . "://" . $host;

$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$current_path = parse_url($request_uri, PHP_URL_PATH);

if ($current_path === '/' || $current_path === '/index.php' || basename($current_path) === 'index.php') {
    $redirect_url = rtrim($base_url, '/') . '/frontend/index.html';
    
    if (strpos($request_uri, '/api/') === 0 || strpos($request_uri, '/backend/') === 0) {
    } else {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Redirecting...</title>
            <meta http-equiv="refresh" content="0; url=<?php echo htmlspecialchars($redirect_url); ?>">
            <script>
                window.location.href = "<?php echo $redirect_url; ?>";
                
                setTimeout(function() {
                    document.body.innerHTML = '<h3>Redirecting...</h3><p>If not redirected, <a href="<?php echo $redirect_url; ?>">click here</a></p>';
                }, 1000);
            </script>
        </head>
        <body>
            <p>Redirecting to application...</p>
        </body>
        </html>
        <?php
        exit;
    }
}
