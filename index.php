<?php
require 'db.php';

$error = '';
$success = '';
$prono_guardado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $partido_id = (int)($_POST['partido_id'] ?? 0);
    $goles_local = $_POST['goles_local'] ?? '';
    $goles_visitante = $_POST['goles_visitante'] ?? '';

    // Validaciones
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico inválido.';
    } elseif (!str_ends_with($email, '@pentapro.com')) {
        $error = 'Solo se aceptan correos @pentapro.com.';
    } elseif ($partido_id <= 0) {
        $error = 'Selecciona un partido.';
    } elseif ($goles_local === '' || $goles_visitante === '') {
        $error = 'Ingresa los dos marcadores.';
    } else {
        $gl = (int)$goles_local;
        $gv = (int)$goles_visitante;
        if ($gl < 0 || $gl > 20 || $gv < 0 || $gv > 20) {
            $error = 'Marcadores fuera de rango.';
        } else {
            // Verificar que el partido existe
            $partido = db()->prepare('SELECT id FROM partidos WHERE id = ?');
            $partido->execute([$partido_id]);
            if (!$partido->fetch()) {
                $error = 'Partido no encontrado.';
            } else {
                // Crear o recuperar participante
                $ins = db()->prepare('INSERT IGNORE INTO participantes (email) VALUES (?)');
                $ins->execute([$email]);
                $p = db()->prepare('SELECT id FROM participantes WHERE email = ?');
                $p->execute([$email]);
                $part = $p->fetch();

                // Verificar que no tenga pronóstico previo
                $chk = db()->prepare('SELECT id FROM pronosticos WHERE participante_id = ? AND partido_id = ?');
                $chk->execute([$part['id'], $partido_id]);
                if ($chk->fetch()) {
                    $error = 'Ya tienes un pronóstico registrado para ese partido.';
                } else {
                    $now = now_bogota();
                    $ins2 = db()->prepare('INSERT INTO pronosticos (participante_id, partido_id, goles_local, goles_visitante, ingresado_at) VALUES (?,?,?,?,?)');
                    $ins2->execute([$part['id'], $partido_id, $gl, $gv, $now]);
                    $success = '¡Pronóstico guardado!';
                    $prono_guardado = true;
                }
            }
        }
    }
}

