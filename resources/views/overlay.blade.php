<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="api-url" content="{{ route('api.match') }}" />
  <title>vMix Scoreboard Overlay</title>
  <link rel="stylesheet" href="{{ asset('css/style.css') }}" />
</head>
<body>
  <div id="overlay" class="overlay scene-live sport-volley" hidden>
    {{-- Papan skor pojok kiri (GT 11 SCOREBOARD LONG LOGO NEW) --}}
    <div class="scorebug" id="scorebug" aria-hidden="true">
      <span class="bug-logo" id="bugLogo">
        <img id="liveLogo" alt="" hidden />
        <svg class="logo-volley" viewBox="0 0 200 200" role="img" aria-label="Bola voli">
          <circle cx="100" cy="100" r="92" fill="#d9d9d9" stroke="#fff" stroke-width="5" />
          <g fill="none" stroke="#001c54" stroke-width="7" stroke-linecap="round">
            <path d="M100 10C74 55 74 145 100 190" />
            <path d="M100 10c38 34 38 146 0 180" />
            <path d="M12 84c44-16 132-16 176 0" />
            <path d="M12 116c44 16 132 16 176 0" />
          </g>
        </svg>
        <svg class="logo-futsal" viewBox="0 0 200 200" role="img" aria-label="Bola futsal">
          <circle cx="100" cy="100" r="92" fill="#d9d9d9" stroke="#fff" stroke-width="5" />
          <path d="M100 42l48 35-18 56H70L52 77z" fill="#001c54" />
          <g stroke="#001c54" stroke-width="7">
            <path d="M100 42V10M148 77l30-10M130 133l19 25M70 133l-19 25M52 77L22 67" />
          </g>
        </svg>
      </span>

      <div class="bug-panel">
        <div class="bug-line">
          <span class="bug-name">
            <span class="bug-color" id="homeColor"></span>
            <span class="bug-team" id="homeName">HOME</span>
          </span>
          <span class="bug-sets" id="homeSetsWon">0</span>
          <span class="bug-score" id="homeScore">0</span>
        </div>
        <div class="bug-line">
          <span class="bug-name">
            <span class="bug-color" id="awayColor"></span>
            <span class="bug-team" id="awayName">AWAY</span>
          </span>
          <span class="bug-sets" id="awaySetsWon">0</span>
          <span class="bug-score" id="awayScore">0</span>
        </div>
      </div>
    </div>

    {{-- Layar mulai/selesai (GT 01 VOLLEYBALL 2 OPENING B) --}}
    <div class="intro" id="intro" aria-hidden="true">
      <div class="intro-headline" id="introSport">VOLI</div>
      <div class="intro-panel">
        <div class="intro-logo">
          <img id="introHomeLogo" alt="" hidden />
          <span class="intro-logo-fallback" id="introHomeFallback">H</span>
        </div>
        <div class="intro-logo">
          <img id="introAwayLogo" alt="" hidden />
          <span class="intro-logo-fallback" id="introAwayFallback">A</span>
        </div>
      </div>
      <div class="intro-strip">
        <span id="introDesc">LIVE • SET 1</span>
      </div>
      <div class="intro-teams">
        <div class="intro-team">
          <span class="intro-team-name" id="introHomeName">HOME</span>
        </div>
        <div class="intro-team">
          <span class="intro-team-name" id="introAwayName">AWAY</span>
        </div>
      </div>
    </div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
