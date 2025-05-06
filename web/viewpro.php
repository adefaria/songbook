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
        header('Content-Type: text/plain; charset=utf-8');
        readfile($filePath);
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