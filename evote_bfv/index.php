<?php /* index.php - responsive & elegant voting form */ ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>E-Voting Demo</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    :root{
      --bg: #0f172a;
      --card: rgba(255,255,255,.12);
      --card-solid: #ffffff;
      --text: #0b1220;
      --muted: #6b7280;
      --accent: #2563eb;
      --accent-2: #22d3ee;
      --ring: rgba(37,99,235,.55);
      --ok: #10b981;
      --warn: #f59e0b;
      --shadow: 0 20px 60px rgba(0,0,0,.35);
      --radius: 20px;
    }
    * { box-sizing: border-box; }
    html,body { height: 100%; }
    body{
      margin:0; font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif;
      color: var(--text); background:
        radial-gradient(1200px 800px at 20% -10%, #334155 0%, transparent 60%),
        radial-gradient(1000px 700px at 120% 10%, #1e293b 0%, transparent 50%),
        linear-gradient(180deg, #0b1020, #0f172a);
      display:grid; place-items:center; padding:24px;
    }
    .shell{
      width:min(960px, 100%);
      display:grid; gap:20px;
    }
    .hero{
      color:#e5e7eb; text-align:center; line-height:1.25;
    }
    .hero h1{ margin:0; font-size: clamp(22px, 3.5vw, 32px); font-weight:800; letter-spacing:.2px; }
    .hero p{ margin:.5rem 0 0; color:#cbd5e1; font-size: clamp(13px, 2.5vw, 15px); }
    .card{
      background: linear-gradient(180deg, rgba(255,255,255,.86), #fff);
      border-radius: var(--radius); box-shadow: var(--shadow); padding: clamp(18px, 3vw, 28px);
      border:1px solid rgba(255,255,255,.6);
    }
    .row{ margin:12px 0; }
    .label{ font-weight:700; margin-bottom:8px; color:#111827; }
    .hint{ color: var(--muted); font-size:13px; margin-top:6px; }

    /* NIK input */
    .input{
      width:100%; padding:12px 14px; border-radius:14px;
      border:1px solid #e5e7eb; outline:none; font-size:15px; background:#fff;
      transition: box-shadow .2s, border-color .2s, transform .06s;
    }
    .input:focus{
      border-color: var(--accent);
      box-shadow: 0 0 0 5px rgba(37,99,235,.12);
    }

    /* Candidate grid */
    .grid{
      display:grid; gap:12px;
      grid-template-columns: repeat(1, minmax(0,1fr));
    }
    @media (min-width:560px){ .grid{ grid-template-columns: repeat(2, minmax(0,1fr)); } }
    @media (min-width:900px){ .grid{ grid-template-columns: repeat(5, minmax(0,1fr)); } }

    .opt{
      position:relative; border-radius:16px; overflow:hidden; background:#fff; border:1px solid #e5e7eb;
      transition: transform .06s ease, box-shadow .15s ease;
    }
    .opt:hover{ transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.10); }
    .opt input{ position:absolute; inset:0; opacity:0; cursor:pointer; }
    .opt label{
      display:block; padding:16px; height:100%; cursor:pointer;
      display:grid; place-items:center; text-align:center; gap:10px;
    }
    .tag{
      display:inline-block; padding:8px 12px; border-radius:999px;
      background:linear-gradient(135deg, rgba(34,211,238,.18), rgba(37,99,235,.18));
      color:#0b1220; font-weight:700; letter-spacing:.2px;
    }
    .kname{ font-weight:700; }
    .opt:has(input:checked){
      border-color: transparent;
      box-shadow: 0 0 0 4px var(--ring), 0 12px 28px rgba(37,99,235,.20);
      background: linear-gradient(180deg, #ffffff, rgba(255,255,255,.92));
      outline: 2px solid transparent; /* avoid accessibility outline clash */
    }

    /* Actions */
    .actions{ display:flex; gap:10px; flex-wrap:wrap; justify-content:center; margin-top:6px; }
    .btn{
      appearance:none; border:0; padding:12px 18px; border-radius:14px;
      font-weight:700; cursor:pointer; transition: transform .06s ease, box-shadow .15s ease, background .2s;
    }
    .btn:disabled{ opacity:.7; cursor:not-allowed; }
    .btn-primary{
      background:linear-gradient(135deg, var(--accent), var(--accent-2)); color:#fff;
      box-shadow: 0 10px 24px rgba(37,99,235,.35);
    }
    .btn-primary:hover{ transform: translateY(-1px); box-shadow: 0 14px 28px rgba(37,99,235,.4); }
    .btn-ghost{ background:#eef2ff; color:#1f2937; }
    .footnote{ text-align:center; color:#6b7280; font-size:12px; margin-top:10px; }

    /* Toast */
    .toast{
      position: fixed; left:50%; top:16px; transform: translateX(-50%) translateY(-20px);
      background:#111827; color:#fff; border-radius:12px; padding:10px 14px; font-size:14px;
      box-shadow: 0 12px 36px rgba(0,0,0,.35); opacity:0; pointer-events:none; transition: .25s ease;
      display:flex; gap:10px; align-items:center;
    }
    .toast.show{ opacity:1; transform: translateX(-50%) translateY(0); }
    .dot{ width:8px; height:8px; border-radius:999px; background: var(--warn); box-shadow: 0 0 0 4px rgba(245,158,11,.2); }
    .sr-only{ position:absolute!important; height:1px;width:1px;overflow:hidden;clip:rect(1px,1px,1px,1px);white-space:nowrap; border:0; padding:0; }
  </style>
</head>
<body>

  <div class="shell">
    <div class="hero">
      <h1>E-Voting • Demo Form</h1>
      <p>Masukkan NIK (opsional) lalu pilih satu kandidat. Kirim, dan sistem menyimpan ke <i>plain</i> &amp; <i>encrypted</i> sekaligus.</p>
    </div>

    <form id="voteForm" class="card" method="post" action="submit.php" novalidate>
      <!-- NIK -->
      <div class="row">
        <div class="label">NIK <span class="hint">(opsional, bebas, max 16 karakter)</span></div>
        <input class="input" type="text" name="nik" maxlength="16" placeholder="Contoh: 3201XXXXXXXXXXXX">
        <div class="hint">Boleh kosong atau duplikat. Tidak ada validasi untuk pengujian skenario.</div>
      </div>

      <!-- Kandidat -->
      <div class="row">
        <div class="label">Pilih Kandidat <span class="hint">(wajib pilih salah satu)</span></div>
        <div class="grid">
          <?php for ($i=1;$i<=5;$i++): ?>
            <div class="opt">
              <input id="cand<?= $i ?>" type="radio" name="kandidat" value="<?= $i ?>">
              <label for="cand<?= $i ?>">
                <span class="tag">#<?= $i ?></span>
                <div class="kname">Kandidat <?= $i ?></div>
              </label>
            </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="actions">
        <button id="btnVote" class="btn btn-primary" type="submit">Vote</button>
        <button class="btn btn-ghost" type="reset">Reset</button>
      </div>
      <div class="footnote">Dengan menekan “Vote”, data akan dicatat untuk pengujian akurasi HE.</div>

      <!-- Live region for accessibility -->
      <div class="sr-only" aria-live="polite" id="live"></div>
    </form>
  </div>

  <!-- Toast -->
  <div id="toast" class="toast" role="status" aria-live="polite">
    <span class="dot" aria-hidden="true"></span>
    <span id="toastMsg">Pesan</span>
  </div>

  <script>
    const form = document.getElementById('voteForm');
    const btn  = document.getElementById('btnVote');
    const toast = document.getElementById('toast');
    const toastMsg = document.getElementById('toastMsg');
    const live = document.getElementById('live');

    function showToast(msg){
      toastMsg.textContent = msg;
      live.textContent = msg;
      toast.classList.add('show');
      clearTimeout(showToast._t);
      showToast._t = setTimeout(()=> toast.classList.remove('show'), 2400);
    }

    form.addEventListener('submit', (e)=>{
      const checked = form.querySelector('input[name="kandidat"]:checked');
      if(!checked){
        e.preventDefault();
        showToast('Silakan pilih satu kandidat terlebih dahulu.');
        return;
      }
      // nice UX: prevent double submit
      btn.disabled = true;
      btn.textContent = 'Mengirim...';
    });

    form.addEventListener('reset', ()=>{
      // UX: restore button state quickly
      btn.disabled = false;
      btn.textContent = 'Vote';
      showToast('Form di-reset.');
    });
  </script>
</body>
</html>
