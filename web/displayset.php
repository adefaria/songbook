<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook Artist</title>
  <link rel="stylesheet" type="text/css" media="screen" href="/css/Music.css">
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="SHORTCUT ICON" href="http://defaria.com/favicon.ico" type="image/png">

<?php
include_once "songbook.php";
$set = $_REQUEST ["set"];
?>

<style>
li {
width: 100%;
}
</style>
</head>

<div class="heading">
<a href="/songbook"><img src="/Icons/Home.png" alt="Home"></a>
  <h1 class="centered">Andrew DeFaria's Songbook</h1>

  <h2 class="centered"><?php echo "Set: " . basename ($set, ".lst")?></h2>
</div>

<div id="content">

<?php
global $songDir, $artists;

print "<ol>";

$firstLine = true;

foreach (file("$songDir/$set") as $line) {
  // Skip first line which is merely the set name again
  if ($firstLine) {
    $firstLine = false;
    continue;
  } // if

  if (preg_match ("/(.*)\s+-\s+(.*)/", $line, $matches)) {
    $song   = trim ($matches[1]);
    $artist = trim ($matches[2]);
  } else {
    $song   = trim ($line);
    $artist = "";
  } // if

  if (file_exists ("$songDir/$song.pro")) {
    print "<li><a href=\"webchord.cgi?chordpro=$song.pro\">";
    print basename ($song);
    print "</a>";
  } else {
    print "<li>";
    print basename ($song);
  }

  print " - ";

  if (in_array ($artist, $artists)) {
    print "<a href=\"displayartist.php?artist=$artist\">$artist</a>";
  } else {
    print $artist;
  } // if

  print "</li>";
} // foreach

print "</ol>";
?>

</body>
</html>
