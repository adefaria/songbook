<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook</title>
  <link rel="stylesheet" type="text/css" media="screen" href="/css/Music.css">
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="stylesheet" type="text/css" href="songbook.css">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">

  <script src="songbook.js"></script>
  <?php
  include_once "songbook.php";
  ?>

</head>

<body style="margin-top: 130px; margin-right: 10px; margin-left: 10px; margin-bottom: 10px;">
  <table width="100%" id="heading">
    <tbody>
      <tr>
        <td align="left" style="padding-left: 10px;" valign="middle" width="50"><img alt="Home"
            src="/songbook/Music.ico" style="width: 100%; height: auto;"><br>&nbsp;2.1</td>
        <td align=" center">
          <h1 style="color: white">Andrew DeFaria's Songbook</h1>
        </td>
      </tr>
    </tbody>
  </table>

  <div id="content">
    <p>As a professional musician do yourself a favor and invest in a tablet or if
      you must an iPad and get either SongBook (for Android) or OnSong (for iPad) then
      send me a request to sign up for Dropbox which integrates with these apps and
      your "songbook" will be automated. Note that Songbook is also available for
      Windows. More info on this is available <a href="songbook.html">here</a>. For
      people who must remain with paper...</p>

    <p>The following songs are available here. Select an artist or a song and then
      Go or type in a lyric or song title into the search box. You can print the
      result if you wish to have a paper copy. If new songs are added by me or others
      this page will automatically update so you can come back here and get your
      copy.</p>

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
                  // The original songsDropdown also showed artist, but the value was just the .pro file.
                  // For simplicity here, just the title is used for display, matching the value.
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
            <input type="text" name="q" id="search-q" class="uniform-input-width">
          </form>
        </td>
        <td><input type="submit" form="search-form" value="Search"></td>
      </tr>

    </table>

</body>

</html>