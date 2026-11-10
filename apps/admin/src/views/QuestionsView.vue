<script setup>
import { computed, ref } from 'vue'
import { Archive, ArrowRight, Download, Eye, FilePlus2, FileQuestion as FileQuestionIcon, Filter, Pencil, Play, Search, SlidersHorizontal, Trash2 } from 'lucide-vue-next'
import { deleteDraftQuestion, nextQuestionStatus, store } from '../demo-store'
import LatexText from '../components/LatexText.vue'

const emit = defineEmits(['new-question', 'edit-question', 'preview-question', 'try-question'])
const query = ref('')
const selectedStatus = ref('Semua status')
const statuses = ['Semua status', 'Draf', 'Pemeriksaan', 'Disetujui', 'Terbit', 'Arsip']
const filteredQuestions = computed(() => store.questions.filter((question) => {
  const matchesQuery = `${question.id} ${question.title} ${question.topic}`.toLowerCase().includes(query.value.toLowerCase())
  const matchesStatus = selectedStatus.value === 'Semua status' || question.status === selectedStatus.value
  return matchesQuery && matchesStatus
}))

function nextAction(status) {
  return ({ Draf: 'Kirim review', Pemeriksaan: 'Setujui', Disetujui: 'Terbitkan' })[status]
}
function statusClass(value) { return `status-${value.toLowerCase().replaceAll(' ', '-')}` }
function removeDraft(question) {
  if (window.confirm(`Hapus draf “${question.title}”? Draf yang belum dipakai paket dapat dihapus permanen.`)) deleteDraftQuestion(question)
}
function requestChanges(question) { question.status = 'Draf'; question.updated = 'Baru saja' }
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row"><div><div class="eyebrow">KONTEN PEMBELAJARAN <span>/</span> BANK SOAL</div><h1>Bank soal</h1><p class="page-subtitle">Buat, tinjau, pratinjau, dan uji soal sebelum masuk ke paket tes.</p></div><button class="button button-primary" @click="emit('new-question')"><FilePlus2 :size="17" /> Tulis soal</button></div>
    <div class="content-tabs"><button class="content-tab active">Semua soal <span>{{ store.questions.length }}</span></button><button class="content-tab">Perlu pemeriksaan <span class="tab-count">{{ store.questions.filter((item) => item.status === 'Pemeriksaan').length }}</span></button><button class="content-tab">Terbit</button><button class="content-tab">Arsip</button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input v-model="query" placeholder="Cari judul, ID, atau subelemen..." /><kbd>⌘ K</kbd></label><label class="filter-select"><Filter :size="15" /><select v-model="selectedStatus"><option v-for="status in statuses" :key="status">{{ status }}</option></select></label><button class="button button-outline"><SlidersHorizontal :size="16" /> Filter</button><button class="icon-button subtle"><Download :size="17" /></button></div>
    <section class="panel table-panel"><div class="table-scroll"><table class="data-table question-table"><thead><tr><th class="checkbox-col"><input type="checkbox" /></th><th>SOAL</th><th>FORMAT</th><th>LEVEL</th><th>STATUS</th><th>DIPERBARUI</th><th>AKSI</th></tr></thead><tbody><tr v-for="question in filteredQuestions" :key="`${question.id}-${question.revision}`"><td><input type="checkbox" /></td><td><div class="table-primary"><span class="table-id">{{ question.id }} <i>· Rev {{ question.revision }}</i></span><b><LatexText :text="question.title" /></b><small>{{ question.topic }} <span>·</span> Oleh {{ question.author }}</small></div></td><td><span class="format-cell"><FileQuestionIcon :size="14" />{{ question.format }}</span></td><td><span class="level-label">{{ question.level }}</span></td><td><span :class="['status-tag', statusClass(question.status)]"><i></i>{{ question.status }}</span></td><td class="muted-cell">{{ question.updated }}</td><td class="question-actions"><button class="icon-button tiny" title="Pratinjau" @click="emit('preview-question', question)"><Eye :size="15" /></button><button class="icon-button tiny" title="Uji coba" @click="emit('try-question', question)"><Play :size="15" /></button><button v-if="question.status !== 'Arsip'" class="icon-button tiny" :title="question.status === 'Terbit' ? 'Buat revisi baru' : 'Edit'" @click="emit('edit-question', { question, mode: question.status === 'Terbit' ? 'revise' : 'edit' })"><Pencil :size="15" /></button><button v-if="nextAction(question.status)" class="inline-action" @click="nextQuestionStatus(question)">{{ nextAction(question.status) }} <ArrowRight :size="13" /></button><button v-if="question.status === 'Pemeriksaan'" class="text-button" @click="requestChanges(question)">Minta perbaikan</button><button v-if="question.status === 'Terbit'" class="icon-button tiny" title="Arsipkan" @click="nextQuestionStatus(question)"><Archive :size="15" /></button><button v-if="question.status === 'Draf'" class="icon-button tiny danger-icon" title="Hapus draf" @click="removeDraft(question)"><Trash2 :size="15" /></button></td></tr><tr v-if="filteredQuestions.length === 0"><td colspan="7" class="empty-state">Tidak ada soal yang cocok dengan pencarian ini.</td></tr></tbody></table></div><div class="table-footer"><span>Menampilkan <b>{{ filteredQuestions.length }}</b> dari <b>{{ store.questions.length }}</b> revisi demo</span><div class="pagination"><button disabled>‹</button><button class="current-page">1</button><button>2</button><button>3</button><button>›</button></div></div></section>
    <div class="info-callout"><div class="callout-mark">i</div><p><b>CRUD mengikuti riwayat soal.</b> Draf bisa diedit atau dihapus. Revisi terbit tidak diubah; buat revisi baru. Arsip tetap menyimpan kaitan ke paket dan hasil lama.</p><span class="latex-engine-label">Persamaan dirender dengan LaTeX.js</span></div>
  </div>
</template>
