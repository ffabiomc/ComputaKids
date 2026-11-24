<?php
declare(strict_types=1);
namespace ComputaKids;
use PDO;
final class Auth {
 public function __construct(private PDO $db) {}
 public function login(string $login,string $password): bool {
  $q=$this->db->prepare("SELECT u.*,t.status AS tenant_status FROM usuarios u LEFT JOIN tenants t ON t.id=u.tenant_id WHERE u.login=?");
  $q->execute([$login]); $u=$q->fetch();
  $valid=password_verify($password,$u['senha_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
  if (!$u || !$valid || !$u['ativo'] || ($u['papel']!=='admin' && $u['tenant_status']!=='ativo')) return false;
  if($u['papel']==='aluno'&&!$this->individualAllowed((int)$u['tenant_id'],(int)$u['id']))return false;
  session_regenerate_id(true); $_SESSION=['uid'=>(int)$u['id'],'version'=>(int)$u['session_version'],'csrf'=>bin2hex(random_bytes(32))]; return true;
 }
 public function current(): ?array {
  if (!isset($_SESSION['uid'],$_SESSION['version'])) return null;
  $q=$this->db->prepare("SELECT u.id,u.nome,u.login,u.papel,u.tenant_id,u.ativo,u.trocar_senha,u.session_version,t.status AS tenant_status FROM usuarios u LEFT JOIN tenants t ON t.id=u.tenant_id WHERE u.id=?");
  $q->execute([$_SESSION['uid']]); $u=$q->fetch();
  if (!$u || !$u['ativo'] || (int)$u['session_version']!==$_SESSION['version'] || ($u['papel']!=='admin' && $u['tenant_status']!=='ativo')) { $this->logout(); return null; }
  if($u['papel']==='aluno'&&!(isset($_SESSION['remote_class'])?$this->remoteAllowed((int)$u['tenant_id'],(int)$u['id'],(int)$_SESSION['remote_class']):$this->individualAllowed((int)$u['tenant_id'],(int)$u['id']))){$this->logout();return null;}
  if(isset($_SESSION['remote_class']))$u['trocar_senha']=0;
  return $u;
 }
 public function remoteLogin(string $id,string $secret):bool {
  if(isset($_SESSION['uid']))return false;
  $u=(new StudentAccess($this->db))->consume($id,$secret);
  if(!$u)return false;
  session_regenerate_id(true);
  $_SESSION=['uid'=>(int)$u['id'],'version'=>(int)$u['session_version'],'remote_class'=>(int)$u['turma_id'],'csrf'=>bin2hex(random_bytes(32))];
  return true;
 }
 private function remoteAllowed(int $tenant,int $student,int $class):bool {
  $q=$this->db->prepare('SELECT 1 FROM matriculas m JOIN turmas t ON t.tenant_id=m.tenant_id AND t.id=m.turma_id JOIN turma_acesso a ON a.tenant_id=t.tenant_id AND a.turma_id=t.id WHERE m.tenant_id=? AND m.aluno_id=? AND m.turma_id=? AND m.ativa=1 AND t.arquivada=0 AND a.remoto=1');
  $q->execute([$tenant,$student,$class]);return (bool)$q->fetchColumn();
 }
 private function individualAllowed(int $tenant,int $student):bool {
  $q=$this->db->prepare('SELECT 1 FROM matriculas m JOIN turmas t ON t.tenant_id=m.tenant_id AND t.id=m.turma_id LEFT JOIN turma_acesso a ON a.tenant_id=t.tenant_id AND a.turma_id=t.id WHERE m.tenant_id=? AND m.aluno_id=? AND m.ativa=1 AND t.arquivada=0 AND COALESCE(a.individual,1)=1 LIMIT 1');
  $q->execute([$tenant,$student]);return (bool)$q->fetchColumn();
 }
 public function logout(): void { $_SESSION=[]; session_regenerate_id(true); }
 public function changePassword(array $u,string $old,string $new): bool {
  if(isset($_SESSION['remote_class']))return false;
  if(strlen($new)<12 || strlen($new)>72) return false;
  $q=$this->db->prepare('SELECT senha_hash FROM usuarios WHERE id=? AND session_version=?'); $q->execute([$u['id'],$u['session_version']]); $hash=$q->fetchColumn();
  if (!$hash || !password_verify($old,$hash)) return false;
  $q=$this->db->prepare('UPDATE usuarios SET senha_hash=?,trocar_senha=0,session_version=session_version+1 WHERE id=? AND session_version=?');
  $q->execute([Security::hash($new),$u['id'],$u['session_version']]);
  if($q->rowCount()!==1)return false;
  session_regenerate_id(true); $_SESSION['version']=(int)$u['session_version']+1; return true;
 }
}
