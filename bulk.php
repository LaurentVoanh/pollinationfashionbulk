<?php
// Configuration & Utilitaires
$apiKeyPollinations = ' YOUR API KEY POLLINATION';
$mistralKeys = [' YOUR API KEY MISTRAL 1', ' YOUR API KEY MISTRAL 2', ' YOUR API KEY MISTRAL 3'];
$dirJson = __DIR__ . "/etape1/";
$dirImg = __DIR__ . "/image/";

if (!is_dir($dirJson)) mkdir($dirJson, 0777, true);
if (!is_dir($dirImg)) mkdir($dirImg, 0777, true);

// LOGIQUE DE TRAITEMENT AJAX
if (isset($_GET['action']) && $_GET['action'] == 'process') {
    header('Content-Type: application/json');
    
    // 1. Choisir une ligne au hasard
    $lines = file("todo.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) die(json_encode(['error' => 'todo.txt vide']));
    $userPrompt = trim($lines[array_rand($lines)]);
    
    
    
    // 2. Préparer le Full Prompt : Design based on large color-blocking with grafic representation of $userPrompt. Thick matte fabric, solid color. Structured cut, geometric. Studio lighting. High quality, luxury simplicity
    
    
    // $subPrompt = "Design based on large color-blocking with grafic representation of $userPrompt. The graphic is created through structured fabric paneling. Thick matte fabric, solid colors. Structured cut, geometric. Studio lighting. High quality, luxury simplicity.";
 // 1. Définir les options dans un tableau
$prompts = [
    // Tes prompts originaux
    "Design based on large color-blocking with grafic representation of $userPrompt. Thick matte fabric, solid color. Structured cut, geometric. Studio lighting. High quality, luxury simplicity",
    "Design based on large color-blocking with grafic representation of $userPrompt. The graphic is created through structured fabric paneling. Thick matte fabric, solid colors. Structured cut, geometric. Studio lighting. High quality, luxury simplicity."
];

// 2. Sélectionner une clé au hasard
$randomKey = array_rand($prompts);

// 3. Assigner la valeur choisie à votre variable
$subPrompt = $prompts[$randomKey];
    
    
    
    
    $fullPrompt = "top model, inside contemporary museum abstrac art, Minimalist and luxe fashion photography, hd 8K, cinematic, very original modern design, 2026 fashion collection. " . $subPrompt;
    
    // 3. Génération Image Pollinations
    $seed = rand(1000, 999999);
    $model = "turbo";
    $urlPollination = "https://gen.pollinations.ai/image/" . rawurlencode($fullPrompt) . "?model=$model&seed=$seed&key=$apiKeyPollinations&nologo=true&private=true&width=1024&height=1024";
    
    $imgData = file_get_contents($urlPollination);
    $imgName = uniqid() . "_$seed.jpg";
    file_put_contents($dirImg . $imgName, $imgData);
    
    // 4. Analyse Mistral
    $k = $mistralKeys[array_rand($mistralKeys)];
    $b64 = 'data:image/jpg;base64,' . base64_encode($imgData);
  $promptMistral = "Tu es expert en Haute Couture. Analyse cette image pour créer une fiche produit de luxe. 
Fais une description marketing longue, élogieuse, utilisant un vocabulaire riche et vendeur (soie, structure, avant-garde, excellence). 
Estime le temps de couture nécessaire en heures. 
Pour le prix, estime le coût de fabrication (matières et main d'oeuvre) et multiplie-le par 5 pour obtenir le prix de vente final.
Réponds uniquement en JSON strict (sans markdown) : 
{
  \"description\": \"Texte marketing détaillé et luxueux\",
  \"temps_couture\": \"X heures\",
  \"cout_fabrication_estime\": \"X €\",
  \"prix\": \"Prix final calculé (5x plus cher) €\",
  \"couleur\": \"nom exact de la nuance\",
  \"hashtags\": \"#Luxe #HauteCouture #Excellence\"
}";
    
    $ch = curl_init("https://api.mistral.ai/v1/chat/completions");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $k", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        "model" => "pixtral-12b-2409",
        "messages" => [[
            "role" => "user", 
            "content" => [
                ["type" => "text", "text" => $promptMistral],
                ["type" => "image_url", "image_url" => ["url" => $b64]]
            ]
        ]],
        "response_format" => ["type" => "json_object"],
        "temperature" => 0.7
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $raw = curl_exec($ch);
    curl_close($ch);
    
    $resMistral = json_decode($raw, true);
    $ficheProduit = json_decode($resMistral['choices'][0]['message']['content'] ?? '{}', true);

    // 5. Sauvegarde JSON (Format demandé)
    $id = uniqid() . "_" . str_replace(' ', '-', substr($userPrompt, 0, 15));
    $finalData = [
        "id" => $id,
        "date_created" => date("Y-m-d H:i:s"),
        "target" => "Sur mesure",
        "garment" => "Design",
        "user_prompt" => $userPrompt,
        "full_prompt" => $fullPrompt,
        "images" => [[
            "url" => "image/" . $imgName,
            "seed" => $seed,
            "date" => date("Y-m-d H:i:s"),
            "fiche_produit" => $ficheProduit,
            "boutique" => 1
        ]]
    ];
    
    file_put_contents($dirJson . $id . ".json", json_encode($finalData, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'status' => 'ok',
        'img' => "image/" . $imgName,
        'prompt' => $userPrompt,
        'analysis' => $ficheProduit
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulk Generation 2026</title>
    <style>
        body { font-family: sans-serif; background: #f4f4f4; padding: 20px; }
        .container { display: flex; gap: 20px; justify-content: center; flex-wrap: wrap; }
        .worker-box { background: #fff; border: 1px solid #ddd; padding: 15px; width: 300px; border-radius: 8px; text-align: center; min-height: 450px; }
        .img-vignette { width: 100%; height: 250px; object-fit: cover; border-radius: 4px; margin-top: 10px; }
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 30px; height: 30px; animation: spin 2s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .status-ok { color: green; font-weight: bold; margin-top: 10px; }
        pre { text-align: left; font-size: 10px; background: #eee; padding: 5px; overflow-x: auto; }
    </style>
</head>
<body>

<h2 style="text-align: center;">Système de Génération Triple Flux</h2>

<div class="container">
    <div id="slot-1" class="worker-box"><p>Initialisation slot 1...</p></div>
    <div id="slot-2" class="worker-box"><p>Initialisation slot 2...</p></div>
    <div id="slot-3" class="worker-box"><p>Initialisation slot 3...</p></div>
</div>

<script>
async function runProcess(slotId) {
    const container = document.getElementById(slotId);
    
    while(true) {
        // Reset affichage (Page blanche dans le bloc)
        container.innerHTML = `<h4>Processus actif</h4><div class="loader"></div><p>Choix du prompt...</p>`;
        
        try {
            const response = await fetch('bulk.php?action=process');
            const data = await response.json();
            
            if(data.status === 'ok') {
                container.innerHTML = `
                    <p><strong>Prompt:</strong> ${data.prompt}</p>
                    <img src="${data.img}" class="img-vignette">
                    <div class="loader"></div>
                    <p>Analyse Mistral en cours...</p>
                    <pre>${JSON.stringify(data.analysis, null, 2)}</pre>
                    <div class="status-ok">OK - Redémarrage...</div>
                `;
                
                // Petite pause visuelle avant de relancer
                await new Promise(r => setTimeout(r, 2000));
                // 2000 pour rapide, 60000 pour minute
            }
        } catch (e) {
            container.innerHTML = `<p style="color:red">Erreur, nouvelle tentative...</p>`;
            await new Promise(r => setTimeout(r, 5000));
            // 5000 pour rapide
        }
    }
}
// ACTIVATE 1 2 OR 3 TASK AT THE SAME TIME
// Lancement des 3 processus en simultané
runProcess('slot-1');
// runProcess('slot-2');
// runProcess('slot-3');
</script>

</body>
</html>
