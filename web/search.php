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
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook: Search</title>
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="stylesheet" type="text/css" href="/songbook/songbook.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" type="text/css" href="/songbook/question.mark.css">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">
  <script src="/songbook/songbook.js"></script>
  <script src="/songbook/question.mark.js"></script>
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
          <h1>Songbook</h1>
          <h2>Search Results</h2>
        </td>
      </tr>
    </tbody>
  </table>

  <div id="content">
    <!-- Navigation Table Removed -->

    <h2><?php
    if (count($songmatches) == 0) {
      print "No songs matched \"$searchterm\"";
    } elseif (count($songmatches) == 1) {
      // Automatic redirect if only one match
      $singleMatch = $songmatches[0];
      $targetUrl = "webchord.cgi?chordpro=" . urlencode($singleMatch['file']);
      // Ideally this should be a header redirect, but since we already printed HTML head...
      // We can use JS or meta refresh, or just rely on the user clicking.
      // But the request was "go to the song page".
      // Let's use JS for immediate effect since headers might have been sent (although included at top)
      // Actually, line 100 is inside the body.
      // Let's try JS redirect.
      print "<script>window.location.href = '$targetUrl';</script>";
      print "One song matched \"$searchterm\". Redirecting...";
    } else {
      print count($songmatches) . " songs matched \"$searchterm\"";
    } // if
    ?></h2>

    <?php
    if (count($songmatches) > 0) {
      print "<div class='song-list-container'>";

      foreach ($songmatches as $songmatch) {
        // Parse song to get details (Key, Capo)
        $songData = parseSong($songmatch['file']);

        $title = basename($songmatch['file'], ".pro");
        $proLink = "webchord.cgi?chordpro=" . urlencode($songmatch['file']);

        $artistName = $songmatch['artist'];
        $artistLink = "displayartist.php?artist=" . urlencode($artistName);

        $keyHTML = $songData['key'] ? " | Key: " . htmlspecialchars($songData['key']) : "";
        $capoHTML = $songData['capo'] ? " | Capo: " . htmlspecialchars($songData['capo']) : "";

        $metaLine = "by <a href=\"$artistLink\">" . htmlspecialchars($artistName) . "</a>" . $keyHTML . $capoHTML;

        // Lyrics Preview
        $preview = htmlspecialchars(getLyricsPreview($songmatch['file']));

        print "<div class='song-card'>";
        print "<div class='song-card-title'><a href=\"$proLink\">$title</a></div>";
        print "<div class='song-card-meta'>$metaLine</div>";
        print "<div class='song-card-lyrics'>$preview</div>";
        print "</div>";
      }

      print "</div>"; // song-list-container
    } // if
    ?>

  </div>

</body>

</html>