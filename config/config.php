<?php
// ---------- Konfigurasi database (sesuaikan dengan XAMPP/Laragon kamu) ----------
$DB_HOST = 'localhost';
$DB_NAME = 'interntrack';
$DB_USER = 'root';
$DB_PASS = '';

session_start();
date_default_timezone_set('Asia/Jakarta');

// Alamat dasar aplikasi (otomatis, mis. /InternTrack), dipakai untuk link antar folder
$_sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
define('BASE', rtrim(preg_replace('#/(admin|siswa|auth|laporan)/[^/]*$|/[^/]*$#', '', $_sn), '/'));
function home_url() { $u = $_SESSION['user'] ?? null; return BASE . ($u && $u['role'] === 'siswa' ? '/siswa/dashboard.php' : '/admin/dashboard.php'); }

try {
    $db = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    die('Koneksi database gagal. Pastikan database "' . htmlspecialchars($DB_NAME) . '" sudah dibuat dan SQL sudah di-import. <br>' . htmlspecialchars($ex->getMessage()));
}

// ---------- Helper query ----------
function q($sql, $params = []) { global $db; $st = $db->prepare($sql); $st->execute($params); return $st; }
function all($sql, $params = []) { return q($sql, $params)->fetchAll(); }
function one($sql, $params = []) { $r = q($sql, $params)->fetch(); return $r ?: null; }
function val($sql, $params = []) { return q($sql, $params)->fetchColumn(); }

// ---------- Helper umum ----------
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function redirect($url) { header('Location: ' . $url); exit; }
function flash($msg, $type = 'ok') { $_SESSION['flash'] = [$msg, $type]; }
function tgl($d) { return $d ? date('d-m-Y', strtotime($d)) : '-'; }
function jurusan($kelas) { $p = explode(' ', trim((string)$kelas)); return $p[1] ?? '-'; }
function post($k) { return trim($_POST[$k] ?? ''); }
function get($k) { return trim($_GET[$k] ?? ''); }

function options($rows, $vk, $lk, $sel = '') {
    $o = '';
    foreach ($rows as $r) {
        $o .= '<option value="' . e($r[$vk]) . '"' . ((string)$r[$vk] === (string)$sel ? ' selected' : '') . '>' . e($r[$lk]) . '</option>';
    }
    return $o;
}

function badge($t) {
    $ok = ['Disetujui', 'Hadir', 'Aktif', 'Sangat Baik', 'Baik'];
    $warn = ['Pending', 'Izin', 'Sakit', 'Terlambat', 'Cukup', 'Belum Lengkap'];
    $err = ['Revisi', 'Alpa', 'Kurang'];
    $c = in_array($t, $ok) ? 'b-ok' : (in_array($t, $warn) ? 'b-warn' : (in_array($t, $err) ? 'b-err' : 'b-gray'));
    return '<span class="badge ' . $c . '">' . e($t) . '</span>';
}

function pct($hadir, $total) { return $total > 0 ? round($hadir * 100 / $total, 1) : null; }
function kategori($p) {
    if ($p === null) return '-';
    if ($p >= 90) return 'Sangat Baik';
    if ($p >= 80) return 'Baik';
    if ($p >= 70) return 'Cukup';
    return 'Kurang';
}
function status_pkl($r) {
    if ($r['id_perusahaan'] && $r['id_pembimbing']) return 'Aktif';
    if (!$r['id_perusahaan'] && !$r['id_pembimbing']) return 'Belum Ditempatkan';
    return 'Belum Lengkap';
}

// ---------- Login & hak akses ----------
function me() { return $_SESSION['user'] ?? null; }
function require_login() { if (!me()) redirect(BASE . '/auth/login.php'); }
function require_role($roles) {
    require_login();
    if (!in_array(me()['role'], $roles)) { flash('Kamu tidak punya akses ke halaman itu.', 'err'); redirect(home_url()); }
}

// Batasi data siswa sesuai role. Mengembalikan kondisi SQL, parameter ditambahkan ke $params.
function scope(&$params, $a = 's') {
    $u = me();
    switch ($u['role']) {
        case 'pembimbing': $params[] = $u['ref']; return "$a.id_pembimbing = ?";
        case 'perusahaan': $params[] = $u['ref']; return "$a.id_perusahaan = ?";
        case 'siswa':      $params[] = $u['ref']; return "$a.id_siswa = ?";
    }
    return '1=1';
}
function can_access_siswa($id) {
    $p = []; $sc = scope($p);
    return (bool)val("SELECT COUNT(*) FROM siswa s WHERE $sc AND s.id_siswa = ?", array_merge($p, [(int)$id]));
}
function siswa_options($sel = 0) {
    $p = []; $sc = scope($p);
    $rows = all("SELECT s.id_siswa, CONCAT(s.nama_siswa, ' (', IFNULL(s.kelas,'-'), ')') AS label FROM siswa s WHERE $sc ORDER BY s.nama_siswa", $p);
    return options($rows, 'id_siswa', 'label', $sel);
}

