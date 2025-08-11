<?php


?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>E‑Voting – 5 Kandidat</title>
  <style>
    :root {
      --bg: #0f172a;         /* slate-900 */
      --card: #0b1226;       /* slightly lighter */
      --accent: #22c55e;     /* green-500 */
      --accent-600: #16a34a; /* green-600 */
      --text: #e5e7eb;       /* gray-200 */
      --muted: #94a3b8;      /* slate-400 */
      --danger: #ef4444;     /* red-500 */
      --warning: #f59e0b;    /* amber-500 */
    }

    * { box-sizing: border-box; }
    html, body { height: 100%; }

    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, "Helvetica Neue", Arial, "Apple Color Emoji", "Segoe UI Emoji";
      color: var(--text);
      background: radial-gradient(1200px 800px at 50% -20%, #1e293b 0%, var(--bg) 50%, #0b1120 100%);
      display: grid;
      place-items: center; /* Centers everything vertically & horizontally */
    }

    .card {
      width: min(560px, 92vw);
      background: linear-gradient(180deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
      border: 1px solid rgba(255,255,255,0.08);
      backdrop-filter: blur(6px);
      border-radius: 20px;
      padding: 28px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.35);
      display: flex;
      flex-direction: column;
      align-items: center; /* center children horizontally */
      text-align: center;  /* center text */
    }

    h1 {
      margin: 0 0 6px;
      font-size: clamp(1.25rem, 1.1rem + 1.2vw, 1.8rem);
      letter-spacing: 0.2px;
    }
    p.sub {
      margin: 0 0 18px;
      color: var(--muted);
      font-size: 0.95rem;
    }

    form {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center; /* keep form controls centered */
      gap: 16px;
    }

    .field {
      width: 100%;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
    }

    label {
      width: 100%;
      text-align: left;
      font-weight: 600;
      font-size: 0.95rem;
      color: #cbd5e1; /* slate-300 */
    }

    input[type="text"] {
      width: 100%;
      padding: 12px 14px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(2,6,23,0.4);
      color: var(--text);
      outline: none;
      font-size: 1rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input[type="text"]:focus {
      border-color: rgba(34,197,94,0.6);
      box-shadow: 0 0 0 4px rgba(34,197,94,0.12);
    }

    .hint { color: var(--muted); font-size: 0.85rem; }

    .radios {
      width: 100%;
      display: grid;
      grid-template-columns: 1fr;
      gap: 10px;
    }

    .radio-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 12px;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 12px;
      background: rgba(2,6,23,0.35);
      cursor: pointer;
      transition: border-color 0.2s ease, background 0.2s ease;
    }

    .radio-item:hover, .radio-item:has(input:focus) {
      border-color: rgba(34,197,94,0.5);
      background: rgba(34,197,94,0.06);
    }

    .radio-item input {
      width: 18px; height: 18px;
      accent-color: var(--accent);
      cursor: pointer;
    }

    .radio-label { font-weight: 600; }

    button[type="submit"] {
      width: 100%;
      padding: 12px 16px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(180deg, var(--accent), var(--accent-600));
      color: white;
      font-weight: 700;
      letter-spacing: 0.2px;
      font-size: 1rem;
      cursor: pointer;
      box-shadow: 0 8px 18px rgba(34,197,94,0.25);
      transition: transform 0.06s ease, filter 0.15s ease;
    }
    button[type="submit"]:active { transform: translateY(1px); }
    button[type="submit"]:disabled { filter: grayscale(0.4) brightness(0.7); cursor: not-allowed; }

    /* Toast */
    .toast {
      position: fixed;
      top: 16px; left: 50%; transform: translateX(-50%);
      max-width: min(560px, 92vw);
      background: rgba(2,6,23,0.85);
      border: 1px solid rgba(255,255,255,0.18);
      color: var(--text);
      padding: 12px 14px;
      border-radius: 12px;
      box-shadow: 0 10px 24px rgba(0,0,0,0.4);
      display: flex; align-items: center; gap: 10px;
      z-index: 9999;
      opacity: 0; pointer-events: none;
      transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .toast.show { opacity: 1; pointer-events: auto; transform: translate(-50%, 0); }
    .toast .dot { width: 10px; height: 10px; border-radius: 999px; }
    .toast.success .dot { background: var(--accent); }
    .toast.warn .dot { background: var(--warning); }
    .toast.error .dot { background: var(--danger); }

    footer { margin-top: 14px; color: var(--muted); font-size: 0.8rem; }
  </style>
</head>
<body>
  <div class="card" role="main" aria-labelledby="title">
    <h1 id="title">E‑Voting</h1>
    <!-- <p class="sub">Please enter your NIK and choose exactly one candidate.</p> -->

    <form id="voteForm" novalidate>
      <!-- NIK Field -->
      <div class="field">
        <label for="nik">NIK (16 digit) <span aria-hidden="true">*</span></label>
        <input
          id="nik"
          name="nik"
          type="text"
          inputmode="numeric"
          autocomplete="off"
          placeholder="e.g. 3201010101010101"
          maxlength="16"
          required
          aria-describedby="nikHint"
          pattern="\\d{1,16}" />
        <!-- <div id="nikHint" class="hint">Digits only, up to 16 characters.</div> -->
      </div>

      <!-- Candidate Radios -->
      <div class="field">
        <label>Pilih 1 kandidat<span aria-hidden="true">*</span></label>
        <div class="radios" role="radiogroup" aria-label="Kandidat">
          <label class="radio-item"><input type="radio" name="kandidat" value="Kandidat 1" /> <span class="radio-label">Kandidat 1</span></label>
          <label class="radio-item"><input type="radio" name="kandidat" value="Kandidat 2" /> <span class="radio-label">Kandidat 2</span></label>
          <label class="radio-item"><input type="radio" name="kandidat" value="Kandidat 3" /> <span class="radio-label">Kandidat 3</span></label>
          <label class="radio-item"><input type="radio" name="kandidat" value="Kandidat 4" /> <span class="radio-label">Kandidat 4</span></label>
          <label class="radio-item"><input type="radio" name="kandidat" value="Kandidat 5" /> <span class="radio-label">Kandidat 5</span></label>
        </div>
      </div>

      <button type="submit" id="voteBtn">Vote</button>
    </form>

    <!-- <footer>All components are centered vertically & horizontally.</footer> -->
  </div>

  <!-- Toast container (single instance) -->
  <div id="toast" class="toast" role="status" aria-live="polite" aria-atomic="true">
    <span class="dot" aria-hidden="true"></span>
    <span id="toastMsg"></span>
  </div>

  <script>
    (function() {
      const form = document.getElementById('voteForm');
      const nikInput = document.getElementById('nik');
      const voteBtn = document.getElementById('voteBtn');

      // Simple in-memory set to avoid double submit by the same NIK while page is open
      const votedSet = new Set();

      function showToast(message, type = 'success', timeout = 2400) {
        const toast = document.getElementById('toast');
        const msg = document.getElementById('toastMsg');
        msg.textContent = message;
        toast.className = 'toast show ' + type; // reset + apply
        if (toast.querySelector('.dot')) {
          // dot color handled by class
        }
        clearTimeout(showToast._tId);
        showToast._tId = setTimeout(() => {
          toast.classList.remove('show');
        }, timeout);
      }

      function isDigits(str) { return /^\d+$/.test(str); }

      form.addEventListener('submit', function(e) {
        e.preventDefault();

        const nik = nikInput.value.trim();
        const candidate = (new FormData(form)).get('candidate');

        // Validation
        if (!nik) {
          showToast('NIK harus dimasukan!.', 'warn');
          nikInput.focus();
          return;
        }
        if (!isDigits(nik)) {
          showToast('NIK berupa angka!.', 'warn');
          nikInput.focus();
          return;
        }
        if (nik.length > 16) {
          showToast('NIK harus 16 digit.', 'warn');
          nikInput.focus();
          return;
        }
        if (!candidate) {
          showToast('Please choose exactly one candidate.', 'warn');
          return;
        }
        if (votedSet.has(nik)) {
          showToast('This NIK has already voted (session).', 'error');
          return;
        }

        // Simulate vote submit (here you could call fetch to your backend)
        voteBtn.disabled = true;
        setTimeout(() => {
          votedSet.add(nik);
          showToast(`Berhasil!`, 'success');
          form.reset();
          voteBtn.disabled = false;
        }, 600);
      });

      // Optional: live limit NIK length to 16 & digits only
      nikInput.addEventListener('input', () => {
        // remove non-digits
        nikInput.value = nikInput.value.replace(/\D+/g, '').slice(0, 16);
      });
    })();
  </script>
</body>
</html>
