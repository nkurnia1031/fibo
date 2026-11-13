import { reactive } from 'vue'
import { apiRequest } from './api'

export const store = reactive({
  questions: [],
  packages: [],
  students: [],
  adminUsers: [],
  paymentMethods: [],
  topUps: [],
  pointBundles: [],
  accessOffers: [],
  availablePackages: [],
  topUpPagination: { currentPage: 1, lastPage: 1 },
  walletSettings: { signupBonusPoints: 100 },
  walletSummary: {
    confirmedTransferAmount: 0,
    pendingTopUps: 0,
    totalTopUps: 0,
    topUpPoints: 0,
    promotionalPoints: 0,
    spentPoints: 0,
    correctedPoints: 0,
    outstandingPoints: 0,
  },
})

const bodyData = (response) => response?.data?.data ?? response?.data
const collectionData = (response) => Array.isArray(response?.data) ? response.data : (response?.data?.data ?? [])
const setCollection = (key, values) => store[key].splice(0, store[key].length, ...values)
const encode = encodeURIComponent

export async function loadContent(user) {
  const role = user?.role
  const canSeeContent = ['Owner', 'Admin', 'Penulis Konten', 'Reviewer'].includes(role)
  const requests = []
  if (canSeeContent) {
    requests.push(apiRequest('/questions?per_page=100').then((response) => setCollection('questions', collectionData(response))))
    requests.push(apiRequest('/packages?per_page=100').then((response) => setCollection('packages', collectionData(response))))
  } else {
    setCollection('questions', [])
    setCollection('packages', [])
  }
  if (['Owner', 'Admin', 'Support', 'Finance'].includes(role)) requests.push(apiRequest('/students?per_page=100').then((response) => setCollection('students', collectionData(response))))
  else setCollection('students', [])
  if (role === 'Owner') requests.push(apiRequest('/staff?per_page=100').then((response) => setCollection('adminUsers', collectionData(response))))
  else setCollection('adminUsers', [])
  if (['Owner', 'Admin', 'Finance'].includes(role)) requests.push(apiRequest('/payment-methods?per_page=100').then((response) => setCollection('paymentMethods', collectionData(response))))
  else setCollection('paymentMethods', [])
  if (['Owner', 'Admin', 'Finance'].includes(role)) {
    requests.push(apiRequest('/top-ups?per_page=100').then((response) => {
      setCollection('topUps', collectionData(response))
      store.topUpPagination = { currentPage: response.data?.current_page || 1, lastPage: response.data?.last_page || 1 }
    }))
    requests.push(apiRequest('/point-bundles?per_page=100').then((response) => setCollection('pointBundles', collectionData(response))))
    requests.push(apiRequest('/access-offers?per_page=100').then((response) => setCollection('accessOffers', collectionData(response))))
    requests.push(apiRequest('/package-options').then((response) => setCollection('availablePackages', collectionData(response))))
    requests.push(apiRequest('/wallet-settings').then((response) => { store.walletSettings.signupBonusPoints = response.signupBonusPoints }))
    requests.push(apiRequest('/wallet-summary').then((response) => { store.walletSummary = response }))
  } else {
    setCollection('topUps', [])
    store.topUpPagination = { currentPage: 1, lastPage: 1 }
    setCollection('pointBundles', [])
    setCollection('accessOffers', [])
    setCollection('availablePackages', [])
    store.walletSummary = { confirmedTransferAmount: 0, pendingTopUps: 0, totalTopUps: 0, topUpPoints: 0, promotionalPoints: 0, spentPoints: 0, correctedPoints: 0, outstandingPoints: 0 }
  }
  await Promise.all(requests)
}

function upsertQuestion(record) {
  const index = store.questions.findIndex((item) => item.id === record.id && item.revision === record.revision)
  if (index >= 0) store.questions.splice(index, 1, record)
  else store.questions.unshift(record)
  return record
}

export async function saveQuestionRecord(question, changes, mode) {
  const form = new FormData()
  for (const [key, value] of Object.entries(changes)) {
    if (value === undefined || value === null || key === 'imageUrl') continue
    if (Array.isArray(value)) value.forEach((item) => form.append(`${key}[]`, String(item)))
    else if (value instanceof File) form.append(key === 'imageFile' ? 'image' : key, value)
    else form.append(key, String(value))
  }

  let response
  if (mode === 'revise') response = await apiRequest(`/questions/${encode(question.id)}/revisions`, { method: 'POST', body: form })
  else if (mode === 'edit' && question) response = await apiRequest(`/questions/${encode(question.id)}/revisions/${question.revision}`, { method: 'PUT', body: form })
  else response = await apiRequest('/questions', { method: 'POST', body: form })
  return upsertQuestion(bodyData(response))
}

