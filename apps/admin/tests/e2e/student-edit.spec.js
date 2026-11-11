import { expect, test } from '@playwright/test'

async function submitLogin(page, password) {
  await page.goto('/')
  await page.getByLabel('Email', { exact: true }).fill(process.env.E2E_OWNER_EMAIL)
  await page.getByLabel('Kata sandi', { exact: true }).fill(password)
  await page.getByRole('button', { name: 'Masuk' }).click()
}

async function signInOwner(page) {
  await submitLogin(page, process.env.E2E_OWNER_NEW_PASSWORD)
  await expect(page.getByRole('button', { name: 'Murid', exact: true })).toBeVisible()
}

test.beforeAll(async ({ browser }) => {
  const page = await browser.newPage()
  await submitLogin(page, process.env.E2E_OWNER_PASSWORD)

  const passwordChangeField = page.getByLabel('Kata sandi sementara', { exact: true })
  try {
    await passwordChangeField.waitFor({ state: 'visible', timeout: 5_000 })
  } catch {
    await page.getByLabel('Kata sandi', { exact: true }).fill(process.env.E2E_OWNER_NEW_PASSWORD)
    await page.getByRole('button', { name: 'Masuk' }).click()
    await expect(page.getByRole('button', { name: 'Murid', exact: true })).toBeVisible()
    await page.close()
    return
  }

  await passwordChangeField.fill(process.env.E2E_OWNER_PASSWORD)
  await page.getByLabel('Kata sandi baru', { exact: true }).fill(process.env.E2E_OWNER_NEW_PASSWORD)
  await page.getByLabel('Ulangi kata sandi baru', { exact: true }).fill(process.env.E2E_OWNER_NEW_PASSWORD)
  await page.getByRole('button', { name: 'Simpan kata sandi' }).click()
  await expect(page.getByRole('button', { name: 'Murid', exact: true })).toBeVisible()
  await page.close()
})

test('owner can edit a student and see the saved profile after reload', async ({ page }) => {
  await signInOwner(page)
  const studentsNavigation = page.getByRole('button', { name: 'Murid', exact: true })
  const dialogs = []
  page.on('dialog', async (dialog) => {
    dialogs.push(dialog.message())
    await dialog.dismiss()
  })

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

test('owner can add, preview, and edit LaTeX blocks in a question', async ({ page }) => {
  await signInOwner(page)
  await page.getByRole('button', { name: /^Bank soal/ }).click()
  await page.getByRole('button', { name: 'Tulis soal' }).click()
  await page.getByLabel('Judul internal').fill('Soal formula QA')

  const promptEditor = page.getByRole('group', { name: 'Pertanyaan' })
  await promptEditor.getByLabel('Pertanyaan teks 1').fill('Tentukan nilai')
  await promptEditor.getByRole('button', { name: 'Tambah rumus LaTeX' }).click()
  let formulaDialog = page.getByRole('dialog', { name: 'Tambah rumus' })
  await formulaDialog.getByLabel('Rumus LaTeX').fill('x^2 + 1')
  await expect(formulaDialog.locator('.latex-preview')).toBeVisible()
  await formulaDialog.getByRole('button', { name: 'Tambahkan rumus' }).click()

  await promptEditor.getByRole('button', { name: 'Edit rumus 2' }).click()
  formulaDialog = page.getByRole('dialog', { name: 'Edit rumus' })
  await formulaDialog.getByLabel('Rumus LaTeX').fill('x^2 + 2')
  await formulaDialog.getByRole('button', { name: 'Simpan perubahan' }).click()

  const firstOption = page.getByRole('group', { name: 'Pilihan A' })
  await firstOption.getByLabel('Pilihan A teks 1').fill('Hasilnya')
  await firstOption.getByRole('button', { name: 'Tambah rumus LaTeX' }).click()
  formulaDialog = page.getByRole('dialog', { name: 'Tambah rumus' })
  await formulaDialog.getByLabel('Rumus LaTeX').fill('3')
  await formulaDialog.getByRole('button', { name: 'Tambahkan rumus' }).click()
  await page.getByLabel('Pilihan B teks 1').fill('4')

  const explanationEditor = page.getByRole('group', { name: 'Pembahasan' })
  await explanationEditor.getByLabel('Pembahasan teks 1').fill('Substitusikan nilai ke')
  await explanationEditor.getByRole('button', { name: 'Tambah rumus LaTeX' }).click()
  formulaDialog = page.getByRole('dialog', { name: 'Tambah rumus' })
  await formulaDialog.getByLabel('Rumus LaTeX').fill('x^2 + 2 = 3')
  await formulaDialog.getByRole('button', { name: 'Tambahkan rumus' }).click()

  await page.getByRole('button', { name: 'Simpan draf' }).click()
  let questionRow = page.getByRole('row', { name: /Soal formula QA/ })
  await expect(questionRow).toBeVisible()
  await questionRow.getByRole('button', { name: 'Pratinjau' }).click()
  await expect(page.locator('.question-run-modal .latex-preview')).toHaveCount(3)
  await page.locator('.question-run-modal').getByRole('button', { name: 'Tutup', exact: true }).last().click()

  await page.reload()
  await page.getByRole('button', { name: /^Bank soal/ }).click()
  questionRow = page.getByRole('row', { name: /Soal formula QA/ })
  await questionRow.getByRole('button', { name: 'Edit' }).click()
  const savedPromptEditor = page.getByRole('group', { name: 'Pertanyaan' })
  await savedPromptEditor.getByRole('button', { name: 'Edit rumus 2' }).click()
  formulaDialog = page.getByRole('dialog', { name: 'Edit rumus' })
  await expect(formulaDialog.getByLabel('Rumus LaTeX')).toHaveValue('x^2 + 2')
  await formulaDialog.getByRole('button', { name: 'Tutup modal rumus' }).click()
  await page.getByRole('button', { name: 'Simpan draf' }).click()
  await expect(page.getByRole('row', { name: /Soal formula QA/ })).toBeVisible()
})
