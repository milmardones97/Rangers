<?php
session_start();
if (!isset($_SESSION['usuario'])) { header('Location: ../index.php'); exit; }
require_once __DIR__ . '/access_control.php';
if (!sispol_puede_entrar_asuntos_internos($_SESSION['rango'] ?? '')) { header('Location: ../panel.php'); exit; }
require_once __DIR__ . '/../lib/agent_profiles.php';
require_once __DIR__ . '/../lib/rank_icons.php';

$id = trim((string) ($_GET['key'] ?? ''));
$actor = strtoupper((string) ($_SESSION['usuario'] ?? 'USUARIO'));
$notice = ''; $error = '';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['action'] ?? '') === 'decorations') {
            rangers_save_agent_decorations($id, (array) ($_POST['decorations'] ?? []));
            $notice = 'CONDECORACIONES ACTUALIZADAS.';
        } else {
            rangers_add_agent_profile_update($id, (string) ($_POST['categoria'] ?? ''), (string) ($_POST['detalle'] ?? ''), $actor, (string) ($_POST['fecha'] ?? ''));
            $notice = 'ACTUALIZACIÓN REGISTRADA.';
        }
    }
    $profile = rangers_agent_profile($id);
    if (!$profile) throw new RuntimeException('Archivo de agente no encontrado.');
} catch (Throwable $exception) { $error = $exception->getMessage(); $profile = rangers_agent_profile($id); }

