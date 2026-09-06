<?php
// Include shared functions and variables
include_once "songbook.php";

global $songbook;

// --- Handle POST Actions (Save / Delete) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $set_title = trim($_POST['set_title'] ?? '');
        $old_set = trim($_POST['old_set'] ?? '');
        $submitted_songs = $_POST['songs'] ?? [];

        if (empty($set_title)) {
            $error = "Set List title cannot be empty.";
        } else {
            // Requirement 4: Do NOT change the name of the .lst file if editing an existing set
            if (!empty($old_set) && str_ends_with(strtolower($old_set), '.lst')) {
                $target_filename = basename($old_set);
            } else {
                // New set list: Generate filename from title
                $clean_title = preg_replace('/[^\w\s\-\.\'\(\)&]/u', '', $set_title);
                $clean_title = trim($clean_title);
                if (empty($clean_title)) {
                    $clean_title = "Untitled Set";
                }
                $target_filename = $clean_title . ".lst";
            }

            $target_filepath = "$songbook/" . basename($target_filename);

            // First line is the title of the set list, followed by song titles
            $file_lines = [];
            $file_lines[] = $set_title;

            if (is_array($submitted_songs)) {
                foreach ($submitted_songs as $song) {
                    $song_trimmed = trim($song);
                    if ($song_trimmed !== '') {
                        $file_lines[] = $song_trimmed;
                    }
                }
            }

            $content_to_write = implode("\n", $file_lines) . "\n";

            // Save file
            if (file_put_contents($target_filepath, $content_to_write) !== false) {
                @chmod($target_filepath, 0664);
                header("Location: displayset.php?set=" . urlencode(basename($target_filename)));
                exit;
            } else {
                $error = "Failed to save set list file. Check directory write permissions.";
            }
        }
    } else if ($action === 'delete') {
        $set_to_delete = trim($_POST['set_to_delete'] ?? '');
        if (!empty($set_to_delete) && str_ends_with(strtolower($set_to_delete), '.lst')) {
            $filepath_to_delete = "$songbook/" . basename($set_to_delete);
            if (file_exists($filepath_to_delete)) {
                @unlink($filepath_to_delete);
            }
        }
        header("Location: index.php");
        exit;
    }
}

// --- GET Request: Load Set List Data ---
$set_param = $_GET['set'] ?? '';
$set_filename = '';
$set_display_title = '';
$existing_songs = [];
$is_new = true;

if (!empty($set_param)) {
    // Sanitize set parameter
    $clean_param = basename($set_param);
    if (!str_ends_with(strtolower($clean_param), '.lst')) {
        $clean_param .= '.lst';
    }

    $filepath = "$songbook/$clean_param";
    if (file_exists($filepath) && is_readable($filepath)) {
        $set_filename = $clean_param;
        $is_new = false;
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines && count($lines) > 0) {
            $set_display_title = array_shift($lines); // First line is set list name
            foreach ($lines as $line) {
                $line_trimmed = trim($line);
                if (!empty($line_trimmed) && !str_starts_with($line_trimmed, '#')) {
                    // Parse song title if format is Title - Artist
                    if (preg_match("/(.*)\s+-\s+(.*)/", $line_trimmed, $matches)) {
                        $song_title = trim($matches[1]);
                    } else {
                        $song_title = $line_trimmed;
                    }

                    // Lookup song details for duration and file link
                    $song_data = findSong($song_title, null);
                    $existing_songs[] = [
                        'title' => $song_title,
                        'duration' => $song_data['duration'] ?? '',
                        'file' => !empty($song_data['file']) ? basename($song_data['file']) : ($song_title . '.pro')
                    ];
                }
            }
        }
    }
}

