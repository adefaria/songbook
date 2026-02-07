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
const KEY_R = "KeyR"; // For 'return2start'=

const howmanysecs = 10;
const scrollby = 1;
const oneSec = 1000;
const scrollTime = 400;

function loop() {
  // If endtime is not set then we can't loop
  if (endtime == 0) return;

  // if we're past the endtime then it's time to start back at the A marker
  if (song.currentTime > endtime) {
    song.currentTime = starttime;
    if (ascrollpoint != 0) window.scrollTo(0, ascrollpoint);
  } // if
} // loop

function scrollLyrics() {
  window.scrollBy(0, scrollby);
} // scrollLyrics

document.addEventListener("DOMContentLoaded", function () {
  song = document.getElementById("song_audio_player");
  body = document.body; // Use document.body directly

  if (!song) {
    console.error(
      "Audio player #song_audio_player not found. Audio controls will not work."
    );
    return;
  }

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
    clearInterval(scroll);
    interval = setInterval(loop, oneSec);
    scroll = setInterval(scrollLyrics, scrollTime);

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
    clearInterval(scroll);
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
        window.scrollBy(0, 50); // Original scrolled down, kept for consistency
        if (song.paused) song.play(); // Play if paused, as per original
        break;

      case KEY_ARROW_RIGHT:
        e.preventDefault();
        song.currentTime += howmanysecs;
        window.scrollBy(0, -50); // Corrected typo and kept original scroll up
        if (song.paused) song.play(); // Play if paused, as per original
        break;

      case KEY_A: // seta (A key)
        e.preventDefault();
        // Reset endtime if setting a new A marker
        if (endtime !== song.duration) {
          endtime = song.duration; // Implicitly clear B marker
          const bElement = document.getElementById("b");
          if (bElement)
            bElement.innerHTML = "<font color=#666><i>not set</i></font>";
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
        if (elA) elA.innerHTML = "<font color=#666><i>not set</i></font>";
        if (elB) elB.innerHTML = "<font color=#666><i>not set</i></font>";
        // If paused and at end, and then cleared, pressing play should start from 0
        if (song.paused && song.currentTime === endtime) {
          // This state is now handled by spacebar logic
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

  const songDropdown = document.getElementById("song-select");
  if (songDropdown) {
    songDropdown.addEventListener("change", function () {
      const selectedSong = this.value;
      if (selectedSong) {
        // *** Adjust URL structure as needed ***
        const targetUrl = `webchord.cgi?chordpro=${encodeURIComponent(
          selectedSong
        )}`;
        window.location.href = targetUrl;
      }
    });
  }

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

(function () {
  // IIFE to avoid polluting global scope and run immediately

  function fitSongContent() {
    const songElement = document.getElementById("song");
    if (!songElement) {
      return;
    }

    // Reset to CSS-defined font size first.
    // This allows getComputedStyle to get the base font size for calculations
    // and handles cases where the window is enlarged.
    songElement.style.fontSize = "";

    // If clientHeight is 0 after reset, the container is likely not yet rendered correctly,
    // is hidden (display:none), or has no actual height defined by CSS.
    const containerHeight = songElement.clientHeight;
    if (containerHeight <= 0) {
      // You could implement a retry mechanism if this is a common timing issue:
      // setTimeout(fitSongContent, 100);
      return;
    }

    const originalComputedFontSize = parseFloat(
      window.getComputedStyle(songElement).fontSize
    );

    // If no font size is computable (e.g. display:none, or no font-size set anywhere up the chain), abort.
    if (isNaN(originalComputedFontSize) || originalComputedFontSize <= 0) {
      console.warn(
        "Debug: Cannot determine original font size for #song element."
      );
      return;
    }

    let currentFontSize = originalComputedFontSize;
    const MIN_FONT_SIZE = 8; // Minimum readable font size in pixels
    const FONT_STEP = 0.5; // How much to decrease font size by each step (in pixels)

    // Set initial font size for the loop to the original/max
    songElement.style.fontSize = currentFontSize + "px";

    let iterations = 0;
    const MAX_ITERATIONS = 50; // Safety break to prevent infinite loops

    while (
      songElement.scrollHeight > containerHeight &&
      currentFontSize > MIN_FONT_SIZE &&
      iterations < MAX_ITERATIONS
    ) {
      currentFontSize -= FONT_STEP;
      songElement.style.fontSize = currentFontSize + "px";
      iterations++;
      console.log(
        `Debug: Iteration ${iterations}, Font: ${currentFontSize}px, ScrollH: ${songElement.scrollHeight}, ClientH: ${containerHeight}`
      );
    }
  }

  // Run when the initial HTML document has been completely loaded and parsed
  window.addEventListener("DOMContentLoaded", fitSongContent);

  // Run on window resize (with a debounce to avoid excessive calls)
  let resizeTimeout;
  window.addEventListener("resize", () => {
    clearTimeout(resizeTimeout);
    // fitSongContent itself will reset the font size to the CSS default before recalculating
    resizeTimeout = setTimeout(fitSongContent, 250); // Adjust debounce delay as needed (250ms)
  });
})();

// Theme Manager
(function () {
  function applyTheme() {
    // Check for system preference
    const systemPrefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;

    // Apply theme based on system preference (Default is dark via CSS)
    if (systemPrefersLight) {
      document.documentElement.setAttribute("data-theme", "light");
    } else {
      document.documentElement.removeAttribute("data-theme");
    }
  }

  // Apply on execution (safe if script is in head as documentElement exists)
  applyTheme();

  // Listen for system changes
  if (window.matchMedia) {
    window.matchMedia('(prefers-color-scheme: light)').addEventListener('change', applyTheme);
  }
})();
