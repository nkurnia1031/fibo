<script setup>
import { ChevronLeft, ChevronRight, Layers3 } from 'lucide-vue-next'

defineProps({
  items: { type: Array, required: true },
  current: { type: String, required: true },
})
defineEmits(['cycle'])
const isDemoBuild = import.meta.env.VITE_DEMO_SWITCHER === 'true'
</script>

<template>
  <div v-if="isDemoBuild" class="prototype-switcher" aria-label="Pilihan tata letak demo">
    <Layers3 :size="15" />
    <button aria-label="Tata letak sebelumnya" @click="$emit('cycle', -1)"><ChevronLeft :size="16" /></button>
    <span><b>{{ current.toUpperCase() }}</b> · {{ items.find((item) => item.id === current)?.label }}</span>
    <button aria-label="Tata letak berikutnya" @click="$emit('cycle', 1)"><ChevronRight :size="16" /></button>
  </div>
</template>
