<?php
$file = '/opt/songbook/web/Music.ico';
if (file_exists($file)) {
    echo "File $file exists.<br>";
    if (is_readable($file)) {
        echo "File $file is readable.<br>";
        echo "Size: " . filesize($file) . " bytes<br>";
        echo "<img src='/songbook/Music.ico'><br>";
    } else {
        echo "File $file is NOT readable.<br>";
    }
} else {
    echo "File $file does not exist.<br>";
}

$fileMaps = '/opt/clearscm/maps/Maps.png';
if (file_exists($fileMaps)) {
    echo "File $fileMaps exists.<br>";
    if (is_readable($fileMaps)) {
        echo "File $fileMaps is readable.<br>";
        echo "Size: " . filesize($fileMaps) . " bytes<br>";
        echo "<img src='/maps/Maps.png'><br>";
    } else {
        echo "File $fileMaps is NOT readable.<br>";
    }
} else {
    echo "File $fileMaps does not exist.<br>";
}
?>