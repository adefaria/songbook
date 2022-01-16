<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook List</title>
  <link rel="stylesheet" type="text/css" media="screen" href="/songbook/songbook.css">
  <link rel="stylesheet" type="text/css" media="screen" href="/css/Music.css">
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="SHORTCUT ICON" href="https://defaria.com/favicon.ico" type="image/png">

  <?php
  include_once "songbook.php";
  $set = $_REQUEST["set"];
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

  <h2 class="centered"><?php echo "Set: <a href=\"$set\">" . basename($set, ".lst") . "</a>" ?></h2>
</div>

<div id="content">

  <?php
  global $songbook, $songDir, $artists;

  print "<ol>";

  $firstLine = true;

  foreach (file("$songbook/$set") as $line) {
    // Skip first line which is merely the set name again
    if ($firstLine) {
      $firstLine = false;
      continue;
    } // if

    $title;
    $artist;

    if (preg_match("/(.*)\s+-\s+(.*)/", $line, $matches)) {
      $title  = trim($matches[1]);
      $artist = trim($matches[2]);
    } else {
      $title  = trim($line);
      $artist = '';
    } // if

    $song = findSong($title);

    // If $song[file] is not set then findSong didn't find a song
    if (empty($song["file"])) {
      $song["file"] = $title;

      debug("Song $song[file] not found");
    } else {
      debug("Found song[file]: $song[file] - Folder: $song[folder]");
    }

    if (fileExists($song['file'])) {
      print "<li><a href=\"webchord.cgi?chordpro=$song[file]\">$title</a>";
    } else {
      print "<li>$song[file]</li>";
    } // if

    if ($artist == '') {
      $artist = getArtist($song['file']);
    } // if

    if ($artist != '') {
      print " - ";

      if (in_array($artist, $artists)) {
        print "<a href=\"displayartist.php?artist=$artist\">$artist</a>";
      } else {
        print $artist;
      } // if
    } // if

    # TODO: Make this a css element
    print "<font color=\"#ccc\"> $song[folder]</font></li>";
  } // foreach

  print "</ol>";
  ?>

  </body>

</html>