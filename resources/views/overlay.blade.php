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
  <div id="overlay" class="overlay pos-bottom sport-volley" hidden>
    <div class="scoreboard" id="scoreboard">
      <div class="team team-home" id="homeTeam">
        <span class="serve-mark" id="homeServe" aria-hidden="true"></span>
        <span class="team-color" id="homeColor"></span>
        <div class="team-info">
          <span class="team-name" id="homeName">HOME</span>
          <span class="team-meta">
            <span class="sets-won" id="homeSetsWon"></span>
            <span class="timeout-dots" id="homeTimeouts"></span>
          </span>
          <span class="team-sub" id="homeSub"></span>
        </div>
        <span class="team-score" id="homeScore">0</span>
      </div>

      <div class="center">
        <span class="period" id="period">SET 1</span>
        <span class="clock" id="clock"></span>
        <span class="status" id="status"></span>
      </div>

      <div class="team team-away" id="awayTeam">
        <span class="team-score" id="awayScore">0</span>
        <div class="team-info">
          <span class="team-name" id="awayName">AWAY</span>
          <span class="team-meta">
            <span class="sets-won" id="awaySetsWon"></span>
            <span class="timeout-dots" id="awayTimeouts"></span>
          </span>
          <span class="team-sub" id="awaySub"></span>
        </div>
        <span class="team-color" id="awayColor"></span>
        <span class="serve-mark" id="awayServe" aria-hidden="true"></span>
      </div>
    </div>

    <div class="set-strip" id="setStrip" hidden></div>
  </div>

  <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
