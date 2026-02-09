# Bugs/Issues

## Search Box
- [ ] **Positioning**: "Search title or lyrics still not posittioned properly between prev arrow and copyright block".
- [ ] **Styling**:## Search Box
- [ ] Search box in upper right is not styled like the other boxes - [Pending Verification] (Styled with `.search-input` and global input styles)
- [ ] Search box in the footer of a displayed song is not positioned properly - [Pending Verification] (Adjusted flex layout and width)
- [ ] Search box does not clear when you click in it - [Pending Verification] (Added `onclick="this.value=''`)

## Navigation
- [x] When playing a song from a set list, next and previous song buttons are missing - **FIXED & VERIFIED** (Added buttons to header, fixed displayset.php parameter)
- [x] Songs playing in a set context are missing next and previous songs - **FIXED & VERIFIED** (Same fix as above)
- [x] Make previous and next song buttons smaller - **FIXED** (Reduced font to 13px, padding to 6px 10px, height to ~25px)

## Audio Player
- [x] 3 dot menu on the right of the audio player - **CANNOT BE FULLY HIDDEN** (Added CSS and controlslist attribute, but browser still shows button. This is a native browser control that cannot be completely removed via HTML/CSS)

## Search Results
- [ ] Search results now incorrectly includes chords - [Pending Verification] (Stripped chords in `search.php`)
- [ ] Search results not embedded and in lightmode - [Pending Verification] (Added Theme Manager to `songbook.js` to persist theme preference. Added chord stripping.)

## Layout
- [x] Jesse's Girl (and perhaps others) doesn't fit on the page - **FIXED** (Enabled vertical scrolling: `#song` has `overflow-y: auto`, `#lyrics-scroller` has `height: auto` to grow vertically in 2 columns. Font reduces to minimum 8px, then vertical scroll appears if still doesn't fit.)
- [x] Move Set line just below the Songbook title and slightly bigger font - **FIXED** (Moved to appear below "Songbook" with 1.1em font size)
