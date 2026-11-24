<section class="login"><h2>Acesso autorizado pelo professor</h2>
<?php if(isset($_SESSION['remote_challenge'])&&time()-($_SESSION['remote_created']??0)<300):?>
<p>Informe este código ao professor:</p><p><strong><?=h($_SESSION['remote_challenge']['code'])?></strong></p>
<p id="remote-status" role="status">Aguardando autorização...</p>
<script>
(()=>{const csrf=<?=json_encode(\ComputaKids\Security::csrf(),JSON_HEX_TAG)?>;const status=document.getElementById('remote-status');async function poll(){try{const r=await fetch('index.php',{method:'POST',body:new URLSearchParams({action:'remote_poll',csrf}),cache:'no-store'});const j=await r.json();if(j.ready){location.replace('index.php');return;}if(j.expired){status.textContent='Código expirado. Recarregue a página.';return;}}catch(e){status.textContent='Reconectando...';}setTimeout(poll,3000);}setTimeout(poll,3000);})();
</script>
<?php else:?><form method="post"><?php token();?><button name="action" value="remote_start">Solicitar autorização</button></form><?php endif;?></section>
