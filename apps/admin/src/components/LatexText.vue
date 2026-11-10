<script setup>
import { computed } from 'vue'

const props = defineProps({ text: { type: String, default: '' } })
const baseURL = 'https://cdn.jsdelivr.net/npm/latex.js@0.12.6/dist/'
const containsMath = computed(() => /\$|\\\(|\\\[/.test(props.text))
const source = computed(() => `\\documentclass{article}\n\\begin{document}\n${props.text}\n\\end{document}`)
</script>

<template>
  <latex-js v-if="containsMath" :key="source" class="latex-preview" :baseURL="baseURL" hyphenate="false">{{ source }}</latex-js>
  <span v-else>{{ text }}</span>
</template>
