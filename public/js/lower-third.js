// Scene lower third (/overlay?scene=lower): polling /api/lower-third.
(function () {
  "use strict";

  var params = new URLSearchParams(window.location.search);
  if ((params.get("scene") || "").toLowerCase() !== "lower") return;

  var POLL_MS = 1000;
  var LEAVE_MS = 450;
  var API_URL = (document.querySelector('meta[name="api-lower-url"]') || {}).content || "/api/lower-third";

  var els = {
    root: document.getElementById("lowerThird"),
    logo: document.getElementById("lowerLogo"),
    logoImg: document.getElementById("lowerLogoImg"),
    title: document.getElementById("lowerTitle"),
    subtitle: document.getElementById("lowerSubtitle"),
    progress: document.getElementById("lowerProgress")
  };
  var currentKey = "";
  var leaveTimer = null;

  function sanitizeHex(value, fallback) {
    var v = String(value || "").trim().replace(/^#/, "");
    return /^[0-9a-fA-F]{6}$/.test(v) ? "#" + v : fallback;
  }

  function sanitizeText(value, maxLen) {
    return String(value || "").trim().slice(0, maxLen);
  }

  function sanitizeLogo(value) {
    var v = String(value || "").trim();
    return /^(https?:\/\/|\/)/i.test(v) ? v : "";
  }

  function applyTheme(theme) {
    if (!theme) return;
    var primary = sanitizeHex(theme.primary, "#001c54");
    var hex = primary.slice(1);
    var rgb = [0, 2, 4].map(function (i) { return parseInt(hex.slice(i, i + 2), 16); });
    var root = document.documentElement.style;
    root.setProperty("--gt-navy", primary);
    root.setProperty("--gt-navy-90", "rgba(" + rgb.join(", ") + ", 0.9)");
    root.setProperty("--gt-red", sanitizeHex(theme.accent, "#c00000"));
  }

  function show(item) {
    clearTimeout(leaveTimer);
    els.title.textContent = sanitizeText(item.title, 60);
    var subtitle = sanitizeText(item.subtitle, 120);
    els.subtitle.textContent = subtitle;
    els.subtitle.hidden = !subtitle;

    var logo = sanitizeLogo(item.logo);
    els.logo.classList.toggle("has-logo", !!logo);
    if (logo) els.logoImg.src = logo;
    else els.logoImg.removeAttribute("src");

    els.root.hidden = false;
    els.root.classList.remove("is-leaving", "is-entering");
    void els.root.offsetWidth;
    els.root.classList.add("is-entering");
  }

  function hide() {
    if (els.root.hidden || els.root.classList.contains("is-leaving")) return;
    els.root.classList.remove("is-entering");
    els.root.classList.add("is-leaving");
    leaveTimer = setTimeout(function () {
      els.root.hidden = true;
      els.root.classList.remove("is-leaving");
    }, LEAVE_MS);
  }

  function render(data) {
    applyTheme(data && data.theme);
    var item = data && data.lowerThird;
    var key = item ? item.id + "@" + item.shownAt : "";

    if (!item) {
      currentKey = "";
      hide();
      return;
    }

    if (key !== currentKey) {
      currentKey = key;
      show(item);
    }

    var duration = parseInt(item.duration, 10) || 0;
    var remaining = parseInt(item.remaining, 10);
    els.progress.hidden = !duration || isNaN(remaining);
    if (!els.progress.hidden) {
      els.progress.style.transform = "scaleX(" + Math.max(0, remaining / duration) + ")";
    }
  }

  function poll() {
    fetch(API_URL, { cache: "no-store" })
      .then(function (res) {
        if (!res.ok) throw new Error("unavailable");
        return res.json();
      })
      .then(render)
      .catch(function () {
        // Pertahankan tampilan terakhir saat server tidak terjangkau.
      });
  }

  document.getElementById("overlay").hidden = true;
  els.logoImg.addEventListener("error", function () {
    els.logo.classList.remove("has-logo");
  });
  poll();
  setInterval(poll, POLL_MS);
})();
