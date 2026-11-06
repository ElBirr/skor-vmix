# Skor VMix

Papan skor lapangan untuk voli dan futsal, lengkap dengan overlay broadcast untuk vMix. Dibuat dengan Laravel 13 + SQLite.

## Fitur

### Layar Skor Operator — `/`
- Tombol `+` / `−` skor untuk Home dan Away.
- Set voli tercatat otomatis saat mencapai 25 poin (15 di set kelima). Tekan `−` saat skor masih 0-0 untuk membatalkan set terakhir.
- Timeout per tim, dibatasi maksimal 2.
- Toggle serve (pindah bola) untuk voli.
- Sinkron otomatis dengan server tiap 1 detik, jadi bisa dibuka dari HP wasit/operator.
- Toast error jika penyimpanan gagal.

### Pengaturan Pertandingan — `/settings`
- Pilih cabang olahraga: Voli Beregu atau Futsal.
- Nama tim Home/Away beserta sub (pelatih/pemain).
- Warna tim Home/Away dan warna aksen overlay (format hex).
- Period/babak (mis. `SET 1`, `1H`, `2H`), clock (mis. `12:45`), dan status (mis. `LIVE`).
- Buat pertandingan baru dalam satu klik.

### Overlay vMix — `/overlay`
- Halaman transparan siap dipakai sebagai Browser Input vMix (1920×1080).
- `scene=live` (default): papan skor kecil di pojok kiri atas, tampil sepanjang pertandingan (nama tim, set dimenangkan, poin, dan penanda serve).
- `scene=start` atau `scene=end`: layar nama tim dan logo untuk mulai/selesai.
- Logo tim opsional lewat query `logo` (logo event), `homeLogo` / `awayLogo` (logo tim), atau isi URL logo di halaman pengaturan. Jika kosong dipakai ikon olahraga / inisial tim.
- Override tampilan lewat query string tanpa mengubah data pertandingan:
  `scene`, `sport`, `logo`, `home`, `away`, `homeScore`, `awayScore`, `homeColor`, `awayColor`, `homeLogo`, `awayLogo`, `period`, `clock`/`time`, `status`.
- Kontrol via JavaScript dari vMix: `window.updateScoreboard({ home: { score: 10 } })`; panggil `window.resetScoreboard()` untuk kembali mengikuti data server.
- Polling otomatis ke API tiap 1 detik.

### API
- `GET /api/match` — state pertandingan aktif dalam JSON untuk overlay.

## Menjalankan

```bash
composer setup
composer dev
```

Atau manual:

```bash
composer install
cp .env.example .env
php artisan key:generate
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan serve
```

Buka `http://localhost:8000` untuk layar operator, `/settings` untuk pengaturan, dan `/overlay` untuk overlay vMix.

## Akses dari perangkat lain (WiFi)

Server mendengarkan semua interface (`SERVER_HOST=0.0.0.0`), jadi HP wasit atau komputer vMix lain di WiFi yang sama bisa membuka aplikasi.

1. Jalankan `composer dev` (atau `php artisan serve`) di komputer server.
2. Cari IPv4 komputer server dengan `ipconfig`, misalnya `192.168.110.43`.
3. Di perangkat lain buka `http://192.168.110.43:8000` (layar skor) atau `http://192.168.110.43:8000/overlay` (vMix Browser Input).
4. Jika tidak bisa dibuka, izinkan port 8000 di Windows Firewall pada profil Private. Jalankan PowerShell sebagai Administrator:

   ```powershell
   netsh advfirewall firewall add rule name="Skor VMix 8000" dir=in action=allow protocol=TCP localport=8000
   ```

Pastikan komputer server dan perangkat lain berada di jaringan WiFi yang sama, dan jaringan diberi tanda "Private" (bukan "Public") di Windows.

## Catatan

- Buat operator pertama di `/register`, lalu masuk sebelum mengubah skor. Overlay dan API hanya-baca tetap dapat dibuka tanpa login.
- `.env.example` memakai `APP_DEBUG=false` untuk keamanan. Nyalakan `APP_DEBUG=true` hanya saat mengembangkan di lokal.
- Data disimpan di SQLite (`database/database.sqlite`).
