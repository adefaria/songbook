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

// Return a song structure with the absolute path to the song file and the folder we found it in.
// This function prioritizes the order in the $songFolder array.
function findSong($title)
{
  global $songbook, $songFolders;

  $song = array();

  debug("Searching for $title");
  foreach ($songFolders as $folder) {
    $song['file']   = "$songbook/$folder/$title.pro";
    $song['folder'] = $folder;

    debug("Checking for $song[file]");

    if (fileExists($song['file'])) {
      debug("Found");
      break;
    } else {
      debug("Didn't find $song[file]");
    } // if

    $song = array();
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
function fileExists(&$fileName)
{
  $files = glob(dirname($fileName) . '/*');

  $filename = strtolower($fileName);

  foreach ($files as $file) {
    if (strtolower($file) == $filename) {
      $fileName = $file;

      return true;
    } // if
  } // foreach

  return false;
} // fileExists