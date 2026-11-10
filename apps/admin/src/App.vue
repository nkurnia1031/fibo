<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import {
  BarChart3, CreditCard, FileQuestion, LayoutDashboard,
  Package, UsersRound, UserCog,
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
import StaffView from './views/StaffView.vue'
import QuestionEditorModal from './components/QuestionEditorModal.vue'
import QuestionPreviewModal from './components/QuestionPreviewModal.vue'
import PackageEditorModal from './components/PackageEditorModal.vue'
import PrototypeSwitcher from './components/PrototypeSwitcher.vue'
import { createPackageRecord, createPackageRevision, savePackageRecord } from './demo-store'

const navItems = [
  { id: 'overview', label: 'Ringkasan', icon: LayoutDashboard },
  { id: 'questions', label: 'Bank soal', icon: FileQuestion, badge: '8' },
  { id: 'packages', label: 'Paket tes', icon: Package },
  { id: 'students', label: 'Murid', icon: UsersRound },
  { id: 'staff', label: 'Admin & staf', icon: UserCog },
  { id: 'finance', label: 'Langganan & pembayaran', icon: CreditCard },
  { id: 'reports', label: 'Analitik', icon: BarChart3 },
]

const section = ref('overview')
const questionEditor = ref(null)
const questionRun = ref(null)
const packageDialog = ref(null)
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
  staff: StaffView,
  finance: FinanceView,
  reports: ReportsView,
}[section.value] || OverviewView))

function openQuestionEditor(payload = {}) {
  questionEditor.value = { mode: payload.mode || 'create', question: payload.question || null }
}
function openPackageEditor(mode = 'create', packageRecord = null) {
  packageDialog.value = { mode, packageRecord }
}
function savePackage(payload) {
  if (packageDialog.value.mode === 'create') createPackageRecord(payload)
  else savePackageRecord(packageDialog.value.packageRecord, payload)
  packageDialog.value = null
}
function revisePackage(packageRecord) {
  const revision = createPackageRevision(packageRecord, { name: packageRecord.name, questionItems: packageRecord.questionItems.map((item) => ({ ...item })) })
  openPackageEditor('edit', revision)
}

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
    <component
      :is="view"
      @new-question="openQuestionEditor()"
      @edit-question="openQuestionEditor($event)"
      @preview-question="questionRun = { question: $event, mode: 'preview' }"
      @try-question="questionRun = { question: $event, mode: 'try' }"
      @create-package="openPackageEditor()"
      @edit-package="openPackageEditor('edit', $event)"
      @view-package="openPackageEditor('view', $event)"
      @revise-package="revisePackage($event)"
    />
  </component>

  <QuestionEditorModal v-if="questionEditor" :key="`${questionEditor.mode}-${questionEditor.question?.id || 'new'}-${questionEditor.question?.revision || 0}`" :mode="questionEditor.mode" :question="questionEditor.question" @close="questionEditor = null" />
  <QuestionPreviewModal v-if="questionRun" :question="questionRun.question" :mode="questionRun.mode" @close="questionRun = null" />
  <PackageEditorModal v-if="packageDialog" :key="`${packageDialog.mode}-${packageDialog.packageRecord?.id || 'new'}`" :mode="packageDialog.mode" :package-record="packageDialog.packageRecord" @close="packageDialog = null" @save="savePackage" @revise="revisePackage" />
  <PrototypeSwitcher
    :items="variants"
    :current="variant"
    @cycle="cycleVariant"
  />
</template>
