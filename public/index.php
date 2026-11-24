<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
$root=dirname(__DIR__);
if (!is_file($root.'/config/local.php')) {
 header('Location: install.php',true,302); exit;
}
?><!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ComputaKids</title><h1>ComputaKids</h1><p>Instalação configurada. Módulo de autenticação em desenvolvimento.</p></html>
