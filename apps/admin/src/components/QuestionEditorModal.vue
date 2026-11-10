<script setup>
import { computed, ref } from 'vue'
import { ArrowRight, Check, ChevronDown, ImagePlus, Lightbulb, Plus, Save, X } from 'lucide-vue-next'
import { addQuestion } from '../demo-store'
import MathText from './MathText.vue'

const emit = defineEmits(['close'])
const format = ref('Pilihan ganda')
const title = ref('')
const prompt = ref('')
const explanation = ref('')
const level = ref('Aplikasi')
const topic = ref('Bilangan')
const options = ref(['', '', '', ''])
const selectedSingle = ref(0)
const selectedMultiple = ref([0, 2])
const categories = ref([
  { statement: 'Pernyataan pertama benar', answer: 'Benar' },
  { statement: 'Pernyataan kedua benar', answer: 'Salah' },
])
const imagePreview = ref('')
const canSave = computed(() => title.value.trim() && prompt.value.trim() && explanation.value.trim())

function addOption() { options.value.push('') }
function toggleMultiple(index) {
  selectedMultiple.value = selectedMultiple.value.includes(index)
    ? selectedMultiple.value.filter((item) => item !== index)
    : [...selectedMultiple.value, index]
}
function onImageSelected(event) {
  const file = event.target.files?.[0]
  if (file) imagePreview.value = URL.createObjectURL(file)
}
function insertFormula() {
  prompt.value += `${prompt.value ? '\n' : ''}Gunakan rumus $a^2 + b^2 = c^2$ untuk menentukan panjang sisi.`
}
function saveDraft(status = 'Draf') {
  if (!canSave.value) return
  addQuestion({
    title: title.value,
    snippet: prompt.value,
    format: format.value === 'Pilihan ganda' ? 'Pilihan ganda' : format.value === 'PG kompleks · MCMA' ? 'PG kompleks · MCMA' : 'PG kompleks · Kategori',
    topic: topic.value,
    level: level.value,
    status,
  })
  emit('close')
}
</script>

