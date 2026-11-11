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
import AuthView from './components/AuthView.vue'
import { changePassword, getCurrentUser, signIn, signOut } from './api'
import { createPackageRecord, createPackageRevision, loadContent, savePackageRecord, store } from './demo-store'

const allNavItems = [
  { id: 'overview', label: 'Ringkasan', icon: LayoutDashboard },
  { id: 'questions', label: 'Bank soal', icon: FileQuestion },
  { id: 'packages', label: 'Paket tes', icon: Package },
  { id: 'students', label: 'Murid', icon: UsersRound },
  { id: 'staff', label: 'Admin & staf', icon: UserCog },
  { id: 'finance', label: 'Langganan & pembayaran', icon: CreditCard },
  { id: 'reports', label: 'Analitik', icon: BarChart3 },
]
const roleSections = {
  Owner: ['overview', 'questions', 'packages', 'students', 'staff', 'finance', 'reports'],
  Admin: ['overview', 'questions', 'packages', 'students', 'finance', 'reports'],
  'Penulis Konten': ['overview', 'questions', 'packages'],
  Reviewer: ['overview', 'questions', 'packages'],
  Finance: ['overview', 'finance', 'reports'],
  Support: ['overview', 'students'],
}
const variants = [
  { id: 'desk', label: 'Meja kerja' },
  { id: 'flow', label: 'Operasional' },
  { id: 'focus', label: 'Fokus konten' },
]

const authStatus = ref('loading')
const authError = ref('')
const authBusy = ref(false)
const currentUser = ref(null)
const section = ref('overview')
const questionEditor = ref(null)
const questionRun = ref(null)
const packageDialog = ref(null)
const variant = ref(new URLSearchParams(window.location.search).get('variant') || 'desk')
const navItems = computed(() => allNavItems
  .filter((item) => (roleSections[currentUser.value?.role] || []).includes(item.id))
  .map((item) => item.id === 'questions' ? { ...item, badge: String(store.questions.length) } : item))
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

async function loadForUser(user) {
  currentUser.value = user
  if (user.mustChangePassword) {
    authStatus.value = 'password'
    return
  }
  try {
    await loadContent(user)
    if (!(roleSections[user.role] || []).includes(section.value)) section.value = roleSections[user.role]?.[0] || 'overview'
    authStatus.value = 'ready'
    setVariant(variant.value)
  } catch (error) {
    authError.value = error.message
    authStatus.value = 'error'
  }
}

async function initialize() {
  authError.value = ''
  try {
    const response = await getCurrentUser()
    await loadForUser(response.data)
  } catch (error) {
    if (error.status === 401) {
      authStatus.value = 'login'
      authError.value = ''
    } else {
      authStatus.value = 'error'
      authError.value = error.message
    }
  }
}

async function login(credentials) {
  authBusy.value = true
  authError.value = ''
  try {
    const response = await signIn(credentials)
    await loadForUser({ ...response.data, mustChangePassword: response.mustChangePassword })
  } catch (error) {
    authError.value = error.message
  } finally {
    authBusy.value = false
  }
}

async function updatePassword(payload) {
  authBusy.value = true
  authError.value = ''
  try {
    const user = await changePassword(payload)
    await loadForUser(user.data || user)
  } catch (error) {
    authError.value = error.message
  } finally {
    authBusy.value = false
  }
}

async function logout() {
  await signOut().catch(() => {})
  currentUser.value = null
  authStatus.value = 'login'
  authError.value = ''
  questionEditor.value = null
  questionRun.value = null
  packageDialog.value = null
}

function onSessionExpired() {
  currentUser.value = null
  authStatus.value = 'login'
  authError.value = 'Sesi berakhir. Masuk lagi untuk melanjutkan.'
}

function openQuestionEditor(payload = {}) {
  questionEditor.value = { mode: payload.mode || 'create', question: payload.question || null }
}
function openPackageEditor(mode = 'create', packageRecord = null) {
  packageDialog.value = { mode, packageRecord }
}
async function savePackage(payload) {
  try {
    if (packageDialog.value.mode === 'create') await createPackageRecord(payload)
    else await savePackageRecord(packageDialog.value.packageRecord, payload)
    packageDialog.value = null
  } catch (error) {
    window.alert(error.message)
  }
}
async function revisePackage(packageRecord) {
  try {
    const revision = await createPackageRevision(packageRecord, {
      name: packageRecord.name,
      duration: packageRecord.duration,
      questionItems: packageRecord.questionItems.map((item) => ({ ...item })),
    })
    openPackageEditor('edit', revision)
  } catch (error) {
    window.alert(error.message)
  }
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
  if (target instanceof HTMLElement && target.matches('input, textarea, select, [contenteditable="true"]')) return
  cycleVariant(event.key === 'ArrowRight' ? 1 : -1)
}

onMounted(() => {
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('fibo-session-expired', onSessionExpired)
  initialize()
})
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('fibo-session-expired', onSessionExpired)
})
</script>

<template>
  <main v-if="authStatus === 'loading'" class="auth-loading"><span class="loading-spinner"></span> Menghubungkan ke API Fibo…</main>
  <AuthView v-else-if="authStatus === 'login'" mode="login" :busy="authBusy" :error="authError" @login="login" />
  <AuthView v-else-if="authStatus === 'password'" mode="password" :user="currentUser" :busy="authBusy" :error="authError" @change-password="updatePassword" />
  <AuthView v-else-if="authStatus === 'error'" mode="error" :error="authError" @retry="initialize" />
  <template v-else>
    <component :is="layout" :items="navItems" :active="section" @navigate="section = $event">
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

    <button class="auth-session-control" type="button" @click="logout"><span>{{ currentUser.name }}</span><b>Keluar</b></button>
    <QuestionEditorModal v-if="questionEditor" :key="`${questionEditor.mode}-${questionEditor.question?.id || 'new'}-${questionEditor.question?.revision || 0}`" :mode="questionEditor.mode" :question="questionEditor.question" @close="questionEditor = null" />
    <QuestionPreviewModal v-if="questionRun" :question="questionRun.question" :mode="questionRun.mode" @close="questionRun = null" />
    <PackageEditorModal v-if="packageDialog" :key="`${packageDialog.mode}-${packageDialog.packageRecord?.id || 'new'}-${packageDialog.packageRecord?.version || ''}`" :mode="packageDialog.mode" :package-record="packageDialog.packageRecord" @close="packageDialog = null" @save="savePackage" @revise="revisePackage" />
    <PrototypeSwitcher :items="variants" :current="variant" @cycle="cycleVariant" />
  </template>
</template>
