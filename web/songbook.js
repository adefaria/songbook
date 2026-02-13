// Javascript functions for controlling audio
var starttime = 0;
var endtime = 0;
var ascrollpoint = 0;
var bscrollpoint = 0;

var song, interval, scroll, body;

/**
 * Navigates to the next song in the setlist if setlist data is available.
 * This function relies on global variables `setlistSongs`, `currentSongIndex`,
 * and `setlistName` being defined in the HTML page by the server.
 */
function goToNextSong() {
  // Check if the necessary setlist variables exist.
  if (
    typeof setlistSongs !== "undefined" &&
    typeof currentSongIndex !== "undefined" &&
    typeof setlistName !== "undefined"
  ) {
    const nextSongIndex = currentSongIndex + 1;

    // Check if there is a next song in the array.
    if (nextSongIndex < setlistSongs.length) {
      const nextSongFile = setlistSongs[nextSongIndex];
      // Construct the URL for the next song page.
      window.location.href = `webchord.cgi?chordpro=${encodeURIComponent(
        nextSongFile
      )}&setlist=${encodeURIComponent(setlistName)}&songidx=${nextSongIndex}`;
    } else {
      // Optionally, navigate back to the setlist display page when the set is over.
      window.location.href = `displayset.php?set=${encodeURIComponent(
        setlistName
      )}`;
    }
  }
}

// Key constants
const KEY_SPACE = "Space";
const KEY_ARROW_LEFT = "ArrowLeft";
const KEY_ARROW_RIGHT = "ArrowRight";
const KEY_A = "KeyA"; // For 'seta'
const KEY_B = "KeyB"; // For 'setb'
const KEY_C = "KeyC"; // For 'cleara' (historically 'C')
const KEY_D = "KeyD"; // For 'download'
const KEY_R = "KeyR"; // For 'return2start'=

const howmanysecs = 10;
const oneSec = 1000;

function loop() {
  // If endtime is not set then we can't loop
  if (endtime == 0) return;

  // if we're past the endtime then it's time to start back at the A marker
  if (song.currentTime > endtime) {
    song.currentTime = starttime;
    if (ascrollpoint != 0) window.scrollTo(0, ascrollpoint);
  } // if
} // loop




