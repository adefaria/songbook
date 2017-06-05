<?php
$baseDir = getcwd();
$songDir = "/opt/songbook/Songs";
$debug   = $_REQUEST["debug"];

function debug ($msg) {
  global $debug;

  if (isset ($debug)) {
    echo "<font color=red>DEBUG:</font> $msg<br>";
  } // if
} // debug

function getSongs () {
  global $songDir;

  return glob("$songDir/*.pro");
} // getSongs

function getSets () {
  global $songDir;

  return glob("$songDir/*.lst");
} // getSets

function songsDropdown () {
  $songs = getSongs();

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
  $songs = getSongs();
  $artists = getArtists ($songs);

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
  $sets = getSets();

  print "<form method=\"get\" action=\"displayset.php\" name=\"set\">";
  print "Sets:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
  print "<select name=\"set\">";

  sort ($sets);
  foreach ($sets as $set) {
    print "Processing set<br>";
    $title = basename ($set, ".lst");

    print "<option value=\"$title.lst\">$title</option>";
  } // foreach

  print "</select>";
  print "&nbsp;<input type=\"submit\" value=\"Go\">";
  print "</form>";
} // setsDropdown

function getArtist ($song) {
  $lyrics = file_get_contents ($song);

  if (preg_match ("/\{(st|subtitle):(.*)\}/", $lyrics, $matches)) {
    return trim ($matches[2]);
  } else {
    return "";
  } // if
} // getArtist

function getArtists ($songs) {
  $artists = array();

  foreach ($songs as $song) {
    $artist = getArtist ($song);

    if ($artist != '') {
      $artists[$artist] = 1;
    } // if
  } // foreach

  return array_keys ($artists);
} // getArtists

function formatTable ($songs) {
  echo "<ol>";

  foreach ($songs as $song) {
    $artist = getArtist ($song);

    $title = basename ($song, ".pro");

    echo "<li><a href=\"webchord.cgi?chordpro=$song\">$title</a>";

    if ($artist != "") {
    echo "&nbsp;($artist)";
    } // if
  } // foreach

  echo "</ol>";
} // formatTable
