# Fibo · Persiapan TKA Matematika

Website latihan TKA Matematika dengan laman murid sebagai pintu utama dan panel staf untuk mengelola konten.

## Demo

Buka [demo laman murid](https://nkurnia1031.github.io/fibo/). Kamu dapat mencoba pendaftaran, belanja paket dengan Poin, dan meminta Top-up manual menggunakan data simulasi di browser. Tidak ada akun atau pembayaran nyata. [Panel admin produk](https://fibo.naufix.space/admin) memerlukan akses staf.

Prototype laman murid mencakup simulasi akun, Dompet poin, pembelian paket, dan pengajuan Top-up manual. Editor soal admin menggunakan [LaTeX.js](https://latex.js.org/) di panel produk; data prototype murid hanya tersimpan selama halaman terbuka.

## Spek dan urutan pembangunan

- [Spek MVP Admin TKA Matematika](docs/specs/tka-math-admin-mvp.md)
- [Keputusan arsitektur](docs/adr/0001-laravel-vue-modular-monolith.md)
- [Tiket pembangunan di GitHub](https://github.com/nkurnia1031/fibo/issues/1)

Stack yang disiapkan: Laravel 13 API, Vue 3 admin, dan MySQL 8.4 LTS. Backend dan MySQL belum dipakai oleh demo Pages.

## Jalankan panel admin Vue secara lokal

```sh
cd apps/admin
npm ci
npm run dev
```

Panel admin memakai Laravel API pada origin yang sama; untuk demo murid gunakan tautan GitHub Pages di atas.

## Mulai backend lokal

Gunakan PHP 8.3+, Composer 2.7+, ekstensi PHP yang dibutuhkan Laravel/MySQL, Node.js 22.12+, dan Docker Compose.

```sh
docker compose up -d mysql
cd apps/api
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve
```

Backend admin dan alur penulisan soal sudah tersedia. Pendaftaran murid mandiri, Dompet Poin Fibo, Top-up manual, dan pembelian akses sedang dirinci dalam tiket yang ditautkan dari spek.
