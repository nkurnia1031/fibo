import { reactive } from 'vue'

export const store = reactive({
  questions: [
    { id: 'Q-1284', title: 'Perbandingan panjang sisi pada segitiga', prompt: 'Pada segitiga siku-siku ABC, panjang sisi siku-sikunya $6$ cm dan $8$ cm. Berapa panjang sisi miringnya?', format: 'Pilihan ganda', topic: 'Geometri dan Pengukuran', level: 'Penalaran', status: 'Pemeriksaan', updated: '12 menit lalu', author: 'Nadia A.', revision: 3, options: ['8 cm', '9 cm', '10 cm', '14 cm'], answerKey: 2, explanation: 'Gunakan teorema Pythagoras: $c=\\sqrt{6^2+8^2}=\\sqrt{100}=10$ cm.', imageUrl: '' },
    { id: 'Q-1283', title: 'Peluang mengambil bola bernomor genap', prompt: 'Sebuah kotak berisi bola bernomor $1$ sampai $8$. Pilih semua pernyataan yang benar.', format: 'PG kompleks · MCMA', topic: 'Data dan Peluang', level: 'Aplikasi', status: 'Draf', updated: '38 menit lalu', author: 'Rafi P.', revision: 2, options: ['Peluang mengambil bilangan genap adalah $\\frac{1}{2}$.', 'Peluang mengambil bilangan prima adalah $\\frac{1}{4}$.', 'Peluang mengambil bilangan lebih dari 6 adalah $\\frac{1}{4}$.', 'Peluang mengambil angka 1 adalah $\\frac{1}{8}$.'], correctOptions: [0, 2, 3], explanation: 'Ada empat bilangan genap dari delapan bola. Bilangan prima adalah 2, 3, 5, dan 7, jadi peluangnya $\\frac{4}{8}=\\frac{1}{2}$.', imageUrl: '' },
    { id: 'Q-1282', title: 'Pola bilangan pada susunan kursi', prompt: 'Baris pertama memiliki $5$ kursi. Setiap baris berikutnya memiliki $3$ kursi lebih banyak. Berapa banyak kursi pada baris ke-6?', format: 'Pilihan ganda', topic: 'Bilangan', level: 'Pengetahuan', status: 'Disetujui', updated: 'Hari ini, 09.14', author: 'Nadia A.', revision: 1, options: ['17', '20', '23', '25'], answerKey: 1, explanation: 'Pola bertambah 3. Baris ke-6 adalah $5+5\\times3=20$.', imageUrl: '' },
    { id: 'Q-1281', title: 'Model biaya perjalanan', prompt: 'Biaya perjalanan taksi dinyatakan dengan $B=7.000+4.000x$, dengan $x$ adalah jarak dalam kilometer. Tentukan benar atau salah.', format: 'PG kompleks · Kategori', topic: 'Aljabar', level: 'Aplikasi', status: 'Terbit', updated: 'Kemarin', author: 'Sinta R.', revision: 4, categoryStatements: ['Biaya awal perjalanan adalah Rp7.000.', 'Untuk jarak 5 km, biaya perjalanan Rp27.000.'], categoryAnswers: ['Benar', 'Benar'], explanation: 'Substitusi $x=5$: $B=7.000+4.000(5)=27.000$.', imageUrl: '' },
    { id: 'Q-1280', title: 'Luas permukaan prisma segitiga', prompt: 'Sebuah prisma memiliki luas alas $12$ cm$^2$, keliling alas $18$ cm, dan tinggi prisma $7$ cm. Berapa luas permukaannya?', format: 'Pilihan ganda', topic: 'Geometri dan Pengukuran', level: 'Penalaran', status: 'Terbit', updated: 'Kemarin', author: 'Rafi P.', revision: 2, options: ['126 cm²', '138 cm²', '150 cm²', '162 cm²'], answerKey: 1, explanation: 'Luas permukaan prisma adalah $2L_a+K_a\\times t=2(12)+18(7)=150$ cm².', imageUrl: '' },
    { id: 'Q-1279', title: 'Membaca diagram garis penjualan', prompt: 'Diagram menunjukkan jumlah penjualan selama 5 hari. Pilih semua kesimpulan yang sesuai dengan data.', format: 'PG kompleks · MCMA', topic: 'Data dan Peluang', level: 'Pengetahuan', status: 'Arsip', updated: '7 Nov 2026', author: 'Sinta R.', revision: 1, options: ['Penjualan tertinggi terjadi pada hari ke-4.', 'Penjualan selalu meningkat.', 'Rata-rata penjualan adalah 24 unit.', 'Penjualan terendah terjadi pada hari pertama.'], correctOptions: [0, 3], explanation: 'Baca nilai maksimum, minimum, dan pola pada diagram sebelum menyimpulkan.', imageUrl: '' },
  ],
  packages: [
    { id: 'PK-024', version: 1, name: 'Simulasi TKA Matematika #4', questionItems: [{ questionId: 'Q-1281', revision: 4 }, { questionId: 'Q-1280', revision: 2 }], questions: 2, duration: 90, attempts: 428, status: 'Terbit', updated: 'Hari ini, 08.30', framework: 'SMP/MTs · 2025.1' },
    { id: 'PK-023', version: 2, name: 'Latihan Aljabar: Persamaan & Fungsi', questionItems: [{ questionId: 'Q-1281', revision: 4 }], questions: 1, duration: 45, attempts: 1_206, status: 'Terbit', updated: '10 Nov 2026', framework: 'SMP/MTs · 2025.1' },
    { id: 'PK-022', version: 1, name: 'Paket Pemantapan Geometri', questionItems: [{ questionId: 'Q-1280', revision: 2 }, { questionId: 'Q-1281', revision: 4 }], questions: 2, duration: 60, attempts: 0, status: 'Draf', updated: '9 Nov 2026', framework: 'SMP/MTs · 2025.1' },
  ],
  students: [
    { id: 'M-8401', name: 'Alya Putri', email: 'alya.putri@contoh.id', school: 'SMP Negeri 8 Bandung', access: 'Aktif', expires: '14 Des 2026', attempts: '8 / 15', externalId: 'smp8:al-putri', status: 'Aktif' },
    { id: 'M-8402', name: 'Raka Pratama', email: 'raka.p@contoh.id', school: 'SMP Labschool Jakarta', access: 'Aktif', expires: '22 Des 2026', attempts: '12 / 20', externalId: 'labs:rakap', status: 'Aktif' },
    { id: 'M-8403', name: 'Naya Safitri', email: 'naya.s@contoh.id', school: 'MTs Al-Hikmah', access: 'Segera habis', expires: '12 Nov 2026', attempts: '4 / 10', externalId: 'alhikmah:naya', status: 'Aktif' },
    { id: 'M-8404', name: 'Dimas Wicaksono', email: 'dimas.w@contoh.id', school: 'SMP Negeri 3 Surabaya', access: 'Nonaktif', expires: '2 Nov 2026', attempts: '10 / 10', externalId: 'smp3:dimas', status: 'Nonaktif' },
    { id: 'M-8405', name: 'Akun Undangan', email: 'undangan@contoh.id', school: 'Belum ditentukan', access: 'Undangan', expires: '—', attempts: '0 / 0', externalId: 'Belum ditautkan', status: 'Undangan' },
  ],
  adminUsers: [
    { id: 'U-0101', name: 'Nadia Anindya', email: 'nadia@fibo.id', role: 'Owner', status: 'Aktif', lastActive: 'Sekarang', protected: true },
    { id: 'U-0102', name: 'Rafi Pratama', email: 'rafi@fibo.id', role: 'Penulis Konten', status: 'Aktif', lastActive: '12 menit lalu' },
    { id: 'U-0103', name: 'Sinta Rahma', email: 'sinta@fibo.id', role: 'Reviewer', status: 'Aktif', lastActive: '1 jam lalu' },
    { id: 'U-0104', name: 'Bagas Putra', email: 'bagas@fibo.id', role: 'Finance', status: 'Aktif', lastActive: 'Kemarin' },
    { id: 'U-0105', name: 'Akun Undangan', email: 'undangan@fibo.id', role: 'Support', status: 'Undangan', lastActive: 'Belum masuk' },
  ],
  payments: [
    { id: 'INV-2611-084', student: 'Alya Putri', plan: 'Paket 30 hari · 15 percobaan', channel: 'QRIS', amount: 'Rp 149.000', status: 'Menunggu', date: '10 Nov, 09.42' },
    { id: 'INV-2611-083', student: 'Raka Pratama', plan: 'Paket 90 hari · 40 percobaan', channel: 'BCA Virtual Account', amount: 'Rp 349.000', status: 'Terbayar', date: '10 Nov, 08.16' },
    { id: 'INV-2611-082', student: 'Naya Safitri', plan: 'Paket 30 hari · 15 percobaan', channel: 'GoPay', amount: 'Rp 149.000', status: 'Terbayar', date: '9 Nov, 19.35' },
    { id: 'INV-2611-081', student: 'Dimas Wicaksono', plan: 'Paket 30 hari · 15 percobaan', channel: 'BRI Virtual Account', amount: 'Rp 149.000', status: 'Kedaluwarsa', date: '9 Nov, 11.02' },
  ],
  paymentMethods: [
    { id: 'PM-01', name: 'QRIS', type: 'QRIS', provider: 'Midtrans', status: 'Aktif', fee: 'Sesuai kontrak', usage: 1028 },
    { id: 'PM-02', name: 'BCA Virtual Account', type: 'Virtual account', provider: 'Midtrans', status: 'Aktif', fee: 'Sesuai kontrak', usage: 684 },
    { id: 'PM-03', name: 'GoPay', type: 'E-wallet', provider: 'Midtrans', status: 'Aktif', fee: 'Sesuai kontrak', usage: 421 },
    { id: 'PM-04', name: 'DANA', type: 'E-wallet', provider: 'Midtrans', status: 'Nonaktif', fee: 'Sesuai kontrak', usage: 0 },
  ],
  plans: [
    { name: 'Paket 30 hari', validity: '30 hari', quota: '15 percobaan', price: 'Rp 149.000', students: 284, state: 'Aktif' },
    { name: 'Paket 90 hari', validity: '90 hari', quota: '40 percobaan', price: 'Rp 349.000', students: 162, state: 'Aktif' },
    { name: 'Paket 180 hari', validity: '180 hari', quota: '90 percobaan', price: 'Rp 599.000', students: 71, state: 'Aktif' },
  ],
})

