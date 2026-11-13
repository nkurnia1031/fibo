# Spek MVP Fibo TKA Matematika

Status: **baseline implementasi yang disetujui untuk memulai demo dan tiket pembangunan**. Pilihan dapat disesuaikan setelah demo ditinjau.

## Tujuan

Membangun pengalaman web yang memudahkan murid memahami produk, membuat akun, mengerjakan latihan, dan mengelola Poin. Staf mengelola konten, akses, top-up manual, akun, dan laporan. Laman publik murid menjadi pintu utama; panel staf mendukung operasionalnya.

## Pengguna dan batas produk

- Pengguna publik adalah calon murid dan murid. Pengguna internal adalah Owner, Admin, Penulis Konten, Reviewer, Finance, dan Support.
- MVP mencakup laman utama yang responsif, pendaftaran/login murid, katalog paket, Dompet poin, alur pembelian akses, dan pengajuan Top-up manual.
- Panel staf tetap mencakup CRUD soal/paket, review, akun, pengaturan penawaran Poin, pemeriksaan transfer, pemberian/koreksi Poin, serta analitik operasional.
- Tidak ada pembayaran uang langsung per paket, gateway, atau penagihan otomatis. Uang hanya ditransfer saat Top-up; akses dan materi dibeli menggunakan Poin.
- Target awal konten: SMP/MTs, kelas 9. Katalog Jenjang dapat ditambah; Jenjang yang sudah dipakai diarsipkan, bukan dihapus.
- Demo GitHub Pages mensimulasikan alur calon murid dan murid menggunakan state browser. Demo tidak menyimpan akun atau transaksi di backend.

## Keputusan konten