export async function deleteDraftQuestion(question) {
  await apiRequest(`/questions/${encode(question.id)}/revisions/${question.revision}`, {
    method: 'DELETE',
    body: { reason: 'Draf soal dihapus dari bank soal' },
  })
  const index = store.questions.indexOf(question)
  if (index >= 0) store.questions.splice(index, 1)
}

export async function transitionQuestion(question, status, reason = '') {
  const response = await apiRequest(`/questions/${encode(question.id)}/revisions/${question.revision}/transition`, {
    method: 'POST',
    body: { status, reason },
  })
  return upsertQuestion(bodyData(response))
}

function upsertPackage(record) {
  const index = store.packages.findIndex((item) => item.id === record.id && item.version === record.version)
  if (index >= 0) store.packages.splice(index, 1, record)
  else store.packages.unshift(record)
  return record
}

export async function createPackageRecord(packageRecord) {
  const response = await apiRequest('/packages', { method: 'POST', body: packageRecord })
  return upsertPackage(bodyData(response))
}

export async function savePackageRecord(packageRecord, changes) {
  const response = await apiRequest(`/packages/${encode(packageRecord.id)}/versions/${packageRecord.version}`, {
    method: 'PUT',
    body: changes,
  })
  return upsertPackage(bodyData(response))
}

export async function createPackageRevision(packageRecord, changes) {
  const response = await apiRequest(`/packages/${encode(packageRecord.id)}/revisions`, {
    method: 'POST',
    body: changes,
  })
  return upsertPackage(bodyData(response))
}

export async function deleteDraftPackage(packageRecord) {
  await apiRequest(`/packages/${encode(packageRecord.id)}/versions/${packageRecord.version}`, {
    method: 'DELETE',
    body: { reason: 'Draf paket dihapus dari bank paket' },
  })
  const index = store.packages.indexOf(packageRecord)
  if (index >= 0) store.packages.splice(index, 1)
}

export async function transitionPackage(packageRecord, status, reason = '') {
  const response = await apiRequest(`/packages/${encode(packageRecord.id)}/versions/${packageRecord.version}/transition`, {
    method: 'POST',
    body: { status, reason },
  })
  return upsertPackage(bodyData(response))
}

export async function createStudent(student) {
  const response = await apiRequest('/students', { method: 'POST', body: student })
  const record = bodyData(response)
  store.students.unshift(record)
  return record
}

export async function saveStudent(student, changes) {
  const response = await apiRequest(`/students/${encode(student.id)}`, { method: 'PUT', body: changes })
  Object.assign(student, bodyData(response))
  return student
}

export async function closeStudentAccount(student, reason = 'Akun ditutup dari panel Support') {
  const response = await apiRequest(`/students/${encode(student.id)}/close`, { method: 'PATCH', body: { reason } })
  Object.assign(student, bodyData(response))
}

export async function resetStudentCredential(student) {
  return apiRequest(`/students/${encode(student.id)}/credential-reset`, {
    method: 'POST',
    body: { reason: 'Permintaan reset dimulai oleh Support' },
  })
}

export async function deleteUnactivatedStudent(student) {
  await apiRequest(`/students/${encode(student.id)}`, { method: 'DELETE', body: { reason: 'Undangan murid yang belum digunakan dihapus' } })
  const index = store.students.indexOf(student)
  if (index >= 0) store.students.splice(index, 1)
}

export async function createAdminUser(user) {
  const response = await apiRequest('/staff', { method: 'POST', body: user })
  const record = bodyData(response)
  store.adminUsers.unshift(record)
  return { user: record, temporaryPassword: response.temporaryPassword }
}

export async function saveAdminUser(user, changes) {
  const response = await apiRequest(`/staff/${encode(user.id)}`, {
    method: 'PUT',
    body: { name: changes.name, email: changes.email, role: changes.role, reason: 'Peran atau profil staf diperbarui oleh Owner' },
  })
  Object.assign(user, bodyData(response))
}

export async function deactivateAdminUser(user) {
  const response = await apiRequest(`/staff/${encode(user.id)}`, {
    method: 'PUT',
    body: { isActive: user.status === 'Nonaktif', reason: 'Akses staf diubah oleh Owner' },
  })
  Object.assign(user, bodyData(response))
}

export async function deleteInvitedAdminUser(user) {
  await apiRequest(`/staff/${encode(user.id)}`, { method: 'DELETE', body: { reason: 'Undangan staf yang belum digunakan dihapus' } })
  const index = store.adminUsers.indexOf(user)
  if (index >= 0) store.adminUsers.splice(index, 1)
}

