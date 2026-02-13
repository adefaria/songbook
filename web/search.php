<?php
include_once "songbook.php";

$searchterm = isset($_REQUEST["q"]) ? $_REQUEST["q"] : "";

// Check if we are running embedded (in iframe) or standalone
$is_embedded = isset($_REQUEST['bypass']) && $_REQUEST['bypass'] == 'true';

// --- Helper Functions ---

function getSongText($song)
{
  return join("\n", file($song));
} // getSongText

function getSongsByArtist($artistName, $limit = 3)
{
  global $songs;
  $found = [];
  foreach ($songs as $song) {
    if (strcasecmp(getArtist($song), $artistName) === 0) {
      $found[] = basename($song, ".pro");
      if (count($found) >= $limit)
        break;
    }
  }
  return $found;
}

function getSongsInSet($setFile, $limit = 3)
{
  $lines = file($setFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
  $found = [];
  if (!$lines)
    return [];
  // Skip header
  array_shift($lines);
  foreach ($lines as $line) {
    if (str_starts_with(trim($line), '#'))
      continue;
    if (preg_match("/(.*)\s+-\s+(.*)/", $line, $matches)) {
      $found[] = trim($matches[1]);
    } else {
      $found[] = trim($line);
    }
    if (count($found) >= $limit)
      break;
  }
  return $found;
}

function searchArtists($term)
{
  global $artists;
  $matches = [];
  if (empty($term))
    return [];
  $term = strtolower($term);

  if (is_array($artists)) {
    foreach ($artists as $artist) {
      if (strpos(strtolower($artist), $term) !== false) {
        $matches[] = [
          'name' => $artist,
          'songs' => getSongsByArtist($artist)
        ];
      }
    }
  }
  return $matches;
}

function searchSets($term)
{
  global $sets;
  $matches = [];
  if (empty($term))
    return [];
  $term = strtolower($term);

  if (is_array($sets)) {
    foreach ($sets as $set) {
      $title = basename($set, ".lst");
      if (strpos(strtolower($title), $term) !== false) {
        $matches[] = [
          'name' => $title,
          'file' => $set,
          'songs' => getSongsInSet($set)
        ];
      }
    }
  }
  return $matches;
}

function searchSongs($searchterm)
{
  global $songs, $songbook;
  $songmatches = [];

  // Logic 1: If empty searchterm, return ALL songs
  if (empty($searchterm)) {
    foreach ($songs as $song) {
      if (preg_match("#$songbook/(\S+)/#", $song, $matches)) {
        $folder = $matches[1];
      } else {
        $folder = '';
      }
      $songEntry = array(
        'file' => $song,
        'folder' => $folder,
        'artist' => getArtist($song),
      );
      $songmatches[] = $songEntry;
    }
    return $songmatches;
  }

  $tokens = preg_split("/\s+/", $searchterm);
  $searchfor = join(".*", $tokens);

  foreach ($songs as $song) {
    if (preg_match("#$songbook/(\S+)/#", $song, $matches)) {
      $folder = $matches[1];
    } else {
      $folder = '';
    } // if

    $songEntry = array(
      'file' => $song,
      'folder' => $folder,
      'artist' => getArtist($song),
    );

    $text = getSongText($song);

    if (preg_match("/$searchfor/i", $text)) {
      array_push($songmatches, $songEntry);
    } // if
  } // foreach

  return $songmatches;
} // searchSongs

// --- Main Logic ---

$type = $_REQUEST['type'] ?? 'omni';

$artistMatches = [];
$setMatches = [];
$songMatches = [];

if ($type !== 'song') {
  $artistMatches = searchArtists($searchterm);
  $setMatches = searchSets($searchterm);
}
$songMatches = searchSongs($searchterm);

$totalMatches = count($artistMatches) + count($setMatches) + count($songMatches);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html class="scroll-enabled">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <title>Songbook: Search Results</title>
  <link rel="stylesheet" type="text/css" href="songbook.css?v=<?php echo time(); ?>">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">
  <script src="songbook.js"></script>
  <?php include_once "songbook.php"; ?>

  <!-- Inject allSongs for Autocomplete -->
  <script>
    var allSongs = [];
    <?php
    $js_songs = [];
    if (isset($songs) && is_array($songs)) {
      // Create a fresh copy to sort without affecting global order if that matters
      // (Though global $songs is already sorted in songbook.php usually? No, getting it via glob)
      // Let's just use the global $songs logic
      foreach ($songs as $song_item) {
        $title = basename($song_item, ".pro");
        $lyrics = getSearchableLyrics($song_item);
        $js_songs[] = [
          'title' => $title,
          'file' => $song_item,
          'lyrics' => $lyrics
        ];
      }
    }
    if (!empty($js_songs)) {
      echo "allSongs = " . json_encode($js_songs) . ";\n";
    }
    ?>
    console.log("allSongs loaded in search.php:", allSongs.length);
  </script>



  <style>
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      padding: 0 10px;
      /* Add some side padding */
    }

    .song-card {
      height: 100%;
      /* Make cards fill grid cell height */
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
    }

    .song-card-lyrics {
      flex-grow: 1;
      /* Push footer content down if any */
      margin-top: 10px;
    }
  </style>
