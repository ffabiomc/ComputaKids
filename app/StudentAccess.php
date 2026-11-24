<?php
declare(strict_types=1);
namespace ComputaKids;
use PDO;
use RuntimeException;
final class StudentAccess {
 public function __construct(private PDO $db){}
 public function challenge():array {
  $id=bin2hex(random_bytes(16));$code=strtoupper(bin2hex(random_bytes(8)));$secret=bin2hex(random_bytes(32));
  $q=$this->db->prepare('INSERT INTO acesso_desafios(id,codigo_hash,navegador_hash,expira_em) VALUES (UNHEX(?),UNHEX(?),UNHEX(?),DATE_ADD(UTC_TIMESTAMP(),INTERVAL 5 MINUTE))');
  $q->execute([$id,hash('sha256',$code),hash('sha256',$secret)]);
  return ['id'=>$id,'code'=>$code,'secret'=>$secret];
 }
 public function authorize(array $actor,int $class,int $student,string $code):bool {
  if($actor['papel']!=='professor'||$actor['trocar_senha']||!$actor['ativo']||$actor['tenant_status']!=='ativo')return false;
  $code=strtoupper(trim($code));if(!preg_match('/^[A-F0-9]{16}$/D',$code))return false;
  $q=$this->db->prepare("UPDATE acesso_desafios d JOIN matriculas m ON m.tenant_id=? AND m.turma_id=? AND m.aluno_id=? AND m.ativa=1 JOIN turmas t ON t.tenant_id=m.tenant_id AND t.id=m.turma_id AND t.arquivada=0 JOIN turma_acesso a ON a.tenant_id=m.tenant_id AND a.turma_id=m.turma_id AND a.remoto=1 JOIN usuarios u ON u.tenant_id=m.tenant_id AND u.id=m.aluno_id AND u.papel='aluno' AND u.ativo=1 JOIN tenants te ON te.id=m.tenant_id AND te.status='ativo' SET d.tenant_id=m.tenant_id,d.turma_id=m.turma_id,d.aluno_id=m.aluno_id,d.autorizado_em=UTC_TIMESTAMP() WHERE d.codigo_hash=UNHEX(?) AND d.expira_em>UTC_TIMESTAMP() AND d.autorizado_em IS NULL AND d.consumido_em IS NULL");
  $q->execute([(int)$actor['tenant_id'],$class,$student,hash('sha256',$code)]);return $q->rowCount()===1;
 }
 public function consume(string $id,string $secret):?array {
  if(!preg_match('/^[a-f0-9]{32}$/D',$id)||!preg_match('/^[a-f0-9]{64}$/D',$secret))return null;
  $this->db->beginTransaction();
  try {
   $q=$this->db->prepare("SELECT u.id,u.session_version,d.turma_id,d.tenant_id FROM acesso_desafios d JOIN matriculas m ON m.tenant_id=d.tenant_id AND m.turma_id=d.turma_id AND m.aluno_id=d.aluno_id AND m.ativa=1 JOIN turmas t ON t.tenant_id=m.tenant_id AND t.id=m.turma_id AND t.arquivada=0 JOIN turma_acesso a ON a.tenant_id=m.tenant_id AND a.turma_id=m.turma_id AND a.remoto=1 JOIN usuarios u ON u.tenant_id=m.tenant_id AND u.id=m.aluno_id AND u.papel='aluno' AND u.ativo=1 JOIN tenants te ON te.id=m.tenant_id AND te.status='ativo' WHERE d.id=UNHEX(?) AND d.navegador_hash=UNHEX(?) AND d.expira_em>UTC_TIMESTAMP() AND d.autorizado_em IS NOT NULL AND d.consumido_em IS NULL FOR UPDATE");
   $q->execute([$id,hash('sha256',$secret)]);$u=$q->fetch();
   if(!$u){$this->db->rollBack();return null;}
   $q=$this->db->prepare('UPDATE acesso_desafios SET consumido_em=UTC_TIMESTAMP() WHERE id=UNHEX(?) AND consumido_em IS NULL');$q->execute([$id]);
   if($q->rowCount()!==1)throw new RuntimeException('Desafio indisponível.');
   $this->db->commit();return $u;
  }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
 }
}