document.addEventListener("DOMContentLoaded", function () {
  song = document.getElementById("song_audio_player");
  body = document.body; // Use document.body directly

  if (!song) {
    console.error(
      "Audio player #song_audio_player not found. Audio controls will not work."
    );
    return;
  }

  // Prevent audio player from stealing focus (which breaks shortcuts)
  song.addEventListener("focus", function () {
    this.blur();
  });

  // Initialize times. `endtime` will be accurately set on `loadedmetadata`.
  starttime = 0; // Default start is the very beginning
  endtime = song.duration || 0; // Initial guess, will be updated

  song.addEventListener("loadedmetadata", function () {
    endtime = song.duration;
    // If 'C' (clear) was pressed, starttime is 0, endtime is duration.
    // If 'A' was set, starttime is its value.
    // If 'B' was set, endtime is its value.
    // This ensures loop() uses the correct duration from the start.
  });

  song.addEventListener("play", function () {
    clearInterval(interval); // Clear existing intervals to prevent duplicates
    interval = setInterval(loop, oneSec);

    // Scroll to A marker or top when play starts
    if (ascrollpoint !== 0) {
      window.scrollTo(0, ascrollpoint);
    } else if (starttime === 0) {
      // If no A marker and starting from beginning
      window.scrollTo(0, 0);
    }
  });

  song.addEventListener("pause", function () {
    clearInterval(interval);
  });

  song.addEventListener("ended", function () {
    // When the song ends, automatically navigate to the next song.
    goToNextSong();
  });

  // If the song is already playing due to autoplay when this script runs
  if (!song.paused) {
    // Trigger the play handler's logic manually if needed,
    // or ensure the 'play' event fires correctly for autoplay.
    // Modern browsers should fire 'play' for autoplay.
    // If intervals aren't starting for autoplay, you might need:
    // if (!interval) interval = setInterval(loop, oneSec);
    // if (!scroll) scroll = setInterval(scrollLyrics, scrollTime);
  }

  document.addEventListener("keydown", function (e) {
    const currentAudioElementInDOM =
      document.getElementById("song_audio_player");

    if (!song) return; // Ensure song element is available

    const targetTagName = e.target.tagName.toLowerCase();
    // Allow spacebar to work as expected in input fields
    if (
      (targetTagName === "input" || targetTagName === "textarea") &&
      e.code === KEY_SPACE
    ) {
      return;
    }

    // For other shortcuts, you might also want to disable them if an input/textarea has focus,
    // depending on desired behavior. For now, only spacebar is special-cased.

    switch (e.code) {
      case KEY_SPACE:
        e.preventDefault();
        if (song.paused) {
          // If A marker is set, and we are not already past it, jump to A.
          // Or, always jump to A if set. The original logic was:
          // if (starttime != 0) { song.currentTime = starttime; }
          if (starttime !== 0) {
            song.currentTime = starttime;
          } else if (song.currentTime === song.duration && starttime === 0) {
            // If song ended and no A/B loop, restart from actual beginning
            song.currentTime = 0;
          }
          // Otherwise, it will resume from current position.
          song.play();
        } else {
          song.pause();
        }
        break;

      case KEY_R: // return2start (R key)
        e.preventDefault();
        if (starttime !== 0) {
          song.currentTime = starttime;
          if (ascrollpoint !== 0) window.scrollTo(0, ascrollpoint);
        } else {
          song.currentTime = 0;
          window.scrollTo(0, 0);
        }
        break;

      case KEY_ARROW_LEFT:
        e.preventDefault();
        song.currentTime -= howmanysecs;
        if (song.paused) song.play(); 
        break;

      case KEY_ARROW_RIGHT:
        e.preventDefault();
        song.currentTime += howmanysecs;
        if (song.paused) song.play(); 
        break;

      case KEY_A: // seta (A key)
        e.preventDefault();
        // Reset endtime if setting a new A marker
        if (endtime !== song.duration) {
          endtime = song.duration; // Implicitly clear B marker
          const bElement = document.getElementById("b");
          if (bElement)
            bElement.innerHTML = '<span class="not-set">not set</span>';
        }
        starttime = song.currentTime;
        const secsA = Math.floor(starttime % 60);
        const formattedSecsA = secsA < 10 ? "0" + secsA : secsA;
        const aElement = document.getElementById("a");
        if (aElement)
          aElement.innerHTML =
            Math.floor(starttime / 60) + ":" + formattedSecsA;
        ascrollpoint = window.pageYOffset;
        break;

      case KEY_B: // setb (B key)
        e.preventDefault();
        if (song.currentTime > starttime) {
          endtime = song.currentTime;
          // song.currentTime = starttime; // Original jumped back to A, kept this behavior
          const secsB = Math.floor(endtime % 60);
          const formattedSecsB = secsB < 10 ? "0" + secsB : secsB;
          const bElement = document.getElementById("b");
          if (bElement)
            bElement.innerHTML =
              Math.floor(endtime / 60) + ":" + formattedSecsB;
          bscrollpoint = window.pageYOffset; // bscrollpoint is set but not used by loop/scroll
        }
        break;

      case KEY_C: // cleara (C key) - clears both A and B
        e.preventDefault();
        starttime = 0;
        endtime = song.duration; // Ensure this uses the actual duration
        ascrollpoint = 0;
        bscrollpoint = 0;
        const elA = document.getElementById("a");
        const elB = document.getElementById("b");
        if (elA) elA.innerHTML = '<span class="not-set">not set</span>';
        if (elB) elB.innerHTML = '<span class="not-set">not set</span>';
        // If paused and at end, and then cleared, pressing play should start from 0
        if (song.paused && song.currentTime === endtime) {
          // This state is now handled by spacebar logic
        }
        break;

      case KEY_D: // download (D key)
        e.preventDefault();
        const downloadLink = document.getElementById("download-link");
        if (downloadLink) {
          downloadLink.click();
        }
        break;
    }
  });
});

