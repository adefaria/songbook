<?php
$songbook = "/opt/songbook";
$songDir = "$songbook";

if (isset($_REQUEST['debug'])) {
    $debug = $_REQUEST['debug'];
} // if

// As others do not edit their chordpro files carefully we will favor my subdirectory over others.
$songFolders = array('Rob', 'Bluegrass', 'XMAS');

// Initialize music objects
$songs = getSongs($songbook);
$sets = getSets($songbook);
$artists = getArtists($songs);

function debug($msg)
{
    global $debug;

    if (isset($debug)) {
        echo "<font color=red>DEBUG:</font> $msg<br>";
    } // if
} // debug

// Return a song structure given a song title
function parseSong($songfile)
{
    global $songbook;

    $songContents = @file_get_contents($songfile);
    $song = array(
        "file" => $songfile,
    );

    if (preg_match("/\{(st|subtitle):(.*)\}/i", $songContents, $matches)) {
        $song['artist'] = trim($matches[2]);
    } else {
        $song['artist'] = '';
    } // if

    if (preg_match("#$songbook/(.*)\/.*#", $songfile, $matches)) {
        $song['library'] = $matches[1];
    } else {
        $song['library'] = '';
    } // if

    if (preg_match("/\{key:(.*)\}/i", $songContents, $matches)) {
        $song['key'] = trim($matches[1]);
    } else {
        $song['key'] = '';
    } // if

    if (preg_match("/\{capo:(.*)\}/i", $songContents, $matches)) {
        $song['capo'] = trim($matches[1]);
    } else {
        $song['capo'] = '';
    } // if

    if (preg_match("/\{duration:(.*)\}/i", $songContents, $matches)) {
        $song['duration'] = trim($matches[1]);
    } else {
        $song['duration'] = '';
    } // if

    if (preg_match("/\{musicpath:(.*)\}/i", $songContents, $matches)) {
        $song['audio'] = trim($matches[1]);
    } else {
        $song['audio'] = '';
    } # if

    return $song;
} // parseSong

// This function prioritizes the order in the $songFolder array.
function findSong($title, $library)
{
    global $songbook, $songFolders;

    $folders_to_search = array(); // Use a clearer variable name
    debug("findSong(Title: '$title', Library: '$library')");

    // --- Determine Search Order ---
    if (!isset($library) || $library === '' || $library === null) {
        debug("Library not specified. Using default song folder order.");
        // Add configured subfolders first
        $folders_to_search = $songFolders; // ['Rob', 'Bluegrass', 'XMAS']
    } else {
        debug("Library specified: '$library'. Prioritizing.");
        // Add specified library first
        array_unshift($folders_to_search, $library);
        // Add other configured subfolders
        foreach ($songFolders as $folder) {
            if ($folder != $library) {
                array_push($folders_to_search, $folder);
            }
        }
    }

    // --- Add the base songbook directory to the search path ---
    // We add an empty string '' which will represent the base directory later
    // This ensures it's checked *after* the prioritized subfolders.
    array_push($folders_to_search, '');
    debug("Full search folder list ('' represents base): " . implode(', ', $folders_to_search));


    // --- Initialize default song structure ---
    $found_song_data = array(
        'file' => $title . '.pro', // Default filename if not found
        'artist' => '',
        'library' => '',
        'key' => '',
        'capo' => '',
        'duration' => '',
        'audio' => '',
        'error' => 'Not found in search paths' // Default error
    );


    // --- Iterate through the determined search paths ---
    debug("Searching for '$title.pro'");
    foreach ($folders_to_search as $folder) {
        // Construct the path: If $folder is '', it becomes $songbook/$title.pro
        // If $folder is 'Rob', it becomes $songbook/Rob/$title.pro
        $potential_file_path = rtrim($songbook, '/') . '/' . ($folder ? rtrim($folder, '/') . '/' : '') . $title . '.pro';

        debug("Checking path: '$potential_file_path'");

        // Use fileExists (case-insensitive check)
        $actual_file_path = fileExists($potential_file_path);

        if ($actual_file_path) {
            debug("Found match via fileExists: '$actual_file_path'. Parsing...");
            $found_song_data = parseSong($actual_file_path); // Parse the found file

            // Check if parseSong indicated an error (e.g., file not readable)
            if (isset($found_song_data['error'])) {
                debug("findSong: parseSong reported error: " . $found_song_data['error']);
                // Keep the file path even if parsing failed
                $found_song_data['file'] = $actual_file_path;
                // Decide if you want to stop or keep searching if parsing fails.
                // Let's stop here, returning the parse error.
                break;
            } else {
                // Successfully found and parsed
                unset($found_song_data['error']); // Remove default error message
                debug("Successfully found and parsed '$actual_file_path'. Stopping search.");
                break; // Stop searching once found and parsed successfully
            }
        } else {
            debug("No match via fileExists for '$potential_file_path'");
        }
    } // foreach folder

    // If loop finished without finding the file, $found_song_data still holds the default error state.
    debug("--- findSong finished for '$title'. Returning data. ---");
    return $found_song_data;
} // findSong


