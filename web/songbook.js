// Javascript functions for controlling audio
starttime = 0;
endtime   = 0;
song      = null;
interval  = null;

// Keycodes
spacebar       = 32;
leftarrow      = 37;
rightarrow     = 39;
seta           = 65;
setb           = 66;
cleara         = 67;
return2start   = 82;

howmanysecs    = 10;

function loop() {
  // If endtime is not set then we can't loop
  if (endtime == 0) return;

  // if we're past the endtime then it's time to start back at the A marker
  if (song.currentTime > endtime) song.currentTime = starttime;
} // loop

window.onload = function() {
  song = document.getElementById('song');

  starttime = song.currentTime;
  endtime   = song.duration;
  body      = document.getElementsByTagName('body')[0]

  if (!song.paused) {
   // Set up loop
   interval = setInterval(loop, 1000);
  } // if

  body.onkeydown = 
    function(e) {
      var ev = e || event;
      if (ev.keyCode == spacebar) {
        if (song.paused) {
          playing = false;
        } else {
          playing = true;
        } // if

        if (playing) {
          // Stop loop
          clearInterval(interval)
          song.pause();
          playing = false;
        } else {
          if (starttime != 0) {
            song.currentTime = starttime
          } // if

          // Set up loop
          interval = setInterval(loop, 1000);

          song.play();
          playing = true;
        } // if

        e.preventDefault();
        return;
      } else if (ev.keyCode == return2start) {
        if (starttime != null) {
          song.currentTime = starttime;
        } else {
          song.currentTime = 0;
        } // if

        return;
      } else if (ev.keyCode == leftarrow) {
        song.currentTime -= howmanysecs;
        song.play()

        return;
      } else if (ev.keyCode == rightarrow) {
        song.currentTime += howmanysecs;
        song.play();

        return;
      } else if (ev.keyCode == seta) {
        // Reset endtime if setting a new A marker
        if (endtime != song.duration) endtime = song.duration;

        starttime = song.currentTime;

        // Translate seconds to timecode
        document.getElementById('a').innerHTML = Math.floor(starttime / 60) + ':' + Math.floor(starttime % 60);

        return;
      } else if (ev.keyCode == setb) {
        if (song.currentTime > starttime) {
          endtime = song.currentTime;
          document.getElementById('b').innerHTML = Math.floor(endtime / 60) + ':' + Math.floor(endtime % 60);
        } // if
      } else if (ev.keyCode == cleara) {
        starttime = 0;
        endtime   = song.duration;

        document.getElementById('a').innerHTML = '<font color=#666><i>not set</i></font>';
        document.getElementById('b').innerHTML = '<font color=#666><i>not set</i></font>';

        return;
      } // if
    } // function
  }  // getElementByTagName