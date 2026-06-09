<?php
require 'db.php';

// Cargar todos los partidos con sus pronósticos
$partidos_q = db()->query('SELECT * FROM partidos ORDER BY grupo, fecha, id');
$partidos = $partidos_q->fetchAll();

$pronos_q = db()->query('
    SELECT pr.*, p.email, pa.local, pa.visitante, pa.id AS partido_id
    FROM pronosticos pr
    JOIN participantes p ON pr.participante_id = p.id
    JOIN partidos pa ON pr.partido_id = pa.id
    ORDER BY pr.ingresado_at DESC
');
$todos_pronos = $pronos_q->fetchAll();

// Indexar pronósticos por partido
$pronos_por_partido = [];
foreach ($todos_pronos as $pr) {
    $pronos_por_partido[$pr['partido_id']][] = $pr;
}

function puntos_prono(array $prono, array $partido): ?int {
    if ($partido['goles_local'] === null) return null;
    $gl_r = (int)$partido['goles_local'];
    $gv_r = (int)$partido['goles_visitante'];
    $gl_p = (int)$prono['goles_local'];
    $gv_p = (int)$prono['goles_visitante'];
    if ($gl_p === $gl_r && $gv_p === $gv_r) return 10;
    $ganador_real = $gl_r > $gv_r ? 'L' : ($gv_r > $gl_r ? 'V' : 'E');
    $ganador_prono = $gl_p > $gv_p ? 'L' : ($gv_p > $gl_p ? 'V' : 'E');
    return $ganador_prono === $ganador_real ? 5 : 0;
}

// Agrupar partidos
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
<title>Propuestas por Partido — Penta 2026</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--azul:#081EC9;--rojo:#FC0000;--blanco:#fff;--bg:#f4f6ff;--border:#d0d8ff;--text:#0a0f2e;--text2:#4a5280;--radius:10px;}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);font-size:14px;}
.header{background:var(--azul);color:#fff;padding:0 24px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:100;box-shadow:0 2px 8px rgba(8,30,201,0.3);}
.logo{font-family:'Bebas Neue',sans-serif;font-size:24px;letter-spacing:2px;padding:14px 0;}
.logo span{color:#aab4ff;font-size:14px;letter-spacing:1px;margin-left:8px;}
.nav{display:flex;gap:2px;flex:1;}
.nav a{padding:14px 16px;color:rgba(255,255,255,0.7);font-size:13px;font-weight:500;text-decoration:none;border-bottom:2px solid transparent;white-space:nowrap;transition:all .2s;}
.nav a:hover,.nav a.active{color:#fff;border-bottom-color:#fff;}
.main{max-width:900px;margin:0 auto;padding:28px 20px;}
.section-title{font-family:'Bebas Neue',sans-serif;font-size:30px;letter-spacing:2px;margin-bottom:24px;color:var(--azul);}
.section-title span{color:var(--rojo);}
.grupo-titulo{font-family:'Bebas Neue',sans-serif;font-size:18px;letter-spacing:1px;color:var(--azul);margin:28px 0 10px;padding-bottom:4px;border-bottom:2px solid var(--azul);}
.partido-block{background:#fff;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:16px;overflow:hidden;box-shadow:0 1px 4px rgba(8,30,201,0.06);}
.partido-header{background:var(--azul);color:#fff;padding:12px 18px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.partido-vs{font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:1px;}
.partido-meta{font-size:12px;color:#aab4ff;}
.resultado-badge{background:var(--rojo);color:#fff;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:700;font-family:'Bebas Neue',sans-serif;letter-spacing:1px;}
.pendiente-badge{background:rgba(255,255,255,0.15);color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;}
.pronos-list{padding:0;}
.prono-row{display:flex;align-items:center;gap:10px;padding:10px 18px;border-bottom:1px solid #eef0ff;}
.prono-row:last-child{border-bottom:none;}
.prono-email{flex:1;font-size:13px;color:var(--text2);}
.prono-score{font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:2px;min-width:60px;text-align:center;}
.prono-time{font-size:11px;color:#8893b8;white-space:nowrap;}
.prono-pts{min-width:52px;text-align:right;font-family:'Bebas Neue',sans-serif;font-size:17px;}
.pts-10{color:var(--azul);}
.pts-5{color:#FF8C00;}
.pts-0{color:#ccc;}
.pts-pend{color:#ccc;font-size:13px;}
.empty{text-align:center;padding:20px;color:var(--text2);font-size:13px;}
.sort-bar{display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.sort-bar span{font-size:12px;color:var(--text2);font-weight:600;text-transform:uppercase;letter-spacing:.8px;}
.sort-btn{padding:7px 14px;border-radius:20px;border:1px solid var(--border);background:#fff;color:var(--text2);font-size:12px;font-weight:600;cursor:pointer;transition:all .18s;font-family:'DM Sans',sans-serif;}
.sort-btn.active{background:var(--azul);color:#fff;border-color:var(--azul);}
.sort-btn:hover:not(.active){border-color:var(--azul);color:var(--azul);}
</style>
</head>
<body>
<header class="header">
  <div class="logo"><img src="https://pentamarketing.co/penta-logo.png" alt="Penta" style="height:34px;vertical-align:middle;"/> <span>MUNDIAL 2026</span></div>
  <nav class="nav">
    <a href="index.php">Pronósticos</a>
    <a href="partidos.php" class="active">Ver Propuestas</a>
    <a href="ranking.php">Ranking</a>
  </nav>
</header>
<main class="main">
  <div class="section-title">PROPUESTAS POR <span>PARTIDO</span></div>

  <div class="sort-bar">
    <span>Ordenar partidos:</span>
    <button class="sort-btn active" onclick="sortGrupos('fecha')" id="btn-fecha">Por grupo</button>
    <button class="sort-btn" onclick="sortGrupos('fecha-asc')" id="btn-fecha-asc">Por fecha</button>
    <button class="sort-btn" onclick="sortGrupos('activo')" id="btn-activo">Más activo</button>
  </div>

  <div id="grupos-container">
  <?php foreach ($grupos as $grp => $pts): ?>
    <div class="grupo-titulo"><?= htmlspecialchars($grp) ?></div>
    <?php foreach ($pts as $partido): ?>
      <?php
        $tiene_resultado = $partido['goles_local'] !== null;
        $pronos = $pronos_por_partido[$partido['id']] ?? [];
      ?>
      <?php static $order_idx = 0; $order_idx++; ?>
      <div class="partido-block" data-count="<?= count($pronos) ?>" data-order="<?= $order_idx ?>" data-fecha="<?= htmlspecialchars($partido['fecha'] ?? '') ?>">
        <div class="partido-header">
          <div>
            <div class="partido-vs"><?= htmlspecialchars($partido['local']) ?> vs <?= htmlspecialchars($partido['visitante']) ?></div>
            <div class="partido-meta"><?= htmlspecialchars($partido['fecha']) ?> &nbsp;|&nbsp; <?= count($pronos) ?> propuesta<?= count($pronos) !== 1 ? 's' : '' ?></div>
          </div>
          <?php if ($tiene_resultado): ?>
            <span class="resultado-badge"><?= $partido['goles_local'] ?> — <?= $partido['goles_visitante'] ?></span>
          <?php else: ?>
            <span class="pendiente-badge">Resultado pendiente</span>
          <?php endif; ?>
        </div>
        <?php if (empty($pronos)): ?>
          <div class="empty">Aún no hay pronósticos para este partido.</div>
        <?php else: ?>
          <div class="pronos-list">
            <?php foreach ($pronos as $pr):
              $pts_val = puntos_prono($pr, $partido);
            ?>
              <div class="prono-row">
                <div class="prono-email"><?= htmlspecialchars($pr['email']) ?></div>
                <div class="prono-score"><?= $pr['goles_local'] ?>—<?= $pr['goles_visitante'] ?></div>
                <div class="prono-time">⏱ <?= htmlspecialchars(format_bogota($pr['ingresado_at'])) ?> (UTC-5)</div>
                <div class="prono-pts <?= $pts_val === null ? 'pts-pend' : 'pts-'.$pts_val ?>">
                  <?php if ($pts_val === null): ?>?<?php elseif ($pts_val > 0): ?>+<?= $pts_val ?><?php else: ?>0<?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endforeach; ?>
  </div><!-- /grupos-container -->
</main>
<script>
function sortGrupos(mode) {
  document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('btn-' + (mode === 'fecha-asc' ? 'fecha-asc' : mode)).classList.add('active');

  const container = document.getElementById('grupos-container');
  const blocks = Array.from(container.querySelectorAll('.partido-block'));

  if (mode === 'fecha-asc') {
    blocks.sort((a, b) => {
      const fa = a.dataset.fecha || '';
      const fb = b.dataset.fecha || '';
      return fa.localeCompare(fb);
    });
    blocks.forEach(b => container.appendChild(b));
  } else if (mode === 'activo') {
    blocks.sort((a, b) => {
      const ca = parseInt(a.dataset.count || '0');
      const cb = parseInt(b.dataset.count || '0');
      return cb - ca;
    });
    blocks.forEach(b => container.appendChild(b));
  } else {
    // Restaurar orden original por fecha (orden del DOM inicial)
    blocks.sort((a, b) => {
      const da = parseInt(a.dataset.order || '0');
      const db2 = parseInt(b.dataset.order || '0');
      return da - db2;
    });
    blocks.forEach(b => container.appendChild(b));
  }
}
</script>
</body>
</html>
