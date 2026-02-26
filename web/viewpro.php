<?php
$songDirectory = "/opt/songbook/";

if (isset($_GET['file'])) {
    $fileName = $_GET['file'];

    // 1. Prevent directory traversal: only allow the filename part.
    $fileName = basename($fileName);

    // 2. Ensure we are only trying to access .pro files.
    if (strtolower(substr($fileName, -4)) !== '.pro') {
        header("HTTP/1.0 400 Bad Request");
        echo "Error: Invalid file type. Only .pro files are permitted.";
        exit;
    }

    $filePath = $songDirectory . $fileName;

    if (file_exists($filePath) && is_readable($filePath)) {
        // Change Content-Type to HTML for styling
        header('Content-Type: text/html; charset=utf-8');

        echo "<!DOCTYPE html>\n";
        echo "<html>\n<head>\n";
        echo '<meta charset="utf-8">' . "\n";
        echo '<title>' . htmlspecialchars($fileName) . '</title>' . "\n";
        echo '<link rel="stylesheet" type="text/css" href="songbook.css?v=' . time() . '">' . "\n";
        echo '<script src="songbook.js?v=' . time() . '"></script>' . "\n";
        echo "</head>\n<body>\n";

        echo "<pre>";
        $content = file_get_contents($filePath);
        echo htmlspecialchars($content);
        echo "</pre>\n";

        echo "</body>\n</html>";
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        echo "Error: File not found or is not readable.";
        exit;
    }
} else {
    header("HTTP/1.0 400 Bad Request");
    echo "Error: No file specified.";
    exit;
}
?>