const nextId = (collection, prefix, start) => {
  const codes = collection.map((item) => Number(item.id.split('-').at(-1))).filter(Number.isFinite)
  const current = codes.length ? Math.max(...codes) : start - 1
  const width = collection[0]?.id.split('-').at(-1).length || String(start).length
  return `${prefix}${String(current + 1).padStart(width, '0')}`
}

export function createQuestion(question) {
  const record = { id: nextId(store.questions, 'Q-', 1279), revision: 1, status: 'Draf', updated: 'Baru saja', author: 'Nadia A.', ...question }
  store.questions.unshift(record)
  return record
}

export function updateQuestion(question, changes) {
  Object.assign(question, changes, { updated: 'Baru saja' })
  return question
}

export function createQuestionRevision(question, changes) {
  const revision = { ...question, ...changes, revision: question.revision + 1, status: 'Draf', updated: 'Baru saja' }
  store.questions.unshift(revision)
  return revision
}

export function deleteDraftQuestion(question) {
  if (question.status !== 'Draf') return false
  const index = store.questions.indexOf(question)
  if (index < 0) return false
  store.questions.splice(index, 1)
  return true
}

export function nextQuestionStatus(question) {
  const next = { Draf: 'Pemeriksaan', Pemeriksaan: 'Disetujui', Disetujui: 'Terbit', Terbit: 'Arsip' }
  if (next[question.status]) question.status = next[question.status]
  question.updated = 'Baru saja'
}

