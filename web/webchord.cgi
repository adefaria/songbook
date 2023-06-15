#!/usr/bin/perl

# Web Chord v1.1 - A CGI script	to convert a ChordPro file to HTML
# Copyright 1998-2003 Martin Vilcans (martin@mamaviol.org)
#
# CGI parameters:
#  chopro - This parameter can be submitted by a form	as a text field or file
#           upload.
#
# History:
# 1998-07-20 Version 1.0
# 2003-08-03 Version 1.1 Uses stylesheets
# 2014-02-05 Added things particular to my implementation of Songbook at
#            https://defaria.com/songbook
use strict;
use warnings;

use CGI       qw(:standard);
use CGI::Carp qw (fatalsToBrowser);
use File::Basename;

my ($chopro, $i);

my $documentRoot = "/web";
my $debug        = param ('debug');
my $infile       = param ('chordpro');
my $title;

sub debug ($) {
  my ($msg) = @_;

  return unless $debug;

  print "<font color=red><b>Debug:</b></font> $msg<br>";

  return;
}    # debug

sub warning ($) {
  my ($msg) = @_;

  debug "warning";

  print "<font color=orange><b>Warning</b></font> $msg<br>";

  return;
}    # warning

sub error {
  my ($msg) = @_;

  print "<html><head><title>Web Chord: Error</title></head>"
    . "<body><h1>Error</h1><p>\n$msg\n</p>"
    . "</body></html>";

  exit;
}    # error

sub getTitle ($) {
  my ($song) = @_;

  return fileparse ($song, qr/\.pro/);
}    # getTitle

sub musicFileExists ($) {
  my ($song) = @_;

  debug "ENTER musicFileExists ($song)";

  my $musicfile = "/opt/media/$title.mp3";

  return -r $musicfile;

  $musicfile = "/opt/media/$title.flac";

  return -r $musicfile;
}    # musicFileExists

sub updateMusicpath ($$) {
  my ($chopro, $song) = @_;

  # If there's no corresponding music file then do nothing
  return unless musicFileExists $song;

  # If the .pro file already has musicpath then do nothing
  return if $chopro =~ /\{musicpath:.*\}/;

  # Otherwise append the musicpath
  my $songfile;

  open $songfile, '>>', $song
    or undef $songfile;

  unless (defined $songfile) {
    my $msg = "Unable to open $song for append - $!<br>";
    $msg .=
"<br>Please notify <a href=\"mailto:adefaria\@gmail.com?subject=Please chmod 666 $song\">Andrew DeFaria</a> so this can be corrected.<br>";
    $msg .= "<br>Thanks";
    warning $msg;

    return;
  }    # unless

  print $songfile "{musicpath:/storage/emulated/0/Music/$title.mp3}\n";

  close $songfile;

  return;
}    # updateMusicPath

