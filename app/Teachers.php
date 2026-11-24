<?php
declare(strict_types=1);
namespace ComputaKids;
use PDO;
use RuntimeException;
final class Teachers {
 public function __construct(private PDO $db,private array $actor) {
  if($actor['papel']!=='admin'||$actor['trocar_senha']) throw new RuntimeException('Acesso negado');
 }
 public function all(): array {
  return $this->db->query("SELECT u.id,u.nome,u.login,u.email,u.ativo,t.status,t.id AS tenant_id,(SELECT COUNT(*) FROM turmas c WHERE c.tenant_id=t.id) AS turmas,(SELECT COUNT(*) FROM usuarios a WHERE a.tenant_id=t.id AND a.papel='aluno') AS alunos FROM usuarios u JOIN tenants t ON t.id=u.tenant_id WHERE u.papel='professor' ORDER BY u.nome LIMIT 500")->fetchAll();
 }
 public function create(string $name,string $login,string $email,string $password): void {
  if(trim($name)===''||mb_strlen($name)>180||!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/D',$login)||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||strlen($password)<12||strlen($password)>72) throw new RuntimeException('Dados inválidos.');
  $this->db->beginTransaction();
  try {
   $q=$this->db->prepare('INSERT INTO tenants(nome) VALUES (?)');$q->execute([$name]);$tenant=$this->db->lastInsertId();
   $q=$this->db->prepare("INSERT INTO usuarios(tenant_id,papel,nome,login,email,senha_hash) VALUES (?,'professor',?,?,?,?)");$q->execute([$tenant,$name,$login,$email,Security::hash($password)]);
   $this->audit($tenant,'professor.criado');$this->db->commit();
  } catch(\Throwable $e){$this->db->rollBack();throw $e;}
 }
 public function status(int $id,bool $active): void {
  $this->db->beginTransaction();
  try {
   $q=$this->db->prepare("SELECT tenant_id FROM usuarios WHERE id=? AND papel='professor' FOR UPDATE");$q->execute([$id]);$tenant=$q->fetchColumn();if(!$tenant)throw new RuntimeException('Professor inexistente.');
   $q=$this->db->prepare('UPDATE tenants SET status=? WHERE id=?');$q->execute([$active?'ativo':'suspenso',$tenant]);
   $q=$this->db->prepare('UPDATE usuarios SET session_version=session_version+1 WHERE tenant_id=?');$q->execute([$tenant]);
   $this->audit((string)$tenant,$active?'professor.reativado':'professor.suspenso');$this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
 }
 private function audit(string $tenant,string $event):void {
  $q=$this->db->prepare('INSERT INTO auditoria(tenant_id,ator_id,evento) VALUES (?,?,?)');$q->execute([$tenant,$this->actor['id'],$event]);
 }
}
