from reportlab.lib import colors
from reportlab.lib.enums import TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import BaseDocTemplate, Frame, PageTemplate, Paragraph, Spacer, Table, TableStyle, PageBreak, KeepTogether

OUT = 'output/pdf/market-monitor-case-study.pdf'

class Report(BaseDocTemplate):
    def __init__(self, filename):
        super().__init__(filename, pagesize=A4, leftMargin=20*mm, rightMargin=20*mm, topMargin=20*mm, bottomMargin=18*mm)
        frame = Frame(self.leftMargin, self.bottomMargin, self.width, self.height, id='normal')
        self.addPageTemplates([PageTemplate(id='report', frames=frame, onPage=self.decorate)])

    def decorate(self, canvas, doc):
        canvas.saveState()
        canvas.setStrokeColor(colors.HexColor('#dbe3ee'))
        canvas.line(self.leftMargin, 13*mm, A4[0]-self.rightMargin, 13*mm)
        canvas.setFont('Helvetica', 8)
        canvas.setFillColor(colors.HexColor('#637083'))
        canvas.drawString(self.leftMargin, 8*mm, 'MARKET MONITOR  /  MINI CASE SYSDEV INTERN 2026')
        canvas.drawRightString(A4[0]-self.rightMargin, 8*mm, f'{doc.page:02d}')
        canvas.restoreState()

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name='CoverTitle', parent=styles['Title'], fontName='Helvetica-Bold', fontSize=28, leading=32, textColor=colors.HexColor('#14213d'), spaceAfter=12))
styles.add(ParagraphStyle(name='Subtitle', parent=styles['Normal'], fontSize=12, leading=18, textColor=colors.HexColor('#637083'), spaceAfter=20))
styles.add(ParagraphStyle(name='H1x', parent=styles['Heading1'], fontName='Helvetica-Bold', fontSize=18, leading=22, textColor=colors.HexColor('#1456a0'), spaceBefore=7, spaceAfter=10))
styles.add(ParagraphStyle(name='H2x', parent=styles['Heading2'], fontName='Helvetica-Bold', fontSize=12, leading=16, textColor=colors.HexColor('#14213d'), spaceBefore=8, spaceAfter=5))
styles.add(ParagraphStyle(name='Bodyx', parent=styles['BodyText'], fontSize=9.5, leading=14, textColor=colors.HexColor('#26364a'), spaceAfter=7))
styles.add(ParagraphStyle(name='Smallx', parent=styles['BodyText'], fontSize=8, leading=11, textColor=colors.HexColor('#637083'), spaceAfter=4))
styles.add(ParagraphStyle(name='Cell', parent=styles['BodyText'], fontSize=8.5, leading=11, textColor=colors.HexColor('#26364a')))
styles.add(ParagraphStyle(name='CellHead', parent=styles['BodyText'], fontName='Helvetica-Bold', fontSize=8.5, leading=11, textColor=colors.white))

def p(text, style='Bodyx'):
    return Paragraph(text, styles[style])

def table(rows, widths):
    t = Table(rows, colWidths=widths, repeatRows=1, hAlign='LEFT')
    t.setStyle(TableStyle([
        ('BACKGROUND', (0,0), (-1,0), colors.HexColor('#1456a0')),
        ('TEXTCOLOR', (0,0), (-1,0), colors.white),
        ('GRID', (0,0), (-1,-1), 0.35, colors.HexColor('#dbe3ee')),
        ('VALIGN', (0,0), (-1,-1), 'TOP'),
        ('LEFTPADDING', (0,0), (-1,-1), 7), ('RIGHTPADDING', (0,0), (-1,-1), 7),
        ('TOPPADDING', (0,0), (-1,-1), 6), ('BOTTOMPADDING', (0,0), (-1,-1), 6),
        ('ROWBACKGROUNDS', (0,1), (-1,-1), [colors.white, colors.HexColor('#f5f8fc')]),
    ]))
    return t

story = []
story += [Spacer(1, 25*mm), p('MARKET MONITOR', 'CoverTitle'), p('Rancangan dan implementasi dashboard pemantauan saham Indonesia dengan Laravel, Inertia React, dan Reverb.', 'Subtitle')]
story += [Spacer(1, 15*mm), table([[p('Ruang lingkup', 'CellHead'), p('Keputusan implementasi', 'CellHead')], [p('Masalah kasus'), p('Menampilkan harga, volume, perubahan harga, serta pembaruan real-time melalui frontend, backend, dan database.')], [p('Produk'), p('Dashboard publik dengan watchlist browser, ringkasan saham naik/turun, grafik harga, dan indikator kesegaran data.')], [p('Data'), p('Adapter live IDX melalui Zapi dengan fallback simulator ketika API key belum tersedia atau provider gagal.')], [p('Deployment'), p('Docker Compose pada VPS milik sendiri dengan MySQL, PHP-FPM, Nginx, scheduler, dan Reverb.')]], [45*mm, 115*mm]), Spacer(1, 20*mm), p('Dokumen ini menjawab tiga pertanyaan pada Mini Case - Sysdev Intern: arsitektur dan alur data, komunikasi real-time, serta deployment dan troubleshooting.', 'Bodyx'), PageBreak()]

