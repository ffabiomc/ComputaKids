<?php
declare(strict_types=1);
use ComputaKids\Database;
use ComputaKids\Security;
require dirname(__DIR__).'/app/Database.php';
require dirname(__DIR__).'/app/Security.php';
header('Cache-Control: no-store');
header('Content-Type: text/html; charset=UTF-8');
$root=dirname(__DIR__);
$target=$root.'/config/local.php';
if (is_file($target)) { http_response_code(403); exit('Instalador bloqueado.'); }
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') { http_response_code(403); exit('HTTPS obrigatório.'); }
$keyFile=$root.'/config/install.key';
if (!is_readable($keyFile) || strlen(trim((string)file_get_contents($keyFile)))<32) { http_response_code(503); exit('Configure a chave privada de instalação conforme a documentação.'); }
Security::start();
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
 try {
  if (!Security::verify($_POST['csrf']??null) || !hash_equals(trim((string)file_get_contents($keyFile)),(string)($_POST['key']??''))) throw new RuntimeException('Acesso inválido.');
  $config=['host'=>trim((string)($_POST['host']??'')),'port'=>3306,'database'=>trim((string)($_POST['database']??'')),'user'=>trim((string)($_POST['user']??'')),'password'=>(string)($_POST['password']??'')];
  foreach (['host','database','user'] as $field) if (!preg_match('/^[a-zA-Z0-9_.-]+$/D',$config[$field])) throw new RuntimeException('Configuração inválida.');
  $login=trim((string)($_POST['login']??''));
  $name=trim((string)($_POST['name']??''));
  $pass=(string)($_POST['admin_password']??'');
  if (!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/D',$login)||$name===''||mb_strlen($name)>180||strlen($pass)<12||strlen($pass)>72) throw new RuntimeException('Dados do administrador inválidos.');
  $lock=fopen($root.'/config/install.lock','c');
  if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) throw new RuntimeException('Instalação em andamento.');
  try {
   if (is_file($target)) throw new RuntimeException('Já instalado.');
   $pdo=Database::connect($config);
   if ($pdo->query('SHOW TABLES')->fetchColumn()!==false) throw new RuntimeException('Use um banco vazio e exclusivo.');
   foreach (['001_identity.sql','002_student_access.sql','003_rate_limits.sql'] as $migration) {
   $sql=file_get_contents($root.'/database/migrations/'.$migration);
   if ($sql===false) throw new RuntimeException('Migração indisponível.');
   foreach (explode(';',preg_replace('/^--.*$/m','',$sql)) as $statement) if (trim($statement)!=='') $pdo->exec($statement);
   }
   $stmt=$pdo->prepare("INSERT INTO usuarios (papel,nome,login,senha_hash,trocar_senha) VALUES ('admin',?,?,?,0)");
   $stmt->execute([$name,$login,Security::hash($pass)]);
   $tmp=tempnam($root.'/config','cfg');
   if ($tmp===false) throw new RuntimeException('Configuração indisponível.');
   chmod($tmp,0600);
   if (file_put_contents($tmp,'<?php return '.var_export($config,true).';')===false || !rename($tmp,$target)) { @unlink($tmp); throw new RuntimeException('Falha ao gravar configuração.'); }
   @unlink($keyFile);
   header('Location: index.php',true,303); exit;
  } finally { flock($lock,LOCK_UN); fclose($lock); }
 } catch (Throwable $e) { $error='Não foi possível instalar. Verifique os dados, a chave, as permissões e se o banco está vazio. Em caso de migração parcial, use um novo banco vazio.'; }
}
?><!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalar ComputaKids</title><style>body{font:16px system-ui;background:#eef2ff;margin:0}main{max-width:520px;margin:32px auto;background:white;padding:24px;border-radius:16px}label{display:block;margin:12px 0}input{display:block;width:100%;box-sizing:border-box;padding:10px;border:1px solid #aaa;border-radius:6px}button{background:#4338ca;color:white;border:0;padding:12px;border-radius:8px;width:100%}</style><main><h1>ComputaKids</h1><p>Instalação inicial</p><?php if($error): ?><p role="alert"><?=Security::escape($error)?></p><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?=Security::escape(Security::csrf())?>"><?php foreach(['key'=>'Chave privada de instalação','host'=>'Servidor MySQL','database'=>'Banco de dados','user'=>'Usuário MySQL','password'=>'Senha MySQL','name'=>'Nome do administrador','login'=>'Login do administrador','admin_password'=>'Senha do administrador (12 a 72 bytes)'] as $field=>$label): ?><label><?=Security::escape($label)?><input name="<?=$field?>" type="<?=in_array($field,['key','password','admin_password'],true)?'password':'text'?>" <?=$field==='host'?'value="localhost"':''?> required></label><?php endforeach; ?><button>Instalar plataforma</button></form></main></html>
