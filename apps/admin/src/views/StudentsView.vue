<script setup>
import { computed, ref } from 'vue'
import { LockKeyhole as LockIcon, MoreHorizontal, Pencil, Plus, Search, UserPlus, UserRoundCheck, X } from 'lucide-vue-next'
import { closeStudentAccount, createStudent, deleteUnactivatedStudent, resetStudentCredential, saveStudent, store } from '../demo-store'

const query = ref('')
const activeStudent = ref(null)
const mode = ref('')
const saving = ref(false)
const form = ref({ name: '', email: '', school: '', externalId: '' })
const filtered = computed(() => store.students.filter((student) => `${student.name} ${student.email} ${student.school}`.toLowerCase().includes(query.value.toLowerCase())))
function statusClass(value) { return value === 'Aktif' ? 'status-terbit' : value === 'Segera habis' ? 'status-review' : value === 'Undangan' ? 'status-disetujui' : 'status-arsip' }
function open(student = null) {
  activeStudent.value = student
  mode.value = student ? 'edit' : 'create'
  form.value = student ? { name: student.name, email: student.email, school: student.school, externalId: student.externalId } : { name: '', email: '', school: '', externalId: '' }
}
async function save() {
  if (!form.value.name.trim() || !form.value.email.trim()) return
  saving.value = true
  try {
    if (activeStudent.value) await saveStudent(activeStudent.value, { ...form.value })
    else await createStudent({ ...form.value })
    mode.value = ''
  } catch (error) {
    window.alert(error.message)
  } finally {
    saving.value = false
  }
}
async function toggle(student) {
  try { await saveStudent(student, { status: student.status === 'Nonaktif' ? 'Aktif' : 'Nonaktif' }) }
  catch (error) { window.alert(error.message) }
}
async function reset(student) {
  try {
    const result = await resetStudentCredential(student)
    window.alert(result.message)
  } catch (error) { window.alert(error.message) }
}
async function removeInvite(student) {
  if (!window.confirm('Hapus undangan yang belum pernah digunakan?')) return
  try { await deleteUnactivatedStudent(student) }
  catch (error) { window.alert(error.message) }
}
async function closeAccount(student) {
  if (!window.confirm(`Tutup akun ${student.name}? Riwayat tes dan transaksi tetap tersimpan.`)) return
  try { await closeStudentAccount(student) }
  catch (error) { window.alert(error.message) }
}
function exportStudents() {
  const records = [
    ['ID', 'Nama', 'Email', 'Sekolah', 'Status akses', 'Masa akses', 'Percobaan'],
    ...filtered.value.map((student) => [student.id, student.name, student.email, student.school, student.access, student.expires, student.attempts]),
  ]
  const csv = records.map((record) => record.map((value) => `"${String(value ?? '').replaceAll('"', '""')}"`).join(',')).join('\r\n')
  const url = URL.createObjectURL(new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' }))
  const link = document.createElement('a')
  link.href = url
  link.download = 'daftar-murid-fibo.csv'
  link.click()
  URL.revokeObjectURL(url)
}
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row"><div><div class="eyebrow">AKUN DAN AKSES <span>/</span> MURID</div><h1>Murid</h1><p class="page-subtitle">Buat dan kelola akun, hak akses, serta permintaan pemulihan murid.</p></div><button class="button button-primary" @click="open()"><UserPlus :size="17" /> Provisi murid</button></div>
    <div class="student-stat-row"><div class="student-stat"><span>Total akun</span><strong>{{ store.students.length }}</strong><small>akun tercatat di database</small></div><div class="student-stat"><span>Akses aktif</span><strong>{{ store.students.filter((student) => student.access === 'Aktif' || student.access === 'Segera habis').length }}</strong><small><i class="status-dot"></i> akses tersedia</small></div><div class="student-stat"><span>Undangan</span><strong>{{ store.students.filter((student) => student.status === 'Undangan').length }}</strong><small>menunggu aktivasi email</small></div><div class="student-stat"><span>Autentikasi murid</span><strong>Email & kata sandi</strong><small>verifikasi melalui email</small></div></div>
    <div class="content-tabs"><button class="content-tab active">Semua murid <span>{{ store.students.length }}</span></button><button class="content-tab">Akses aktif</button><button class="content-tab">Segera habis</button><button class="content-tab">Nonaktif</button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input v-model="query" placeholder="Cari nama, email, atau sekolah..." /></label><button class="button button-outline" @click="exportStudents">Ekspor</button></div>
    <section class="panel table-panel"><div class="table-scroll"><table class="data-table student-table"><thead><tr><th>MURID</th><th>SEKOLAH</th><th>STATUS AKSES</th><th>MASA AKSES</th><th>PERCOBAAN</th><th>AKSI</th></tr></thead><tbody><tr v-for="student in filtered" :key="student.id"><td><div class="student-name-cell"><span class="avatar" :class="`student-${student.id.slice(-1)}`">{{ student.name.split(' ').map((part) => part[0]).join('').slice(0, 2) }}</span><span><b>{{ student.name }}</b><small>{{ student.email }}</small></span></div></td><td class="school-cell">{{ student.school }}</td><td><span :class="['status-tag', statusClass(student.access)]"><i></i>{{ student.access }}</span></td><td><span class="expiry-cell">{{ student.expires }}</span></td><td class="attempt-cell">{{ student.attempts }}</td><td><div class="row-actions"><button class="icon-button tiny" title="Lihat / edit" @click="open(student)"><Pencil :size="14" /></button><button v-if="student.status !== 'Ditutup'" class="icon-button tiny" title="Mulai reset kredensial" @click="reset(student)"><LockIcon :size="14" /></button><button v-if="student.status !== 'Ditutup'" class="icon-button tiny" :title="student.status === 'Nonaktif' ? 'Aktifkan' : 'Nonaktifkan login'" @click="toggle(student)"><UserRoundCheck :size="15" /></button><button v-if="student.status === 'Undangan'" class="icon-button tiny danger-icon" title="Hapus undangan" @click="removeInvite(student)"><X :size="14" /></button><button v-else-if="student.status !== 'Ditutup'" class="icon-button tiny danger-icon" title="Tutup akun" @click="closeAccount(student)"><MoreHorizontal :size="15" /></button></div></td></tr></tbody></table></div><div class="table-footer"><span>Menampilkan {{ filtered.length }} akun</span><div class="pagination"><button disabled>‹</button><button class="current-page">1</button><button>›</button></div></div></section>
    <div class="privacy-note"><LockIcon /><p><b>Privasi murid dijaga.</b> Panel tidak menyimpan kata sandi. Reset mengirim tautan aman ke email murid dan tercatat di audit.</p></div>
    <div v-if="mode" class="modal-backdrop" @click.self="mode = ''"><section class="modal-card form-modal"><button class="modal-close" @click="mode = ''"><X :size="18" /></button><span class="section-kicker">AKUN MURID · {{ activeStudent?.id || 'BARU' }}</span><h2>{{ activeStudent ? 'Edit data murid' : 'Provisi akun murid' }}</h2><p class="modal-description">Murid dapat mendaftar sendiri. Provisi membuat undangan dan mengirim tautan untuk menetapkan kata sandi.</p><label class="field-label">Nama murid<input v-model="form.name" placeholder="Nama lengkap" /></label><label class="field-label">Email<input v-model="form.email" type="email" placeholder="murid@contoh.id" /></label><label class="field-label">Sekolah<input v-model="form.school" placeholder="Nama sekolah" /></label><label class="field-label">ID referensi internal (opsional)<input v-model="form.externalId" placeholder="Nomor referensi" /></label><div v-if="activeStudent" class="framework-lock"><span>Riwayat tersimpan</span><b>{{ activeStudent.attempts }} percobaan · {{ activeStudent.expires }}</b></div><div class="modal-footer"><button class="button button-outline" @click="mode = ''">Batal</button><button class="button button-primary" :disabled="saving || !form.name.trim() || !form.email.trim()" @click="save">{{ saving ? 'Menyimpan…' : activeStudent ? 'Simpan perubahan' : 'Buat undangan' }}</button></div></section></div>
  </div>
</template>