story += [p('1. Arsitektur dan alur data', 'H1x'), p('Aplikasi menggunakan monorepo Laravel. Server-side routing, controller, validasi, dan Eloquent berada di Laravel; Inertia mengirim halaman React tanpa API gateway terpisah. Endpoint JSON digunakan untuk snapshot dan riwayat karena keduanya juga menjadi mekanisme pemulihan ketika koneksi WebSocket terputus.', 'Bodyx')]
story += [table([[p('Komponen', 'CellHead'), p('Tanggung jawab', 'CellHead'), p('Data keluar', 'CellHead')], [p('ZapiMarketData'), p('Mengambil kuotasi IDX server-side dari endpoint trading-info-daily dengan API key.'), p('Quote live dan price point')], [p('MarketSimulator'), p('Fallback lokal ketika provider belum dikonfigurasi atau gagal.'), p('Quote simulasi')], [p('MySQL'), p('Menyimpan instrumen, kuotasi terakhir, serta titik grafik 24 jam.'), p('Snapshot konsisten')], [p('MarketController'), p('Menyajikan Inertia page dan endpoint snapshot/history.'), p('HTML/Inertia atau JSON')], [p('Reverb'), p('Mengirim event quote batch ke browser yang tersambung.'), p('market / quotes.updated')], [p('React'), p('Menampilkan tabel, watchlist, ringkasan, grafik, dan status koneksi.'), p('UI responsif')]], [35*mm, 80*mm, 45*mm]), Spacer(1, 8), p('<b>Alur:</b> scheduler memanggil adapter Zapi → transaksi MySQL memperbarui quote dan price point → satu snapshot lengkap dibroadcast → React memperbarui tabel dan grafik. Jika provider gagal, quote terakhir dipertahankan dan simulator menjadi fallback.', 'Bodyx'), p('Model domain sengaja kecil: instruments menyimpan kode dan sektor; quotes menyimpan keadaan terbaru; price_points menyimpan satu titik per saham per menit. Harga adalah integer rupiah, volume adalah jumlah lembar, dan timestamp disimpan UTC lalu ditampilkan dalam WIB.', 'Bodyx'), PageBreak()]

story += [p('2. Komunikasi frontend, backend, dan real-time', 'H1x'), p('Inertia menangani navigasi halaman. Browser mengambil GET /api/market/snapshot saat membuka dashboard dan GET /api/market/{symbol}/history?range=1h|24h untuk grafik. Laravel Reverb mengirim event publik quotes.updated melalui channel market.', 'Bodyx'), table([[p('Antarmuka', 'CellHead'), p('Bentuk', 'CellHead'), p('Alasan', 'CellHead')], [p('Inertia'), p('GET / dan /saham/{symbol}'), p('Navigasi React tetap server-driven dan sederhana.')], [p('HTTP JSON'), p('Snapshot dan history'), p('Menjadi sumber kebenaran dan mekanisme resync.')], [p('Zapi IDX'), p('REST server-side dengan x-api-key'), p('API key tidak bocor ke browser dan quote dinormalisasi ke kontrak internal.')], [p('WebSocket Reverb'), p('Channel publik market'), p('Update push tanpa polling lima detik di setiap browser.')], [p('localStorage'), p('Daftar watchlist'), p('Tanpa akun dan migrasi user; sesuai scope demo publik.')]], [40*mm, 53*mm, 67*mm]), Spacer(1, 8), p('Event memakai ShouldBroadcastNow dan ShouldRescue. Database commit selesai lebih dulu; kegagalan broadcast dicatat tetapi tidak membatalkan harga yang telah tersimpan. Provider live dibatasi satu kali per interval konfigurasi agar tidak membebani API.', 'Bodyx'), p('UI memisahkan status transport dari kesegaran data. Socket tersambung tetapi provider berhenti tetap ditampilkan sebagai data tertunda setelah 15 detik. Saat provider gagal, quote terakhir tetap tersedia dan simulator menjadi fallback.', 'Bodyx'), p('Batasan yang disengaja: tanpa order book, transaksi, autentikasi, atau klaim data resmi. Lisensi provider IDX tetap harus dipastikan sebelum penggunaan komersial.', 'Bodyx'), PageBreak()]

