#!/usr/bin/perl

# Web Chord v1.1 - A CGI script to convert a ChordPro file to HTML
# Copyright 1998-2003 Martin Vilcans (martin@mamaviol.org)
# Substantial updates from Andrew@DeFaria.com
#
# CGI parameters:
#  chordpro - Filename or full path to the ChordPro file.
#  setlist  - (Optional) Filename of the setlist this song came from.
#  songidx  - (Optional) 0-based index of the song within the setlist.
#  debug    - (Optional) Set to 1 to enable debug output.
#
# History:
# 1998-07-20 Version 1.0
# 2003-08-03 Version 1.1 Uses stylesheets
# 2014-02-05 Added things particular to my implementation of Songbook at
#            https://defaria.com/songbook
# 2025-05-04 Version 2.0: Integrated setlist navigation (Prev/Next buttons),
#            OO CGI style. Added music file path handling, HTML escaping, and
#            other improvements
use strict;
use warnings;

use CGI;
use CGI::Carp qw (fatalsToBrowser);
use File::Basename;
use File::Spec;
use Cwd 'abs_path';
use HTML::Entities;

my $base_dir = "/opt/songbook";
my $song_dir = $base_dir;

my $list_dir = $base_dir;

my $music_base_dir = "/opt/songbook/Music";
my $music_web_path = "/Music";

my $q = CGI->new;

my $debug               = $q->param ('debug');
my $song_filename_param = $q->param ('chordpro');
my $setlist_name_param  = $q->param ('setlist');
my $song_index_param    = $q->param ('songidx');

my $title;
my $infile;

sub debug ($) {
  my ($msg) = @_;
  return unless $debug;

  print "DEBUG: " . HTML::Entities::encode_entities ($msg) . "<br>";
}    # debug

sub warning ($) {
  my ($msg) = @_;

  print "WARNING: " . HTML::Entities::encode_entities ($msg) . "<br>";
}    # warning

sub error {
  my ($msg) = @_;
  print $q->header (
    -type   => 'text/html',
    -status => '500 Internal Server Error'
  );
  print $q->start_html (-title => 'Web Chord: Error');
  print $q->h1         ('Error');
  print $q->p (HTML::Entities::encode_entities ($msg));    # Escape message
  print $q->end_html;
  exit;
}    # error

sub trim {
  my $s = shift;
  return $s unless defined $s;
  $s =~ s/^\s+|\s+$//g;
  return $s;
}    # trim

# Custom JavaScript string escaper to avoid dependency on newer CGI.pm versions.
sub escape_javascript {
  my $str = shift;
  return '' unless defined $str;

  # Escape backslashes first, then other special characters.
  $str =~ s/\\/\\\\/g;
  $str =~ s/'/\\'/g;
  $str =~ s/"/\\"/g;
  $str =~ s/\n/\\n/g;
  $str =~ s/\r/\\r/g;
  return $str;
}    # escape_javascript

# Searches priority folders and base directory, case-insensitively.
sub find_actual_song_path {
  my ($base_search_dir, $filename) = @_;
  debug (
"[find_actual_song_path] Searching for '$filename' starting from '$base_search_dir'"
  );
  return undef unless defined $filename && $filename ne '';
  my $lower_filename = lc ($filename);
  my @dirs_to_search;
  my @priority_folders = ('Rob', 'Bluegrass', 'XMAS');
  push @dirs_to_search,
    map {File::Spec->catdir ($base_search_dir, $_)} @priority_folders;
  push @dirs_to_search, $base_search_dir;
  debug (
    "[find_actual_song_path] Search paths: " . join (", ", @dirs_to_search));

  foreach my $dir (@dirs_to_search) {
    next unless -d $dir;
    opendir my $dh, $dir
      or do {warn "Cannot open directory '$dir': $!"; next;};
    debug ("[find_actual_song_path] Checking dir '$dir'");
    while (my $entry = readdir $dh) {
      if (lc ($entry) eq $lower_filename) {
        my $found_path = File::Spec->catfile ($dir, $entry);
        if (-f $found_path && -r _) {
          closedir $dh;
          debug ("[find_actual_song_path] Found match: '$found_path'");
          return $found_path;
        } else {
          debug (
"[find_actual_song_path] Found matching name '$entry' but not a readable file."
          );
        }
      } ## end if (lc ($entry) eq $lower_filename)
    } ## end while (my $entry = readdir...)
    closedir $dh;
  } ## end foreach my $dir (@dirs_to_search)
  debug ("[find_actual_song_path] Did not find '$filename' in search paths.");
  return undef;
}    # find_actual_song_path

