const API_ROOT = '/api/v1'
let csrfToken = ''
let csrfRequest = null

async function ensureCsrfToken() {
  if (csrfToken) return csrfToken
  if (!csrfRequest) {
    csrfRequest = fetch(`${API_ROOT}/auth/csrf`, {
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    }).then(async (response) => {
      const payload = await response.json()
      if (!response.ok || !payload.token) throw new Error('Tidak dapat menyiapkan sesi aman. Muat ulang halaman dan coba lagi.')
      csrfToken = payload.token
      return csrfToken
    }).finally(() => {
      csrfRequest = null
    })
  }
  return csrfRequest
}

export async function apiRequest(path, options = {}) {
  const method = (options.method || 'GET').toUpperCase()
  const isMutation = !['GET', 'HEAD', 'OPTIONS'].includes(method)
  if (isMutation) await ensureCsrfToken()

  const headers = new Headers(options.headers || {})
  headers.set('Accept', 'application/json')
  let body = options.body
  if (body instanceof FormData) {
    // The browser sets the multipart boundary.
  } else if (body !== undefined && body !== null) {
    headers.set('Content-Type', 'application/json')
    body = JSON.stringify(body)
  }
  if (isMutation && csrfToken) headers.set('X-CSRF-TOKEN', csrfToken)

  const response = await fetch(`${API_ROOT}${path}`, {
    ...options,
    method,
    headers,
    body,
    credentials: 'same-origin',
  })
  const payload = response.status === 204 ? null : await response.json().catch(() => ({}))

  if (response.status === 401 && !path.startsWith('/auth/')) {
    window.dispatchEvent(new CustomEvent('fibo-session-expired'))
  }

  if (!response.ok) {
    const validationMessage = Object.values(payload?.errors || {}).flat()[0]
    const error = new Error(validationMessage || payload?.message || `Permintaan gagal (${response.status}).`)
    error.status = response.status
    error.payload = payload
    throw error
  }

  return payload
}

export async function signIn(credentials) {
  await ensureCsrfToken()
  const result = await apiRequest('/auth/login', { method: 'POST', body: credentials })
  csrfToken = ''
  await ensureCsrfToken()
  return result
}

export async function getCurrentUser() {
  return apiRequest('/auth/me')
}

export async function signOut() {
  await apiRequest('/auth/logout', { method: 'POST' })
  csrfToken = ''
}

export async function changePassword(values) {
  const result = await apiRequest('/auth/password', { method: 'PUT', body: values })
  csrfToken = ''
  await ensureCsrfToken()
  return result.data
}
