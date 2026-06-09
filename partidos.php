<?php
require 'db.php';

// Orden canónico de fases
$fase_orden = ['grupos' => 0, 'r16' => 1, 'qf' => 2, 'sf' => 3, 'final' => 4];
$fase_labels = [
    'grupos' => 'Fase de Grupos',
    'r16'    => '16avos de Final',
    'qf'     => 'Cuartos de Final',
    'sf'     => 'Semifinales',
    'final'  => 'Final',
];

$partidos_q = db()->query('SELECT * FROM partidos ORDER BY fecha, id');
$partidos = $partidos_q->fetchAll();

$pronos_q = db()->query('
    SELECT pr.*, p.email, pa.local, pa.visitante, pa.id AS partido_id
    FROM pronosticos pr
    JOIN participantes p ON pr.participante_id = p.id
    JOIN partidos pa ON pr.partido_id = pa.id
    ORDER BY pr.ingresado_at DESC
');
$todos_pronos = $pronos_q->fetchAll();

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
    $ganador_real  = $gl_r > $gv_r ? 'L' : ($gv_r > $gl_r ? 'V' : 'E');
    $ganador_prono = $gl_p > $gv_p ? 'L' : ($gv_p > $gl_p ? 'V' : 'E');
    return $ganador_prono === $ganador_real ? 5 : 0;
}

// Agrupar por fase en orden canónico
$por_fase = [];
foreach ($partidos as $p) {
    $por_fase[$p['fase']][] = $p;
}
uksort($por_fase, fn($a, $b) => ($fase_orden[$a] ?? 99) - ($fase_orden[$b] ?? 99));
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
.section-title{font-family:'Bebas Neue',sans-serif;font-size:30px;letter-spacing:2px;margin-bottom:20px;color:var(--azul);}
.section-title span{color:var(--rojo);}

/* Buscador */
.search-wrap{position:relative;margin-bottom:24px;}
.search-wrap input{width:100%;padding:11px 16px 11px 40px;border:1px solid var(--border);border-radius:var(--radius);font-family:'DM Sans',sans-serif;font-size:14px;color:var(--text);outline:none;background:#fff;transition:border-color .2s;}
.search-wrap input:focus{border-color:var(--azul);}
.search-wrap svg{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text2);pointer-events:none;}
#no-results{display:none;text-align:center;padding:40px;color:var(--text2);font-size:14px;}

