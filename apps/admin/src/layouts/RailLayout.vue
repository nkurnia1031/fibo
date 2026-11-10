<script setup>
import { Bell, CircleHelp, Search } from 'lucide-vue-next'
defineProps({ items: { type: Array, required: true }, active: { type: String, required: true } })
defineEmits(['navigate'])
</script>

<template>
  <div class="app-shell focus-shell">
    <aside class="focus-rail">
      <a class="rail-brand" href="#" @click.prevent="$emit('navigate', 'overview')">f</a>
      <nav class="rail-nav"><button v-for="item in items" :key="item.id" :class="['rail-link', { active: active === item.id }]" :aria-label="item.label" :title="item.label" @click="$emit('navigate', item.id)"><component :is="item.icon" :size="19" stroke-width="1.8" /></button></nav>
      <div class="rail-bottom"><button class="rail-link" aria-label="Bantuan"><CircleHelp :size="18" /></button><div class="avatar avatar-peach">NA</div></div>
    </aside>
    <div class="focus-main">
      <header class="focus-header"><div><span class="focus-section">TKA MATEMATIKA <span>/</span> ADMIN</span><div class="focus-heading">Ruang kendali <span class="live-pill"><i></i> SMP / MTs</span></div></div><div class="top-actions"><button class="search-trigger"><Search :size="16" /><span>Perintah atau cari...</span><kbd>⌘ K</kbd></button><button class="icon-button"><Bell :size="18" /><i class="notification-dot"></i></button></div></header>
      <main class="focus-content"><slot /></main>
    </div>
  </div>
</template>
