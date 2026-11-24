<?php
declare(strict_types=1);
namespace ComputaKids;
use PDO;
final class RateLimit {
 public function __construct(private PDO $db){}
 public function allow(string $scope,string $identity,int $limit):bool {
  $bucket=hash('sha256',$scope.':'.$identity);
  $window=intdiv(time(),60);
  $this->db->beginTransaction();
  try {
   $q=$this->db->prepare('INSERT INTO rate_limits(bucket,window_id,attempts) VALUES(UNHEX(?),?,1) ON DUPLICATE KEY UPDATE attempts=IF(window_id=VALUES(window_id),LEAST(attempts+1,1000000),1),window_id=VALUES(window_id)');
   $q->execute([$bucket,$window]);
   $q=$this->db->prepare('SELECT attempts FROM rate_limits WHERE bucket=UNHEX(?)');$q->execute([$bucket]);$allowed=(int)$q->fetchColumn()<=$limit;
   $this->db->commit();return $allowed;
  }catch(\Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
 }
}
