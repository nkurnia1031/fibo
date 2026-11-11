<script setup>
import { computed, ref } from 'vue'
import { KeyRound, Pencil, Plus, Search, ShieldCheck, Trash2, UserRoundX, X } from 'lucide-vue-next'
import { createAdminUser, deactivateAdminUser, deleteInvitedAdminUser, resetAdminPassword, saveAdminUser, store } from '../demo-store'

const query = ref('')
const selected = ref(null)
const modalOpen = ref(false)
const temporaryPassword = ref('')
const temporaryPasswordFor = ref('')
const form = ref({ name: '', email: '', role: 'Penulis Konten' })
const roles = ['Owner', 'Admin', 'Penulis Konten', 'Reviewer', 'Finance', 'Support']
const filtered = computed(() => store.adminUsers.filter((user) => `${user.name} ${user.email} ${user.role}`.toLowerCase().includes(query.value.toLowerCase())))
const ownerCount = computed(() => store.adminUsers.filter((user) => user.role === 'Owner' && user.status === 'Aktif').length)
function open(user = null) { selected.value = user; modalOpen.value = true; form.value = user ? { name: user.name, email: user.email, role: user.role } : { name: '', email: '', role: 'Penulis Konten' } }
async function save() {
  if (!form.value.name.trim() || !form.value.email.trim()) return
  try {
    if (selected.value) {
      if (selected.value.role === 'Owner' && form.value.role !== 'Owner' && ownerCount.value <= 1) { window.alert('Organisasi harus memiliki setidaknya satu Owner aktif.'); return }
      await saveAdminUser(selected.value, form.value)
    } else {
      const created = await createAdminUser(form.value)
      temporaryPassword.value = created.temporaryPassword
      temporaryPasswordFor.value = created.user.email
    }
    modalOpen.value = false
  } catch (error) { window.alert(error.message) }
}
async function toggle(user) {
  if (user.role === 'Owner' && user.status === 'Aktif' && ownerCount.value <= 1) { window.alert('Organisasi harus memiliki setidaknya satu Owner aktif.'); return }
  try { await deactivateAdminUser(user) }
  catch (error) { window.alert(error.message) }
}
async function remove(user) {
  if (!window.confirm(`Hapus undangan ${user.email}?`)) return
  try { await deleteInvitedAdminUser(user) }
  catch (error) { window.alert(error.message) }
}
async function startReset(user) {
  if (!window.confirm(`Buat kata sandi sementara baru untuk ${user.email}? Sesi aktifnya akan dicabut.`)) return
  try {
    temporaryPassword.value = await resetAdminPassword(user)
    temporaryPasswordFor.value = user.email
  } catch (error) { window.alert(error.message) }
}
async function copyTemporaryPassword() {
  try {
    await navigator.clipboard.writeText(temporaryPassword.value)
    window.alert('Kata sandi sementara disalin.')
  } catch {
    window.alert('Salin kata sandi yang tampil secara manual.')
  }
}
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row"><div><div class="eyebrow">PENGATURAN ORGANISASI <span>/</span> ADMIN & STAF</div><h1>Admin & staf</h1><p class="page-subtitle">Undang staf dan kelola peran serta akses panel.</p></div><button class="button button-primary" @click="open()"><Plus :size="17" /> Undang staf</button></div>
    <div class="content-tabs"><button class="content-tab active">Semua akun <span>{{ store.adminUsers.length }}</span></button><button class="content-tab">Aktif</button><button class="content-tab">Undangan</button><button class="content-tab">Nonaktif</button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input v-model="query" placeholder="Cari nama, email, atau peran..." /></label><span class="owner-pill"><ShieldCheck :size="15" /> {{ ownerCount }} Owner aktif</span></div>
    <section class="panel table-panel"><div class="table-scroll"><table class="data-table staff-table"><thead><tr><th>STAF</th><th>PERAN</th><th>STATUS</th><th>AKTIVITAS TERAKHIR</th><th>AKSI</th></tr></thead><tbody><tr v-for="user in filtered" :key="user.id"><td><div class="student-name-cell"><span class="avatar staff-avatar">{{ user.name.split(' ').map((part) => part[0]).join('').slice(0, 2) }}</span><span><b>{{ user.name }}</b><small>{{ user.email }} · {{ user.id }}</small></span></div></td><td><span class="role-chip">{{ user.role }}</span></td><td><span :class="['status-tag', user.status === 'Aktif' ? 'status-terbit' : user.status === 'Undangan' ? 'status-disetujui' : 'status-arsip']"><i></i>{{ user.status }}</span></td><td class="muted-cell">{{ user.lastActive }}</td><td><div class="row-actions"><button class="icon-button tiny" title="Edit staf dan peran" @click="open(user)"><Pencil :size="14" /></button><button v-if="user.status !== 'Undangan'" class="icon-button tiny" title="Mulai reset akses" @click="startReset(user)"><KeyRound :size="14" /></button><button v-if="!user.protected" class="icon-button tiny" :title="user.status === 'Nonaktif' ? 'Aktifkan' : 'Nonaktifkan akses'" @click="toggle(user)"><UserRoundX :size="15" /></button><button v-if="user.status === 'Undangan' && !user.protected" class="icon-button tiny danger-icon" title="Hapus undangan" @click="remove(user)"><Trash2 :size="14" /></button></div></td></tr><tr v-if="!filtered.length"><td colspan="5" class="empty-state">Tidak ada akun yang cocok.</td></tr></tbody></table></div><div class="table-footer"><span>Owner menetapkan peran dan menjaga minimal satu Owner aktif.</span><span class="audit-inline">Perubahan peran dan akses masuk audit log</span></div></section>
    <div class="role-summary"><div v-for="role in roles" :key="role"><b>{{ role }}</b><span>{{ store.adminUsers.filter((user) => user.role === role).length }} akun</span></div></div>
    <div v-if="modalOpen" class="modal-backdrop" @click.self="modalOpen = false"><section class="modal-card form-modal"><button class="modal-close" @click="modalOpen = false"><X :size="18" /></button><span class="section-kicker">AKUN STAF · {{ selected?.id || 'AKUN BARU' }}</span><h2>{{ selected ? 'Edit akun staf' : 'Buat akun staf' }}</h2><p class="modal-description">Akun panel memakai autentikasi internal. Kata sandi sementara ditampilkan satu kali setelah akun dibuat.</p><label class="field-label">Nama<input v-model="form.name" placeholder="Nama staf" /></label><label class="field-label">Email<input v-model="form.email" type="email" placeholder="staf@fibo.id" /></label><label class="field-label">Peran<select v-model="form.role"><option v-for="role in roles" :key="role">{{ role }}</option></select></label><div class="role-help">{{ form.role === 'Penulis Konten' ? 'Mengelola draf soal; tidak menyetujui soal sendiri.' : form.role === 'Reviewer' ? 'Meminta perbaikan atau menyetujui soal.' : form.role === 'Finance' ? 'Mengelola pembayaran dan langganan.' : form.role === 'Support' ? 'Membantu akses akun murid.' : 'Hak akses administratif sesuai peran.' }}</div><div class="modal-footer"><button class="button button-outline" @click="modalOpen = false">Batal</button><button class="button button-primary" :disabled="!form.name.trim() || !form.email.trim()" @click="save">{{ selected ? 'Simpan perubahan' : 'Buat akun' }}</button></div></section></div>
    <div v-if="temporaryPassword" class="modal-backdrop" @click.self="temporaryPassword = ''"><section class="modal-card form-modal credential-modal"><button class="modal-close" @click="temporaryPassword = ''"><X :size="18" /></button><span class="section-kicker">KREDENSIAL SEMENTARA</span><h2>Akun siap digunakan</h2><p class="modal-description">Bagikan kredensial ini dengan aman kepada {{ temporaryPasswordFor }}. Pengguna wajib mengganti kata sandi saat masuk.</p><code class="temporary-password">{{ temporaryPassword }}</code><div class="modal-footer"><button class="button button-outline" @click="copyTemporaryPassword">Salin</button><button class="button button-primary" @click="temporaryPassword = ''">Selesai</button></div></section></div>
  </div>
</template>