$agent = $profile['agent'] ?? []; $user = $profile['user'] ?? []; $updates = $profile['updates'] ?? [];
$decorations = $profile['decorations'] ?? []; $catalog = require __DIR__ . '/../config/decorations.php';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Archivo de agente</title>
<style>
:root{--g:#d7ee63;--b:#acd52d;--s:#dff58b;--r:#ff6b6b}*{box-sizing:border-box}body{margin:0;background:#000;color:var(--g);font-family:"Courier New",monospace}body:before{content:"";position:fixed;inset:0;pointer-events:none;background:repeating-linear-gradient(to bottom,rgba(255,255,255,.025) 0 1px,transparent 1px 4px)}main{position:relative;width:min(1160px,calc(100% - 30px));margin:28px auto}.top{background:var(--b);color:#111;padding:12px 16px;font:bold 24px "Courier New",monospace}.grid{display:grid;grid-template-columns:360px 1fr;gap:16px;margin-top:16px}.panel{border:1px solid rgba(215,238,99,.3);padding:16px;background:repeating-linear-gradient(-45deg,rgba(163,214,63,.08) 0 12px,rgba(163,214,63,.03) 12px 24px)}.photo{width:100%;aspect-ratio:1;object-fit:cover;background:#050805;border:2px solid var(--g)}.rank-icon{width:34px;height:34px;object-fit:contain;vertical-align:middle;margin-right:8px}h2{font-size:21px;margin:0 0 14px}h3{font-size:16px;margin:18px 0 10px}.meta{color:#fff;line-height:1.5}.update{border-left:4px solid var(--g);padding:10px;margin:9px 0;background:rgba(0,0,0,.72)}.update small{color:#fff}label{display:block;color:#fff;font-weight:bold;font-size:13px;margin:9px 0 5px}input,select,textarea{width:100%;background:#000;color:var(--s);border:2px solid rgba(172,213,45,.48);padding:10px;font:15px "Courier New",monospace;text-transform:uppercase}textarea{min-height:110px}.btn{display:inline-block;margin-top:12px;padding:10px 14px;border:2px solid var(--g);background:#000;color:var(--g);font:bold 15px "Courier New",monospace;text-decoration:none;cursor:pointer}.btn:hover{background:var(--g);color:#000}.msg{padding:10px;margin:12px 0;background:#111;border-left:4px solid var(--g);font-weight:bold}.error{color:var(--r);border-color:var(--r)}.medal-list{display:flex;flex-wrap:wrap;gap:9px;padding:10px;background:rgba(0,0,0,.55);border:1px solid rgba(215,238,99,.22)}.medal{width:100%;min-height:52px;display:grid;place-items:center;padding:6px;background:#080a05;border:1px solid rgba(215,238,99,.25)}.medal img{max-width:100%;max-height:52px;object-fit:contain}.medal small{font-weight:bold;text-align:center;margin-top:4px}.medal-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;max-height:340px;overflow:auto;padding:3px}.medal-option{display:flex;align-items:center;gap:8px;padding:8px;border:1px solid rgba(215,238,99,.25);background:rgba(0,0,0,.55);cursor:pointer}.medal-option:hover{border-color:var(--g)}.medal-option input{appearance:none;-webkit-appearance:none;width:20px;height:20px;flex:none;margin:0;padding:0;border:2px solid var(--g);background:#000;cursor:pointer;display:grid;place-content:center}.medal-option input:checked{background:var(--b);box-shadow:0 0 9px rgba(215,238,99,.55)}.medal-option input:checked:after{content:"✓";color:#071000;font:bold 16px Arial;line-height:1}.medal-option:has(input:checked){border-color:var(--g);background:rgba(172,213,45,.16)}.medal-option input:focus-visible{outline:2px solid #fff;outline-offset:2px}.medal-option img{width:86px;height:25px;object-fit:contain;flex:none}.medal-option span{font-size:12px;font-weight:bold}.empty-medals{font-size:13px;color:#fff;text-align:center;padding:12px}@media(max-width:800px){.grid{grid-template-columns:1fr}main{width:calc(100% - 20px);margin:12px auto}.top{font-size:18px}.medal-grid{grid-template-columns:1fr}}
</style></head><body><main>
<div class="top">ARCHIVO DE AGENTE</div>
<?php if ($notice): ?><div class="msg"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
<?php if ($error): ?><div class="msg error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
<div class="grid"><aside class="panel">
<?php if (!empty($agent['profile_image'])): ?><img class="photo" src="<?php echo htmlspecialchars($agent['profile_image']); ?>" alt="Perfil"><?php else: ?><div class="photo" style="display:grid;place-items:center">SIN FOTO</div><?php endif; ?>
<h2><?php echo htmlspecialchars($agent['full_name'] ?? ''); ?></h2>
<div class="meta">USUARIO: <?php echo htmlspecialchars($user['username'] ?? ''); ?><br>RANGO: <?php if ($icon = rangers_rank_icon_url($agent['rank_name'] ?? '')): ?><img class="rank-icon" src="<?php echo htmlspecialchars($icon); ?>" alt=""><?php endif; ?><?php echo htmlspecialchars($agent['rank_name'] ?? ''); ?><br>PLACA: <?php echo htmlspecialchars($agent['badge_code'] ?? 'SIN PLACA'); ?><br>ESTADO: <?php echo htmlspecialchars($agent['status'] ?? 'ACTIVO'); ?><br>INGRESO: <?php echo htmlspecialchars($agent['join_date'] ?? ''); ?></div>
<h3>CONDECORACIONES</h3><div class="medal-list"><?php if ($decorations === []): ?><div class="empty-medals">SIN CONDECORACIONES REGISTRADAS.</div><?php else: ?><?php foreach ($decorations as $medal): ?><div class="medal"><img src="../assets/medallas/<?php echo rawurlencode($medal['file']); ?>" alt="<?php echo htmlspecialchars($medal['name']); ?>"><small><?php echo htmlspecialchars($medal['name']); ?></small></div><?php endforeach; ?><?php endif; ?></div>
</aside><section class="panel"><h2>ACTUALIZACIONES DEL PERFIL</h2>
<form method="post"><label>CATEGORÍA</label><select name="categoria"><option>ACTIVO</option><option>CONDECORACIÓN</option><option>SANCIÓN</option><option>ASCENSO</option><option>DESCENSO</option><option>SUSPENSIÓN</option><option>DESPEDIDO</option><option>RETIRADO</option></select><label>FECHA Y HORA</label><input type="datetime-local" name="fecha" value="<?php echo date('Y-m-d\TH:i'); ?>"><label>DETALLE</label><textarea name="detalle" required></textarea><button class="btn">REGISTRAR ACTUALIZACIÓN</button></form>
<h3>ASIGNAR CONDECORACIONES</h3><form method="post"><input type="hidden" name="action" value="decorations"><div class="medal-grid"><?php foreach ($catalog as $key => $medal): ?><label class="medal-option"><input type="checkbox" name="decorations[]" value="<?php echo htmlspecialchars($key); ?>" <?php echo isset($decorations[$key]) ? 'checked' : ''; ?>><img src="../assets/medallas/<?php echo rawurlencode($medal['file']); ?>" alt=""><span><?php echo htmlspecialchars($medal['name']); ?></span></label><?php endforeach; ?></div><button class="btn">GUARDAR CONDECORACIONES</button></form>
<div style="margin-top:20px"><?php foreach ($updates as $update): ?><article class="update"><strong><?php echo htmlspecialchars($update['category'] ?? ''); ?></strong><br><small>[<?php echo htmlspecialchars($update['at'] ?? ''); ?>] <?php echo htmlspecialchars($update['author'] ?? ''); ?></small><p><?php echo nl2br(htmlspecialchars($update['body'] ?? '')); ?></p></article><?php endforeach; ?></div>
</section></div><a class="btn" href="archivos_agentes.php">VOLVER A ARCHIVOS</a></main></body></html>
