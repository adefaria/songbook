/**
 * Set List Editor JavaScript (v4.0)
 * Handles song search autocomplete, custom non-database songs,
 * top insertion, drag-and-drop reordering (Mouse & Touch),
 * song removal, song preview links, live total duration calculation,
 * and unsaved changes (dirty state) detection.
 */

document.addEventListener("DOMContentLoaded", function () {
  const songListContainer = document.getElementById("setlist-items");
  const searchInput = document.getElementById("editor-song-search");
  const resultsContainer = document.getElementById("editor-autocomplete-results");
  const form = document.getElementById("setlist-editor-form");
  const nameInput = document.getElementById("set-title-input");

  if (!songListContainer || !searchInput || !resultsContainer) {
    return; // Not on the setlist editor page
  }

  let currentFocus = -1;
  let isDirty = false;

  function markDirty() {
    isDirty = true;
  }

  if (nameInput) {
    nameInput.addEventListener("input", markDirty);
  }

  // --- Helper Functions for Duration Calculation ---
  function parseDurationToSeconds(durationStr) {
    if (!durationStr) return 0;
    const parts = durationStr.trim().split(":");
    if (parts.length === 2) {
      const min = parseInt(parts[0], 10) || 0;
      const sec = parseInt(parts[1], 10) || 0;
      return min * 60 + sec;
    } else if (parts.length === 3) {
      const hr = parseInt(parts[0], 10) || 0;
      const min = parseInt(parts[1], 10) || 0;
      const sec = parseInt(parts[2], 10) || 0;
      return hr * 3600 + min * 60 + sec;
    }
    return 0;
  }

  function formatSecondsToDuration(totalSec) {
    if (totalSec <= 0) return "0:00";
    const hours = Math.floor(totalSec / 3600);
    const mins = Math.floor((totalSec % 3600) / 60);
    const secs = totalSec % 60;
    const formattedSecs = secs < 10 ? "0" + secs : secs;

    if (hours > 0) {
      const formattedMins = mins < 10 ? "0" + mins : mins;
      return `${hours}h ${formattedMins}m ${formattedSecs}s`;
    } else {
      return `${mins}:${formattedSecs}`;
    }
  }

  // --- Autocomplete & Search Logic ---
  function filterSongs(query) {
    const trimmed = query.trim();
    if (!trimmed) {
      resultsContainer.classList.remove("show");
      resultsContainer.innerHTML = "";
      return;
    }

    let matches = [];
    if (typeof allSongs !== "undefined" && Array.isArray(allSongs)) {
      const lowerQuery = trimmed.toLowerCase();
      matches = allSongs.filter(
        (song) =>
          song.title.toLowerCase().includes(lowerQuery) ||
          (song.lyrics && song.lyrics.toLowerCase().includes(lowerQuery))
      );
    }

    displayResults(matches, trimmed);
  }

  function displayResults(matches, query) {
    resultsContainer.innerHTML = "";
    currentFocus = -1;

    const safeQuery = query.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(${safeQuery})`, "gi");

    // Existing song matches
    matches.forEach((song) => {
      const item = document.createElement("div");
      item.className = "autocomplete-item";

      let displayHTML = "";
      if (song.title.toLowerCase().includes(query.toLowerCase())) {
        displayHTML = song.title.replace(regex, "<strong>$1</strong>");
      } else {
        displayHTML = song.title;
      }

      if (
        !song.title.toLowerCase().includes(query.toLowerCase()) &&
        song.lyrics &&
        song.lyrics.toLowerCase().includes(query.toLowerCase())
      ) {
        displayHTML += " <small style='opacity:0.7'><i>(Lyrics match)</i></small>";
      }

      if (song.duration) {
        displayHTML += ` <span style='float: right; opacity: 0.8; font-size: 0.85em;'>⏱️ ${escapeHtml(song.duration)}</span>`;
      }

      item.innerHTML = displayHTML;

      item.addEventListener("click", function () {
        addSongToSetlist(song.title, song.duration || "");
        searchInput.value = "";
        resultsContainer.classList.remove("show");
        resultsContainer.innerHTML = "";
        searchInput.focus();
      });

      resultsContainer.appendChild(item);
    });

    // Option to add Custom / Non-database song
    const customItem = document.createElement("div");
    customItem.className = "autocomplete-item add-custom-item";
    customItem.innerHTML = `➕ Add <strong>"${escapeHtml(query)}"</strong> <i>(Custom Song)</i>`;
    customItem.addEventListener("click", function () {
      addSongToSetlist(query, "");
      searchInput.value = "";
      resultsContainer.classList.remove("show");
      resultsContainer.innerHTML = "";
      searchInput.focus();
    });

    resultsContainer.appendChild(customItem);
    resultsContainer.classList.add("show");
  }

  searchInput.addEventListener("input", function () {
    filterSongs(this.value);
  });

  searchInput.addEventListener("keydown", function (e) {
    let x = resultsContainer.getElementsByTagName("div");
    if (e.keyCode === 40) {
      // Down arrow
      currentFocus++;
      addActive(x);
    } else if (e.keyCode === 38) {
      // Up arrow
      currentFocus--;
      addActive(x);
    } else if (e.keyCode === 13) {
      // Enter
      e.preventDefault();
      if (currentFocus > -1 && x[currentFocus]) {
        x[currentFocus].click();
      } else if (searchInput.value.trim() !== "") {
        // Add typed text as custom song
        addSongToSetlist(searchInput.value.trim(), "");
        searchInput.value = "";
        resultsContainer.classList.remove("show");
        resultsContainer.innerHTML = "";
      }
    }
  });

  function addActive(x) {
    if (!x || x.length === 0) return false;
    removeActive(x);
    if (currentFocus >= x.length) currentFocus = 0;
    if (currentFocus < 0) currentFocus = x.length - 1;
    x[currentFocus].classList.add("active");
    x[currentFocus].scrollIntoView({ block: "nearest" });
  }

  function removeActive(x) {
    for (let i = 0; i < x.length; i++) {
      x[i].classList.remove("active");
    }
  }

  document.addEventListener("click", function (e) {
    if (e.target !== searchInput) {
      resultsContainer.classList.remove("show");
    }
  });

  // --- Add Song Item to Top of List ---
  window.addSongToSetlist = function (songTitle, duration) {
    if (!songTitle || !songTitle.trim()) return;

    // Lookup duration from allSongs if not passed
    if (!duration && typeof allSongs !== "undefined" && Array.isArray(allSongs)) {
      const match = allSongs.find(
        (s) => s.title.toLowerCase() === songTitle.trim().toLowerCase()
      );
      if (match && match.duration) {
        duration = match.duration;
      }
    }

    const cleanTitle = songTitle.trim();
    const li = document.createElement("li");
    li.className = "setlist-item";
    li.draggable = true;
    li.dataset.duration = duration || "";

    const songUrl = `webchord.cgi?chordpro=${encodeURIComponent(cleanTitle + ".pro")}`;
    const durationHTML = duration ? `<span class="item-duration" title="Song duration">⏱️ ${escapeHtml(duration)}</span>` : `<span class="item-duration empty">--:--</span>`;

    li.innerHTML = `
      <span class="drag-handle" title="Drag to reorder">&#8942;&#8942;</span>
      <span class="item-number"></span>
      <a href="${songUrl}" class="item-title-link" title="Open song page">${escapeHtml(cleanTitle)}</a>
      <input type="hidden" name="songs[]" value="${escapeHtml(cleanTitle)}">
      ${durationHTML}
      <button type="button" class="remove-song-btn" title="Remove song">&times;</button>
    `;

    // Requirement 2: Place newly added songs at the TOP of the list
    if (songListContainer.firstChild) {
      songListContainer.insertBefore(li, songListContainer.firstChild);
    } else {
      songListContainer.appendChild(li);
    }

    attachDragListeners(li);
    attachTouchListeners(li);
    updateLineNumbersAndDuration();
    markDirty();
  };

  function escapeHtml(text) {
    const div = document.createElement("div");
    div.innerText = text;
    return div.innerHTML;
  }

  // --- Line Numbers & Total Duration Calculation ---
  function updateLineNumbersAndDuration() {
    const items = songListContainer.querySelectorAll(".setlist-item");
    let totalSeconds = 0;

    items.forEach((item, index) => {
      const numSpan = item.querySelector(".item-number");
      if (numSpan) {
        numSpan.textContent = `${index + 1}.`;
      }
      const durAttr = item.dataset.duration || "";
      totalSeconds += parseDurationToSeconds(durAttr);
    });

    const countBadge = document.getElementById("song-count-badge");
    if (countBadge) {
      const totalDurText = formatSecondsToDuration(totalSeconds);
      countBadge.textContent = `${items.length} song${items.length === 1 ? "" : "s"} | Total: ${totalDurText}`;
    }

    const emptyNotice = document.getElementById("empty-setlist-notice");
    if (emptyNotice) {
      emptyNotice.style.display = items.length === 0 ? "block" : "none";
    }
  }

  // --- Remove Song Listener ---
  songListContainer.addEventListener("click", function (e) {
    if (e.target && e.target.classList.contains("remove-song-btn")) {
      const item = e.target.closest(".setlist-item");
      if (item) {
        item.style.opacity = "0";
        item.style.transform = "translateX(20px)";
        item.style.transition = "all 0.2s ease";
        setTimeout(() => {
          item.remove();
          updateLineNumbersAndDuration();
          markDirty();
        }, 200);
      }
    }
  });

  // --- Mouse Drag & Drop ---
  let draggingItem = null;

  function attachDragListeners(item) {
    item.addEventListener("dragstart", function (e) {
      draggingItem = item;
      item.classList.add("dragging");
      e.dataTransfer.effectAllowed = "move";
      e.dataTransfer.setData("text/plain", "");
    });

    item.addEventListener("dragend", function () {
      if (draggingItem) {
        draggingItem.classList.remove("dragging");
        draggingItem = null;
      }
      updateLineNumbersAndDuration();
      markDirty();
    });
  }

  songListContainer.addEventListener("dragover", function (e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = "move";

    if (!draggingItem) return;

    const afterElement = getDragAfterElement(songListContainer, e.clientY);
    if (afterElement == null) {
      songListContainer.appendChild(draggingItem);
    } else {
      songListContainer.insertBefore(draggingItem, afterElement);
    }
  });

  function getDragAfterElement(container, y) {
    const draggableElements = [
      ...container.querySelectorAll(".setlist-item:not(.dragging)"),
    ];

    return draggableElements.reduce(
      (closest, child) => {
        const box = child.getBoundingClientRect();
        const offset = y - box.top - box.height / 2;
        if (offset < 0 && offset > closest.offset) {
          return { offset: offset, element: child };
        } else {
          return closest;
        }
      },
      { offset: Number.NEGATIVE_INFINITY }
    ).element;
  }

  // --- Touch Drag & Drop (Tablet Support) ---
  function attachTouchListeners(item) {
    const handle = item.querySelector(".drag-handle");
    if (!handle) return;

    let activeItem = null;
    let placeholder = null;

    handle.addEventListener("touchstart", function (e) {
      if (e.touches.length !== 1) return;

      activeItem = item;
      activeItem.classList.add("touch-dragging");

      placeholder = document.createElement("li");
      placeholder.className = "setlist-item placeholder-item";
      placeholder.style.height = `${activeItem.offsetHeight}px`;
      songListContainer.insertBefore(placeholder, activeItem);

      e.preventDefault();
    }, { passive: false });

    handle.addEventListener("touchmove", function (e) {
      if (!activeItem || e.touches.length !== 1) return;

      const currentY = e.touches[0].clientY;
      const afterElement = getDragAfterElement(songListContainer, currentY);

      if (afterElement == null) {
        songListContainer.appendChild(placeholder);
      } else {
        songListContainer.insertBefore(placeholder, afterElement);
      }

      e.preventDefault();
    }, { passive: false });

    const endTouch = function () {
      if (!activeItem) return;

      if (placeholder && placeholder.parentNode) {
        songListContainer.insertBefore(activeItem, placeholder);
        placeholder.remove();
      }

      activeItem.classList.remove("touch-dragging");
      activeItem = null;
      placeholder = null;

      updateLineNumbersAndDuration();
      markDirty();
    };

    handle.addEventListener("touchend", endTouch);
    handle.addEventListener("touchcancel", endTouch);
  }

  // Initialize existing items in the list
  const initialItems = songListContainer.querySelectorAll(".setlist-item");
  initialItems.forEach((item) => {
    attachDragListeners(item);
    attachTouchListeners(item);
  });
  updateLineNumbersAndDuration();

  // --- Form Validation & Submit (Clears Dirty Flag) ---
  if (form) {
    form.addEventListener("submit", function (e) {
      if (!nameInput || !nameInput.value.trim()) {
        e.preventDefault();
        alert("Please enter a Set List title.");
        if (nameInput) nameInput.focus();
        return;
      }
      isDirty = false; // Normal form save - allow navigation
    });
  }

  // --- Unsaved Changes Prevention (beforeunload & Navigation Click) ---
  window.addEventListener("beforeunload", function (e) {
    if (isDirty) {
      e.preventDefault();
      e.returnValue = "You have unsaved changes in your set list.";
      return e.returnValue;
    }
  });

  document.addEventListener("click", function (e) {
    const clickable = e.target.closest("a, button.btn-secondary, button.btn-danger");
    if (!clickable) return;

    // Ignore song title preview links or song removal buttons or autocomplete items
    if (
      clickable.classList.contains("remove-song-btn") ||
      clickable.classList.contains("autocomplete-item") ||
      clickable.classList.contains("item-title-link") ||
      clickable.type === "submit"
    ) {
      return;
    }

    if (isDirty) {
      const confirmLeave = confirm(
        "You have unsaved changes in your set list. Are you sure you want to leave without saving?"
      );
      if (!confirmLeave) {
        e.preventDefault();
        e.stopPropagation();
      } else {
        isDirty = false; // User confirmed leaving
      }
    }
  }, true);
});
