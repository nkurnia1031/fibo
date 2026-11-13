<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atur ulang kata sandi — Fibo</title>
    <style>
        :root { font: 16px/1.5 system-ui, sans-serif; color: #17231d; background: #f7f8f4; }
        body { display: grid; min-height: 100vh; place-items: center; margin: 0; padding: 20px; }
        main { width: min(100%, 410px); border: 1px solid #e3e9e2; border-radius: 18px; padding: 28px; background: white; box-shadow: 0 12px 40px #1833210d; }
        h1 { margin: 0 0 8px; font-size: 25px; }
        p { color: #66756c; }
        label { display: block; margin-top: 16px; font-size: 13px; font-weight: 650; }
        input { width: 100%; box-sizing: border-box; margin-top: 6px; border: 1px solid #dce6db; border-radius: 9px; padding: 11px; font: inherit; }
        button { width: 100%; margin-top: 22px; border: 0; border-radius: 9px; padding: 12px; background: #20764b; color: white; font: inherit; font-weight: 750; cursor: pointer; }
        #message { min-height: 24px; margin-top: 14px; color: #a64f42; font-size: 13px; }
        #message.success { color: #20764b; }
    </style>
</head>
<body>
<main>
    <h1>Atur ulang kata sandi</h1>
    <p>Buat kata sandi baru untuk akun murid Fibo.</p>
    <form id="reset-form">
        <input id="token" name="token" type="hidden">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" autocomplete="email" required>
        <label for="password">Kata sandi baru</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>
        <label for="password_confirmation">Ulangi kata sandi baru</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="10" required>
        <button type="submit">Simpan kata sandi</button>
    </form>
    <div id="message" role="status" aria-live="polite"></div>
</main>
<script>
    const query = new URLSearchParams(window.location.search)
    document.querySelector('#token').value = query.get('token') || ''
    document.querySelector('#email').value = query.get('email') || ''
    document.querySelector('#reset-form').addEventListener('submit', async (event) => {
        event.preventDefault()
        const message = document.querySelector('#message')
        message.classList.remove('success')
        message.textContent = 'Menyimpan kata sandi…'
        try {
            const csrf = await fetch('/api/v1/auth/csrf', { headers: { Accept: 'application/json' }, credentials: 'same-origin' }).then((response) => response.json())
            const form = new FormData(event.currentTarget)
            const response = await fetch('/api/v1/student/password/reset', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf.token },
                body: JSON.stringify(Object.fromEntries(form.entries())),
            })
            const result = await response.json()
            if (!response.ok) throw new Error(result.message || Object.values(result.errors || {}).flat()[0] || 'Kata sandi belum dapat diperbarui.')
            message.classList.add('success')
            message.textContent = result.message
            event.currentTarget.reset()
        } catch (error) {
            message.textContent = error.message
        }
    })
</script>
</body>
</html>
