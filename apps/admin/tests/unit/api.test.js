import assert from 'node:assert/strict'
import { test } from 'node:test'

test('refreshes the CSRF token after login and password change', async () => {
  const originalFetch = globalThis.fetch
  const calls = []
  const csrfTokens = ['before-login', 'after-login', 'after-password-change']

  globalThis.fetch = async (input, options = {}) => {
    const url = String(input)
    const method = options.method || 'GET'
    const headers = new Headers(options.headers || {})
    calls.push({ url, method, csrfToken: headers.get('X-CSRF-TOKEN') })

    if (url.endsWith('/auth/csrf')) {
      return Response.json({ token: csrfTokens.shift() })
    }

    if (url.endsWith('/auth/login')) {
      assert.equal(headers.get('X-CSRF-TOKEN'), 'before-login')
      return Response.json({ data: { id: 'U-0001' }, mustChangePassword: true })
    }

    if (url.endsWith('/auth/password')) {
      assert.equal(headers.get('X-CSRF-TOKEN'), 'after-login')
      return Response.json({ data: { id: 'U-0001', role: 'Owner' } })
    }

    if (url.endsWith('/students/M-0001')) {
      assert.equal(method, 'PUT')
      assert.equal(headers.get('X-CSRF-TOKEN'), 'after-password-change')
      return Response.json({ data: { id: 'M-0001', name: 'Alya Diperbarui' } })
    }

    throw new Error(`Unexpected request: ${method} ${url}`)
  }

  try {
    const api = await import(`../../src/api.js?unit=${Date.now()}`)

    await api.signIn({ email: 'owner@example.test', password: 'temporary-password' })
    await api.changePassword({
      currentPassword: 'temporary-password',
      password: 'new-password-long-enough',
      password_confirmation: 'new-password-long-enough',
    })
    const response = await api.apiRequest('/students/M-0001', {
      method: 'PUT',
      body: { name: 'Alya Diperbarui' },
    })

    assert.equal(response.data.name, 'Alya Diperbarui')
    assert.deepEqual(calls.map((call) => [call.method, call.url.split('/').at(-1)]), [
      ['GET', 'csrf'],
      ['POST', 'login'],
      ['GET', 'csrf'],
      ['PUT', 'password'],
      ['GET', 'csrf'],
      ['PUT', 'M-0001'],
    ])
  } finally {
    globalThis.fetch = originalFetch
  }
})
