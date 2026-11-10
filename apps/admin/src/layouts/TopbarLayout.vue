<script setup>
import { Bell, ChevronDown, Search } from 'lucide-vue-next'
defineProps({ items: { type: Array, required: true }, active: { type: String, required: true } })
defineEmits(['navigate'])
</script>

<template>
  <div class="app-shell flow-shell">
    <header class="flow-header">
      <a class="brand brand-horizontal" href="#" @click.prevent="$emit('navigate', 'overview')"><span class="brand-mark">f</span><span>fibo<span class="brand-dot">.</span></span><span class="header-divider"></span><span class="header-product">TKA Matematika</span></a>
      <nav class="flow-nav"><button v-for="item in items" :key="item.id" :class="['flow-nav-item', { active: active === item.id }]" @click="$emit('navigate', item.id)"><component :is="item.icon" :size="16" />{{ item.label }}</button></nav>
      <div class="flow-user"><button class="icon-button"><Bell :size="18" /><i class="notification-dot"></i></button><div class="avatar avatar-peach">NA</div><ChevronDown :size="15" /></div>
    </header>
    <main class="flow-page"><div class="flow-meta"><span class="live-pill"><i></i> SMP / MTs · Kelas 9</span><button class="search-trigger"><Search :size="16" /><span>Cari apa saja...</span><kbd>⌘ K</kbd></button></div><slot /></main>
  </div>
</template>
