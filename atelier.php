<?php
session_start();
$pwd="ADMIN PASSWORD";
$apiKey=' POLLINATION API KEY '; 
$model="turbo"; 
$dirJson=__DIR__."/etape1/"; $dirImg=__DIR__."/image/";

if(isset($_GET['logout'])){session_destroy();header("Location: ?");exit;}
if(isset($_POST['pass']) && $_POST['pass']===$pwd) $_SESSION['auth']=true;
if(!isset($_SESSION['auth'])): ?>
    <!DOCTYPE html>
    <html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="style.css"></head>
    <body style="display:flex; justify-content:center; align-items:center; height:100vh; background:#f9f9f9;">
        <form method="POST" style="background:white; padding:40px; border-radius:8px; box-shadow:0 5px 15px rgba(0,0,0,0.05); width:100%; max-width:350px;">
            <h2 style="margin-top:0; text-align:center; font-weight:300;">ACCÈS ATELIER</h2>
            <input type="password" name="pass" placeholder="Code d'accès" style="margin-bottom:15px; text-align:center;">
            <button class="btn-submit">ENTRER</button>
        </form>
    </body></html>
<?php exit; endif;

function slugify($t){return trim(preg_replace('/[^a-z0-9]+/','-',strtolower($t)),'-');}

if(isset($_POST['action']) && $_POST['action']=='create') {
    $t=$_POST['target']; $g=$_POST['garment']; $p=$_POST['prompt']; $q=(int)$_POST['qty'];
    $full="
    
    Minimalist fashion photography of a $t top model wearing $g, very original modern design, 2026 fashion collection.  . Design based on large color-blocking with grafic representation of $p. Thick matte fabric, solid color. Structured cut, geometric. Studio lighting. High quality, luxury simplicity.
    
    
    
    
    
    Minimalist fashion photography of a $t top model wearing a $g, very original modern design, 2026 fashion collection. Trend: $p. Design based on color-blocking. Thick matte fabric, solid color. Structured cut, geometric. Studio lighting. High quality, luxury simplicity.";
    $id=uniqid()."_".slugify($g);
    $data=['id'=>$id, 'date_created'=>date("Y-m-d H:i:s"), 'target'=>$t, 'garment'=>$g, 'user_prompt'=>$p, 'full_prompt'=>$full, 'quantity_total'=>$q, 'quantity_done'=>0, 'quantity_remaining'=>$q, 'status'=>0, 'images'=>[]];
    file_put_contents($dirJson.$id.'.json', json_encode($data));
    header("Location: atelier.php"); exit;
}

if(isset($_GET['ajax']) && isset($_POST['jid'])) {
    $f=$dirJson.$_POST['jid'].'.json';
    if(!file_exists($f)) exit;
    $d=json_decode(file_get_contents($f),true);
    
    if($d['quantity_remaining'] <= 0) {
        $d['status']=1; file_put_contents($f, json_encode($d));
        echo json_encode(['status'=>'finished', 'done'=>$d['quantity_done']]); exit;
    }
    
    $seed=rand(1000,999999);
    $url="https://gen.pollinations.ai/image/  ".rawurlencode($d['full_prompt'])."?model=$model&seed=$seed&key=$apiKey&nologo=true&private=true&width=1024&height=1024";
    $bin=@file_get_contents($url);
    
    if($bin) {
        $fold=slugify($d['garment']);
        if(!is_dir($dirImg.$fold)) mkdir($dirImg.$fold,0777,true);
        $name=slugify(substr($d['user_prompt'],0,20))."_$seed.jpg";
        file_put_contents($dirImg.$fold."/".$name, $bin);
        
        $d['quantity_done']++; $d['quantity_remaining']--; $d['full_boutique']=0;
        $d['images'][]=['url'=>"image/$fold/$name", 'seed'=>$seed, 'date'=>date("Y-m-d H:i:s")];
        if($d['quantity_remaining']<=0) $d['status']=1;
        
        file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT));
        echo json_encode(['status'=>'success', 'url'=>"image/$fold/$name", 'done'=>$d['quantity_done'], 'total'=>$d['quantity_total']]);
    } else {
        echo json_encode(['status'=>'error']);
    }
    exit;
}

$orders=glob($dirJson."*.json"); 
usort($orders, function($a,$b){return filemtime($b)-filemtime($a);});
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Atelier Création</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header>
    <span class="brand">Atelier Création</span>
    <nav class="nav-links">
        <a href="index.php">Boutique</a>
        <a href="boutique.php">IA Analyste</a>
        <a href="?logout=1" style="color:red;">Sortir</a>
    </nav>
