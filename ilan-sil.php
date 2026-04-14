<?php
require_once '../config.php';
if (!admin()) { header('Location: /giris.php'); exit; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $db->prepare("SELECT resim FROM ilanlar WHERE id = ?");
$stmt->execute([$id]);
$resim = $stmt->fetchColumn();
if ($resim && file_exists(__DIR__ . '/' . $resim)) unlink(__DIR__ . '/' . $resim);

$db->prepare("DELETE FROM ilanlar WHERE id = ?")->execute([$id]);
bildirim('İlan silindi!', 'success');
header('Location: /index.php');
exit;
