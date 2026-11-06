# Skor VMix

Papan skor lapangan untuk voli dan futsal, lengkap dengan overlay broadcast untuk vMix. Dibuat dengan Laravel 13 + SQLite.

## Fitur

### Layar Skor Operator — `/`
- Tombol `+` / `−` skor untuk Home dan Away.
- Tombol `+ Set` / `− Set` untuk mencatat atau membatalkan set yang dimenangkan.
- Timeout per tim, dibatasi maksimal 2.
- Toggle serve (pindah bola) untuk voli.
- Sinkron otomatis dengan server tiap 1 detik, jadi bisa dibuka dari HP wasit/operator.
- Toast error jika penyimpanan gagal.

### Pengaturan Pertandingan — `/settings`
- Pilih cabang olahraga: Voli Beregu atau Futsal.
- Nama tim Home/Away beserta sub (pelatih/pemain).
- Warna tim Home/Away dan warna aksen overlay (format hex).
- Posisi overlay: atas atau bawah.
- Period/babak (mis. `SET 1`, `1H`, `2H`), clock (mis. `12:45`), dan status (mis. `LIVE`).
- Set aktif dan skor set sebelumnya (format `25-23,25-20`).
- Buat pertandingan baru dalam satu klik.

### Overlay vMix — `/overlay`
- Halaman transparan siap dipakai sebagai Browser Input vMix (1920×1080).
- Layout terpisah untuk voli dan futsal, posisi atas/bawah.
- Menampilkan skor, jumlah set dimenangkan, indikator timeout, penanda serve, serta strip skor set.
- Override tampilan lewat query string tanpa mengubah data pertandingan:
  `sport`, `pos`, `accent`, `home`, `away`, `homeSub`, `awaySub`, `homeScore`, `awayScore`, `homeColor`, `awayColor`, `period`, `clock`/`time`, `status`, `setScores`, `set`, `half`, `serving`, `homeTimeouts`, `awayTimeouts`.
- Kontrol via JavaScript dari vMix: `window.updateScoreboard({ home: { score: 10 } })`.
- Polling otomatis ke API tiap 1 detik.

### API
- `GET /api/match` — state pertandingan aktif dalam JSON untuk overlay.

## Menjalankan

```bash
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan serve
```

Buka `http://localhost:8000` untuk layar operator, `/settings` untuk pengaturan, dan `/overlay` untuk overlay vMix.

## Catatan

- Buat operator pertama di `/register`, lalu masuk sebelum mengubah skor. Overlay dan API hanya-baca tetap dapat dibuka tanpa login.
- Data disimpan di SQLite (`database/database.sqlite`).
