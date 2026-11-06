(function () {
  "use strict";

  var VALID_SPORTS = ["volley", "futsal"];
  var VALID_POS = ["bottom", "top"];
  var POLL_MS = 1000;
  var API_URL = (document.querySelector('meta[name="api-url"]') || {}).content || "/api/match";

  var els = {
    overlay: document.getElementById("overlay"),
    homeName: document.getElementById("homeName"),
    awayName: document.getElementById("awayName"),
    homeSub: document.getElementById("homeSub"),
    awaySub: document.getElementById("awaySub"),
    homeScore: document.getElementById("homeScore"),
    awayScore: document.getElementById("awayScore"),
    homeColor: document.getElementById("homeColor"),
    awayColor: document.getElementById("awayColor"),
    homeServe: document.getElementById("homeServe"),
    awayServe: document.getElementById("awayServe"),
    homeSetsWon: document.getElementById("homeSetsWon"),
    awaySetsWon: document.getElementById("awaySetsWon"),
    homeTimeouts: document.getElementById("homeTimeouts"),
    awayTimeouts: document.getElementById("awayTimeouts"),
    period: document.getElementById("period"),
    clock: document.getElementById("clock"),
    status: document.getElementById("status"),
    setStrip: document.getElementById("setStrip")
  };

  var params = new URLSearchParams(window.location.search);

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

  function parseSetScores(raw) {
    if (!raw) return [];
    return String(raw)
      .split(",")
      .map(function (pair) {
        var parts = pair.split("-").map(function (p) {
          return clampInt(p.trim(), 0, 99, null);
        });
        if (parts.length !== 2 || parts[0] === null || parts[1] === null) return null;
        return { home: parts[0], away: parts[1] };
      })
      .filter(function (set) {
        return set !== null;
      })
      .slice(0, 5);
  }

  // Data default (dipakai jika API belum tersedia / file dibuka statis)
  function defaultModel() {
    return {
      sport: "volley",
      pos: "bottom",
      accent: "#f5a623",
      home: { name: "HOME", sub: "", score: 0, color: "#2563eb", setsWon: 0, timeouts: 0 },
      away: { name: "AWAY", sub: "", score: 0, color: "#dc2626", setsWon: 0, timeouts: 0 },
      period: "SET 1",
      clock: "",
      status: "",
      sets: [],
      currentSet: 1,
      serving: ""
    };
  }

  // Model dari respons API (/api/match)
  function modelFromApi(data) {
    var model = defaultModel();
    if (!data) return model;

    model.sport = VALID_SPORTS.indexOf(data.sport) === -1 ? model.sport : data.sport;
    model.pos = VALID_POS.indexOf(data.pos) === -1 ? model.pos : data.pos;
    model.accent = sanitizeHex(data.accent, model.accent);
    model.period = sanitizeText(data.period, model.period, 20);
    model.clock = sanitizeText(data.clock, "", 10);
    model.status = sanitizeText(data.status, "", 20);
    model.sets = Array.isArray(data.sets) ? data.sets : [];
    model.currentSet = clampInt(data.currentSet, 1, 9, 1);
    model.serving = data.serving === "home" || data.serving === "away" ? data.serving : "";

    if (data.home) {
      model.home.name = sanitizeText(data.home.name, model.home.name, 24);
      model.home.sub = sanitizeText(data.home.sub, "", 20);
      model.home.score = clampInt(data.home.score, 0, 999, 0);
      model.home.color = sanitizeHex(data.home.color, model.home.color);
      model.home.setsWon = clampInt(data.home.setsWon, 0, 9, 0);
      model.home.timeouts = clampInt(data.home.timeouts, 0, 4, 0);
    }
    if (data.away) {
      model.away.name = sanitizeText(data.away.name, model.away.name, 24);
      model.away.sub = sanitizeText(data.away.sub, "", 20);
      model.away.score = clampInt(data.away.score, 0, 999, 0);
      model.away.color = sanitizeHex(data.away.color, model.away.color);
      model.away.setsWon = clampInt(data.away.setsWon, 0, 9, 0);
      model.away.timeouts = clampInt(data.away.timeouts, 0, 4, 0);
    }
    return model;
  }

  // Override hanya untuk param yang benar-benar ada di URL
  function applyQueryOverrides(model) {
    if (params.has("sport")) {
      var sport = params.get("sport").toLowerCase();
      if (VALID_SPORTS.indexOf(sport) !== -1) model.sport = sport;
    }
    if (params.has("pos")) {
      var pos = params.get("pos").toLowerCase();
      if (VALID_POS.indexOf(pos) !== -1) model.pos = pos;
    }
    if (params.has("accent")) model.accent = sanitizeHex(params.get("accent"), model.accent);
    if (params.has("home")) model.home.name = sanitizeText(params.get("home"), model.home.name, 24);
    if (params.has("away")) model.away.name = sanitizeText(params.get("away"), model.away.name, 24);
    if (params.has("homeSub")) model.home.sub = sanitizeText(params.get("homeSub"), "", 20);
    if (params.has("awaySub")) model.away.sub = sanitizeText(params.get("awaySub"), "", 20);
    if (params.has("homeScore")) model.home.score = clampInt(params.get("homeScore"), 0, 999, 0);
    if (params.has("awayScore")) model.away.score = clampInt(params.get("awayScore"), 0, 999, 0);
    if (params.has("homeColor")) model.home.color = sanitizeHex(params.get("homeColor"), model.home.color);
    if (params.has("awayColor")) model.away.color = sanitizeHex(params.get("awayColor"), model.away.color);
    if (params.has("period")) model.period = sanitizeText(params.get("period"), model.period, 20);
    if (params.has("clock")) model.clock = sanitizeText(params.get("clock"), "", 10);
    if (params.has("time")) model.clock = sanitizeText(params.get("time"), "", 10);
    if (params.has("status")) model.status = sanitizeText(params.get("status"), "", 20);
    if (params.has("setScores")) model.sets = parseSetScores(params.get("setScores"));
    if (params.has("set")) {
      model.currentSet = clampInt(params.get("set"), 1, 9, 1);
      if (!params.has("period")) model.period = "SET " + model.currentSet;
    }
    if (params.has("half") && !params.has("period")) {
      model.period = "BABAK " + clampInt(params.get("half"), 1, 2, 1);
    }
    if (params.has("serving")) {
      var serving = params.get("serving").toLowerCase();
      model.serving = serving === "home" || serving === "away" ? serving : "";
    }
    if (params.has("homeTimeouts")) model.home.timeouts = clampInt(params.get("homeTimeouts"), 0, 4, 0);
    if (params.has("awayTimeouts")) model.away.timeouts = clampInt(params.get("awayTimeouts"), 0, 4, 0);
    return model;
  }

  function render(model) {
    els.overlay.hidden = false;
    els.overlay.className = "overlay pos-" + model.pos + " sport-" + model.sport;
    document.documentElement.style.setProperty("--accent", model.accent);
    document.documentElement.style.setProperty("--home-color", model.home.color);
    document.documentElement.style.setProperty("--away-color", model.away.color);

    els.homeName.textContent = model.home.name;
    els.awayName.textContent = model.away.name;
    els.homeSub.textContent = model.home.sub;
    els.awaySub.textContent = model.away.sub;
    els.homeScore.textContent = model.home.score;
    els.awayScore.textContent = model.away.score;
    els.period.textContent = (model.period || "").toUpperCase();
    els.clock.textContent = model.clock;
    els.status.textContent = (model.status || "").toUpperCase();

    els.homeServe.classList.toggle("serving", model.serving === "home");
    els.awayServe.classList.toggle("serving", model.serving === "away");

    renderSetsWon(els.homeSetsWon, model.home.setsWon);
    renderSetsWon(els.awaySetsWon, model.away.setsWon);
    renderTimeouts(els.homeTimeouts, model.home.timeouts);
    renderTimeouts(els.awayTimeouts, model.away.timeouts);

    renderSetStrip(model);
  }

  var MAX_SETS = 3; // best of 5 => 3 kemenangan set
  var MAX_TIMEOUTS = 2; // 2 timeout per set

  function renderSetsWon(el, won) {
    var html = "";
    for (var i = 0; i < MAX_SETS; i++) {
      html += '<span class="set-dot' + (i < won ? " won" : "") + '"></span>';
    }
    el.innerHTML = html;
  }

  function renderTimeouts(el, used) {
    var html = "";
    for (var i = 0; i < MAX_TIMEOUTS; i++) {
      html += '<span class="to-dot' + (i < used ? " used" : "") + '"></span>';
    }
    el.innerHTML = html;
  }

  function renderSetStrip(model) {
    if (model.sport !== "volley" || !model.sets.length) {
      els.setStrip.hidden = true;
      els.setStrip.innerHTML = "";
      return;
    }

    var html = '<span class="set-label">Set Sebelumnya</span>';
    model.sets.forEach(function (set, index) {
      var isCurrent = index + 1 === model.currentSet;
      html +=
        '<span class="set-item' +
        (isCurrent ? " current" : "") +
        '">' +
        '<span class="set-num">S' +
        (index + 1) +
        "</span>" +
        set.home +
        "-" +
        set.away +
        "</span>";
    });
    els.setStrip.innerHTML = html;
    els.setStrip.hidden = false;
  }

  function applyAndRender(model) {
    window.scoreboardModel = model;
    render(applyQueryOverrides(model));
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

  applyAndRender(defaultModel());
  poll();
  setInterval(poll, POLL_MS);
})();
