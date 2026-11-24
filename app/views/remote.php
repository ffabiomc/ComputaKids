<section class="login"><h2>Acesso autorizado pelo professor</h2>
<?php if(isset($_SESSION['remote_challenge'])&&time()-($_SESSION['remote_created']??0)<300):?>
<div id="remote-qr" aria-label="QR Code de autorização"></div><p>Escaneie com o aplicativo do professor. Alternativamente, informe o código:</p><script src="assets/qrcode.min.js"></script><script>if(window.QRCode)new QRCode(document.getElementById('remote-qr'),{text:<?=json_encode($_SESSION['remote_challenge']['code'],JSON_HEX_TAG)?>,width:224,height:224});</script><p><strong><?=h($_SESSION['remote_challenge']['code'])?></strong></p>
<p id="remote-status" role="status">Aguardando autorização...</p>
<script>
(()=>{const csrf=<?=json_encode(\ComputaKids\Security::csrf(),JSON_HEX_TAG)?>;const status=document.getElementById('remote-status');async function poll(){try{const r=await fetch('index.php',{method:'POST',body:new URLSearchParams({action:'remote_poll',csrf}),cache:'no-store'});const j=await r.json();if(j.ready){location.replace('index.php');return;}if(j.expired){status.textContent='Código expirado. Recarregue a página.';return;}}catch(e){status.textContent='Reconectando...';}setTimeout(poll,3000);}setTimeout(poll,3000);})();
</script>
<?php else:?><form method="post"><?php token();?><button name="action" value="remote_start">Solicitar autorização</button></form><?php endif;?></section>
