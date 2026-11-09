(function () {
  "use strict";

  var VALID_SPORTS = ["volley", "futsal"];
  var VALID_SCENES = ["live", "start", "end"];
  var POLL_MS = 1000;

  var params = new URLSearchParams(window.location.search);
  var scene = (params.get("scene") || "").toLowerCase();
  if (VALID_SCENES.indexOf(scene) === -1) scene = "live";

  var API_URL = (document.querySelector('meta[name="api-url"]') || {}).content || "/api/match";
  // Scene selesai tetap butuh data tim walau pertandingan sudah diarsipkan.
  if (scene === "end") API_URL += (API_URL.indexOf("?") === -1 ? "?" : "&") + "latest=1";

  var els = {
    overlay: document.getElementById("overlay"),
    bugLogo: document.getElementById("bugLogo"),
    liveLogo: document.getElementById("liveLogo"),
    homeName: document.getElementById("homeName"),
    awayName: document.getElementById("awayName"),
    homeScore: document.getElementById("homeScore"),
    awayScore: document.getElementById("awayScore"),
    homeColor: document.getElementById("homeColor"),
    awayColor: document.getElementById("awayColor"),
    homeSetsWon: document.getElementById("homeSetsWon"),
    awaySetsWon: document.getElementById("awaySetsWon"),
    intro: document.getElementById("intro"),
    introSport: document.getElementById("introSport"),
    introDesc: document.getElementById("introDesc"),
    introHomeName: document.getElementById("introHomeName"),
    introAwayName: document.getElementById("introAwayName"),
    introHomeLogo: document.getElementById("introHomeLogo"),
    introAwayLogo: document.getElementById("introAwayLogo"),
    introHomeFallback: document.getElementById("introHomeFallback"),
    introAwayFallback: document.getElementById("introAwayFallback")
  };

  function clampInt(value, min, max, fallback) {
    var n = parseInt(value, 10);
    if (isNaN(n)) return fallback;
    return Math.min(max, Math.max(min, n));
  }

  function sanitizeText(value, fallback, maxLen) {
    if (value === null || value === undefined) return fallback;
    var text = String(value).trim();
    if (!text) return fallback;
    return text.length > maxLen ? text.slice(0, maxLen) : text;
  }

  function sanitizeHex(value, fallback) {
    if (!value) return fallback;
    var v = String(value).trim().replace(/^#/, "");
    if (/^[0-9a-fA-F]{6}$/.test(v)) return "#" + v;
    return fallback;
  }

  // Hanya URL http(s) atau path lokal, supaya src tidak bisa diisi javascript:.
  function sanitizeLogo(value) {
    if (!value) return "";
    var v = String(value).trim();
    return /^(https?:\/\/|\/)/i.test(v) ? v : "";
  }

  // Data default (dipakai jika API belum tersedia / file dibuka statis)
  function defaultModel() {
    return {
      sport: "volley",
      logo: "",
      home: {
        name: "HOME",
        logo: "",
        score: 0,
        color: "#2563eb",
        setsWon: 0
      },
      away: {
        name: "AWAY",
        logo: "",
        score: 0,
        color: "#dc2626",
        setsWon: 0
      },
      period: "SET 1",
      clock: "",
      status: ""
    };
  }

  // Model dari respons API (/api/match)
  function modelFromApi(data) {
    var model = defaultModel();
    if (!data) return model;

    model.sport = VALID_SPORTS.indexOf(data.sport) === -1 ? model.sport : data.sport;
    model.logo = sanitizeLogo(data.logo);
    model.period = sanitizeText(data.period, model.period, 20);
    model.clock = sanitizeText(data.clock, "", 10);
    model.status = sanitizeText(data.status, "", 20);

    if (data.home) {
      model.home.name = sanitizeText(data.home.name, model.home.name, 24);
      model.home.logo = sanitizeLogo(data.home.logo);
      model.home.score = clampInt(data.home.score, 0, 999, 0);
      model.home.color = sanitizeHex(data.home.color, model.home.color);
      model.home.setsWon = clampInt(data.home.setsWon, 0, 9, 0);
    }
    if (data.away) {
      model.away.name = sanitizeText(data.away.name, model.away.name, 24);
      model.away.logo = sanitizeLogo(data.away.logo);
      model.away.score = clampInt(data.away.score, 0, 999, 0);
      model.away.color = sanitizeHex(data.away.color, model.away.color);
      model.away.setsWon = clampInt(data.away.setsWon, 0, 9, 0);
    }
    return model;
  }

  // Override hanya untuk param yang benar-benar ada di URL
  function applyQueryOverrides(model) {
    if (params.has("sport")) {
      var sport = params.get("sport").toLowerCase();
      if (VALID_SPORTS.indexOf(sport) !== -1) model.sport = sport;
    }
    if (params.has("logo")) model.logo = sanitizeLogo(params.get("logo"));
    if (params.has("home")) model.home.name = sanitizeText(params.get("home"), model.home.name, 24);
    if (params.has("away")) model.away.name = sanitizeText(params.get("away"), model.away.name, 24);
    if (params.has("homeLogo")) model.home.logo = sanitizeLogo(params.get("homeLogo"));
    if (params.has("awayLogo")) model.away.logo = sanitizeLogo(params.get("awayLogo"));
    if (params.has("homeScore")) model.home.score = clampInt(params.get("homeScore"), 0, 999, 0);
    if (params.has("awayScore")) model.away.score = clampInt(params.get("awayScore"), 0, 999, 0);
    if (params.has("homeColor")) model.home.color = sanitizeHex(params.get("homeColor"), model.home.color);
    if (params.has("awayColor")) model.away.color = sanitizeHex(params.get("awayColor"), model.away.color);
    if (params.has("period")) model.period = sanitizeText(params.get("period"), model.period, 20);
    if (params.has("clock")) model.clock = sanitizeText(params.get("clock"), "", 10);
    if (params.has("time")) model.clock = sanitizeText(params.get("time"), "", 10);
    if (params.has("status")) model.status = sanitizeText(params.get("status"), "", 20);
    return model;
  }

  function render(model) {
    if (els.overlay.dataset.out !== "1") els.overlay.hidden = false;
    els.overlay.className = "overlay scene-" + scene + " sport-" + model.sport;
    document.documentElement.style.setProperty("--home-color", model.home.color);
    document.documentElement.style.setProperty("--away-color", model.away.color);

    if (!els.overlay.dataset.entered) {
      els.overlay.dataset.entered = "1";
      playEnter();
    }

    // Papan skor pojok kiri
    els.homeName.textContent = model.home.name;
    els.awayName.textContent = model.away.name;
    els.homeScore.textContent = model.home.score;
    els.awayScore.textContent = model.away.score;
    els.homeSetsWon.textContent = model.sport === "volley" ? model.home.setsWon : "";
    els.awaySetsWon.textContent = model.sport === "volley" ? model.away.setsWon : "";
    renderEventLogo(model.logo);

    // Scene mulai/selesai
    els.introSport.textContent = model.sport === "futsal" ? "FUTSAL" : "VOLI";
    els.introDesc.textContent = introDescription(model);
    els.introHomeName.textContent = model.home.name;
    els.introAwayName.textContent = model.away.name;
    renderTeamLogo(els.introHomeLogo, els.introHomeFallback, model.home);
    renderTeamLogo(els.introAwayLogo, els.introAwayFallback, model.away);
  }

  function introDescription(model) {
    var status = model.status || (scene === "end" ? "SELESAI" : "LIVE");
    var parts = [status];

    if (scene === "end") {
      parts.push(
        model.sport === "futsal"
          ? model.home.score + " - " + model.away.score
          : model.home.setsWon + " - " + model.away.setsWon
      );
      return parts.join(" • ");
    }

    if (model.period) parts.push(model.period);
    if (model.clock) parts.push(model.clock);
    return parts.join(" • ");
  }

  function renderEventLogo(url) {
    if (!url || els.liveLogo.dataset.failed === url) {
      els.liveLogo.hidden = true;
      els.liveLogo.removeAttribute("src");
      els.bugLogo.classList.remove("has-logo");
      return;
    }
    if (els.liveLogo.getAttribute("src") !== url) els.liveLogo.src = url;
    els.liveLogo.hidden = false;
    els.bugLogo.classList.add("has-logo");
  }

  function renderTeamLogo(img, fallback, team) {
    if (team.logo && img.dataset.failed !== team.logo) {
      if (img.getAttribute("src") !== team.logo) img.src = team.logo;
      img.hidden = false;
      fallback.hidden = true;
      return;
    }
    img.removeAttribute("src");
    img.hidden = true;
    fallback.hidden = false;
    fallback.textContent = (team.name || "?").trim().slice(0, 2).toUpperCase();
  }

  // Jika gambar gagal dimuat, kembalikan ke logo cadangan.
  function wireLogoFallback(img, fallback, onError) {
    img.addEventListener("error", function () {
      img.hidden = true;
      img.dataset.failed = img.getAttribute("src") || "";
      if (onError) onError();
      else if (fallback) fallback.hidden = false;
    });
  }

  function applyAndRender(model) {
    window.scoreboardModel = model;
    render(applyQueryOverrides(model));
  }

  // Elemen scene aktif (papan skor atau layar mulai/selesai)
  function sceneEl() {
    return scene === "live" ? document.getElementById("scorebug") : els.intro;
  }

  function playEnter() {
    var el = sceneEl();
    el.classList.remove("is-leaving", "is-entering");
    void el.offsetWidth; // paksa animasi bisa diulang
    el.classList.add("is-entering");
  }

  // Gabung partial tanpa menghapus objek home/away hasil merge
  function mergePartial(base, partial) {
    if (partial.home) base.home = Object.assign({}, base.home, partial.home);
    if (partial.away) base.away = Object.assign({}, base.away, partial.away);
    var home = base.home;
    var away = base.away;
    Object.assign(base, partial);
    base.home = home;
    base.away = away;
    return base;
  }

  function poll() {
    fetch(API_URL, { cache: "no-store" })
      .then(function (res) {
        if (!res.ok) throw new Error("no match");
        return res.json();
      })
      .then(function (data) {
        var model = modelFromApi(data);
        if (window.scoreboardOverrides) model = mergePartial(model, window.scoreboardOverrides);
        applyAndRender(model);
      })
      .catch(function () {
        // API belum tersedia: pertahankan render terakhir / default
        if (!els.overlay.dataset.ready) {
          applyAndRender(defaultModel());
        }
      })
      .finally(function () {
        els.overlay.dataset.ready = "1";
      });
  }

  // Hook opsional untuk update manual dari console/vMix scripting
  window.updateScoreboard = function (partial) {
    if (!partial || typeof partial !== "object") return;
    window.scoreboardOverrides = mergePartial(window.scoreboardOverrides || {}, partial);
    applyAndRender(mergePartial(window.scoreboardModel || defaultModel(), window.scoreboardOverrides));
  };

  // Hapus override manual dan kembali mengikuti data server
  window.resetScoreboard = function () {
    window.scoreboardOverrides = null;
    poll();
  };

  // Animasi keluar/masuk manual, bisa dipanggil dari vMix (Execute JavaScript)
  window.overlayOut = function () {
    var el = sceneEl();
    els.overlay.dataset.out = "1";
    el.classList.remove("is-entering");
    el.classList.add("is-leaving");
    setTimeout(function () {
      els.overlay.hidden = true;
      el.classList.remove("is-leaving");
    }, 360);
  };

  window.overlayIn = function () {
    delete els.overlay.dataset.out;
    els.overlay.hidden = false;
    playEnter();
  };

  wireLogoFallback(els.liveLogo, null, function () {
    els.liveLogo.hidden = true;
    els.bugLogo.classList.remove("has-logo");
  });
  wireLogoFallback(els.introHomeLogo, els.introHomeFallback);
  wireLogoFallback(els.introAwayLogo, els.introAwayFallback);
  applyAndRender(defaultModel());
  poll();
  setInterval(poll, POLL_MS);
})();
