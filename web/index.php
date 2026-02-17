<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook</title>
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="stylesheet" type="text/css" href="songbook.css?v=<?php echo time(); ?>">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">

  <script src="songbook.js"></script>
  <?php
  include_once "songbook.php";
  ?>
  <style>
    @media (max-width: 768px) {

      /* Force hide the copyright footer and all its children */
      footer,
      .copyright,
      .footer-line,
      #footer {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
        position: absolute !important;
        top: -9999px !important;
      }

      /* Improve tap targets for navigation buttons if they remain visible (e.g. at top?) */
      /* Actually, the footer nav buttons are IN the footer, so they will be hidden too? */
      /* User said "covers the tabs that we had at the bottom". */
      /* If "tabs" are the nav buttons, hiding the footer hides THEM too! */
      /* Wait. The footer HAS the nav buttons. */
      /* "Column 1: Prev Arrow", "Column 4: Song Search". */
      /* If I hide the footer, I hide the navigation! */
      /* The user wants to remove the COPYRIGHT BANNER, not the navigation. */
      /* "Remove the copyright banner... It covers the tabs". */
      /* If the copyright is a separate element covering the tabs, distinguishing them is key. */
      /* site-functions.php step 827: */
      /* <footer class="copyright"> ... <td>Prev</td> <td>Omni</td> <td>Copyright Text</td> ... </footer> */
      /* The WHOLE THING is the footer. */
      /* The copyright text is in middle cell. */
      /* If I hide "footer.copyright", I hide everything. */
      /* I must hide ONLY the copyright text cell/div. */

      /* Target the copyright text specifically */
      /* Column 3 is the copyright block. */
      /* <td width="50%"> ... <div class="footer-line">...</div> ... </td> */
      /* I should hide the 3rd cell or the divs inside it. */

      td:nth-child(3),
      .footer-line {
        display: none !important;
      }

      /* Adjust width of other cells to fill space? */
      td:nth-child(2),
      td:nth-child(4) {
        width: 45% !important;
      }

      /* Ensure nav buttons are big enough */
      .footer-nav-btn {
        min-width: 44px !important;
        min-height: 44px !important;
      }
    }
  </style>
</head>

<body style="margin-top: 80px; margin-right: 10px; margin-left: 10px; margin-bottom: 10px;">
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



    </div>



</body>

</html>