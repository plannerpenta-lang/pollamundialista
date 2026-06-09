<?php
require 'db.php';

// Calcular puntos por participante
$participantes = db()->query('SELECT * FROM participantes ORDER BY email')->fetchAll();
$partidos_map = [];
foreach (db()->query('SELECT * FROM partidos')->fetchAll() as $p) {
    $partidos_map[$p['id']] = $p;
}
$pronos_all = db()->query('
    SELECT pr.*, pa.goles_local AS r_local, pa.goles_visitante AS r_vis
    FROM pronosticos pr
    JOIN partidos pa ON pr.partido_id = pa.id
')->fetchAll();

$pronos_por_part = [];
foreach ($pronos_all as $pr) {
    $pronos_por_part[$pr['participante_id']][] = $pr;
}

$ranking = [];
foreach ($participantes as $p) {
    $pts = 0;
    $exactos = 0;
    $ganadores = 0;
    $total_prono = count($pronos_por_part[$p['id']] ?? []);
    foreach ($pronos_por_part[$p['id']] ?? [] as $pr) {
        if ($pr['r_local'] === null) continue;
        $gl_r = (int)$pr['r_local']; $gv_r = (int)$pr['r_vis'];
        $gl_p = (int)$pr['goles_local']; $gv_p = (int)$pr['goles_visitante'];
        if ($gl_p === $gl_r && $gv_p === $gv_r) { $pts += 10; $exactos++; }
        else {
            $gr = $gl_r > $gv_r ? 'L' : ($gv_r > $gl_r ? 'V' : 'E');
            $gp = $gl_p > $gv_p ? 'L' : ($gv_p > $gl_p ? 'V' : 'E');
            if ($gr === $gp) { $pts += 5; $ganadores++; }
        }
    }
    $ranking[] = ['email' => $p['email'], 'pts' => $pts, 'exactos' => $exactos, 'ganadores' => $ganadores, 'total' => $total_prono];
}

// Ordenar: más puntos primero, desempate exactos
usort($ranking, fn($a,$b) => $b['pts'] <=> $a['pts'] ?: $b['exactos'] <=> $a['exactos']);

$partidos_jugados = array_filter($partidos_map, fn($p) => $p['goles_local'] !== null);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ranking — Penta 2026</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--azul:#081EC9;--rojo:#FC0000;--blanco:#fff;--bg:#f4f6ff;--border:#d0d8ff;--text:#0a0f2e;--text2:#4a5280;--radius:10px;--gold:#FFB800;--silver:#8899AA;--bronze:#CD7F32;}
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
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;margin-bottom:24px;}
.stat{background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:16px;text-align:center;box-shadow:0 1px 4px rgba(8,30,201,0.06);}
.stat-num{font-family:'Bebas Neue',sans-serif;font-size:38px;color:var(--azul);line-height:1;}
.stat-label{font-size:11px;color:var(--text2);margin-top:4px;}
.card{background:#fff;border:1px solid var(--border);border-radius:var(--radius);overflow:hidden;box-shadow:0 1px 4px rgba(8,30,201,0.06);}
table{width:100%;border-collapse:collapse;}
th{font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--text2);padding:10px 14px;text-align:left;border-bottom:1px solid var(--border);background:#f8f9ff;}
td{padding:12px 14px;border-bottom:1px solid #eef0ff;font-size:13px;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#f4f6ff;}
.rank-num{font-family:'Bebas Neue',sans-serif;font-size:22px;}
.r1{color:var(--gold);}
.r2{color:var(--silver);}
.r3{color:var(--bronze);}
.rl{color:var(--rojo);}
.pts-big{font-family:'Bebas Neue',sans-serif;font-size:24px;color:var(--azul);}
.badge{display:inline-block;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:500;}
.badge-blue{background:#e8ecff;color:var(--azul);}
.badge-orange{background:#fff3e0;color:#e65c00;}
.empty{text-align:center;padding:48px;color:var(--text2);}
.leyenda{font-size:12px;color:var(--text2);margin-bottom:16px;}
</style>
</head>
<body>
<header class="header">
  <div class="logo"><img src="https://pentamarketing.co/penta-logo.png" alt="Penta" style="height:34px;vertical-align:middle;"/> <span>MUNDIAL 2026</span></div>
  <nav class="nav">
    <a href="index.php">Pronósticos</a>
    <a href="partidos.php">Ver Propuestas</a>
    <a href="ranking.php" class="active">Ranking</a>
  </nav>
</header>
<main class="main">
  <div class="section-title">TABLA DE <span>POSICIONES</span></div>

  <div class="stats-row">
    <div class="stat"><div class="stat-num"><?= count($participantes) ?></div><div class="stat-label">Participantes</div></div>
    <div class="stat"><div class="stat-num"><?= count($partidos_jugados) ?></div><div class="stat-label">Partidos jugados</div></div>
    <div class="stat"><div class="stat-num"><?= count($pronos_all) ?></div><div class="stat-label">Pronósticos</div></div>
    <div class="stat"><div class="stat-num"><?= array_sum(array_column($ranking, 'exactos')) ?></div><div class="stat-label">Exactos 🎯</div></div>
  </div>

  <p class="leyenda">🏆 Resultado exacto: <strong>10 pts</strong> &nbsp;|&nbsp; ✓ Ganador correcto: <strong>5 pts</strong> &nbsp;|&nbsp; Desempate por cantidad de exactos.</p>

  <div class="card">
    <?php if (empty($ranking)): ?>
      <div class="empty">Aún no hay participantes registrados.</div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th style="width:44px">#</th>
          <th>Participante</th>
          <th style="text-align:center">Exactos</th>
          <th style="text-align:center">Ganador</th>
          <th style="text-align:center">Pronósticos</th>
          <th style="text-align:right">Pts</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ranking as $i => $r):
          $pos = $i + 1;
          $es_ultimo = $pos === count($ranking) && count($ranking) > 1;
          $rc = $pos === 1 ? 'r1' : ($pos === 2 ? 'r2' : ($pos === 3 ? 'r3' : ($es_ultimo ? 'rl' : '')));
          $medal = $pos === 1 ? '🥇' : ($pos === 2 ? '🥈' : ($pos === 3 ? '🥉' : ($es_ultimo ? '😅' : $pos)));
        ?>
        <tr>
          <td><span class="rank-num <?= $rc ?>"><?= $medal ?></span></td>
          <td style="font-size:13px;"><?= htmlspecialchars($r['email']) ?></td>
          <td style="text-align:center"><span class="badge badge-blue"><?= $r['exactos'] ?> 🎯</span></td>
          <td style="text-align:center"><span class="badge badge-orange"><?= $r['ganadores'] ?> ✓</span></td>
          <td style="text-align:center;color:var(--text2)"><?= $r['total'] ?></td>
          <td style="text-align:right"><span class="pts-big"><?= $r['pts'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
