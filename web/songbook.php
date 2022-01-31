<?php
$songbook   = "/opt/songbook";
$songDir    = "$songbook/Andrew";

if (isset($_REQUEST['debug'])) {
  $debug = $_REQUEST['debug'];
} // if

// As others do not edit their chordpro files carefully we will favor my subdirectory over others.
$songFolders = array('Andrew', 'Rick', 'Mikey', 'Kent', 'Bluegrass', 'XMAS');

// Initialize music objects
$songs   = getSongs($songbook);
$sets    = getSets($songbook);
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
  $song         = array(
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

  $folders = array();
  debug("findSong($title, $library)");
  if (!isset($library)) {
    debug("Using default songfolders");
    $folders = $songFolders;
  } else {
    debug("Pushing $library");
    // Favor passed in $library
    array_unshift($folders, $library);

    foreach ($songFolders as $folder) {
      if ($folder != $library) {
        array_push($folders, $folder);
      } // if
    } // foreach
  }

  debug("Searching for $title");
  foreach ($folders as $folder) {
    debug("Checking for $songbook/$folder/$title.pro");

    $song = array(
      'artist'   => '',
      'library'  => '',
      'key'      => '',
      'capo'     => '',
      'duration' => '',
      'audio'    => '',
    );

    $song['file'] = fileExists("$songbook/$folder/$title.pro");

    if ($song['file']) {
      $song = parseSong($song['file']);

      if (!isset($song['capo'])) {
        echo "capo not found for song $song[file]<br>";
        #exit;
      }
      break;
    } else {
      $song['file'] = $title;
      debug("Didn't find $title.pro in $songbook/$folder");
    } // if
  } // foreach

  return $song;
} // findSong

function getSongs($songbook)
{
  return glob("$songbook/*/*.pro");
} // getSongs

function getSets($songbook)
{
  return glob("$songbook/*/*.lst");
} // getSets

function songsDropdown()
{
  global $songs;

  print "<form method=\"get\" action=\"webchord.cgi\" name=\"song\">";
  print "Songs:&nbsp;&nbsp;";
  print "<select name=\"chordpro\">";

  sort($songs);

  foreach ($songs as $song) {
    $title  = basename($song, ".pro");
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
  print "<select name=\"artist\">";

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
  print "<select name=\"set\">";

  sort($sets);

  foreach ($sets as $set) {
    preg_match("/.*\/(.*)\.lst/", $set, $matches);
    $title = $matches[1];
    preg_match("/\/opt\/songbook\/(.*)/", dirname($set), $matches);
    $subdir = $matches[1];
    $title = basename($set, ".lst");

    print "<option value=\"$subdir/$title.lst\">$subdir/$title</option>";
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