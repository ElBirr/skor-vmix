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
        <img class="logo-volley" src="{{ asset('img/ball-volley.png') }}" alt="Bola voli" />
        <img class="logo-futsal" src="{{ asset('img/ball-futsal.png') }}" alt="Bola sepak" />
      </span>

      <div class="bug-panel">
        <div class="bug-line">
          <span class="bug-name">
            <span class="bug-color" id="homeColor"></span>
            <span class="bug-team" id="homeName">HOME</span>
            <span class="bug-serve" id="homeServe"></span>
            <span class="bug-goal" aria-hidden="true">
              <img class="bug-goal-mark" id="homeGoalMark" alt="" hidden />
              <img class="bug-goal-logo" id="homeGoalLogo" alt="" hidden />
              <span class="bug-goal-text">POIN!</span>
            </span>
          </span>
          <span class="bug-sets" id="homeSetsWon">0</span>
          <span class="bug-score" id="homeScore">0</span>
        </div>
        <div class="bug-line">
          <span class="bug-name">
            <span class="bug-color" id="awayColor"></span>
            <span class="bug-team" id="awayName">AWAY</span>
            <span class="bug-serve" id="awayServe"></span>
            <span class="bug-goal" aria-hidden="true">
              <img class="bug-goal-mark" id="awayGoalMark" alt="" hidden />
              <img class="bug-goal-logo" id="awayGoalLogo" alt="" hidden />
              <span class="bug-goal-text">POIN!</span>
            </span>
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

    {{-- Banner pergantian set (scene live) --}}
    <div class="set-banner" id="setBanner" hidden>
      <span class="set-banner-logo"><img id="setBannerLogo" alt="" hidden /></span>
      <span class="set-banner-body">
        <span class="set-banner-label" id="setBannerLabel">SET 1 MILIK</span>
        <span class="set-banner-team" id="setBannerTeam">TIM</span>
      </span>
      <span class="set-banner-score" id="setBannerScore">25 - 21</span>
    </div>

    {{-- Layar mulai/selesai (GT 01 VOLLEYBALL 2 OPENING B) --}}
    <div class="intro" id="intro" aria-hidden="true">
      <div class="intro-event-logo" id="introEventLogo" hidden>
        <img id="introEventLogoImg" alt="" />
      </div>
      <div class="intro-headline" id="introSport">VOLI</div>
      <div class="intro-round" id="introRound" hidden></div>
      <div class="intro-panel">
        <span class="intro-half intro-half-home" aria-hidden="true"></span>
        <span class="intro-half intro-half-away" aria-hidden="true"></span>
        <span class="intro-impact" aria-hidden="true"></span>
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
      <div class="intro-venue" id="introVenue" hidden></div>
      <div class="intro-teams">
        <div class="intro-team intro-team-home" id="introHomeTeam">
          <span class="intro-team-name" id="introHomeName">HOME</span>
        </div>
        <div class="intro-team intro-team-away" id="introAwayTeam">
          <span class="intro-team-name" id="introAwayName">AWAY</span>
        </div>
      </div>
      <div class="intro-sets" id="introSets" hidden></div>
      <div class="intro-stats" id="introStats" hidden></div>
    </div>
  </div>

  {{-- Konfeti pemenang di scene selesai --}}
  <div class="confetti" id="confetti" aria-hidden="true"></div>

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

  {{-- Layar jeda (scene=break) & stinger transisi (scene=transition) --}}
  <div class="brk" id="breakScene" hidden>
    <div class="brk-bg" aria-hidden="true"></div>
    <div class="brk-wipe" aria-hidden="true"><i></i><i></i><i></i></div>
    <div class="brk-content">
      <img class="brk-logo" id="breakLogo" alt="" hidden />
      <div class="brk-headline" id="breakHeadline">VOLI</div>
      <img class="brk-art" src="{{ asset('img/volleyball.svg') }}" alt="" />
      <div class="brk-title" id="breakTitle">ISTIRAHAT</div>
      <div class="brk-match" id="breakMatch">
        <span class="brk-home">HOME</span>
        <span class="brk-score">0 - 0</span>
        <span class="brk-away">AWAY</span>
      </div>
      <div class="brk-info" id="breakInfo" hidden></div>
    </div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
  <script src="{{ asset('js/lower-third.js') }}"></script>
  <script src="{{ asset('js/break.js') }}"></script>
</body>
</html>
