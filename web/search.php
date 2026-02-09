<?php
include_once "songbook.php";

$searchterm = isset($_REQUEST["q"]) ? $_REQUEST["q"] : "";
$songmatches = array();

// Check if we are running embedded (in iframe) or standalone
// The main site router adds ?bypass=true when loading into iframe
$is_embedded = isset($_REQUEST['bypass']) && $_REQUEST['bypass'] == 'true';

function getSongText($song)
{
  return join("\n", file($song));
} // getSongText

function search($searchterm)
{
  global $songs, $songbook, $songmatches;

  if (empty($searchterm))
    return array();

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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <title>Songbook: Search Results</title>
  <link rel="stylesheet" type="text/css" href="songbook.css?v=<?php echo time(); ?>">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">
  <script src="songbook.js"></script>
  <?php include_once "songbook.php"; ?>
</head>

<body style="margin-top: 130px; margin-right: 10px; margin-left: 10px; margin-bottom: 50px;">
  <!-- Added bottom margin for footer -->

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
          <h2>Search Results</h2>
        </td>
      </tr>
    </tbody>
  </table>

  <div id="content">

    <!-- Compact Navigation Tabs (Consistent with index.php) -->
    <div
      style="display: flex; gap: 15px; justify-content: center; align-items: center; padding: 10px; flex-wrap:wrap; margin-top: 20px; border-bottom: 1px solid var(--border-color); margin-bottom: 20px;">

      <!-- Artists -->
      <form method="get" action="displayartist.php" style="margin:0;">
        <select name="artist" class="uniform-input-width" onchange="this.form.submit()" style="margin:0;">
          <option value=''>Artists...</option>
          <?php
          if (isset($artists) && is_array($artists)) {
            $sorted_artists = $artists;
            sort($sorted_artists);
            foreach ($sorted_artists as $artist_item) {
              echo "<option value=\"" . htmlspecialchars($artist_item) . "\">" . htmlspecialchars($artist_item) . "</option>";
            }
          }
          ?>
        </select>
      </form>

      <!-- Sets -->
      <form method="get" action="displayset.php" style="margin:0;">
        <select name="set" class="uniform-input-width" onchange="this.form.submit()" style="margin:0;">
          <option value=''>Sets...</option>
          <?php
          if (isset($sets) && is_array($sets)) {
            $sorted_sets = $sets;
            sort($sorted_sets);
            foreach ($sorted_sets as $set_item) {
              $title = basename($set_item, ".lst");
              echo "<option value=\"" . htmlspecialchars($title . ".lst") . "\">" . htmlspecialchars($title) . "</option>";
            }
          }
          ?>
        </select>
      </form>

      <!-- Songs -->
      <form method="get" action="webchord.cgi" style="margin:0;">
        <select name="chordpro" class="uniform-input-width" onchange="this.form.submit()" style="margin:0;">
          <option value=''>Songs...</option>
          <?php
          if (isset($songs) && is_array($songs)) {
            $sorted_songs = $songs;
            sort($sorted_songs);
            foreach ($sorted_songs as $song_item) {
              $title = basename($song_item, ".pro");
              echo "<option value=\"" . htmlspecialchars($title . ".pro") . "\">" . htmlspecialchars($title) . "</option>";
            }
          }
          ?>
        </select>
      </form>
    </div>

    <!-- Results -->
    <h2 style="margin-top: 20px;">
      <?php
      if (count($songmatches) == 0) {
        print "No songs matched \"$searchterm\"";
      } else {
        print count($songmatches) . " songs matched \"$searchterm\"";
      }
      ?>
    </h2>

    <?php
    if (count($songmatches) > 0) {
      print "<div class='song-list-container'>";
      foreach ($songmatches as $songmatch) {
        $keyHTML = "";
        $capoHTML = "";

        // We need to parse the song to get Key/Capo. 
        // parseSong is available from songbook.php
        $songData = parseSong($songmatch['file']);

        $title = basename($songmatch['file'], ".pro");
        $proLink = "webchord.cgi?chordpro=" . urlencode(basename($songmatch['file'])); // Pass filename only if in search path, or relative path? 
        // webchord.cgi handles search. Let's pass basename if it's in a standard folder, or relative path.
        // The search() function returns 'file' as full path.
        // webchord.cgi expects 'chordpro' param. If full path, it validates against root.
        // To be safe, let's pass the full path but verify webchord accepts it. 
        // webchord.cgi step 1422 line 453 handles paths with slashes.
        // However, passing full absolute path might be safer if allowed.
        // Let's pass the basename if possible, or relative path?
        // songbook.php search returns absolute paths.
        // let's try just basename if unique, or relative.
        // Actually, webchord.cgi logic: if (slash) -> checks realpath.
        // So let's pass the filename. If it's in a subfolder, we might need relative path.
        // $songmatch['folder'] contains the folder name (e.g. 'Rob').
        $linkParam = ($songmatch['folder']) ? $songmatch['folder'] . '/' . $title . '.pro' : $title . '.pro';
        $proLink = "webchord.cgi?chordpro=" . urlencode($linkParam);


        $artistName = $songmatch['artist'];
        $artistLink = "displayartist.php?artist=" . urlencode($artistName);

        if (!empty($songData['key']))
          $keyHTML = " | Key: <span class='accent-text'>" . htmlspecialchars($songData['key']) . "</span>";
        if (!empty($songData['capo']) && $songData['capo'] !== '0')
          $capoHTML = " | Capo: <span class='accent-text'>" . htmlspecialchars($songData['capo']) . "</span>";

        $metaLine = "by <a href=\"$artistLink\">" . htmlspecialchars($artistName) . "</a>" . $keyHTML . $capoHTML;

        // Lyrics Preview (Simple read first few lines)
        $lines = file($songmatch['file']);
        $preview = "";
        $count = 0;
        foreach ($lines as $line) {
          $line = preg_replace('/\[.*?\]/', '', $line); // Strip chords
          if (trim($line) == "" || strpos(trim($line), "{") === 0 || strpos(trim($line), "#") === 0)
            continue;
          $preview .= htmlspecialchars($line) . "<br>";
          $count++;
          if ($count >= 4)
            break;
        }

        print "<div class='song-card'>";
        print "<div class='song-card-title'><a href=\"$proLink\">$title</a></div>";
        print "<div class='song-card-meta'>$metaLine</div>";
        print "<div class='song-card-lyrics'>$preview</div>";
        print "</div>";
      }
      print "</div>";
    }
    ?>

  </div>

</body>

</html>