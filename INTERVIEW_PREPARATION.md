# Market Monitor - Interview Preparation

Dokumen ini membantu mempersiapkan pembahasan Market Monitor pada interview user untuk posisi System Developer Intern di IDXSTI.

## 1. Opening pitch

Gunakan penjelasan singkat berikut untuk membuka sesi:

> Saya membangun Market Monitor sebagai dashboard pasar saham Indonesia untuk menampilkan harga, perubahan, volume, bid, ask, top movers, dan grafik IHSG. Aplikasi ini menggunakan Laravel sebagai backend, Inertia React sebagai frontend, MySQL untuk menyimpan quote dan price point, Zapi sebagai provider data IDX, serta Laravel Reverb untuk mengirim pembaruan ke browser. Aplikasi dideploy menggunakan Docker Compose pada VPS dengan Caddy sebagai reverse proxy HTTPS.
>
> Fokus utama saya adalah membuat alur data yang jelas, menjaga API key tetap di server, menggunakan satu sumber data yang konsisten, dan menyediakan HTTP resync apabila koneksi WebSocket terputus.

Demo production: https://idxsti.bangpens.my.id

Repository: https://github.com/afenmarbun/market-monitor

## 2. Arsitektur dan alur data

Jelaskan alur berikut secara berurutan:

```text
Zapi HTTP API
    -> Laravel Scheduler
    -> ZapiMarketData
    -> Validasi dan normalisasi
    -> MySQL
    -> Laravel API / Inertia
    -> React dashboard
```

Jalur realtime berjalan melalui alur berikut:

```text
Quote berhasil disimpan
    -> QuotesUpdated event
    -> Laravel Reverb
    -> Laravel Echo
    -> React memperbarui state
```

Tanggung jawab komponen utama:

- `ZapiMarketData` menangani request ke Zapi, validasi response, normalisasi field, rate limiting, dan status provider.
- `MarketSnapshot` membentuk kontrak data yang stabil untuk frontend.
- `instruments` menyimpan katalog saham seperti symbol, name, dan sector.
- `quotes` menyimpan kondisi harga live terakhir setiap saham.
- `price_points` menyimpan titik historis untuk grafik.
- `QuotesUpdated` mengirim snapshot terbaru melalui channel publik `market`.
- React mengelola tabel, chart, pencarian, tab top movers, dan status koneksi.

Kalimat penting yang dapat digunakan:

> Backend menjadi source of truth. Frontend tidak memanggil Zapi secara langsung; frontend hanya berkomunikasi dengan kontrak data yang disediakan Laravel.

## 3. Penjelasan Zapi dan realtime

Ini adalah bagian yang perlu dijelaskan dengan jujur. Zapi menggunakan HTTP request-response melalui endpoint seperti `stock-summary` dan `index-summary`, bukan koneksi WebSocket atau streaming feed. Karena itu data dari Zapi bersifat near real-time atau periodically refreshed, bukan tick-by-tick real-time.

Laravel Reverb tetap berguna karena mengirim hasil polling dari backend ke browser secara efisien. Namun WebSocket di sisi frontend tidak dapat menjadikan sumber data Zapi sebagai streaming realtime. Jika polling diset 15 menit, data dapat tertinggal sampai sekitar 15 menit ditambah waktu request dan normalisasi.

Kalimat yang disarankan:

> Untuk kebutuhan dashboard informasi, HTTP polling Zapi sudah cukup. Namun untuk trading, order execution, atau monitoring low-latency, solusi ini belum layak disebut real-time murni. Pengembangan berikutnya adalah menambahkan provider IDX atau market data provider berlisensi yang menyediakan streaming feed melalui WebSocket atau koneksi khusus market data.

Dalam implementasi saat ini, scheduler menjalankan `market:tick` setiap lima detik, tetapi `ZapiMarketData` membatasi request provider berdasarkan `MARKET_DATA_POLL_SECONDS`, yang default-nya 900 detik. Dengan cara ini banyak browser tidak membuat request Zapi secara langsung dan quota provider lebih terkontrol.

Zapi tetap dapat dipertahankan untuk snapshot, historical data, backfill setelah reconnect, dan rekonsiliasi data jika suatu saat streaming provider ditambahkan.

