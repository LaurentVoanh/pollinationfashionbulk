<?php
session_start();
function parseP($s){ return (int)preg_replace('/[^0-9]/','',$s); }

if(isset($_GET['del'])) {
    unset($_SESSION['cart'][$_GET['del']]);
    $_SESSION['cart']=array_values($_SESSION['cart']);
    header("Location: cart.php"); exit;
}

$sent=false;
if(isset($_POST['mail']) && !empty($_SESSION['cart'])) {
    $email=$_POST['mail'];
    $admin="yourmail@gmail.com";
    $msg="New order from $email:\n\n";
    $tot=0;
    foreach($_SESSION['cart'] as $c) {
        $msg.="- {$c['name']} (x{$c['qty']}) : {$c['price']}\n";
        $tot+=parseP($c['price'])*$c['qty'];
    }
    $msg.="\nTOTAL: ".number_format($tot,0,',',' ')." €";
    @mail($admin, "HCT Order", $msg, "From: no-reply@hct.com");
    @mail($email, "Order Confirmation", "Thank you for your order:\n$msg", "From: no-reply@hct.com");
    $_SESSION['cart']=[]; $sent=true;
}

$total=0;
if(isset($_SESSION['cart'])) foreach($_SESSION['cart'] as $c) $total+=parseP($c['price'])*$c['qty'];
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Shop</title><link rel="stylesheet" href="style.css?1"></head>
<body>
<header><a href="index.php" class="brand">&larr; Back Time line</a><span class="brand">Shop</span></header>
<div class="container" style="max-width:800px;">
    <?php if($sent): ?><div style="background:#d4edda; color:#155724; padding:20px; border-radius:4px; margin-bottom:20px;">Your order get success !</div><?php endif; ?>
    <?php if(empty($_SESSION['cart'])): ?>
        <p style="text-align:center; margin-top:50px;">Your basket is empty.</p>
    <?php else: ?>
        <div style="background:white; border:1px solid #eee; border-radius:8px; overflow:hidden;">
            <?php foreach($_SESSION['cart'] as $i=>$c): ?>
            <div class="cart-item" style="padding:15px; border-bottom:1px solid #eee;">
                <img src="<?= $c['img'] ?>" class="cart-img">
                <div class="cart-details">
                    <div class="cart-name"><?= htmlspecialchars($c['name']) ?></div>
                    <div style="color:#666; font-size:0.9rem;"><?= htmlspecialchars($c['price']) ?></div>
                    <div class="cart-actions">
                        <span style="font-size:0.9rem;">Quantity: <strong><?= $c['qty'] ?></strong></span>
                        <a href="?del=<?= $i ?>" style="color:#e74c3c; text-decoration:none; font-size:0.8rem; border:1px solid #eee; padding:5px 10px; border-radius:4px;">Delete</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align:right; font-size:1.5rem; margin:20px 0; font-weight:bold;">TOTAL: <?= number_format($total,0,',',' ') ?> €</div>
        <form method="POST" class="form-group">
            <h3 style="margin-top:0;">Complete your pre-order</h3>
            <input type="email" name="mail" placeholder="your@email.com" required>
            <button class="btn-submit" style="background:black;">Send pre-order</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
