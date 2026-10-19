<?php
require __DIR__ . '/../config/config.php';
require_role(['admin', 'pembimbing', 'perusahaan']);
$isAdmin = me()['role'] === 'admin';
$act = get('act') ?: 'list';
$id = (int)get('id');
$errors = [];
$f = ['nisn' => '', 'nama_siswa' => '', 'kelas' => '', 'id_perusahaan' => '', 'id_pembimbing' => '', 'username' => ''];

/* ---------- Proses POST (hanya admin) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) { flash('Hanya admin yang boleh mengubah data siswa.', 'err'); redirect('siswa.php'); }

    if (post('do') === 'delete') {
        $did = (int)post('id');
        $row = one("SELECT id_user FROM siswa WHERE id_siswa = ?", [$did]);
        if ($row) {
            q("DELETE FROM siswa WHERE id_siswa = ?", [$did]);
            if ($row['id_user']) q("DELETE FROM users WHERE id_user = ?", [$row['id_user']]);
            flash('Data siswa dihapus (termasuk jurnal, absensi, dan monitoring miliknya).');
        }
        redirect('siswa.php');
    }

    $editId = (int)post('id');
    foreach ($f as $k => $v) $f[$k] = post($k);
    $password = $_POST['password'] ?? '';

    if ($f['nisn'] === '') $errors[] = 'NISN tidak boleh kosong.';
    elseif (!preg_match('/^\d{1,10}$/', $f['nisn'])) $errors[] = 'NISN harus berupa angka, maksimal 10 digit.';
    elseif (val("SELECT COUNT(*) FROM siswa WHERE nisn = ? AND id_siswa <> ?", [$f['nisn'], $editId])) $errors[] = 'NISN sudah terdaftar, tidak boleh duplikat.';
    if ($f['nama_siswa'] === '') $errors[] = 'Nama tidak boleh kosong.';
    if ($f['kelas'] === '') $errors[] = 'Kelas tidak boleh kosong.';

    $oldUser = $editId ? val("SELECT id_user FROM siswa WHERE id_siswa = ?", [$editId]) : null;
    if ($f['username'] === '') $errors[] = 'Username tidak boleh kosong.';
    elseif (val("SELECT COUNT(*) FROM users WHERE username = ? AND id_user <> ?", [$f['username'], (int)$oldUser])) $errors[] = 'Username sudah dipakai.';
    if (!$editId || !$oldUser) { if ($password === '') $errors[] = 'Password tidak boleh kosong.'; }
    if ($password !== '' && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (!$errors) {
        $pr = $f['id_perusahaan'] ?: null;
        $pb = $f['id_pembimbing'] ?: null;
        $db->beginTransaction();
        try {
            if ($editId && $oldUser) {
                q("UPDATE users SET username = ? WHERE id_user = ?", [$f['username'], $oldUser]);
                if ($password !== '') q("UPDATE users SET password = ? WHERE id_user = ?", [password_hash($password, PASSWORD_DEFAULT), $oldUser]);
                $uid = $oldUser;
            } else {
                q("INSERT INTO users (username, password, role) VALUES (?, ?, 'siswa')", [$f['username'], password_hash($password, PASSWORD_DEFAULT)]);
                $uid = $db->lastInsertId();
            }
            if ($editId) {
                q("UPDATE siswa SET nisn=?, nama_siswa=?, kelas=?, id_perusahaan=?, id_pembimbing=?, id_user=? WHERE id_siswa=?",
                  [$f['nisn'], $f['nama_siswa'], $f['kelas'], $pr, $pb, $uid, $editId]);
                flash('Data siswa berhasil diperbarui.');
            } else {
                q("INSERT INTO siswa (nisn, nama_siswa, kelas, id_user, id_perusahaan, id_pembimbing) VALUES (?,?,?,?,?,?)",
                  [$f['nisn'], $f['nama_siswa'], $f['kelas'], $uid, $pr, $pb]);
                flash('Data siswa berhasil ditambahkan.');
            }
            $db->commit();
            redirect('siswa.php');
        } catch (Exception $ex) {
            $db->rollBack();
            $errors[] = 'Gagal menyimpan: ' . $ex->getMessage();
        }
    }
    $act = $editId ? 'edit' : 'add';
    $id = $editId;
}

$perusahaanList = all("SELECT id_perusahaan, nama_perusahaan FROM perusahaan ORDER BY nama_perusahaan");
$pembimbingList = all("SELECT id_pembimbing, nama_pembimbing FROM pembimbing ORDER BY nama_pembimbing");

/* ---------- Form tambah / edit ---------- */
if ($isAdmin && ($act === 'add' || $act === 'edit')) {
    if ($act === 'edit' && !$_POST) {
        $row = one("SELECT s.*, u.username FROM siswa s LEFT JOIN users u ON u.id_user = s.id_user WHERE s.id_siswa = ?", [$id]);
        if (!$row) { flash('Data siswa tidak ditemukan.', 'err'); redirect('siswa.php'); }
        foreach ($f as $k => $v) $f[$k] = $row[$k] ?? '';
    }
    page_start($act === 'edit' ? 'Edit Siswa' : 'Tambah Siswa', 'siswa.php');
    show_errors($errors);
    ?>
    <form method="post" class="form">
        <input type="hidden" name="id" value="<?= $act === 'edit' ? $id : 0 ?>">
        <div class="row"><label>NISN</label><input type="text" name="nisn" maxlength="10" value="<?= e($f['nisn']) ?>"></div>
        <div class="row"><label>Nama Siswa</label><input type="text" name="nama_siswa" value="<?= e($f['nama_siswa']) ?>"></div>
        <div class="row"><label>Kelas</label><input type="text" name="kelas" value="<?= e($f['kelas']) ?>" placeholder="contoh: XII RPL 1">
            <div class="hint">Jurusan diambil dari kelas (kata kedua), misalnya RPL.</div></div>
        <div class="row"><label>Perusahaan PKL</label>
            <select name="id_perusahaan"><option value="">- belum ditempatkan -</option><?= options($perusahaanList, 'id_perusahaan', 'nama_perusahaan', $f['id_perusahaan']) ?></select></div>
        <div class="row"><label>Guru Pembimbing</label>
            <select name="id_pembimbing"><option value="">- belum ada -</option><?= options($pembimbingList, 'id_pembimbing', 'nama_pembimbing', $f['id_pembimbing']) ?></select></div>
        <div class="row"><label>Username login</label><input type="text" name="username" value="<?= e($f['username']) ?>"></div>
        <div class="row"><label>Password</label><input type="password" name="password">
            <?php if ($act === 'edit'): ?><div class="hint">Kosongkan jika tidak ingin mengganti password.</div><?php endif; ?></div>
        <div class="btns"><button class="btn">Simpan</button> <a class="btn btn-gray" href="siswa.php">Batal</a></div>
    </form>
    <?php
    page_end(); exit;
}

