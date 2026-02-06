<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook Artist</title>
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="stylesheet" type="text/css" href="songbook.css?v=<?php echo time(); ?>">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">

  <?php
  include_once "songbook.php";
  $artist = $_REQUEST["artist"];
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
          <h2>Songs from <?php echo $artist; ?></h2>
        </td>
      </tr>
    </tbody>
  </table>

  <div id="content">

    <?php
    global $songs, $songbook;

    $artistsSongs = array();

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

      if ($songEntry["artist"] == $artist) {
        array_push($artistsSongs, $songEntry);
      } // if
    } // foreach
    
    print "<ol class='song-list'>";

    foreach ($artistsSongs as $artistSong) {
      print "<li><a href=\"webchord.cgi?chordpro=$artistSong[file]\">"
        . basename($artistSong['file'], ".pro")
        . "</a> <span class=\"song-folder\"> $artistSong[folder]</span></li>";
    } // foreach
    ?>

</body>

</html>