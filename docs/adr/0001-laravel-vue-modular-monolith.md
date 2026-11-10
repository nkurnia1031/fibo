# ADR-0001: Laravel API, Vue admin, dan MySQL

- Status: Accepted
- Tanggal: 2026-11-10

## Konteks

MVP membutuhkan panel staf internal, backend untuk konten dan penilaian, serta API untuk klien siswa eksternal. Pilihan stack telah ditetapkan: MySQL, Laravel, dan Vue. Repo belum memiliki aplikasi.

## Keputusan

- Gunakan Laravel 13 sebagai modular monolith dan pemilik API REST JSON berversi.
- Gunakan Vue 3 + Vite untuk panel admin; layani panel dan API pada origin yang sama di deployment produk.
- Gunakan MySQL 8.4 LTS.
- Gunakan akun staf yang terautentikasi sesi. Identitas murid tetap pada identity provider eksternal dan dipetakan melalui issuer/subject.
- Bentuk pembayaran bergantung gateway di balik adapter, dimulai dengan Midtrans.
- Demo GitHub Pages memakai fixture Vue tanpa backend. Backend produksi tidak ditiru oleh persistence browser.

## Konsekuensi

- Satu aplikasi Laravel menjaga transaksi lintas soal, paket, percobaan, dan pembayaran tanpa layanan terpisah.
- Vue dapat berkembang sebagai panel independen dan klien API yang sama dapat dipakai aplikasi eksternal.
- Perlu aturan CORS/origin, validasi token eksternal, dan perlindungan callback gateway.
- Demo Pages tidak dapat memverifikasi keamanan, transaksi, API, atau koneksi MySQL; itu diselesaikan di tiket implementasi.
- Gunakan queue database dan object storage privat di awal; tambahkan Redis atau infrastruktur lain hanya bila kebutuhan operasional terbukti.

## Sumber

- [Laravel 13 release notes](https://laravel.com/framework/docs/releases)
- [Vue FAQ](https://vuejs.org/about/faq)
- [MySQL 8.4 LTS release model](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html)
