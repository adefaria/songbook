// Javascript functions for controlling audio
starttime = null;
endtime   = null;

// Keycodes
spacebar       = 32;
leftarrow      = 37;
uparrow        = 38;
rightarrow     = 39;
downarrow      = 40;
seta           = 65;
backfewsecs    = 66;
cleara         = 67;
forwardfewsecs = 70;
return2start   = 82;

howmanysecs    = 10;

window.onload = function() {
  song = document.getElementById('song');

  starttime = song.currentTime;
  endtime   = song.duration;
  body      = document.getElementsByTagName('body')[0]

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
          song.pause();
          playing = false;
        } else {
          if (starttime != 0) {
            song.currentTime = starttime
          } // if

          song.play();
          playing = true;
        } // if

        e.preventDefault();
        return;
      } else if (ev.keyCode == return2start || ev.keyCode == uparrow) {
        if (starttime != null) {
          song.currentTime = starttime;
        } else {
          song.currentTime = 0;
        } // if

        return;
      } else if (ev.keyCode == backfewsecs || ev.keyCode == leftarrow) {
        song.currentTime -= howmanysecs;
        song.play()

        return;
      } else if (ev.keyCode == forwardfewsecs || ev.keyCode == rightarrow) {
        song.currentTime += howmanysecs;
        song.play();

        return;
      } else if (ev.keyCode == seta) {
        starttime = song.currentTime;

        return;
      } else if (ev.keyCode == cleara) {
        starttime = 0;

        return;
      } // if
    } // function
  }  // getElementByTagName