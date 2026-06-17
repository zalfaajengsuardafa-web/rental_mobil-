<?php
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['error' => 'ID tidak valid']);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM mobil WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$mobil = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mobil) {
    echo json_encode(['error' => 'Mobil tidak ditemukan']);
    exit;
}

echo json_encode($mobil);
