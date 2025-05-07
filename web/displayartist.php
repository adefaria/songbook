<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook Artist</title>
  <link rel="stylesheet" type="text/css" media="screen" href="/css/Music.css">
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="stylesheet" type="text/css" href="songbook.css">
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
        <td align="left" style="padding-left: 10px;" valign="middle" width="50">
          <a href="/songbook"><img alt="Home" border="0" src="/Icons/Home.ico"
              style="width: 100%; height: auto;"><br>&nbsp;&nbsp;2.0</a>
        </td>
        <td align=" center">
          <h1 style="color: white">Songs from <?php echo $artist; ?></h1>
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
    
    print "<ol>";

    foreach ($artistsSongs as $artistSong) {
      print "<li><a href=\"webchord.cgi?chordpro=$artistSong[file]\">"
        . basename($artistSong['file'], ".pro")
        # TODO: Make this a css element
        . "</a> <font color=\"#ccc\"> $artistSong[folder]</font></li>";
    } // foreach
    ?>

</body>

</html>