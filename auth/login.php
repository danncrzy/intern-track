<?php
require __DIR__ . '/../config/config.php';
if (me()) redirect(home_url());

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = post('username');
    $password = $_POST['password'] ?? '';

    if ($username === '') {
        $error = 'Username wajib diisi.';
    } elseif ($password === '') {
        $error = 'Password wajib diisi.';
    } else {
        $u = one("SELECT * FROM users WHERE username = ?", [$username]);
        // password di data awal masih teks biasa, data baru memakai hash
        $ok = false;
        if ($u) {
            $ok = (strpos($u['password'], '$2y$') === 0) ? password_verify($password, $u['password']) : ($u['password'] === $password);
        }
        if (!$ok) {
            $error = 'Username atau password salah.';
        } else {
            $ref = null; $name = 'Administrator';
            if ($u['role'] === 'siswa') {
                $r = one("SELECT id_siswa, nama_siswa FROM siswa WHERE id_user = ?", [$u['id_user']]);
                if ($r) { $ref = $r['id_siswa']; $name = $r['nama_siswa']; }
            } elseif ($u['role'] === 'pembimbing') {
                $r = one("SELECT id_pembimbing, nama_pembimbing FROM pembimbing WHERE id_user = ?", [$u['id_user']]);
                if ($r) { $ref = $r['id_pembimbing']; $name = $r['nama_pembimbing']; }
            } elseif ($u['role'] === 'perusahaan') {
                $r = one("SELECT id_perusahaan, nama_perusahaan FROM perusahaan WHERE id_user = ?", [$u['id_user']]);
                if ($r) { $ref = $r['id_perusahaan']; $name = $r['nama_perusahaan']; }
            }
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => $u['id_user'], 'username' => $u['username'], 'role' => $u['role'], 'ref' => $ref, 'name' => $name];
            redirect(home_url());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - InternTrack</title>
<link rel="stylesheet" href="<?= BASE ?>/assets/style.css">
</head>
<body>
<div class="loginbox">
    <h1>InternTrack</h1>
    <p>Sistem Informasi Monitoring PKL</p>
    <?php if ($error): ?><div class="msg msg-err"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" value="<?= e($username) ?>" autofocus>
        <label for="password">Password</label>
        <input type="password" id="password" name="password">
        <button class="btn" style="width:100%">Login</button>
    </form>
</div>
</body>
</html>