// Cargar partidos agrupados
$partidos_q = db()->query('SELECT * FROM partidos ORDER BY grupo, fecha, id');
$partidos = $partidos_q->fetchAll();
$grupos = [];
foreach ($partidos as $p) {
    $grupos[$p['grupo'] ?: $p['fase']][] = $p;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Polla Mundialista Penta 2026</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--azul:#081EC9;--rojo:#FC0000;--blanco:#fff;--bg:#f4f6ff;--border:#d0d8ff;--text:#0a0f2e;--text2:#4a5280;--radius:10px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px;min-height:100vh;}
.header{background:var(--azul);color:var(--blanco);padding:0 24px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(8,30,201,0.3);}
.logo{font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:2px;padding:14px 0;}
.logo span{color:#aab4ff;font-size:14px;letter-spacing:1px;margin-left:8px;}
.nav{display:flex;gap:2px;flex:1;}
.nav a{padding:14px 16px;color:rgba(255,255,255,0.7);font-size:13px;font-weight:500;text-decoration:none;border-bottom:2px solid transparent;white-space:nowrap;transition:all .2s;}
.nav a:hover,.nav a.active{color:#fff;border-bottom-color:#fff;}
.main{max-width:900px;margin:0 auto;padding:28px 20px;}
.section-title{font-family:'Bebas Neue',sans-serif;font-size:30px;letter-spacing:2px;margin-bottom:24px;color:var(--azul);}
.section-title span{color:var(--rojo);}
.card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:20px;box-shadow:0 1px 4px rgba(8,30,201,0.06);}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--text2);margin-bottom:6px;}
.field input,.field select{width:100%;padding:10px 12px;border:1px solid var(--border);border-radius:var(--radius);font-family:'DM Sans',sans-serif;font-size:13px;color:var(--text);outline:none;transition:border-color .2s;background:#fff;}
.field input:focus,.field select:focus{border-color:var(--azul);}
.score-row{display:flex;align-items:center;gap:10px;}
.score-row .field{flex:1;}
.score-sep{font-family:'Bebas Neue',sans-serif;font-size:22px;color:var(--text2);margin-top:16px;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:11px 22px;border-radius:var(--radius);font-family:'DM Sans',sans-serif;font-size:14px;font-weight:600;cursor:pointer;border:none;transition:all .18s;}
.btn-primary{background:var(--azul);color:#fff;}
.btn-primary:hover{background:#061aaa;}
.btn-danger{background:var(--rojo);color:#fff;}
.alert{padding:12px 16px;border-radius:var(--radius);font-size:13px;font-weight:500;margin-bottom:16px;}
.alert-error{background:#fff0f0;border:1px solid #ffcccc;color:#cc0000;}
.alert-success{background:#f0f4ff;border:1px solid #b0c0ff;color:var(--azul);}
.grupo-label{font-family:'Bebas Neue',sans-serif;font-size:16px;letter-spacing:1px;color:var(--azul);margin:20px 0 8px;padding-bottom:4px;border-bottom:2px solid var(--azul);}
.partido-opt{padding:4px 0;}
.partido-select-info{font-size:12px;color:var(--text2);margin-top:4px;}
@media(max-width:600px){.form-grid{grid-template-columns:1fr;}}
</style>
</head>
<body>
<header class="header">
  <div class="logo">PENTA <span>MUNDIAL 2026</span></div>
  <nav class="nav">
    <a href="index.php" class="active">Pronósticos</a>
    <a href="partidos.php">Ver Propuestas</a>
    <a href="ranking.php">Ranking</a>
  </nav>
</header>
<main class="main">
  <div class="section-title">INGRESA TU <span>PRONÓSTICO</span></div>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success">✓ <?= htmlspecialchars($success) ?> <a href="partidos.php">Ver todas las propuestas →</a></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST">
      <div class="form-grid" style="margin-bottom:16px;">
        <div class="field" style="grid-column:1/-1;">
          <label>Tu correo @pentapro.com *</label>
          <input type="email" name="email" placeholder="nombre@pentapro.com" value="<?= htmlspecialchars(strtolower($_POST['email'] ?? '')) ?>" required autocomplete="email"/>
          <div class="partido-select-info">Solo se aceptan correos @pentapro.com. Un pronóstico por partido.</div>
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Partido *</label>
          <select name="partido_id" required onchange="updateLabels(this)">
            <option value="">— Selecciona un partido —</option>
            <?php foreach ($grupos as $grp => $pts): ?>
              <optgroup label="<?= htmlspecialchars($grp) ?>">
                <?php foreach ($pts as $pt): ?>
                  <?php $res = $pt['goles_local'] !== null ? ' (' . $pt['goles_local'] . '-' . $pt['goles_visitante'] . ')' : ''; ?>
                  <option value="<?= $pt['id'] ?>"
                    data-local="<?= htmlspecialchars($pt['local']) ?>"
                    data-visitante="<?= htmlspecialchars($pt['visitante']) ?>"
                    <?= (isset($_POST['partido_id']) && $_POST['partido_id'] == $pt['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pt['local'] . ' vs ' . $pt['visitante']) ?> — <?= htmlspecialchars($pt['fecha']) ?><?= $res ?>
                  </option>
                <?php endforeach; ?>
              </optgroup>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="score-row" style="margin-bottom:20px;">
        <div class="field">
          <label id="lbl-local">Local</label>
          <input type="number" name="goles_local" id="gl" min="0" max="20" value="<?= htmlspecialchars($_POST['goles_local'] ?? '0') ?>" required style="text-align:center;font-family:'Bebas Neue',sans-serif;font-size:26px;padding:10px;"/>
        </div>
        <div class="score-sep">—</div>
        <div class="field">
          <label id="lbl-visitante">Visitante</label>
          <input type="number" name="goles_visitante" id="gv" min="0" max="20" value="<?= htmlspecialchars($_POST['goles_visitante'] ?? '0') ?>" required style="text-align:center;font-family:'Bebas Neue',sans-serif;font-size:26px;padding:10px;"/>
        </div>
      </div>
      <p style="font-size:12px;color:var(--text2);margin-bottom:16px;">🏆 Resultado exacto: <strong>10 pts</strong> &nbsp;|&nbsp; ✓ Ganador correcto: <strong>5 pts</strong></p>
      <button type="submit" class="btn btn-primary">Guardar pronóstico →</button>
    </form>
  </div>
</main>
<script>
function updateLabels(sel) {
  const opt = sel.options[sel.selectedIndex];
  const local = opt.dataset.local || 'Local';
  const vis = opt.dataset.visitante || 'Visitante';
  document.getElementById('lbl-local').textContent = local;
  document.getElementById('lbl-visitante').textContent = vis;
}
// Inicializar si hay un partido pre-seleccionado
const sel = document.querySelector('select[name=partido_id]');
if (sel && sel.value) updateLabels(sel);
</script>
</body>
</html>
