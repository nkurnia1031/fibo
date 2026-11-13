<script setup>
import { computed, ref, watch } from 'vue'
import { Check, CreditCard, Download, Pencil, Plus, Search, Trash2, WalletCards, X } from 'lucide-vue-next'
import {
  createAccessOffer, createPaymentMethod, createPointBundle, deleteAccessOffer, deletePointBundle,
  deleteUnusedPaymentMethod, reviewTopUp, saveAccessOffer, savePaymentMethod, savePointBundle, adjustStudentPoints,
  saveWalletSettings, store, topUpProofUrl, loadMoreTopUps,
} from '../demo-store'

const tabs = [
  { id: 'topups', label: 'Top-up murid' },
  { id: 'bundles', label: 'Paket Poin' },
  { id: 'offers', label: 'Penawaran akses' },
  { id: 'methods', label: 'Kanal transfer' },
]
const tab = ref('topups')
const query = ref('')
const dialog = ref('')
const activeRecord = ref(null)
const saving = ref(false)
const savingSettings = ref(false)
const settingsReason = ref('Pengaturan bonus pendaftaran diperbarui oleh Finance')
const reviewDecision = ref('Confirmed')
const reviewReason = ref('')
const activeTopUp = ref(null)
const bundleForm = ref({ name: '', transferAmount: '', points: '', bonusPoints: 0, isActive: true })
const offerForm = ref({ productType: 'package', testPackageId: '', materialCode: '', title: '', description: '', pricePoints: '', validityDays: '', attemptQuota: '', isActive: true })
const methodForm = ref({ name: '', type: 'Transfer bank', provider: '', accountName: '', accountNumber: '', instructions: '', fee: 'Dikelola manual', isActive: false })
const selectedStudentId = ref('')
const adjustmentPoints = ref('')
const adjustmentReason = ref('')
const adjustingPoints = ref(false)
const adjustmentIdempotencyKey = ref('')
const loadingMoreTopUps = ref(false)
const selectedStudent = computed(() => store.students.find((item) => item.id === selectedStudentId.value) || null)

watch([selectedStudentId, adjustmentPoints, adjustmentReason], () => {
  if (!adjustingPoints.value) adjustmentIdempotencyKey.value = ''
})

const filteredTopUps = computed(() => store.topUps.filter((item) => `${item.id} ${item.student?.name || ''} ${item.student?.email || ''} ${item.paymentReference || ''} ${item.paymentMethod || ''}`.toLowerCase().includes(query.value.toLowerCase())))
const filteredBundles = computed(() => store.pointBundles.filter((item) => `${item.name} ${item.id}`.toLowerCase().includes(query.value.toLowerCase())))
const filteredOffers = computed(() => store.accessOffers.filter((item) => `${item.title} ${item.package || ''} ${item.id}`.toLowerCase().includes(query.value.toLowerCase())))
const filteredMethods = computed(() => store.paymentMethods.filter((item) => `${item.name} ${item.type} ${item.provider}`.toLowerCase().includes(query.value.toLowerCase())))
const issuedPoints = computed(() => Number(store.walletSummary.topUpPoints || 0) + Number(store.walletSummary.promotionalPoints || 0))

function rupiah(value) { return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(Number(value || 0)) }
function number(value) { return new Intl.NumberFormat('id-ID').format(Number(value || 0)) }
function statusClass(value) { return value === 'Confirmed' || value === 'Aktif' ? 'status-terbit' : value === 'Pending' ? 'status-review' : 'status-arsip' }
function dateTime(value) { return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '—' }
function printReport() { window.print() }
async function showMoreTopUps() {
  loadingMoreTopUps.value = true
  try { await loadMoreTopUps() }
  catch (error) { window.alert(error.message) }
  finally { loadingMoreTopUps.value = false }
}

