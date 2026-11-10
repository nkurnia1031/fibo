<script setup>
import { ref } from 'vue'
import { ArrowRight, ChevronDown as ChevronDownIcon, Clock3, MoreHorizontal, PackageCheck, Play, Plus, Search } from 'lucide-vue-next'
import { store } from '../demo-store'

const showCreate = ref(false)
const name = ref('')
const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value)
function createPackage() {
  if (!name.value.trim()) return
  store.packages.unshift({ id: `PK-0${store.packages.length + 25}`, name: name.value, questions: 0, duration: 'Tanpa batas', attempts: 0, status: 'Draf', updated: 'Baru saja' })
  name.value = ''
  showCreate.value = false
}
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row"><div><div class="eyebrow">KONTEN PEMBELAJARAN <span>/</span> PAKET TES</div><h1>Paket tes</h1><p class="page-subtitle">Susun soal, tetapkan waktu, dan pilih revisi yang akan digunakan.</p></div><button class="button button-primary" @click="showCreate = true"><Plus :size="17" /> Buat paket tes</button></div>
    <div class="package-summary"><div><span class="summary-label">Paket terbit</span><strong>18</strong><small>untuk SMP / MTs kelas 9</small></div><div><span class="summary-label">Percobaan 30 hari</span><strong>6.482</strong><small class="trend-up">Naik 9,4% dari periode lalu</small></div><div><span class="summary-label">Rata-rata selesai</span><strong>78<span class="summary-percent">%</span></strong><small>berdasarkan 4.028 percobaan</small></div><div class="package-summary-art"><div class="art-paper"><i></i><i></i><i></i><span>∑</span></div></div></div>
    <div class="content-tabs"><button class="content-tab active">Semua paket <span>{{ store.packages.length }}</span></button><button class="content-tab">Terbit</button><button class="content-tab">Draf</button><button class="content-tab">Arsip</button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input placeholder="Cari nama paket tes..." /><kbd>⌘ K</kbd></label><button class="filter-select"><span>Semua status</span><ChevronDownIcon /></button><button class="button button-outline">Kerangka: SMP/MTs <ChevronDownIcon /></button></div>
    <div class="package-list"><article v-for="(item, index) in store.packages" :key="item.id" class="panel package-card"><div class="package-thumb" :class="`thumb-${index % 3}`"><span>{{ index === 0 ? '∑' : index === 1 ? 'x²' : '△' }}</span><i></i><i></i></div><div class="package-card-content"><div class="package-card-top"><span class="table-id">{{ item.id }} <i>· Diperbarui {{ item.updated }}</i></span><span :class="['status-tag', item.status === 'Terbit' ? 'status-terbit' : 'status-draf']"><i></i>{{ item.status }}</span></div><h2>{{ item.name }}</h2><div class="package-tags"><span><PackageCheck :size="14" /> {{ item.questions }} soal</span><span><Clock3 :size="14" /> {{ item.duration }}</span><span><Play :size="14" /> {{ formatNumber(item.attempts) }} percobaan</span></div><div class="package-card-bottom"><span class="pinned-revision"><i></i> Revisi soal dipatok</span><div><button class="icon-button tiny"><MoreHorizontal :size="17" /></button><button class="button button-small button-outline">Buka paket <ArrowRight :size="14" /></button></div></div></div></article></div>
    <div class="demo-note"><span class="note-dot"></span> Setiap paket memakai satu Jenjang dan versi kerangka. Urutan soal dipilih manual dan tetap stabil setelah diterbitkan.</div>
    <div v-if="showCreate" class="modal-backdrop" @click.self="showCreate = false"><section class="modal-card package-modal"><button class="modal-close" @click="showCreate = false">×</button><span class="section-kicker">PAKET TES BARU</span><h2>Mulai susun paket</h2><p class="modal-description">Tentukan nama dan kerangka. Soal serta timer dapat diatur setelah paket dibuat.</p><label class="field-label">Nama paket<input v-model="name" autofocus placeholder="Contoh: Simulasi TKA Matematika #5" /></label><label class="field-label">Jenjang<select><option>SMP / MTs · Kelas 9</option></select></label><label class="field-label">Versi Kerangka TKA<select><option>SMP/MTs Matematika · 2025.1</option></select></label><div class="modal-footer"><button class="button button-outline" @click="showCreate = false">Batal</button><button class="button button-primary" @click="createPackage">Buat draf <ArrowRight :size="15" /></button></div></section></div>
  </div>
</template>
