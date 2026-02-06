<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook: Search</title>
  <link rel="stylesheet" type="text/css" media="screen" href="/songbook/songbook.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">

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
        'file' => $song,
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

<body style="margin-top: 130px; margin-right: 10px; margin-left: 10px; margin-bottom: 10px;">
  <table width="100%" id="heading">
    <tbody>
      <tr>
        <td align="center" valign="middle" width="50">
          <a href="/songs" target="_top" style="text-decoration: none;">
            <span class="home-icon" style="font-size: 40px; line-height: 1;">&#9835;</span>
          </a>
          <div class="version-text">3.0</div>
        </td>
        <td align="center">
          <h1>Songbook</h1>
          <h2>Search Results</h2>
        </td>
      </tr>
    </tbody>
  </table>

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
      print "<ol class='song-list'>";
    } // if
    
    foreach ($songmatches as $songmatch) {
      $title = basename($songmatch['file'], ".pro");
      print "<li><a href=\"webchord.cgi?chordpro=$songmatch[file]\">$title</a>";
      print " - <a href=\"displayartist.php?artist=$songmatch[artist]\">$songmatch[artist]</a>";
      print " <span class=\"song-folder\"> $songmatch[folder]</span></li>";
    } // foreach
    
    if (count($songmatches) > 0) {
      print "</ol>";
    } // if
    ?>

  </div>
</body>

</html>