export async function resetAdminPassword(user) {
  const response = await apiRequest(`/staff/${encode(user.id)}/password-reset`, {
    method: 'POST',
    body: { reason: 'Permintaan reset akses staf oleh Owner' },
  })
  user.status = 'Undangan'
  user.mustChangePassword = true
  return response.temporaryPassword
}

export async function createPaymentMethod(method) {
  const response = await apiRequest('/payment-methods', { method: 'POST', body: method })
  const record = bodyData(response)
  store.paymentMethods.unshift(record)
  return record
}

export async function savePaymentMethod(method, changes) {
  const response = await apiRequest(`/payment-methods/${encode(method.id)}`, { method: 'PATCH', body: changes })
  Object.assign(method, bodyData(response))
}

export async function deleteUnusedPaymentMethod(method) {
  await apiRequest(`/payment-methods/${encode(method.id)}`, { method: 'DELETE', body: { reason: 'Metode pembayaran dihapus' } })
  const index = store.paymentMethods.indexOf(method)
  if (index >= 0) store.paymentMethods.splice(index, 1)
}

async function reloadWalletAdmin() {
  const [topUps, pointBundles, accessOffers, paymentMethods, settings, summary] = await Promise.all([
    apiRequest('/top-ups?per_page=100'),
    apiRequest('/point-bundles?per_page=100'),
    apiRequest('/access-offers?per_page=100'),
    apiRequest('/payment-methods?per_page=100'),
    apiRequest('/wallet-settings'),
    apiRequest('/wallet-summary'),
  ])
  setCollection('topUps', collectionData(topUps))
  store.topUpPagination = { currentPage: topUps.data?.current_page || 1, lastPage: topUps.data?.last_page || 1 }
  setCollection('pointBundles', collectionData(pointBundles))
  setCollection('accessOffers', collectionData(accessOffers))
  setCollection('paymentMethods', collectionData(paymentMethods))
  store.walletSettings.signupBonusPoints = settings.signupBonusPoints
  store.walletSummary = summary
}

export async function loadMoreTopUps() {
  const nextPage = store.topUpPagination.currentPage + 1
  if (nextPage > store.topUpPagination.lastPage) return
  const response = await apiRequest(`/top-ups?per_page=100&page=${nextPage}`)
  store.topUps.push(...collectionData(response))
  store.topUpPagination = { currentPage: response.data?.current_page || nextPage, lastPage: response.data?.last_page || nextPage }
}

export async function reviewTopUp(topUp, decision, reason) {
  await apiRequest(`/top-ups/${topUp.id}/review`, { method: 'POST', body: { decision, reason } })
  await reloadWalletAdmin()
}

export function topUpProofUrl(topUp) {
  return `/api/v1/top-ups/${topUp.id}/proof`
}

export async function createPointBundle(bundle) {
  await apiRequest('/point-bundles', { method: 'POST', body: bundle })
  await reloadWalletAdmin()
}

export async function savePointBundle(bundle, changes) {
  await apiRequest(`/point-bundles/${encode(bundle.id)}`, { method: 'PUT', body: changes })
  await reloadWalletAdmin()
}

export async function deletePointBundle(bundle) {
  await apiRequest(`/point-bundles/${encode(bundle.id)}`, { method: 'DELETE', body: { reason: 'Paket Poin yang belum dipakai dihapus' } })
  await reloadWalletAdmin()
}

export async function createAccessOffer(offer) {
  await apiRequest('/access-offers', { method: 'POST', body: offer })
  await reloadWalletAdmin()
}

export async function saveAccessOffer(offer, changes) {
  await apiRequest(`/access-offers/${encode(offer.id)}`, { method: 'PUT', body: changes })
  await reloadWalletAdmin()
}

export async function deleteAccessOffer(offer) {
  await apiRequest(`/access-offers/${encode(offer.id)}`, { method: 'DELETE', body: { reason: 'Penawaran akses yang belum dipakai dihapus' } })
  await reloadWalletAdmin()
}

export async function saveWalletSettings(settings) {
  const result = await apiRequest('/wallet-settings', { method: 'PUT', body: settings })
  store.walletSettings.signupBonusPoints = result.signupBonusPoints
}

export async function adjustStudentPoints(student, changes) {
  const response = await apiRequest(`/students/${encode(student.id)}/points`, { method: 'POST', body: changes })
  student.pointBalance = response.balance
  await reloadWalletAdmin()
  return response
}
