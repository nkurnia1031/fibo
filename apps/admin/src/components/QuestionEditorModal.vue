<script setup>
import { computed, ref, watch } from 'vue'
import { Check, ImagePlus, Lightbulb, Plus, Save, X } from 'lucide-vue-next'
import { createQuestion, createQuestionRevision, updateQuestion } from '../demo-store'
import LatexText from './LatexText.vue'

const props = defineProps({ mode: { type: String, default: 'create' }, question: { type: Object, default: null } })
const emit = defineEmits(['close', 'saved'])
const format = ref('Pilihan ganda')
const title = ref('')
const prompt = ref('')
const explanation = ref('')
const misconception = ref('')
const tags = ref('')
const level = ref('Aplikasi')
const topic = ref('Bilangan')
const options = ref(['', '', '', ''])
const selectedSingle = ref(0)
const selectedMultiple = ref([])
const categories = ref([{ statement: '', answer: 'Benar' }])
const imagePreview = ref('')
const imageUrl = ref('')
const isPublishedRevision = computed(() => props.mode === 'revise')
const heading = computed(() => props.mode === 'create' ? 'Tulis soal TKA' : isPublishedRevision.value ? 'Buat revisi soal' : 'Edit soal')
const canSave = computed(() => {
  if (!title.value.trim() || !prompt.value.trim() || !explanation.value.trim()) return false
  if (format.value === 'PG kompleks · Kategori') return categories.value.length > 0 && categories.value.every((item) => item.statement.trim())
  const optionCount = options.value.filter((item) => item.trim()).length
  return optionCount >= 2 && (format.value !== 'PG kompleks · MCMA' || selectedMultiple.value.length > 0)
})

watch(() => props.question, (question) => {
  if (!question) return
  title.value = question.title || ''
  prompt.value = question.prompt || question.snippet || ''
  explanation.value = question.explanation || ''
  misconception.value = question.commonMistakes || ''
  tags.value = Array.isArray(question.tags) ? question.tags.join(', ') : question.tags || ''
  level.value = question.level || 'Aplikasi'
  topic.value = question.topic || 'Bilangan'
  format.value = question.format || 'Pilihan ganda'
  options.value = question.options?.length ? [...question.options] : ['', '', '', '']
  selectedSingle.value = question.answerKey ?? 0
  selectedMultiple.value = [...(question.correctOptions || [])]
  categories.value = question.categoryStatements?.map((statement, index) => ({ statement, answer: question.categoryAnswers?.[index] || 'Benar' })) || [{ statement: '', answer: 'Benar' }]
  imageUrl.value = question.imageUrl || ''
  imagePreview.value = question.imageUrl || ''
}, { immediate: true })

function toggleMultiple(index) {
  selectedMultiple.value = selectedMultiple.value.includes(index)
    ? selectedMultiple.value.filter((item) => item !== index)
    : [...selectedMultiple.value, index]
}
function onImageSelected(event) {
  const file = event.target.files?.[0]
  if (!file) return
  imagePreview.value = URL.createObjectURL(file)
  imageUrl.value = imagePreview.value
}
function insertFormula() {
  prompt.value += `${prompt.value ? '\n' : ''}Gunakan rumus $a^2 + b^2 = c^2$ untuk menentukan panjang sisi.`
}
function save(status = 'Draf') {
  if (!canSave.value) return
  const cleanOptions = options.value.map((item) => item.trim()).filter(Boolean)
  const payload = {
    title: title.value.trim(), prompt: prompt.value.trim(), explanation: explanation.value.trim(), commonMistakes: misconception.value.trim(), tags: tags.value.split(',').map((tag) => tag.trim()).filter(Boolean), format: format.value,
    topic: topic.value, level: level.value, options: format.value === 'PG kompleks · Kategori' ? [] : cleanOptions,
    answerKey: Math.min(selectedSingle.value, cleanOptions.length - 1),
    correctOptions: selectedMultiple.value.filter((index) => index < cleanOptions.length),
    categoryStatements: categories.value.map((item) => item.statement.trim()).filter(Boolean),
    categoryAnswers: categories.value.filter((item) => item.statement.trim()).map((item) => item.answer),
    imageUrl: imageUrl.value, status,
  }
  if (props.mode === 'revise') createQuestionRevision(props.question, payload)
  else if (props.mode === 'edit' && props.question) updateQuestion(props.question, payload)
  else createQuestion(payload)
  emit('saved')
  emit('close')
}
</script>

