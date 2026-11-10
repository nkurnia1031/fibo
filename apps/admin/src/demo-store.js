import { reactive } from 'vue'

export const store = reactive({
  questions: [
    { id: 'Q-1284', title: 'Perbandingan panjang sisi pada segitiga', snippet: 'Diketahui segitiga ABC dengan panjang sisi ...', format: 'Pilihan ganda', topic: 'Geometri dan Pengukuran', level: 'Penalaran', status: 'Pemeriksaan', updated: '12 menit lalu', author: 'Nadia A.', revision: 3 },
    { id: 'Q-1283', title: 'Peluang mengambil bola bernomor genap', snippet: 'Sebuah kotak berisi 8 bola bernomor ...', format: 'PG kompleks · MCMA', topic: 'Data dan Peluang', level: 'Aplikasi', status: 'Draf', updated: '38 menit lalu', author: 'Rafi P.', revision: 2 },
    { id: 'Q-1282', title: 'Pola bilangan pada susunan kursi', snippet: 'Pada baris pertama terdapat 5 kursi ...', format: 'Pilihan ganda', topic: 'Bilangan', level: 'Pengetahuan', status: 'Disetujui', updated: 'Hari ini, 09.14', author: 'Nadia A.', revision: 1 },
    { id: 'Q-1281', title: 'Model biaya perjalanan', snippet: 'Biaya perjalanan taksi dinyatakan dengan ...', format: 'PG kompleks · Kategori', topic: 'Aljabar', level: 'Aplikasi', status: 'Terbit', updated: 'Kemarin', author: 'Sinta R.', revision: 4 },
    { id: 'Q-1280', title: 'Luas permukaan prisma segitiga', snippet: 'Sebuah prisma memiliki alas berbentuk ...', format: 'Pilihan ganda', topic: 'Geometri dan Pengukuran', level: 'Penalaran', status: 'Terbit', updated: 'Kemarin', author: 'Rafi P.', revision: 2 },
    { id: 'Q-1279', title: 'Membaca diagram garis penjualan', snippet: 'Diagram menunjukkan penjualan selama ...', format: 'PG kompleks · MCMA', topic: 'Data dan Peluang', level: 'Pengetahuan', status: 'Arsip', updated: '7 Nov 2026', author: 'Sinta R.', revision: 1 },
  ],
  packages: [
    { id: 'PK-024', name: 'Simulasi TKA Matematika #4', questions: 25, duration: '90 menit', attempts: 428, status: 'Terbit', updated: 'Hari ini, 08.30' },
    { id: 'PK-023', name: 'Latihan Aljabar: Persamaan & Fungsi', questions: 15, duration: '45 menit', attempts: 1_206, status: 'Terbit', updated: '10 Nov 2026' },
    { id: 'PK-022', name: 'Paket Pemantapan Geometri', questions: 20, duration: '60 menit', attempts: 0, status: 'Draf', updated: '9 Nov 2026' },
  ],
  students: [
    { id: 'M-8401', name: 'Alya Putri', email: 'alya.putri@contoh.id', school: 'SMP Negeri 8 Bandung', access: 'Aktif', expires: '14 Des 2026', attempts: '8 / 15' },
    { id: 'M-8402', name: 'Raka Pratama', email: 'raka.p@contoh.id', school: 'SMP Labschool Jakarta', access: 'Aktif', expires: '22 Des 2026', attempts: '12 / 20' },
    { id: 'M-8403', name: 'Naya Safitri', email: 'naya.s@contoh.id', school: 'MTs Al-Hikmah', access: 'Segera habis', expires: '12 Nov 2026', attempts: '4 / 10' },
    { id: 'M-8404', name: 'Dimas Wicaksono', email: 'dimas.w@contoh.id', school: 'SMP Negeri 3 Surabaya', access: 'Nonaktif', expires: '2 Nov 2026', attempts: '10 / 10' },
  ],
  payments: [
    { id: 'INV-2611-084', student: 'Alya Putri', plan: 'Paket 30 hari · 15 percobaan', channel: 'QRIS', amount: 'Rp 149.000', status: 'Menunggu', date: '10 Nov, 09.42' },
    { id: 'INV-2611-083', student: 'Raka Pratama', plan: 'Paket 90 hari · 40 percobaan', channel: 'BCA Virtual Account', amount: 'Rp 349.000', status: 'Terbayar', date: '10 Nov, 08.16' },
    { id: 'INV-2611-082', student: 'Naya Safitri', plan: 'Paket 30 hari · 15 percobaan', channel: 'GoPay', amount: 'Rp 149.000', status: 'Terbayar', date: '9 Nov, 19.35' },
    { id: 'INV-2611-081', student: 'Dimas Wicaksono', plan: 'Paket 30 hari · 15 percobaan', channel: 'BRI Virtual Account', amount: 'Rp 149.000', status: 'Kedaluwarsa', date: '9 Nov, 11.02' },
  ],
  plans: [
    { name: 'Paket 30 hari', validity: '30 hari', quota: '15 percobaan', price: 'Rp 149.000', students: 284, state: 'Aktif' },
    { name: 'Paket 90 hari', validity: '90 hari', quota: '40 percobaan', price: 'Rp 349.000', students: 162, state: 'Aktif' },
    { name: 'Paket 180 hari', validity: '180 hari', quota: '90 percobaan', price: 'Rp 599.000', students: 71, state: 'Aktif' },
  ],
})

export function addQuestion(question) {
  store.questions.unshift({
    id: `Q-${1284 + store.questions.length - 5}`,
    revision: 1,
    status: 'Draf',
    updated: 'Baru saja',
    author: 'Nadia A.',
    ...question,
  })
}

export function nextQuestionStatus(question) {
  const next = { Draf: 'Pemeriksaan', Pemeriksaan: 'Disetujui', Disetujui: 'Terbit', Terbit: 'Arsip' }
  if (next[question.status]) question.status = next[question.status]
  question.updated = 'Baru saja'
}

export function confirmPayment(payment) {
  payment.status = 'Terbayar'
  payment.date = 'Baru saja'
}

export function toggleStudent(student) {
  student.access = student.access === 'Nonaktif' ? 'Aktif' : 'Nonaktif'
}
