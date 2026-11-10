<script setup>
import { computed, ref } from 'vue'
import { ArrowUpDown, ChevronDown as ChevronDownIcon, Download, LockKeyhole as LockIcon, MoreHorizontal, Search, UserPlus, UserRoundCheck } from 'lucide-vue-next'
import { store, toggleStudent } from '../demo-store'

const query = ref('')
const filtered = computed(() => store.students.filter((student) => `${student.name} ${student.email} ${student.school}`.toLowerCase().includes(query.value.toLowerCase())))
function statusClass(value) { return value === 'Aktif' ? 'status-terbit' : value === 'Segera habis' ? 'status-review' : 'status-arsip' }
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row"><div><div class="eyebrow">AKUN DAN AKSES <span>/</span> MURID</div><h1>Murid</h1><p class="page-subtitle">Cari akun, lihat status akses, dan bantu proses pemulihan akun.</p></div><button class="button button-primary"><UserPlus :size="17" /> Impor / provisi murid</button></div>
    <div class="student-stat-row"><div class="student-stat"><span>Total akun</span><strong>3.106</strong><small>semua akun terdaftar</small></div><div class="student-stat"><span>Akses aktif</span><strong>2.418</strong><small><i class="status-dot"></i> 77,8% dari total</small></div><div class="student-stat"><span>Segera kedaluwarsa</span><strong>124</strong><small>dalam 7 hari ke depan</small></div><div class="student-stat"><span>Perlu bantuan</span><strong>12</strong><small>permintaan reset kredensial</small></div></div>
    <div class="content-tabs"><button class="content-tab active">Semua murid <span>{{ store.students.length }}</span></button><button class="content-tab">Akses aktif</button><button class="content-tab">Segera habis</button><button class="content-tab">Nonaktif</button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input v-model="query" placeholder="Cari nama, email, atau sekolah..." /><kbd>⌘ K</kbd></label><button class="filter-select">Status akses <ChevronDownIcon /></button><button class="button button-outline"><Download :size="16" /> Ekspor</button></div>
    <section class="panel table-panel"><div class="table-scroll"><table class="data-table student-table"><thead><tr><th><input type="checkbox" /></th><th>MURID <button class="sort-button"><ArrowUpDown :size="12" /></button></th><th>SEKOLAH</th><th>STATUS AKSES</th><th>MASA AKSES</th><th>PERCOBAAN</th><th></th></tr></thead><tbody><tr v-for="student in filtered" :key="student.id"><td><input type="checkbox" /></td><td><div class="student-name-cell"><span class="avatar" :class="`student-${student.id.slice(-1)}`">{{ student.name.split(' ').map((part) => part[0]).join('').slice(0, 2) }}</span><span><b>{{ student.name }}</b><small>{{ student.email }}</small></span></div></td><td class="school-cell">{{ student.school }}</td><td><span :class="['status-tag', statusClass(student.access)]"><i></i>{{ student.access }}</span></td><td><span class="expiry-cell">{{ student.expires }}</span></td><td class="attempt-cell">{{ student.attempts }}</td><td><button class="icon-button tiny" :title="student.access === 'Nonaktif' ? 'Aktifkan' : 'Nonaktifkan'" @click="toggleStudent(student)"><UserRoundCheck v-if="student.access === 'Nonaktif'" :size="16" /><MoreHorizontal v-else :size="16" /></button></td></tr></tbody></table></div><div class="table-footer"><span>Demo menampilkan {{ filtered.length }} dari 3.106 akun murid</span><div class="pagination"><button disabled>‹</button><button class="current-page">1</button><button>2</button><button>3</button><button>›</button></div></div></section>
    <div class="privacy-note"><LockIcon /><p><b>Privasi murid dijaga.</b> Panel tidak menyimpan kata sandi murid. Proses autentikasi berasal dari identity provider eksternal; Support hanya dapat menonaktifkan akun atau memulai reset.</p></div>
  </div>
</template>
