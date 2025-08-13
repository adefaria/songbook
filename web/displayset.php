<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
  <meta name="GENERATOR" content="Mozilla/4.61 [en] (Win98; U) [Netscape]">
  <title>Songbook List</title>
  <!-- Ensure CSS paths are correct relative to your web server root -->
  <link rel="stylesheet" type="text/css" media="screen" href="/songbook/songbook.css">
  <link rel="stylesheet" type="text/css" media="screen" href="/css/Music.css">
  <link rel="stylesheet" type="text/css" media="print" href="/css/Print.css">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">

  <?php
  // Include shared functions and variables
  include_once "songbook.php";

  // --- Input Sanitization & Validation ---
  $set_param = $_REQUEST["set"] ?? ''; // Use null coalescing operator for safety

  // Basic check: Ensure $set looks like a filename, ends with .lst, and prevent traversal.
  if (empty($set_param) || basename($set_param) !== $set_param || !str_ends_with(strtolower($set_param), '.lst')) {
    // Handle error appropriately - display message, log, exit
    header("HTTP/1.1 400 Bad Request");
    // Use htmlspecialchars for safety when echoing user input back
    echo "Invalid set list specified: " . htmlspecialchars($set_param);
    // Consider a more user-friendly error page
    exit;
  }
  $set = $set_param; // Use the sanitized variable
  // Generate a display title for the page heading/browser tab
  $set_display_title = htmlspecialchars(basename($set, ".lst"));

  // --- File Path Configuration ---
  global $songbook; // Use the global variable defined in songbook.php
  // Construct the full path to the setlist file
  $set_filepath = "$songbook/$set";

  // --- File Existence Check ---
  // Check if the file exists and is readable before proceeding
  if (!file_exists($set_filepath) || !is_readable($set_filepath)) {
    // Store an error message to display later in the HTML body
    $set_lines = false; // Indicate failure to read
    $error_message = "Error: Set list file '" . htmlspecialchars($set) . "' not found or cannot be read.";
  } else {
    // Read file lines, ignoring newline characters and skipping empty lines
    $set_lines = file($set_filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $error_message = null; // No error encountered yet
  }
  ?>
</head>

<body style="margin-top: 130px; margin-right: 10px; margin-left: 10px; margin-bottom: 10px;">
  <table width="100%" id="heading">
    <tbody>
      <tr>
        <td align="left" style="padding-left: 10px;" valign="middle" width="50">
          <a href="/songbook"><img alt="Home" border="0" src="/songbook/Music.ico"
              style="width: 100%; height: auto;"><br>&nbsp;&nbsp;2.0</a>
        </td>
        <td align=" center">
          <h1 style="color: white"><?php echo $set_display_title; ?></h1>
        </td>
      </tr>
    </tbody>
  </table>

  <div id="content">

    <?php if ($error_message): /* Display error if file reading failed */ ?>
      <p style="color: red; font-weight: bold;"><?php echo $error_message; ?></p>
    <?php else: /* Proceed if file was read successfully */ ?>
      <table class="setlist-table">
        <thead>
          <?php
          // --- Process Setlist Lines Once ---
          $song_details_list = []; // Array to hold processed song data
          $current_song_index = 0; // 0-based index for navigation parameters
          $set_name_from_file = ''; // To store the name from the first line

          // Check if $set_lines is valid and has content
          if ($set_lines !== false && count($set_lines) > 0) {
            // Assume the first line is the set name and remove it from the array
            $set_name_from_file = htmlspecialchars(array_shift($set_lines));

            // Loop through the remaining lines (potential songs)
            foreach ($set_lines as $line) {
              // Skip comment lines (starting with #)
              if (str_starts_with(trim($line), '#')) {
                debug("Skipping comment line: " . $line);
                continue;
              }
              // Skip blank lines
              if (trim($line) === '') {
                debug("Skipping blank line.");
                continue;
              }

              // Parse title and optional artist from the line
              if (preg_match("/(.*)\s+-\s+(.*)/", $line, $matches)) {
                $title = trim($matches[1]);
                $artist_from_line = trim($matches[2]);
              } else {
                $title = trim($line);
                $artist_from_line = '';
              }
              debug("Processing line for title: '" . $title . "'");

              // Find song details using the function from songbook.php
              // Pass null for library to use the default search logic in findSong
              $song_data = findSong($title, null); // Contains file, artist, key, etc.

              // Store necessary info for this song
              $details = [
                'display_title' => htmlspecialchars($title), // Sanitize title from line
                'artist_from_line' => $artist_from_line,
                'song_metadata' => $song_data, // Store the whole array returned by findSong
                // Check if a file was actually found and exists (case-insensitive)
                'found' => (!empty($song_data["file"]) && fileExists($song_data['file'])),
                'index' => $current_song_index // Store the 0-based index for nav links
              ];

              // Add the processed details to our list
              $song_details_list[] = $details;
              $current_song_index++; // Increment index for the next song
            }
          } else if ($set_lines !== false) {
            // File was readable but empty after removing header/comments
            $set_name_from_file = 'Empty Setlist';
            debug("Setlist file was readable but empty after removing header/comments.");
          }
          // Note: The case where $set_lines === false is handled by the $error_message check above

          // Print table column headers
          print <<<END
<tr>
  <th class="centered">#</th>
  <th>Title</th>
  <th>Artist</th>
  <th class="centered">Key</th>
  <th class="centered">Capo</th>
  <th class="right-aligned">Duration</th>
</tr>
END;
          ?>
        </thead>
        <tbody>
          <?php
          // --- Iterate through the collected details and display table rows ---
          $count = 0; // 1-based counter for display
          $totalDuration = 0; // Accumulator for total time

          // Check if there are any songs to display
          if (empty($song_details_list)) {
            print '<tr><td colspan="7" class="centered">No songs found in this setlist.</td></tr>';
          } else {
            foreach ($song_details_list as $details) {
              $count++;
              $song = $details['song_metadata']; // Get the metadata array from findSong
              $song_file_for_link = $song['file'] ?? ''; // Get the file path if found

              // --- Build Link to webchord.cgi with Navigation Parameters ---
              if ($details['found']) {
                // Use fileExists again to ensure we get the correctly cased filename
                $actual_file_path = fileExists($song_file_for_link);
                $actual_filename = basename($actual_file_path); // Get just the filename

                // Build query parameters using http_build_query for safe encoding
                $link_params = http_build_query([
                  'chordpro' => $actual_file_path, // Pass the full path
                  'setlist' => $set,           // Pass the original setlist filename (e.g., MySet.lst)
                  'songidx' => $details['index'] // Pass the 0-based index
                ]);
                // Construct the HTML link
                // Ensure webchord.cgi path is correct relative to web root
                $songtitle_html = "<a href=\"webchord.cgi?$link_params\">{$details['display_title']}</a>";
                debug("Generated link for '{$details['display_title']}': webchord.cgi?" . $link_params);
              } else {
                // Song file not found by findSong/fileExists
                $songtitle_html = $details['display_title'] . " <small>(Not Found)</small>"; // Indicate not found
                debug("Song '{$details['display_title']}' not found by findSong/fileExists.");
              }

              // --- Build Artist HTML (with link) ---
              // Prioritize artist from the set list line, fall back to song metadata
              $display_artist = $details['artist_from_line'] ?: ($song['artist'] ?? '');
              $artist_html = ''; // Default to empty
              if (!empty($display_artist)) {
                $safe_artist = htmlspecialchars($display_artist);
                $url_artist = urlencode($display_artist); // Encode for use in URL
                $artist_html = "<a href=\"displayartist.php?artist=$url_artist\">$safe_artist</a>";
              }

              // --- Key, Capo Display ---
              $display_key = htmlspecialchars($song['key'] ?? '');
              $display_capo = htmlspecialchars($song['capo'] ?? '');

              // --- Duration Calculation and Display ---
              $display_duration = ''; // Default to empty
              // Check if duration exists and matches M:SS format
              if (!empty($song['duration']) && preg_match('/^(\d+):([0-5]?\d)$/', $song["duration"], $time_matches)) {
                // Add to total duration (convert M:SS to seconds)
                $totalDuration += ms2s(intval($time_matches[1]), intval($time_matches[2]));
                $display_duration = htmlspecialchars($song['duration']);
              } else {
                // Optionally display 'N/A' or leave blank if duration missing/invalid
                // $display_duration = 'N/A';
                debug("Invalid or missing duration for '{$details['display_title']}': " . ($song['duration'] ?? 'N/A'));
              }

              // --- Print the HTML Table Row using HEREDOC syntax ---
              print <<<END
<tr>
  <td class="centered">$count</td>
  <td>$songtitle_html</td>
  <td>$artist_html</td>
  <td class="centered">$display_key</td>
  <td class="centered">$display_capo</td>
  <td class="right-aligned">$display_duration</td>
</tr>
END;
            } // end foreach song detail
          } // end if empty check
          ?>
        </tbody>
        <tfoot>
          <?php
          // --- Print Footer Row with Total Duration ---
          // Convert total seconds back to M:SS format
          $total_display = s2ms($totalDuration);
          print <<<END
<tr>
  <th colspan="5" style="text-align: left;">Total Duration</th>
  <td class="right-aligned">$total_display</td>
</tr>
END;
          ?>
        </tfoot>
      </table>
    <?php endif; // End check for $error_message ?>

  </div> <!-- End #content -->

</body> <!-- Added closing body tag -->

</html>
