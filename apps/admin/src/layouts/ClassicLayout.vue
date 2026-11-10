<script setup>
import { Bell, ChevronDown, CircleHelp, Search } from 'lucide-vue-next'
defineProps({ items: { type: Array, required: true }, active: { type: String, required: true } })
defineEmits(['navigate'])
</script>

<template>
  <div class="app-shell classic-shell">
    <aside class="sidebar">
      <a class="brand" href="#" @click.prevent="$emit('navigate', 'overview')">
        <span class="brand-mark">f</span><span>fibo<span class="brand-dot">.</span><small>ADMIN TKA</small></span>
      </a>
      <div class="workspace-label">RUANG KERJA</div>
      <nav class="side-nav">
        <button v-for="item in items" :key="item.id" :class="['nav-item', { active: active === item.id }]" @click="$emit('navigate', item.id)">
          <component :is="item.icon" :size="18" stroke-width="1.8" />
          <span>{{ item.label }}</span><b v-if="item.badge" class="nav-badge">{{ item.badge }}</b>
        </button>
      </nav>
      <div class="sidebar-bottom">
        <button class="nav-item"><CircleHelp :size="18" /> <span>Pusat bantuan</span></button>
        <div class="workspace-card"><span class="workspace-icon">S</span><span><b>SMP / MTs</b><small>Kelas sasaran 9</small></span><ChevronDown :size="15" /></div>
        <div class="user-card"><div class="avatar avatar-green">NA</div><span><b>Nadia Anindya</b><small>Owner</small></span><button class="dots-button" aria-label="Menu akun">···</button></div>
      </div>
    </aside>
    <div class="shell-main">
      <header class="topbar">
        <div class="breadcrumb"><span>Ruang kerja</span><span class="crumb-slash">/</span><b>Admin TKA Matematika</b></div>
        <div class="top-actions"><button class="search-trigger"><Search :size="16" /><span>Cari soal, paket, murid...</span><kbd>⌘ K</kbd></button><button class="icon-button" aria-label="Notifikasi"><Bell :size="18" /><i class="notification-dot"></i></button><div class="avatar avatar-peach">NA</div></div>
      </header>
      <main class="page-content"><slot /></main>
    </div>
  </div>
</template>
