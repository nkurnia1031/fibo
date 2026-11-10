# Spek MVP Admin TKA Matematika

Status: **baseline implementasi yang disetujui untuk memulai demo dan tiket pembangunan**. Pilihan dapat disesuaikan setelah demo ditinjau.

## Tujuan

Membangun panel staf dan backend untuk mengelola konten latihan TKA Matematika, paket tes, identitas murid, langganan, pembayaran, dan laporan. Demo pertama memperlihatkan alur kerja admin dan memakai data contoh di browser.

## Pengguna dan batas produk

- Pengguna utama adalah Owner, Admin, Penulis Konten, Reviewer, Finance, dan Support.
- Klien siswa berada di luar MVP. Backend menyediakan API agar klien eksternal dapat mengambil paket, menyimpan jawaban, dan mengirim percobaan.
- Situs pemasaran, aplikasi siswa, pembayaran produksi, dan analitik tingkat lanjut berada di luar demo pertama.
- Launch awal: SMP/MTs, kelas sasaran 9. Katalog Jenjang dapat ditambah; Jenjang yang sudah dipakai diarsipkan, bukan dihapus.

## Keputusan konten

Kerangka SMP/MTs memuat elemen Bilangan, Aljabar, Geometri dan Pengukuran, serta Data dan Peluang. Tiga level kognitifnya adalah Pengetahuan dan Pemahaman, Aplikasi, dan Penalaran. Bentuk respons resminya adalah pilihan ganda satu jawaban, pilihan ganda kompleks MCMA, dan pilihan ganda kompleks kategori. Ketiganya berasal dari kerangka resmi; soal grup dengan stimulus bersama merupakan dimensi terpisah dari bentuk respons. [Perka BSKAP 047/H/AN/2025](https://pusmendik.kemendikdasmen.go.id/tka/page/download_file/700298_47)

Keputusan produk:

- Setiap revisi soal wajib memiliki pertanyaan, kunci jawaban, pembahasan, level kognitif, dan satu subelemen utama. Miskonsepsi umum dan tag tambahan opsional.
- Penulis menyusun konten dengan notasi LaTeX dan unggahan gambar. Demo menggunakan LaTeX.js 0.12.6 untuk merender sumber matematika pada editor, pratinjau, dan uji coba; sumber LaTeX tetap disimpan apa adanya. Implementasi menerima subset sintaks/makro yang didukung LaTeX.js; paket TeX arbitrer tidak dieksekusi. Validasi tipe dan ukuran gambar, sanitasi konten, serta penyimpanan privat wajib pada backend. [Dokumentasi LaTeX.js](https://latex.js.org/usage.html)
- Stimulus grup disimpan terpisah dan ditautkan ke soal-soal yang menggunakannya.
- Tidak ada format esai pada MVP karena tiga bentuk respons di atas adalah bentuk yang ditetapkan kerangka untuk jenjang ini. Penambahan format lain memerlukan keputusan produk tersendiri.
- Hanya satu jawaban penuh benar memperoleh 1 poin. MCMA harus sama persis dengan himpunan kunci; kategori harus benar di semua baris. Salah atau kosong mendapat 0; tidak ada kredit parsial.

## Alur dan izin staf

### Soal

`Draf → Pemeriksaan → Disetujui → Terbit → Arsip`. Reviewer dapat meminta perbaikan, yang mengembalikan soal ke Draf. Penulis tidak dapat menyetujui soalnya sendiri. Reviewer menyetujui; Admin atau Owner menerbitkan dan mengarsipkan. Revisi yang sudah terbit tidak diedit; perubahan membuat revisi baru. Arsip mencegah pemakaian baru dan tetap menjaga riwayat.

CRUD bank soal pada panel: buat, baca/pratinjau, edit dan hapus draf; uji coba jawaban dengan skor penuh 1 atau 0; kirim ke pemeriksaan; dan buat revisi untuk soal terbit. Revisi terbit dan arsip tidak diubah atau dihapus permanen.

### Paket tes

- Admin/Owner membuat paket dengan memilih dan mengurutkan soal secara manual.
- Admin/Owner dapat membuat, membaca, mengedit, menerbitkan, mengarsipkan, dan menghapus paket yang masih Draf. Paket terbit tidak diedit langsung; perubahan dibuat sebagai versi baru. Paket yang memiliki riwayat tidak dihapus permanen.
- Satu paket memakai satu Jenjang dan satu versi Kerangka TKA.
- Setiap butir paket menunjuk ke revisi soal tertentu. Paket terbit tidak berubah diam-diam; perubahan isi berarti menerbitkan versi paket baru.
- Timer opsional ditentukan per paket. Urutan yang ditampilkan tetap mengikuti urutan manual pada MVP.

### Percobaan

- Timer dimulai saat murid memulai percobaan. Murid bisa melanjutkan dengan waktu tersisa; waktu tidak direset.
- Jawaban otomatis tersimpan setiap perubahan (debounce singkat) dan saat berpindah soal. MVP membutuhkan koneksi aktif; sinkronisasi offline belum termasuk.
- Submit dapat dilakukan murid kapan saja atau otomatis ketika timer habis.
- Setiap retake membuat Percobaan baru dan mengurangi kuota jika Hak akses masih tersedia.
- Simpan respons dan skor per butir, jumlah poin, serta persentase. Item salah/kosong bernilai nol.

## Identitas, peran, dan audit

Autentikasi staf dikelola aplikasi. Enam peran bawaan: Owner, Admin, Penulis Konten, Reviewer, Finance, Support. Hak akses minimum mengikuti tabel:

| Kemampuan | Owner | Admin | Penulis | Reviewer | Finance | Support |
| --- | --- | --- | --- | --- | --- | --- |
| Menetapkan peran | Ya | Tidak | Tidak | Tidak | Tidak | Tidak |
| Mengelola Jenjang/kerangka | Ya | Ya | Baca | Baca | Tidak | Tidak |
| Membuat/mengedit draf soal | Ya | Ya | Ya | Ya | Tidak | Tidak |
| Meminta revisi/menyetujui soal | Ya | Ya | Tidak untuk soal sendiri | Ya | Tidak | Tidak |
| Menerbitkan/mengarsipkan soal | Ya | Ya | Tidak | Tidak | Tidak | Tidak |
| Mengelola akun murid | Ya | Ya | Tidak | Tidak | Tidak | Ya |
| Mengelola paket tes | Ya | Ya | Draf | Review | Tidak | Tidak |
| Mengelola langganan/pembayaran | Ya | Ya | Tidak | Tidak | Ya | Baca |
| Melihat laporan | Ya | Ya | Konten | Konten | Bisnis | Operasional |

Murid dibuat atau diimpor melalui proses eksternal/provisi. CRUD panel mencakup pencarian, lihat/edit profil, provisi/undangan, penonaktifan login, permintaan reset kredensial, serta penutupan akun. Undangan tanpa percobaan dapat dihapus; akun yang sudah dipakai tidak dihapus permanen. Kredensial murid tidak dikelola panel ini.

Owner dapat mengundang, membaca, mengedit, menonaktifkan/mengaktifkan, dan menghapus undangan staf yang belum digunakan; sistem menjaga minimal satu Owner aktif. Finance dapat membuat, membaca, mengedit, mengaktifkan/menonaktifkan, dan menghapus metode pembayaran yang belum memiliki transaksi. Metode yang punya riwayat hanya dapat dinonaktifkan agar catatan lama tetap utuh.

Catat aktor, waktu, objek, perubahan sebelum/sesudah, dan alasan untuk perubahan peran, soal, akses akun, langganan, serta pembayaran. Riwayat bersifat append-only untuk pengguna aplikasi.

## Langganan dan pembayaran

- Paket prabayar memiliki masa berlaku dan kuota percobaan. Renewal manual.
- Pembatalan mencegah perpanjangan berikutnya tetapi akses berlangsung sampai tanggal bayar berakhir.
- Refund penuh mencabut akses; refund parsial tidak didukung MVP.
- Hanya pembayaran terkonfirmasi yang mengaktifkan atau memperpanjang akses. Callback harus diverifikasi, idempoten, dan dicatat di audit.
- Integrasi pertama direkomendasikan melalui Midtrans dengan virtual account/transfer bank, QRIS, dan e-wallet yang tersedia pada akun merchant. Gateway dibungkus adapter supaya pilihan dapat diganti setelah uji akun merchant. Metode tersedia bergantung konfigurasi merchant. [Metode Midtrans](https://docs.midtrans.com/docs/apa-saja-metode-pembayaran-yang-disediakan-oleh-midtrans), [notifikasi pembayaran Midtrans](https://docs.midtrans.com/docs/https-notification-webhooks)

## Akun ditutup dan privasi

Menutup akun langsung menonaktifkan login. Riwayat percobaan, pembayaran, dan audit dipertahankan dalam bentuk yang tidak mengidentifikasi murid secara langsung. Rekomendasi awal: masa tenggang 24 bulan setelah penutupan, lalu anonimisasi/hapus pengenal langsung melalui pekerjaan terjadwal; durasi disimpan sebagai konfigurasi dan harus ditinjau Finance/Legal sebelum produksi. Retensi catatan transaksi gateway mengikuti kebutuhan operasional dan penelaahan tersebut.

## Analitik MVP

Satu dashboard menampilkan dua kelompok metrik yang tidak dicampur:

- **Belajar:** percobaan dimulai/selesai, tingkat penyelesaian, nilai rata-rata dan distribusi, ketepatan per elemen/subelemen/level kognitif, soal paling sering benar/salah, serta waktu pengerjaan.
- **Bisnis:** pembayaran terkonfirmasi, nilai penjualan kotor, refund, langganan aktif/kedaluwarsa, kuota terjual/dipakai, dan perpanjangan.

Hasil belajar dihitung dari percobaan yang disubmit; percobaan berlangsung ditampilkan terpisah. Akses laporan dibatasi per peran. Filter awal: rentang waktu, Jenjang, paket tes, dan status pembayaran.

## API dan arsitektur

- **Backend:** Laravel 13 sebagai modular monolith dan REST JSON API berversi `/api/v1`.
- **Panel admin:** Vue 3 + Vite sebagai SPA, memakai API Laravel.
- **Database:** MySQL 8.4 LTS. Perubahan skema lewat migration Laravel; data awal lewat seeder.
- **Auth staf:** sesi web aman pada origin yang sama. **Auth klien siswa:** token singkat yang diterbitkan identity provider eksternal dan divalidasi backend; `issuer + subject` menjadi pemetaan akun murid, bukan kata sandi lokal.
- **Payment:** Midtrans melalui adapter; callback diverifikasi dan diproses idempoten. Akses tidak pernah diberikan berdasarkan redirect browser.
- **Media:** gambar soal masuk ke object storage privat melalui URL unggah bertanda tangan atau endpoint backend; metadata terhubung ke revisi soal.
- **Operasional awal:** queue database untuk pekerjaan tertunda, log terstruktur, backup MySQL harian, health check, dan pencatatan kegagalan callback. Redis dan multi-region ditunda sampai beban mengharuskannya.
- **Demo publik:** Vue SPA memakai data fixture lokal dan bersifat baca/tulis dalam memori browser. Tidak ada data nyata atau persistence. Backend dan MySQL tidak berjalan di GitHub Pages.

Laravel 13 mendukung PHP 8.3–8.5; Vue 3 adalah versi mayor terkini yang direkomendasikan untuk proyek baru; MySQL 8.4 berada di jalur LTS dengan perubahan yang lebih terkendali. [Siklus rilis Laravel](https://laravel.com/framework/docs/releases), [FAQ Vue](https://vuejs.org/about/faq), [rilis LTS MySQL](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html)

## Versi kerangka baru

Kurator membuat versi Kerangka TKA baru dan mengimpor katalog resminya. Soal dari versi sebelumnya disalin sebagai revisi Draf; kurator meninjau pemetaan elemen, subelemen, level, dan bentuk respons. Paket dan percobaan lama tetap terikat pada versi serta revisi asal. Tidak ada pemetaan otomatis yang langsung menerbitkan soal.

## Batas demo pertama

Demo menunjukkan navigasi dashboard, CRUD bank soal dan paket tes, editor soal dengan LaTeX.js serta unggahan/pratinjau gambar, pratinjau murid dan uji coba yang menilai jawaban, CRUD akun murid dan staf, CRUD metode pembayaran, siklus review, dan ringkasan analitik menggunakan data contoh. Tombol aksi mengubah state browser agar alur dapat dicoba; refresh mengembalikan data contoh. Demo tidak mengirim pembayaran, menyimpan data, mengautentikasi pengguna, atau mengakses data siswa nyata.

## Kriteria MVP operasional

1. Staf dapat masuk dan hanya menjalankan aksi sesuai peran; aksi yang diwajibkan mempunyai audit record.
2. Penulis dapat membuat tiga bentuk respons resmi, memakai notasi matematika dan gambar, lalu mengirim revisi ke review.
3. Reviewer dapat meminta revisi atau menyetujui; penulis tidak dapat menyetujui revisinya sendiri; publikasi membuat versi tidak berubah.
4. Admin dapat membangun paket dengan satu Jenjang/versi kerangka, mengurutkan soal, dan mematok revisi.
5. API dapat membuat, memulihkan, menyimpan jawaban, menyubmit, serta menilai percobaan sesuai timer dan kuota.
6. Pembayaran callback yang tervalidasi secara idempoten mengubah Hak akses; callback gagal dapat direkonsiliasi.
7. Penutupan akun menonaktifkan akses dan proses retensi menganonimkan pengenal langsung sesuai konfigurasi.
8. Dashboard menampilkan metrik belajar dan bisnis secara terpisah dan dapat difilter.

## Urutan tiket pembangunan

1. [#18 Fondasi Laravel, Vue, dan MySQL](https://github.com/nkurnia1031/fibo/issues/18)
2. [#16 Katalog Jenjang dan Kerangka TKA](https://github.com/nkurnia1031/fibo/issues/16)
3. [#14 Autentikasi staf, RBAC, dan audit](https://github.com/nkurnia1031/fibo/issues/14)
4. [#17 Bank soal, editor, dan review](https://github.com/nkurnia1031/fibo/issues/17)
5. [#12 Paket tes dan revisi yang dipatok](https://github.com/nkurnia1031/fibo/issues/12)
6. [#20 Akun murid dan API percobaan](https://github.com/nkurnia1031/fibo/issues/20)
7. [#13 Paket langganan, Midtrans, dan Hak akses](https://github.com/nkurnia1031/fibo/issues/13)
8. [#19 Analitik belajar dan bisnis](https://github.com/nkurnia1031/fibo/issues/19)
9. [#15 Migrasi Kerangka TKA antarversi](https://github.com/nkurnia1031/fibo/issues/15)