if (empty($set_display_title) && !$is_new) {
    $set_display_title = basename($set_filename, ".lst");
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html class="scroll-enabled">

<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Songbook - <?php echo $is_new ? "Create Set List" : "Edit " . htmlspecialchars($set_display_title); ?></title>

  <link rel="stylesheet" type="text/css" href="/songbook/songbook.css?v=<?php echo time(); ?>">
  <link rel="SHORTCUT ICON" href="/songbook/Music.ico" type="image/png">

  <script src="/songbook/songbook.js?v=<?php echo time(); ?>"></script>
  <script src="/songbook/seteditor.js?v=<?php echo time(); ?>"></script>

  <!-- Inject allSongs data with duration for autocomplete -->
  <script>
    var allSongs = [];
    <?php
    $js_songs = [];
    if (isset($songs) && is_array($songs)) {
      foreach ($songs as $song_item) {
        $title = basename($song_item, ".pro");
        $song_parsed = parseSong($song_item);
        $lyrics = getSearchableLyrics($song_item);
        $js_songs[] = [
          'title' => $title,
          'file' => basename($song_item),
          'duration' => $song_parsed['duration'] ?? '',
          'lyrics' => $lyrics
        ];
      }
    }
    if (!empty($js_songs)) {
      echo "allSongs = " . json_encode($js_songs) . ";\n";
    }
    ?>
  </script>
</head>

<body class="scroll-enabled" style="margin-top: 130px; margin-right: 10px; margin-left: 10px; margin-bottom: 20px;">
  <div class="help-icon">
    <a href="/songs/help.html" target="_top" title="Help / User Guide">🛟</a>
  </div>

  <table width="100%" id="heading">
    <tbody>
      <tr>
        <td align="center" valign="middle" width="50">
          <a href="/songs" target="_top" style="text-decoration: none;">
            <span class="home-icon" style="font-size: 40px; line-height: 1; color: #4285F4;">&#9835;</span>
          </a>
          <div class="version-text">4.0</div>
        </td>
        <td align="center">
          <h1><a href="/songs" target="_top" style="text-decoration: none; color: inherit;">Songbook</a></h1>
          <h2><?php echo $is_new ? "Create Set List" : "Edit Set List"; ?></h2>
        </td>
      </tr>
    </tbody>
  </table>

  <div id="content" style="max-width: 800px; margin: 0 auto; padding: 10px;">

    <?php if (!empty($error)): ?>
      <div style="background-color: #ff333322; color: #ff4444; border: 1px solid #ff4444; padding: 10px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
        <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form id="setlist-editor-form" method="POST" action="editset.php">
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="old_set" value="<?php echo htmlspecialchars($set_filename); ?>">

      <!-- Set List Header & Title Field -->
      <div class="editor-section">
        <label for="set-title-input" class="editor-label">Set List Title:</label>
        <input type="text" id="set-title-input" name="set_title" class="editor-input-title"
          value="<?php echo htmlspecialchars($set_display_title); ?>"
          placeholder="e.g. Saturday Night Show" required autocomplete="off">
      </div>

      <!-- Add Song Search Box -->
      <div class="editor-section">
        <label for="editor-song-search" class="editor-label">Add Songs to Set List:</label>
        <div style="position: relative;">
          <input type="text" id="editor-song-search" class="song-search-input editor-search-bar"
            placeholder="🔍 Search song title or lyrics to add..." autocomplete="off">
          <div id="editor-autocomplete-results" class="autocomplete-results"></div>
        </div>
      </div>

      <!-- Songs List Header with Counter & Total Duration -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; margin-bottom: 8px;">
        <h3 style="margin: 0; text-align: left; color: var(--accent-color);">Songs in Set List</h3>
        <span id="song-count-badge" class="badge">0 songs | Total: 0:00</span>
      </div>

      <!-- Empty State Notice -->
      <div id="empty-setlist-notice" class="empty-notice" style="<?php echo count($existing_songs) > 0 ? 'display: none;' : ''; ?>">
        No songs in set list yet. Use the search box above to find and add songs.
      </div>

      <!-- Drag & Drop Reorderable List -->
      <ul id="setlist-items" class="setlist-editor-list">
        <?php foreach ($existing_songs as $index => $song): ?>
          <li class="setlist-item" draggable="true" data-duration="<?php echo htmlspecialchars($song['duration']); ?>">
            <span class="drag-handle" title="Drag to reorder">&#8942;&#8942;</span>
            <span class="item-number"><?php echo ($index + 1) . '.'; ?></span>
            <a href="webchord.cgi?chordpro=<?php echo urlencode($song['file']); ?>" class="item-title-link" title="Open song page">
              <?php echo htmlspecialchars($song['title']); ?>
            </a>
            <input type="hidden" name="songs[]" value="<?php echo htmlspecialchars($song['title']); ?>">
            <?php if (!empty($song['duration'])): ?>
              <span class="item-duration" title="Song duration">⏱️ <?php echo htmlspecialchars($song['duration']); ?></span>
            <?php else: ?>
              <span class="item-duration empty">--:--</span>
            <?php endif; ?>
            <button type="button" class="remove-song-btn" title="Remove song">&times;</button>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Action Button Bar -->
      <div class="editor-actions">
        <button type="submit" class="btn btn-primary">💾 Save Set List</button>

        <?php if (!$is_new && !empty($set_filename)): ?>
          <a href="displayset.php?set=<?php echo urlencode($set_filename); ?>" class="btn btn-secondary">Cancel</a>
        <?php else: ?>
          <a href="index.php" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>

        <button type="button" onclick="window.print();" class="btn btn-blue print-btn" style="cursor: pointer;">🖨️ Print Set</button>

        <?php if (!$is_new && !empty($set_filename)): ?>
          <button type="button" class="btn btn-danger" onclick="confirmDeleteSet('<?php echo htmlspecialchars(addslashes($set_filename)); ?>', '<?php echo htmlspecialchars(addslashes($set_display_title)); ?>')">
            🗑️ Delete Set List
          </button>
        <?php endif; ?>
      </div>
    </form>

    <!-- Hidden Form for Deletion -->
    <?php if (!$is_new && !empty($set_filename)): ?>
      <form id="delete-set-form" method="POST" action="editset.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="set_to_delete" value="<?php echo htmlspecialchars($set_filename); ?>">
      </form>
    <?php endif; ?>

  </div>

  <script>
    function confirmDeleteSet(filename, title) {
      if (confirm('Are you sure you want to delete the set list "' + title + '"?\nThis action cannot be undone.')) {
        document.getElementById('delete-set-form').submit();
      }
    }
  </script>
</body>

</html>
