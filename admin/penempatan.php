<?php
require __DIR__ . '/../config/config.php';
require_role(['admin']);
$act = get('act') ?: 'list';
$errors = [];

$perusahaanList = all("SELECT id_perusahaan, nama_perusahaan FROM perusahaan ORDER BY nama_perusahaan");
$pembimbingList = all("SELECT id_pembimbing, nama_pembimbing FROM pembimbing ORDER BY nama_pembimbing");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = (int)post('id_siswa');
    if (post('do') === 'lepas') {
        q("UPDATE siswa SET id_perusahaan = NULL, id_pembimbing = NULL WHERE id_siswa = ?", [$sid]);
        flash('Penempatan siswa dilepas.');
        redirect('penempatan.php');
    }
    $pr = (int)post('id_perusahaan');
    $pb = (int)post('id_pembimbing');
    if (!$sid || !val("SELECT COUNT(*) FROM siswa WHERE id_siswa = ?", [$sid])) $errors[] = 'Siswa wajib dipilih.';
    if (!$pr) $errors[] = 'Perusahaan wajib dipilih.';
    if (!$pb) $errors[] = 'Pembimbing wajib dipilih.';
    if (!$errors) {
        q("UPDATE siswa SET id_perusahaan = ?, id_pembimbing = ? WHERE id_siswa = ?", [$pr, $pb, $sid]);
        flash('Penempatan PKL berhasil disimpan.');
        redirect('penempatan.php');
    }
    $act = 'edit';
}

/* ---------- Form penempatan ---------- */
if ($act === 'edit') {
    $sid = (int)(post('id_siswa') ?: get('id'));
    $s = one("SELECT * FROM siswa WHERE id_siswa = ?", [$sid]);
    $selP = $_POST ? post('id_perusahaan') : ($s['id_perusahaan'] ?? '');
    $selB = $_POST ? post('id_pembimbing') : ($s['id_pembimbing'] ?? '');
    $siswaList = all("SELECT id_siswa, CONCAT(nama_siswa, ' (', IFNULL(kelas,'-'), ')') label FROM siswa ORDER BY nama_siswa");
    page_start('Atur Penempatan PKL', 'penempatan.php');
    show_errors($errors);
    ?>
    <form method="post" class="form">
        <div class="row"><label>Siswa</label>
            <select name="id_siswa"><option value="">- pilih siswa -</option><?= options($siswaList, 'id_siswa', 'label', $sid) ?></select></div>
        <div class="row"><label>Perusahaan</label>
            <select name="id_perusahaan"><option value="">- pilih perusahaan -</option><?= options($perusahaanList, 'id_perusahaan', 'nama_perusahaan', $selP) ?></select></div>
        <div class="row"><label>Guru Pembimbing</label>
            <select name="id_pembimbing"><option value="">- pilih pembimbing -</option><?= options($pembimbingList, 'id_pembimbing', 'nama_pembimbing', $selB) ?></select></div>
        <div class="btns"><button class="btn">Simpan</button> <a class="btn btn-gray" href="penempatan.php">Batal</a></div>
    </form>
    <?php
    page_end(); exit;
}

/* ---------- Daftar ---------- */
$qs = get('q'); $fp = get('id_perusahaan'); $fb = get('id_pembimbing'); $fs = get('status');
$where = ['1=1']; $par = [];
if ($qs !== '') { $where[] = '(s.nama_siswa LIKE ? OR s.nisn LIKE ?)'; $par[] = "%$qs%"; $par[] = "%$qs%"; }
if ($fp !== '') { $where[] = 's.id_perusahaan = ?'; $par[] = $fp; }
if ($fb !== '') { $where[] = 's.id_pembimbing = ?'; $par[] = $fb; }
if ($fs === 'aktif') $where[] = 's.id_perusahaan IS NOT NULL AND s.id_pembimbing IS NOT NULL';
if ($fs === 'belum') $where[] = 's.id_perusahaan IS NULL AND s.id_pembimbing IS NULL';
if ($fs === 'sebagian') $where[] = '(s.id_perusahaan IS NULL) <> (s.id_pembimbing IS NULL)';
$rows = siswa_stats(implode(' AND ', $where), $par);

page_start('Penempatan PKL', 'penempatan.php');
?>
<div class="top"><span><?= count($rows) ?> siswa</span><a class="btn" href="penempatan.php?act=edit">+ Tempatkan Siswa</a></div>
<form method="get" class="filter">
    <div><label>Cari nama / NISN</label><input type="text" name="q" value="<?= e($qs) ?>"></div>
    <div><label>Perusahaan</label><select name="id_perusahaan"><option value="">Semua</option><?= options($perusahaanList, 'id_perusahaan', 'nama_perusahaan', $fp) ?></select></div>
    <div><label>Pembimbing</label><select name="id_pembimbing"><option value="">Semua</option><?= options($pembimbingList, 'id_pembimbing', 'nama_pembimbing', $fb) ?></select></div>
    <div><label>Status</label><select name="status">
        <option value="">Semua</option>
        <option value="aktif"<?= $fs === 'aktif' ? ' selected' : '' ?>>Aktif</option>
        <option value="sebagian"<?= $fs === 'sebagian' ? ' selected' : '' ?>>Belum Lengkap</option>
        <option value="belum"<?= $fs === 'belum' ? ' selected' : '' ?>>Belum Ditempatkan</option>
    </select></div>
    <div><button class="btn">Cari</button> <a class="btn btn-gray" href="penempatan.php">Reset</a></div>
</form>
<table>
    <tr><th>No</th><th>Siswa</th><th>Kelas</th><th>Perusahaan</th><th>Pembimbing</th><th>Status</th><th>Aksi</th></tr>
    <?php foreach ($rows as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><?= e($r['nama_siswa']) ?><br><small><?= e($r['nisn']) ?></small></td>
            <td><?= e($r['kelas']) ?></td>
            <td><?= e($r['nama_perusahaan'] ?: '-') ?></td>
            <td><?= e($r['nama_pembimbing'] ?: '-') ?></td>
            <td><?= badge(status_pkl($r)) ?></td>
            <td class="act">
                <a class="btn btn-sm btn-gray" href="penempatan.php?act=edit&id=<?= $r['id_siswa'] ?>">Atur</a>
                <?php if ($r['id_perusahaan'] || $r['id_pembimbing']): ?>
                <form method="post" class="inline" onsubmit="return confirm('Lepas penempatan <?= e($r['nama_siswa']) ?>?')">
                    <input type="hidden" name="do" value="lepas"><input type="hidden" name="id_siswa" value="<?= $r['id_siswa'] ?>">
                    <button class="btn btn-sm btn-red">Lepas</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="7" class="empty">Data tidak ditemukan.</td></tr><?php endif; ?>
</table>
<?php page_end(); ?>
