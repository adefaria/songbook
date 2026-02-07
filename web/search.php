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
<?php if (!$is_embedded): ?>
  <?php include '/opt/defaria.com/includes/header.php'; ?>
  <!-- Additional Songbook Styles for Standalone Mode -->
  <link rel="stylesheet" type="text/css" href="/songbook/songbook.css?v=<?php echo time(); ?>">
  <link rel="stylesheet" type="text/css" href="/songbook/question.mark.css">
  <script src="/songbook/songbook.js"></script>
  <script src="/songbook/question.mark.js"></script>

  <?php include '/opt/defaria.com/includes/wrapper_top.php'; ?>

  <!-- Content Wrapper for Songbook styling compatibility -->
  <div id="songbook-standalone" style="padding: 10px;">

    <style>
      /* Fix header overlap in standalone mode */
      #heading {
        top: 75px !important;
        /* height of .top-bar */
        z-index: 900 !important;
        /* Below top-bar (1000) */
      }

      body {
        padding-top: 195px !important;
        /* 75px top-bar + 120px songbook-header */
      }

      /* Ensure Songbook inputs are visible */
      input,
      select {
        position: relative;
        z-index: 50;
      }
    </style>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Theme Toggle Logic (Matches index.php)
        const btnToDark = document.getElementById('btn-to-dark');
        const btnToLight = document.getElementById('btn-to-light');

        function setCookie(name, value, days) {
          let expires = "";
          if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
          }
          document.cookie = name + "=" + (value || "") + expires + "; path=/";
        }

        function getCookie(name) {
          const nameEQ = name + "=";
          const ca = document.cookie.split(';');
          for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) == ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
          }
          return null;
        }

        function setTheme(theme) {
          document.documentElement.setAttribute('data-theme', theme);
          setCookie('theme', theme, 365);

          // Update Buttons Visibility
          if (theme === 'light') {
            if (btnToDark) btnToDark.style.display = 'block';
            if (btnToLight) btnToLight.style.display = 'none';
          } else {
            if (btnToDark) btnToDark.style.display = 'none';
            if (btnToLight) btnToLight.style.display = 'block';
          }
        }

        // Initial Theme Set
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'dark';
        setTheme(currentTheme);

        // Toggle Handlers
        if (btnToDark) {
          btnToDark.addEventListener('click', () => setTheme('dark'));
        }
        if (btnToLight) {
          btnToLight.addEventListener('click', () => setTheme('light'));
        }

        // Sidebar Active State for 'Music'
        const lyricsTab = document.getElementById('tab-music');
        if (lyricsTab) lyricsTab.classList.add('active');
      });
    </script>
  <?php else: ?>
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
    <?php endif; ?>

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

    <div id="content">
      <table>
        <!-- Artists Dropdown Row -->
        <tr>
          <th><label for="artist-select">Artists:</label></th>
          <td>
            <form method="get" action="displayartist.php" name="artist_form" id="artist-form">
              <select name="artist" id="artist-select" class="uniform-input-width">
                <option value=''>Select an artist...</option>
                <?php
                if (isset($artists) && is_array($artists)) {
                  $sorted_artists = $artists; // Use a copy for sorting
                  sort($sorted_artists);
                  foreach ($sorted_artists as $artist_item) {
                    echo "<option value=\"" . htmlspecialchars($artist_item) . "\">" . htmlspecialchars($artist_item) . "</option>";
                  }
                }
                ?>
              </select>
            </form>
          </td>
          <td><input type="submit" form="artist-form" value="Go"></td>
        </tr>

        <!-- Sets Dropdown Row -->
        <tr>
          <th><label for="set-select">Sets:</label></th>
          <td>
            <form method="get" action="displayset.php" name="set_form" id="set-form">
              <select name="set" id="set-select" class="uniform-input-width">
                <option value=''>Select a set...</option>
                <?php
                if (isset($sets) && is_array($sets)) {
                  $sorted_sets = $sets; // Use a copy for sorting
                  sort($sorted_sets);
                  foreach ($sorted_sets as $set_item) {
                    $title = basename($set_item, ".lst");
                    echo "<option value=\"" . htmlspecialchars($title . ".lst") . "\">" . htmlspecialchars($title) . "</option>";
                  }
                }
                ?>
              </select>
            </form>
          </td>
          <td><input type="submit" form="set-form" value="Go"></td>
        </tr>

        <!-- Songs Dropdown Row -->
        <tr>
          <th><label for="song-select">Songs:</label></th>
          <td>
            <form method="get" action="webchord.cgi" name="song_form" id="song-form">
              <select name="chordpro" id="song-select" class="uniform-input-width">
                <option value=''>Select a song...</option>
                <?php
                if (isset($songs) && is_array($songs)) {
                  $sorted_songs = $songs; // Use a copy for sorting
                  sort($sorted_songs);
                  foreach ($sorted_songs as $song_item) {
                    $title = basename($song_item, ".pro");
                    echo "<option value=\"" . htmlspecialchars($title . ".pro") . "\">" . htmlspecialchars($title) . "</option>";
                  }
                }
                ?>
              </select>
            </form>
          </td>
          <td><input type="submit" form="song-form" value="Go"></td>
        </tr>

        <!-- Search Row -->
        <tr>
          <th><label for="search-q">Search:</label></th>
          <td>
            <form method="get" action="search.php" name="search_form" id="search-form">
              <input type="text" name="q" id="search-q" class="uniform-input-width"
                value="<?php echo htmlspecialchars($searchterm); ?>">
            </form>
          </td>
          <td><input type="submit" form="search-form" value="Search"></td>
        </tr>

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

    <?php if (!$is_embedded): ?>
  </div> <!-- Close songbook-standalone -->
  </main> <!-- Close main -->
  </div> <!-- Close app-container -->
  <?php include '/opt/defaria.com/includes/footer.php'; ?>
<?php else: ?>
  </body>

  </html>
<?php endif; ?>