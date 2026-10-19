<?php
require __DIR__ . '/../config/config.php';
require_role(['admin']);
$act = get('act') ?: 'list';
$id = (int)get('id');
$errors = [];
$f = ['nip' => '', 'nama_pembimbing' => '', 'username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('do') === 'delete') {
        $did = (int)post('id');
        $row = one("SELECT id_user FROM pembimbing WHERE id_pembimbing = ?", [$did]);
        if ($row) {
            if (val("SELECT COUNT(*) FROM monitoring WHERE id_pembimbing = ?", [$did])) {
                flash('Pembimbing tidak bisa dihapus karena sudah punya catatan monitoring.', 'err');
            } else {
                q("DELETE FROM pembimbing WHERE id_pembimbing = ?", [$did]);
                if ($row['id_user']) q("DELETE FROM users WHERE id_user = ?", [$row['id_user']]);
                flash('Pembimbing dihapus. Siswa bimbingannya kembali tanpa pembimbing.');
            }
        }
        redirect('pembimbing.php');
    }

    $editId = (int)post('id');
    foreach ($f as $k => $v) $f[$k] = post($k);
    $password = $_POST['password'] ?? '';
    $oldUser = $editId ? val("SELECT id_user FROM pembimbing WHERE id_pembimbing = ?", [$editId]) : null;

    if ($f['nip'] === '') $errors[] = 'NIP tidak boleh kosong.';
    elseif (!preg_match('/^\d{1,20}$/', $f['nip'])) $errors[] = 'NIP harus berupa angka, maksimal 20 digit.';
    elseif (val("SELECT COUNT(*) FROM pembimbing WHERE nip = ? AND id_pembimbing <> ?", [$f['nip'], $editId])) $errors[] = 'NIP sudah terdaftar.';
    if ($f['nama_pembimbing'] === '') $errors[] = 'Nama tidak boleh kosong.';
    if ($f['username'] === '') $errors[] = 'Username tidak boleh kosong.';
    elseif (val("SELECT COUNT(*) FROM users WHERE username = ? AND id_user <> ?", [$f['username'], (int)$oldUser])) $errors[] = 'Username sudah dipakai.';
    if (!$oldUser && $password === '') $errors[] = 'Password tidak boleh kosong.';
    if ($password !== '' && strlen($password) < 6) $errors[] = 'Password minimal 6 karakter.';

    if (!$errors) {
        $db->beginTransaction();
        try {
            if ($oldUser) {
                q("UPDATE users SET username = ? WHERE id_user = ?", [$f['username'], $oldUser]);
                if ($password !== '') q("UPDATE users SET password = ? WHERE id_user = ?", [password_hash($password, PASSWORD_DEFAULT), $oldUser]);
                $uid = $oldUser;
            } else {
                q("INSERT INTO users (username, password, role) VALUES (?, ?, 'pembimbing')", [$f['username'], password_hash($password, PASSWORD_DEFAULT)]);
                $uid = $db->lastInsertId();
            }
            if ($editId) {
                q("UPDATE pembimbing SET nip=?, nama_pembimbing=?, id_user=? WHERE id_pembimbing=?", [$f['nip'], $f['nama_pembimbing'], $uid, $editId]);
                flash('Data pembimbing diperbarui.');
            } else {
                q("INSERT INTO pembimbing (nip, nama_pembimbing, id_user) VALUES (?,?,?)", [$f['nip'], $f['nama_pembimbing'], $uid]);
                flash('Pembimbing berhasil ditambahkan.');
            }
            $db->commit();
            redirect('pembimbing.php');
        } catch (Exception $ex) {
            $db->rollBack();
            $errors[] = 'Gagal menyimpan: ' . $ex->getMessage();
        }
    }
    $act = $editId ? 'edit' : 'add';
    $id = $editId;
}

if ($act === 'add' || $act === 'edit') {
    if ($act === 'edit' && !$_POST) {
        $row = one("SELECT b.*, u.username FROM pembimbing b LEFT JOIN users u ON u.id_user = b.id_user WHERE b.id_pembimbing = ?", [$id]);
        if (!$row) { flash('Data tidak ditemukan.', 'err'); redirect('pembimbing.php'); }
        foreach ($f as $k => $v) $f[$k] = $row[$k] ?? '';
    }
    page_start($act === 'edit' ? 'Edit Pembimbing' : 'Tambah Pembimbing', 'pembimbing.php');
    show_errors($errors);
    ?>
    <form method="post" class="form">
        <input type="hidden" name="id" value="<?= $act === 'edit' ? $id : 0 ?>">
        <div class="row"><label>NIP</label><input type="text" name="nip" maxlength="20" value="<?= e($f['nip']) ?>"></div>
        <div class="row"><label>Nama Pembimbing</label><input type="text" name="nama_pembimbing" value="<?= e($f['nama_pembimbing']) ?>"></div>
        <div class="row"><label>Username login</label><input type="text" name="username" value="<?= e($f['username']) ?>"></div>
        <div class="row"><label>Password</label><input type="password" name="password">
            <?php if ($act === 'edit'): ?><div class="hint">Kosongkan jika tidak ingin mengganti password.</div><?php endif; ?></div>
        <div class="btns"><button class="btn">Simpan</button> <a class="btn btn-gray" href="pembimbing.php">Batal</a></div>
    </form>
    <?php
    page_end(); exit;
}

