<?php
// includes/voltar_dashboard.php — link padronizado "Voltar ao Dashboard"
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$perfil = $_SESSION['perfil'] ?? 'user';

// Destino conforme perfil
$destino = 'dashboard_user.php';
if ($perfil === 'admin') {
  $destino = 'dashboard.php';
} elseif ($perfil === 'conferente') {
  $destino = 'dashboard_conferente.php';
}
?>
<a href="<?= htmlspecialchars($destino) ?>" class="back-link">‹ Voltar ao Dashboard</a>
