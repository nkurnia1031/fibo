<script setup>
import { computed, ref } from 'vue'
import { Check, RotateCcw, X } from 'lucide-vue-next'
import LatexText from './LatexText.vue'

const props = defineProps({ question: { type: Object, required: true }, mode: { type: String, default: 'preview' } })
const emit = defineEmits(['close'])
const selected = ref([])
const submitted = ref(false)
const categoryAnswers = ref((props.question.categoryStatements || []).map(() => ''))
const isTryout = computed(() => props.mode === 'try')
const exactCorrect = computed(() => {
  if (props.question.format === 'Pilihan ganda') return selected.value.length === 1 && selected.value[0] === props.question.answerKey
  if (props.question.format === 'PG kompleks · MCMA') {
    const correct = props.question.correctOptions || []
    return correct.length === selected.value.length && correct.every((answer) => selected.value.includes(answer))
  }
  const answers = props.question.categoryAnswers || []
  return answers.length === categoryAnswers.value.length && answers.every((answer, index) => answer === categoryAnswers.value[index])
})
const score = computed(() => submitted.value && exactCorrect.value ? 1 : 0)
function submit() { submitted.value = true }
function reset() { submitted.value = false; selected.value = []; categoryAnswers.value = (props.question.categoryStatements || []).map(() => '') }
</script>

<template>
  <div class="modal-backdrop" @click.self="emit('close')"><section class="modal-card question-run-modal">
    <header class="run-header"><div><span class="section-kicker">{{ isTryout ? 'UJI COBA SOAL' : 'PRATINJAU SOAL' }} · {{ question.id }} · REV {{ question.revision || 1 }}</span><h2>{{ question.title }}</h2></div><button class="modal-close" aria-label="Tutup" @click="emit('close')"><X :size="18" /></button></header>
    <div class="run-body"><div class="run-meta"><span>{{ question.format }}</span><span>{{ question.topic }}</span><span>{{ question.level }}</span><span>1 poin</span></div><div class="run-prompt"><LatexText :text="question.prompt || question.snippet || ''" /></div><img v-if="question.imageUrl" :src="question.imageUrl" class="preview-image" alt="Gambar pendukung soal" />
      <div v-if="question.format !== 'PG kompleks · Kategori'" class="run-options"><label v-for="(option, index) in question.options || []" :key="index" :class="['run-option', { selected: selected.includes(index) }]"><input :disabled="submitted || !isTryout" :type="question.format === 'Pilihan ganda' ? 'radio' : 'checkbox'" :checked="selected.includes(index)" @change="question.format === 'Pilihan ganda' ? selected = [index] : selected = selected.includes(index) ? selected.filter((item) => item !== index) : [...selected, index]" /><b>{{ String.fromCharCode(65 + index) }}</b><LatexText :text="option" /></label></div>
      <div v-else class="run-categories"><div v-for="(statement, index) in question.categoryStatements || []" :key="index" class="run-category"><LatexText :text="statement" /><select v-model="categoryAnswers[index]" :disabled="submitted || !isTryout"><option value="">Pilih jawaban</option><option>Benar</option><option>Salah</option></select></div></div>
      <div v-if="submitted" :class="['score-result', exactCorrect ? 'correct' : 'incorrect']"><div><b>{{ exactCorrect ? 'Jawaban benar' : 'Belum tepat' }} · {{ score }}/1 poin</b><span>{{ exactCorrect ? 'Seluruh jawaban sesuai kunci.' : 'Nilai penuh diberikan jika seluruh jawaban benar.' }}</span></div><Check v-if="exactCorrect" :size="19" /></div>
      <div v-if="submitted || !isTryout" class="solution-box"><b>Kunci dan pembahasan</b><p><template v-if="question.format === 'Pilihan ganda'">Jawaban: {{ String.fromCharCode(65 + (question.answerKey || 0)) }}. </template><template v-else-if="question.format === 'PG kompleks · MCMA'">Jawaban: {{ (question.correctOptions || []).map((index) => String.fromCharCode(65 + index)).join(', ') }}. </template><LatexText :text="question.explanation || 'Pembahasan belum diisi.'" /></p></div>
    </div><footer class="modal-footer run-footer"><span class="run-mode-note">{{ isTryout ? 'Mode latihan tidak menyimpan percobaan murid.' : `Status konten: ${question.status}` }}</span><div><button v-if="isTryout && submitted" class="button button-outline" @click="reset"><RotateCcw :size="14" /> Coba lagi</button><button v-if="isTryout && !submitted" class="button button-primary" @click="submit">Periksa jawaban</button><button class="button button-outline" @click="emit('close')">Tutup</button></div></footer>
  </section></div>
</template>
