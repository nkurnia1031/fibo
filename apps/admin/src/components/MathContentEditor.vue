<script setup>
import { nextTick, ref, watch } from 'vue'
import { Plus, Type } from 'lucide-vue-next'
import { parseMathContent, serializeMathContent } from '../math-content'
import LatexFormulaModal from './LatexFormulaModal.vue'
import LatexText from './LatexText.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  label: { type: String, required: true },
  placeholder: { type: String, default: 'Tulis teks soal di sini…' },
  compact: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue'])
let nextBlockId = 0
const blocks = ref(withIds(parseMathContent(props.modelValue)))
const activeTextId = ref('')
const textareas = new Map()
const formulaDialog = ref(null)

function withIds(contentBlocks) {
  return contentBlocks.map((block) => ({ ...block, id: `content-block-${++nextBlockId}` }))
}

function makeText(content = '') {
  return { type: 'text', content, id: `content-block-${++nextBlockId}` }
}

function makeFormula(value, display = false) {
  return { type: 'formula', value, display, id: `content-block-${++nextBlockId}` }
}

function serialize() {
  return serializeMathContent(blocks.value)
}

function updateModel() {
  emit('update:modelValue', serialize())
}

watch(() => props.modelValue, (value) => {
  if (value === serialize()) return
  blocks.value = withIds(parseMathContent(value))
}, { immediate: true })

function bindTextarea(blockId, element) {
  if (element) textareas.set(blockId, element)
  else textareas.delete(blockId)
}

function focusText(block) {
  activeTextId.value = block.id
}

async function focusBlock(block) {
  activeTextId.value = block.id
  await nextTick()
  const textarea = textareas.get(block.id)
  textarea?.focus()
  textarea?.setSelectionRange(block.content.length, block.content.length)
}

async function addText() {
  const activeIndex = blocks.value.findIndex((block) => block.id === activeTextId.value)
  const index = activeIndex >= 0 ? activeIndex + 1 : blocks.value.length
  const block = makeText()
  blocks.value.splice(index, 0, block)
  updateModel()
  await focusBlock(block)
}

function addFormula() {
  const active = blocks.value.find((block) => block.id === activeTextId.value && block.type === 'text')
    || [...blocks.value].reverse().find((block) => block.type === 'text')
  const textarea = active ? textareas.get(active.id) : null
  const start = textarea?.selectionStart ?? active?.content.length ?? 0
  const end = textarea?.selectionEnd ?? start
  const index = active ? blocks.value.findIndex((block) => block.id === active.id) : blocks.value.length

  formulaDialog.value = {
    editing: false,
    value: '',
    display: false,
    insertion: active ? { blockId: active.id, start, end, index } : null,
  }
}

function editFormula(block) {
  formulaDialog.value = {
    editing: true,
    blockId: block.id,
    value: block.value,
    display: block.display,
  }
}

async function saveFormula(formula) {
  const dialog = formulaDialog.value
  if (!dialog) return

  if (dialog.editing) {
    const block = blocks.value.find((item) => item.id === dialog.blockId)
    if (block) {
      block.value = formula.value
      block.display = formula.display
    }
    formulaDialog.value = null
    updateModel()
    return
  }

  const formulaBlock = makeFormula(formula.value, formula.display)
  if (dialog.insertion) {
    const index = blocks.value.findIndex((block) => block.id === dialog.insertion.blockId)
    const textBlock = blocks.value[index]
    if (index >= 0 && textBlock?.type === 'text') {
      const { start, end } = dialog.insertion
      const before = makeText(textBlock.content.slice(0, start))
      const after = makeText(textBlock.content.slice(end))
      blocks.value.splice(index, 1, before, formulaBlock, after)
      formulaDialog.value = null
      updateModel()
      await focusBlock(after)
      return
    }
  }

  const after = makeText()
  blocks.value.push(formulaBlock, after)
  formulaDialog.value = null
  updateModel()
  await focusBlock(after)
}

function removeFormula(block) {
  const index = blocks.value.findIndex((item) => item.id === block.id)
  if (index < 0) return

  const before = blocks.value[index - 1]
  const after = blocks.value[index + 1]
  if (before?.type === 'text' && after?.type === 'text') {
    before.content += after.content
    blocks.value.splice(index, 2)
    focusText(before)
  } else if (before?.type === 'text') {
    blocks.value.splice(index, 1)
    focusText(before)
  } else if (after?.type === 'text') {
    blocks.value.splice(index, 1)
    focusText(after)
  } else {
    const text = makeText()
    blocks.value.splice(index, 1, text)
    focusText(text)
  }

  updateModel()
}
</script>

<template>
  <div class="math-content-editor" :class="{ compact }" role="group" :aria-label="label">
    <div class="math-content-blocks">
      <div v-for="(block, index) in blocks" :key="block.id" class="math-content-block">
        <div v-if="block.type === 'text'" class="math-text-block">
          <textarea
            :ref="(element) => bindTextarea(block.id, element)"
            :aria-label="`${label} teks ${index + 1}`"
            :rows="compact ? 1 : 3"
            :placeholder="placeholder"
            :value="block.content"
            @focus="focusText(block)"
            @input="block.content = $event.target.value; focusText(block); updateModel()"
          />
        </div>
        <div v-else class="math-formula-block">
          <button class="math-formula-card" type="button" :aria-label="`Edit rumus ${index + 1}`" @click="editFormula(block)">
            <span class="math-formula-card-label">{{ block.display ? 'PERSAMAAN' : 'RUMUS' }} · klik untuk edit</span>
            <LatexText :text="block.display ? `\\[${block.value}\\]` : `\\(${block.value}\\)`" />
          </button>
          <button class="math-formula-remove" type="button" :aria-label="`Hapus rumus ${index + 1}`" @click="removeFormula(block)">Hapus</button>
        </div>
      </div>
    </div>

    <div class="math-content-toolbar">
      <span>Konten</span>
      <button type="button" aria-label="Tambah teks" @mousedown.prevent @click="addText"><Type :size="14" /> Teks</button>
      <button type="button" aria-label="Tambah rumus LaTeX" @mousedown.prevent @click="addFormula"><b>ƒx</b> Rumus</button>
    </div>

    <LatexFormulaModal
      v-if="formulaDialog"
      :formula="formulaDialog.value"
      :display="formulaDialog.display"
      :editing="formulaDialog.editing"
      @close="formulaDialog = null"
      @save="saveFormula"
    />
  </div>
</template>
