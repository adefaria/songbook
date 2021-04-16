<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
   "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook</title>
  <link rel="stylesheet" type="text/css" media="screen" href="/css/Music.css">
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="SHORTCUT ICON" href="https://defaria.com/favicon.ico" type="image/png">

<?php
include_once "songbook.php";
?>

</head>

<body>

<table width="100%">
  <tbody>
    <tr>
      <td align="left"><a href="news.html"><img src="/Icons/news.png"></a></td>
      <td align="right"><a href="https://github.com/adefaria/songbook"><img src="/Icons/history.png"></a></td>
    </tr>
  </tbody>
</table>

<div class="heading">
  <h1 class="centered">Andrew DeFaria's Songbook</h1>
</div>

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

<p>Want to <a href="https://www.dropbox.com/sh/jy385ihkuc5ncn4/AAC9eQEWTVmxYPcuJsxrWT8aa?dl=0">download my Songbook songfiles?</a>
and then use the Download dropdown in the upper right corner to either <b>Direct
download</b> (which will download a .zip file with all of the songs) or <b>Save
to Your Dropbox</b>.</p>

 <p>You can also use the same facility to <a href="https://www.dropbox.com/sh/dkadircz25mnqee/AADUFPXW09ovK5hA-8EfL3Eca?dl=0">download
 the corresponding Media (MP3) files</a> to listen to, play along with or place in
 /sdcard/SongBook/Media on your Android tablet so that you can play them directly from
 Songbook! <b>Note:</b> Do not do a Direct Download as all of the media files are too large for Dropbox to zip.</p>
</body>
</html>
