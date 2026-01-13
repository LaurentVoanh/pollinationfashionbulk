<?php
$ref = $_GET['ref'] ?? '';
$found = null;
$parent = null;

// Recherche du produit dans les fichiers JSON
foreach(glob(__DIR__."/etape1/*.json") as $f) {
    $d = json_decode(file_get_contents($f), true);
    if (isset($d['images'])) {
        foreach($d['images'] as $img) {
            if(md5($img['url']) == $ref) { 
                $found = $img; 
                $parent = $d; 
                break 2; 
            }
        }
    }
}

if(!$found) die("Produit introuvable");
$info = $found['fiche_produit'] ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0">
    <title><?= htmlspecialchars($parent['garment'] ?? 'Détails') ?></title>
    <style>
        :root { 
            --bg: #ffffff;
            --text: #1a1a1a; 
            --light: #f9f9f9; 
            --accent: #000000; 
            --border: #e0e0e0;
            --font-main: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        body { 
            background: var(--bg); 
            color: var(--text); 
            font-family: var(--font-main); 
            margin: 0; 
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* Conteneur principal sans iframe */
        .details-container { 
            display: flex; 
            min-height: 100vh;
            width: 100%;
        }

        /* Colonne Image - Fixe sur PC */
        .details-img-col { 
            flex: 1.2; 
            background: #f4f4f4; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 40px;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .details-img-col img { 
            max-width: 100%; 
            max-height: 85vh; 
            object-fit: contain; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        /* Colonne Infos - Scrollable */
        .details-info-col { 
            flex: 1; 
            background: white;
            padding: 60px 50px; 
            overflow-y: auto;
        }

        .btn-back {
            display: inline-block;
            margin-bottom: 30px;
            text-decoration: none;
            color: #888;
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 1px;
        }

        h1 { margin: 0; font-size: 2.2rem; text-transform: capitalize; }
        .collection { font-size: 1rem; color: #666; margin-top: 5px; border-bottom: 1px solid #eee; padding-bottom: 25px; margin-bottom: 25px; }
        .price { font-size: 2rem; font-weight: 700; margin-bottom: 30px; }
        .description { line-height: 1.8; color: #333; font-size: 1.1rem; margin-bottom: 40px; }

        .specs { 
            background: var(--light); 
            padding: 30px; 
            border-radius: 4px; 
        }
        .specs p { margin: 10px 0; font-size: 0.95rem; }
        .hashtags { margin-top: 20px; color: #999; font-style: italic; font-size: 0.85rem; }

        /* Responsive Mobile */
        @media (max-width: 900px) {
            .details-container { flex-direction: column; }
            .details-img-col { 
                position: relative; 
                height: 60vh; 
                padding: 20px;
                flex: none;
            }
            .details-info-col { 
                padding: 40px 20px; 
                flex: none;
            }
            h1 { font-size: 1.8rem; }
        }
    </style>
</head>
<body>

    <div class="details-container">
        <div class="details-img-col">
            <img src="<?= htmlspecialchars($found['url']) ?>" alt="Produit">
        </div>

        <div class="details-info-col">
            <a href="index.php" class="btn-back">← Retour à la collection</a>
            
            <h1><?= htmlspecialchars($parent['garment'] ?? 'Vêtement') ?></h1>
            <div class="collection"><?= htmlspecialchars($parent['target'] ?? 'Hiver') ?> Collection</div>
            
            <div class="price"><?= htmlspecialchars($info['prix'] ?? 'Sur Demande') ?></div>
            
            <div class="description">
                <?= nl2br(htmlspecialchars($info['description'] ?? 'Analyse du design en cours...')) ?>
            </div>
            
            <div class="specs">
                <p><strong>Couleur :</strong> <?= htmlspecialchars($info['couleur'] ?? '-') ?></p>
                <p><strong>Inspiration :</strong> <?= htmlspecialchars($parent['user_prompt'] ?? 'Design original') ?></p>
                <div class="hashtags"><?= htmlspecialchars($info['hashtags'] ?? '') ?></div>
            </div>
        </div>
    </div>

</body>
</html>