</header>
<div class="container" style="max-width:900px;">
    
    <!-- FORMULAIRE -->
    <div class="form-group">
        <h2 style="margin-top:0;">Nouvelle Production</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:15px;">
                <div>
                    <label>Modèle</label>
                    <select name="target" id="targetSelect" onchange="updateGarments()">
                        <option value="Femme">Femme</option>
                        <option value="Homme">Homme</option>
                    </select>
                </div>
                <div>
                    <label>Type de pièce</label>
                    <select name="garment" id="garmentSelect"></select>
                </div>
            </div>

            <div style="margin-bottom:15px;">
                <label>Imaginez votre design (Dictée vocale dispo)</label>
                <div class="input-group">
                    <textarea name="prompt" id="promptInput" placeholder="Décrivez le tissu, la coupe, les couleurs, l'ambiance..." required></textarea>
                    <button type="button" class="mic-btn" onclick="startDictation()">🎤</button>
                </div>
            </div>

            <div>
                <label>Quantité</label>
                <select name="qty"><option value="5">5 (Test Rapide)</option><option value="50">50 (Collection Complète)</option></select>
            </div>
            
            <button class="btn-submit">LANCER LA PRODUCTION</button>
        </form>
    </div>

    <!-- LISTE COMMANDES -->
    <h2>Commandes en cours</h2>
    <div class="order-list">
        <?php foreach($orders as $f): $o=json_decode(file_get_contents($f),true); ?>
        <div class="order-card" id="card_<?= $o['id'] ?>" onclick="toggleProcess('<?= $o['id'] ?>')">
            <div style="font-weight:bold; font-size:1rem;"><?= $o['garment'] ?></div>
            <div style="font-size:0.85rem; color:#666; margin-bottom:8px;">Cible: <?= $o['target'] ?> &bull; <?= mb_strimwidth($o['user_prompt'], 0, 40, "...") ?></div>
            
            <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid #eee; padding-top:8px;">
                <span style="font-size:0.85rem;">
                    Images: <strong id="done_<?= $o['id'] ?>"><?= $o['quantity_done'] ?></strong> / <?= $o['quantity_total'] ?>
                </span>
                <span id="status_<?= $o['id'] ?>" style="font-size:0.7rem; background:#f4f4f4; padding:4px 8px; border-radius:4px; font-weight:bold;">
                    <?= $o['status'] ? 'TERMINÉ' : 'CLIQUER POUR DÉMARRER' ?>
                </span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div id="live-preview"><img id="prev-img" src=""></div>

<script>
// --- LISTES DYNAMIQUES ---
const clothes = {
    'Femme': [
        "Robe de Soirée Haute Couture", "Tailleur Pantalon", "Ensemble Jupe et Veste", "Robe de Cocktail", 
        "Blouse en Soie", "Jupe Crayon", "Manteau Oversize", "Trench Coat", "Kimono Moderne", 
        "Combinaison Pantalon", "Corset Structuré", "Robe Fourreau", "Boléro Brodé", "Pantalon Palazzo", 
        "Veste en Tweed", "Cape Longue", "Robe Asymétrique", "Ensemble Lin Été", "Jupe Plissée", "Top Bustier"
    ],
    'Homme': [
        "Costume 3 Pièces", "Smoking", "Veste Officier", "Manteau Long en Laine", "Chemise en Soie", 
        "Pantalon à Pinces", "Blouson Aviateur", "Gilet de Costume", "Trench Coat Homme", "Pull Cachemire", 
        "Ensemble Lin Décontracté", "Veste Croisée", "Pantalon Chino Luxe", "Saharienne", "Blazer Velours", 
        "Chemise Col Mao", "Short Tailleur", "Cardigan Grosse Maille", "Caban", "Veste en Cuir Structurée"
    ]
};

function updateGarments() {
    const target = document.getElementById('targetSelect').value;
    const select = document.getElementById('garmentSelect');
    select.innerHTML = '';
    clothes[target].forEach(item => {
        let opt = document.createElement('option');
        opt.value = item;
        opt.innerText = item;
        select.appendChild(opt);
    });
}
updateGarments();

// --- DICTÉE VOCALE ---
function startDictation() {
    if (window.hasOwnProperty('webkitSpeechRecognition')) {
        var recognition = new webkitSpeechRecognition();
        recognition.continuous = false;
        recognition.interimResults = false;
        recognition.lang = "fr-FR";
        let btn = document.querySelector('.mic-btn');
        btn.classList.add('listening');
        recognition.onresult = function(e) {
            document.getElementById('promptInput').value += " " + e.results[0][0].transcript;
            recognition.stop();
            btn.classList.remove('listening');
        };
        recognition.onerror = function(e) { btn.classList.remove('listening'); };
        recognition.start();
    } else { alert("Utilisez Chrome pour la dictée vocale."); }
}

// --- GENERATION ---
let currentId = null;
let isRunning = false;

function toggleProcess(id) {
    if(currentId === id && isRunning) { stop(); return; }
    if(isRunning) { stop(); setTimeout(()=>start(id), 500); }
    else { start(id); }
}

function stop() {
    isRunning = false;
    if(currentId) {
        document.getElementById('card_'+currentId).classList.remove('active');
        let badge = document.getElementById('status_'+currentId);
        if(badge.innerText !== 'TERMINÉ') badge.innerText = "PAUSE";
    }
    currentId = null;
    document.getElementById('live-preview').style.display = 'none';
}

async function start(id) {
    currentId = id; isRunning = true;
    document.getElementById('card_'+id).classList.add('active');
    document.getElementById('status_'+id).innerHTML = 'EN COURS <div class="spinner"></div>';
    document.getElementById('live-preview').style.display = 'block';

    while(isRunning) {
        let fd = new FormData(); fd.append('jid', id);
        try {
            let req = await fetch('?ajax=1', {method:'POST', body:fd});
            let res = await req.json();
            
            if(res.status === 'success') {
                document.getElementById('done_'+id).innerText = res.done;
                document.getElementById('prev-img').src = res.url;
                if(res.done >= res.total) {
                    stop();
                    document.getElementById('status_'+id).innerText = "TERMINÉ";
                    break;
                }
            } else if(res.status === 'finished') {
                stop();
                document.getElementById('status_'+id).innerText = "TERMINÉ";
                break;
            }
        } catch(e) { console.log(e); }
        await new Promise(r => setTimeout(r, 1000));
    }
}
</script>
</body>
</html>