story += [p('3. Deployment di VPS dan troubleshooting', 'H1x'), p('Docker Compose menjalankan lima service. Image aplikasi berisi vendor Composer dan hasil build Vite; service scheduler dan Reverb memakai image yang sama agar versi kode konsisten.', 'Bodyx'), table([[p('Service', 'CellHead'), p('Port', 'CellHead'), p('Peran', 'CellHead')], [p('web'), p('80 (di belakang TLS reverse proxy)'), p('Nginx melayani public/ dan meneruskan PHP serta upgrade WebSocket.')], [p('app'), p('9000 internal'), p('PHP-FPM dan Laravel.')], [p('db'), p('3306 internal'), p('MySQL dengan volume persisten.')], [p('scheduler'), p('internal'), p('php artisan schedule:work; tick lima detik.')], [p('reverb'), p('8080 internal'), p('WebSocket; hanya diakses Nginx dan Laravel.')]], [30*mm, 48*mm, 82*mm]), Spacer(1, 8), p('<b>Rilis:</b> siapkan DNS dan port 80/443 → isi APP_KEY dan secret Reverb melalui environment runtime → docker compose up -d --build → migrate --force → seed saat instalasi awal → cek /up, snapshot, dan koneksi WebSocket. Database serta sertifikat berada di volume persisten; jangan menghapus volume ketika rollback image.', 'Bodyx'), p('<b>Troubleshooting:</b> mulai dari DNS dan firewall, lanjut ke docker compose ps serta log web/app/db/scheduler/reverb, lalu cek /up. Jika halaman gagal, periksa PHP-FPM dan APP_KEY. Jika data kosong, jalankan migrate dan seed. Jika data berhenti, cek scheduler serta perintah market:tick. Jika WebSocket gagal, periksa VITE_REVERB_HOST, TLS, reverse proxy /app/, allowed origin, dan log Reverb.', 'Bodyx'), p('Kondisi produksi menggunakan APP_DEBUG=false, database dan Reverb tidak diekspos langsung, serta restart policy. Untuk horizontal scaling, Reverb dan cache dapat dipindahkan ke Redis; itu sengaja belum ditambahkan karena satu VPS cukup untuk demo.', 'Bodyx'), PageBreak()]

story += [p('4. Verifikasi dan sumber', 'H1x'), p('Verifikasi lokal yang dilakukan:', 'Bodyx'), table([[p('Pemeriksaan', 'CellHead'), p('Hasil', 'CellHead')], [p('php artisan migrate:fresh --seed', 'Cell'), p('Berhasil membuat tabel domain dan seed 12 saham dengan riwayat 24 jam.')], [p('php artisan market:tick', 'Cell'), p('Berhasil memperbarui kuotasi dan membuat batch broadcast.')], [p('Live Zapi tick', 'Cell'), p('Request bulk berhasil; snapshot menghasilkan source=live dan 12 quote IDX.')], [p('ZapiMarketDataTest', 'Cell'), p('Berhasil menormalisasi close, previous, volume, dan API key menggunakan Http::fake.')], [p('php artisan test --compact', 'Cell'), p('5 tests passed, 15 assertions.')], [p('npm run build', 'Cell'), p('Berhasil menghasilkan manifest dan aset production Vite.')], [p('vendor/bin/pint --dirty --format agent', 'Cell'), p('Berhasil memformat file PHP yang diubah.')]], [65*mm, 95*mm]), Spacer(1, 8), p('Sumber teknis:', 'H2x'), p('1. IDX Data Services: https://www.idx.id/en/products/idx-data-services/ (lisensi direct/redistributor, produk real-time, delayed, dan EoD).', 'Smallx'), p('2. Zapi IDX API: https://zpi.web.id/api/finance/idx (stock-summary dan stock-history; API key server-side).', 'Smallx'), p('3. Laravel HTTP Client: https://laravel.com/docs/13.x/http-client (timeout, connectTimeout, retry, dan error handling).', 'Smallx'), p('4. Laravel Broadcasting, Reverb, dan Scheduling: https://laravel.com/docs/13.x/broadcasting, https://laravel.com/docs/13.x/reverb, https://laravel.com/docs/13.x/scheduling', 'Smallx'), p('5. Laravel Deployment dan Docker Compose: https://laravel.com/docs/13.x/deployment dan https://docs.docker.com/compose/how-tos/production/', 'Smallx'), Spacer(1, 10), p('Kesimpulan desain: adapter provider menjaga kontrak dashboard tetap stabil, live quote tersedia saat key dan lisensi siap, sedangkan simulator menjaga demo tetap bisa berjalan saat API eksternal belum dikonfigurasi.', 'Bodyx')]

Report(OUT).build(story)