/* ---------- Detail ---------- */
if ($act === 'detail') {
    if (!can_access_siswa($id)) { flash('Data siswa tidak ditemukan.', 'err'); redirect('siswa.php'); }
    $s = siswa_stats('s.id_siswa = ?', [$id])[0];
    $p = pct($s['hadir'], $s['total']);
    $jurnal = all("SELECT * FROM jurnal WHERE id_siswa = ? ORDER BY tanggal DESC", [$id]);
    $mon = all("SELECT m.*, b.nama_pembimbing FROM monitoring m JOIN pembimbing b ON b.id_pembimbing = m.id_pembimbing WHERE m.id_siswa = ? ORDER BY m.tanggal_kunjungan DESC", [$id]);
    page_start('Detail Siswa', 'siswa.php');
    ?>
    <div class="dl">
        <div><span>NISN</span><span><?= e($s['nisn']) ?></span></div>
        <div><span>Nama</span><span><?= e($s['nama_siswa']) ?></span></div>
        <div><span>Kelas / Jurusan</span><span><?= e($s['kelas']) ?> / <?= e(jurusan($s['kelas'])) ?></span></div>
        <div><span>Perusahaan PKL</span><span><?= e($s['nama_perusahaan'] ?: '-') ?></span></div>
        <div><span>Guru Pembimbing</span><span><?= e($s['nama_pembimbing'] ?: '-') ?></span></div>
        <div><span>Status PKL</span><span><?= badge(status_pkl($s)) ?></span></div>
        <div><span>Kehadiran</span><span><?= $s['hadir'] ?> dari <?= $s['total'] ?> hari
            (<?= $p === null ? '-' : $p . '%' ?>) <?= $p === null ? '' : badge(kategori($p)) ?></span></div>
    </div>
    <h2>Jurnal kegiatan</h2>
    <table>
        <tr><th width="100">Tanggal</th><th>Kegiatan</th><th width="100">Status</th></tr>
        <?php foreach ($jurnal as $j): ?>
            <tr><td><?= tgl($j['tanggal']) ?></td><td><?= e($j['kegiatan']) ?></td><td><?= badge($j['status_validasi']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$jurnal): ?><tr><td colspan="3" class="empty">Belum ada jurnal.</td></tr><?php endif; ?>
    </table>
    <h2>Catatan monitoring</h2>
    <table>
        <tr><th width="100">Tanggal</th><th width="160">Pembimbing</th><th>Catatan</th></tr>
        <?php foreach ($mon as $m): ?>
            <tr><td><?= tgl($m['tanggal_kunjungan']) ?></td><td><?= e($m['nama_pembimbing']) ?></td><td><?= e($m['catatan_feedback']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$mon): ?><tr><td colspan="3" class="empty">Belum ada monitoring.</td></tr><?php endif; ?>
    </table>
    <p><?php if ($isAdmin): ?><a class="btn" href="siswa.php?act=edit&id=<?= $id ?>">Edit</a> <?php endif; ?><a class="btn btn-gray" href="siswa.php">Kembali</a></p>
    <?php
    page_end(); exit;
}