</head>

<body class="scroll-enabled" style="margin-top: 110px; margin-right: 10px; margin-left: 10px; margin-bottom: 120px;">
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
          <h2>Search results for "<?php echo htmlspecialchars($searchterm); ?>"</h2>
          <h3 style="margin: 5px 0 0 0; font-size: 1rem; color: var(--accent-color);">
            <?php
            if ($totalMatches == 0) {
              print "No matches found";
            } else {
              $counts = [];
              if (count($artistMatches) > 0)
                $counts[] = count($artistMatches) . " artists";
              if (count($setMatches) > 0)
                $counts[] = count($setMatches) . " sets";
              if (count($songMatches) > 0)
                $counts[] = count($songMatches) . " songs";
              print implode(", ", $counts) . " matched";
            }
            ?>
          </h3>
        </td>
        <td width="50">&nbsp;</td>
      </tr>
    </tbody>
  </table>

  <div id="content">

    <?php if ($totalMatches > 0): ?>
      <div class="card-grid">

        <!-- Artist Matches -->
        <?php foreach ($artistMatches as $artist): ?>
          <div class="song-card">
            <div class="song-card-title">
              Artist:
              <a
                href="displayartist.php?artist=<?php echo urlencode($artist['name']); ?>"><?php echo htmlspecialchars($artist['name']); ?></a>
            </div>
            <div class="song-card-meta">
              <?php
              $links = [];
              foreach ($artist['songs'] as $sTitle) {
                // Display as separate lines
                $link = "<a href=\"webchord.cgi?chordpro=" . urlencode($sTitle . ".pro") . "\">" . htmlspecialchars($sTitle) . "</a>";
                echo "<div style='margin-bottom: 4px;'>" . $link . "</div>";
              }
              if (empty($artist['songs'])) {
                echo "No songs found";
              }
              ?>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Set Matches -->
        <?php foreach ($setMatches as $set): ?>
          <div class="song-card">
            <div class="song-card-title">
              <?php
              // sets are absolute paths from searchSets. We need relative to $songbook for displayset.php
              global $songbook;
              $relPath = str_replace($songbook . '/', '', $set['file']);
              ?>
              Set:
              <a
                href="displayset.php?set=<?php echo urlencode($relPath); ?>"><?php echo htmlspecialchars($set['name']); ?></a>
            </div>
            <div class="song-card-meta">
              <?php
              if (empty($set['songs'])) {
                echo "No songs found in set";
              } else {
                echo "<ul style='margin: 0; padding-left: 20px;'>";
                foreach ($set['songs'] as $sTitle) {
                  echo "<li><a href=\"webchord.cgi?chordpro=" . urlencode($sTitle . ".pro") . "&setlist=" . urlencode(basename($set['file'])) . "\">" . htmlspecialchars($sTitle) . "</a></li>";
                }
                echo "</ul>";
              }
              ?>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Song Matches -->
        <?php foreach ($songMatches as $songmatch): ?>
          <?php
          $songData = parseSong($songmatch['file']);
          $title = basename($songmatch['file'], ".pro");

          $linkParam = ($songmatch['folder']) ? $songmatch['folder'] . '/' . $title . '.pro' : $title . '.pro';
          $proLink = "webchord.cgi?chordpro=" . urlencode($linkParam);

          $artistName = $songmatch['artist'];
          $artistLink = "displayartist.php?artist=" . urlencode($artistName);
          $keyHTML = !empty($songData['key']) ? " | Key: <span class='accent-text'>" . htmlspecialchars($songData['key']) . "</span>" : "";

          $lines = file($songmatch['file']);
          $lyricsText = "";
          foreach ($lines as $line) {
            $line = trim(preg_replace('/\[.*?\]/', '', $line));
            if ($line == "" || strpos($line, "{") === 0 || strpos($line, "#") === 0)
              continue;
            $lyricsText = $line;
            break;
          }
          if (strlen($lyricsText) > 45) {
            $lyricsText = substr($lyricsText, 0, 42) . "...";
          }
          ?>
          <div class="song-card">
            <div class="song-card-title">Song: <a href="<?php echo $proLink; ?>"><?php echo $title; ?></a></div>
            <div class="song-card-meta">By <a
                href="<?php echo $artistLink; ?>"><?php echo htmlspecialchars($artistName); ?></a><?php echo $keyHTML; ?>
            </div>
            <div class="song-card-lyrics"><?php echo htmlspecialchars($lyricsText); ?></div>
          </div>
        <?php endforeach; ?>

      </div> <!-- End card-grid -->
    <?php endif; ?>

  </div>

</body>

</html>