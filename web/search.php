<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook: Search</title>
  <link rel="stylesheet" type="text/css" media="screen" href="/css/Music.css">
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="SHORTCUT ICON" href="https://defaria.com/favicon.ico" type="image/png">

  <?php
  include_once "songbook.php";

  $searchterm = $_REQUEST["q"];
  $songmatches = array();

  function getSongText($song)
  {
    return join("\n", file($song));
  } // getSongText

  function search($searchterm)
  {
    global $songs, $songbook, $songmatches;

    $tokens = preg_split("/\s+/", $searchterm);
    $searchfor = join(".*", $tokens);

    foreach ($songs as $song) {
      if (preg_match("#$songbook/(\S+)/#", $song, $matches)) {
        $folder = $matches[1];
      } else {
        $folder = '';
      } // if

      debug("Song: $song from folder $folder");

      $songEntry = array(
        'file'   => $song,
        'folder' => $folder,
        'artist' => getArtist($song),
      );

      $text = getSongText($song);

      preg_match("/$searchfor/i", $text, $matches);

      if ($matches) {
        array_push($songmatches, $songEntry);
      } // if
    } // foreach

    return $songmatches;
  } // search

  $songmatches = search($searchterm);
  ?>
</head>

<body>

  <div class="heading">
    <a href="/songbook"><img src="/Icons/Home.png" alt="Home"></a>
    <h1 class="centered">Andrew DeFaria's Songbook</h1>

    <h2><?php
        if (count($songmatches) == 0) {
          print "No songs matched \"$searchterm\"";
        } elseif (count($songmatches) == 1) {
          print "One song matched \"$searchterm\"";
        } else {
          print count($songmatches) . " songs matched \"$searchterm\"";
        } // if
        ?></h2>
  </div>

  <div id="content">

    <?php
    if (count($songmatches) > 0) {
      print "<ol>";
    } // if

    foreach ($songmatches as $songmatch) {
      $title = basename($songmatch['file'], ".pro");
      print "<li><a href=\"webchord.cgi?chordpro=$songmatch[file]\">$title</a>";
      print " - <a href=\"displayartist.php?artist=$songmatch[artist]\">$songmatch[artist]</a>";
      print " <font color=\"#ccc\"> $songmatch[folder]</font></li>";
    } // foreach

    if (count($songmatches) > 0) {
      print "</ol>";
    } // if
    ?>

  </div>
</body>

</html>