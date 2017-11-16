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
#            http://defaria.com/songbook
use strict;
use warnings;

use CGI qw(:standard);
use CGI::Carp qw (fatalsToBrowser);
use File::Basename;

my ($chopro, $output, $i);

my $documentRoot = "/web";
my $debug        = param ('debug');
my $infile       = param ('chordpro');

unless (-f $infile) {
  $infile = '/opt/songbook/Andrew/' . $infile;

  unless (-f $infile) {
    $infile = '/web/xmas/' . param ('chordpro');

    unless (-f $infile) {
      print "Unable to open $infile";
      exit 1;
    } # unless
  } # unless
} # unless

sub debug ($) {
  my ($msg) = @_;

  return unless $debug;

  print "<font color=red><b>Debug:</b></font> $msg<br>";  

  return;
} # debug

sub warning ($) {
  my ($msg) = @_;

  debug "warning";

  print "<font color=orange><b>Warning</b></font> $msg<br>";

  return;
} # warning

sub error {
  my ($msg) = @_;

  print "<html><head><title>Web Chord: Error</title></head>" .
    "<body><h1>Error</h1><p>\n$msg\n</p>" .
    "</body></html>";

  exit;
} # error

sub musicFileExists ($) {
  my ($song) = @_;

  debug "ENTER musicFileExists ($song)";

  my $title     = fileparse ($song, qr/\.pro/);
  my $musicfile = "/opt/media/$title.mp3";

  if (-r $musicfile) {
    debug "Exists!";

    return $title;
  } else {
    debug "Could not find $musicfile";

    return undef;
  } # if
} # musicFileExists

sub updateMusicpath ($$) {
  my ($chopro, $song) = @_;

  my $title = musicFileExists $song;

  # If there's no corresponding music file then do nothing
  return unless $title;

  # If the .pro file already has musicpath then do nothing
  if ($chopro =~ /\{musicpath:.*\}/) {
    debug "$song already has musicpath";
  } # if
  
  return if $chopro =~ /\{musicpath:.*\}/;

  # Otherwise append the musicpath
  my $songfile;

  open $songfile, '>>', $song
    or undef $songfile;

  unless (defined $songfile) {
    my $msg  = "Unable to open $song for append - $!<br>";
       $msg .= "<br>Please notify <a href=\"mailto:adefaria\@gmail.com?subject=Please chmod 666 $song\">Andrew DeFaria</a> so this can be corrected.<br>";
       $msg .= "<br>Thanks"; 
    warning $msg;

    return;
  } # unless

  my $songbase = '/sdcard';

  print $songfile "{musicpath:$songbase/SongBook/Media/$title.mp3}\n";

  close $songfile;

  return;
} # updateMusicPath

# Outputs the HTML code of the chordpro file in the first parameter
sub chopro2html ($$) {
  my ($chopro, $song) = @_;

  $chopro =~ s/\</\&lt;/g; # replace < with &lt;
  $chopro =~ s/\>/\&gt;/g; # replace > with &gt;
  $chopro =~ s/\&/\&amp;/g; # replace & with &amp;

  my $title = "ChordPro Song";
  my $artist = "Unknown";

  if (($chopro =~ /^{title:(.*)}/mi) || ($chopro =~ /^{t:(.*)}/mi)) {
    $title = $1;
  } # if

  if (($chopro =~ /^{subtitle:(.*)}/mi) || ($chopro =~ /^{st:(.*)}/mi)) {
    $artist = $1;
  } # if
  
  print <<END;
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

      $title = musicFileExists $song;

      if ($title) {
        updateMusicpath $chopro, $song;
      } # if

      print << "END";
<table id="heading">
  <tbody>
    <tr>
      <td align="left"><a href="/songbook"><img src="/Icons/Home.png" alt="Home"></a></td>
      <td><div id="title">$title</div>
          <div id="artist"><a href="/songbook/displayartist.php?artist=$artist">$artist</a></div></td>
      <td align="right" width="300px">
        <audio id="song" controls autoplay style="padding:0; margin:0">
          <source src="https://defaria.com/Media/$title.mp3" style="padding:0; margin:0" type='audio/mp3'>
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
  my $mode = 0; # mode defines which class to use

  #mode =           0           1              2             3
  #       normal      chorus         normal+tab    chorus+tab
  my @lClasses = ('lyrics', 'lyrics_chorus', 'lyrics_tab', 'lyrics_chorus_tab'  );
  my @cClasses = ('chords', 'chords_chorus', 'chords_tab', 'chords_chorus_tab'  );

  while($chopro ne '') {
    $chopro =~ s/(.*)\n?//; # extract and remove first line
    $_ = $1;
    chomp;

    if (/^#(.*)/) {                                # a line starting with # is a comment
      print "<!--$1-->\n";                         # insert as HTML comment
    } elsif (/{(.*)}/) {                           # this is a command
      $_ = $1;
      if (/^start_of_chorus/i || /^soc/i) {        # start_of_chorus
        $mode |= 1;
      } elsif (/^end_of_chorus/i || /^eoc/i) {     # end_of_chorus
        $mode &= ~1;
      } elsif (/^comment:/i || /^c:/i) {           # comment
        print "<span class=\"comment\">($')</span>\n";
      } elsif (/^comment_italic:/i || /^ci:/i) {   # comment_italic
        print "<span class=\"comment_italic\">($')</span>\n";
      } elsif (/^comment_box:/i || /^cb:/i) {      # comment_box
        print "<P class=\"comment_box\">$'</P>\n";
      } elsif (/^start_of_tab/i || /^sot/i) {      # start_of_tab
        $mode |= 2;
      } elsif (/^end_of_tab/i || /^eot/i) {        # end_of_tab
        $mode &= ~2;
      } else {
        print "<!--Unsupported command: $_-->\n";
      }
    } else { # this is a line with chords and lyrics
      my(@chords,@lyrics);
      @chords=("");
      @lyrics=();
      s/\s/\&nbsp;/g;         # replace spaces with hard spaces
      while(s/(.*?)\[(.*?)\]//) {
        push(@lyrics,$1);
        push(@chords,$2 eq '\'|' ? '|' : $2);
      }
      push(@lyrics,$_);       # rest of line (after last chord) into @lyrics

      if ($lyrics[0] eq "") {  # line began with a chord
        shift(@chords);       # remove first item
        shift(@lyrics);       # (they are both empty)
      }

      if (@lyrics==0) {  # empty line?
        print "<BR>\n";
      } elsif (@lyrics==1 && $chords[0] eq "") { # line without chords
        print "<div class=\"$lClasses[$mode]\">$lyrics[0]</div>\n";
      } else {
        print "<table cellpadding=0 cellspacing=0>";
        print "<tr>\n";
        my($i);
        for($i = 0; $i < @chords; $i++) {
          print "<td class=\"$cClasses[$mode]\">$chords[$i]</td>";
        }
        print "</tr>\n<tr>\n";
        for($i = 0; $i < @lyrics; $i++) {
          print "<td class=\"$lClasses[$mode]\">$lyrics[$i]</td>";
        }
        print "</tr></table>\n";
      } # if
    } # if
  } # while
  
  print "</div>";
} # chordpro2html

## Main
print header;

unless ($infile) {
  error "No chordpro parameter";
} # unless

open my $file, '<', $infile
  or error "Unable to open file $infile - $!";

{
  local $/;
  $chopro = <$file>;
}

chopro2html ($chopro, $infile);

print end_html();

exit;