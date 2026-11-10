<script setup>
import { nextTick, onMounted, ref, watch } from 'vue'
import renderMathInElement from 'katex/contrib/auto-render'

const props = defineProps({ text: { type: String, default: '' } })
const element = ref(null)

async function render() {
  await nextTick()
  if (!element.value) return
  renderMathInElement(element.value, {
    delimiters: [
      { left: '$$', right: '$$', display: true },
      { left: '\\(', right: '\\)', display: false },
      { left: '$', right: '$', display: false },
    ],
    throwOnError: false,
    strict: 'ignore',
  })
}

onMounted(render)
watch(() => props.text, render)
</script>

<template><span ref="element">{{ text }}</span></template>