function openBundle(item = null) {
  activeRecord.value = item
  bundleForm.value = item
    ? { name: item.name, transferAmount: item.transferAmount, points: item.points, bonusPoints: item.bonusPoints, isActive: item.isActive }
    : { name: '', transferAmount: '', points: '', bonusPoints: 0, isActive: true }
  dialog.value = 'bundle'
}
function openOffer(item = null) {
  activeRecord.value = item
  offerForm.value = item
    ? { productType: item.type, testPackageId: item.testPackageId || '', materialCode: item.materialCode || '', title: item.title, description: item.description || '', pricePoints: item.pricePoints, validityDays: item.validityDays || '', attemptQuota: item.attemptQuota || '', isActive: item.isActive }
    : { productType: 'package', testPackageId: '', materialCode: '', title: '', description: '', pricePoints: '', validityDays: '', attemptQuota: '', isActive: true }
  dialog.value = 'offer'
}
function openMethod(item = null) {
  activeRecord.value = item
  methodForm.value = item
    ? { name: item.name, type: item.type, provider: item.provider, accountName: item.accountName || '', accountNumber: item.accountNumber || '', instructions: item.instructions || '', fee: item.fee || 'Dikelola manual', isActive: item.isActive }
    : { name: '', type: 'Transfer bank', provider: '', accountName: '', accountNumber: '', instructions: '', fee: 'Dikelola manual', isActive: false }
  dialog.value = 'method'
}
function openReview(item, decision) {
  activeTopUp.value = item
  reviewDecision.value = decision
  reviewReason.value = decision === 'Confirmed' ? 'Transfer sudah diterima dan sesuai.' : ''
  dialog.value = 'review'
}

async function saveRecord() {
  saving.value = true
  try {
    if (dialog.value === 'bundle') {
      const payload = { ...bundleForm.value, transferAmount: Number(bundleForm.value.transferAmount), points: Number(bundleForm.value.points), bonusPoints: Number(bundleForm.value.bonusPoints || 0), reason: 'Paket Top-up Poin dikelola Finance' }
      if (activeRecord.value) await savePointBundle(activeRecord.value, payload)
      else await createPointBundle(payload)
    } else if (dialog.value === 'offer') {
      const payload = {
        ...offerForm.value,
        testPackageId: offerForm.value.productType === 'package' ? Number(offerForm.value.testPackageId) : null,
        materialCode: offerForm.value.productType === 'material' ? offerForm.value.materialCode : null,
        pricePoints: Number(offerForm.value.pricePoints),
        validityDays: offerForm.value.validityDays === '' ? null : Number(offerForm.value.validityDays),
        attemptQuota: offerForm.value.attemptQuota === '' ? null : Number(offerForm.value.attemptQuota),
        reason: 'Penawaran akses dikelola Finance',
      }
      if (activeRecord.value) await saveAccessOffer(activeRecord.value, payload)
      else await createAccessOffer(payload)
    } else if (dialog.value === 'method') {
      const payload = { ...methodForm.value, reason: 'Instruksi transfer dikelola Finance' }
      if (activeRecord.value) await savePaymentMethod(activeRecord.value, payload)
      else await createPaymentMethod(payload)
    } else if (dialog.value === 'review') {
      await reviewTopUp(activeTopUp.value, reviewDecision.value, reviewReason.value.trim())
    }
    dialog.value = ''
  } catch (error) {
    window.alert(error.message)
  } finally {
    saving.value = false
  }
}

async function saveBonus() {
  savingSettings.value = true
  try {
    await saveWalletSettings({ signupBonusPoints: Number(store.walletSettings.signupBonusPoints), reason: settingsReason.value })
  } catch (error) {
    window.alert(error.message)
  } finally {
    savingSettings.value = false
  }
}
async function submitPointsAdjustment() {
  if (!selectedStudent.value || !adjustmentReason.value.trim() || Number(adjustmentPoints.value) === 0) return
  adjustingPoints.value = true
  try {
    await adjustStudentPoints(selectedStudent.value, {
      points: Number(adjustmentPoints.value),
      reason: adjustmentReason.value.trim(),
      idempotencyKey: adjustmentIdempotencyKey.value || (adjustmentIdempotencyKey.value = crypto.randomUUID()),
    })
    adjustmentIdempotencyKey.value = ''
    adjustmentPoints.value = ''
    adjustmentReason.value = ''
  } catch (error) {
    window.alert(error.message)
  } finally {
    adjustingPoints.value = false
  }
}
async function toggleMethod(method) {
  try { await savePaymentMethod(method, { isActive: !method.isActive, reason: 'Status kanal transfer diubah oleh Finance' }) }
  catch (error) { window.alert(error.message) }
}
async function removeMethod(method) {
  if (method.usage > 0) { window.alert('Kanal dengan riwayat Top-up hanya dapat dinonaktifkan.'); return }
  if (!window.confirm(`Hapus kanal ${method.name}?`)) return
  try { await deleteUnusedPaymentMethod(method) }
  catch (error) { window.alert(error.message) }
}
async function removeBundle(bundle) {
  if (!window.confirm(`Hapus paket ${bundle.name}?`)) return
  try { await deletePointBundle(bundle) }
  catch (error) { window.alert(error.message) }
}
async function removeOffer(offer) {
  if (!window.confirm(`Hapus penawaran ${offer.title}?`)) return
  try { await deleteAccessOffer(offer) }
  catch (error) { window.alert(error.message) }
}
</script>