/* Acordeón de fase */
.fase-section{margin-bottom:12px;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;background:#fff;box-shadow:0 1px 4px rgba(8,30,201,0.06);}
.fase-toggle{width:100%;display:flex;align-items:center;justify-content:space-between;padding:14px 20px;background:#fff;border:none;cursor:pointer;font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:2px;color:var(--azul);text-align:left;transition:background .15s;}
.fase-toggle:hover{background:#f0f4ff;}
.fase-toggle.open{background:#f0f4ff;}
.fase-toggle .meta{font-family:'DM Sans',sans-serif;font-size:12px;font-weight:500;color:var(--text2);letter-spacing:0;}
.fase-toggle .arrow{transition:transform .25s;color:var(--azul);}
.fase-toggle.open .arrow{transform:rotate(180deg);}
.fase-body{display:none;padding:16px;}
.fase-body.open{display:block;}

/* Partido */
.partido-block{background:#fff;border:1px solid var(--border);border-radius:var(--radius);margin-bottom:12px;overflow:hidden;box-shadow:0 1px 3px rgba(8,30,201,0.05);}
.partido-block:last-child{margin-bottom:0;}
.partido-header{background:var(--azul);color:#fff;padding:11px 16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.partido-vs{font-family:'Bebas Neue',sans-serif;font-size:19px;letter-spacing:1px;}
.partido-meta{font-size:12px;color:#aab4ff;}
.resultado-badge{background:var(--rojo);color:#fff;padding:3px 10px;border-radius:20px;font-size:13px;font-weight:700;font-family:'Bebas Neue',sans-serif;letter-spacing:1px;}
.pendiente-badge{background:rgba(255,255,255,0.15);color:#fff;padding:3px 10px;border-radius:20px;font-size:11px;}
.pronos-list{padding:0;}
.prono-row{display:flex;align-items:center;gap:10px;padding:9px 16px;border-bottom:1px solid #eef0ff;}
.prono-row:last-child{border-bottom:none;}
.prono-email{flex:1;font-size:13px;color:var(--text2);}
.prono-score{font-family:'Bebas Neue',sans-serif;font-size:20px;letter-spacing:2px;min-width:60px;text-align:center;}
.prono-time{font-size:11px;color:#8893b8;white-space:nowrap;}
.prono-pts{min-width:52px;text-align:right;font-family:'Bebas Neue',sans-serif;font-size:17px;}
.pts-10{color:var(--azul);}
.pts-5{color:#FF8C00;}
.pts-0{color:#ccc;}
.pts-pend{color:#ccc;font-size:13px;}
.empty{text-align:center;padding:16px;color:var(--text2);font-size:13px;}
</style>
</head>
<body>
<header class="header">
  <div class="logo"><img src="https://pentamarketing.co/penta-logo.png" alt="Penta" style="height:34px;vertical-align:middle;"/> <span>MUNDIAL 2026</span></div>
  <nav class="nav">
    <a href="index.php">Pronósticos</a>
    <a href="partidos.php" class="active">Ver Propuestas</a>
    <a href="ranking.php">Ranking</a>
    <a href="admin-penta2026.php" style="margin-left:auto;opacity:.6;font-size:12px;">Admin</a>
  </nav>
</header>
<main class="main">
  <div class="section-title">PROPUESTAS POR <span>PARTIDO</span></div>

  <!-- Buscador -->
  <div class="search-wrap">
    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
    <input type="text" id="buscador" placeholder="Buscar partido (ej: Brasil, Argentina, Grupo C…)" autocomplete="off"/>
  </div>

  <div id="fases-container">
  <?php foreach ($por_fase as $fase => $partidos_fase):
    $label = $fase_labels[$fase] ?? $fase;
    $total_pronos_fase = 0;
    foreach ($partidos_fase as $p) $total_pronos_fase += count($pronos_por_partido[$p['id']] ?? []);
    $open = $fase === 'grupos';
  ?>
    <div class="fase-section" data-fase="<?= $fase ?>">
      <button class="fase-toggle <?= $open ? 'open' : '' ?>" onclick="toggleFase(this)">
        <span><?= htmlspecialchars($label) ?></span>
        <span style="display:flex;align-items:center;gap:12px;">
          <span class="meta"><?= count($partidos_fase) ?> partidos &nbsp;·&nbsp; <?= $total_pronos_fase ?> pronósticos</span>
          <svg class="arrow" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </span>
      </button>
      <div class="fase-body <?= $open ? 'open' : '' ?>">
        <?php foreach ($partidos_fase as $partido):
          $tiene_resultado = $partido['goles_local'] !== null;
          $pronos = $pronos_por_partido[$partido['id']] ?? [];
        ?>
          <div class="partido-block"
               data-local="<?= htmlspecialchars(strtolower($partido['local'])) ?>"
               data-visitante="<?= htmlspecialchars(strtolower($partido['visitante'])) ?>"
               data-grupo="<?= htmlspecialchars(strtolower($partido['grupo'] ?? '')) ?>"
               data-fase-key="<?= $fase ?>">
            <div class="partido-header">
              <div>
                <div class="partido-vs"><?= htmlspecialchars($partido['local']) ?> vs <?= htmlspecialchars($partido['visitante']) ?></div>
                <div class="partido-meta"><?= htmlspecialchars($partido['fecha'] ?? '') ?> &nbsp;|&nbsp; <?= htmlspecialchars($partido['grupo'] ?: strtoupper($fase)) ?> &nbsp;|&nbsp; <?= count($pronos) ?> propuesta<?= count($pronos) !== 1 ? 's' : '' ?></div>
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
      </div>
    </div>
  <?php endforeach; ?>
  </div>
  <div id="no-results">No se encontraron partidos para "<span id="no-results-q"></span>".</div>
</main>
<script>
function toggleFase(btn) {
  btn.classList.toggle('open');
  btn.nextElementSibling.classList.toggle('open');
}

document.getElementById('buscador').addEventListener('input', function() {
  const q = this.value.trim().toLowerCase();
  const blocks = document.querySelectorAll('.partido-block');
  const fases  = document.querySelectorAll('.fase-section');
  let total = 0;

  if (!q) {
    blocks.forEach(b => b.style.display = '');
    fases.forEach(f => {
      f.style.display = '';
      // Restaurar estado original del acordeón
      const toggle = f.querySelector('.fase-toggle');
      const body   = f.querySelector('.fase-body');
      const esGrupos = f.dataset.fase === 'grupos';
      toggle.classList.toggle('open', esGrupos);
      body.classList.toggle('open', esGrupos);
    });
    document.getElementById('no-results').style.display = 'none';
    return;
  }

  blocks.forEach(b => {
    const local     = b.dataset.local || '';
    const visitante = b.dataset.visitante || '';
    const grupo     = b.dataset.grupo || '';
    const faseKey   = b.dataset.faseKey || '';
    const match = local.includes(q) || visitante.includes(q) || grupo.includes(q) || faseKey.includes(q);
    b.style.display = match ? '' : 'none';
    if (match) total++;
  });

  // Mostrar/ocultar fases y abrir las que tienen resultados
  fases.forEach(f => {
    const visibles = Array.from(f.querySelectorAll('.partido-block')).filter(b => b.style.display !== 'none');
    if (visibles.length > 0) {
      f.style.display = '';
      f.querySelector('.fase-toggle').classList.add('open');
      f.querySelector('.fase-body').classList.add('open');
    } else {
      f.style.display = 'none';
    }
  });

  const nr = document.getElementById('no-results');
  nr.style.display = total === 0 ? 'block' : 'none';
  document.getElementById('no-results-q').textContent = this.value.trim();
});
</script>
</body>
</html>
