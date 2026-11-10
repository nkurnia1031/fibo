# Fibo · Admin TKA Matematika

Demo panel admin untuk alur MVP TKA Matematika.

## Demo

Buka [demo interaktif](https://nkurnia1031.github.io/fibo/). Pilih tata letak dengan tombol di tengah bawah atau tombol panah kiri/kanan. Demo memakai data contoh sementara di browser; tidak ada login, pembayaran, atau data murid nyata.

## Spek dan urutan pembangunan

- [Spek MVP Admin TKA Matematika](docs/specs/tka-math-admin-mvp.md)
- [Keputusan arsitektur](docs/adr/0001-laravel-vue-modular-monolith.md)
- [Tiket pembangunan di GitHub](https://github.com/nkurnia1031/fibo/issues/1)

Stack yang disiapkan: Laravel 13 API, Vue 3 admin, dan MySQL 8.4 LTS. Backend dan MySQL belum dipakai oleh demo Pages.

## Jalankan panel Vue secara lokal

```sh
cd apps/admin
npm ci
npm run dev
```

Tata letak dapat dipilih dengan `?variant=desk`, `?variant=flow`, atau `?variant=focus`.

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

Fondasi Laravel belum berisi alur domain MVP. Pembangunannya dipecah dalam tiket yang ditautkan dari spek.
