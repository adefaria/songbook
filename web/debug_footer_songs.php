<?php
// Mock server variables if needed
$_SERVER['REQUEST_URI'] = '/songbook';

// Include the file under test
include "/opt/defaria.com/php/site-functions.php";

echo "Testing song loading logic...\n";

// Replicate the logic from site-functions.php
global $songs;
$mySongs = $songs;

if (empty($mySongs)) {
    if (!function_exists('getSongs')) {
        if (file_exists("/opt/songbook/web/songbook.php")) {
            include_once "/opt/songbook/web/songbook.php";
        }
    }
    if (function_exists('getSongs')) {
        $mySongs = getSongs("/opt/songbook");
    }
}

echo "Songs count: " . (is_array($mySongs) ? count($mySongs) : "Not an array") . "\n";

if (is_array($mySongs) && count($mySongs) > 0) {
    echo "First song: " . $mySongs[0] . "\n";

    // Check lyrics helper
    if (function_exists('getSearchableLyrics')) {
        echo "getSearchableLyrics exists.\n";
    } else {
        echo "getSearchableLyrics MISSING.\n";
    }
} else {
    echo "No songs found.\n";
}
?>