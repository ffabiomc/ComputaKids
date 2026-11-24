<?php
declare(strict_types=1);
use ComputaKids\{Database,Security,Auth,Teachers,Classroom,AccessPolicy};
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
$root=dirname(__DIR__);
if(!is_file($root.'/config/local.php')){header('Location: install.php');exit;}
if(empty($_SERVER['HTTPS'])||$_SERVER['HTTPS']==='off'){http_response_code(403);exit('HTTPS obrigatório.');}
foreach(['Database','Security','Auth','Teachers','Classroom','AccessPolicy'] as $class)require $root.'/app/'.$class.'.php';
Security::start();
try{$db=Database::connect(require $root.'/config/local.php');}catch(Throwable $e){http_response_code(503);exit('Serviço temporariamente indisponível.');}
$auth=new Auth($db);$u=$auth->current();$error='';
function h(mixed $v):string{return Security::escape((string)$v);}
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  if(!Security::verify($_POST['csrf']??null))throw new RuntimeException('Solicitação inválida.');
  $action=$_POST['action']??'';
  if($action==='login'&&!$u){
   if(($_SESSION['retry_at']??0)>time())throw new RuntimeException('Aguarde antes de tentar novamente.');
   $_SESSION['retry_at']=time()+3;
   if(!$auth->login(trim((string)($_POST['login']??'')),(string)($_POST['password']??'')))throw new RuntimeException('Credenciais inválidas.');
  }elseif($action==='logout'&&$u){$auth->logout();}
  elseif($action==='password'&&$u){if(!$auth->changePassword($u,(string)($_POST['old']??''),(string)($_POST['new']??'')))throw new RuntimeException('Não foi possível alterar a senha.');}
  elseif($u&&$u['papel']==='admin'&&!$u['trocar_senha']){
   $teachers=new Teachers($db,$u);
   if($action==='create')$teachers->create(trim((string)($_POST['name']??'')),trim((string)($_POST['login']??'')),trim((string)($_POST['email']??'')),(string)($_POST['password']??''));
   elseif(in_array($action,['suspend','activate'],true))$teachers->status((int)($_POST['id']??0),$action==='activate');
   else throw new RuntimeException('Operação inválida.');
  }elseif($u&&$u['papel']==='professor'&&!$u['trocar_senha']){
   $classroom=new Classroom($db,$u);
   switch($action){
    case 'access_save': (new AccessPolicy($db,$u))->save((int)($_POST['id']??0),isset($_POST['individual']),isset($_POST['shared']),isset($_POST['remote']),(string)($_POST['shared_password']??''));break;
    case 'class_save': $classroom->saveClass((int)($_POST['id']??0),trim((string)($_POST['name']??'')),(int)($_POST['grade']??-1),(int)($_POST['year']??0));break;
    case 'class_archive':case 'class_restore':$classroom->archive((int)($_POST['id']??0),$action==='class_archive');break;
    case 'student_create':$classroom->createStudent(trim((string)($_POST['name']??'')),trim((string)($_POST['login']??'')),(string)($_POST['password']??''),(int)($_POST['class']??0));break;
    case 'enroll':case 'unenroll':$student=(int)($_POST['student']??0);$class=(int)($_POST['class']??0);if($action==='enroll')$classroom->enroll($student,$class);else $classroom->unenroll($student,$class);break;
    default:throw new RuntimeException('Operação inválida.');
   }
  }else throw new RuntimeException('Acesso negado.');
  header('Location: index.php',true,303);exit;
 }catch(Throwable $e){$error='Não foi possível concluir a operação. Verifique os dados e tente novamente.';}
}
function token():void{echo '<input type="hidden" name="csrf" value="'.h(Security::csrf()).'">';}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ComputaKids</title><style>body{font:16px system-ui;background:#eef2ff;color:#172554;margin:0}header{background:#312e81;color:white;padding:20px}main{max-width:1000px;margin:24px auto;padding:16px}section{background:white;border-radius:16px;padding:24px;margin-bottom:16px}label{display:block;margin:10px 0}input{padding:10px;border:1px solid #94a3b8;border-radius:8px;max-width:100%;box-sizing:border-box}label input{display:block;width:100%}button{background:#4338ca;color:white;border:0;border-radius:8px;padding:10px 16px;cursor:pointer}table{width:100%;border-collapse:collapse}td,th{text-align:left;padding:10px;border-bottom:1px solid #ddd}.scroll{overflow:auto}.login{max-width:420px;margin:auto}</style></head><body><header><strong>ComputaKids</strong> · Aprenda. Programe. Crie.</header><main><?php if($error):?><p role="alert"><?=h($error)?></p><?php endif;?>
<?php if(!$u):?><section class="login"><h1>Entrar</h1><form method="post"><?php token();?><input type="hidden" name="action" value="login"><label>Usuário<input name="login" autocomplete="username" required></label><label>Senha<input type="password" name="password" autocomplete="current-password" required></label><button>Entrar</button></form></section>
<?php else:?><section><h1>Olá, <?=h($u['nome'])?></h1><form method="post"><?php token();?><button name="action" value="logout">Sair</button></form></section>
<?php if($u['trocar_senha']):?><section><h2>Altere sua senha inicial</h2><form method="post"><?php token();?><label>Senha atual<input type="password" name="old" required></label><label>Nova senha<input type="password" name="new" minlength="12" required></label><button name="action" value="password">Salvar</button></form></section>
<?php elseif($u['papel']==='admin'): $rows=(new Teachers($db,$u))->all();?><section><h2>Administração geral</h2><p>Gerenciamento de professores</p><form method="post"><?php token();?><label>Nome<input name="name" required maxlength="180"></label><label>Usuário<input name="login" required maxlength="100"></label><label>E-mail<input name="email" type="email" required></label><label>Senha inicial<input name="password" type="password" minlength="12" required></label><button name="action" value="create">Cadastrar professor</button></form></section><section><h2>Professores cadastrados</h2><div class="scroll"><table><thead><tr><th>Nome</th><th>Usuário</th><th>Turmas</th><th>Alunos</th><th>Situação</th><th>Ação</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=h($r['nome'])?></td><td><?=h($r['login'])?></td><td><?=h($r['turmas'])?></td><td><?=h($r['alunos'])?></td><td><?=h($r['status'])?></td><td><form method="post"><?php token();?><input type="hidden" name="id" value="<?=h($r['id'])?>"><button name="action" value="<?=$r['status']==='ativo'?'suspend':'activate'?>"><?=$r['status']==='ativo'?'Suspender':'Reativar'?></button></form></td></tr><?php endforeach;?></tbody></table></div></section>
<?php elseif($u['papel']==='professor'):require $root.'/app/views/teacher.php';?>
<?php else:?><section><h2>Painel <?=h($u['papel'])?></h2><p>Novos módulos em desenvolvimento.</p></section><?php endif;endif;?></main></body></html>