<template>
  <div class="modal-backdrop author-backdrop" @click.self="emit('close')">
    <section class="author-modal">
      <header class="author-header"><div><span class="section-kicker">BANK SOAL · DRAF BARU</span><h2>Tulis soal TKA</h2><p>Versi 1 · SMP / MTs · Kerangka 2025.1</p></div><button class="modal-close" aria-label="Tutup" @click="emit('close')"><X :size="19" /></button></header>
      <div class="author-body">
        <div class="author-form">
          <label class="field-label">Judul internal<span class="required">*</span><input v-model="title" placeholder="Contoh: Menentukan luas daerah segitiga" /></label>
          <div class="field-two-col"><label class="field-label">Bentuk respons<span class="required">*</span><select v-model="format"><option>Pilihan ganda</option><option>PG kompleks · MCMA</option><option>PG kompleks · Kategori</option></select></label><label class="field-label">Level kognitif<span class="required">*</span><select v-model="level"><option>Pengetahuan dan Pemahaman</option><option>Aplikasi</option><option>Penalaran</option></select></label></div>
          <label class="field-label">Subelemen utama<span class="required">*</span><select v-model="topic"><option>Bilangan</option><option>Aljabar</option><option>Geometri dan Pengukuran</option><option>Data dan Peluang</option></select></label>
          <div class="editor-label-row"><label class="field-label">Pertanyaan<span class="required">*</span></label><div class="editor-tools"><button title="Tambah rumus" @click="insertFormula">ƒx</button><label class="image-tool" title="Unggah gambar"><ImagePlus :size="16" /><input type="file" accept="image/png,image/jpeg,image/webp" @change="onImageSelected" /></label></div></div>
          <textarea v-model="prompt" class="prompt-input" rows="5" placeholder="Tulis pertanyaan. Gunakan $...$ untuk rumus, misalnya $x^2 + 3x = 10$." />
          <div v-if="imagePreview" class="image-preview"><img :src="imagePreview" alt="Pratinjau gambar soal" /><button @click="imagePreview = ''"><X :size="14" /> Hapus gambar</button></div>
          <div class="math-hint"><Lightbulb :size="14" /> Notasi LaTeX dapat ditulis sebagai <code>$x^2$</code> atau <code>\(x^2\)</code>. Gambar akan disimpan bersama revisi soal.</div>
          <section class="answer-editor"><div class="subsection-heading"><b>Jawaban</b><span class="required">*</span><small>{{ format === 'Pilihan ganda' ? 'Pilih satu jawaban benar' : format === 'PG kompleks · MCMA' ? 'Pilih semua jawaban yang benar' : 'Tentukan jawaban untuk setiap pernyataan' }}</small></div><div v-if="format !== 'PG kompleks · Kategori'" class="option-list"><label v-for="(option, index) in options" :key="index" class="option-edit"><input v-if="format === 'Pilihan ganda'" v-model="selectedSingle" type="radio" :value="index" /><input v-else type="checkbox" :checked="selectedMultiple.includes(index)" @change="toggleMultiple(index)" /><span class="option-letter">{{ String.fromCharCode(65 + index) }}</span><input v-model="options[index]" :placeholder="`Tulis pilihan ${String.fromCharCode(65 + index)}`" /></label><button class="add-option" @click="addOption"><Plus :size="14" /> Tambah pilihan</button></div><div v-else class="category-list"><div v-for="(item, index) in categories" :key="index" class="category-edit"><input v-model="item.statement" /><select v-model="item.answer"><option>Benar</option><option>Salah</option></select></div><button class="add-option" @click="categories.push({ statement: '', answer: 'Benar' })"><Plus :size="14" /> Tambah pernyataan</button></div></section>
          <label class="field-label explanation-field">Pembahasan<span class="required">*</span><textarea v-model="explanation" rows="3" placeholder="Jelaskan langkah penyelesaian dan konsep yang digunakan." /></label>
          <details class="optional-fields"><summary>Informasi tambahan <ChevronDown :size="14" /></summary><div class="optional-inside"><label class="field-label">Miskonsepsi umum<input placeholder="Opsional" /></label><label class="field-label">Tag tambahan<input placeholder="Misalnya: soal cerita, HOTS" /></label></div></details>
        </div>
        <aside class="author-preview"><div class="preview-top"><span class="section-kicker">PRATINJAU MURID</span><span class="preview-badge"><i></i> Real-time</span></div><div class="preview-paper"><div class="preview-item-count">SOAL BARU <span>· 1 poin</span></div><h3>{{ title || 'Judul soal akan tampil di sini' }}</h3><div class="preview-prompt"><MathText :text="prompt || 'Tulis pertanyaan di panel kiri untuk melihat pratinjau.'" /></div><img v-if="imagePreview" class="preview-image" :src="imagePreview" alt="Gambar soal" /><div v-if="format !== 'PG kompleks · Kategori'" class="preview-options"><div v-for="(option, index) in options" :key="index" class="preview-option"><span>{{ String.fromCharCode(65 + index) }}</span><MathText :text="option || `Pilihan ${String.fromCharCode(65 + index)}`" /></div></div><div v-else class="preview-categories"><div v-for="(item, index) in categories" :key="index"><MathText :text="item.statement || `Pernyataan ${index + 1}`" /><span>Benar</span><span>Salah</span></div></div><div class="preview-meta"><span>{{ topic }}</span><span>{{ level }}</span></div></div><div class="preview-footer"><span>Penilaian otomatis</span><b>1 poin untuk jawaban penuh benar</b></div><div class="preview-revision"><span>Terikat ke</span><b>SMP/MTs · Kerangka 2025.1</b><small>Disimpan sebagai revisi baru</small></div></aside>
      </div>
      <footer class="author-footer"><div class="save-state"><span class="save-dot"></span> Draf disimpan di demo ini</div><div><button class="button button-quiet" @click="emit('close')">Batal</button><button class="button button-outline" :disabled="!canSave" @click="saveDraft()"><Save :size="15" /> Simpan draf</button><button class="button button-primary" :disabled="!canSave" @click="saveDraft('Pemeriksaan')"><Check :size="15" /> Simpan & kirim review</button></div></footer>
    </section>
  </div>
</template>