## 4. Keputusan UI/UX

Jelaskan alasan susunan dashboard:

- IHSG dibuat paling besar karena merupakan indikator pasar utama.
- Chart IHSG memiliki pilihan `1D`, `1W`, `1M`, dan `1Y`.
- Nilai indeks seperti IHSG mempertahankan decimal precision, contohnya `6619.673` tidak dibulatkan menjadi `6620`.
- Top Movers berada di sisi kanan agar user langsung melihat saham dengan perubahan terbesar.
- Tab `Gainer`, `Loser`, dan `Volume` menghindari terlalu banyak card pada halaman.
- Tabel berada di bawah karena berisi data detail yang lebih padat.
- Search digunakan untuk menemukan saham dengan cepat.
- Badge status membedakan `Live`, `Data delayed`, dan `Connecting`.
- UI menggunakan light mode dengan komponen yang konsisten dan responsive layout.

Jika ditanya mengapa tidak menambahkan fitur yang lebih kompleks:

> Saya membatasi scope pada market monitoring agar alur full-stack dan kualitas implementasinya jelas. Order execution, portfolio, authentication, alert, dan watchlist server-side dapat ditambahkan setelah kebutuhan user serta sumber data realtime yang sesuai sudah tersedia.

## 5. Urutan demo lima menit

Gunakan urutan berikut ketika melakukan live demo:

1. Buka https://idxsti.bangpens.my.id.
2. Jelaskan header, logo IDXSTI, nama aplikasi, dan status data.
3. Tunjukkan card IHSG dan pilih tab `1M`.
4. Jelaskan bahwa nilai indeks mempertahankan angka desimal.
5. Tunjukkan Top Movers, lalu pindah antara `Gainer`, `Loser`, dan `Volume`.
6. Gunakan search untuk mencari saham tertentu.
7. Tunjukkan kolom tabel seperti bid, ask, value, lot, frequency, previous close, open, high, dan low.
8. Jelaskan bahwa browser menerima data dari backend, bukan dari Zapi secara langsung.
9. Jika diperlukan, buka DevTools untuk menunjukkan endpoint snapshot/status atau koneksi WebSocket.

Siapkan screenshot dashboard sebagai backup jika koneksi internet atau provider Zapi sedang bermasalah.

## 6. Pertanyaan teknis yang mungkin muncul

### Mengapa menggunakan Laravel dan Inertia React?

Laravel memberikan struktur backend untuk routing, database, scheduler, HTTP client, testing, dan broadcasting. Inertia memungkinkan penggunaan routing Laravel bersama React tanpa membangun API gateway dan sistem routing SPA yang terpisah. React tetap memberikan UI interaktif dengan deployment yang sederhana.

### Mengapa API key tidak dipanggil dari React?

API key tidak boleh terekspos di browser. Backend bertindak sebagai adapter sekaligus boundary keamanan. Backend juga menangani rate limiting, normalisasi, penyimpanan, dan error handling sebelum data dikirim ke frontend.

### Mengapa menggunakan WebSocket jika Zapi memakai HTTP?

WebSocket digunakan untuk jalur backend ke browser. Setelah Laravel memperoleh snapshot baru dari Zapi, Reverb mengirimkannya ke browser yang sedang tersambung. Saya tidak mengklaim Zapi sebagai realtime murni karena upstream-nya tetap HTTP polling.

### Bagaimana jika Zapi gagal?

Backend mencatat error dan mempertahankan data live terakhir. Frontend menampilkan status delayed ketika data melewati freshness window. Aplikasi tidak membuat quote random atau data dummy.

### Bagaimana menjaga konsistensi data?

Quote dan price point ditulis dalam transaksi database. Event broadcast dikirim setelah proses penyimpanan selesai sehingga client menerima snapshot yang sudah commit. Setiap data memiliki `source` dan `quoted_at` untuk melacak asal dan umur data.

### Mengapa harga saham integer tetapi IHSG float?

Harga saham IDX menggunakan tick size rupiah sehingga disimpan sebagai integer. Nilai indeks seperti IHSG dapat memiliki pecahan desimal, sehingga normalizer indeks mempertahankan tipe float.

