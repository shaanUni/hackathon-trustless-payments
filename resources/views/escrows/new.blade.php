<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Create Escrow</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>label{display:block;margin:.5rem 0}</style>
</head>
<body>
  <h1>Create Escrow</h1>

  <form id="create-form">
    <label>Employer Address
      <input name="employer_address" required>
    </label>

    <label>Freelancer Address
      <input name="freelancer_address" required>
    </label>

    <label>Amount (ALGO)
      <input name="amount_algo" type="number" step="0.000001" min="0.000001" required>
    </label>

    <label>Deadline Round
      <input name="deadline_round" type="number" min="1" required>
    </label>

    <details>
      <summary>Advanced (optional)</summary>
      <label>Release SHA-256 (hex)
        <input name="sha256_release_hex" pattern="[0-9a-fA-F]{64}">
      </label>
      <label>Cancel SHA-256 (hex)
        <input name="sha256_cancel_hex" pattern="[0-9a-fA-F]{64}">
      </label>
    </details>

    <button type="submit">Create</button>
  </form>

  <pre id="out"></pre>

  <script>
  const form = document.getElementById('create-form');
  const out  = document.getElementById('out');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(form).entries());

    const res = await fetch('/api/escrows', {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
      body: JSON.stringify(data)
    });

    const json = await res.json();
    out.textContent = JSON.stringify(json, null, 2);

    if (res.ok && json.escrow_id) {
      // take the user to the show page
      window.location.href = `/escrows/${json.escrow_id}`;
    }
  });
  </script>
</body>
</html>