# Outputs the HTML code of the chordpro file in the first parameter
sub chopro2html ($$) {
  my ($chopro, $song) = @_;

  $chopro =~ s/\</\&lt;/g;     # replace < with &lt;
  $chopro =~ s/\>/\&gt;/g;     # replace > with &gt;
  $chopro =~ s/\&/\&amp;/g;    # replace & with &amp;

  my $artist = "Unknown";

  if ( ($chopro =~ /^\{title:\s*(.*)\}/mi)
    || ($chopro =~ /^\{title:\s*(.*)\}/mi))
  {
    $title = $1;
  }                            # if

  if ( ($chopro =~ /^\{subtitle:\s*(.*)\}/mi)
    || ($chopro =~ /^\{subtitle:(.*)\}/mi))
  {
    $artist = $1;
  }                            # if

  print <<"END";
<html>
<head>
<title>$title</title>
<link rel="stylesheet" type="text/css" href="songbook.css">
<link rel="stylesheet" type="text/css" href="question.mark.css">
<script src="songbook.js"></script>
<script src="question.mark.js"></script>
</head>

<body>
END

  if ($title) {
    updateMusicpath $chopro, $song;
  }    # if

  (my $profile = $infile) =~ s/\/opt\/songbook\///;
  my $titleLink = "<a href=\"Andrew/$profile\">$title</a>";
  print << "END";
<table id="heading">
  <tbody>
    <tr>
      <td align="left"><a href="/songbook"><img src="/Icons/Home.png" alt="Home"></a></td>
      <td><div id="title">$titleLink</div>
          <div id="artist"><a href="/songbook/displayartist.php?artist=$artist">$artist</a></div></td>
      <td align="right" width="300px">
        <audio id="song" controls autoplay style="padding:0; margin:0">
END

  my $musicFile;

  if (-r "/opt/media/$title.mp3") {
    print "<source src=\"/Media/$title.mp3\"";
  } elsif (-r "/opt/media/$title.flac") {
    print "<source src=\"/Media/$title.flac\"";
  }    # if

  print << "END";
          style="padding:0; margin:0" type='audio/mp3'>
          Your user agent does not support the HTML5 Audio element.
        </audio><br>
        <p align="center" <font size=-1><b>Mark A:</b></font>
                          <font size=-1 color=#666><span id="a"><i>not set</i></span></font>
                          <font size=-1><b>Mark B:</b></font>
                          <font size=-1 color=#666><span id="b">not set</span></font></p>
      </td>
    </tr>
  </tbody>
</table>
<div id="song">
END
  my $mode = 0;    # mode defines which class to use

  #mode =           0           1              2             3
  #       normal      chorus         normal+tab    chorus+tab
  my @lClasses = ('lyrics', 'lyrics_chorus', 'lyrics_tab', 'lyrics_chorus_tab');
  my @cClasses = ('chords', 'chords_chorus', 'chords_tab', 'chords_chorus_tab');

  while ($chopro ne '') {
    $chopro =~ s/(.*)\n?//;    # extract and remove first line
    $_ = $1;
    chomp;

    if (/^#(.*)/) {            # a line starting with # is a comment
      print "<!--$1-->\n";     # insert as HTML comment
    } elsif (/{(.*)}/) {    # this is a command
      $_ = $1;
      if (/^start_of_chorus/i || /^soc/i) {    # start_of_chorus
        $mode |= 1;
      } elsif (/^end_of_chorus/i || /^eoc/i) {    # end_of_chorus
        $mode &= ~1;
      } elsif (/^comment:/i || /^c:/i) {          # comment
        print "<span class=\"comment\">($')</span>\n";
      } elsif (/^comment_italic:/i || /^ci:/i) {    # comment_italic
        print "<span class=\"comment_italic\">($')</span>\n";
      } elsif (/^comment_box:/i || /^cb:/i) {       # comment_box
        print "<P class=\"comment_box\">$'</P>\n";
      } elsif (/^start_of_tab/i || /^sot/i) {       # start_of_tab
        $mode |= 2;
      } elsif (/^end_of_tab/i || /^eot/i) {         # end_of_tab
        $mode &= ~2;
      } else {
        print "<!--Unsupported command: $_-->\n";
      }
    } else {    # this is a line with chords and lyrics
      my (@chords, @lyrics);
      @chords = ("");
      @lyrics = ();
      s/\s/\&nbsp;/g;    # replace spaces with hard spaces
      while (s/(.*?)\[(.*?)\]//) {
        push (@lyrics, $1);
        push (@chords, $2 eq '\'|' ? '|' : $2);
      }
      push (@lyrics, $_);    # rest of line (after last chord) into @lyrics

      if ($lyrics[0] eq "") {    # line began with a chord
        shift (@chords);         # remove first item
        shift (@lyrics);         # (they are both empty)
      }

      if (@lyrics == 0) {        # empty line?
        print "<BR>\n";
      } elsif (@lyrics == 1 && $chords[0] eq "") {    # line without chords
        print "<div class=\"$lClasses[$mode]\">$lyrics[0]</div>\n";
      } else {
        print "<table cellpadding=0 cellspacing=0>";
        print "<tr>\n";
        for (my $i = 0; $i < @chords; $i++) {
          print "<td class=\"$cClasses[$mode]\">$chords[$i]</td>";
        }
        print "</tr>\n<tr>\n";
        for ($i = 0; $i < @lyrics; $i++) {
          print "<td class=\"$lClasses[$mode]\">$lyrics[$i]</td>";
        }
        print "</tr></table>\n";
      }    # if
    }    # if
  }    # while

  print "</div>";
}    # chordpro2html

## Main
print header;

unless ($infile) {
  error "No chordpro parameter";
}    # unless

my $chordpro;

$chordpro = param ('chordpro');

if (-f $chordpro) {
  $infile = $chordpro;
} else {
  my @songbooks = qw(Andrew Rick Mikey Kent Rob Bluegrass Kent XMAS);

  for (@songbooks) {
    if (-f "/opt/songbook/$_/$chordpro") {
      $infile = "/opt/songbook/$_/$chordpro";

      last;
    }    # if
  }    # for
}    # if

open my $file, '<', $infile
  or error "Unable to open file \"$infile\" - $!";

{
  local $/;
  $chopro = <$file>;
}

$title = getTitle $infile;

chopro2html ($chopro, $infile);

print end_html;

exit;