Kerangka SMP/MTs memuat elemen Bilangan, Aljabar, Geometri dan Pengukuran, serta Data dan Peluang. Tiga level kognitifnya adalah Pengetahuan dan Pemahaman, Aplikasi, dan Penalaran. Bentuk respons resminya adalah pilihan ganda satu jawaban, pilihan ganda kompleks MCMA, dan pilihan ganda kompleks kategori. Ketiganya berasal dari kerangka resmi; soal grup dengan stimulus bersama merupakan dimensi terpisah dari bentuk respons. [Perka BSKAP 047/H/AN/2025](https://pusmendik.kemendikdasmen.go.id/tka/page/download_file/700298_47)

Keputusan produk:

- Setiap revisi soal wajib memiliki pertanyaan, kunci jawaban, pembahasan, level kognitif, dan satu subelemen utama. Miskonsepsi umum dan tag tambahan opsional.
- Penulis menyusun konten dengan notasi LaTeX dan unggahan gambar. Demo menggunakan LaTeX.js 0.12.6 untuk merender sumber matematika pada editor, pratinjau, dan uji coba; sumber LaTeX tetap disimpan apa adanya. Implementasi menerima subset sintaks/makro yang didukung LaTeX.js; paket TeX arbitrer tidak dieksekusi. Validasi tipe dan ukuran gambar, sanitasi konten, serta penyimpanan privat wajib pada backend. [Dokumentasi LaTeX.js](https://latex.js.org/usage.html)
- Stimulus grup disimpan terpisah dan ditautkan ke soal-soal yang menggunakannya.
- Tidak ada format esai pada MVP karena tiga bentuk respons di atas adalah bentuk yang ditetapkan kerangka untuk jenjang ini. Penambahan format lain memerlukan keputusan produk tersendiri.
- Skor satu butir adalah 1 untuk jawaban penuh benar dan 0 untuk salah/kosong. MCMA harus sama persis dengan himpunan kunci; kategori harus benar di semua baris. Skor latihan tidak memengaruhi Dompet poin; kredit parsial tidak tersedia.

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
- Simpan respons dan Skor butir, total skor, serta persentase. Item salah/kosong bernilai nol; Skor tidak menambah saldo belanja.

## Akun murid, peran staf, dan audit

Murid membuat akun sendiri dari laman utama menggunakan nama, email, dan kata sandi. Pendaftaran hanya meminta data minimum; Jenjang/kelas dan profil tambahan dapat dilengkapi setelah masuk. Email diverifikasi sebelum murid dapat mengajukan Top-up atau membeli akses. Murid dapat masuk, keluar, dan memulai reset kata sandi sendiri. Autentikasi murid dan staf memakai guard/session terpisah.

Owner/Admin/Support dapat mencari, melihat, mengedit profil, menonaktifkan login, memulai reset, dan menutup akun. Akun murid tidak bergantung pada identity provider eksternal. Akun yang sudah memiliki riwayat tidak dihapus permanen.

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
| Mengelola Top-up, Poin, dan Hak akses | Ya | Ya | Tidak | Tidak | Ya | Baca |
| Melihat laporan | Ya | Ya | Konten | Konten | Bisnis | Operasional |

CRUD panel murid mencakup pencarian, lihat/edit profil, penonaktifan login, permintaan reset kredensial, dan penutupan akun. Akun hanya dapat dihapus permanen jika belum memiliki percobaan, saldo/transaksi Poin, atau permintaan Top-up. Kredensial murid dikelola melalui alur reset aman, tidak dapat dibaca staf.

Owner dapat mengundang, membaca, mengedit, menonaktifkan/mengaktifkan, dan menghapus undangan staf yang belum digunakan; sistem menjaga minimal satu Owner aktif. Finance dapat membuat, membaca, mengedit, mengaktifkan/menonaktifkan, dan menghapus kanal/instruksi Top-up yang belum dipakai. Kanal yang punya riwayat hanya dapat dinonaktifkan agar bukti transfer lama tetap utuh.

Catat aktor, waktu, objek, perubahan sebelum/sesudah, dan alasan untuk perubahan peran, soal, akun, saldo/Transaksi poin, Top-up, serta Hak akses. Riwayat bersifat append-only untuk pengguna aplikasi.

## Poin, Top-up, dan pembelian akses

- Di produk, **Poin Fibo** (disebut “Poin” pada label saldo dan harga) adalah saldo belanja. Skor/nilai latihan selalu ditampilkan terpisah dan tidak dapat dibelanjakan.
- Dompet poin memakai bilangan bulat. Poin Fibo tidak dapat ditransfer antarmurid, ditarik menjadi uang, atau membuat saldo negatif. Poin Fibo tidak kedaluwarsa pada MVP.
- Saldo dibentuk dari Transaksi poin permanen: pembelian Poin, bonus pendaftaran/promosi, pembelian akses, pengembalian/koreksi, dan pembatalan yang memenuhi syarat. Catatan lama tidak diedit atau dihapus.
- Finance mengelola Paket top-up berisi nominal transfer dan jumlah Poin. Murid memilih paket, melihat instruksi transfer, mengirim referensi/bukti, lalu mengajukan pemeriksaan.
- Top-up berstatus `Menunggu → Terkonfirmasi` atau `Menunggu → Ditolak`. Finance memeriksa transfer di luar sistem lalu mengonfirmasi atau menolak secara manual. Poin dikreditkan tepat satu kali hanya saat dikonfirmasi; tidak ada callback gateway.
- Registrasi memberi bonus awal yang dapat dikonfigurasi. Finance/Owner dapat menerbitkan Poin promosi atau koreksi manual dengan alasan wajib. Mesin kode promo dan referral ditunda; kampanye awal menggunakan bonus pendaftaran dan penerbitan promosi yang tercatat.
- Penawaran akses menghubungkan paket tes/materi dengan harga Poin dan syarat akses (mis. satu paket, masa berlaku, atau kuota). Paket tes tetap menjadi konten; harga dan masa akses diatur sebagai penawaran agar pemasaran dapat mengubahnya tanpa mengubah revisi konten.
- Paket tes, materi, dan akses belajar berjangka memiliki harga Poin yang ditampilkan sebelum pembelian. Debit Poin dan pembuatan Hak akses terjadi atomik; sistem menolak saldo yang tidak cukup dan permintaan duplikat.
- Percobaan tetap mengurangi kuota Hak akses menurut aturan paket. Pembelian materi tidak dapat mengubah percobaan lama.
- Langganan belum dibutuhkan untuk MVP. Jika ditambahkan, harga serta perpanjangannya wajib memakai Poin, tidak menarik uang langsung. Perpanjangan manual; auto-renew berbayar dan debit otomatis ditunda.
- Akses digital yang sudah dipakai tidak mendapat refund otomatis. Finance dapat mencatat koreksi Poin untuk kasus khusus dengan alasan; Hak akses terkait dicabut jika belum digunakan dan riwayat percobaan tetap utuh.
- Metode pembayaran admin berubah menjadi instruksi kanal Top-up manual (mis. rekening bank, nama pemilik, nomor rekening, catatan). Data sensitif tidak ditampilkan pada metrik umum.

Top-up tetap mencatat nominal uang yang diperiksa untuk rekonsiliasi. Catatan uang ini terpisah dari saldo dan harga belanja Poin; materi tidak dijual melalui pembayaran uang langsung.

## Akun ditutup dan privasi

Menutup akun langsung menonaktifkan login. Riwayat percobaan, Top-up, Transaksi poin, dan audit dipertahankan dalam bentuk yang tidak mengidentifikasi murid secara langsung. Rekomendasi awal: masa tenggang 24 bulan setelah penutupan, lalu anonimisasi/hapus pengenal langsung melalui pekerjaan terjadwal; durasi disimpan sebagai konfigurasi dan harus ditinjau Finance/Legal sebelum produksi. Retensi bukti transfer mengikuti kebutuhan operasional dan penelaahan tersebut.

## Analitik MVP

Satu dashboard menampilkan dua kelompok metrik yang tidak dicampur:

- **Belajar:** percobaan dimulai/selesai, tingkat penyelesaian, nilai rata-rata dan distribusi, ketepatan per elemen/subelemen/level kognitif, soal paling sering benar/salah, serta waktu pengerjaan.
- **Bisnis:** transfer Top-up menunggu/terkonfirmasi/ditolak dan nominal uang diterima; Poin diterbitkan/dipromosikan/dibelanjakan/dikoreksi; saldo/kewajiban Poin; paket akses terjual/dipakai; akses aktif/kedaluwarsa.

Hasil belajar dihitung dari percobaan yang disubmit; percobaan berlangsung ditampilkan terpisah. Nominal transfer tidak dicampur dengan Poin yang beredar atau dibelanjakan. Akses laporan dibatasi per peran. Filter awal: rentang waktu, Jenjang, paket tes, dan status Top-up.

## API dan arsitektur

- **Backend:** Laravel 13 sebagai modular monolith dan REST JSON API berversi `/api/v1`.
- **Panel admin:** Vue 3 + Vite sebagai SPA, memakai API Laravel.
- **Database:** MySQL 8.4 LTS. Perubahan skema lewat migration Laravel; data awal lewat seeder.
- **Auth staf dan murid:** sesi web aman pada origin yang sama, memakai guard terpisah. Murid mendaftar dengan email/kata sandi aplikasi, verifikasi email, throttle login/register, dan alur reset kredensial.
- **Wallet:** tabel ledger append-only dengan saldo konsisten, idempotency key untuk mutasi, dan debit Poin + pembuatan Hak akses dalam transaksi MySQL atomik.
- **Top-up:** permintaan dan bukti transfer disimpan privat; Finance mengonfirmasi manual. Konfirmasi serta kredit Poin idempoten; integrasi gateway tidak termasuk MVP.
- **Media:** gambar soal masuk ke object storage privat melalui URL unggah bertanda tangan atau endpoint backend; metadata terhubung ke revisi soal.
- **Operasional awal:** queue database untuk pekerjaan tertunda, log terstruktur, backup MySQL harian, health check, dan pencatatan kegagalan pemeriksaan Top-up. Redis dan multi-region ditunda sampai beban mengharuskannya.
- **Demo GitHub Pages:** laman murid memakai data fixture dan state dalam memori browser. Daftar, saldo, pembelian, dan Top-up hanya simulasi; tidak ada akun, persistence, transfer, atau konfirmasi uang yang nyata. Panel admin di domain produk tetap memakai API Laravel.

Laravel 13 mendukung PHP 8.3–8.5; Vue 3 adalah versi mayor terkini yang direkomendasikan untuk proyek baru; MySQL 8.4 berada di jalur LTS dengan perubahan yang lebih terkendali. [Siklus rilis Laravel](https://laravel.com/framework/docs/releases), [FAQ Vue](https://vuejs.org/about/faq), [rilis LTS MySQL](https://dev.mysql.com/doc/refman/8.4/en/mysql-releases.html)

## Versi kerangka baru

Kurator membuat versi Kerangka TKA baru dan mengimpor katalog resminya. Soal dari versi sebelumnya disalin sebagai revisi Draf; kurator meninjau pemetaan elemen, subelemen, level, dan bentuk respons. Paket dan percobaan lama tetap terikat pada versi serta revisi asal. Tidak ada pemetaan otomatis yang langsung menerbitkan soal.

## Batas demo pertama

Halaman pertama GitHub Pages menunjukkan laman publik murid: penjelasan produk, contoh soal, daftar paket dengan harga Poin, pendaftaran/login contoh, Dompet poin, pembelian akses, dan pengajuan Top-up manual. Tautan staf menuju panel admin pada domain produk. Panel admin tersebut membutuhkan sesi staf dan API Laravel; demo Pages tidak berpura-pura menjalankan alur admin tanpa backend.

Semua tindakan demo hanya mengubah state browser; refresh mengembalikan fixture. Pendaftaran, saldo, pembelian, Top-up, dan konfirmasi tidak terhubung ke backend. Jangan gunakan rekening, kredensial, atau identitas nyata di demo. Situs produksi menggunakan API; deploy UI simulasi ke domain produk menunggu implementasi auth, ledger, dan pemeriksaan Top-up di tiket terkait.

## Kriteria MVP operasional

1. Pengunjung dapat memahami nilai produk, melihat paket serta harga Poin, lalu membuat akun dengan formulir singkat dan mengakses dashboard murid.
2. Penulis dapat membuat tiga bentuk respons resmi, memakai notasi matematika dan gambar, lalu mengirim revisi ke review.
3. Reviewer dapat meminta revisi atau menyetujui; penulis tidak dapat menyetujui revisinya sendiri; publikasi membuat versi tidak berubah.
4. Admin dapat membangun paket dengan satu Jenjang/versi kerangka, mengurutkan soal, dan mematok revisi.
5. Murid dapat melakukan Top-up manual; Finance dapat meninjau bukti dan mengkredit Poin tepat satu kali setelah transfer diperiksa.
6. Murid dapat membeli paket/materi memakai Poin; saldo dan Hak akses berubah atomik, dan percobaan mengikuti timer serta kuota.
7. Bonus promosi, debit, koreksi, dan pembelian dapat ditelusuri pada ledger dan audit.
8. Penutupan akun menonaktifkan akses dan proses retensi menganonimkan pengenal langsung sesuai konfigurasi.
9. Dashboard menampilkan metrik belajar, uang Top-up, dan Poin secara terpisah serta dapat difilter.

## Urutan tiket pembangunan

1. [#18 Fondasi Laravel, Vue, dan MySQL](https://github.com/nkurnia1031/fibo/issues/18)
2. [#21 Laman publik, pendaftaran, dan alur murid](https://github.com/nkurnia1031/fibo/issues/21)
3. [#16 Katalog Jenjang dan Kerangka TKA](https://github.com/nkurnia1031/fibo/issues/16)
4. [#14 Autentikasi staf, RBAC, dan audit](https://github.com/nkurnia1031/fibo/issues/14)
5. [#17 Bank soal, editor, dan review](https://github.com/nkurnia1031/fibo/issues/17)
6. [#12 Paket tes dan revisi yang dipatok](https://github.com/nkurnia1031/fibo/issues/12)
7. [#20 Akun murid dan API percobaan](https://github.com/nkurnia1031/fibo/issues/20)
8. [#13 Dompet poin, Top-up manual, dan Hak akses](https://github.com/nkurnia1031/fibo/issues/13)
9. [#19 Analitik belajar, uang Top-up, dan Poin](https://github.com/nkurnia1031/fibo/issues/19)
10. [#15 Migrasi Kerangka TKA antarversi](https://github.com/nkurnia1031/fibo/issues/15)
