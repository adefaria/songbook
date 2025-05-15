(function () {
  "use strict";
  function e(e) {
    e.className = e.className.replace(/help-isVisible*/g, "");
    e.className = e.className.trim();
  }
  function t() {
    var e = window,
      t = document,
      n = t.documentElement,
      r = t.getElementsByTagName("body")[0],
      i = e.innerWidth || n.clientWidth || r.clientWidth;
    return i;
  }
  function n() {
    var e = document;
    return Math.max(
      e.body.scrollHeight,
      e.documentElement.scrollHeight,
      e.body.offsetHeight,
      e.documentElement.offsetHeight,
      e.body.clientHeight,
      e.documentElement.clientHeight
    );
  }
  function r(e) {
    e.helpColsTotal = 0;
    for (e.i = 0; e.i < e.helpLists.length; e.i += 1) {
      if (e.helpLists[e.i].className.indexOf("help-list") !== -1) {
        e.helpColsTotal += 1;
      }
      e.helpListsHeights[e.i] = e.helpLists[e.i].offsetHeight;
    }
    e.maxHeight = Math.max.apply(Math, e.helpListsHeights);
    if (t() <= 1180 && t() > 630 && e.helpColsTotal > 2) {
      e.helpColsTotal = 2;
      e.maxHeight = e.maxHeight * e.helpColsTotal;
    }
    if (t() <= 630) {
      e.maxHeight = e.maxHeight * e.helpColsTotal;
      e.helpColsTotal = 1;
    }
    // Ensure elements exist before trying to set style
    if (e.helpListWrap && e.helpList) {
      e.helpListWrap.style.offsetWidth = // Note: offsetWidth is read-only, did you mean style.width?
        e.helpList.offsetWidth * e.helpColsTotal + "px";
      e.helpListWrap.style.height = e.maxHeight + "px";
    }
    if (e.helpModal && e.helpList) {
      e.helpModal.style.width =
        e.helpList.offsetWidth * e.helpColsTotal + 60 + "px";
      e.helpModal.style.height = e.maxHeight + 100 + "px";
    }
  }
  function i(e) {
    e = e || window.event;
    var t = e.keyCode || e.which;
    return t;
  }
  function s() {
    var t = document.getElementById("helpUnderlay"),
      s = document.getElementById("helpModal"),
      o = document.getElementById("helpClose"),
      u = null,
      a = {
        i: null,
        maxHeight: null,
        helpListWrap: document.getElementById("helpListWrap"),
        helpList: document.querySelector(".help-list"),
        helpLists: document.querySelectorAll(".help-list"),
        helpModal: s,
        helpColsTotal: null,
        helpListsHeights: [],
      },
      f;

    // Ensure helpListWrap and helpList are found before calling r(a)
    if (a.helpListWrap && a.helpList) {
      r(a);
    } else {
      console.warn("Help list elements not found for initial sizing.");
    }

    document.addEventListener(
      "keypress",
      function (e) {
        if (i(e) === 63) {
          // '?' key
          // Ensure helpUnderlay exists before trying to modify it
          var helpUnderlayElement = document.getElementById("helpUnderlay");
          if (helpUnderlayElement) {
            f = helpUnderlayElement.className;
            if (f.indexOf("help-isVisible") === -1) {
              helpUnderlayElement.className += " help-isVisible";
            }
            helpUnderlayElement.style.height = n() + "px";
          }
        }
      },
      false
    );
    document.addEventListener(
      "keyup",
      function (n) {
        if (i(n) === 27) {
          // ESC key
          // Ensure helpUnderlay exists
          var helpUnderlayElement = document.getElementById("helpUnderlay");
          if (helpUnderlayElement) {
            e(helpUnderlayElement);
          }
        }
      },
      false
    );
    // Ensure elements exist before adding event listeners
    if (t) {
      t.addEventListener(
        "click",
        function () {
          e(t);
        },
        false
      );
    }
    if (s) {
      s.addEventListener(
        "click",
        function (e) {
          e.stopPropagation();
        },
        false
      );
    }
    if (o) {
      o.addEventListener(
        "click",
        function () {
          // Ensure helpUnderlay (t) exists
          if (t) {
            e(t);
          }
        },
        false
      );
    }
    window.onresize = function () {
      if (u !== null) {
        clearTimeout(u);
      }
      u = setTimeout(function () {
        // Ensure helpListWrap and helpList are found before calling r(a)
        if (a.helpListWrap && a.helpList) {
          r(a);
        }
      }, 100);
    };
  }
  function o() {
    var e = false;
    if (window.XMLHttpRequest) {
      e = new XMLHttpRequest();
    }
    return e;
  }
  // MODIFIED FUNCTION u(responseText, callback)
  function u(responseText, callback) {
    if (!document.getElementById("helpUnderlay")) {
      var bodyEl = document.getElementsByTagName("body")[0];
      if (bodyEl) {
        // Create a temporary container element
        var tempContainer = document.createElement("div");
        // Set its innerHTML to the response text. This is safe as it's a new, detached element.
        tempContainer.innerHTML = responseText;
        // Append all children of the new container directly to the body.
        // This assumes responseText contains top-level elements like <div id="helpUnderlay">...</div>
        while (tempContainer.firstChild) {
          bodyEl.appendChild(tempContainer.firstChild);
        }
        callback(); // Call the original callback (s)
      } else {
        console.error("Cannot append help content: body element not found.");
      }
    }
  }
  function a(e) {
    // e is the XMLHttpRequest object
    if (e.readyState === 4) {
      if (e.status === 200 || e.status === 304) {
        var t_responseText = e.responseText;
        u(t_responseText, function () {
          // Pass responseText and callback s
          s();
        });
      } else {
        console.error("Help content request failed with status:", e.status);
      }
    }
  }
  function f() {
    var xhr = o(); // xhr instead of e
    if (xhr) {
      xhr.onreadystatechange = function () {
        a(xhr); // pass xhr to a
      };
      xhr.open("POST", "question.mark.html", true); // Using POST, but GET might be more appropriate if not sending data
      xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded"); // Usually for POST with data
      xhr.send(null); // No data sent for POST, consider GET
    } else {
      var bodyEl = document.getElementsByTagName("body")[0];
      if (bodyEl) {
        bodyEl.innerHTML +=
          "Error: Your browser does not support Ajax for help content.";
      } else {
        console.error("Cannot display Ajax error: body element not found.");
      }
    }
  }
  f(); // Initialize
})();
