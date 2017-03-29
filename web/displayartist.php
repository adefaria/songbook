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
$artist = $_REQUEST ["artist"];
?>

<div class="heading">
<a href="/songbook"><img src="/Icons/Home.png" alt="Home"></a>
  <h1 class="centered">Andrew DeFaria's Songbook</h1>

  <h2 class="centered"><?php echo $artist?></h2>
</div>

<div id="content">

<?php
$artistsSongs = array();

foreach ($songs as $song) {
  $songArtist = getArtist ($song);

  if ($songArtist == $artist) {
    array_push ($artistsSongs, $song);
  } // if
} // foreach

print "<ol>";

foreach ($artistsSongs as $artistSong) {
  print "<li><a href=\"webchord.cgi?chordpro=$artistSong\">";
  print basename ($artistSong, ".pro");
  print "</a></li>";
} // foreach
?>

</body>
</html>
