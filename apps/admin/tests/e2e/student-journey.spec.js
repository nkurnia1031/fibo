import { expect, test } from '@playwright/test'

test('student can register, buy learning access with points, and request a manual top-up', async ({ page }) => {
  await page.goto('/student-demo.html')
  await expect(page.getByRole('heading', { name: /Belajar lebih tenang/ })).toBeVisible()

  await page.getByRole('button', { name: 'Daftar gratis' }).first().click()
  const signup = page.getByRole('dialog', { name: 'Mulai gratis' })
  await signup.getByLabel('Nama panggilan').fill('Nadia QA')
  await signup.getByLabel('Email').fill('nadia.qa@example.test')
  await signup.getByLabel('Kata sandi').fill('demo-password-123')
  await signup.getByRole('button', { name: 'Buat akun gratis' }).click()

  await expect(page.getByRole('heading', { name: 'Halo, Nadia QA!' })).toBeVisible()
  await expect(page.locator('#wallet-balance')).toHaveText('100')

  await page.locator('#available-products').getByRole('button', { name: 'Beli' }).first().click()
  const purchase = page.getByRole('dialog', { name: 'Konfirmasi pembelian' })
  await expect(purchase).toContainText('60 poin')
  await purchase.getByRole('button', { name: 'Konfirmasi pembelian' }).click()
  await expect(page.locator('#wallet-balance')).toHaveText('40')
  await expect(page.locator('#owned-products')).toContainText('Paket Aljabar Dasar')

  await page.getByRole('button', { name: 'Top up poin' }).click()
  const topup = page.getByRole('dialog', { name: 'Ajukan top-up' })
  await topup.getByLabel('Nomor referensi transfer').fill('REF-DEMO-001')
  await topup.getByLabel('Bukti transfer').setInputFiles({
    name: 'bukti-transfer.png',
    mimeType: 'image/png',
    buffer: Buffer.from('demo transfer receipt'),
  })
  await topup.getByRole('button', { name: 'Kirim untuk diperiksa' }).click()

  await expect(page.locator('.pending-box')).toContainText('Top-up menunggu pemeriksaan')
  await expect(page.locator('.pending-box')).toContainText('500 poin')
  await expect(page.locator('#wallet-balance')).toHaveText('40')
})
