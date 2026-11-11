<script setup>
import { computed, ref } from 'vue'
import { LockKeyhole, LogIn, ShieldCheck } from 'lucide-vue-next'

const props = defineProps({ mode: { type: String, default: 'login' }, user: { type: Object, default: null }, error: { type: String, default: '' }, busy: { type: Boolean, default: false } })
const emit = defineEmits(['login', 'change-password', 'retry'])
const email = ref('')
const password = ref('')
const currentPassword = ref('')
const newPassword = ref('')
const confirmation = ref('')
const isPasswordMode = computed(() => props.mode === 'password')

function submit() {
  if (isPasswordMode.value) {
    emit('change-password', { currentPassword: currentPassword.value, password: newPassword.value, password_confirmation: confirmation.value })
  } else {
    emit('login', { email: email.value, password: password.value })
  }
}
</script>

<template>
  <main class="auth-screen">
    <form class="auth-card" @submit.prevent="mode === 'error' ? emit('retry') : submit()">
      <div class="auth-brand"><span><ShieldCheck :size="22" /></span><b>FIBO</b><small>ADMIN TKA MATEMATIKA</small></div>
      <div class="auth-heading"><span class="section-kicker">{{ isPasswordMode ? 'AKUN STAF' : 'RUANG ADMIN' }}</span><h1>{{ mode === 'error' ? 'API belum tersedia' : isPasswordMode ? 'Ganti kata sandi sementara' : 'Masuk ke Fibo' }}</h1><p>{{ mode === 'error' ? 'Koneksi ke backend belum berhasil. Coba muat ulang sesi.' : isPasswordMode ? `Halo ${user?.name || ''}. Buat kata sandi baru sebelum melanjutkan.` : 'Gunakan akun admin untuk mengelola bank soal dan paket tes.' }}</p></div>
      <template v-if="mode === 'login'">
        <label class="field-label">Email<input v-model="email" type="email" autocomplete="username" required placeholder="nama@fibo.id" /></label>
        <label class="field-label">Kata sandi<input v-model="password" type="password" autocomplete="current-password" required placeholder="Kata sandi" /></label>
      </template>
      <template v-else-if="isPasswordMode">
        <label class="field-label">Kata sandi sementara<input v-model="currentPassword" type="password" autocomplete="current-password" required /></label>
        <label class="field-label">Kata sandi baru<input v-model="newPassword" type="password" autocomplete="new-password" minlength="12" required /></label>
        <label class="field-label">Ulangi kata sandi baru<input v-model="confirmation" type="password" autocomplete="new-password" minlength="12" required /></label>
        <small class="auth-hint"><LockKeyhole :size="14" /> Minimal 12 karakter.</small>
      </template>
      <p v-if="error" class="auth-error" role="alert">{{ error }}</p>
      <button class="button button-primary auth-submit" :disabled="busy" type="submit">{{ busy ? 'Memproses…' : mode === 'error' ? 'Coba lagi' : isPasswordMode ? 'Simpan kata sandi' : 'Masuk' }}<LogIn v-if="!busy && mode === 'login'" :size="16" /></button>
    </form>
  </main>
</template>
