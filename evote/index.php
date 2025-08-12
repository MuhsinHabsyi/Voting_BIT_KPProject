<?php /* index.php */ ?>
<!doctype html>
<html lang="id">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>E-Voting</title>
<style>
  :root { font-family: system-ui, Arial, sans-serif; }
  body { margin:0; min-height:100svh; display:grid; place-items:center; background:#0f172a; }
  .card { width:min(520px, 92vw); background:white; padding:28px; border-radius:20px; box-shadow:0 10px 30px rgba(0,0,0,.25); }
  .row { margin:12px 0; }
  .center { text-align:center; }
  button { padding:12px 18px; border:0; border-radius:12px; box-shadow:0 6px 16px rgba(0,0,0,.2); cursor:pointer; }
</style>
<div class="card">
  <h2 class="center">E-Voting (Demo)</h2>
  <form method="post" action="submit.php" onsubmit="return cek();">
    <div class="row">
      <label>NIK (bebas, tidak divalidasi)</label><br>
      <input type="text" name="nik" maxlength="16" style="width:100%;padding:10px;border-radius:10px;border:1px solid #e5e7eb">
    </div>
    <div class="row">
      <label>Pilih Kandidat</label><br>
      <?php for ($i=1;$i<=5;$i++): ?>
        <label style="display:inline-block;margin:6px 10px">
          <input type="radio" name="kandidat" value="<?= $i ?>"> Kandidat <?= $i ?>
        </label>
      <?php endfor; ?>
    </div>
    <div class="row center">
      <button type="submit">Vote</button>
    </div>
  </form>
</div>
<script>
function cek(){
  const k = document.querySelector('input[name="kandidat"]:checked');
  if(!k){ alert('Pilih 1 kandidat.'); return false; }
  return true;
}
</script>
</html>