### Bagaimana deployment dilakukan?

Docker Compose menjalankan PHP-FPM, Nginx, MySQL, scheduler, dan Reverb. Caddy di host menangani TLS dan meneruskan traffic ke web container. Database dan Reverb tidak diekspos langsung ke internet.

## 7. Cerita troubleshooting yang perlu disiapkan

### Masalah

Badge berubah menjadi `Data delayed` ketika jam perdagangan sudah dimulai.

### Investigasi

Endpoint status menunjukkan `market_open=true`, tetapi `last_success_at` dan `snapshot_as_of` sudah terlalu lama. Log scheduler terlihat berjalan, tetapi server mengalami banyak zombie process dan error `Resource temporarily unavailable`.

### Akar masalah

Scheduler menjalankan command dengan `runInBackground()` setiap interval. Child process tidak direap dengan baik di container sehingga terus menumpuk dan akhirnya menghabiskan resource process VPS.

### Perbaikan

`runInBackground()` dihapus. `market:tick` sekarang berjalan secara synchronous dengan `withoutOverlapping`, sehingga satu proses selesai dan direap sebelum tick berikutnya.

### Verifikasi

Scheduler kembali menjalankan tick setiap lima detik, quote berhasil diperbarui, snapshot menjadi fresh, dan badge kembali menjadi `Live`.

Cerita ini menunjukkan bahwa saya tidak hanya membangun fitur, tetapi juga mampu mendiagnosis masalah production dari symptom sampai root cause.

## 8. Pengembangan lanjutan

Pengembangan paling penting adalah menambahkan provider streaming. Zapi HTTP cocok untuk snapshot dan polling berkala, tetapi tidak cocok untuk kebutuhan low-latency. Provider IDX atau market data provider berlisensi yang menyediakan WebSocket atau feed khusus dapat diintegrasikan melalui adapter yang sama.

Pengembangan lain yang dapat dilakukan:

- Menampilkan freshness indicator seperti `Updated 30 seconds ago`.
- Membedakan status `Live`, `Delayed`, `Stale`, dan `Disconnected`.
- Menambahkan monitoring dan alert untuk provider failure.
- Memindahkan cache dan broadcast coordination ke Redis jika traffic meningkat.
- Menambahkan queue worker untuk ingestion.
- Menambahkan reconnect dan heartbeat monitoring untuk WebSocket.
- Menyimpan `provider_timestamp`, `received_at`, dan `broadcasted_at`.
- Menambahkan authentication dan role-based access jika dashboard tidak lagi public.
- Menambahkan observability seperti structured logging dan metrics.

## 9. Checklist sebelum interview

- [ ] Bisa menjelaskan arsitektur tanpa membaca catatan.
- [ ] Bisa menjelaskan alur Zapi -> Laravel -> MySQL -> React.
- [ ] Bisa membedakan HTTP polling dan WebSocket streaming.
- [ ] Bisa menjelaskan mengapa API key harus server-side.
- [ ] Bisa melakukan demo dalam waktu sekitar lima menit.
- [ ] Bisa menjelaskan tabel `instruments`, `quotes`, dan `price_points`.
- [ ] Bisa menjelaskan deployment Docker Compose di VPS.
- [ ] Bisa menceritakan troubleshooting zombie process dan scheduler.
- [ ] Bisa menyebutkan keterbatasan saat ini tanpa menutupi kekurangannya.
- [ ] Bisa menjelaskan roadmap menuju true real-time.

## 10. Hal yang sebaiknya tidak diklaim

Hindari klaim berikut:

- “Aplikasi ini realtime sepenuhnya.”
- “Zapi mengirim data melalui WebSocket.”
- “Data selalu diperbarui setiap detik.”
- “API key aman karena hanya disembunyikan di frontend.”
- “Jika Zapi gagal, aplikasi menggunakan data random.”

Gunakan istilah yang lebih akurat:

- Near real-time.
- HTTP polling provider.
- WebSocket delivery dari backend ke browser.
- Last known live snapshot.
- No dummy data fallback.
- Streaming provider sebagai roadmap untuk true real-time.