export function createPackageRecord(packageRecord) {
  const record = { id: nextId(store.packages, 'PK-', 20), version: 1, questionItems: [], questions: 0, attempts: 0, status: 'Draf', updated: 'Baru saja', framework: 'SMP/MTs · 2025.1', ...packageRecord }
  store.packages.unshift(record)
  return record
}

export function createPackageRevision(packageRecord, changes) {
  const revision = { ...packageRecord, ...changes, id: nextId(store.packages, 'PK-', 20), version: packageRecord.version + 1, status: 'Draf', attempts: 0, updated: 'Baru saja' }
  store.packages.unshift(revision)
  return revision
}

export function deleteDraftPackage(packageRecord) {
  if (packageRecord.status !== 'Draf') return false
  const index = store.packages.indexOf(packageRecord)
  if (index < 0) return false
  store.packages.splice(index, 1)
  return true
}

export function transitionPackage(packageRecord, status) {
  packageRecord.status = status
  packageRecord.updated = 'Baru saja'
}

export function savePackageRecord(packageRecord, changes) {
  Object.assign(packageRecord, changes, { questions: changes.questionItems.length, updated: 'Baru saja' })
}

export function createStudent(student) {
  const record = { id: nextId(store.students, 'M-', 8400), access: 'Undangan', attempts: '0 / 0', status: 'Undangan', expires: '—', externalId: 'Belum ditautkan', ...student }
  store.students.unshift(record)
  return record
}

