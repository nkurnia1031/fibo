<script setup>
import { computed, ref, watch } from 'vue'
import { ArrowDown, ArrowUp, Check, Plus, X } from 'lucide-vue-next'
import { store } from '../demo-store'

const props = defineProps({ mode: { type: String, default: 'create' }, packageRecord: { type: Object, default: null } })
const emit = defineEmits(['close', 'save', 'revise'])
const name = ref('')
const duration = ref(60)
const selected = ref([])
const isReadOnly = computed(() => props.mode === 'view')
const availableQuestions = computed(() => store.questions.filter((question) => question.status === 'Terbit'))
const title = computed(() => ({ create: 'Buat paket tes', edit: 'Edit draf paket', revise: 'Buat revisi paket', view: 'Detail paket tes' }[props.mode]))
const durationLabel = computed(() => Number(duration.value) > 0 ? `${duration.value} menit` : 'Tanpa batas')

watch(() => props.packageRecord, (record) => {
  name.value = record?.name || ''
  duration.value = typeof record?.duration === 'number' ? record.duration : 0
  selected.value = record?.questionItems?.map((item) => `${item.questionId}:${item.revision}`) || []
}, { immediate: true })

function toggleQuestion(key) {
  if (selected.value.includes(key)) selected.value = selected.value.filter((item) => item !== key)
  else selected.value.push(key)
}
function move(index, offset) {
  const target = index + offset
  if (target < 0 || target >= selected.value.length) return
  const reordered = [...selected.value]
  ;[reordered[index], reordered[target]] = [reordered[target], reordered[index]]
  selected.value = reordered
}
function save() {
  if (!name.value.trim() || !selected.value.length) return
  const questionItems = selected.value.map((key) => {
    const [questionId, revision] = key.split(':')
    return { questionId, revision: Number(revision) }
  })
  emit('save', { name: name.value.trim(), duration: Number(duration.value) || 0, questionItems, questions: questionItems.length, framework: 'SMP/MTs · 2025.1' })
}
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')"><section class="modal-card package-builder">
    <header class="run-header"><div><span class="section-kicker">PAKET TES · {{ packageRecord?.id || 'BARU' }}<template v-if="packageRecord"> · VERSI {{ packageRecord.version }}</template></span><h2>{{ title }}</h2></div><button class="modal-close" aria-label="Tutup" @click="emit('close')"><X :size="18" /></button></header>
    <div class="package-builder-body">
      <div class="field-two-col"><label class="field-label">Nama paket<span class="required">*</span><input v-model="name" :readonly="isReadOnly" placeholder="Contoh: Simulasi TKA Matematika #5" /></label><label class="field-label">Timer (menit)<select v-model="duration" :disabled="isReadOnly"><option :value="0">Tanpa batas waktu</option><option :value="30">30 menit</option><option :value="45">45 menit</option><option :value="60">60 menit</option><option :value="90">90 menit</option><option :value="120">120 menit</option></select></label></div>
      <div class="framework-lock"><span>Jenjang dan kerangka</span><b>SMP / MTs · Matematika · 2025.1</b><small>Satu paket terikat ke satu versi kerangka.</small></div>
      <div class="builder-columns"><section><div class="builder-heading"><div><b>Pilih soal terbit</b><small>Centang soal; hanya revisi terbit yang tersedia.</small></div><span class="count-pill">{{ availableQuestions.length }}</span></div><div class="available-question-list"><label v-for="question in availableQuestions" :key="`${question.id}:${question.revision}`" class="available-question"><input type="checkbox" :checked="selected.includes(`${question.id}:${question.revision}`)" :disabled="isReadOnly" @change="toggleQuestion(`${question.id}:${question.revision}`)" /><span><b>{{ question.title }}</b><small>{{ question.id }} · Rev {{ question.revision }} · {{ question.topic }}</small></span></label><p v-if="!availableQuestions.length" class="empty-state">Belum ada soal terbit untuk ditambahkan.</p></div></section>
        <section><div class="builder-heading"><div><b>Urutan dalam paket</b><small>{{ selected.length }} soal · {{ durationLabel }}</small></div><span class="count-pill">{{ selected.length }}</span></div><div class="selected-question-list"><div v-for="(key, index) in selected" :key="key" class="selected-question"><span class="order-number">{{ index + 1 }}</span><span class="selected-copy"><b>{{ store.questions.find((item) => item.id === key.split(':')[0] && item.revision === Number(key.split(':')[1]))?.title || key.split(':')[0] }}</b><small>{{ key.replace(':', ' · Rev ') }}</small></span><button v-if="!isReadOnly" class="icon-button tiny" title="Naikkan" :disabled="index === 0" @click="move(index, -1)"><ArrowUp :size="14" /></button><button v-if="!isReadOnly" class="icon-button tiny" title="Turunkan" :disabled="index === selected.length - 1" @click="move(index, 1)"><ArrowDown :size="14" /></button><button v-if="!isReadOnly" class="icon-button tiny danger-icon" title="Hapus dari paket" @click="toggleQuestion(key)"><X :size="14" /></button></div><p v-if="!selected.length" class="empty-state">Pilih soal dari daftar kiri. Urutan dapat diubah.</p></div></section></div>
    </div><footer class="modal-footer run-footer"><span class="run-mode-note">Setiap soal dipatok ke revisi yang dipilih.</span><div><button class="button button-outline" @click="emit('close')">{{ isReadOnly ? 'Tutup' : 'Batal' }}</button><button v-if="isReadOnly && packageRecord?.status === 'Terbit'" class="button button-primary" @click="emit('revise', packageRecord)"><Plus :size="15" /> Buat revisi</button><button v-else-if="!isReadOnly" class="button button-primary" :disabled="!name.trim() || !selected.length" @click="save"><Check :size="15" /> Simpan draf</button></div></footer>
  </section></div>
</template>
