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
        <td align="left" style="padding-left: 10px;" valign="middle" width="50"><img alt="Home" src="/Icons/Home.ico"
            style="width: 100%; height: auto;"><br>&nbsp;&nbsp;2.0</td>
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

    <?php
    artistsDropdown();
    setsDropdown();
    songsDropdown();
    ?>

    <form method="get" action="search.php" name="search">
      Search:&nbsp;<input type="text" name="q"><input type="submit" value="Search">

    </form>
</body>

</html>