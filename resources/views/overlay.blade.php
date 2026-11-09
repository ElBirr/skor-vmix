<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="api-url" content="{{ route('api.match') }}" />
  <meta name="api-lower-url" content="{{ route('api.lower-third') }}" />
  <title>vMix Scoreboard Overlay</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
</head>
<body>
  <div id="overlay" class="overlay scene-live sport-volley" hidden>
    {{-- Papan skor pojok kiri (GT 11 SCOREBOARD LONG LOGO NEW) --}}
    <div class="scorebug" id="scorebug" aria-hidden="true">
      <span class="bug-logo" id="bugLogo">
        <svg class="logo-volley" viewBox="0 0 200 200" role="img" aria-label="Bola voli">
          <circle cx="100" cy="100" r="92" fill="#d9d9d9" stroke="#fff" stroke-width="5" />
          <g fill="none" stroke="#001c54" stroke-width="7" stroke-linecap="round">
            <path d="M100 10C74 55 74 145 100 190" />
            <path d="M100 10c38 34 38 146 0 180" />
            <path d="M12 84c44-16 132-16 176 0" />
            <path d="M12 116c44 16 132 16 176 0" />
          </g>
        </svg>
        <svg class="logo-futsal" viewBox="0 0 200 200" role="img" aria-label="Bola sepak">
          <defs><clipPath id="ballClip"><circle cx="100" cy="100" r="90" /></clipPath></defs>
          <circle cx="100" cy="100" r="92" fill="#ffffff" />
          <g clip-path="url(#ballClip)" fill="#001c54">
            <polygon points="100.0,70.0 128.5,90.7 117.6,124.3 82.4,124.3 71.5,90.7" />
            <polygon points="100.0,38.0 71.5,17.3 82.4,-16.3 117.6,-16.3 128.5,17.3" />
            <polygon points="159.0,80.8 169.9,47.3 205.1,47.3 216.0,80.8 187.5,101.6" />
            <polygon points="136.4,150.2 171.7,150.2 182.6,183.7 154.1,204.4 125.5,183.7" />
            <polygon points="63.6,150.2 74.5,183.7 45.9,204.4 17.4,183.7 28.3,150.2" />
            <polygon points="41.0,80.8 12.5,101.6 -16.0,80.8 -5.1,47.3 30.1,47.3" />
            <path d="M100.0 70.0L100.0 38.0 M128.5 90.7L159.0 80.8 M117.6 124.3L136.4 150.2 M82.4 124.3L63.6 150.2 M71.5 90.7L41.0 80.8" stroke="#001c54" stroke-width="6" />
          </g>
          <circle cx="100" cy="100" r="92" fill="none" stroke="#fff" stroke-width="5" />
        </svg>
      </span>

      <div class="bug-panel">
        <div class="bug-line">
          <span class="bug-name">
            <span class="bug-color" id="homeColor"></span>
            <span class="bug-team" id="homeName">HOME</span>
            <span class="bug-timeouts" id="homeTimeouts"><i></i><i></i></span>
            <span class="bug-serve" id="homeServe" hidden></span>
          </span>
          <span class="bug-sets" id="homeSetsWon">0</span>
          <span class="bug-score" id="homeScore">0</span>
        </div>
        <div class="bug-line">
          <span class="bug-name">
            <span class="bug-color" id="awayColor"></span>
            <span class="bug-team" id="awayName">AWAY</span>
            <span class="bug-timeouts" id="awayTimeouts"><i></i><i></i></span>
            <span class="bug-serve" id="awayServe" hidden></span>
          </span>
          <span class="bug-sets" id="awaySetsWon">0</span>
          <span class="bug-score" id="awayScore">0</span>
        </div>
      </div>

      {{-- Info servis / timeout di bawah papan skor --}}
      <div class="bug-tag" id="bugTag" hidden>
        <span class="bug-tag-color" id="bugTagColor"></span>
        <span class="bug-tag-label" id="bugTagLabel">SERVIS</span>
        <span class="bug-tag-team" id="bugTagTeam">HOME</span>
        <span class="bug-tag-clock" id="bugTagClock" hidden></span>
        <span class="bug-tag-progress" id="bugTagProgress"></span>
      </div>
    </div>

    {{-- Layar mulai/selesai (GT 01 VOLLEYBALL 2 OPENING B) --}}
    <div class="intro" id="intro" aria-hidden="true">
      <div class="intro-headline" id="introSport">VOLI</div>
      <div class="intro-panel">
        <span class="intro-shine" aria-hidden="true"></span>
        <div class="intro-logo intro-logo-home">
          <img id="introHomeLogo" alt="" hidden />
          <span class="intro-logo-fallback" id="introHomeFallback">H</span>
        </div>
        <div class="intro-vs">VS</div>
        <div class="intro-logo intro-logo-away">
          <img id="introAwayLogo" alt="" hidden />
          <span class="intro-logo-fallback" id="introAwayFallback">A</span>
        </div>
      </div>
      <div class="intro-strip">
        <span id="introDesc">LIVE • SET 1</span>
      </div>
      <div class="intro-teams">
        <div class="intro-team intro-team-home" id="introHomeTeam">
          <span class="intro-team-name" id="introHomeName">HOME</span>
        </div>
        <div class="intro-team intro-team-away" id="introAwayTeam">
          <span class="intro-team-name" id="introAwayName">AWAY</span>
        </div>
      </div>
      <div class="intro-sets" id="introSets" hidden></div>
    </div>
  </div>

  {{-- Lower third (scene=lower) --}}
  <div class="lower" id="lowerThird" hidden>
    <span class="lower-accent" id="lowerLogo">
      <img id="lowerLogoImg" alt="" />
    </span>
    <div class="lower-body">
      <div class="lower-title" id="lowerTitle"></div>
      <div class="lower-sub" id="lowerSubtitle" hidden></div>
      <span class="lower-progress" id="lowerProgress" hidden></span>
    </div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/lower-third.js') }}"></script>
</body>
</html>
