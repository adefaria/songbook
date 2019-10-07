<?php
$baseDir    = getcwd();
$songbook   = "/opt/songbook";
$songFolder = "Andrew";
$songDir    = "/opt/songbook/$songFolder";

if (isset ($_REQUEST['debug'])) {
  $debug = $_REQUEST['debug'];
} // if

// Initialize music objects
$songs   = getSongs($songDir);
$sets    = getSets($songDir);
$artists = getArtists($songs);

function debug ($msg) {
  global $debug;

  if (isset ($debug)) {
    echo "<font color=red>DEBUG:</font> $msg<br>";
  } // if
} // debug

function getSongs ($songDir) {
  return glob("$songDir/*.pro");
} // getSongs

function getSets ($songDir) {
  global $songbook;

  return glob("$songbook/*/*.lst");
  return glob("$songDir/*.lst");
} // getSets

function songsDropdown () {
  global $songs;

  print "<form method=\"get\" action=\"webchord.cgi\" name=\"song\">";
  print "Songs:&nbsp;&nbsp;";
  print "<select name=\"chordpro\">";

  sort ($songs);
  foreach ($songs as $song) {
    $title = basename ($song, ".pro");
    $artist = getArtist ($song);

    print "<option value=\"$title.pro\">$title</option>";

    if ($artist != "") {
      $title .= "&nbsp;($artist)";
    } // if
  } // foreach

  print "</select>";
  print "&nbsp;<input type=\"submit\" value=\"Go\">";
  print "</form>";
} // songsDropdown

function artistsDropdown () {
  global $artists;

  print "<form method=\"get\" action=\"displayartist.php\" name=\"artist\">";
  print "Artists:&nbsp;&nbsp;";
  print "<select name=\"artist\">";

  sort ($artists);
  foreach ($artists as $artist) {
    print "<option>$artist</option>";
  } // foreach

  print "</select>";
  print "&nbsp;<input type=\"submit\" value=\"Go\">";
  print "</form>";
} // artistsDropdown

function setsDropdown () {
  global $sets;

  print "<form method=\"get\" action=\"displayset.php\" name=\"set\">";
  print "Sets:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
  print "<select name=\"set\">";

  sort ($sets);
  foreach ($sets as $set) {
    preg_match("/.*\/(.*)\.lst/", $set, $matches);
    $title = $matches[1];
    preg_match("/\/opt\/songbook\/(.*)/", dirname($set), $matches);
    $subdir = $matches[1];
    $title = basename($set, ".lst");

    print "<option value=\"$subdir/$title.lst\">$title</option>";
  } // foreach

  print "</select>";
  print "&nbsp;<input type=\"submit\" value=\"Go\">";
  print "</form>";
} // setsDropdown

function getArtist ($song) {
  $lyrics = @file_get_contents ($song);

  if (preg_match ("/\{(st|subtitle):(.*)\}/", $lyrics, $matches)) {
    return trim ($matches[2]);
  } else {
    return "";
  } // if
} // getArtist

function getArtists ($songs) {
  foreach ($songs as $song) {
    $artist = getArtist ($song);

    if ($artist != '') {
      $artists[$artist] = 1;
    } // if
  } // foreach

  return array_keys ($artists);
} // getArtists

