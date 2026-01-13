<?php
session_start();
$dir = __DIR__ . "/favoris/";
$userPrefix = session_id();

// 1. Suppression d'un favori (Uniquement si appartient à l'utilisateur)
if (isset($_GET['del'])) {
    $f = $dir . "fav_" . $userPrefix . "_" . $_GET['del'] . ".json";
    if (file_exists($f)) {
        $d = json_decode(file_get_contents($f), true);
        $d['statut_favoris'] = 0;
        file_put_contents($f, json_encode($d));
    }
    header("Location: favoris.php" . (isset($_GET['p']) ? "?p=".$_GET['p'] : "")); 
    exit;
}

// 2. Récupération des favoris actifs DE L'UTILISATEUR
$all_favs = [];
// On utilise le prefixe dans le glob pour ne lister que ses fichiers
$files = glob($dir . "fav_" . $userPrefix . "_*.json");
if ($files) {
    foreach ($files as $f) {
        $d = json_decode(file_get_contents($f), true);
        if ($d && isset($d['statut_favoris']) && $d['statut_favoris'] == 1) {
            $d['file_mtime'] = filemtime($f); 
            $all_favs[] = $d;
        }
    }
}

// 3. Tri par le plus récent
usort($all_favs, function($a, $b) {
    return $b['file_mtime'] <=> $a['file_mtime'];
});

// 4. Logique de Pagination
$limit = 40; 
$total_items = count($all_favs);
$total_pages = ceil($total_items / $limit);
$current_page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;

if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$offset = ($current_page - 1) * $limit;
$favs = array_slice($all_favs, $offset, $limit);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Best off</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; background: #f9f9f9; color: #333; }
        header { background: #fff; padding: 15px 20px; display: flex; align-items: center; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 100; }
        .brand-link { text-decoration: none; color: #666; font-size: 0.9rem; margin-right: 20px; }
        .brand-title { font-weight: bold; font-size: 1.1rem; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 20px; }
        @media (max-width: 480px) { .grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
        .card { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
        .card-img-wrap { width: 100%; aspect-ratio: 3/4; overflow: hidden; background: #eee; }
        .card-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
        .card-info { padding: 10px; flex-grow: 1; }
        .card-title { font-size: 0.85rem; font-weight: 500; text-transform: capitalize; }
        .btn-del { display: block; text-align: center; background: #f8f8f8; color: #ff4444; padding: 10px; text-decoration: none; font-size: 0.7rem; font-weight: bold; border-top: 1px solid #eee; transition: background 0.2s; }
        .btn-del:hover { background: #ffebeb; }
        .pagination { display: flex; justify-content: center; align-items: center; margin: 40px 0; gap: 5px; flex-wrap: wrap; }
        .pagination a { padding: 8px 15px; text-decoration: none; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 4px; font-size: 0.9rem; }
        .pagination a.active { background: #000; color: #fff; border-color: #000; }
        .pagination a:hover:not(.active) { background: #f0f0f0; }
        .empty-msg { text-align: center; margin-top: 100px; color: #999; }
    </style>
</head>
<body>

<header>
    <a href="index.php" class="brand-link">&larr; Back Timeline</a>
    <span class="brand-title">My Best off (<?= $total_items ?>)</span>
</header>

<div class="container">
    <?php if(empty($favs)): ?>
        <div class="empty-msg">
            <p>Nothing for now, click on Heart on picture you like.</p>
            <a href="index.php" style="color: #000; font-weight: bold;">Discover the collection</a>
        </div>
    <?php else: ?>
        <div class="grid">
            <?php foreach($favs as $fav): ?>
            <div class="card">
                <div class="card-img-wrap">
                    <img src="<?= htmlspecialchars($fav['image_url']) ?>" loading="lazy">
                </div>
                <div class="card-info">
                    <div class="card-title"><?= htmlspecialchars($fav['garment']) ?></div>
                </div>
                <a href="?del=<?= $fav['favoris_id'] ?>&p=<?= $current_page ?>" class="btn-del">REMOVE</a>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?p=<?= $current_page - 1 ?>">&laquo;</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?p=<?= $i ?>" class="<?= ($i == $current_page) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>

            <?php if ($current_page < $total_pages): ?>
                <a href="?p=<?= $current_page + 1 ?>">&raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
