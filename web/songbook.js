// Javascript functions for controlling audio
var starttime = 0;
var endtime = 0;
var ascrollpoint = 0;
var bscrollpoint = 0;

var song, interval, scroll, body;

// Keycodes
const spacebar = 32;
const leftarrow = 37;
const rightarrow = 39;
const seta = 65;
const setb = 66;
const cleara = 67;
const return2start = 82;

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

function scrollLyrics(x, y) {
  window.scrollBy(0, scrollby);
} // scrollLyrics

window.onload = function () {
  song = document.getElementById("song");

  starttime = song.currentTime;
  endtime = song.duration;
  body = document.getElementsByTagName("body")[0];

  if (!song.paused) {
    // Set up loop
    interval = setInterval(loop, oneSec);
    scroll = setInterval(scrollLyrics, scrollTime);
  } // if

  body.onkeydown = function (e) {
    var ev = e || event;
    if (ev.keyCode == spacebar) {
      if (song.paused) {
        playing = false;
      } else {
        playing = true;
      } // if

      if (playing) {
        // Stop loop
        clearInterval(interval);
        clearInterval(scroll);

        song.pause();
        playing = false;
      } else {
        if (ascrollpoint != 0) {
          window.scrollTo(0, ascrollpoint);
        } else {
          window.scrollTo(0, 0);
        } // if

        if (starttime != 0) {
          song.currentTime = starttime;
        } // if

        // Set up loop
        interval = setInterval(loop, oneSec);
        scroll = setInterval(scrollLyrics, scrollTime);

        if (ascrollpoint != 0) window.scrollTo(0, ascrollpoint);

        song.play();

        playing = true;
      } // if

      e.preventDefault();

      return;
    } else if (ev.keyCode == return2start) {
      if (starttime != 0) {
        song.currentTime = starttime;
      } else {
        song.currentTime = 0;
        body.scrollTo(0, 0);
      } // if

      return;
    } else if (ev.keyCode == leftarrow) {
      song.currentTime -= howmanysecs;
      body.scrollBy(0, 50);
      song.play();

      return;
    } else if (ev.keyCode == rightarrow) {
      song.currentTime += howmanysecs;
      bosy.scrollBy(0, -50);
      song.play();

      return;
    } else if (ev.keyCode == seta) {
      // Reset endtime if setting a new A marker
      if (endtime != song.duration) endtime = song.duration;

      starttime = song.currentTime;

      // Translate seconds to timecode
      secs = Math.floor(starttime % 60);
      if (secs < 10) secs = "0" + secs;

      document.getElementById("a").innerHTML =
        Math.floor(starttime / 60) + ":" + secs;

      ascrollpoint = window.pageYOffset;

      return;
    } else if (ev.keyCode == setb) {
      if (song.currentTime > starttime) {
        endtime = song.currentTime;
        song.currentTime = starttime;

        // Translate seconds to timecode
        secs = Math.floor(endtime % 60);
        if (secs < 10) secs = "0" + secs;

        document.getElementById("b").innerHTML =
          Math.floor(endtime / 60) + ":" + secs;

        bscrollpoint = window.pageYOffset;
      } // if
    } else if (ev.keyCode == cleara) {
      starttime = 0;
      endtime = song.duration;
      ascrollpoint = 0;
      bscrollpoint = 0;

      document.getElementById("a").innerHTML =
        "<font color=#666><i>not set</i></font>";
      document.getElementById("b").innerHTML =
        "<font color=#666><i>not set</i></font>";

      return;
    } // if
  }; // function
}; // getElementByTagName

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
});