sub musicFileExists ($) {
  my ($base_title) = @_;
  debug ("ENTER musicFileExists ($base_title)");
  return 1 if -r File::Spec->catfile ($music_base_dir, "$base_title.mp3");
  return 1 if -r File::Spec->catfile ($music_base_dir, "$base_title.flac");
  debug ("No music file found for $base_title");
  return 0;
}    # musicFileExists

sub updateMusicpath ($$) {
  my ($chopro_content, $song_filepath) = @_;
  return;
  my $base_title = fileparse ($song_filepath, qr/\.[^.]*$/);
  return unless musicFileExists ($base_title);
  debug ("Music file exists for $base_title");
  if ($chopro_content =~ /\{musicpath:.*\}/i) {
    debug ("Music path already exists in $song_filepath");
    return;
  }
  debug ("Music path missing, attempting to append to $song_filepath");
  my $music_ext =
    (-r File::Spec->catfile ($music_base_dir, "$base_title.mp3"))
    ? "mp3"
    : "flac";
  my $music_path_line = "{musicpath:$music_web_path/$base_title.$music_ext}\n";
  my $songfile_fh;
  unless (open $songfile_fh, '>>', $song_filepath) {
    my $err_msg = "Unable to open $song_filepath for append - $!";
    $err_msg .=
"<br><br>This usually means the web server user (e.g., www-data) needs write permission on the file.";
    $err_msg .= "<br>Please notify the administrator.";
    warning ($err_msg);
    return;
  } ## end unless (open $songfile_fh,...)
  print $songfile_fh $music_path_line;
  close $songfile_fh;
  debug ("Appended music path: $music_path_line");
  return;
}    # updateMusicpath

sub find_prev_valid_index {
  my ($songs_ref, $current_index) = @_;
  for (my $i = $current_index - 1; $i >= 0; $i--) {
    return $i if defined $songs_ref->[$i];
  }
  return undef;
}    # find_prev_valid_index

sub find_next_valid_index {
  my ($songs_ref, $current_index) = @_;
  my $last_index = $#{$songs_ref};
  for (my $i = $current_index + 1; $i <= $last_index; $i++) {
    return $i if defined $songs_ref->[$i];
  }
  return undef;
}    # find_next_valid_index