<template>
  <div class="view-wrap">
    <div class="page-title-row">
      <div><div class="eyebrow">DOMPET POIN <span>/</span> FINANCE</div><h1>Poin dan Top-up</h1><p class="page-subtitle">Periksa transfer manual, atur paket Poin, dan kelola harga akses belajar.</p></div>
      <button v-if="tab === 'bundles'" class="button button-primary" @click="openBundle()"><Plus :size="17" /> Paket Poin</button>
      <button v-else-if="tab === 'offers'" class="button button-primary" @click="openOffer()"><Plus :size="17" /> Penawaran akses</button>
      <button v-else-if="tab === 'methods'" class="button button-primary" @click="openMethod()"><Plus :size="17" /> Kanal transfer</button>
      <button v-else class="button button-outline" @click="printReport"><Download :size="16" /> Cetak</button>
    </div>

    <div class="finance-metrics">
      <div class="finance-metric"><span class="metric-icon green"><WalletCards :size="18" /></span><div><small>Transfer terkonfirmasi</small><strong>{{ rupiah(store.walletSummary.confirmedTransferAmount) }}</strong><em>uang diterima untuk Top-up</em></div></div>
      <div class="finance-metric"><span class="metric-icon lilac"><CreditCard :size="18" /></span><div><small>Poin diterbitkan</small><strong>{{ number(issuedPoints) }}</strong><em>Top-up {{ number(store.walletSummary.topUpPoints) }} · promo {{ number(store.walletSummary.promotionalPoints) }}</em></div></div>
      <div class="finance-metric"><span class="metric-icon peach"><Check :size="18" /></span><div><small>Poin dibelanjakan</small><strong>{{ number(store.walletSummary.spentPoints) }}</strong><em>debit pembelian akses</em></div></div>
      <div class="finance-metric"><span class="metric-icon mint"><Pencil :size="18" /></span><div><small>Poin dikoreksi</small><strong>{{ number(store.walletSummary.correctedPoints) }}</strong><em>koreksi saldo manual</em></div></div>
      <div class="finance-metric"><span class="metric-icon green"><WalletCards :size="18" /></span><div><small>Saldo murid</small><strong>{{ number(store.walletSummary.outstandingPoints) }}</strong><em>total Poin yang masih beredar</em></div></div>
      <div class="finance-metric"><span class="metric-icon peach"><Check :size="18" /></span><div><small>Perlu pemeriksaan</small><strong>{{ number(store.walletSummary.pendingTopUps) }}</strong><em>permintaan Top-up pending</em></div></div>
    </div>

    <div class="content-tabs"><button v-for="item in tabs" :key="item.id" :class="['content-tab', { active: tab === item.id }]" @click="tab = item.id">{{ item.label }} <span>{{ item.id === 'topups' ? store.walletSummary.totalTopUps : item.id === 'bundles' ? store.pointBundles.length : item.id === 'offers' ? store.accessOffers.length : store.paymentMethods.length }}</span></button></div>
    <div class="filter-row"><label class="search-field"><Search :size="17" /><input v-model="query" :placeholder="tab === 'topups' ? 'Cari nama murid, referensi, atau kanal…' : 'Cari data…'" /></label></div>

    <template v-if="tab === 'topups'">
      <section class="panel table-panel"><div class="table-scroll"><table class="data-table payment-table"><thead><tr><th>MURID / REFERENSI</th><th>PAKET POIN</th><th>KANAL TRANSFER</th><th>BUKTI</th><th>NOMINAL</th><th>POIN MASUK</th><th>STATUS</th><th></th></tr></thead><tbody>
        <tr v-for="item in filteredTopUps" :key="item.id"><td><div class="table-primary"><b>{{ item.student?.name || 'Murid' }}</b><small>{{ item.paymentReference || item.student?.email }}</small></div></td><td>{{ item.bundle }}</td><td><div class="table-primary"><b>{{ item.paymentMethod }}</b><small>{{ item.paymentDetails?.accountNumber || '' }}</small></div></td><td><a class="inline-action" :href="topUpProofUrl(item)" target="_blank" rel="noreferrer">Lihat bukti</a></td><td class="amount-cell">{{ rupiah(item.transferAmount) }}</td><td>{{ number(Number(item.points) + Number(item.bonusPoints)) }}</td><td><span :class="['status-tag', statusClass(item.status)]"><i></i>{{ item.status }}</span></td><td><div v-if="item.status === 'Pending'" class="row-actions"><button class="inline-action" @click="openReview(item, 'Confirmed')"><Check :size="14" /> Setujui</button><button class="icon-button tiny danger-icon" title="Tolak Top-up" @click="openReview(item, 'Rejected')"><X :size="14" /></button></div><small v-else>{{ item.reviewReason }}</small></td></tr>
        <tr v-if="!filteredTopUps.length"><td colspan="8" class="empty-state">Belum ada pengajuan Top-up.</td></tr>
      </tbody></table></div><div class="table-footer"><span>Transfer baru menambah saldo setelah dikonfirmasi Finance.</span><span>{{ filteredTopUps.length }} dari {{ store.walletSummary.totalTopUps }} permintaan dimuat</span></div></section>
      <div v-if="store.topUpPagination.currentPage < store.topUpPagination.lastPage" class="load-more-row"><button class="button button-outline" :disabled="loadingMoreTopUps" @click="showMoreTopUps">{{ loadingMoreTopUps ? 'Memuat…' : 'Muat permintaan lebih lama' }}</button></div>
    </template>

    <template v-else-if="tab === 'bundles'">
      <section class="panel table-panel"><div class="table-scroll"><table class="data-table method-table"><thead><tr><th>PAKET TOP-UP</th><th>NOMINAL TRANSFER</th><th>POIN DASAR</th><th>BONUS</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
        <tr v-for="item in filteredBundles" :key="item.id"><td><b class="method-name">{{ item.name }}</b><small class="method-id">{{ item.id }}</small></td><td>{{ rupiah(item.transferAmount) }}</td><td>{{ number(item.points) }}</td><td>{{ number(item.bonusPoints) }}</td><td><span :class="['status-tag', statusClass(item.isActive ? 'Aktif' : 'Nonaktif')]"><i></i>{{ item.isActive ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="row-actions"><button class="icon-button tiny" title="Edit paket Poin" @click="openBundle(item)"><Pencil :size="14" /></button><button class="icon-button tiny danger-icon" title="Hapus paket yang belum dipakai" :disabled="item.usage > 0" @click="removeBundle(item)"><Trash2 :size="14" /></button></div></td></tr>
        <tr v-if="!filteredBundles.length"><td colspan="6" class="empty-state">Belum ada paket Poin. Buat nominal transfer dan Poin yang diterima.</td></tr>
      </tbody></table></div></section>
    </template>

    <template v-else-if="tab === 'offers'">
      <section class="panel table-panel"><div class="table-scroll"><table class="data-table method-table"><thead><tr><th>PENAWARAN</th><th>ISI / MATERI</th><th>HARGA</th><th>MASA AKSES</th><th>KUOTA COBA</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
        <tr v-for="item in filteredOffers" :key="item.id"><td><b class="method-name">{{ item.title }}</b><small class="method-id">{{ item.id }}</small></td><td>{{ item.package || item.materialCode || '—' }}</td><td><b>{{ number(item.pricePoints) }} Poin</b></td><td>{{ item.validityDays ? `${item.validityDays} hari` : 'Tanpa batas' }}</td><td>{{ item.attemptQuota ? number(item.attemptQuota) : 'Tanpa batas' }}</td><td><span :class="['status-tag', statusClass(item.isActive ? 'Aktif' : 'Nonaktif')]"><i></i>{{ item.isActive ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="row-actions"><button class="icon-button tiny" title="Edit penawaran" @click="openOffer(item)"><Pencil :size="14" /></button><button class="icon-button tiny danger-icon" title="Hapus penawaran yang belum dibeli" :disabled="item.usage > 0" @click="removeOffer(item)"><Trash2 :size="14" /></button></div></td></tr>
        <tr v-if="!filteredOffers.length"><td colspan="7" class="empty-state">Belum ada penawaran akses. Paket tes yang diterbitkan dapat dijual dengan harga Poin di sini.</td></tr>
      </tbody></table></div></section>
    </template>

    <template v-else>
      <div class="info-callout"><div class="callout-mark">i</div><p>Isi rekening dan instruksi transfer manual. Detail kanal disalin ke permintaan Top-up sehingga riwayat tetap terbaca setelah kanal diubah atau dinonaktifkan.</p></div>
      <section class="panel table-panel"><div class="table-scroll"><table class="data-table method-table"><thead><tr><th>KANAL</th><th>JENIS</th><th>REKENING</th><th>INSTRUKSI</th><th>RIWAYAT</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
        <tr v-for="method in filteredMethods" :key="method.id"><td><b class="method-name">{{ method.name }}</b><small class="method-id">{{ method.id }}</small></td><td>{{ method.type }} · {{ method.provider }}</td><td><div class="table-primary"><b>{{ method.accountName || 'Belum diatur' }}</b><small>{{ method.accountNumber || '' }}</small></div></td><td>{{ method.instructions || '—' }}</td><td>{{ number(method.usage) }}</td><td><span :class="['status-tag', statusClass(method.isActive ? 'Aktif' : 'Nonaktif')]"><i></i>{{ method.isActive ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="row-actions"><button class="icon-button tiny" title="Edit instruksi" @click="openMethod(method)"><Pencil :size="14" /></button><button class="icon-button tiny" :title="method.isActive ? 'Nonaktifkan' : 'Aktifkan'" @click="toggleMethod(method)"><CreditCard :size="14" /></button><button class="icon-button tiny danger-icon" title="Hapus kanal tanpa riwayat" :disabled="method.usage > 0" @click="removeMethod(method)"><Trash2 :size="14" /></button></div></td></tr>
        <tr v-if="!filteredMethods.length"><td colspan="7" class="empty-state">Belum ada kanal transfer manual.</td></tr>
      </tbody></table></div><div class="table-footer"><span>Kanal yang memiliki riwayat hanya dapat dinonaktifkan.</span><span>{{ filteredMethods.length }} kanal</span></div></section>
    </template>

    <section class="panel wallet-settings"><div><span class="section-kicker">BONUS PENDAFTARAN</span><h2>Atur Poin awal akun baru</h2><p>Perubahan hanya berlaku untuk pendaftaran berikutnya dan tercatat di audit.</p></div><label class="field-label">Poin bonus<input v-model.number="store.walletSettings.signupBonusPoints" type="number" min="0" max="100000000" /></label><button class="button button-primary" :disabled="savingSettings" @click="saveBonus">{{ savingSettings ? 'Menyimpan…' : 'Simpan bonus' }}</button></section>
    <section class="panel wallet-settings adjustment-panel"><div><span class="section-kicker">KOREKSI / PROMOSI</span><h2>Poin murid</h2><p>Penambahan atau koreksi saldo wajib memiliki alasan audit. Saldo tidak dapat menjadi negatif.</p></div><label class="field-label">Pilih murid<select v-model="selectedStudentId"><option value="">Pilih murid</option><option v-for="student in store.students" :key="student.id" :value="student.id">{{ student.name }} · {{ number(student.pointBalance) }} Poin</option></select></label><label class="field-label">Poin (+/-)<input v-model="adjustmentPoints" type="number" step="1" placeholder="Contoh: 100 atau -20" /></label><label class="field-label">Alasan<input v-model="adjustmentReason" placeholder="Contoh: bonus kampanye November" /></label><button class="button button-primary" :disabled="adjustingPoints || !selectedStudent || !adjustmentReason.trim() || Number(adjustmentPoints) === 0" @click="submitPointsAdjustment">{{ adjustingPoints ? 'Menyimpan…' : 'Catat penyesuaian' }}</button></section>

    <div v-if="dialog" class="modal-backdrop" @click.self="dialog = ''"><section class="modal-card form-modal"><button class="modal-close" @click="dialog = ''"><X :size="18" /></button>
      <template v-if="dialog === 'review'"><span class="section-kicker">PERMINTAAN TOP-UP · #{{ activeTopUp?.id }}</span><h2>{{ reviewDecision === 'Confirmed' ? 'Konfirmasi transfer' : 'Tolak Top-up' }}</h2><p class="modal-description">{{ activeTopUp?.student?.name }} · {{ rupiah(activeTopUp?.transferAmount) }} · {{ number(Number(activeTopUp?.points) + Number(activeTopUp?.bonusPoints)) }} Poin</p><a class="button button-outline" :href="topUpProofUrl(activeTopUp)" target="_blank" rel="noreferrer">Buka bukti transfer</a><label class="field-label">Alasan pemeriksaan<textarea v-model="reviewReason" rows="3" placeholder="Catat alasan konfirmasi atau penolakan"></textarea></label></template>
      <template v-else-if="dialog === 'bundle'"><span class="section-kicker">PAKET POIN · {{ activeRecord?.id || 'BARU' }}</span><h2>{{ activeRecord ? 'Edit paket Top-up' : 'Buat paket Top-up' }}</h2><label class="field-label">Nama paket<input v-model="bundleForm.name" placeholder="Contoh: 1.000 Poin" /></label><label class="field-label">Nominal transfer (Rp)<input v-model="bundleForm.transferAmount" type="number" min="1" /></label><label class="field-label">Poin yang diterima<input v-model="bundleForm.points" type="number" min="1" /></label><label class="field-label">Bonus Poin<input v-model="bundleForm.bonusPoints" type="number" min="0" /></label><label class="field-check"><input v-model="bundleForm.isActive" type="checkbox" /> Tampilkan untuk Top-up murid</label></template>
      <template v-else-if="dialog === 'offer'"><span class="section-kicker">PENAWARAN AKSES · {{ activeRecord?.id || 'BARU' }}</span><h2>{{ activeRecord ? 'Edit penawaran' : 'Buat penawaran akses' }}</h2><label class="field-label">Jenis produk<select v-model="offerForm.productType"><option value="package">Paket tes</option><option value="material">Materi</option></select></label><label v-if="offerForm.productType === 'package'" class="field-label">Paket terbit<select v-model="offerForm.testPackageId"><option value="">Pilih paket</option><option v-for="item in store.availablePackages" :key="item.id" :value="item.id">{{ item.name }} · v{{ item.version }}</option></select></label><label v-else class="field-label">Kode materi<input v-model="offerForm.materialCode" placeholder="Contoh: RUMUS-ALJABAR" /></label><label class="field-label">Nama yang ditampilkan<input v-model="offerForm.title" placeholder="Nama penawaran" /></label><label class="field-label">Deskripsi<textarea v-model="offerForm.description" rows="2"></textarea></label><div class="field-two-col"><label class="field-label">Harga (Poin)<input v-model="offerForm.pricePoints" type="number" min="1" /></label><label class="field-label">Masa akses (hari, kosong = tanpa batas)<input v-model="offerForm.validityDays" type="number" min="1" /></label></div><label class="field-label">Kuota percobaan (kosong = tanpa batas)<input v-model="offerForm.attemptQuota" type="number" min="1" /></label><label class="field-check"><input v-model="offerForm.isActive" type="checkbox" /> Aktifkan penawaran untuk murid</label></template>
      <template v-else><span class="section-kicker">KANAL TRANSFER · {{ activeRecord?.id || 'BARU' }}</span><h2>{{ activeRecord ? 'Edit instruksi transfer' : 'Tambah kanal transfer' }}</h2><label class="field-label">Nama tampilan<input v-model="methodForm.name" placeholder="Contoh: Transfer Bank BCA" /></label><div class="field-two-col"><label class="field-label">Jenis<select v-model="methodForm.type"><option>Transfer bank</option><option>Transfer manual</option><option>QRIS manual</option></select></label><label class="field-label">Bank / penyedia<input v-model="methodForm.provider" placeholder="Contoh: BCA" /></label></div><label class="field-label">Nama pemilik rekening<input v-model="methodForm.accountName" /></label><label class="field-label">Nomor rekening / tujuan<input v-model="methodForm.accountNumber" /></label><label class="field-label">Instruksi transfer<textarea v-model="methodForm.instructions" rows="3"></textarea></label><label class="field-check"><input v-model="methodForm.isActive" type="checkbox" /> Tampilkan ke murid</label></template>
      <div class="modal-footer"><button class="button button-outline" @click="dialog = ''">Batal</button><button class="button button-primary" :disabled="saving || (dialog === 'review' && !reviewReason.trim())" @click="saveRecord">{{ saving ? 'Menyimpan…' : dialog === 'review' ? 'Simpan keputusan' : 'Simpan perubahan' }}</button></div>
    </section></div>
  </div>
</template>
