<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Escrow #{{ $escrow->id }}</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:1rem}
    .row{display:flex;gap:2rem;flex-wrap:wrap}
    .card{border:1px solid #ddd;border-radius:12px;padding:1rem;min-width:300px}
    label{display:block;margin:.5rem 0}
    input,button,textarea{padding:.5rem}
    pre{background:#f9f9f9;padding:1rem;overflow:auto}
  </style>
</head>
<body>
  <h1>Escrow #{{ $escrow->id }}</h1>

  <div class="row">
    <div class="card">
      <h3>Details</h3>
      <div><strong>Escrow Address:</strong> {{ $escrow->escrow_address }}</div>
      <div><strong>Employer:</strong> {{ $escrow->employer_address }}</div>
      <div><strong>Freelancer:</strong> {{ $escrow->freelancer_address }}</div>
      <div><strong>Amount (µALGO):</strong> {{ $escrow->amount_microalgo }}</div>
      <div><strong>Deadline Round:</strong> {{ $escrow->deadline_round }}</div>
      <div><strong>Status:</strong> <span id="status">{{ $escrow->status }}</span></div>
    </div>

    <div class="card">
      <h3>Fund (manual)</h3>
      <p>Send exactly <b>{{ $escrow->amount_microalgo }}</b> µALGO to:</p>
      <p><code>{{ $escrow->escrow_address }}</code></p>
      <p>Use your TestNet wallet (e.g., Pera). Then click “Refresh”.</p>
      <button id="refresh">Refresh</button>
    </div>

    <div class="card">
      <h3>Release</h3>
      <form id="release-form">
        <label>Preimage (bytes or ASCII, optional if past deadline)
          <input name="preimage">
        </label>
        <button type="submit">Release to Freelancer</button>
      </form>
      <hr>
      <h3>Refund</h3>
      <form id="refund-form">
        <label>Cancel Preimage (required before deadline)
          <input name="preimage" required>
        </label>
        <button type="submit">Refund to Employer</button>
      </form>
    </div>

    <div class="card">
      <h3>On-chain</h3>
      <pre id="chain"></pre>
    </div>
  </div>

  <script>
  const escrowId = {{ $escrow->id }};
  const chainBox = document.getElementById('chain');
  const refreshBtn = document.getElementById('refresh');
  const statusEl = document.getElementById('status');

  async function loadChain() {
    const res = await fetch(`/api/escrows/${escrowId}`);
    const json = await res.json();
    chainBox.textContent = JSON.stringify(json, null, 2);
    if (json.escrow && json.escrow.status) statusEl.textContent = json.escrow.status;
  }

  refreshBtn.addEventListener('click', loadChain);
  loadChain();

  document.getElementById('release-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const preimage = new FormData(e.target).get('preimage') || null;
    const res = await fetch(`/api/escrows/${escrowId}/release`, {
      method:'POST',
      headers:{ 'Content-Type':'application/json','Accept':'application/json' },
      body: JSON.stringify(preimage ? { preimage } : {})
    });
    const json = await res.json();
    alert(res.ok ? `Released! txId=${json.txId}` : `Error: ${JSON.stringify(json)}`);
    loadChain();
  });

  document.getElementById('refund-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const preimage = new FormData(e.target).get('preimage');
    const res = await fetch(`/api/escrows/${escrowId}/refund`, {
      method:'POST',
      headers:{ 'Content-Type':'application/json','Accept':'application/json' },
      body: JSON.stringify({ preimage })
    });
    const json = await res.json();
    alert(res.ok ? `Refunded! txId=${json.txId}` : `Error: ${JSON.stringify(json)}`);
    loadChain();
  });
  </script>
</body>
</html>