// Wait for the HTML document to be fully loaded before running the script
document.addEventListener("DOMContentLoaded", function () {
  const artistDropdown = document.getElementById("artist-select");

  if (artistDropdown) {
    artistDropdown.addEventListener("change", function () {
      const selectedArtist = this.value;
      if (selectedArtist) {
        const targetUrl = `displayartist.php?artist=${encodeURIComponent(
          selectedArtist
        )}`;
        window.location.href = targetUrl;
      }
    });
  }

  const setDropdown = document.getElementById("set-select");
  if (setDropdown) {
    setDropdown.addEventListener("change", function () {
      const selectedSet = this.value;
      if (selectedSet) {
        const targetUrl = `displayset.php?set=${encodeURIComponent(
          selectedSet
        )}`;
        window.location.href = targetUrl;
      }
    });
  }

  // *** Autocomplete Logic for Songs ***
  // *** Autocomplete Logic for Songs ***
  const searchInputs = document.querySelectorAll(".song-search-input, #song-search");

  // Helper to init a pair
  function initAutocomplete(songSearchInput, songResultsContainer) {
      if (!songSearchInput || !songResultsContainer) return;

      let currentFocus = -1;

      if (typeof allSongs === 'undefined') {
          console.error("allSongs variable is undefined! Check index.php generation.");
          return;
      }
      
      // Filter function
      function filterSongs(query) {
        if (!query) {
          closeAllLists();
          return;
        }
        
        if (!Array.isArray(allSongs)) {
            return;
        }
  
        const lowerQuery = query.toLowerCase();
        // Filter songs that contain the query string (case-insensitive) in title OR lyrics
        const matches = allSongs.filter(song => 
          song.title.toLowerCase().includes(lowerQuery) || 
          (song.lyrics && song.lyrics.toLowerCase().includes(lowerQuery))
        );
  
        displayResults(matches, lowerQuery);
      }
  
      // Display results function
      function displayResults(matches, query) {
        // Clear previous results
        songResultsContainer.innerHTML = "";
        currentFocus = -1;
  
        if (matches.length === 0) {
          songResultsContainer.classList.remove("show");
          return;
        }
        
        const safeQuery = query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const regex = new RegExp(`(${safeQuery})`, "gi");
  
        matches.forEach(song => {
          const item = document.createElement("div");
          item.className = "autocomplete-item";
          
          let displayHTML = "";
          
          // Highlight title match
          if (song.title.toLowerCase().includes(query)) {
             displayHTML = song.title.replace(regex, "<strong>$1</strong>");
          } else {
             displayHTML = song.title; // No title match, just display title
          }
           
          // Indicate lyric match if title doesn't match
          if (!song.title.toLowerCase().includes(query) && song.lyrics && song.lyrics.toLowerCase().includes(query)) {
              displayHTML += " <small style='opacity:0.7'><i>(Lyrics match)</i></small>";
          }
  
          item.innerHTML = displayHTML;
          item.dataset.file = song.file; // Store filename
  
          item.addEventListener("click", function () {
            // Navigate to the song
            window.location.href = `webchord.cgi?chordpro=${encodeURIComponent(song.file)}`;
          });
  
          songResultsContainer.appendChild(item);
        });
  
        songResultsContainer.classList.add("show");
      }
  
      // Event Listeners for Input
      songSearchInput.addEventListener("input", function () {
        filterSongs(this.value);
      });
      
      songSearchInput.addEventListener("focus", function() {
        // Optional: Trigger search on focus?
      });
  
      songSearchInput.addEventListener("keydown", function (e) {
        let x = songResultsContainer.getElementsByTagName("div");
        if (e.keyCode === 40) { // Arrow Down
          currentFocus++;
          addActive(x);
        } else if (e.keyCode === 38) { // Arrow Up
          currentFocus--;
          addActive(x);
        } else if (e.keyCode === 13) { // Enter
          e.preventDefault();
          if (currentFocus > -1) {
            if (x) x[currentFocus].click();
          } else if (x && x.length > 0) {
               x[0].click();
          }
        }
      });

      function addActive(x) {
        if (!x) return false;
        removeActive(x);
        if (currentFocus >= x.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (x.length - 1);
        x[currentFocus].classList.add("active");
        x[currentFocus].scrollIntoView({block: "nearest"});
      }
  
      function removeActive(x) {
        for (let i = 0; i < x.length; i++) {
          x[i].classList.remove("active");
        }
      }
  }

  // Iterate all found inputs and init
  searchInputs.forEach(input => {
      // Find sibling container
      // index.php structure: input + div.autocomplete-results
      // site-functions.php structure: input + div.autocomplete-results
      // Using parentNode is safest if they are siblings
      const container = input.parentNode.querySelector(".autocomplete-results");
      if (container) {
          initAutocomplete(input, container);
      }
  });

  // Global close listener
  function closeAllLists(elmnt) {
    const items = document.getElementsByClassName("autocomplete-results");
    for (let i = 0; i < items.length; i++) {
        // If clicked element is NOT the input associated with this list...
        // But we don't know which input is associated easily here without map.
        // Simplified: If clicked element is not an input and not the list itself
        if (elmnt.classList && (elmnt.classList.contains("song-search-input") || elmnt.id === "song-search" || elmnt.id === "footer-song-search")) {
             // Let that input handle its own list logic (or keep it open)
             // But we should close OTHERS?
             // For now, close all except if we are typing in one.
             // Actually, input event opens it.
        } else {
           items[i].classList.remove("show");
        }
    }
  }

  document.addEventListener("click", function (e) {
    // If click is inside an input, initAutocomplete handles it (or rather input event).
    // If click is outside, close all.
    // If click is inside a list item, that item handles it.
    
    // Check if target is an input
    const isInput = e.target.classList.contains("song-search-input") || e.target.id === "song-search" || e.target.id === "footer-song-search";
    if (!isInput) {
        closeAllLists(e.target);
    }
  });

  // Add event listener for the "Next Song" button
  const nextSongButton = document.getElementById("next-song-btn");
  if (nextSongButton) {
    nextSongButton.addEventListener("click", function () {
      goToNextSong();
    });

    // Conditionally show the button only if there is a next song.
    if (
      typeof setlistSongs !== "undefined" &&
      typeof currentSongIndex !== "undefined" &&
      currentSongIndex + 1 < setlistSongs.length
    ) {
      // Make sure the next song in the array is not null
      if (setlistSongs[currentSongIndex + 1]) {
        nextSongButton.style.display = "block"; // Or "inline-block"
      }
    }
  }
});

// Custom Logic for Two-Column Scrolling
(function () {
  // We need to override or augment the scrolling logic because we are now
  // scrolling an inner div (#lyrics-scroller) via margin-top to achieve the
  // "flow up" effect in a fixed-height container, rather than scrolling the window.

  // State for our custom scroller
  let currentScrollY = 0; // Represents pixels scrolled DOWN (negative margin)

  function getScroller() {
    return document.getElementById("lyrics-scroller");
  }

  function getContainer() {
    return document.getElementById("song");
  }

  // Re-implement Fit Content to work with the new structure
  window.fitSongContent = function() {
    const scroller = getScroller();
    const container = getContainer();
    if (!scroller || !container) return;

    // Reset styles
    scroller.style.fontSize = "";
    
    // We only resize if we are in desktop/fixed-height mode
    if (window.getComputedStyle(container).height === 'auto') return;
    
    // Use requestAnimationFrame to ensure layout is complete
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        performFit();
      });
    });
    
    function performFit() {
      let fontSize = parseFloat(window.getComputedStyle(scroller).fontSize);
      const minFontSize = 8; // Lowered from 10px to fit very long songs in embedded context
      const maxIterations = 50;
      let iterations = 0;
      
      // Check if content actually overflows
      function hasOverflow() {
        // Force reflow
        const _ = scroller.offsetHeight;
        
        // Vertical overflow: content taller than its container
        const verticalOverflow = scroller.scrollHeight > scroller.clientHeight + 2;
        
        // Horizontal overflow: content wider than its container (indicates >2 columns)
        const horizontalOverflow = scroller.scrollWidth > scroller.clientWidth + 2;
        
        return verticalOverflow || horizontalOverflow;
      }
      
      // Iteratively shrink only if there's actual overflow
      while (hasOverflow() && fontSize > minFontSize && iterations < maxIterations) {
        fontSize -= 0.5;
        scroller.style.fontSize = `${fontSize}px`;
        iterations++;
      }
    }
  };

  // Split lyrics into two columns for vertical scrolling
  window.splitLyricsIntoColumns = function() {
    const scroller = getScroller();
    if (!scroller) return;
    
    // Check if already split (has .lyrics-column children)
    if (scroller.querySelector('.lyrics-column')) return;
    
    // Get all direct children of the scroller
    const children = Array.from(scroller.children);
    if (children.length === 0) return;
    
    // Calculate split point (middle)
    const midpoint = Math.ceil(children.length / 2);
    
    // Create two column containers
    const leftColumn = document.createElement('div');
    leftColumn.className = 'lyrics-column lyrics-column-left';
    
    const rightColumn = document.createElement('div');
    rightColumn.className = 'lyrics-column lyrics-column-right';
    
    // Move first half to left column
    for (let i = 0; i < midpoint; i++) {
      leftColumn.appendChild(children[i]);
    }
    
    // Move second half to right column
    for (let i = midpoint; i < children.length; i++) {
      rightColumn.appendChild(children[i]);
    }
    
    // Remove leading BR tags from both columns to ensure alignment
    function removeLeadingBRs(column) {
      while (column.firstChild && column.firstChild.tagName === 'BR') {
        column.removeChild(column.firstChild);
      }
    }
    
    removeLeadingBRs(leftColumn);
    removeLeadingBRs(rightColumn);
    
    // Clear scroller and add columns
    scroller.innerHTML = '';
    scroller.appendChild(leftColumn);
    scroller.appendChild(rightColumn);
  };

  // Call split function first, then fit content
  window.addEventListener("DOMContentLoaded", () => {
    window.splitLyricsIntoColumns();
    window.fitSongContent();

    // Check if we should autoplay (when accessed via direct link)
    const urlParams = new URLSearchParams(window.location.search);
    const inIframe = window.self !== window.top;
    
    // Autoplay if we're in an iframe and have a chordpro parameter
    if (inIframe && urlParams.has('chordpro')) {
      const audio = document.querySelector('audio');
      if (audio) {
        // Robust Autoplay Logic
        const attemptPlay = () => {
          const playPromise = audio.play();
          if (playPromise !== undefined) {
            playPromise.then(_ => {
              // Autoplay started!
            }).catch(error => {
              console.log('Autoplay prevented by browser:', error);
              
              // Fallback: Play on first interaction (invisible to user)
              const playOnInteraction = () => {
                // Ensure we unmute before playing!
                audio.muted = false; 
                audio.play().then(() => {
                  // Success - remove listeners
                  ['click', 'keydown', 'touchstart'].forEach(e => 
                    document.removeEventListener(e, playOnInteraction));
                }).catch(e => console.log('Interaction play failed:', e));
              };

              // Listen for any user interaction
              ['click', 'keydown', 'touchstart'].forEach(e => 
                document.addEventListener(e, playOnInteraction));
                
              // Also try muted as a backup, so it at least starts visually
              audio.muted = true;
              audio.play().then(() => {
                  // If muted autoplay works, we still want the interaction to UNMUTE it
                  // The listeners above will handle that (calling play() on unmuted audio)
                  showToast("Playing muted. Click to unmute.");
              }).catch(e => console.log('Muted autoplay also prevented'));
            });
          }
        };

        if (audio.readyState >= 3) {
            attemptPlay();
        } else {
            audio.addEventListener('canplay', attemptPlay, { once: true });
        }
      }
    }
  
    // Update URL in Parent Window
    if (inIframe) {
      try {
        const songPath = window.location.pathname + window.location.search;
        // We expect songPath to look like /songbook/webchord.cgi?chordpro=...
        // and we want parent URL to look like /songs/webchord.cgi?chordpro=...
        // If path doesn't contain /songbook/, fallback to simple replace or just append
        let parentPath = songPath.replace('/songbook/', '/songs/');
        
        // Safety check: if replace didn't do anything (maybe path is different), 
        // ensure we start with /songs/ if strictly songbook content
        if (parentPath === songPath && songPath.includes('webchord.cgi')) {
             parentPath = '/songs' + songPath;
        }

        window.top.history.replaceState(null, '', parentPath);
      } catch (e) {
        // Cross-origin, can't update parent URL
        console.log('Could not update parent URL:', e);
      }
    }
  });
  
  let resizeTimeout;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      window.splitLyricsIntoColumns();
      window.fitSongContent();
    }, 100);
  });
})();

