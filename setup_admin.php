<?php
/**
 * ADMIN SETUP SCRIPT
 * Run once at: http://localhost:8000/setup_admin.php
 * DELETE THIS FILE after running!
 */
require_once 'includes/functions.php';

$admin_username = 'admin';
$admin_password = 'Admin@123';
$admin_email    = 'admin@sololeveling.com';
$admin_name     = 'System Administrator';
$hash = password_hash($admin_password, PASSWORD_DEFAULT);

$db = new Database();
$conn = $db->getConnection();

// Check if admin exists
$check = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
$check->execute();

if ($check->rowCount() > 0) {
    // Update existing
    $stmt = $conn->prepare("UPDATE users SET password_hash = :hash, is_admin = 1, current_rank = 'S', shadow_coins = 99999, total_xp = 999999 WHERE username = 'admin'");
    $stmt->execute([':hash' => $hash]);
    $action = 'updated';
} else {
    // Create new
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, is_admin, current_rank, shadow_coins, total_xp, is_active) VALUES (:u, :e, :h, :n, 1, 'S', 99999, 999999, 1)");
    $stmt->execute([':u'=>$admin_username, ':e'=>$admin_email, ':h'=>$hash, ':n'=>$admin_name]);
    $action = 'created';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Setup</title>
<style>
  body { font-family: monospace; background: #05050f; color: #00d4ff; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
  .box { background: rgba(10,12,35,0.95); border: 1px solid rgba(0,212,255,0.4); border-radius: 8px; padding: 40px; max-width: 440px; width: 90%; box-shadow: 0 0 40px rgba(0,212,255,0.15); }
  h2 { color: #00ffaa; letter-spacing: 3px; font-size: 1rem; margin: 0 0 20px; }
  .row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(0,212,255,0.1); font-size: 0.9rem; }
  .label { color: #7a8db0; }
  .value { color: #fff; font-weight: bold; }
  .warn { background: rgba(255,51,102,0.1); border: 1px solid rgba(255,51,102,0.4); border-radius: 4px; padding: 12px; margin-top: 20px; color: #ff3366; font-size: 0.82rem; }
  a { display: block; margin-top: 20px; background: rgba(0,212,255,0.1); border: 1px solid #00d4ff; color: #00d4ff; text-decoration: none; text-align: center; padding: 12px; border-radius: 4px; letter-spacing: 2px; font-size: 0.85rem; }
  a:hover { background: rgba(0,212,255,0.2); }
</style>
</head>
<body>
<div class="box">
  <h2>✅ ADMIN <?= strtoupper($action) ?></h2>
  <div class="row"><span class="label">Username</span><span class="value">admin</span></div>
  <div class="row"><span class="label">Password</span><span class="value">Admin@123</span></div>
  <div class="row"><span class="label">Rank</span><span class="value">S-Rank</span></div>
  <div class="row"><span class="label">Shadow Coins</span><span class="value">99,999</span></div>
  <div class="row"><span class="label">Total XP</span><span class="value">999,999</span></div>
  <div class="warn">⚠️ DELETE this file after logging in! It is a security risk.</div>
  <a href="/solo-leveling-fitness/pages/login.php">→ GO TO LOGIN</a>
</div>
</body>
</html>
