<?php
session_start();
$dirJson = __DIR__ . "/etape1/";
$dirFavoris = __DIR__ . "/favoris/";

// Création du dossier favoris s'il n'existe pas
if (!is_dir($dirFavoris)) mkdir($dirFavoris, 0777, true);

// Identifiant unique du visiteur basé sur sa session
$userPrefix = session_id();

// AJAX FAVORIS
if (isset($_POST['action']) && $_POST['action'] === 'toggle_fav') {
    $imageUrl = $_POST['image_url'];
    $jsonId = $_POST['json_id'];
    $favId = md5($imageUrl);
    // Le nom du fichier inclut maintenant le prefixe de l'utilisateur
    $favFile = $dirFavoris . 'fav_' . $userPrefix . '_' . $favId . '.json';
    
    $newStatus = 1;
    if (file_exists($favFile)) {
        $d = json_decode(file_get_contents($favFile), true);
        $newStatus = ($d['statut_favoris'] == 1) ? 0 : 1;
        $d['statut_favoris'] = $newStatus;
        file_put_contents($favFile, json_encode($d));
    } else {
        $parent = json_decode(file_get_contents($dirJson . $jsonId . '.json'), true);
        file_put_contents($favFile, json_encode([
            'favoris_id' => $favId, 
            'image_url' => $imageUrl, 
            'statut_favoris' => 1,
            'parent_order_id' => $parent['id'], 
            'garment' => $parent['garment']
        ]));
    }
    echo json_encode(['status' => 'success', 'new_state' => $newStatus]); exit;
}

// CATALOGUE
$items = [];
foreach (glob($dirJson . "*.json") as $f) {
    $d = json_decode(file_get_contents($f), true);
    if (!empty($d['images'])) {
        foreach ($d['images'] as $img) {
            if (($img['boutique'] ?? 0) == 1) { 
                $items[] = [
                    'hash' => md5($img['url']),
                    'url' => $img['url'],
                    'json_id' => $d['id'],
                    'name' => $d['garment'] . " " . $d['target'],
                    'date' => $img['date'] ?? $d['date_created'],
                    'price' => $img['fiche_produit']['prix'] ?? "Sur Demande"
                ];
            }
        }
    }
}
usort($items, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 40;
$itemsPage = array_slice($items, ($page-1)*$perPage, $perPage);
$totalPages = ceil(count($items) / $perPage);

$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;

// RECUPERATION DES FAVORIS (Uniquement ceux de l'utilisateur actuel)
$myFavs = [];
foreach(glob($dirFavoris . "fav_" . $userPrefix . "_*.json") as $f) {
    $d = json_decode(file_get_contents($f), true);
    if(isset($d['statut_favoris']) && $d['statut_favoris'] == 1) {
        $myFavs[] = $d['image_url'];
    }
}

if (isset($_GET['add_cart'])) {
    if(!isset($_SESSION['cart'])) $_SESSION['cart']=[];
    $ref=$_GET['ref']; $found=false;
    foreach($_SESSION['cart'] as &$c) { if($c['ref']==$ref){$c['qty']++; $found=true; break;} }
    if(!$found) $_SESSION['cart'][]=['ref'=>$ref, 'name'=>$_GET['name'], 'price'=>$_GET['price'], 'img'=>$_GET['img'], 'qty'=>1];
    header("Location: index.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Haute Couture Today</title>
    <link rel="stylesheet" href="style.css?<?php echo rand(1, 999999); ?>">
</head>
<body>
<header>
    <a href="index.php" class="brand">Haute Couture</a>
    <nav class="nav-links">
        <a href="atelier.php"> Pro</a>
        <a href="favoris.php">Love <span class="badge" id="fav-badge"><?= count($myFavs) ?></span></a>
        <a href="cart.php">Card <span class="badge"><?= $cartCount ?></span></a>
    </nav>
</header>

<div class="container">
    <div class="grid">
        <?php foreach($itemsPage as $p): $isFav = in_array($p['url'], $myFavs); ?>
        <div class="card">
            <div class="card-img-wrap">
                <img src="<?= $p['url'] ?>" loading="lazy" alt="<?= htmlspecialchars($p['name']) ?>" onerror="this.onerror=null; this.src='https://hautecouture.today/image/695d9b68ce68c_704627.jpg';">
                <button class="fav-btn <?= $isFav?'active':'' ?>" onclick="toggleFav(this, '<?= $p['url'] ?>', '<?= $p['json_id'] ?>')">
                    <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                </button>
            </div>
            <div class="card-info">
                <div class="card-title"><?= htmlspecialchars($p['name']) ?></div>
                <div class="card-price"><?= htmlspecialchars($p['price']) ?></div>
            </div>
            <div class="actions">
                <a href="?add_cart=1&ref=<?= $p['hash'] ?>&name=<?= urlencode($p['name']) ?>&price=<?= urlencode($p['price']) ?>&img=<?= urlencode($p['url']) ?>" class="btn-add">Add to card</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div style="margin-top:40px; text-align:center; padding:20px;">
        <?php if($page>1): ?><a href="?page=<?= $page-1 ?>" style="margin-right:15px; text-decoration:none; color:black;">&larr; Previous</a><?php endif; ?>
        <span style="font-weight:bold; color:#888;">PAGE <?= $page ?></span>
        <?php if($page<$totalPages): ?><a href="?page=<?= $page+1 ?>" style="margin-left:15px; text-decoration:none; color:black;">Next &rarr;</a><?php endif; ?>
    </div>
</div>

<div id="modal" class="modal-overlay">
    <div class="modal-content">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <iframe id="iframe-details" src=""></iframe>
    </div>
</div>

<script>
async function toggleFav(btn, url, jid) {
    btn.classList.toggle('active');
    const badge = document.getElementById('fav-badge');
    let count = parseInt(badge.innerText);
    badge.innerText = btn.classList.contains('active') ? count + 1 : Math.max(0, count - 1);

    let fd = new FormData(); 
    fd.append('action','toggle_fav'); 
    fd.append('image_url',url); 
    fd.append('json_id',jid);
    try { await fetch('index.php', {method:'POST', body:fd}); } catch(e) { console.error(e); }
}

function openDetails(hash) {
    document.getElementById('iframe-details').src = 'details.php?ref='+hash;
    document.getElementById('modal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('modal').style.display = 'none';
    document.getElementById('iframe-details').src = '';
}
</script>
</body>
</html>