if ($act === 'detail') {
    $b = one("SELECT b.*, u.username FROM pembimbing b LEFT JOIN users u ON u.id_user = b.id_user WHERE b.id_pembimbing = ?", [$id]);
    if (!$b) { flash('Data tidak ditemukan.', 'err'); redirect('pembimbing.php'); }
    $siswa = all("SELECT s.*, p.nama_perusahaan FROM siswa s LEFT JOIN perusahaan p ON p.id_perusahaan = s.id_perusahaan WHERE s.id_pembimbing = ? ORDER BY s.nama_siswa", [$id]);
    $jmlMon = val("SELECT COUNT(*) FROM monitoring WHERE id_pembimbing = ?", [$id]);
    page_start('Detail Pembimbing', 'pembimbing.php');
    ?>
    <div class="dl">
        <div><span>NIP</span><span><?= e($b['nip']) ?></span></div>
        <div><span>Nama</span><span><?= e($b['nama_pembimbing']) ?></span></div>
        <div><span>Username login</span><span><?= e($b['username'] ?: '-') ?></span></div>
        <div><span>Siswa bimbingan</span><span><?= count($siswa) ?></span></div>
        <div><span>Kunjungan monitoring</span><span><?= $jmlMon ?></span></div>
    </div>
    <h2>Siswa bimbingan</h2>
    <table>
        <tr><th>NISN</th><th>Nama</th><th>Kelas</th><th>Perusahaan</th></tr>
        <?php foreach ($siswa as $s): ?>
            <tr><td><?= e($s['nisn']) ?></td><td><?= e($s['nama_siswa']) ?></td><td><?= e($s['kelas']) ?></td><td><?= e($s['nama_perusahaan'] ?: '-') ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$siswa): ?><tr><td colspan="4" class="empty">Belum ada siswa bimbingan.</td></tr><?php endif; ?>
    </table>
    <p><a class="btn" href="pembimbing.php?act=edit&id=<?= $id ?>">Edit</a> <a class="btn btn-gray" href="pembimbing.php">Kembali</a></p>
    <?php
    page_end(); exit;
}

$qs = get('q');
$where = '1=1'; $par = [];
if ($qs !== '') { $where = '(b.nama_pembimbing LIKE ? OR b.nip LIKE ?)'; $par = ["%$qs%", "%$qs%"]; }
$rows = all("SELECT b.*, (SELECT COUNT(*) FROM siswa s WHERE s.id_pembimbing = b.id_pembimbing) jml FROM pembimbing b WHERE $where ORDER BY b.nama_pembimbing", $par);

page_start('Data Pembimbing', 'pembimbing.php');
?>
<div class="top"><span><?= count($rows) ?> pembimbing</span><a class="btn" href="pembimbing.php?act=add">+ Tambah Pembimbing</a></div>
<form method="get" class="filter">
    <div><label>Cari nama / NIP</label><input type="text" name="q" value="<?= e($qs) ?>"></div>
    <div><button class="btn">Cari</button> <a class="btn btn-gray" href="pembimbing.php">Reset</a></div>
</form>
<table>
    <tr><th>No</th><th>NIP</th><th>Nama</th><th>Jumlah Siswa</th><th>Aksi</th></tr>
    <?php foreach ($rows as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= e($r['nip']) ?></td>
            <td><?= e($r['nama_pembimbing']) ?></td>
            <td class="num"><?= $r['jml'] ?></td>
            <td class="act">
                <a class="btn btn-sm btn-gray" href="pembimbing.php?act=detail&id=<?= $r['id_pembimbing'] ?>">Detail</a>
                <a class="btn btn-sm btn-gray" href="pembimbing.php?act=edit&id=<?= $r['id_pembimbing'] ?>">Edit</a>
                <?= delete_form('pembimbing.php', $r['id_pembimbing'], 'Hapus', 'Hapus pembimbing ' . $r['nama_pembimbing'] . '?') ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="empty">Data tidak ditemukan.</td></tr><?php endif; ?>
</table>
<?php page_end(); ?>
