<?php
session_start();
$keys=[' YOUR API KEY MISTRAL 1',' YOUR API KEY MISTRAL 2',' YOUR API KEY MISTRAL 3'];

function getBase64($path) {
    $real = __DIR__ . "/" . urldecode($path);
    if(!file_exists($real)) return null;
    return 'data:image/jpg;base64,' . base64_encode(file_get_contents($real));
}

if(isset($_POST['action']) && $_POST['action']=='analyze') {
    $dir=__DIR__."/etape1/";
    foreach(glob($dir."*.json") as $f) {
        $d=json_decode(file_get_contents($f),true);
        if(($d['full_boutique']??0)==1) continue;
        
        $idx=-1;
        foreach($d['images'] as $i=>$img) { if(($img['boutique']??0)!=1){ $idx=$i; break; } }
        
        if($idx>-1) {
            $b64=getBase64($d['images'][$idx]['url']);
            if(!$b64) {
                $d['images'][$idx]['boutique']=1; 
                file_put_contents($f, json_encode($d));
                echo json_encode(['status'=>'skip']); exit;
            }
            
            $k=$keys[array_rand($keys)];
            $prompt="Tu es expert Luxe. JSON strict (sans markdown): {\"description\": \"Texte marketing court\", \"prix\": \"1 200 €\", \"couleur\": \"nom\", \"hashtags\": \"#Luxe\"}";
            
            $ch=curl_init("https://api.mistral.ai/v1/chat/completions  ");
            curl_setopt($ch,CURLOPT_HTTPHEADER,["Authorization: Bearer $k", "Content-Type: application/json"]);
            curl_setopt($ch,CURLOPT_POST,1);
            curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode([
                "model"=>"pixtral-12b-2409",
                "messages"=>[[ "role"=>"user", "content"=>[["type"=>"text","text"=>$prompt],["type"=>"image_url","image_url"=>$b64]] ]],
                "temperature"=>0.7
            ]));
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
            $raw=curl_exec($ch); curl_close($ch);
            
            $res=json_decode($raw,true)['choices'][0]['message']['content']??"{}";
            $clean=json_decode(str_replace(['```json','```'], '', $res), true);
            
            $d['images'][$idx]['fiche_produit'] = $clean ?: ["description"=>"Erreur analyse"];
            $d['images'][$idx]['boutique'] = 1;
            
            $done=true; foreach($d['images'] as $i) if(($i['boutique']??0)!=1) $done=false;
            if($done) $d['full_boutique']=1;
            
            file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT));
            echo json_encode(['status'=>'success', 'item'=>$d['images'][$idx]['url']]); exit;
        } else {
            $d['full_boutique']=1; file_put_contents($f, json_encode($d));
        }
    }
    echo json_encode(['status'=>'finished']); exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>IA Analyste</title><link rel="stylesheet" href="style.css"></head>
<body>
<header><span class="brand">IA Analyste (Mistral)</span><nav class="nav-links"><a href="atelier.php">Retour Atelier</a></nav></header>
<div class="container" style="max-width:800px; text-align:center; padding-top:40px;">
    <div style="background:white; padding:40px; border-radius:8px; border:1px solid #eee;">
        <h2>Génération des Fiches Produits</h2>
        <p style="color:#666; margin-bottom:30px;">L'IA analyse chaque image générée pour créer une description, un prix et des hashtags.</p>
        <button id="btn-run" onclick="run()" style="padding:15px 30px; background:black; color:white; border:none; cursor:pointer; font-size:1rem; border-radius:4px; width:100%;">LANCER L'ANALYSE</button>
        <div id="log" style="margin-top:30px; text-align:left; background:#f9f9f9; padding:20px; border:1px solid #ddd; height:300px; overflow-y:auto; border-radius:4px; font-family:monospace; font-size:0.85rem;">
            En attente de démarrage...
        </div>
    </div>
    <script>
    async function run() {
        let btn = document.getElementById('btn-run');
        let log = document.getElementById('log');
        btn.disabled = true;
        btn.innerHTML = 'ANALYSE EN COURS <div class="spinner" style="border-color:#fff; border-top-color:transparent;"></div>';
        while(true) {
            let fd=new FormData(); fd.append('action','analyze');
            try {
                let res=await(await fetch('boutique.php', {method:'POST',body:fd})).json();
                if(res.status=='success') log.innerHTML = '<div style="color:green; margin-bottom:5px;">[OK] Traité : '+res.item+'</div>' + log.innerHTML;
                else if(res.status=='finished') { 
                    log.innerHTML = '<div style="font-weight:bold; margin-bottom:5px;">-- TERMINÉ --</div>' + log.innerHTML; 
                    btn.innerHTML = 'ANALYSE TERMINÉE';
                    break; 
                }
                else log.innerHTML = '<div style="color:orange; margin-bottom:5px;">[SKIP]...</div>' + log.innerHTML;
            } catch(e){ log.innerHTML = '<div style="color:red;">Erreur...</div>' + log.innerHTML; }
            await new Promise(r=>setTimeout(r, 1500));
        }
    }
    </script>
</div>
</body>
</html>
