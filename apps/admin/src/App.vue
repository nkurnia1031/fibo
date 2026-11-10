<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import {
  BarChart3, CreditCard, FileQuestion, LayoutDashboard,
  Package, UsersRound,
} from 'lucide-vue-next'
import ClassicLayout from './layouts/ClassicLayout.vue'
import TopbarLayout from './layouts/TopbarLayout.vue'
import RailLayout from './layouts/RailLayout.vue'
import OverviewView from './views/OverviewView.vue'
import QuestionsView from './views/QuestionsView.vue'
import PackagesView from './views/PackagesView.vue'
import StudentsView from './views/StudentsView.vue'
import FinanceView from './views/FinanceView.vue'
import ReportsView from './views/ReportsView.vue'
import QuestionEditorModal from './components/QuestionEditorModal.vue'
import PrototypeSwitcher from './components/PrototypeSwitcher.vue'

const navItems = [
  { id: 'overview', label: 'Ringkasan', icon: LayoutDashboard },
  { id: 'questions', label: 'Bank soal', icon: FileQuestion, badge: '8' },
  { id: 'packages', label: 'Paket tes', icon: Package },
  { id: 'students', label: 'Murid', icon: UsersRound },
  { id: 'finance', label: 'Langganan & pembayaran', icon: CreditCard },
  { id: 'reports', label: 'Analitik', icon: BarChart3 },
]

const section = ref('overview')
const showQuestionEditor = ref(false)
const variants = [
  { id: 'desk', label: 'Meja kerja' },
  { id: 'flow', label: 'Operasional' },
  { id: 'focus', label: 'Fokus konten' },
]
const variant = ref(new URLSearchParams(window.location.search).get('variant') || 'desk')
const layout = computed(() => ({ desk: ClassicLayout, flow: TopbarLayout, focus: RailLayout }[variant.value] || ClassicLayout))
const view = computed(() => ({
  overview: OverviewView,
  questions: QuestionsView,
  packages: PackagesView,
  students: StudentsView,
  finance: FinanceView,
  reports: ReportsView,
}[section.value] || OverviewView))

function setVariant(next) {
  const safeVariant = variants.some((item) => item.id === next) ? next : 'desk'
  variant.value = safeVariant
  const url = new URL(window.location.href)
  url.searchParams.set('variant', safeVariant)
  window.history.replaceState({}, '', url)
}

function cycleVariant(direction) {
  const current = variants.findIndex((item) => item.id === variant.value)
  const next = (current + direction + variants.length) % variants.length
  setVariant(variants[next].id)
}

function onKeydown(event) {
  if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return
  const target = event.target
  if (target instanceof HTMLElement && (target.matches('input, textarea, select, [contenteditable="true"]'))) return
  cycleVariant(event.key === 'ArrowRight' ? 1 : -1)
}

onMounted(() => {
  setVariant(variant.value)
  window.addEventListener('keydown', onKeydown)
})
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
  <component
    :is="layout"
    :items="navItems"
    :active="section"
    @navigate="section = $event"
  >
    <component :is="view" @new-question="showQuestionEditor = true" />
  </component>

  <QuestionEditorModal v-if="showQuestionEditor" @close="showQuestionEditor = false" />
  <PrototypeSwitcher
    :items="variants"
    :current="variant"
    @cycle="cycleVariant"
  />
</template>
