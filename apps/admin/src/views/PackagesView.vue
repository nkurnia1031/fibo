<script setup>
import { computed, ref } from 'vue'
import { Archive, ArrowRight, Clock3, Eye, PackageCheck, Pencil, Play, Plus, Search, Trash2 } from 'lucide-vue-next'
import { deleteDraftPackage, store, transitionPackage } from '../demo-store'

const emit = defineEmits(['create-package', 'edit-package', 'view-package', 'revise-package'])
const query = ref('')
const status = ref('Semua status')
const statuses = ['Semua status', 'Draf', 'Pemeriksaan', 'Disetujui', 'Terbit', 'Arsip']
const filteredPackages = computed(() => store.packages.filter((item) => (`${item.id} ${item.name}`).toLowerCase().includes(query.value.toLowerCase()) && (status.value === 'Semua status' || item.status === status.value)))
const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value || 0)
const nextStatus = (value) => ({ Draf: 'Pemeriksaan', Pemeriksaan: 'Disetujui', Disetujui: 'Terbit' })[value]
async function remove(item) {
  if (!window.confirm(`Hapus draf “${item.name}”?`)) return
  try { await deleteDraftPackage(item) } catch (error) { window.alert(error.message) }
}
async function changeStatus(item, next) {
  try { await transitionPackage(item, next) } catch (error) { window.alert(error.message) }
}
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row"><div><div class="eyebrow">KONTEN PEMBELAJARAN <span>/</span> PAKET TES</div><h1>Paket tes</h1><p class="page-subtitle">Susun soal, tetapkan waktu, dan pilih revisi soal yang digunakan.</p></div><button class="button button-primary" @click="emit('create-package')"><Plus :size="17" /> Buat paket tes</button></div>
    <div class="package-summary"><div><span class="summary-label">Paket terbit</span><strong>{{ store.packages.filter((item) => item.status === 'Terbit').length }}</strong><small>untuk SMP / MTs</small></div><div><span class="summary-label">Percobaan tercatat</span><strong>{{ formatNumber(store.packages.reduce((sum, item) => sum + item.attempts, 0)) }}</strong><small>seluruh paket demo</small></div><div><span class="summary-label">Paket dalam draf</span><strong>{{ store.packages.filter((item) => item.status === 'Draf').length }}</strong><small>siap disunting</small></div><div class="package-summary-art"><div class="art-paper"><i></i><i></i><i></i><span>∑</span></div></div></div>
    <div class="content-tabs"><button class="content-tab active">Semua paket <span>{{ store.packages.length }}</span></button><button class="content-tab">Terbit <span>{{ store.packages.filter((item) => item.status === 'Terbit').length }}</span></button><button class="content-tab">Draf <span>{{ store.packages.filter((item) => item.status === 'Draf').length }}</span></button><button class="content-tab">Arsip <span>{{ store.packages.filter((item) => item.status === 'Arsip').length }}</span></button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input v-model="query" placeholder="Cari nama paket tes..." /></label><label class="filter-select"><select v-model="status"><option v-for="item in statuses" :key="item">{{ item }}</option></select></label><button class="button button-outline">Kerangka: SMP/MTs 2025.1</button></div>
    <div class="package-list"><article v-for="(item, index) in filteredPackages" :key="item.id + item.version" class="panel package-card"><div class="package-thumb" :class="`thumb-${index % 3}`"><span>{{ index % 3 === 0 ? '∑' : index % 3 === 1 ? 'x²' : '△' }}</span><i></i><i></i></div><div class="package-card-content"><div class="package-card-top"><span class="table-id">{{ item.id }} · v{{ item.version }} <i>· Diperbarui {{ item.updated }}</i></span><span :class="['status-tag', `status-${item.status.toLowerCase()}`]"><i></i>{{ item.status }}</span></div><h2>{{ item.name }}</h2><div class="package-tags"><span><PackageCheck :size="14" /> {{ item.questionItems?.length ?? item.questions }} soal dipatok</span><span><Clock3 :size="14" /> {{ item.duration ? `${item.duration} menit` : 'Tanpa batas' }}</span><span><Play :size="14" /> {{ formatNumber(item.attempts) }} percobaan</span></div><div class="package-card-bottom"><span class="pinned-revision"><i></i>{{ item.framework }} · revisi soal dipatok</span><div class="package-actions"><button class="icon-button tiny" title="Lihat detail" @click="emit('view-package', item)"><Eye :size="15" /></button><button v-if="item.status === 'Draf'" class="icon-button tiny" title="Edit draf" @click="emit('edit-package', item)"><Pencil :size="15" /></button><button v-if="item.status === 'Terbit'" class="icon-button tiny" title="Buat revisi" @click="emit('revise-package', item)"><Plus :size="15" /></button><button v-if="nextStatus(item.status)" class="inline-action" @click="changeStatus(item, nextStatus(item.status))">{{ nextStatus(item.status) === 'Pemeriksaan' ? 'Kirim review' : nextStatus(item.status) === 'Disetujui' ? 'Setujui' : 'Terbitkan' }} <ArrowRight :size="13" /></button><button v-if="item.status === 'Draf'" class="icon-button tiny danger-icon" title="Hapus draf" @click="remove(item)"><Trash2 :size="15" /></button><button v-if="item.status === 'Terbit'" class="icon-button tiny" title="Arsipkan" @click="changeStatus(item, 'Arsip')"><Archive :size="15" /></button></div></div></div></article><div v-if="!filteredPackages.length" class="panel empty-state">Tidak ada paket yang cocok dengan pencarian.</div></div>
    <div class="demo-note"><span class="note-dot"></span> Paket tersimpan di database. Revisi mengunci soal dan urutannya; revisi lama tetap tersimpan.</div>
  </div>
</template>
