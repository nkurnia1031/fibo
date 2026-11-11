import { expect, test } from '@playwright/test'

test('owner can edit a student and see the saved profile after reload', async ({ page }) => {
  const dialogs = []
  page.on('dialog', async (dialog) => {
    dialogs.push(dialog.message())
    await dialog.dismiss()
  })

  await page.goto('/')
  await page.getByLabel('Email', { exact: true }).fill(process.env.E2E_OWNER_EMAIL)
  await page.getByLabel('Kata sandi', { exact: true }).fill(process.env.E2E_OWNER_PASSWORD)
  await page.getByRole('button', { name: 'Masuk' }).click()

  await page.getByLabel('Kata sandi sementara', { exact: true }).fill(process.env.E2E_OWNER_PASSWORD)
  await page.getByLabel('Kata sandi baru', { exact: true }).fill(process.env.E2E_OWNER_NEW_PASSWORD)
  await page.getByLabel('Ulangi kata sandi baru', { exact: true }).fill(process.env.E2E_OWNER_NEW_PASSWORD)
  await page.getByRole('button', { name: 'Simpan kata sandi' }).click()

  const studentsNavigation = page.getByRole('button', { name: 'Murid', exact: true })
  await expect(studentsNavigation).toBeVisible()
  await studentsNavigation.click()

  const studentRow = page.getByRole('row', { name: /Alya Demo/ })
  await expect(studentRow).toBeVisible()
  await studentRow.getByRole('button', { name: 'Lihat / edit' }).click()
  await page.getByLabel('Nama murid').fill('Alya Demo QA')
  await page.getByRole('button', { name: 'Simpan perubahan' }).click()

  await expect(page.getByRole('heading', { name: 'Edit data murid' })).toHaveCount(0)
  await expect(page.getByRole('row', { name: /Alya Demo QA/ })).toBeVisible()
  expect(dialogs).toEqual([])

  await page.reload()
  await page.getByRole('button', { name: 'Murid', exact: true }).click()
  await expect(page.getByRole('row', { name: /Alya Demo QA/ })).toBeVisible()
})
