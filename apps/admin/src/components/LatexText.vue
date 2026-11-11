<script setup>
import { computed } from 'vue'
import { parseMathContent, serializeMathContent } from '../math-content'

const props = defineProps({ text: { type: String, default: '' } })
const baseURL = 'https://cdn.jsdelivr.net/npm/latex.js@0.12.6/dist/'
const blocks = computed(() => parseMathContent(props.text))
const content = computed(() => serializeMathContent(blocks.value))
const containsMath = computed(() => blocks.value.some((block) => block.type === 'formula'))
const source = computed(() => `\\documentclass{article}\n\\begin{document}\n${content.value}\n\\end{document}`)
</script>

<template>
  <latex-js v-if="containsMath" :key="source" class="latex-preview" :baseURL="baseURL" hyphenate="false">{{ source }}</latex-js>
  <span v-else>{{ text }}</span>
</template>
