# Market Monitor

Market Monitor adalah dashboard publik untuk memantau data saham BEI. Aplikasi mendukung data live melalui Zapi dan fallback simulator untuk local development atau saat provider eksternal belum dikonfigurasi. Harga, perubahan, volume, dan riwayat grafik tersedia melalui HTTP serta Laravel Reverb.

## Menjalankan lokal

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Jalankan simulator pada terminal lain dengan `php artisan schedule:work`. Buka `http://localhost:8000`. Endpoint pemeriksaan aplikasi ada di `/up`; snapshot pasar ada di `/api/market/snapshot`.

## Mengaktifkan data live IDX

Provider live yang terintegrasi adalah Zapi IDX. Isi environment berikut dengan API key server-side:

```dotenv
MARKET_DATA_PROVIDER=zapi
ZPI_API_KEY=your-server-side-key
ZPI_BASE_URL=https://api.zpi.web.id/v1/finance:idx
MARKET_DATA_POLL_SECONDS=900
MARKET_DATA_ONLY_OPEN_SESSION=true
```

Tanpa `ZPI_API_KEY`, aplikasi otomatis memakai simulator. Key tidak boleh dimasukkan ke React atau variable `VITE_*`. Endpoint bulk Zapi yang digunakan adalah `stock-summary`, sehingga satu request mengambil seluruh daftar saham lokal per interval. Default interval 15 menit hanya pada jam perdagangan IDX agar free tier 600 request/bulan tidak cepat habis. Paket berbayar dapat memakai interval lebih pendek. Provider gagal atau timeout tidak menghapus data terakhir yang tersimpan.

## Docker Compose

Salin `.env.example` ke environment produksi, isi `APP_KEY` dan `REVERB_APP_SECRET`, lalu jalankan:

```bash
docker compose up -d --build
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed --force
```

Compose menjalankan PHP-FPM, Nginx, MySQL, scheduler, dan Reverb. Port database serta Reverb tidak dipublikasikan ke internet. Untuk domain produksi, terminasi TLS di reverse proxy VPS dan isi `APP_URL`, `VITE_REVERB_HOST`, `VITE_REVERB_SCHEME=https`, serta `VITE_REVERB_PORT=443` sebelum build aset.

## Catatan produk

Data live tetap tunduk pada lisensi dan ketentuan provider. Watchlist disimpan di browser tanpa akun. Jika sumber data BEI resmi langsung ditambahkan kemudian, penggantian dilakukan pada adapter provider tanpa mengubah format API atau komponen dashboard.

## Troubleshooting singkat

1. Periksa DNS dan port 80/443 di VPS.
2. Jalankan `docker compose ps` dan `docker compose logs --tail=100 app web scheduler reverb db`.
3. Cek `/up`, lalu `docker compose exec app php artisan route:list`.
4. Jika harga berhenti, cek scheduler dan `php artisan market:tick`.
5. Jika browser tidak menerima update, cek URL Reverb publik, sertifikat TLS, dan log Reverb.
