(function () {
  "use strict";

  var VALID_SPORTS = ["volley", "futsal"];
  var VALID_SCENES = ["live", "start", "end"];
  var POLL_MS = 1000;

  var params = new URLSearchParams(window.location.search);
  // Scene lower third ditangani lower-third.js.
  if (["lower", "break", "transition"].indexOf((params.get("scene") || "").toLowerCase()) !== -1) return;

  var scene = (params.get("scene") || "").toLowerCase();
  if (VALID_SCENES.indexOf(scene) === -1) scene = "live";

  var API_URL = (document.querySelector('meta[name="api-url"]') || {}).content || "/api/match";
  // Scene selesai tetap butuh data tim walau pertandingan sudah diarsipkan.
  if (scene === "end") API_URL += (API_URL.indexOf("?") === -1 ? "?" : "&") + "latest=1";

  var els = {
    overlay: document.getElementById("overlay"),
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
    introEventLogo: document.getElementById("introEventLogo"),
    introEventLogoImg: document.getElementById("introEventLogoImg"),
    introVenue: document.getElementById("introVenue"),
    confetti: document.getElementById("confetti"),
    introHomeLogoBox: document.querySelector(".intro-logo-home"),
    introAwayLogoBox: document.querySelector(".intro-logo-away"),
    introVs: document.querySelector(".intro-vs"),
    introHomeTeam: document.getElementById("introHomeTeam"),
    introAwayTeam: document.getElementById("introAwayTeam"),
    homeServe: document.getElementById("homeServe"),
    awayServe: document.getElementById("awayServe"),
    bugTag: document.getElementById("bugTag"),
    bugTagColor: document.getElementById("bugTagColor"),
    bugTagLabel: document.getElementById("bugTagLabel"),
    bugTagTeam: document.getElementById("bugTagTeam"),
    bugTagClock: document.getElementById("bugTagClock"),
    bugTagProgress: document.getElementById("bugTagProgress")
  };

  var VALID_SIDES = ["home", "away"];
  var lastScores = { home: null, away: null };
  var lastSetsWon = { home: null, away: null };
  // Posisi tim ditukar: skor berpindah baris, jangan dianggap poin baru.
  var lastTeamNames = null;
  var sidesJustSwapped = false;
  // Label SERVIS hanya tampil sebentar setiap kali servis berpindah.
  var SERVE_TAG_MS = 8000;
  var serveTag = { side: "", until: 0 };
  // Kartu "AKHIR SET" tampil sebentar setiap kali set selesai.
  var SET_END_MS = 8000;
  var setEnd = { count: null, until: 0 };
  // Kontrol dari halaman operator; hanya bereaksi saat nilainya berubah
  // supaya overlayIn()/overlayOut() dari vMix tetap bisa dipakai.
  var remote = { visible: null, serveCue: null };
  var confettiPlayed = false;
  var MONTHS = ["JAN", "FEB", "MAR", "APR", "MEI", "JUN", "JUL", "AGU", "SEP", "OKT", "NOV", "DES"];

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
      currentSet: 1,
      headline: "",
      venue: "",
      round: "",
      durationMinutes: null,
      date: "",
      sets: [],
      serving: "",
      timeout: null,
      isFinished: false,
      stats: null,
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
    model.currentSet = clampInt(data.currentSet, 1, 5, 1);
    model.headline = sanitizeText(data.headline, "", 40);
    model.venue = sanitizeText(data.venue, "", 60);
    model.round = sanitizeText(data.round, "", 40);
    model.durationMinutes = data.durationMinutes == null ? null : clampInt(data.durationMinutes, 0, 1440, 0);
    model.date = /^\d{4}-\d{2}-\d{2}$/.test(data.date || "") ? data.date : "";

    if (Array.isArray(data.sets)) {
      model.sets = data.sets.slice(0, 5).map(function (set) {
        return {
          home: clampInt(set.home, 0, 999, 0),
          away: clampInt(set.away, 0, 999, 0)
        };
      });
    }

    model.isFinished = data.isFinished === true;
    if (data.stats && data.stats.totals && data.stats.longestRun) {
      model.stats = {
        totals: {
          home: clampInt(data.stats.totals.home, 0, 9999, 0),
          away: clampInt(data.stats.totals.away, 0, 9999, 0)
        },
        longestRun: {
          home: clampInt(data.stats.longestRun.home, 0, 999, 0),
          away: clampInt(data.stats.longestRun.away, 0, 999, 0)
        }
      };
    }
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
      model.home.name = sanitizeText(data.home.name, model.home.name, 50);
      model.home.logo = sanitizeLogo(data.home.logo);
      model.home.score = clampInt(data.home.score, 0, 999, 0);
      model.home.color = sanitizeHex(data.home.color, model.home.color);
      model.home.setsWon = clampInt(data.home.setsWon, 0, 9, 0);
      model.home.timeouts = clampInt(data.home.timeouts, 0, 2, 0);
    }
    if (data.away) {
      model.away.name = sanitizeText(data.away.name, model.away.name, 50);
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
    if (params.has("home")) model.home.name = sanitizeText(params.get("home"), model.home.name, 50);
    if (params.has("away")) model.away.name = sanitizeText(params.get("away"), model.away.name, 50);
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
    var teamNames = model.home.name + "|" + model.away.name;
    sidesJustSwapped = lastTeamNames === model.away.name + "|" + model.home.name && model.home.name !== model.away.name;
    lastTeamNames = teamNames;
    renderGoalBanner(model);
    setScore(els.homeScore, "home", model.home.score);
    setScore(els.awayScore, "away", model.away.score);
    renderServe(model);
    renderTag(model);
    setSetsWon(els.homeSetsWon, "home", model.sport === "volley" ? model.home.setsWon : "");
    setSetsWon(els.awaySetsWon, "away", model.sport === "volley" ? model.away.setsWon : "");

    // Scene mulai/selesai
    els.introSport.textContent = model.headline || (model.sport === "futsal" ? "FUTSAL" : "VOLI");
    els.introDesc.textContent = introDescription(model);
    renderWinner(model);
    renderEventHeader(model);
    setFittedText(els.introHomeName, model.home.name);
    setFittedText(els.introAwayName, model.away.name);
    renderTeamLogo(els.introHomeLogo, els.introHomeFallback, model.home);
    renderTeamLogo(els.introAwayLogo, els.introAwayFallback, model.away);
    renderSetHistory(model);
    renderStats(model);
  }

  // Nama tim panjang: kecilkan huruf sampai muat, lalu pecah 2 baris bila perlu.
  var NAME_MAX_PX = 56;
  var NAME_MIN_PX = 30;

  function setFittedText(el, text) {
    if (el.dataset.fitted === text) return;
    el.textContent = text;
    fitText(el);
  }

  function fitText(el) {
    // Scene tersembunyi belum bisa diukur; ulangi di render berikutnya.
    if (el.clientWidth === 0) {
      delete el.dataset.fitted;
      return;
    }
    el.classList.remove("is-wrapped");
    var size = NAME_MAX_PX;
    el.style.fontSize = size + "px";
    while (el.scrollWidth > el.clientWidth && size > NAME_MIN_PX) {
      size -= 2;
      el.style.fontSize = size + "px";
    }
    if (el.scrollWidth > el.clientWidth) el.classList.add("is-wrapped");
    el.dataset.fitted = el.textContent;
  }

  // Ukur ulang setelah font selesai dimuat, karena lebar huruf bisa berubah.
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(function () {
      [els.introHomeName, els.introAwayName].forEach(fitText);
    });
  }

  // Ulang animasi kelas tertentu pada elemen.
  function replay(el, className) {
    el.classList.remove(className);
    void el.offsetWidth;
    el.classList.add(className);
  }

  // Skor berubah: animasi "pop" supaya penonton sadar ada poin baru.
  // Efek poin bertambah lewat ?scoreFx=, bisa digabung dengan koma: hop,shine,goal.
  // Default hop,goal. "both" = hop,shine, "none" = tanpa efek tambahan.
  var SCORE_FX = (function () {
    var raw = (params.get("scoreFx") || "hop,goal").toLowerCase();
    if (raw === "both") raw = "hop,shine";
    return raw.split(",").map(function (fx) { return fx.trim(); });
  })();

  function hasFx(name) {
    return SCORE_FX.indexOf(name) !== -1;
  }

  // Bilah POIN!/GOL!: teks sesuai olahraga, logo tim kecil + watermark bila ada.
  function renderGoalBanner(model) {
    var label = model.sport === "futsal" ? "GOL!" : "POIN!";
    ["home", "away"].forEach(function (side) {
      var logo = model[side].logo;
      [document.getElementById(side + "GoalLogo"), document.getElementById(side + "GoalMark")].forEach(function (img) {
        var usable = logo && img.dataset.failed !== logo;
        img.hidden = !usable;
        if (usable && img.getAttribute("src") !== logo) img.src = logo;
      });
      var banner = document.getElementById(side + "GoalLogo").parentNode;
      banner.classList.toggle("has-logo", !!logo && !document.getElementById(side + "GoalLogo").hidden);
      banner.querySelector(".bug-goal-text").textContent = label;
    });
  }

  function hopBall(side) {
    var ball = document.getElementById("bugLogo");
    ball.classList.remove("hop-home", "hop-away");
    void ball.offsetWidth;
    ball.classList.add("hop-" + side);
  }

  function setScore(el, side, score) {
    var changed = lastScores[side] !== null && lastScores[side] !== score;
    if (changed && els.overlay.dataset.ready && !sidesJustSwapped) {
      replay(el, "is-bumped");
      // Poin bertambah (bukan koreksi): bola memantul ke baris tim pencetak poin.
      if (score > lastScores[side]) {
        if (hasFx("hop")) hopBall(side);
        if (hasFx("shine")) replay(el.parentNode, "is-shining");
        if (hasFx("goal")) replay(el.parentNode.querySelector(".bug-name"), "is-goal");
      }
    }
    lastScores[side] = score;
    el.textContent = score;
  }

  // Jumlah set berubah: kotak set berkilat.
  function setSetsWon(el, side, value) {
    var changed = lastSetsWon[side] !== null && lastSetsWon[side] !== value;
    if (changed && value !== "" && els.overlay.dataset.ready && !sidesJustSwapped) replay(el, "is-bumped");
    lastSetsWon[side] = value;
    el.textContent = value;
  }

  function renderServe(model) {
    var serving = model.sport === "volley" ? model.serving : "";
    // Ruang bola selalu disediakan di kedua baris supaya lebar papan tidak berubah.
    els.homeServe.classList.toggle("is-on", serving === "home");
    els.awayServe.classList.toggle("is-on", serving === "away");
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
      showSetBanner(model);
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
    els.introHomeLogoBox.classList.toggle("is-winner", winner === "home");
    els.introAwayLogoBox.classList.toggle("is-winner", winner === "away");
    els.introHomeLogoBox.classList.toggle("is-loser", winner === "away");
    els.introAwayLogoBox.classList.toggle("is-loser", winner === "home");

    if (winner && !confettiPlayed) {
      confettiPlayed = true;
      launchConfetti(model[winner].color);
    }
  }

  // Hujan konfeti warna tim pemenang, sekali setiap scene selesai masuk.
  function launchConfetti(color) {
    var colors = [color, color, "#ffd400", "#ffffff"];
    els.confetti.replaceChildren();
    for (var i = 0; i < 90; i++) {
      var piece = document.createElement("i");
      piece.style.left = (Math.random() * 100).toFixed(1) + "%";
      piece.style.background = colors[i % colors.length];
      piece.style.animationDelay = (1.2 + Math.random() * 1.5).toFixed(2) + "s";
      piece.style.animationDuration = (2.6 + Math.random() * 1.8).toFixed(2) + "s";
      piece.style.setProperty("--drift", (Math.random() * 240 - 120).toFixed(0) + "px");
      piece.style.setProperty("--spin", (Math.random() * 1080 - 540).toFixed(0) + "deg");
      els.confetti.appendChild(piece);
    }
    setTimeout(function () {
      els.confetti.replaceChildren();
    }, 6000);
  }

  // Logo event di atas headline, venue & tanggal di bawah strip (scene mulai).
  function renderEventHeader(model) {
    var logo = els.introEventLogoImg.dataset.failed === model.logo ? "" : model.logo;
    els.introEventLogo.hidden = !logo;
    // Logo event sudah berisi nama event; headline hanya tampil bila diisi khusus.
    els.intro.classList.toggle("has-event-logo", !!logo && !model.headline);
    if (logo && els.introEventLogoImg.getAttribute("src") !== logo) els.introEventLogoImg.src = logo;

    var round = document.getElementById("introRound");
    round.textContent = model.round;
    round.hidden = !model.round;

    // Opening: venue & tanggal. Hasil akhir: durasi pertandingan.
    var parts = [];
    if (scene === "end") {
      if (model.durationMinutes) parts.push("DURASI " + formatDuration(model.durationMinutes));
    } else {
      if (model.venue) parts.push(model.venue);
      if (model.date) {
        var d = model.date.split("-");
        parts.push(parseInt(d[2], 10) + " " + MONTHS[parseInt(d[1], 10) - 1] + " " + d[0]);
      }
    }
    els.introVenue.textContent = parts.join(" • ");
    els.introVenue.hidden = parts.length === 0;
  }

  function formatDuration(minutes) {
    var hours = Math.floor(minutes / 60);
    var rest = minutes % 60;
    if (!hours) return rest + " MENIT";
    return hours + " JAM" + (rest ? " " + rest + " MENIT" : "");
  }

  // Banner besar di tengah bawah saat set selesai (scene live).
  var SET_BANNER_MS = 6000;
  var setBannerTimer = null;

  function showSetBanner(model) {
    if (scene !== "live" || !model.sets.length) return;
    var banner = document.getElementById("setBanner");
    var index = model.sets.length - 1;
    var last = model.sets[index];
    var side = last.home >= last.away ? "home" : "away";
    var team = model[side];

    document.getElementById("setBannerLabel").textContent = model.isFinished
      ? "PEMENANG PERTANDINGAN"
      : "SET " + (index + 1) + " MILIK";
    document.getElementById("setBannerTeam").textContent = team.name;
    document.getElementById("setBannerScore").textContent = model.isFinished
      ? model.home.setsWon + " - " + model.away.setsWon
      : last.home + " - " + last.away;

    var logo = document.getElementById("setBannerLogo");
    var usable = team.logo && logo.dataset.failed !== team.logo;
    logo.hidden = !usable;
    if (usable) logo.src = team.logo;
    banner.classList.toggle("has-logo", !!usable);
    banner.style.setProperty("--banner-color", team.color);

    clearTimeout(setBannerTimer);
    banner.hidden = false;
    banner.classList.remove("is-leaving");
    replay(banner, "is-showing");
    setBannerTimer = setTimeout(function () {
      banner.classList.remove("is-showing");
      banner.classList.add("is-leaving");
      setBannerTimer = setTimeout(function () {
        banner.hidden = true;
        banner.classList.remove("is-leaving");
      }, 500);
    }, SET_BANNER_MS);
  }

  // Statistik ringkas di scene selesai (voli).
  function renderStats(model) {
    var box = document.getElementById("introStats");
    if (scene !== "end" || !model.stats) {
      box.hidden = true;
      box.dataset.key = "";
      return;
    }

    var rows = [
      ["TOTAL POIN", model.stats.totals],
      ["POIN BERUNTUN TERPANJANG", model.stats.longestRun]
    ];
    var key = JSON.stringify(rows);
    if (box.dataset.key === key) return;
    box.dataset.key = key;

    box.replaceChildren();
    rows.forEach(function (row, i) {
      var line = document.createElement("div");
      line.className = "intro-stat";
      line.style.setProperty("--i", i);
      var home = row[1].home;
      var away = row[1].away;
      [[home, home > away], [row[0], false], [away, away > home]].forEach(function (cell, j) {
        var span = document.createElement("span");
        span.textContent = cell[0];
        span.className = j === 1 ? "intro-stat-label" : "intro-stat-value" + (cell[1] ? " is-lead" : "");
        line.appendChild(span);
      });
      box.appendChild(line);
    });
    box.hidden = false;
  }

  // Riwayat skor per set, hanya di scene selesai.
  function renderSetHistory(model) {
    if (scene !== "end" || model.sets.length === 0) {
      els.introSets.hidden = true;
      els.introSets.replaceChildren();
      els.introSets.dataset.key = "";
      return;
    }

    var key = JSON.stringify([model.sets, model.home.name, model.away.name, model.home.color, model.away.color]);
    if (els.introSets.dataset.key === key) return;
    els.introSets.dataset.key = key;

    // Tabel skor per set: baris home & away, kolom tiap set + total set.
    var table = document.createElement("table");
    table.className = "intro-set-table";
    var head = table.insertRow();
    head.className = "intro-set-head";
    ["TIM"].concat(model.sets.map(function (_, i) { return "SET " + (i + 1); }), ["SET"]).forEach(function (text, i, all) {
      var th = document.createElement("th");
      th.textContent = text;
      if (i === all.length - 1) th.className = "is-total";
      head.appendChild(th);
    });

    ["home", "away"].forEach(function (side, row) {
      var other = side === "home" ? "away" : "home";
      var tr = table.insertRow();
      tr.style.setProperty("--i", row + 1);
      if (model[side].setsWon > model[other].setsWon) tr.className = "is-winner-row";
      tr.style.setProperty("--row-color", model[side].color);
      var name = tr.insertCell();
      name.className = "is-name";
      name.textContent = model[side].name;
      model.sets.forEach(function (set) {
        var td = tr.insertCell();
        td.textContent = set[side];
        if (set[side] > set[other]) td.className = "is-won";
      });
      var total = tr.insertCell();
      total.className = "is-total";
      total.textContent = model[side].setsWon;
    });

    els.introSets.replaceChildren(table);
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

    // Voli: set berjalan dan skor set diisi otomatis dari data pertandingan.
    if (model.sport === "volley") {
      parts.push("SET " + model.currentSet);
      if (model.home.setsWon || model.away.setsWon) {
        parts.push(model.home.setsWon + " - " + model.away.setsWon);
      }
      return parts.join(" • ");
    }

    if (model.period) parts.push(model.period);
    if (model.clock) parts.push(model.clock);
    return parts.join(" • ");
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

  // Bersihkan sisa class efek poin; tanpa ini animasi lama ikut diputar ulang
  // saat overlay yang tersembunyi ditampilkan kembali.
  function clearScoreEffects() {
    document.querySelectorAll(".is-bumped, .is-goal, .is-shining").forEach(function (node) {
      node.classList.remove("is-bumped", "is-goal", "is-shining");
    });
    document.getElementById("bugLogo").classList.remove("hop-home", "hop-away");
  }

  function playEnter() {
    var el = sceneEl();
    clearScoreEffects();
    el.classList.remove("is-leaving", "is-entering");
    void el.offsetWidth; // paksa animasi bisa diulang
    el.classList.add("is-entering");
    confettiPlayed = false;
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
    // Durasi koreografi keluar: papan skor ±0,7 dtk, opening/hasil ±1 dtk.
    setTimeout(function () {
      els.overlay.hidden = true;
      el.classList.remove("is-leaving");
    }, scene === "live" ? 700 : 1000);
  };

  window.overlayIn = function () {
    delete els.overlay.dataset.out;
    els.overlay.hidden = false;
    playEnter();
  };

  els.introEventLogoImg.addEventListener("error", function () {
    els.introEventLogoImg.dataset.failed = els.introEventLogoImg.getAttribute("src") || "";
    els.introEventLogo.hidden = true;
  });
  ["homeGoalLogo", "homeGoalMark", "awayGoalLogo", "awayGoalMark", "setBannerLogo"].forEach(function (id) {
    var img = document.getElementById(id);
    img.addEventListener("error", function () {
      img.dataset.failed = img.getAttribute("src") || "";
      img.hidden = true;
      img.parentNode.classList.remove("has-logo");
    });
  });
  wireLogoFallback(els.introHomeLogo, els.introHomeFallback);
  wireLogoFallback(els.introAwayLogo, els.introAwayFallback);
  applyAndRender(defaultModel());
  poll();
  setInterval(poll, POLL_MS);
})();
