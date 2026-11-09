(function () {
  "use strict";

  var VALID_SPORTS = ["volley", "futsal"];
  var VALID_SCENES = ["live", "start", "end"];
  var POLL_MS = 1000;

  var params = new URLSearchParams(window.location.search);
  // Scene lower third ditangani lower-third.js.
  if ((params.get("scene") || "").toLowerCase() === "lower") return;

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
    introAwayFallback: document.getElementById("introAwayFallback"),
    introSets: document.getElementById("introSets"),
    introVs: document.querySelector(".intro-vs"),
    introHomeTeam: document.getElementById("introHomeTeam"),
    introAwayTeam: document.getElementById("introAwayTeam"),
    homeServe: document.getElementById("homeServe"),
    awayServe: document.getElementById("awayServe"),
    homeTimeouts: document.getElementById("homeTimeouts"),
    awayTimeouts: document.getElementById("awayTimeouts"),
    bugTag: document.getElementById("bugTag"),
    bugTagColor: document.getElementById("bugTagColor"),
    bugTagLabel: document.getElementById("bugTagLabel"),
    bugTagTeam: document.getElementById("bugTagTeam"),
    bugTagClock: document.getElementById("bugTagClock"),
    bugTagProgress: document.getElementById("bugTagProgress")
  };

  var VALID_SIDES = ["home", "away"];
  var lastScores = { home: null, away: null };
  // Label SERVIS hanya tampil sebentar setiap kali servis berpindah.
  var SERVE_TAG_MS = 8000;
  var serveTag = { side: "", until: 0 };
  // Kartu "AKHIR SET" tampil sebentar setiap kali set selesai.
  var SET_END_MS = 8000;
  var setEnd = { count: null, until: 0 };
  // Kontrol dari halaman operator; hanya bereaksi saat nilainya berubah
  // supaya overlayIn()/overlayOut() dari vMix tetap bisa dipakai.
  var remote = { visible: null, serveCue: null };

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
        setsWon: 0,
        timeouts: 0
      },
      away: {
        name: "AWAY",
        logo: "",
        score: 0,
        color: "#dc2626",
        setsWon: 0,
        timeouts: 0
      },
      period: "SET 1",
      clock: "",
      status: "",
      sets: [],
      serving: "",
      timeout: null,
      isFinished: false,
      theme: { primary: "#001c54", accent: "#c00000" }
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

    if (Array.isArray(data.sets)) {
      model.sets = data.sets.slice(0, 5).map(function (set) {
        return {
          home: clampInt(set.home, 0, 999, 0),
          away: clampInt(set.away, 0, 999, 0)
        };
      });
    }

    model.isFinished = data.isFinished === true;
    if (data.theme) {
      model.theme.primary = sanitizeHex(data.theme.primary, model.theme.primary);
      model.theme.accent = sanitizeHex(data.theme.accent, model.theme.accent);
    }
    model.serving = VALID_SIDES.indexOf(data.serving) === -1 ? "" : data.serving;
    if (data.timeout && VALID_SIDES.indexOf(data.timeout.side) !== -1) {
      var duration = clampInt(data.timeout.duration, 1, 600, 30);
      model.timeout = {
        side: data.timeout.side,
        duration: duration,
        remaining: clampInt(data.timeout.remaining, 0, duration, 0)
      };
    }

    if (data.home) {
      model.home.name = sanitizeText(data.home.name, model.home.name, 24);
      model.home.logo = sanitizeLogo(data.home.logo);
      model.home.score = clampInt(data.home.score, 0, 999, 0);
      model.home.color = sanitizeHex(data.home.color, model.home.color);
      model.home.setsWon = clampInt(data.home.setsWon, 0, 9, 0);
      model.home.timeouts = clampInt(data.home.timeouts, 0, 2, 0);
    }
    if (data.away) {
      model.away.name = sanitizeText(data.away.name, model.away.name, 24);
      model.away.logo = sanitizeLogo(data.away.logo);
      model.away.score = clampInt(data.away.score, 0, 999, 0);
      model.away.color = sanitizeHex(data.away.color, model.away.color);
      model.away.setsWon = clampInt(data.away.setsWon, 0, 9, 0);
      model.away.timeouts = clampInt(data.away.timeouts, 0, 2, 0);
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
    if (params.has("serving")) {
      var serving = params.get("serving").toLowerCase();
      model.serving = VALID_SIDES.indexOf(serving) === -1 ? "" : serving;
    }
    if (params.has("status")) model.status = sanitizeText(params.get("status"), "", 20);
    return model;
  }

  function render(model) {
    if (els.overlay.dataset.out !== "1") els.overlay.hidden = false;
    els.overlay.className = "overlay scene-" + scene + " sport-" + model.sport;
    document.documentElement.style.setProperty("--home-color", model.home.color);
    document.documentElement.style.setProperty("--away-color", model.away.color);
    applyTheme(model.theme);

    if (!els.overlay.dataset.entered) {
      els.overlay.dataset.entered = "1";
      playEnter();
    }

    // Papan skor pojok kiri
    els.homeName.textContent = model.home.name;
    els.awayName.textContent = model.away.name;
    setScore(els.homeScore, "home", model.home.score);
    setScore(els.awayScore, "away", model.away.score);
    renderServe(model);
    renderTimeouts(model);
    renderTag(model);
    els.homeSetsWon.textContent = model.sport === "volley" ? model.home.setsWon : "";
    els.awaySetsWon.textContent = model.sport === "volley" ? model.away.setsWon : "";
    renderEventLogo(model.logo);

    // Scene mulai/selesai
    els.introSport.textContent = model.sport === "futsal" ? "FUTSAL" : "VOLI";
    els.introDesc.textContent = introDescription(model);
    renderWinner(model);
    els.introHomeName.textContent = model.home.name;
    els.introAwayName.textContent = model.away.name;
    renderTeamLogo(els.introHomeLogo, els.introHomeFallback, model.home);
    renderTeamLogo(els.introAwayLogo, els.introAwayFallback, model.away);
    renderSetHistory(model);
  }

  // Ulang animasi kelas tertentu pada elemen.
  function replay(el, className) {
    el.classList.remove(className);
    void el.offsetWidth;
    el.classList.add(className);
  }

  // Skor berubah: animasi "pop" supaya penonton sadar ada poin baru.
  function setScore(el, side, score) {
    var changed = lastScores[side] !== null && lastScores[side] !== score;
    if (changed && els.overlay.dataset.ready) replay(el, "is-bumped");
    lastScores[side] = score;
    el.textContent = score;
  }

  function renderServe(model) {
    var serving = model.sport === "volley" ? model.serving : "";
    els.homeServe.hidden = serving !== "home";
    els.awayServe.hidden = serving !== "away";
  }

  // Kondisi set point / match point voli (best of 5, set ke-5 sampai 15).
  function pointSituation(model) {
    if (model.sport !== "volley" || model.isFinished) return null;
    var target = model.sets.length === 4 ? 15 : 25;
    var h = model.home.score;
    var a = model.away.score;
    var side = h > a ? "home" : a > h ? "away" : "";
    if (!side || Math.max(h, a) < target - 1) return null;
    return { side: side, label: model[side].setsWon === 2 ? "MATCH POINT" : "SET POINT" };
  }

  function formatClock(seconds) {
    return Math.floor(seconds / 60) + ":" + String(seconds % 60).padStart(2, "0");
  }

  // Isi label di bawah papan skor, urutan prioritas:
  // TIME OUT > AKHIR SET > SET/MATCH POINT > SERVIS.
  function tagContent(model) {
    var now = Date.now();

    if (model.sets.length > setEnd.count && els.overlay.dataset.ready) {
      setEnd.until = now + SET_END_MS;
    }
    setEnd.count = model.sets.length;

    if (model.serving !== serveTag.side) {
      serveTag.side = model.serving;
      serveTag.until = model.serving ? now + SERVE_TAG_MS : 0;
    }

    if (model.timeout) {
      var t = model.timeout;
      return {
        mode: "timeout", side: t.side, label: "TIME OUT",
        clock: formatClock(t.remaining), progress: t.remaining / t.duration
      };
    }

    if (now < setEnd.until && model.sets.length) {
      var index = model.sets.length - 1;
      var last = model.sets[index];
      return {
        mode: "set", side: last.home >= last.away ? "home" : "away",
        label: "AKHIR SET " + (index + 1), clock: last.home + "-" + last.away, key: index
      };
    }

    var point = pointSituation(model);
    if (point) return { mode: "point", side: point.side, label: point.label, key: point.label };

    if (model.sport === "volley" && model.serving && now < serveTag.until) {
      return { mode: "serve", side: model.serving, label: "SERVIS" };
    }

    return null;
  }

  function renderTag(model) {
    var content = tagContent(model);
    if (!content) {
      hideTag();
      return;
    }

    var tag = els.bugTag;
    var key = content.mode + ":" + content.side + ":" + (content.key || "");
    if (tag.dataset.key !== key) {
      tag.dataset.key = key;
      tag.hidden = false;
      tag.classList.remove("is-hiding");
      replay(tag, "is-showing");
    }
    ["timeout", "set", "point", "serve"].forEach(function (mode) {
      tag.classList.toggle("is-" + mode, content.mode === mode);
    });
    tag.style.setProperty("--tag-color", model[content.side].color);
    els.bugTagLabel.textContent = content.label;
    els.bugTagTeam.textContent = model[content.side].name;
    els.bugTagClock.hidden = !content.clock;
    els.bugTagClock.textContent = content.clock || "";
    els.bugTagProgress.style.transform = "scaleX(" + (content.progress || 0) + ")";
  }

  // Titik sisa jatah timeout (voli: 2 per set).
  function renderTimeouts(model) {
    ["home", "away"].forEach(function (side) {
      var dots = els[side + "Timeouts"];
      dots.hidden = model.sport !== "volley";
      Array.prototype.forEach.call(dots.children, function (dot, i) {
        dot.classList.toggle("is-used", i < model[side].timeouts);
      });
    });
  }

  // Warna tema dari pengaturan (warna utama dan aksen).
  function applyTheme(theme) {
    var root = document.documentElement.style;
    var hex = theme.primary.slice(1);
    var rgb = [0, 2, 4].map(function (i) { return parseInt(hex.slice(i, i + 2), 16); });
    root.setProperty("--gt-navy", theme.primary);
    root.setProperty("--gt-navy-90", "rgba(" + rgb.join(", ") + ", 0.9)");
    root.setProperty("--gt-red", theme.accent);
  }

  function hideTag() {
    var tag = els.bugTag;
    if (tag.hidden || tag.dataset.key === "") return;
    tag.dataset.key = "";
    tag.classList.remove("is-showing");
    tag.classList.add("is-hiding");
    setTimeout(function () {
      if (tag.dataset.key === "") tag.hidden = true;
    }, 320);
  }

  // Scene selesai: sorot tim pemenang dan tampilkan skor akhir di tengah.
  function renderWinner(model) {
    var winner = "";
    if (scene === "end") {
      var h = model.sport === "futsal" ? model.home.score : model.home.setsWon;
      var a = model.sport === "futsal" ? model.away.score : model.away.setsWon;
      winner = h > a ? "home" : a > h ? "away" : "";
      els.introVs.textContent = h + " - " + a;
    } else {
      els.introVs.textContent = "VS";
    }
    els.introHomeTeam.classList.toggle("is-winner", winner === "home");
    els.introAwayTeam.classList.toggle("is-winner", winner === "away");
  }

  // Riwayat skor per set, hanya di scene selesai.
  function renderSetHistory(model) {
    if (scene !== "end" || model.sets.length === 0) {
      els.introSets.hidden = true;
      els.introSets.replaceChildren();
      els.introSets.dataset.key = "";
      return;
    }

    var key = JSON.stringify(model.sets);
    if (els.introSets.dataset.key === key) return;
    els.introSets.dataset.key = key;

    els.introSets.replaceChildren();
    model.sets.forEach(function (set, index) {
      var chip = document.createElement("span");
      chip.className = "intro-set";
      chip.style.setProperty("--i", index);

      var label = document.createElement("b");
      label.textContent = "Set " + (index + 1);
      chip.appendChild(label);

      var score = document.createElement("span");
      score.textContent = set.home + " - " + set.away;
      chip.appendChild(score);

      els.introSets.appendChild(chip);
    });
    els.introSets.hidden = false;
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
        applyRemoteControl(data && data.overlay);
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

  function applyRemoteControl(control) {
    if (!control) return;

    var visible = control[scene] !== false;
    if (remote.visible === null) {
      if (!visible) {
        els.overlay.dataset.out = "1";
        els.overlay.hidden = true;
      }
    } else if (visible !== remote.visible) {
      if (visible) window.overlayIn();
      else window.overlayOut();
    }
    remote.visible = visible;

    var cue = parseInt(control.serveCue, 10) || 0;
    if (remote.serveCue !== null && cue !== remote.serveCue) {
      serveTag.side = "";
    }
    remote.serveCue = cue;
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