export function saveStudent(student, changes) {
  Object.assign(student, changes)
  if (changes.status === 'Nonaktif') student.access = 'Nonaktif'
  if (changes.status === 'Aktif' && student.access === 'Nonaktif') student.access = 'Aktif'
}

export function closeStudentAccount(student) {
  student.status = 'Ditutup'
  student.access = 'Ditutup'
}

export function deleteUnactivatedStudent(student) {
  if (student.status !== 'Undangan' || student.attempts !== '0 / 0') return false
  const index = store.students.indexOf(student)
  if (index < 0) return false
  store.students.splice(index, 1)
  return true
}

export function createAdminUser(user) {
  const record = { id: nextId(store.adminUsers, 'U-', 100), status: 'Undangan', lastActive: 'Belum masuk', ...user }
  store.adminUsers.unshift(record)
  return record
}

export function saveAdminUser(user, changes) { Object.assign(user, changes) }

export function deactivateAdminUser(user) {
  if (user.protected) return false
  user.status = user.status === 'Nonaktif' ? 'Aktif' : 'Nonaktif'
  return true
}

export function deleteInvitedAdminUser(user) {
  if (user.status !== 'Undangan' || user.protected) return false
  const index = store.adminUsers.indexOf(user)
  if (index < 0) return false
  store.adminUsers.splice(index, 1)
  return true
}

export function createPaymentMethod(method) {
  const record = { id: nextId(store.paymentMethods, 'PM-', 5), status: 'Aktif', usage: 0, ...method }
  store.paymentMethods.unshift(record)
  return record
}

export function savePaymentMethod(method, changes) { Object.assign(method, changes) }

export function deleteUnusedPaymentMethod(method) {
  if (method.usage > 0) return false
  const index = store.paymentMethods.indexOf(method)
  if (index < 0) return false
  store.paymentMethods.splice(index, 1)
  return true
}

export function confirmPayment(payment) {
  payment.status = 'Terbayar'
  payment.date = 'Baru saja'
}
