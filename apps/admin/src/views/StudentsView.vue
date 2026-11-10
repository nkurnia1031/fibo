<script setup>
import { computed, ref } from 'vue'
import { LockKeyhole as LockIcon, MoreHorizontal, Pencil, Plus, Search, UserPlus, UserRoundCheck, X } from 'lucide-vue-next'
import { closeStudentAccount, createStudent, deleteUnactivatedStudent, saveStudent, store } from '../demo-store'

const query = ref('')
const activeStudent = ref(null)
const mode = ref('')
const form = ref({ name: '', email: '', school: '', externalId: '' })
const filtered = computed(() => store.students.filter((student) => `${student.name} ${student.email} ${student.school}`.toLowerCase().includes(query.value.toLowerCase())))
function statusClass(value) { return value === 'Aktif' ? 'status-terbit' : value === 'Segera habis' ? 'status-review' : value === 'Undangan' ? 'status-disetujui' : 'status-arsip' }
function open(student = null) {
  activeStudent.value = student
  mode.value = nextMode
  form.value = student ? { name: student.name, email: student.email, school: student.school, externalId: student.externalId } : { name: '', email: '', school: '', externalId: '' }
}
function save() {
  if (!form.value.name.trim() || !form.value.email.trim()) return
  if (activeStudent.value) saveStudent(activeStudent.value, { ...form.value })
  else createStudent({ ...form.value })
  mode.value = ''
}
function toggle(student) { saveStudent(student, { status: student.status === 'Nonaktif' ? 'Aktif' : 'Nonaktif' }) }
function reset(student) { window.alert(`Permintaan reset kredensial untuk ${student.email} dimulai melalui identity provider.`) }
function removeInvite(student) { if (window.confirm('Hapus undangan yang belum pernah digunakan?')) deleteUnactivatedStudent(student) }
function closeAccount(student) { if (window.confirm(`Tutup akun ${student.name}? Riwayat tes dan transaksi tetap tersimpan.`)) closeStudentAccount(student) }
function exportStudents() { window.alert('Ekspor daftar murid demo') }
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row"><div><div class="eyebrow">AKUN DAN AKSES <span>/</span> MURID</div><h1>Murid</h1><p class="page-subtitle">Buat dan kelola akun, hak akses, serta permintaan pemulihan murid.</p></div><button class="button button-primary" @click="open()"><UserPlus :size="17" /> Provisi murid</button></div>
    <div class="student-stat-row"><div class="student-stat"><span>Total akun</span><strong>{{ store.students.length }}</strong><small>semua akun contoh</small></div><div class="student-stat"><span>Akses aktif</span><strong>{{ store.students.filter((student) => student.access === 'Aktif' || student.access === 'Segera habis').length }}</strong><small><i class="status-dot"></i> akses tersedia</small></div><div class="student-stat"><span>Undangan</span><strong>{{ store.students.filter((student) => student.status === 'Undangan').length }}</strong><small>menunggu ditautkan</small></div><div class="student-stat"><span>Perlu bantuan</span><strong>12</strong><small>permintaan reset kredensial</small></div></div>
    <div class="content-tabs"><button class="content-tab active">Semua murid <span>{{ store.students.length }}</span></button><button class="content-tab">Akses aktif</button><button class="content-tab">Segera habis</button><button class="content-tab">Nonaktif</button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input v-model="query" placeholder="Cari nama, email, atau sekolah..." /></label><button class="button button-outline" @click="exportStudents">Ekspor</button></div>
    <section class="panel table-panel"><div class="table-scroll"><table class="data-table student-table"><thead><tr><th>MURID</th><th>SEKOLAH</th><th>STATUS AKSES</th><th>MASA AKSES</th><th>PERCOBAAN</th><th>AKSI</th></tr></thead><tbody><tr v-for="student in filtered" :key="student.id"><td><div class="student-name-cell"><span class="avatar" :class="`student-${student.id.slice(-1)}`">{{ student.name.split(' ').map((part) => part[0]).join('').slice(0, 2) }}</span><span><b>{{ student.name }}</b><small>{{ student.email }}</small></span></div></td><td class="school-cell">{{ student.school }}</td><td><span :class="['status-tag', statusClass(student.access)]"><i></i>{{ student.access }}</span></td><td><span class="expiry-cell">{{ student.expires }}</span></td><td class="attempt-cell">{{ student.attempts }}</td><td><div class="row-actions"><button class="icon-button tiny" title="Lihat / edit" @click="open(student)"><Pencil :size="14" /></button><button v-if="student.status !== 'Ditutup'" class="icon-button tiny" title="Mulai reset kredensial" @click="reset(student)"><LockIcon :size="14" /></button><button v-if="student.status !== 'Ditutup'" class="icon-button tiny" :title="student.status === 'Nonaktif' ? 'Aktifkan' : 'Nonaktifkan login'" @click="toggle(student)"><UserRoundCheck :size="15" /></button><button v-if="student.status === 'Undangan'" class="icon-button tiny danger-icon" title="Hapus undangan" @click="removeInvite(student)"><X :size="14" /></button><button v-else-if="student.status !== 'Ditutup'" class="icon-button tiny danger-icon" title="Tutup akun" @click="closeAccount(student)"><MoreHorizontal :size="15" /></button></div></td></tr></tbody></table></div><div class="table-footer"><span>Menampilkan {{ filtered.length }} akun demo</span><div class="pagination"><button disabled>‹</button><button class="current-page">1</button><button>›</button></div></div></section>
    <div class="privacy-note"><LockIcon /><p><b>Privasi murid dijaga.</b> Panel tidak menyimpan kata sandi. Autentikasi berasal dari identity provider; Support hanya dapat menonaktifkan login atau memulai reset.</p></div>
    <div v-if="mode" class="modal-backdrop" @click.self="mode = ''"><section class="modal-card form-modal"><button class="modal-close" @click="mode = ''"><X :size="18" /></button><span class="section-kicker">AKUN MURID · {{ activeStudent?.id || 'BARU' }}</span><h2>{{ activeStudent ? 'Edit data murid' : 'Provisi akun murid' }}</h2><p class="modal-description">Akun dibuat atau ditautkan oleh proses identitas eksternal. Kata sandi tidak dikelola di panel ini.</p><label class="field-label">Nama murid<input v-model="form.name" placeholder="Nama lengkap" /></label><label class="field-label">Email<input v-model="form.email" type="email" placeholder="murid@contoh.id" /></label><label class="field-label">Sekolah<input v-model="form.school" placeholder="Nama sekolah" /></label><label class="field-label">ID eksternal<input v-model="form.externalId" placeholder="ID dari identity provider" /></label><div v-if="activeStudent" class="framework-lock"><span>Riwayat tersimpan</span><b>{{ activeStudent.attempts }} percobaan · {{ activeStudent.expires }}</b></div><div class="modal-footer"><button class="button button-outline" @click="mode = ''">Batal</button><button class="button button-primary" :disabled="!form.name.trim() || !form.email.trim()" @click="save">{{ activeStudent ? 'Simpan perubahan' : 'Buat undangan' }}</button></div></section></div>
  </div>
</template>
