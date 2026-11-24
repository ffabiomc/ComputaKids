<?php
declare(strict_types=1);
namespace ComputaKids;
use PDO;
use RuntimeException;
final class AccessPolicy {
 private int $tenant;
 public function __construct(private PDO $db,array $actor){
  if($actor['papel']!=='professor'||$actor['trocar_senha']||!$actor['ativo']||$actor['tenant_status']!=='ativo')throw new RuntimeException('Acesso negado.');
  $this->tenant=(int)$actor['tenant_id'];
 }
 public function save(int $class,bool $individual,bool $shared,bool $remote,string $password):void{
  if(!$individual&&!$shared&&!$remote)throw new RuntimeException('Selecione um método.');
  if($password!==''&&(strlen($password)<12||strlen($password)>72))throw new RuntimeException('Senha inválida.');
  $this->db->beginTransaction();
  try{
   $q=$this->db->prepare('SELECT id FROM turmas WHERE tenant_id=? AND id=? AND arquivada=0 FOR UPDATE');$q->execute([$this->tenant,$class]);if(!$q->fetchColumn())throw new RuntimeException('Turma indisponível.');
   $q=$this->db->prepare('SELECT senha_hash FROM turma_acesso WHERE tenant_id=? AND turma_id=?');$q->execute([$this->tenant,$class]);$hash=$q->fetchColumn()?:null;
   if(!$shared)$hash=null;elseif($password!=='')$hash=Security::hash($password);
   if($shared&&!$hash)throw new RuntimeException('Defina a senha.');
   $q=$this->db->prepare('INSERT INTO turma_acesso(tenant_id,turma_id,individual,compartilhado,remoto,senha_hash) VALUES (?,?,?,?,?,?) ON DUPLICATE KEY UPDATE individual=VALUES(individual),compartilhado=VALUES(compartilhado),remoto=VALUES(remoto),senha_hash=VALUES(senha_hash),versao=versao+1');
   $q->execute([$this->tenant,$class,(int)$individual,(int)$shared,(int)$remote,$hash]);
   $q=$this->db->prepare("UPDATE usuarios u JOIN matriculas m ON m.tenant_id=u.tenant_id AND m.aluno_id=u.id SET u.session_version=u.session_version+1 WHERE m.tenant_id=? AND m.turma_id=? AND u.papel='aluno'");$q->execute([$this->tenant,$class]);
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
 }
 public function all():array{
  $q=$this->db->prepare('SELECT t.id,t.nome,t.ano_letivo,COALESCE(a.individual,1) AS individual,COALESCE(a.compartilhado,0) AS compartilhado,COALESCE(a.remoto,0) AS remoto FROM turmas t LEFT JOIN turma_acesso a ON a.tenant_id=t.tenant_id AND a.turma_id=t.id WHERE t.tenant_id=? AND t.arquivada=0 ORDER BY t.nome');$q->execute([$this->tenant]);return $q->fetchAll();
 }
}
