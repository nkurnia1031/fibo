<script setup>
import { computed, ref, watch } from 'vue'
import LatexText from './LatexText.vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  formula: { type: String, default: '' },
  display: { type: Boolean, default: false },
  editing: { type: Boolean, default: false },
})
const emit = defineEmits(['close', 'save'])
const value = ref(props.formula)
const display = ref(props.display)
const previewText = computed(() => {
  if (!value.value.trim()) return ''
  return display.value ? `\\[${value.value}\\]` : `\\(${value.value}\\)`
})

watch(() => props.formula, (formula) => { value.value = formula })
watch(() => props.display, (isDisplay) => { display.value = isDisplay })

function insertTemplate(template) {
  value.value += template
}

function save() {
  if (!value.value.trim()) return
  emit('save', { value: value.value.trim(), display: display.value })
}
</script>

<template>
  <Teleport to="body">
    <div class="latex-formula-backdrop" @click.self="emit('close')">
      <section class="latex-formula-modal" role="dialog" aria-modal="true" aria-labelledby="latex-formula-title">
        <header class="latex-formula-header">
          <div>
            <span class="section-kicker">KOMPONEN MATEMATIKA</span>
            <h2 id="latex-formula-title">{{ editing ? 'Edit rumus' : 'Tambah rumus' }}</h2>
            <p>Tulis ekspresi, lalu cek hasil render sebelum memasukkannya.</p>
          </div>
          <button class="modal-close" type="button" aria-label="Tutup modal rumus" @click="emit('close')"><X :size="18" /></button>
        </header>

        <div class="latex-formula-body">
          <label class="latex-formula-input-label" for="latex-formula-input">Rumus LaTeX</label>
          <textarea
            id="latex-formula-input"
            v-model="value"
            autofocus
            rows="4"
            placeholder="Contoh: \\frac{-b + \\sqrt{b^2-4ac}}{2a}"
          />
          <div class="latex-formula-snippets" aria-label="Contoh rumus cepat">
            <span>Sisipkan contoh:</span>
            <button type="button" @click="insertTemplate('\\frac{}{}')">Pecahan</button>
            <button type="button" @click="insertTemplate('\\sqrt{}')">Akar</button>
            <button type="button" @click="insertTemplate('^{}')">Pangkat</button>
            <button type="button" @click="insertTemplate('\\pi')">π</button>
          </div>
          <label class="latex-display-toggle"><input v-model="display" type="checkbox" /> Tampilkan sebagai persamaan tersendiri</label>
          <section class="latex-formula-preview-panel" aria-label="Preview rumus">
            <div class="latex-formula-preview-heading"><b>Preview</b><span>Dirender dengan LaTeX.js</span></div>
            <div v-if="previewText" class="latex-formula-preview" aria-label="Hasil render rumus"><LatexText :text="previewText" /></div>
            <div v-else class="latex-formula-preview-empty">Preview rumus akan tampil di sini.</div>
          </section>
        </div>

        <footer class="latex-formula-footer">
          <button class="button button-quiet" type="button" @click="emit('close')">Batal</button>
          <button class="button button-primary" type="button" :disabled="!value.trim()" @click="save">{{ editing ? 'Simpan perubahan' : 'Tambahkan rumus' }}</button>
        </footer>
      </section>
    </div>
  </Teleport>
</template>