## 11. Workflow Engineering dari Awal sampai Selesai

Ketika menerima mini case ini, saya memulai dengan memahami problem bisnis dan kebutuhan user terlebih dahulu, bukan langsung membuat UI. Saya mengidentifikasi bahwa aplikasi perlu menampilkan informasi pasar seperti harga, perubahan, volume, bid, dan ask. Dari situ saya membatasi scope menjadi dashboard monitoring pasar yang sederhana, mudah digunakan, dan dapat dideploy ke VPS tanpa menambahkan kompleksitas seperti order execution, portfolio management, atau authentication yang belum dibutuhkan.

Setelah memahami scope, saya menentukan arsitektur teknis. Saya menggunakan Laravel sebagai backend karena menyediakan routing, controller, Eloquent ORM, scheduler, HTTP client, testing, dan broadcasting. Untuk frontend saya menggunakan Inertia React agar dapat membangun UI interaktif dengan React tetapi tetap menggunakan routing dan data flow dari Laravel. MySQL digunakan untuk menyimpan instrumen saham, quote terakhir, dan price point historis. Untuk deployment, saya menggunakan Docker Compose yang menjalankan PHP-FPM, Nginx, MySQL, scheduler, dan Laravel Reverb.

Tahap berikutnya adalah membuat model domain dan kontrak data. Saya memisahkan data menjadi tiga bagian utama, yaitu `instruments` untuk katalog saham, `quotes` untuk data harga terbaru, dan `price_points` untuk data historis chart. Saya juga menentukan format data internal agar frontend tidak bergantung langsung pada struktur response dari Zapi. Harga saham disimpan sebagai integer karena mengikuti tick size rupiah, sedangkan nilai indeks seperti IHSG dipertahankan sebagai decimal karena Zapi dapat mengirim nilai seperti `6619.673`.

Untuk integrasi data, saya membuat service `ZapiMarketData` sebagai adapter provider. API key Zapi hanya digunakan di backend dan tidak pernah dimasukkan ke React atau variable frontend. Service tersebut menangani request HTTP, timeout, retry, rate limiting, validasi response, normalisasi field, penyimpanan ke database, dan pengambilan riwayat indeks. Saya juga menambahkan status provider agar aplikasi dapat mengetahui apakah provider sedang aktif, market sedang buka, kapan polling terakhir berhasil, dan apakah terdapat error.

Saya kemudian membangun API dan alur realtime. Laravel menyediakan endpoint untuk snapshot market, data indeks, status provider, dan history saham. Setelah data berhasil disimpan, backend mengirim event `quotes.updated` melalui Laravel Reverb pada channel `market`. React menggunakan Laravel Echo untuk menerima event tersebut dan memperbarui state dashboard. Saya juga tetap menyediakan HTTP resync berkala karena WebSocket dapat terputus akibat jaringan, browser sleep, reverse proxy, atau proses deployment.

Dalam menjelaskan realtime, saya menggunakan istilah yang akurat. Zapi yang digunakan adalah HTTP polling provider, bukan streaming WebSocket. Reverb hanya membuat pengiriman data dari backend ke browser menjadi realtime setelah backend mendapatkan snapshot baru dari Zapi. Karena itu aplikasi ini lebih tepat disebut near real-time atau periodically refreshed dashboard. Jika kebutuhan aplikasinya berubah menjadi trading atau low-latency monitoring, saya akan menambahkan provider IDX atau market data provider berlisensi yang menyediakan streaming feed melalui WebSocket atau koneksi market data khusus.

Setelah backend berjalan, saya membangun UI berdasarkan prioritas informasi. Card IHSG dibuat paling besar karena merupakan indikator utama pasar. Top Movers ditempatkan di sisi kanan agar user dapat langsung melihat saham dengan perubahan terbesar. Tabel saham diletakkan di bawah karena berisi data yang lebih detail. Saya menambahkan tab untuk Gainer, Loser, dan Volume agar informasi tetap padat tanpa menambah terlalu banyak card. Saya juga menambahkan search, chart 30 hari IHSG, status badge, logo IDXSTI, footer, dan layout light mode yang konsisten.