<template>
  <div class="modal-backdrop author-backdrop" @click.self="emit('close')">
    <section class="author-modal">
      <header class="author-header"><div><span class="section-kicker">BANK SOAL · {{ isPublishedRevision ? 'REVISI BARU' : mode === 'edit' ? 'EDIT DRAF' : 'DRAF BARU' }}</span><h2>{{ heading }}</h2><p>{{ question ? `${question.id} · Rev ${question.revision || 1}` : 'Versi 1' }} · SMP / MTs · Kerangka 2025.1</p></div><button class="modal-close" aria-label="Tutup" @click="emit('close')"><X :size="19" /></button></header>
      <div class="author-body">
        <div class="author-form">
          <label class="field-label">Judul internal<span class="required">*</span><input v-model="title" placeholder="Contoh: Menentukan luas daerah segitiga" /></label>
          <div class="field-two-col"><label class="field-label">Bentuk respons<span class="required">*</span><select v-model="format"><option>Pilihan ganda</option><option>PG kompleks · MCMA</option><option>PG kompleks · Kategori</option></select></label><label class="field-label">Level kognitif<span class="required">*</span><select v-model="level"><option>Pengetahuan</option><option>Pengetahuan dan Pemahaman</option><option>Aplikasi</option><option>Penalaran</option></select></label></div>
          <label class="field-label">Subelemen utama<span class="required">*</span><select v-model="topic"><option>Bilangan</option><option>Aljabar</option><option>Geometri dan Pengukuran</option><option>Data dan Peluang</option></select></label>
          <div class="editor-label-row"><label class="field-label">Pertanyaan<span class="required">*</span></label><div class="editor-tools"><button title="Sisipkan contoh rumus" @click="insertFormula">ƒx</button><label class="image-tool" title="Unggah gambar"><ImagePlus :size="16" /><input type="file" accept="image/png,image/jpeg,image/webp" @change="onImageSelected" /></label></div></div>
          <textarea v-model="prompt" class="prompt-input" rows="5" placeholder="Tulis pertanyaan. Gunakan notasi LaTeX, misalnya $x^2 + 3x = 10$." />
          <div v-if="imagePreview" class="image-preview"><img :src="imagePreview" alt="Pratinjau gambar soal" /><button @click="imagePreview = ''; imageUrl = ''"><X :size="14" /> Hapus gambar</button></div>
          <div class="math-hint"><Lightbulb :size="14" /> Persamaan dirender dengan LaTeX.js. Gunakan notasi matematika standar; gambar disimpan pada revisi soal.</div>
          <section class="answer-editor"><div class="subsection-heading"><b>Jawaban</b><span class="required">*</span><small>{{ format === 'Pilihan ganda' ? 'Pilih satu jawaban benar' : format === 'PG kompleks · MCMA' ? 'Pilih semua jawaban yang benar' : 'Tentukan jawaban untuk setiap pernyataan' }}</small></div><div v-if="format !== 'PG kompleks · Kategori'" class="option-list"><label v-for="(option, index) in options" :key="index" class="option-edit"><input v-if="format === 'Pilihan ganda'" v-model="selectedSingle" type="radio" :value="index" /><input v-else type="checkbox" :checked="selectedMultiple.includes(index)" @change="toggleMultiple(index)" /><span class="option-letter">{{ String.fromCharCode(65 + index) }}</span><input v-model="options[index]" :placeholder="`Tulis pilihan ${String.fromCharCode(65 + index)}`" /></label><button class="add-option" @click="options.push('')"><Plus :size="14" /> Tambah pilihan</button></div><div v-else class="category-list"><div v-for="(item, index) in categories" :key="index" class="category-edit"><input v-model="item.statement" placeholder="Pernyataan" /><select v-model="item.answer"><option>Benar</option><option>Salah</option></select><button class="icon-button tiny danger-icon" title="Hapus pernyataan" @click="categories.splice(index, 1)"><X :size="14" /></button></div><button class="add-option" @click="categories.push({ statement: '', answer: 'Benar' })"><Plus :size="14" /> Tambah pernyataan</button></div></section>
          <label class="field-label explanation-field">Pembahasan<span class="required">*</span><textarea v-model="explanation" rows="3" placeholder="Jelaskan langkah penyelesaian dan konsep yang digunakan." /></label>
          <details class="optional-fields"><summary>Informasi tambahan</summary><div class="optional-inside"><label class="field-label">Miskonsepsi umum<input v-model="misconception" placeholder="Opsional" /></label><label class="field-label">Tag tambahan<input v-model="tags" placeholder="Pisahkan tag dengan koma" /></label></div></details>
        </div>
        <aside class="author-preview"><div class="preview-top"><span class="section-kicker">PRATINJAU MURID</span><span class="preview-badge"><i></i> Real-time</span></div><div class="preview-paper"><div class="preview-item-count">SOAL · <span>1 poin</span></div><h3>{{ title || 'Judul soal akan tampil di sini' }}</h3><div class="preview-prompt"><LatexText :text="prompt || 'Tulis pertanyaan di panel kiri untuk melihat pratinjau.'" /></div><img v-if="imagePreview" class="preview-image" :src="imagePreview" alt="Gambar soal" /><div v-if="format !== 'PG kompleks · Kategori'" class="preview-options"><div v-for="(option, index) in options" :key="index" class="preview-option"><span>{{ String.fromCharCode(65 + index) }}</span><LatexText :text="option || `Pilihan ${String.fromCharCode(65 + index)}`" /></div></div><div v-else class="preview-categories"><div v-for="(item, index) in categories" :key="index"><LatexText :text="item.statement || `Pernyataan ${index + 1}`" /><span>Benar</span><span>Salah</span></div></div><div class="preview-meta"><span>{{ topic }}</span><span>{{ level }}</span></div></div><div class="preview-footer"><span>Penilaian otomatis</span><b>1 poin untuk jawaban penuh benar</b></div><div class="preview-revision"><span>Kerangka</span><b>SMP/MTs · 2025.1</b><small>{{ isPublishedRevision ? 'Revisi terbit tetap tersimpan' : 'Draf dapat diperbarui' }}</small></div></aside>
      </div>
      <footer class="author-footer"><div class="save-state"><span class="save-dot"></span> Perubahan tersimpan di demo ini</div><div><button class="button button-quiet" @click="emit('close')">Batal</button><button class="button button-outline" :disabled="!canSave" @click="save('Draf')"><Save :size="15" /> Simpan draf</button><button class="button button-primary" :disabled="!canSave" @click="save('Pemeriksaan')"><Check :size="15" /> Simpan & kirim review</button></div></footer>
    </section>
  </div>
</template>
