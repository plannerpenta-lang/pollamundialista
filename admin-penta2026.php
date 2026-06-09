<?php
require 'db.php';
session_start();

$msg = '';
$msg_type = 'success';

// Login
if (isset($_POST['login'])) {
    $pass = $_POST['password'] ?? '';
    // Cambia esta contraseña antes de subir
    if ($pass === 'Penta2026Admin!') {
        $_SESSION['admin_penta'] = true;
    } else {
        $msg = 'Contraseña incorrecta.';
        $msg_type = 'error';
    }
}

if (isset($_POST['logout'])) {
    unset($_SESSION['admin_penta']);
    header('Location: admin-penta2026.php');
    exit;
}

$logged = !empty($_SESSION['admin_penta']);

if ($logged) {
    // Guardar resultado de partido
    if (isset($_POST['guardar_resultado'])) {
        $pid = (int)$_POST['partido_id'];
        $gl  = $_POST['goles_local'];
        $gv  = $_POST['goles_visitante'];
        if ($gl !== '' && $gv !== '') {
            $upd = db()->prepare('UPDATE partidos SET goles_local=?, goles_visitante=? WHERE id=?');
            $upd->execute([(int)$gl, (int)$gv, $pid]);
            $msg = 'Resultado guardado.';
        } else {
            $msg = 'Ingresa los dos marcadores.'; $msg_type = 'error';
        }
    }

    // Borrar resultado
    if (isset($_POST['borrar_resultado'])) {
        $pid = (int)$_POST['partido_id'];
        db()->prepare('UPDATE partidos SET goles_local=NULL, goles_visitante=NULL WHERE id=?')->execute([$pid]);
        $msg = 'Resultado eliminado.';
    }

    // Agregar partido
    if (isset($_POST['agregar_partido'])) {
        $local = trim($_POST['p_local'] ?? '');
        $vis   = trim($_POST['p_visitante'] ?? '');
        $fase  = $_POST['p_fase'] ?? 'grupos';
        $grupo = trim($_POST['p_grupo'] ?? '');
        $fecha = $_POST['p_fecha'] ?? '';
        if ($local && $vis) {
            $ins = db()->prepare('INSERT INTO partidos (local, visitante, fase, grupo, fecha) VALUES (?,?,?,?,?)');
            $ins->execute([$local, $vis, $fase, $grupo, $fecha ?: null]);
            $msg = "Partido $local vs $vis agregado.";
        } else {
            $msg = 'Local y visitante son obligatorios.'; $msg_type = 'error';
        }
    }

    // Eliminar pronóstico
    if (isset($_POST['eliminar_prono'])) {
        $prid = (int)$_POST['prono_id'];
        db()->prepare('DELETE FROM pronosticos WHERE id = ?')->execute([$prid]);
        $msg = 'Pronóstico eliminado.';
    }

    // Cargar datos
    $partidos = db()->query('SELECT * FROM partidos ORDER BY grupo, fecha, id')->fetchAll();
    $pronos_admin = db()->query('
        SELECT pr.id, pr.goles_local, pr.goles_visitante, pr.ingresado_at,
               p.email, pa.id AS partido_id, pa.local, pa.visitante
        FROM pronosticos pr
        JOIN participantes p ON pr.participante_id = p.id
        JOIN partidos pa ON pr.partido_id = pa.id
        ORDER BY pa.grupo, pa.fecha, pa.id, pr.ingresado_at DESC
    ')->fetchAll();
    $pronos_x_partido = [];
    foreach ($pronos_admin as $pr) {
        $pronos_x_partido[$pr['partido_id']][] = $pr;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Penta 2026</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--azul:#081EC9;--rojo:#FC0000;--blanco:#fff;--bg:#f4f6ff;--border:#d0d8ff;--text:#0a0f2e;--text2:#4a5280;--radius:10px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px;}
.header{background:var(--azul);color:#fff;padding:0 24px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(8,30,201,0.3);}
.logo{font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:2px;padding:14px 0;}
.logo span{color:#aab4ff;font-size:14px;letter-spacing:1px;margin-left:8px;}
.nav a{padding:14px 16px;color:rgba(255,255,255,0.7);font-size:13px;font-weight:500;text-decoration:none;white-space:nowrap;}
.nav a:hover{color:#fff;}
.main{max-width:900px;margin:0 auto;padding:28px 20px;}
.section-title{font-family:'Bebas Neue',sans-serif;font-size:30px;letter-spacing:2px;margin-bottom:24px;color:var(--azul);}
.section-title span{color:var(--rojo);}
.card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:22px;margin-bottom:20px;box-shadow:0 1px 4px rgba(8,30,201,0.06);}
.card-title{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:var(--text2);margin-bottom:16px;}
.field label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--text2);margin-bottom:6px;}
.field input,.field select{width:100%;padding:9px 12px;border:1px solid var(--border);border-radius:var(--radius);font-family:'DM Sans',sans-serif;font-size:13px;color:var(--text);outline:none;background:#fff;}
.field input:focus,.field select:focus{border-color:var(--azul);}
.form-grid{display:grid;gap:12px;}
.form-grid.two{grid-template-columns:1fr 1fr;}
.form-grid.three{grid-template-columns:1fr 1fr 1fr;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:var(--radius);font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .18s;}
.btn-primary{background:var(--azul);color:#fff;}
.btn-primary:hover{background:#061aaa;}
.btn-danger{background:var(--rojo);color:#fff;}
.btn-danger:hover{background:#cc0000;}
.btn-sm{padding:5px 10px;font-size:12px;}
.btn-outline{background:#fff;color:var(--text2);border:1px solid var(--border);}
.btn-outline:hover{border-color:var(--azul);color:var(--azul);}
.alert{padding:12px 16px;border-radius:var(--radius);font-size:13px;font-weight:500;margin-bottom:16px;}
.alert-success{background:#f0f4ff;border:1px solid #b0c0ff;color:var(--azul);}
.alert-error{background:#fff0f0;border:1px solid #ffcccc;color:#cc0000;}
table{width:100%;border-collapse:collapse;}
th{font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--text2);padding:8px 12px;text-align:left;border-bottom:1px solid var(--border);background:#f8f9ff;}
td{padding:10px 12px;border-bottom:1px solid #eef0ff;font-size:13px;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
.score-mini{display:flex;align-items:center;gap:6px;}
.score-mini input{width:42px;padding:5px;text-align:center;border:1px solid var(--border);border-radius:6px;font-family:'Bebas Neue',sans-serif;font-size:16px;outline:none;}
.score-mini input:focus{border-color:var(--azul);}
.resultado-actual{font-family:'Bebas Neue',sans-serif;font-size:18px;color:var(--azul);letter-spacing:2px;}
.pin-wrap{max-width:360px;margin:80px auto;background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:36px;text-align:center;box-shadow:0 2px 12px rgba(8,30,201,0.08);}
.pin-wrap h2{font-family:'Bebas Neue',sans-serif;font-size:26px;letter-spacing:2px;color:var(--azul);margin-bottom:8px;}
.pin-wrap p{color:var(--text2);font-size:13px;margin-bottom:20px;}
.pin-input{width:100%;padding:12px;border:1px solid var(--border);border-radius:var(--radius);font-size:16px;text-align:center;outline:none;margin-bottom:14px;}
.pin-input:focus{border-color:var(--azul);}
@media(max-width:600px){.form-grid.two,.form-grid.three{grid-template-columns:1fr;}}
</style>
</head>
<body>
<header class="header">
  <div class="logo"><img src="https://pentamarketing.co/penta-logo.png" alt="Penta" style="height:34px;vertical-align:middle;"/> <span>MUNDIAL 2026</span></div>
  <nav class="nav" style="display:flex;gap:2px;flex:1;">
    <a href="index.php">← Volver al sitio</a>
    <?php if ($logged): ?>
      <form method="POST" style="margin-left:auto;padding:10px 0;">
        <button type="submit" name="logout" class="btn btn-outline btn-sm">Cerrar sesión</button>
      </form>
    <?php endif; ?>
  </nav>
</header>

<?php if (!$logged): ?>
<div class="pin-wrap">
  <h2>🔐 ADMIN</h2>
  <p>Acceso exclusivo para administradores</p>
  <?php if ($msg): ?>
    <div class="alert alert-error"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <form method="POST">
    <input type="password" name="password" class="pin-input" placeholder="Contraseña" autofocus/>
    <button type="submit" name="login" class="btn btn-primary" style="width:100%;">Entrar</button>
  </form>
</div>

<?php else: ?>
<main class="main">
  <div class="section-title">PANEL <span>ADMIN</span></div>

  <?php if ($msg): ?>
    <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <!-- Agregar partido -->
  <div class="card">
    <div class="card-title">+ Agregar nuevo partido</div>
    <form method="POST">
      <div class="form-grid three" style="margin-bottom:12px;">
        <div class="field"><label>Local *</label><input type="text" name="p_local" placeholder="Ej: Brasil"/></div>
        <div class="field"><label>Visitante *</label><input type="text" name="p_visitante" placeholder="Ej: Argentina"/></div>
        <div class="field"><label>Fase</label>
          <select name="p_fase">
            <option value="grupos">Grupos</option>
            <option value="r16">16avos</option>
            <option value="qf">Cuartos</option>
            <option value="sf">Semifinal</option>
            <option value="final">Final</option>
          </select>
        </div>
      </div>
      <div class="form-grid two" style="margin-bottom:14px;">
        <div class="field"><label>Grupo / Etiqueta</label><input type="text" name="p_grupo" placeholder="Ej: Grupo A"/></div>
        <div class="field"><label>Fecha</label><input type="date" name="p_fecha"/></div>
      </div>
      <button type="submit" name="agregar_partido" class="btn btn-primary">+ Agregar partido</button>
    </form>
  </div>

  <!-- Lista de partidos con resultados -->
  <div class="card">
    <div class="card-title">Partidos y resultados oficiales</div>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>Partido</th>
            <th>Fase / Grupo</th>
            <th>Fecha</th>
            <th>Resultado actual</th>
            <th>Establecer resultado</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($partidos as $p): ?>
          <tr>
            <td style="font-weight:500;"><?= htmlspecialchars($p['local']) ?> vs <?= htmlspecialchars($p['visitante']) ?></td>
            <td style="color:var(--text2);"><?= htmlspecialchars($p['grupo'] ?: $p['fase']) ?></td>
            <td style="color:var(--text2);"><?= htmlspecialchars($p['fecha'] ?? '—') ?></td>
            <td>
              <?php if ($p['goles_local'] !== null): ?>
                <span class="resultado-actual"><?= $p['goles_local'] ?> — <?= $p['goles_visitante'] ?></span>
                <form method="POST" style="display:inline;margin-left:8px;" onsubmit="return confirm('¿Borrar este resultado?')">
                  <input type="hidden" name="partido_id" value="<?= $p['id'] ?>"/>
                  <button type="submit" name="borrar_resultado" class="btn btn-danger btn-sm">✕</button>
                </form>
              <?php else: ?>
                <span style="color:#ccc;font-size:12px;">Pendiente</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" style="display:flex;align-items:center;gap:8px;">
                <input type="hidden" name="partido_id" value="<?= $p['id'] ?>"/>
                <div class="score-mini">
                  <input type="number" name="goles_local" min="0" max="20" placeholder="—" value="<?= $p['goles_local'] ?? '' ?>"/>
                  <span style="font-family:'Bebas Neue';font-size:16px;color:var(--text2);">:</span>
                  <input type="number" name="goles_visitante" min="0" max="20" placeholder="—" value="<?= $p['goles_visitante'] ?? '' ?>"/>
                </div>
                <button type="submit" name="guardar_resultado" class="btn btn-primary btn-sm">✓</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <!-- Pronósticos por partido -->
  <div class="card">
    <div class="card-title">Pronósticos registrados (con opción de eliminar)</div>
    <?php foreach ($partidos as $p):
      $pronos_p = $pronos_x_partido[$p['id']] ?? [];
      if (empty($pronos_p)) continue;
    ?>
      <div style="margin-bottom:20px;">
        <div style="font-weight:600;font-size:13px;margin-bottom:8px;padding-bottom:4px;border-bottom:1px solid var(--border);">
          <?= htmlspecialchars($p['local']) ?> vs <?= htmlspecialchars($p['visitante']) ?>
          <span style="color:var(--text2);font-weight:400;font-size:12px;"> — <?= htmlspecialchars($p['grupo'] ?: $p['fase']) ?> · <?= count($pronos_p) ?> pronóstico<?= count($pronos_p) !== 1 ? 's' : '' ?></span>
        </div>
        <table>
          <thead>
            <tr><th>Email</th><th>Pronóstico</th><th>Ingresado</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($pronos_p as $pr): ?>
            <tr>
              <td><?= htmlspecialchars($pr['email']) ?></td>
              <td style="font-family:'Bebas Neue',sans-serif;font-size:17px;letter-spacing:1px;"><?= $pr['goles_local'] ?> — <?= $pr['goles_visitante'] ?></td>
              <td style="color:var(--text2);font-size:12px;"><?= htmlspecialchars(format_bogota($pr['ingresado_at'])) ?></td>
              <td>
                <form method="POST" onsubmit="return confirm('¿Eliminar pronóstico de <?= htmlspecialchars(addslashes($pr['email'])) ?>?')">
                  <input type="hidden" name="prono_id" value="<?= $pr['id'] ?>"/>
                  <button type="submit" name="eliminar_prono" class="btn btn-danger btn-sm">Eliminar</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endforeach; ?>
  </div>
</main>
<?php endif; ?>
</body>
</html>
