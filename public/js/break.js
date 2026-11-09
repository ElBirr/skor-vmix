// Scene jeda (?scene=break) dan stinger transisi (?scene=transition).
(function () {
  "use strict";

  var params = new URLSearchParams(window.location.search);
  var scene = (params.get("scene") || "").toLowerCase();
  if (scene !== "break" && scene !== "transition") return;

  var POLL_MS = 2000;
  var STINGER_MS = 2200;
  var MONTHS = ["JAN", "FEB", "MAR", "APR", "MEI", "JUN", "JUL", "AGU", "SEP", "OKT", "NOV", "DES"];
  var apiUrl = (document.querySelector('meta[name="api-url"]') || {}).content || "/api/match";
  apiUrl += (apiUrl.indexOf("?") === -1 ? "?" : "&") + "latest=1";

  var root = document.getElementById("breakScene");
  var els = {
    headline: document.getElementById("breakHeadline"),
    title: document.getElementById("breakTitle"),
    match: document.getElementById("breakMatch"),
    info: document.getElementById("breakInfo")
  };
  var stingerTimer = null;

  function text(value, maxLen) {
    return String(value || "").trim().slice(0, maxLen);
  }

  function hex(value, fallback) {
    var v = String(value || "").trim().replace(/^#/, "");
    return /^[0-9a-fA-F]{6}$/.test(v) ? "#" + v : fallback;
  }

  function applyTheme(data) {
    var style = document.documentElement.style;
    var theme = data.theme || {};
    style.setProperty("--gt-navy", hex(theme.primary, "#001c54"));
    style.setProperty("--gt-red", hex(theme.accent, "#c00000"));
    if (data.home) style.setProperty("--home-color", hex(data.home.color, "#2563eb"));
    if (data.away) style.setProperty("--away-color", hex(data.away.color, "#dc2626"));
  }

  function render(data) {
    applyTheme(data);
    var sport = data.sport === "futsal" ? "FUTSAL" : "VOLI";
    els.headline.textContent = text(data.headline, 40) || sport;

    var home = data.home || {};
    var away = data.away || {};
    var score = data.sport === "futsal"
      ? (home.score || 0) + " - " + (away.score || 0)
      : (home.setsWon || 0) + " - " + (away.setsWon || 0);
    els.match.querySelector(".brk-home").textContent = text(home.name, 50) || "HOME";
    els.match.querySelector(".brk-score").textContent = score;
    els.match.querySelector(".brk-away").textContent = text(away.name, 50) || "AWAY";

    var parts = [];
    if (data.venue) parts.push(text(data.venue, 60));
    if (/^\d{4}-\d{2}-\d{2}$/.test(data.date || "")) {
      var d = data.date.split("-");
      parts.push(parseInt(d[2], 10) + " " + MONTHS[parseInt(d[1], 10) - 1] + " " + d[0]);
    }
    els.info.textContent = parts.join(" • ");
    els.info.hidden = parts.length === 0;
  }

  function poll() {
    fetch(apiUrl, { cache: "no-store" })
      .then(function (res) {
        if (!res.ok) throw new Error("no match");
        return res.json();
      })
      .then(render)
      .catch(function () {
        // Tanpa data pertandingan tetap tampilkan teks default.
      });
  }

  function replay(className) {
    root.classList.remove(className);
    void root.offsetWidth;
    root.classList.add(className);
  }

  // Stinger diputar sekali; bisa diulang dari vMix lewat overlayIn().
  function playStinger() {
    clearTimeout(stingerTimer);
    root.hidden = false;
    replay("is-playing");
    stingerTimer = setTimeout(function () {
      root.classList.remove("is-playing");
    }, STINGER_MS);
  }

  document.getElementById("overlay").hidden = true;
  root.classList.add("brk-" + scene);
  els.title.textContent = text(params.get("text"), 40) || "ISTIRAHAT";

  if (scene === "transition") {
    window.overlayIn = playStinger;
    window.overlayOut = function () {
      root.classList.remove("is-playing");
    };
    playStinger();
    return;
  }

  window.overlayIn = function () {
    root.hidden = false;
    replay("is-entering");
  };
  window.overlayOut = function () {
    root.classList.remove("is-entering");
    replay("is-leaving");
  };
  root.hidden = false;
  root.classList.add("is-entering");
  poll();
  setInterval(poll, POLL_MS);
})();
