<?php
require __DIR__ . '/../config/config.php';
require_role(['admin']);
$act = get('act') ?: 'list';
$id = (int)get('id');
$errors = [];
$f = ['nama_perusahaan' => '', 'alamat' => '', 'username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('do') === 'delete') {
        $did = (int)post('id');
        $row = one("SELECT id_user FROM perusahaan WHERE id_perusahaan = ?", [$did]);
        if ($row) {
            q("DELETE FROM perusahaan WHERE id_perusahaan = ?", [$did]); // siswa terkait otomatis jadi belum ditempatkan
            if ($row['id_user']) q("DELETE FROM users WHERE id_user = ?", [$row['id_user']]);
            flash('Perusahaan dihapus. Siswa yang ditempatkan di sana kembali menjadi belum ditempatkan.');
        }
        redirect('perusahaan.php');
    }

    $editId = (int)post('id');
    foreach ($f as $k => $v) $f[$k] = post($k);
    $password = $_POST['password'] ?? '';
    $oldUser = $editId ? val("SELECT id_user FROM perusahaan WHERE id_perusahaan = ?", [$editId]) : null;

    if ($f['nama_perusahaan'] === '') $errors[] = 'Nama perusahaan tidak boleh kosong.';
    if ($f['alamat'] === '') $errors[] = 'Alamat tidak boleh kosong.';
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
                q("INSERT INTO users (username, password, role) VALUES (?, ?, 'perusahaan')", [$f['username'], password_hash($password, PASSWORD_DEFAULT)]);
                $uid = $db->lastInsertId();
            }
            if ($editId) {
                q("UPDATE perusahaan SET nama_perusahaan=?, alamat=?, id_user=? WHERE id_perusahaan=?", [$f['nama_perusahaan'], $f['alamat'], $uid, $editId]);
                flash('Data perusahaan diperbarui.');
            } else {
                q("INSERT INTO perusahaan (nama_perusahaan, alamat, id_user) VALUES (?,?,?)", [$f['nama_perusahaan'], $f['alamat'], $uid]);
                flash('Perusahaan berhasil ditambahkan.');
            }
            $db->commit();
            redirect('perusahaan.php');
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
        $row = one("SELECT p.*, u.username FROM perusahaan p LEFT JOIN users u ON u.id_user = p.id_user WHERE p.id_perusahaan = ?", [$id]);
        if (!$row) { flash('Data tidak ditemukan.', 'err'); redirect('perusahaan.php'); }
        foreach ($f as $k => $v) $f[$k] = $row[$k] ?? '';
    }
    page_start($act === 'edit' ? 'Edit Perusahaan' : 'Tambah Perusahaan', 'perusahaan.php');
    show_errors($errors);
    ?>
    <form method="post" class="form">
        <input type="hidden" name="id" value="<?= $act === 'edit' ? $id : 0 ?>">
        <div class="row"><label>Nama Perusahaan</label><input type="text" name="nama_perusahaan" value="<?= e($f['nama_perusahaan']) ?>"></div>
        <div class="row"><label>Alamat</label><textarea name="alamat"><?= e($f['alamat']) ?></textarea></div>
        <div class="row"><label>Username login</label><input type="text" name="username" value="<?= e($f['username']) ?>"></div>
        <div class="row"><label>Password</label><input type="password" name="password">
            <?php if ($act === 'edit'): ?><div class="hint">Kosongkan jika tidak ingin mengganti password.</div><?php endif; ?></div>
        <div class="btns"><button class="btn">Simpan</button> <a class="btn btn-gray" href="perusahaan.php">Batal</a></div>
    </form>
    <?php
    page_end(); exit;
}

if ($act === 'detail') {
    $p = one("SELECT p.*, u.username FROM perusahaan p LEFT JOIN users u ON u.id_user = p.id_user WHERE p.id_perusahaan = ?", [$id]);
    if (!$p) { flash('Data tidak ditemukan.', 'err'); redirect('perusahaan.php'); }
    $siswa = all("SELECT s.*, b.nama_pembimbing FROM siswa s LEFT JOIN pembimbing b ON b.id_pembimbing = s.id_pembimbing WHERE s.id_perusahaan = ? ORDER BY s.nama_siswa", [$id]);
    page_start('Detail Perusahaan', 'perusahaan.php');
    ?>
    <div class="dl">
        <div><span>Nama Perusahaan</span><span><?= e($p['nama_perusahaan']) ?></span></div>
        <div><span>Alamat</span><span><?= e($p['alamat']) ?></span></div>
        <div><span>Username login</span><span><?= e($p['username'] ?: '-') ?></span></div>
        <div><span>Jumlah siswa PKL</span><span><?= count($siswa) ?></span></div>
    </div>
    <h2>Siswa yang PKL di sini</h2>
    <table>
        <tr><th>NISN</th><th>Nama</th><th>Kelas</th><th>Pembimbing</th></tr>
        <?php foreach ($siswa as $s): ?>
            <tr><td><?= e($s['nisn']) ?></td><td><?= e($s['nama_siswa']) ?></td><td><?= e($s['kelas']) ?></td><td><?= e($s['nama_pembimbing'] ?: '-') ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$siswa): ?><tr><td colspan="4" class="empty">Belum ada siswa.</td></tr><?php endif; ?>
    </table>
    <p><a class="btn" href="perusahaan.php?act=edit&id=<?= $id ?>">Edit</a> <a class="btn btn-gray" href="perusahaan.php">Kembali</a></p>
    <?php
    page_end(); exit;
}

$qs = get('q');
$where = '1=1'; $par = [];
if ($qs !== '') { $where = '(p.nama_perusahaan LIKE ? OR p.alamat LIKE ?)'; $par = ["%$qs%", "%$qs%"]; }
$rows = all("SELECT p.*, (SELECT COUNT(*) FROM siswa s WHERE s.id_perusahaan = p.id_perusahaan) jml FROM perusahaan p WHERE $where ORDER BY p.nama_perusahaan", $par);

page_start('Data Perusahaan', 'perusahaan.php');
?>
<div class="top"><span><?= count($rows) ?> perusahaan</span><a class="btn" href="perusahaan.php?act=add">+ Tambah Perusahaan</a></div>
<form method="get" class="filter">
    <div><label>Cari nama / alamat</label><input type="text" name="q" value="<?= e($qs) ?>"></div>
    <div><button class="btn">Cari</button> <a class="btn btn-gray" href="perusahaan.php">Reset</a></div>
</form>
<table>
    <tr><th>No</th><th>Nama Perusahaan</th><th>Alamat</th><th>Jumlah Siswa</th><th>Aksi</th></tr>
    <?php foreach ($rows as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= e($r['nama_perusahaan']) ?></td>
            <td><?= e($r['alamat']) ?></td>
            <td class="num"><?= $r['jml'] ?></td>
            <td class="act">
                <a class="btn btn-sm btn-gray" href="perusahaan.php?act=detail&id=<?= $r['id_perusahaan'] ?>">Detail</a>
                <a class="btn btn-sm btn-gray" href="perusahaan.php?act=edit&id=<?= $r['id_perusahaan'] ?>">Edit</a>
                <?= delete_form('perusahaan.php', $r['id_perusahaan'], 'Hapus', 'Hapus ' . $r['nama_perusahaan'] . '? Siswa di sana akan menjadi belum ditempatkan.') ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="empty">Data tidak ditemukan.</td></tr><?php endif; ?>
</table>
<?php page_end(); ?>