// Data siswa + rekap absensi/jurnal (dipakai dashboard, siswa, laporan)
function siswa_stats($where = '1=1', $extra = []) {
    $p = []; $sc = scope($p);
    $sql = "SELECT s.*, p.nama_perusahaan, b.nama_pembimbing,
              COALESCE(ab.total,0) total, COALESCE(ab.hadir,0) hadir, COALESCE(ab.terlambat,0) terlambat,
              COALESCE(ab.izin,0) izin, COALESCE(ab.sakit,0) sakit, COALESCE(ab.alpa,0) alpa,
              (SELECT COUNT(*) FROM jurnal j WHERE j.id_siswa = s.id_siswa) jml_jurnal,
              (SELECT COUNT(*) FROM jurnal j WHERE j.id_siswa = s.id_siswa AND j.status_validasi = 'Revisi') revisi,
              (SELECT COUNT(*) FROM monitoring m WHERE m.id_siswa = s.id_siswa) jml_monitoring
            FROM siswa s
            LEFT JOIN perusahaan p ON p.id_perusahaan = s.id_perusahaan
            LEFT JOIN pembimbing b ON b.id_pembimbing = s.id_pembimbing
            LEFT JOIN (SELECT id_siswa, COUNT(*) total,
                          SUM(status_hadir IN ('Hadir','Terlambat')) hadir,
                          SUM(status_hadir = 'Terlambat') terlambat,
                          SUM(status_hadir = 'Izin') izin,
                          SUM(status_hadir = 'Sakit') sakit,
                          SUM(status_hadir = 'Alpa') alpa
                       FROM absensi GROUP BY id_siswa) ab ON ab.id_siswa = s.id_siswa
            WHERE $sc AND ($where)
            ORDER BY s.nama_siswa";
    return all($sql, array_merge($p, $extra));
}
// Alasan siswa perlu perhatian (kosong = aman)
function alasan_perhatian($r) {
    $a = [];
    if ($r['alpa'] > 0) $a[] = 'Pernah alpa';
    $p = pct($r['hadir'], $r['total']);
    if ($p !== null && $p < 75) $a[] = 'Kehadiran ' . $p . '%';
    if ($r['revisi'] > 0) $a[] = 'Jurnal perlu revisi';
    if (!$r['id_perusahaan']) $a[] = 'Belum ditempatkan';
    return $a;
}

// ---------- Tampilan ----------
function page_start($title, $active = '') {
    $u = me();
    $all = ['admin', 'pembimbing', 'perusahaan', 'siswa'];
    $a = BASE . ($u['role'] === 'siswa' ? '/siswa/' : '/admin/');
    $menu = [
        [$a . 'dashboard.php', 'Dashboard', $all],
        [$a . 'siswa.php', 'Data Siswa', ['admin', 'pembimbing', 'perusahaan']],
        [$a . 'perusahaan.php', 'Data Perusahaan', ['admin']],
        [$a . 'pembimbing.php', 'Data Pembimbing', ['admin']],
        [$a . 'penempatan.php', 'Penempatan PKL', ['admin']],
        [$a . 'jurnal.php', 'Jurnal Kegiatan', $all],
        [$a . 'absensi.php', 'Absensi', $all],
        [$a . 'monitoring.php', 'Monitoring', $all],
        [BASE . '/laporan/laporan.php', 'Laporan', ['admin', 'pembimbing', 'perusahaan']],
    ];
    $roleLabel = ['admin' => 'Admin', 'pembimbing' => 'Guru Pembimbing', 'perusahaan' => 'Perusahaan', 'siswa' => 'Siswa'];
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>' . e($title) . ' - InternTrack</title><link rel="stylesheet" href="' . BASE . '/assets/style.css"></head><body>';
    echo '<div class="side"><div class="brand">InternTrack<small>Monitoring PKL</small></div><ul>';
    foreach ($menu as $m) {
        if (!in_array($u['role'], $m[2])) continue;
        echo '<li><a href="' . $m[0] . '"' . ($active === basename($m[0]) ? ' class="on"' : '') . '>' . $m[1] . '</a></li>';
    }
    echo '</ul><div class="who"><b>' . e($u['name']) . '</b><br>' . $roleLabel[$u['role']] . '<br><a href="' . BASE . '/auth/logout.php">Keluar</a></div></div>';
    echo '<div class="main"><h1>' . e($title) . '</h1>';
    if (!empty($_SESSION['flash'])) {
        echo '<div class="msg ' . ($_SESSION['flash'][1] === 'err' ? 'msg-err' : 'msg-ok') . '">' . e($_SESSION['flash'][0]) . '</div>';
        unset($_SESSION['flash']);
    }
}
function page_end() {
    echo '</div></body></html>';
}
function show_errors($errors) {
    if (!$errors) return;
    echo '<div class="msg msg-err"><b>Data belum bisa disimpan:</b><ul>';
    foreach ($errors as $er) echo '<li>' . e($er) . '</li>';
    echo '</ul></div>';
}
// Tombol hapus dengan konfirmasi (alert JS)
function delete_form($url, $id, $label = 'Hapus', $pesan = 'Yakin ingin menghapus data ini?') {
    return '<form method="post" action="' . e($url) . '" class="inline" onsubmit="return confirm(\'' . e($pesan) . '\')">'
        . '<input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="' . (int)$id . '">'
        . '<button class="btn btn-sm btn-red">' . e($label) . '</button></form>';
}