/* ---------- Daftar + pencarian + filter ---------- */
$qs = get('q'); $fk = get('kelas'); $fp = get('id_perusahaan'); $fb = get('id_pembimbing'); $fs = get('status');
$where = ['1=1']; $par = [];
if ($qs !== '') { $where[] = '(s.nama_siswa LIKE ? OR s.nisn LIKE ?)'; $par[] = "%$qs%"; $par[] = "%$qs%"; }
if ($fk !== '') { $where[] = 's.kelas = ?'; $par[] = $fk; }
if ($fp !== '') { $where[] = 's.id_perusahaan = ?'; $par[] = $fp; }
if ($fb !== '') { $where[] = 's.id_pembimbing = ?'; $par[] = $fb; }
if ($fs === 'aktif') $where[] = 's.id_perusahaan IS NOT NULL AND s.id_pembimbing IS NOT NULL';
if ($fs === 'belum') $where[] = 's.id_perusahaan IS NULL AND s.id_pembimbing IS NULL';
if ($fs === 'sebagian') $where[] = '(s.id_perusahaan IS NULL) <> (s.id_pembimbing IS NULL)';
$rows = siswa_stats(implode(' AND ', $where), $par);
$kelasList = all("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL ORDER BY kelas");

page_start('Data Siswa', 'siswa.php');
?>
<div class="top">
    <span><?= count($rows) ?> siswa ditemukan</span>
    <?php if ($isAdmin): ?><a class="btn" href="siswa.php?act=add">+ Tambah Siswa</a><?php endif; ?>
</div>
<form method="get" class="filter">
    <div><label>Cari nama / NISN</label><input type="text" name="q" value="<?= e($qs) ?>"></div>
    <div><label>Kelas</label><select name="kelas"><option value="">Semua</option><?= options($kelasList, 'kelas', 'kelas', $fk) ?></select></div>
    <div><label>Perusahaan</label><select name="id_perusahaan"><option value="">Semua</option><?= options($perusahaanList, 'id_perusahaan', 'nama_perusahaan', $fp) ?></select></div>
    <?php if (me()['role'] !== 'pembimbing'): ?>
    <div><label>Pembimbing</label><select name="id_pembimbing"><option value="">Semua</option><?= options($pembimbingList, 'id_pembimbing', 'nama_pembimbing', $fb) ?></select></div>
    <?php endif; ?>
    <div><label>Status PKL</label><select name="status">
        <option value="">Semua</option>
        <option value="aktif"<?= $fs === 'aktif' ? ' selected' : '' ?>>Aktif</option>
        <option value="sebagian"<?= $fs === 'sebagian' ? ' selected' : '' ?>>Belum Lengkap</option>
        <option value="belum"<?= $fs === 'belum' ? ' selected' : '' ?>>Belum Ditempatkan</option>
    </select></div>
    <div><button class="btn">Cari</button> <a class="btn btn-gray" href="siswa.php">Reset</a></div>
</form>
<div class="tablewrap">
<table>
    <tr><th>No</th><th>NISN</th><th>Nama</th><th>Kelas</th><th>Jurusan</th><th>Perusahaan</th><th>Pembimbing</th><th>Status</th><th>Aksi</th></tr>
    <?php foreach ($rows as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= e($r['nisn']) ?></td>
            <td><?= e($r['nama_siswa']) ?></td>
            <td><?= e($r['kelas']) ?></td>
            <td><?= e(jurusan($r['kelas'])) ?></td>
            <td><?= e($r['nama_perusahaan'] ?: '-') ?></td>
            <td><?= e($r['nama_pembimbing'] ?: '-') ?></td>
            <td><?= badge(status_pkl($r)) ?></td>
            <td class="act">
                <a class="btn btn-sm btn-gray" href="siswa.php?act=detail&id=<?= $r['id_siswa'] ?>">Detail</a>
                <?php if ($isAdmin): ?>
                    <a class="btn btn-sm btn-gray" href="siswa.php?act=edit&id=<?= $r['id_siswa'] ?>">Edit</a>
                    <?= delete_form('siswa.php', $r['id_siswa'], 'Hapus', 'Hapus siswa ' . $r['nama_siswa'] . '? Jurnal, absensi, dan monitoringnya ikut terhapus.') ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="9" class="empty">Data tidak ditemukan.</td></tr><?php endif; ?>
</table>
</div>
<?php page_end(); ?>
