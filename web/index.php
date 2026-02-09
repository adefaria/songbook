<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook</title>
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="stylesheet" type="text/css" href="songbook.css?v=<?php echo time(); ?>">
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
        <td align="center" valign="middle" width="50">
          <a href="/songs" target="_top" style="text-decoration: none;">
            <span class="home-icon" style="font-size: 40px; line-height: 1; color: #4285F4;">&#9835;</span>
          </a>
          <div class="version-text">3.0</div>
        </td>
        <td align="center">
          <h1><a href="/songs" target="_top" style="text-decoration: none; color: inherit;">Songbook</a></h1>
        </td>
      </tr>
    </tbody>
  </table>

  <div id="content">
    <p>This is my songbook. It is designed to be a functional web app that can display lead sheets and play audio for
      the songs in the Songbook. It also uses the store of ChordPro formated files and set lists shared from my
      NextCloud server to the Songbook App on my Android Tablet.</p>

    <!-- Navigation Bar (Compact, No Search, No Border) -->
    <div
      style="display: flex; gap: 15px; justify-content: center; align-items: center; padding: 10px; flex-wrap:wrap; margin-top: 20px;">

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



</body>

</html>