<script setup>
import { computed, ref } from 'vue'
import { ArrowRight, Download, FilePlus2, FileQuestion, Filter, MoreHorizontal, Search, SlidersHorizontal } from 'lucide-vue-next'
import { nextQuestionStatus, store } from '../demo-store'
import MathText from '../components/MathText.vue'

const emit = defineEmits(['new-question'])
const query = ref('')
const selectedStatus = ref('Semua status')
const statuses = ['Semua status', 'Draf', 'Pemeriksaan', 'Disetujui', 'Terbit', 'Arsip']
const filteredQuestions = computed(() => store.questions.filter((question) => {
  const matchesQuery = `${question.id} ${question.title} ${question.topic}`.toLowerCase().includes(query.value.toLowerCase())
  const matchesStatus = selectedStatus.value === 'Semua status' || question.status === selectedStatus.value
  return matchesQuery && matchesStatus
}))

function actionLabel(status) {
  return ({ Draf: 'Kirim review', Pemeriksaan: 'Setujui', Disetujui: 'Terbitkan', Terbit: 'Arsipkan' })[status]
}
function statusClass(value) { return `status-${value.toLowerCase().replaceAll(' ', '-')}` }
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row"><div><div class="eyebrow">KONTEN PEMBELAJARAN <span>/</span> BANK SOAL</div><h1>Bank soal</h1><p class="page-subtitle">Kelola soal, revisi, dan alur pemeriksaan konten TKA.</p></div><button class="button button-primary" @click="emit('new-question')"><FilePlus2 :size="17" /> Tulis soal</button></div>
    <div class="content-tabs"><button class="content-tab active">Semua soal <span>{{ store.questions.length }}</span></button><button class="content-tab">Perlu pemeriksaan <span class="tab-count">8</span></button><button class="content-tab">Terbit</button><button class="content-tab">Arsip</button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input v-model="query" placeholder="Cari judul, ID, atau subelemen..." /><kbd>⌘ K</kbd></label><label class="filter-select"><Filter :size="15" /><select v-model="selectedStatus"><option v-for="status in statuses" :key="status">{{ status }}</option></select></label><button class="button button-outline"><SlidersHorizontal :size="16" /> Filter</button><button class="icon-button subtle"><Download :size="17" /></button></div>
    <section class="panel table-panel"><div class="table-scroll"><table class="data-table question-table"><thead><tr><th class="checkbox-col"><input type="checkbox" /></th><th>SOAL</th><th>FORMAT</th><th>LEVEL</th><th>STATUS</th><th>DIPERBARUI</th><th></th></tr></thead><tbody><tr v-for="question in filteredQuestions" :key="question.id"><td><input type="checkbox" /></td><td><div class="table-primary"><span class="table-id">{{ question.id }} <i>· Rev {{ question.revision }}</i></span><b><MathText :text="question.title" /></b><small>{{ question.topic }} <span>·</span> Oleh {{ question.author }}</small></div></td><td><span class="format-cell"><FileQuestionIcon />{{ question.format }}</span></td><td><span class="level-label">{{ question.level }}</span></td><td><span :class="['status-tag', statusClass(question.status)]"><i></i>{{ question.status }}</span></td><td class="muted-cell">{{ question.updated }}</td><td class="row-actions"><button v-if="actionLabel(question.status)" class="inline-action" @click="nextQuestionStatus(question)">{{ actionLabel(question.status) }} <ArrowRight :size="13" /></button><button v-else class="icon-button tiny"><MoreHorizontal :size="16" /></button></td></tr><tr v-if="filteredQuestions.length === 0"><td colspan="7" class="empty-state">Tidak ada soal yang cocok dengan pencarian ini.</td></tr></tbody></table></div><div class="table-footer"><span>Menampilkan <b>{{ filteredQuestions.length }}</b> dari <b>{{ store.questions.length }}</b> soal contoh</span><div class="pagination"><button disabled>‹</button><button class="current-page">1</button><button>2</button><button>3</button><button>›</button></div></div></section>
    <div class="info-callout"><div class="callout-mark">i</div><p><b>Versi soal menjaga hasil lama tetap stabil.</b> Setelah terbit, konten tidak diedit. Perubahan dibuat sebagai revisi baru, lalu dipakai pada paket tes yang sesuai.</p><button class="text-button">Pelajari alur versi <ArrowRight :size="14" /></button></div>
  </div>
</template>