# Takes ChordPro content string and the original filepath
# Returns a list: ($metadata_hashref, $html_string)
sub chopro2html {
  my ($chopro_content, $song_filepath) = @_;

  my $escaped_content = HTML::Entities::encode_entities ($chopro_content);

  my %meta = (
    title  => fileparse ($song_filepath, qr/\.[^.]*$/),    # Default title
    artist => "Unknown",
  );
  my $html_output = "";

  if ($chopro_content =~ /^\{(?:t|title):\s*(.+?)\s*\}/mi) {
    $meta{title} = trim ($1);
  }
  if ($chopro_content =~ /^\{(?:st|subtitle):\s*(.+?)\s*\}/mi) {
    $meta{artist} = trim ($1);
  }
  if ($chopro_content =~ /^\{key:\s*(.+?)\s*\}/mi) {
    $meta{key} = trim ($1);
  }
  if ($chopro_content =~ /^\{capo:\s*(.+?)\s*\}/mi) {
    $meta{capo} = trim ($1);
  }

  my $mode     = 0;    # 0=lyrics, 1=chorus, 2=tab, 3=chorus_tab
  my @lClasses = ('lyrics', 'lyrics_chorus', 'lyrics_tab', 'lyrics_chorus_tab');
  my @cClasses = ('chords', 'chords_chorus', 'chords_tab', 'chords_chorus_tab');

  # Split both original and escaped content to compare
  my @original_lines = split /\n/, $chopro_content;
  my @escaped_lines  = split /\n/, $escaped_content;

  # Process lines using an index to access both original and escaped versions
  for my $i (0 .. $#original_lines) {
    my $original_line = $original_lines[$i];
    my $line = $escaped_lines[$i];    # This is the escaped line we'll process

    # Check the *original* line content for metadata directives we want to hide.
    if ($original_line =~
/^\s*\{(?:t|title|st|subtitle|duration|key|capo|musicpath|tuning|metronome):.*\}\s*$/i
      )
    {
      debug ("Skipping metadata directive: $original_line");
      next;
    } ## end if ($original_line =~ ...)

    # --- Handle Comment Directives (using original line) ---
    elsif ($original_line =~ /^\s*\{(?:c|comment):\s*(.*?)\s*\}\s*$/i) {
      my $comment_text = trim ($1);

      # Escape the extracted comment text *itself* to prevent HTML injection
      my $escaped_comment = HTML::Entities::encode_entities ($comment_text);

      # Wrap in a div with a specific class for styling (e.g., make it
      # italic/blue in CSS)
      $html_output .= "<div class=\"comment\">$escaped_comment</div>\n";
      debug ("Formatting comment directive: $original_line");
      next;
    } ## end elsif ($original_line =~ ...)

    # --- Handle Chorus/Tab Environment Directives (using original line) ---
    # These change the $mode but don't output the directive line itself
    elsif ($original_line =~ /^\s*\{\s*(soc|start_of_chorus)\s*\}/i) {
      $mode |= 1;    # Set chorus bit
      debug ("Entering chorus mode");
      next;
    } elsif ($original_line =~ /^\s*\{\s*(eoc|end_of_chorus)\s*\}/i) {
      $mode &= ~1;    # Clear chorus bit
      debug ("Exiting chorus mode");
      next;
    } elsif ($original_line =~ /^\s*\{\s*(sot|start_of_tab)\s*\}/i) {
      $mode |= 2;     # Set tab bit
      debug ("Entering tab mode");
      next;
    } elsif ($original_line =~ /^\s*\{\s*(eot|end_of_tab)\s*\}/i) {
      $mode &= ~2;    # Clear tab bit
      debug ("Exiting tab mode");
      next;
    }

    if ($line =~ /^\s*\{&lt;(.*)&gt;}\s*$/) {    # Match escaped { }
          # This block is unlikely to work reliably after escaping.
      $html_output .= "<!-- Escaped command potentially skipped: $line -->\n";
    }

    # Handle comments starting with # (also tricky after escaping)
    elsif ($line =~ /^\s*#(.+)/) {

   # This will only match lines literally starting with # in the escaped output.
      $html_output .=
        "<!-- Escaped comment potentially skipped: " . trim ($1) . " -->\n";
    } ## end elsif ($line =~ /^\s*#(.+)/)

# This regex needs to find escaped brackets: &lt; and &gt; - which it won't.
# It currently checks for literal '[' which might exist if escaping failed or wasn't complete.
    elsif ($line =~ /\[/) {
      debug (
"Processing line with potential chords (using flawed escaped logic): $line"
      );

# This section attempts to parse the already-escaped line, which
# won't correctly identify chords like [C] (now &amp;lbrack;C&amp;rbrack; or similar).
# It will likely treat the line as mostly lyrics containing escaped characters.
      my (@chords, @lyrics);
      my $current_line = $line;          # Use the escaped line
      $current_line =~ s/\s/&nbsp;/g;    # Ensure spaces are non-breaking
      @chords = ("");
      @lyrics = ();

# This regex won't work correctly on escaped content like &amp;lbrack;...&amp;rbrack;
      while ($current_line =~ s/^(.*?)\[(.*?)\]//)  # Tries to match literal '['
      {
        push @lyrics, $1;
        my $chord =
          ($2 eq '\'|') ? '|' : $2;    # Chord content might be escaped entities
        push @chords, $chord;
      } ## end while ($current_line =~ s/^(.*?)\[(.*?)\]//...)
      push @lyrics, $current_line;     # Remaining part of the line
      if ($lyrics[0] eq "" && @lyrics > 1) {shift @chords; shift @lyrics;}

      my $temp_line_check = $line;
      $temp_line_check =~ s/\[.*?\]//g;  # Won't correctly remove escaped chords
      $temp_line_check =~ s/\s+//g;
      my $is_chord_only_line = ($temp_line_check eq '');

      # --- Generate table (based on potentially incorrect parsing) ---
      $html_output .=
"<table cellpadding=0 cellspacing=0 border=0 style='margin-bottom: 5px; border-collapse: collapse;'>";

      # Chord Row
      if (@chords > 1 || ($chords[0] ne '')) {
        $html_output .= "<tr>\n";
        for (my $j = 0; $j < @chords; $j++) {

          # *** Add bold tag if in chorus mode ***
          my $chord_content = $chords[$j];
          if ($mode & 1) {    # Check if chorus bit is set
            $chord_content = "<b>$chord_content</b>";
          }

          # Added padding: 0
          $html_output .=
"<td class=\"$cClasses[$mode]\" style='text-align: left; padding: 0 10px 0 0;'>$chord_content</td>\n";
        } ## end for (my $j = 0; $j < @chords...)
        $html_output .= "</tr>\n";
      } ## end if (@chords > 1 || ($chords...))

# Lyric Row (generate ONLY if it's NOT a chord-only line - check might be flawed)
      unless ($is_chord_only_line) {
        $html_output .= "<tr>\n";
        for (my $j = 0; $j < @lyrics; $j++) {
          my $colspan =
            ($j < $#lyrics || @chords == @lyrics)
            ? 1
            : (@chords - @lyrics + 1);

      # Display whatever was parsed as lyrics (likely includes escaped entities)
          my $lyric_display =
            (($lyrics[$j] eq '') || ($lyrics[$j] eq '&nbsp;'))
            ? '&nbsp;'
            : $lyrics[$j];

          # *** Add bold tag if in chorus mode ***
          my $lyric_content = $lyric_display;
          if ($mode & 1) {    # Check if chorus bit is set
            $lyric_content = "<b>$lyric_content</b>";
          }

          # Added padding: 0
          $html_output .=
              "<td class=\"$lClasses[$mode]\""
            . ($colspan > 1 ? " colspan=$colspan" : "")
            . " style='padding: 0;'>$lyric_content</td>\n";   # Added padding: 0
        } ## end for (my $j = 0; $j < @lyrics...)
        $html_output .= "</tr>\n";
      } ## end unless ($is_chord_only_line)
      $html_output .= "</table>\n";

      # --- End flawed logic ---
    } ## end elsif ($line =~ /\[/) (])

    # Handle plain lyric lines (including those with escaped directives/chords)
    elsif ($line =~ /\S/) {    # If the line contains non-whitespace
      my $formatted_line = $line;       # Use the escaped line
      $formatted_line =~ s/\s/&nbsp;/g; # Ensure non-breaking spaces
                                        # *** Add bold tag if in chorus mode ***
      my $output_line = $formatted_line;
      if ($mode & 1) {                  # Check if chorus bit is set
        $output_line = "<b>$output_line</b>";
      }

      # Apply class based on current mode (chorus/tab)
      $html_output .= "<div class=\"$lClasses[$mode]\">$output_line</div>\n";
    } ## end elsif ($line =~ /\S/)

    # Handle blank lines
    else {
      # Don't output multiple <br> if mode is tab, just one is enough
      if ($mode & 2) {                  # Check if tab bit is set
        $html_output .= "<br>\n"
          unless $html_output =~ /<br>\n$/;    # Avoid consecutive breaks in tab
      } else {
        $html_output .= "<br>\n";
      }
    } ## end else [ if ($line =~ /^\s*\{&lt;(.*)&gt;}\s*$/)]
  } ## end for my $i (0 .. $#original_lines)

  return (\%meta, $html_output);
}    # chopro2html

# --- Validate Song File Parameter ---
unless (defined $song_filename_param) {
  error ("No 'chordpro' parameter specified.");
}

# --- Determine and Validate Song File Path ---
if ($song_filename_param =~ m{/}) {

  # If a path is provided, validate it's within the base directory
  my $real_song_base_dir = eval {abs_path ($base_dir)};
  my $real_song_path     = eval {abs_path ($song_filename_param)};
  unless ($real_song_base_dir
    && $real_song_path
    && $real_song_path =~ /^\Q$real_song_base_dir\E/
    && -f $real_song_path
    && -r _)
  {
    error ("Access denied, invalid path, or file not readable: "
        . $q->escapeHTML ($song_filename_param));
  } ## end unless ($real_song_base_dir...)
  $infile = $real_song_path;
  debug ("Using full path provided: $infile");
} else {

  # If only a filename is provided, search for it
  $infile = find_actual_song_path ($song_dir, $song_filename_param);
  unless (defined $infile) {
    error ("Song file not found: " . $q->escapeHTML ($song_filename_param));
  }
  debug ("Found song file via search: $infile");
} ## end else [ if ($song_filename_param...)]

# --- Read ChordPro File Content ---
my $chopro_content;
open my $file_fh, '<', $infile
  or error ("Unable to open file \"$infile\" - $!");
{local $/ = undef; $chopro_content = <$file_fh>;}
close $file_fh;

# --- Declare Nav Variables (outside the conditional block) ---
my $nav_html             = '';
my $prev_link_html       = '';
my $next_link_html       = '';
my $setlist_js_block     = '';
my $next_song_url_for_js = '';    # Will hold the URL for the JS to use

# --- Generate Setlist Navigation HTML (Conditionally) ---
if ( defined $setlist_name_param
  && defined $song_index_param
  && $setlist_name_param ne ''
  && $song_index_param =~ /^\d+$/)
{
  debug (
"Setlist parameters detected (Set: $setlist_name_param, Idx: $song_index_param). Generating navigation."
  );

  # Basic validation of setlist name parameter
  if (
    $setlist_name_param    =~ m{[\\/]}    # Contains slashes
    || $setlist_name_param =~ m{\.\.}     # Contains '..'
    || $setlist_name_param !~ /\.lst$/i
    )    # Doesn't end with .lst (case-insensitive)
  {
    warning ("Invalid setlist name parameter received: "
        . $q->escapeHTML ($setlist_name_param));
  } else {
    my $setlist_path = File::Spec->catfile ($list_dir, $setlist_name_param);
    debug ("Attempting to read setlist: $setlist_path");
    if (-f $setlist_path && -r _) {
      my @all_song_paths_in_set;      # For JS array
      my @songs_in_list_filenames;    # Holds just the filenames found
      if (open my $setlist_fh, '<', $setlist_path) {
        my $is_first_line = 1;
        while (my $line = <$setlist_fh>) {
          chomp $line;
          $line =~ s/\r$//;   # Remove potential Windows line ending
                              # Skip header line (assuming first line is header)
          if ($is_first_line) {$is_first_line = 0; next;}

          # Skip blank lines and comments
          next if $line =~ /^\s*$/ || $line =~ /^#/;

         # Extract song title (assuming format "Title - Artist" or just "Title")
          my $song_entry_title =
            ($line =~ /^(.*?)\s+-\s+.*/) ? trim ($1) : trim ($line);

          # Construct potential filename (adjust extension if needed)
          my $potential_filename = $song_entry_title . ".pro";

          # Find the actual path and check existence
          my $actual_song_file_path =
            find_actual_song_path ($song_dir, $potential_filename);

          if ($actual_song_file_path) {

            # Store the full path for the JS array
            push @all_song_paths_in_set, $actual_song_file_path;

            # Store the full path for the Prev/Next buttons as well
            push @songs_in_list_filenames, $actual_song_file_path;
            debug (
"Found '$actual_song_file_path' for setlist entry '$song_entry_title'"
            );
          } else {

            # Add undef placeholders to keep indices correct
            push @all_song_paths_in_set,   undef;
            push @songs_in_list_filenames, undef;
          } ## end else [ if ($actual_song_file_path)]
        } ## end while (my $line = <$setlist_fh>)
        close $setlist_fh;

        # --- Calculate Indices and Generate Links ---
        my $num_songs          = @songs_in_list_filenames;
        my $current_song_index = int ($song_index_param);

        debug ("Setlist Check: num_songs = $num_songs");
        debug ("Setlist Check: current_song_index = $current_song_index");

        my $prev_idx = find_prev_valid_index (\@songs_in_list_filenames,
          $current_song_index);
        debug ("Prev Button Check: find_prev_valid_index returned "
            . ($prev_idx // 'undef'));

        if (defined $prev_idx) {
          my $prev_song_file = $songs_in_list_filenames[$prev_idx];

      # Ensure the file is actually defined (should be, but belt-and-suspenders)
          if (defined $prev_song_file) {
            debug (
"Prev Button Check: Using prev_idx = $prev_idx, prev_song_file = $prev_song_file"
            );

            # Manually build the query string...
            my @query_parts;

            # Use fileparse here to get just the filename for the URL
            push @query_parts,
              "chordpro=" . $q->escape (fileparse ($prev_song_file));
            push @query_parts, "setlist=" . $q->escape ($setlist_name_param);
            push @query_parts, "songidx=" . $q->escape ($prev_idx);
            my $prev_url_query_manual = "?" . join ('&', @query_parts);
            my $script_basename       = fileparse ($q->script_name ());

            # Extract title for display
            my ($prev_title)       = fileparse ($prev_song_file, qr/\.[^.]*$/);
            my $escaped_prev_title = $q->escapeHTML ($prev_title || '');

            my $prev_button_html = $q->a ({
                -href  => $script_basename . $prev_url_query_manual,
                -class => 'nav-button prev-button'
              },
              "&#10094; Prev"
            );

            # Wrap button and title
            $prev_link_html = $q->div ({-style => 'text-align: center;'},
              $prev_button_html, $q->br,
              $q->span ({-class => 'nav-title'}, $escaped_prev_title));
          } else {
            debug (
"Prev Button Check: File at index $prev_idx was unexpectedly undef."
            );
          }
        } else {
          debug ("Prev Button Check: No valid previous song found.");
        }

        my $next_idx = find_next_valid_index (\@songs_in_list_filenames,
          $current_song_index);
        debug ("Next Button Check: find_next_valid_index returned "
            . ($next_idx // 'undef'));
        if (defined $next_idx) {
          my $next_song_file = $songs_in_list_filenames[$next_idx];

          # Ensure the file is actually defined
          if (defined $next_song_file) {
            debug (
"Next Button Check: Using next_idx = $next_idx, next_song_file = $next_song_file"
            );

            # Manually build the query string...
            my @query_parts;

            # Use fileparse here to get just the filename for the URL
            push @query_parts,
              "chordpro=" . $q->escape (fileparse ($next_song_file));
            push @query_parts, "setlist=" . $q->escape ($setlist_name_param);
            push @query_parts, "songidx=" . $q->escape ($next_idx);
            my $next_url_query_manual = "?" . join ('&', @query_parts);
            my $script_basename       = fileparse ($q->script_name ());

         # --- NEW: Store the full URL for the audio player's data attribute ---
            $next_song_url_for_js = $script_basename . $next_url_query_manual;
            debug ("Next Song URL for JS: " . $next_song_url_for_js);

            # --- END NEW ---

            # Extract title for display
            my ($next_title)       = fileparse ($next_song_file, qr/\.[^.]*$/);
            my $escaped_next_title = $q->escapeHTML ($next_title || '');

            my $next_button_html = $q->a ({
                -href  => $script_basename . $next_url_query_manual,
                -class => 'nav-button next-button'
              },
              "Next &#10095;"
            );

            # Wrap button and title only if the button was created
            if ($next_button_html) {
              $next_link_html = $q->div ({-style => 'text-align: center;'},
                $next_button_html, $q->br,
                $q->span ({-class => 'nav-title'}, $escaped_next_title));
            }
          } else {
            debug (
"Next Button Check: File at index $next_idx was unexpectedly undef."
            );
          }
        } else {
          debug ("Next Button Check: No valid next song found.");
        }

        # --- NEW: Generate JavaScript block with setlist data ---
        my $js_setlist_name = escape_javascript ($setlist_name_param);
        my $js_song_idx     = $song_index_param;  # Already validated as integer

        # Create the JS array of song paths
        my @js_song_array_items;
        foreach my $path (@all_song_paths_in_set) {
          if (defined $path) {

            # Escape the path for use inside a JS string literal
            push @js_song_array_items, "'" . escape_javascript ($path) . "'";
          } else {

            # Use 'null' for songs that weren't found
            push @js_song_array_items, 'null';
          }
        } ## end foreach my $path (@all_song_paths_in_set)
        my $js_song_array_string = join (", ", @js_song_array_items);

        $setlist_js_block = qq{
<script>
  const setlistName = '$js_setlist_name';
  const currentSongIndex = $js_song_idx;
  const setlistSongs = [$js_song_array_string];
</script>};
      } else {
        warning ("Could not open setlist file '$setlist_path': $!");
      }
    } else {
      warning ("Setlist file '$setlist_path' not found or not readable.");
    }
  } ## end else [ if ($setlist_name_param...)]
} else {
  debug (
    "Setlist parameters not detected or invalid. Skipping nav generation.");
}

# --- Convert ChordPro content to HTML ---
# This calls the subroutine which handles directives and formatting
my ($meta, $song_html) = chopro2html ($chopro_content, $infile);
$title = $meta->{title};    # Use title extracted by chopro2html

# --- Update music path if necessary (after getting title) ---
updateMusicpath ($chopro_content, $infile) if $title;

# --- Prepare HTML Output ---
print $q->header (-type => 'text/html', -charset => 'utf-8');
print $q->start_html (
  -title => $title || "Song",
  -dtd   => "-//W3C//DTD HTML 4.01 Transitional//EN",
  -head  => [
    $q->Link ({
        -rel  => 'stylesheet',
        -type => 'text/css',
        -href => '/songbook/songbook.css?v=' . time ()
      }
    ),
    $q->Link ({
        -rel  => 'stylesheet',
        -type => 'text/css',
        -href => '/songbook/question.mark.css'
      }
    ),
    $q->Link ({
        -rel  => 'shortcut icon',
        -type => 'image/x-icon',          # Or image/png if it's a PNG file
        -href => '/songbook/Music.ico'    # Path to your favicon
      }
    ),
    qq{<script src="}
      . $q->escapeHTML ('/songbook/songbook.js')
      . qq{"></script>},
    $setlist_js_block,    # Add the setlist JS block here
    qq{<script src="}
      . $q->escapeHTML ('/songbook/question.mark.js')
      . qq{"></script>}
  ]
);

my $home_link = $q->a (
  {-href => '/songbook'},
  $q->img ({
      -src    => '/songbook/Music.ico',
      -alt    => 'Home',
      -border => 0,
      -style  => 'width: 100%; height: auto;',
    }
  )
);
(my $profile = $infile) =~ s/^\Q$base_dir\E\/?//;
my $title_link = $q->a (
  {-href => "viewpro.php?file=$profile"},
  $q->escapeHTML ($title || 'Unknown Title')
);
my $artist_link = $q->a ({
    -href => "/songbook/displayartist.php?artist="
      . $q->escape ($meta->{artist}),
    -class => 'accent-text',
  },
  $q->escapeHTML ($meta->{artist})
);
my $audio_source        = '';
my $music_file_web_path = '';

# Check for music file using the potentially updated title
if ($title) {
  if (-r File::Spec->catfile ($music_base_dir, "$title.mp3")) {
    $music_file_web_path = "$music_web_path/" . $q->escapeHTML ("$title.mp3");
    $audio_source = qq{<source src="$music_file_web_path" type="audio/mpeg">};
  } elsif (-r File::Spec->catfile ($music_base_dir, "$title.flac")) {
    $music_file_web_path = "$music_web_path/" . $q->escapeHTML ("$title.flac");
    $audio_source = qq{<source src="$music_file_web_path" type="audio/flac">};
  }
} ## end if ($title)
my $audio_player = '';
if ($audio_source) {
  my $style_attr = 'padding:0; margin:0; width: 85%; vertical-align: middle;';

  my $download_link = $q->a ({
      -href     => $music_file_web_path,
      -download => '',
      -class    => 'accent-text',
      -style    =>
'text-decoration: none; margin-left: 5px; font-size: 1.5em; vertical-align: middle;',
      -title => 'Download Audio'
    },
    '&#11015;'
  );    # Down arrow

  $audio_player =
qq{<audio id="song_audio_player" controls autoplay style="$style_attr" data-next-song-url="$next_song_url_for_js">\n}
    . $audio_source
    . qq{\nYour browser does not support HTML5 Audio.\n</audio>}
    . $download_link;
} ## end if ($audio_source)

# --- Build the content for the last cell (Audio/Marks) ---
my $last_cell_content = $audio_player;
if ($audio_player) {
  $last_cell_content .= $q->br
    . $q->p ({
      -align => 'center',
      -style => 'margin-top: 5px; color: var(--text-color); margin-bottom: 0;'
    },
    $q->span ({-class => 'mark-label'},                "Mark A: "),
    $q->span ({-id    => 'a', -class => 'mark-value'}, "not set"),
    '&nbsp;&nbsp;',
    $q->span ({-class => 'mark-label'},                "Mark B: "),
    $q->span ({-id    => 'b', -class => 'mark-value'}, "not set")
    );
} ## end if ($audio_player)

# --- Print the Heading Table ---
my $setlist_link_html         = '';
my $setlist_link_html_wrapped = '';

if (defined $setlist_name_param && $setlist_name_param ne '') {

  # Create link back to displayset.php
  my $setlist_display_url =
    "/songbook/displayset.php?set=" . $q->escape ($setlist_name_param);

  # Prepare link text by removing .lst extension
  (my $setlist_display_name = $setlist_name_param) =~
    s/\.lst$//i;    # Remove .lst (case-insensitive)

  # Create the link with the correct URL and display name
  my $setlist_link = $q->a ({-href => $setlist_display_url},
    $q->escapeHTML ($setlist_display_name));

  # --- Wrap the link in a div with id="title" ---
  $setlist_link_html_wrapped =
    $q->div ({-id => 'title'}, 'Set: ' . $setlist_link);
} ## end if (defined $setlist_name_param...)

print $q->table (
  {-id => 'heading'},

  $q->Tr (

    # Cell 1: Home Icon and Version
    $q->td ({
        -align  => 'center',
        -width  => '50',
        -valign => 'middle',
      },
      $q->a ({
          -href   => '/songs',
          -target => '_top',
          -style  => 'text-decoration: none;'
        },
        $q->span ({
            -class => 'home-icon',
            -style => 'font-size: 40px; line-height: 1;',
          },
          '&#9835;'
        )
      ),
      $q->div ({
          -class => 'version-text',
        },
        "3.0"
      )
    ),

    # Cell 2: Navigation Buttons (Previous)
    $q->td (
      {-align => 'center', -valign => 'middle', -width => '10%'},
      $prev_link_html || ''
    ),

    # Cell 3: Title and Links
    $q->td ({
        -align => 'center',
      },
      $q->h1 ("Songbook"),
      $q->h2 ($title_link),

      # Links Row (Artist, etc.)
      $q->div (
        {-class => 'dim', -style => 'font-size: 10pt; margin-top: -5px;'},
        "by ",
        $artist_link,
        $q->span (" | Key: "),
        $q->span (
          {-class => 'accent-text'},
          $q->escapeHTML ($meta->{key} || 'Unknown')
        ),
        $q->span (" | Capo: "),
        $q->span (
          {-class => 'accent-text'},
          $q->escapeHTML ($meta->{capo} || '0')
        ),
      ),
      $setlist_link_html_wrapped
    ),

    # Cell 4: Navigation Buttons (Next)
    $q->td (
      {-align => 'center', -valign => 'middle', -width => '10%'},
      $next_link_html || ''
    ),

    # Cell 5: Audio Player / Marks
    $q->td (
      {-align => 'center', -width => '300', -valign => 'middle'},
      $last_cell_content
    )
  )
);

# --- Print Song Content ---
print $q->div ({-id => 'song'}, $song_html);

print $q->end_html;

exit 0;