Saya melakukan iterasi UI berdasarkan hasil pengamatan langsung pada browser. Beberapa elemen yang kurang relevan seperti watchlist, detail saham, index board, dan card yang terlalu redundan dihapus agar fokus dashboard lebih jelas. Komponen table juga disesuaikan untuk menampilkan field market yang lebih sesuai seperti bid, ask, value, lot, frequency, previous close, open, high, dan low. Saya menggunakan komponen UI berbasis COSS/shadcn-compatible dan custom market chart agar tampilan lebih konsisten dan profesional.

Sebelum deployment, saya melakukan validasi berlapis. Saya menjalankan PHPUnit feature test untuk market API dan normalisasi Zapi, TypeScript check untuk mendeteksi masalah pada frontend, Vite production build untuk memastikan asset dapat dibuat, Laravel Pint untuk formatting PHP, serta Docker Compose config validation. Saya juga menambahkan pengujian agar aplikasi tidak membuat quote dummy ketika belum ada data live. Dengan begitu, jika Zapi belum tersedia, UI menampilkan status data unavailable, bukan angka random yang dapat menyesatkan user.

Untuk deployment, saya menggunakan VPS sendiri dengan Docker Compose. Image frontend dibangun menggunakan Vite, aplikasi Laravel berjalan pada PHP-FPM, Nginx melayani asset dan meneruskan request PHP, MySQL menggunakan persistent volume, scheduler menjalankan `market:tick`, dan Reverb menangani WebSocket. Caddy pada host VPS digunakan sebagai reverse proxy yang menangani HTTPS dan meneruskan traffic ke web container. Setelah container berjalan, saya menjalankan migration, seed katalog instrumen, optimize configuration, dan memeriksa endpoint `/up`, market status, snapshot, asset CSS/JavaScript, serta WebSocket handshake.

Pada tahap production, saya melakukan troubleshooting berdasarkan data dan log, bukan hanya menebak. Saat muncul mixed content, saya menemukan bahwa Laravel belum mempercayai header HTTPS dari reverse proxy sehingga asset dibuat menggunakan HTTP. Saya memperbaiki trusted proxy configuration dan membangun ulang asset. Saat WebSocket mendapatkan 404, saya menguji handshake secara langsung dan menemukan path `proxy_pass` Nginx melakukan rewrite yang salah. Saya mengubah proxy agar path `/app/...` diteruskan utuh ke Reverb. Saya juga menemukan service Laravel sebelumnya menggunakan Docker stage Nginx, bukan PHP runtime, sehingga `php` tidak tersedia pada scheduler dan Reverb.

Masalah paling penting yang ditemukan adalah scheduler menggunakan `runInBackground()` pada interval lima detik. Di dalam container, child process tidak direap dengan benar dan akhirnya menumpuk menjadi ribuan zombie process. Akibatnya server mengalami error `Resource temporarily unavailable`, proses polling Zapi berhenti, dan badge berubah menjadi `Data delayed`. Saya memperbaikinya dengan menghapus `runInBackground()` dan menjalankan `market:tick` secara synchronous menggunakan `withoutOverlapping`. Setelah redeploy, scheduler berjalan stabil dan freshness check kembali berhasil.

Setelah aplikasi selesai, saya melakukan final verification dari sisi user dan server. Saya memastikan endpoint market mengembalikan `source=live`, jumlah quote sesuai, timestamp snapshot masih baru, asset production merespons HTTP 200 melalui HTTPS, dan WebSocket mengembalikan `101 Switching Protocols`. Saya juga memastikan tidak ada data simulasi yang tersisa di database dan nilai indeks seperti IHSG tetap mempertahankan decimal precision.

Bagi saya, workflow ini menunjukkan bahwa engineering bukan hanya menulis fitur, tetapi juga memahami requirement, membuat batasan scope, memilih trade-off yang sesuai, menjaga keamanan data, menulis test, melakukan deployment, membaca log, menemukan root cause, dan memverifikasi hasil akhir. Keterbatasan Zapi sebagai HTTP polling provider juga saya sampaikan secara terbuka, bersama rencana pengembangan menuju streaming market data yang benar-benar realtime.