function getSongs($songbook)
{
    return glob("$songbook/*.pro");
} // getSongs

function getSets($songbook)
{
    return glob("$songbook/*.lst");
} // getSets

function songsDropdown()
{
    global $songs;

    print "<form method=\"get\" action=\"webchord.cgi\" name=\"song\">";
    print "Songs:&nbsp;&nbsp;";
    print "<select name=\"chordpro\" id=\"song-select\">";
    print "<option value=''>Select a song...</option>";

    sort($songs);

    foreach ($songs as $song) {
        $title = basename($song, ".pro");
        $artist = getArtist($song);

        print "<option value=\"$title.pro\">$title</option>";

        if ($artist != "") {
            $title .= "&nbsp;($artist)";
        } // if
    } // foreach

    print "</select>";
    print "&nbsp;<input type=\"submit\" value=\"Go\">";
    print "</form>";
} // songsDropdown

function artistsDropdown()
{
    global $artists;

    print "<form method=\"get\" action=\"displayartist.php\" name=\"artist\">";
    print "Artists:&nbsp;&nbsp;";
    print "<select name=\"artist\" id=\"artist-select\">";
    print "<option value=''>Select an artist...</option>";

    sort($artists);

    foreach ($artists as $artist) {
        print "<option>$artist</option>";
    } // foreach

    print "</select>";
    print "&nbsp;<input type=\"submit\" value=\"Go\">";
    print "</form>";
} // artistsDropdown

function setsDropdown()
{
    global $sets;

    print "<form method=\"get\" action=\"displayset.php\" name=\"set\">";
    print "Sets:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
    print "<select name=\"set\" id=\"set-select\">";
    print "<option value=''>Select a set...</option>";

    sort($sets);

    foreach ($sets as $set) {
        preg_match("/.*\/(.*)\.lst/", $set, $matches);
        $title = $matches[1];
        preg_match("/\/opt\/songbook\/(.*)/", dirname($set), $matches);
        $title = basename($set, ".lst");

        print "<option value=\"$title.lst\">$title</option>";
    } // foreach

    print "</select>";
    print "&nbsp;<input type=\"submit\" value=\"Go\">";
    print "</form>";
} // setsDropdown

function getArtist($song)
{
    $lyrics = @file_get_contents($song);

    if (preg_match("/\{(st|subtitle):(.*)\}/", $lyrics, $matches)) {
        return trim($matches[2]);
    } else {
        return "";
    } // if
} // getArtist

function getArtists($songs)
{
    foreach ($songs as $song) {
        $artist = getArtist($song);

        if ($artist != '') {
            $artists[$artist] = 1;
        } // if
    } // foreach

    return array_keys($artists);
} // getArtists

// Search for files case insensitive and alter $fileName to reflect the correct
// case
function fileExists($fileName)
{
    $files = glob(dirname($fileName) . '/*');

    $filename = strtolower($fileName);

    debug("Searching for $filename");
    foreach ($files as $file) {
        if (strtolower($file) == $filename) {
            debug("Found file $file");
            return $file;
        } // if
    } // foreach

    return false;
} // fileExists

function ms2s($m, $s)
{
    return ($m * 60) + $s;
} // ms2s

function s2ms($s)
{
    return intdiv($s, 60) . ':' . $s % 60;
} // s2ms
