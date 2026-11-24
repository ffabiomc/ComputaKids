<?php
declare(strict_types=1);
namespace ComputaKids;
final class Security {
 public static function start(): void {
  ini_set('session.use_strict_mode','1');
  session_set_cookie_params(['httponly'=>true,'secure'=>true,'samesite'=>'Lax','path'=>'/']);
  session_start();
 }
 public static function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
 public static function verify(mixed $token): bool { return is_string($token) && hash_equals(self::csrf(),$token); }
 public static function escape(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
 public static function hash(string $password): string { return password_hash($password,defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT); }
}