// Theme Manager
(function () {
  const getStoredTheme = () => localStorage.getItem('theme');
  const setStoredTheme = theme => localStorage.setItem('theme', theme);

  const getPreferredTheme = () => {
    const storedTheme = getStoredTheme();
    if (storedTheme) {
      return storedTheme;
    }
    return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
  };

  const setTheme = theme => {
    if (theme === 'auto') {
      document.documentElement.removeAttribute('data-theme');
      localStorage.removeItem('theme');
    } else {
      document.documentElement.setAttribute('data-theme', theme);
      setStoredTheme(theme);
    }
  };

  const applyTheme = () => {
    const theme = getPreferredTheme();
    // Start with data-theme set based on preference to avoid flash
    // But if auto, we want to remove data-theme or set it to match system?
    // songbook.css uses @media (prefers-color-scheme: light) for light mode.
    // Default is dark.
    // So if system is light, we need data-theme="light" OR rely on media query.
    // However, if manual override is 'dark', we need data-theme="dark".
    // If manual override is 'light', we need data-theme="light".
    // If auto (no override), we remove data-theme and let CSS handle it.
    
    const stored = getStoredTheme();
    if (stored) {
      document.documentElement.setAttribute('data-theme', stored);
    } else {
      // Auto mode: Remove attribute and let CSS media queries work.
      // EXCEPT: The previous logic forced data-theme="light" if system was light. 
      // This suggests the CSS might rely on data-theme="light" for light mode?
      // Let's check songbook.css.
      // [data-theme="light"] .class { ... }
      // @media (prefers-color-scheme: light) { :root { ... } }
      // So CSS handles variables via media query.
      // But specific overrides like `.chords` color use `[data-theme="light"]`.
      // So we MUST set data-theme="light" if system is light, even in auto mode, 
      // OR ensure the CSS selectors use :root:not([data-theme="dark"])?
      // No, the previous JS did: if (systemLight) setAttribute('data-theme', 'light').
      // So I should preserve that behavior for Auto mode.
      
      if (window.matchMedia('(prefers-color-scheme: light)').matches) {
        document.documentElement.setAttribute('data-theme', 'light');
      } else {
        document.documentElement.removeAttribute('data-theme');
      }
    }
  };

  applyTheme();

  // Listen for system changes
  window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', applyTheme);

  // Expose toggle function
  window.toggleTheme = () => {
    const current = document.documentElement.getAttribute('data-theme') || 
                   (window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark');
    const next = current === 'light' ? 'dark' : 'light';
    setTheme(next);
  };
})();


// Toast Notification Function
function showToast(message) {
  let toast = document.getElementById("toast-notification");
  if (!toast) {
    toast = document.createElement("div");
    toast.id = "toast-notification";
    toast.className = "toast";
    document.body.appendChild(toast);
  }
  toast.innerText = message;
  toast.classList.add("show");
  setTimeout(function(){ toast.classList.remove("show"); }, 3000);
}

// Copy Link Function
window.copyCurrentUrlToClipboard = function() {
  // Check if we're in an iframe
  const inIframe = window.self !== window.top;
  
  let url;
  if (inIframe) {
    // Get the current iframe URL
    const iframeUrl = window.location.href;
    
    // Extract the songbook path (e.g., /songbook/webchord.cgi?chordpro=American+Girl.pro)
    const songbookPath = iframeUrl.replace(/^.*\/songbook\//, '/songbook/');
    
    // Construct the parent URL: /songs/webchord.cgi?chordpro=...
    const parentPath = songbookPath.replace('/songbook/', '/songs/');
    
    // Get the parent's origin
    try {
      url = window.top.location.origin + parentPath;
    } catch (e) {
      // Cross-origin, use current origin
      url = window.location.origin + parentPath;
    }
  } else {
    // Not in iframe, use current URL
    url = window.location.href;
  }
  
  // Copy to clipboard
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => {
      showToast("Link copied to clipboard!");
    }).catch(err => {
      console.error('Failed to copy: ', err);
      prompt("Copy this link:", url);
    });
  } else {
    // Fallback for older browsers
    const textArea = document.createElement("textarea");
    textArea.value = url;
    document.body.appendChild(textArea);
    textArea.select();
    try {
      document.execCommand('copy');
      showToast("Link copied to clipboard!");
    } catch (err) {
      prompt("Copy this link:", url);
    }
    document.body.removeChild(textArea);
  }
};

