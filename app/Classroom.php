<?php
declare(strict_types=1);
namespace ComputaKids;
use PDO;
use RuntimeException;
final class Classroom {
 private int $tenant;
 public function __construct(private PDO $db,array $actor){
  if($actor['papel']!=='professor'||$actor['trocar_senha']||!$actor['ativo']||$actor['tenant_status']!=='ativo')throw new RuntimeException('Acesso negado.');
  $this->tenant=(int)$actor['tenant_id'];
 }
 public function classes():array{
  $q=$this->db->prepare('SELECT * FROM turmas WHERE tenant_id=? ORDER BY ano_letivo DESC,nome');$q->execute([$this->tenant]);return $q->fetchAll();
 }
 public function students():array{
  $q=$this->db->prepare("SELECT id,nome,login,ativo FROM usuarios WHERE tenant_id=? AND papel='aluno' ORDER BY nome");$q->execute([$this->tenant]);return $q->fetchAll();
 }
 public function saveClass(int $id,string $name,int $grade,int $year):void{
  if(trim($name)===''||mb_strlen($name)>120||$grade<0||$grade>9||$year<2000||$year>2100)throw new RuntimeException('Dados inválidos.');
  if($id===0){$q=$this->db->prepare('INSERT INTO turmas(tenant_id,nome,etapa,ano_letivo) VALUES (?,?,?,?)');$q->execute([$this->tenant,$name,$grade,$year]);}
  else{$q=$this->db->prepare('UPDATE turmas SET nome=?,etapa=?,ano_letivo=? WHERE tenant_id=? AND id=?');$q->execute([$name,$grade,$year,$this->tenant,$id]);}
 }
 public function archive(int $id,bool $archive):void{
  $q=$this->db->prepare('UPDATE turmas SET arquivada=? WHERE tenant_id=? AND id=?');$q->execute([(int)$archive,$this->tenant,$id]);
 }
 public function createStudent(string $name,string $login,string $password,int $class):void{
  if(trim($name)===''||mb_strlen($name)>180||!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/D',$login)||strlen($password)<12||strlen($password)>72)throw new RuntimeException('Dados inválidos.');
  $this->db->beginTransaction();
  try{
   $this->checkClass($class);
   $q=$this->db->prepare("INSERT INTO usuarios(tenant_id,papel,nome,login,senha_hash) VALUES (?,'aluno',?,?,?)");$q->execute([$this->tenant,$name,$login,Security::hash($password)]);
   $this->insertEnrollment((int)$this->db->lastInsertId(),$class);
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
 }
 private function checkClass(int $id):void{
  $q=$this->db->prepare('SELECT id FROM turmas WHERE tenant_id=? AND id=? AND arquivada=0 FOR UPDATE');$q->execute([$this->tenant,$id]);if(!$q->fetchColumn())throw new RuntimeException('Turma indisponível.');
 }
 private function insertEnrollment(int $student,int $class):void{
  $q=$this->db->prepare('INSERT INTO matriculas(tenant_id,turma_id,aluno_id) VALUES (?,?,?) ON DUPLICATE KEY UPDATE ativa=1');$q->execute([$this->tenant,$class,$student]);
 }
 public function enroll(int $student,int $class):void{
  $this->db->beginTransaction();
  try{$this->checkClass($class);$q=$this->db->prepare("SELECT id FROM usuarios WHERE tenant_id=? AND id=? AND papel='aluno' FOR UPDATE");$q->execute([$this->tenant,$student]);if(!$q->fetchColumn())throw new RuntimeException('Aluno indisponível.');$this->insertEnrollment($student,$class);$this->db->commit();}catch(\Throwable $e){$this->db->rollBack();throw $e;}
 }
 public function enrollments():array{
  $q=$this->db->prepare("SELECT m.aluno_id,m.turma_id,u.nome AS aluno,t.nome AS turma FROM matriculas m JOIN usuarios u ON u.tenant_id=m.tenant_id AND u.id=m.aluno_id JOIN turmas t ON t.tenant_id=m.tenant_id AND t.id=m.turma_id WHERE m.tenant_id=? AND m.ativa=1 ORDER BY t.nome,u.nome");$q->execute([$this->tenant]);return $q->fetchAll();
 }
 public function unenroll(int $student,int $class):void{
  $q=$this->db->prepare('UPDATE matriculas SET ativa=0 WHERE tenant_id=? AND turma_id=? AND aluno_id=?');$q->execute([$this->tenant,$class,$student]);
 }
}
