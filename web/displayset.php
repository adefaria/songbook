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
  <h1 class="centered">Playlist</h1>
</div>

<div id="content">

  <table width="100%" cellspacing="1" cellpadding="1" border="1">
    <tbody>
      <?php
      global $songbook, $songDir, $artists;

      $firstLine     = true;
      $count         = 0;
      $library       = dirname($set);
      $totalDuration = 0;

      foreach (file("$songbook/$set") as $line) {
        // Skip first line which is merely the set name again
        if ($firstLine) {
          $firstLine = false;
          print "<tr><th colspan=\"7\"><a href=\"$set\"> $line</a></th></tr>";
          print <<<END
<tr>
  <th align="center">#</th>
  <th>Title</th>
  <th>Artist</th>
  <th>Library</th>
  <th>Key</th>
  <th>Capo</th>
  <th>Duration</th>
</tr>
END;
          continue;
        } // if

        if (preg_match("/(.*)\s+-\s+(.*)/", $line, $matches)) {
          $title  = trim($matches[1]);
          $artist = trim($matches[2]);
        } else {
          $title  = trim($line);
          $artist = '';
        } // if

        $song = findSong($title, $library);

        // If $song[file] is not set then findSong didn't find a song
        if (empty($song["file"])) {
          $song["file"] = $title;

          debug("Song $song[file] not found");
        } else {
          debug("Found song[file]: $song[file] - Folder: $song[library]");
        }

        $songfile = fileExists($song['file']);

        if ($songfile) {
          $songtitle = "<a href=\"webchord.cgi?chordpro=$songfile\">$title</a>";
        } else {
          $songtitle = $title;
        } // if

        if ($artist == '') {
          $artist = getArtist($song['file']);
        } // if

        $artist = "<a href=\"displayartist.php?artist=$artist\">$artist</a>";

        $count++;

        if (!empty($song['duration'])) {
          list($m, $s) = explode(':', $song["duration"]);
          $totalDuration += ms2s($m, $s);
        }

        debug("Total Duration: $totalDuration");

        print <<<END
<tr>
  <td align="center">$count</td>
  <td>$songtitle</td>
  <td>$artist</td>
  <td>$song[library]</td>
  <td align="center">$song[key]</td>
  <td align="center">$song[capo]</td>
  <td align="right">$song[duration]</td>
</tr>
END;
      } // foreach

      $total = s2ms($totalDuration);
      print <<<END
    <tr>
      <th colspan="6" align="left">Total</th>
      <td align="right">$total</td>
    </tr>
  </tbody>
</table>
END;
      ?>
    </tbody>
    </body>

</html>