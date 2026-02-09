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
            <span class="home-icon" style="font-size: 40px; line-height: 1; color: #4285F4;">&#9835;</span>
          </a>
          <div class="version-text">3.0</div>
        </td>
        <td align="center">
          <h1><a href="/songs" target="_top" style="text-decoration: none; color: inherit;">Songbook</a></h1>
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
    
    print "<div class='song-list-container'>";

    foreach ($artistsSongs as $artistSong) {
      // Parse song to get details (Key, Capo)
      $songData = parseSong($artistSong['file']);

      $title = basename($artistSong['file'], ".pro");
      // Link to rendered Song page
      $proLink = "webchord.cgi?chordpro=" . urlencode($artistSong['file']);

      $artistName = $artistSong['artist'];
      // Link to artist page (reload)
      $artistLink = "displayartist.php?artist=" . urlencode($artistName);

      $keyHTML = $songData['key'] ? " | Key: " . htmlspecialchars($songData['key']) : "";
      $capoHTML = $songData['capo'] ? " | Capo: " . htmlspecialchars($songData['capo']) : "";

      // Metadata Line: "by <artist> | <key> | <capo>"
      $metaLine = "by <a href=\"$artistLink\">" . htmlspecialchars($artistName) . "</a>" . $keyHTML . $capoHTML;

      // Lyrics Preview
      $preview = htmlspecialchars(getLyricsPreview($artistSong['file']));

      print "<div class='song-card'>";
      print "<div class='song-card-title'><a href=\"$proLink\">$title</a></div>";
      print "<div class='song-card-meta'>$metaLine</div>";
      print "<div class='song-card-lyrics'>$preview</div>";
      print "</div>";
    } // foreach
    
    print "</div>"; // Close song-list-container
    ?>



</body>

</html>