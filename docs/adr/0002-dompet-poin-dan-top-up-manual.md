# ADR-0002: Dompet poin dan top-up manual

- Status: Accepted
- Tanggal: 2026-11-13

## Konteks

Produk perlu memberi keluwesan bagi pemasaran tanpa membuat murid membayar uang secara langsung untuk setiap paket atau materi. MVP juga perlu berjalan tanpa integrasi gateway dan tanpa otomatisasi pembayaran.

## Keputusan

- Semua akses produk dibeli menggunakan Poin dari Dompet poin. Poin tidak dapat ditransfer atau diuangkan.
- Unit saldo disebut Poin Fibo; hasil penilaian disebut Skor/nilai agar Skor latihan tidak disalahartikan sebagai saldo belanja.
- Top-up adalah satu-satunya alur uang: murid mengirim bukti transfer manual dan Finance memeriksa transfer sebelum Poin dikreditkan.
- Saldo berasal dari Transaksi poin yang append-only; bonus pendaftaran, kampanye, pembelian, serta koreksi semuanya memiliki sumber dan alasan. Setelah masa retensi akun, identitas pada alasan/metadata boleh disamarkan, tetapi jenis, nilai, saldo setelah transaksi, dan riwayat kredit/debit tetap dipertahankan.
- Pemberian akses dan pendebitan Poin dilakukan dalam satu transaksi atomik. Saldo tidak boleh negatif; pemrosesan ulang tidak boleh menggandakan debit atau kredit.
- Paket akses berjangka dapat diperpanjang dengan Poin secara manual. Penagihan uang otomatis dan integrasi gateway tidak termasuk MVP.
- Akun murid dibuat sendiri menggunakan nama, email, dan kata sandi, dengan verifikasi email. Identitas murid memakai autentikasi aplikasi dan guard yang terpisah dari akun staf.
- Keputusan autentikasi murid dan pembayaran pada ADR-0001 digantikan oleh keputusan ini; pilihan Laravel, Vue, MySQL, dan modular monolith tetap berlaku.

## Konsekuensi

- Finance perlu mengelola Paket top-up dan instruksi transfer, memeriksa setiap permintaan, serta memberi alasan saat membuat koreksi Poin.
- Laporan membedakan uang yang diterima dari Poin yang diterbitkan dan Poin yang dibelanjakan.
- Pembelian akses lebih mudah dipromosikan dengan bonus atau perubahan harga Poin; uang dan Poin tetap dua catatan berbeda untuk rekonsiliasi.
- Pendaftaran murid tidak lagi bergantung pada identity provider eksternal. Kredensial murid harus di-hash, diverifikasi, dapat di-reset, dan dilindungi rate limit.
- Demo dapat mensimulasikan daftar, dompet, pembelian, dan Top-up di browser, tetapi konfirmasi uang dan ketahanan transaksi hanya diuji melalui backend dan QA.
