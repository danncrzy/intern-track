<?php
require __DIR__ . '/../config/config.php';
require_login();
$u = me();
$role = $u['role'];
$isSiswa = $role === 'siswa';
$canEdit = !$isSiswa;
$act = get('act') ?: 'list';
$id = (int)get('id');
$errors = [];
$statusList = ['Hadir', 'Terlambat', 'Izin', 'Sakit', 'Alpa'];
$f = ['id_siswa' => $isSiswa ? $u['ref'] : '', 'tanggal' => date('Y-m-d'), 'status_hadir' => '', 'jam_masuk' => '', 'jam_pulang' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (post('do') === 'delete') {
        $a = one("SELECT id_siswa FROM absensi WHERE id_absensi = ?", [(int)post('id')]);
        if ($canEdit && $a && can_access_siswa($a['id_siswa'])) { q("DELETE FROM absensi WHERE id_absensi = ?", [(int)post('id')]); flash('Data absensi dihapus.'); }
        else flash('Aksi tidak diizinkan.', 'err');
        redirect('absensi.php');
    }

    $editId = (int)post('id');
    if ($editId && !$canEdit) { flash('Siswa tidak bisa mengubah absensi yang sudah tersimpan.', 'err'); redirect('absensi.php'); }
    foreach ($f as $k => $v) $f[$k] = post($k);
    if ($isSiswa) $f['id_siswa'] = $u['ref'];

    if ($f['id_siswa'] === '' || !$f['id_siswa']) $errors[] = 'Siswa wajib dipilih.';
    elseif (!can_access_siswa($f['id_siswa'])) $errors[] = 'Siswa tidak valid.';
    if ($f['status_hadir'] === '') $errors[] = 'Status wajib dipilih.';
    elseif (!in_array($f['status_hadir'], $statusList)) $errors[] = 'Status tidak valid.';
    if ($f['tanggal'] === '') $errors[] = 'Tanggal wajib diisi.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['tanggal']) || !strtotime($f['tanggal'])) $errors[] = 'Format tanggal tidak valid.';
    elseif ($f['tanggal'] > date('Y-m-d')) $errors[] = 'Tanggal tidak boleh lebih dari hari ini.';
    elseif ($f['id_siswa'] && val("SELECT COUNT(*) FROM absensi WHERE id_siswa = ? AND tanggal = ? AND id_absensi <> ?", [$f['id_siswa'], $f['tanggal'], $editId]))
        $errors[] = 'Absensi siswa ini pada tanggal tersebut sudah ada.';

    $hadirLike = in_array($f['status_hadir'], ['Hadir', 'Terlambat']);
    if ($hadirLike && $f['jam_masuk'] === '') $errors[] = 'Jam masuk wajib diisi untuk status Hadir/Terlambat.';
    if ($f['jam_masuk'] !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $f['jam_masuk'])) $errors[] = 'Format jam masuk tidak valid.';
    if ($f['jam_pulang'] !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $f['jam_pulang'])) $errors[] = 'Format jam pulang tidak valid.';
    if ($hadirLike && $f['jam_masuk'] !== '' && $f['jam_pulang'] !== '' && $f['jam_pulang'] < $f['jam_masuk']) $errors[] = 'Jam pulang tidak boleh lebih awal dari jam masuk.';

    if (!$errors) {
        $jm = ($hadirLike && $f['jam_masuk'] !== '') ? $f['jam_masuk'] : null;
        $jp = ($hadirLike && $f['jam_pulang'] !== '') ? $f['jam_pulang'] : null;
        if ($editId) {
            q("UPDATE absensi SET id_siswa=?, tanggal=?, jam_masuk=?, jam_pulang=?, status_hadir=? WHERE id_absensi=?",
              [$f['id_siswa'], $f['tanggal'], $jm, $jp, $f['status_hadir'], $editId]);
            flash('Absensi diperbarui.');
        } else {
            q("INSERT INTO absensi (id_siswa, tanggal, jam_masuk, jam_pulang, status_hadir) VALUES (?,?,?,?,?)",
              [$f['id_siswa'], $f['tanggal'], $jm, $jp, $f['status_hadir']]);
            flash('Absensi berhasil disimpan.');
        }
        redirect('absensi.php');
    }
    $act = $editId ? 'edit' : 'add';
    $id = $editId;
}

/* ---------- Form ---------- */
if ($act === 'add' || ($act === 'edit' && $canEdit)) {
    if ($act === 'edit' && !$_POST) {
        $a = one("SELECT * FROM absensi WHERE id_absensi = ?", [$id]);
        if (!$a || !can_access_siswa($a['id_siswa'])) { flash('Data tidak ditemukan.', 'err'); redirect('absensi.php'); }
        $f = ['id_siswa' => $a['id_siswa'], 'tanggal' => $a['tanggal'], 'status_hadir' => $a['status_hadir'],
              'jam_masuk' => $a['jam_masuk'] ? substr($a['jam_masuk'], 0, 5) : '', 'jam_pulang' => $a['jam_pulang'] ? substr($a['jam_pulang'], 0, 5) : ''];
    }
    page_start($act === 'edit' ? 'Edit Absensi' : 'Isi Absensi', 'absensi.php');
    show_errors($errors);
    ?>
    <form method="post" class="form">
        <input type="hidden" name="id" value="<?= $act === 'edit' ? $id : 0 ?>">
        <?php if ($isSiswa): ?>
            <div class="row"><label>Siswa</label><b><?= e($u['name']) ?></b></div>
        <?php else: ?>
            <div class="row"><label>Siswa</label><select name="id_siswa"><option value="">- pilih siswa -</option><?= siswa_options($f['id_siswa']) ?></select></div>
        <?php endif; ?>
        <div class="row"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($f['tanggal']) ?>"></div>
        <div class="row"><label>Status</label>
            <select name="status_hadir"><option value="">- pilih status -</option>
                <?php foreach ($statusList as $st): ?><option<?= $f['status_hadir'] === $st ? ' selected' : '' ?>><?= $st ?></option><?php endforeach; ?>
            </select></div>
        <div class="row"><label>Jam masuk</label><input type="time" name="jam_masuk" value="<?= e($f['jam_masuk']) ?>"></div>
        <div class="row"><label>Jam pulang</label><input type="time" name="jam_pulang" value="<?= e($f['jam_pulang']) ?>">
            <div class="hint">Jam hanya dipakai untuk status Hadir atau Terlambat.</div></div>
        <div class="btns"><button class="btn">Simpan</button> <a class="btn btn-gray" href="absensi.php">Batal</a></div>
    </form>
    <?php
    page_end(); exit;
}

/* ---------- Rekap kehadiran per siswa ---------- */
$view = get('view') === 'rekap' ? 'rekap' : 'list';
page_start($isSiswa ? 'Absensi Saya' : 'Absensi', 'absensi.php');
?>
<div class="top">
    <div class="tabs" style="margin:0">
        <a href="absensi.php" class="<?= $view === 'list' ? 'on' : '' ?>">Riwayat</a><a href="absensi.php?view=rekap" class="<?= $view === 'rekap' ? 'on' : '' ?>">Rekap Kehadiran</a>
    </div>
    <a class="btn" href="absensi.php?act=add">+ Isi Absensi</a>
</div>

<?php if ($view === 'rekap'):
    $rows = siswa_stats('1=1');
    ?>
    <p>Persentase kehadiran = (Hadir + Terlambat) / total hari tercatat. Kategori: 90% ke atas Sangat Baik, 80-89% Baik, 70-79% Cukup, di bawah 70% Kurang.</p>
    <div class="tablewrap">
    <table>
        <tr><th>Siswa</th><th>Kelas</th><th class="num">Hadir</th><th class="num">Terlambat</th><th class="num">Izin</th><th class="num">Sakit</th><th class="num">Alpa</th><th class="num">Total</th><th class="num">Persentase</th><th>Kategori</th></tr>
        <?php foreach ($rows as $r): $pc = pct($r['hadir'], $r['total']); ?>
            <tr>
                <td><?= e($r['nama_siswa']) ?></td><td><?= e($r['kelas']) ?></td>
                <td class="num"><?= $r['hadir'] - $r['terlambat'] ?></td><td class="num"><?= $r['terlambat'] ?></td>
                <td class="num"><?= $r['izin'] ?></td><td class="num"><?= $r['sakit'] ?></td><td class="num"><?= $r['alpa'] ?></td>
                <td class="num"><?= $r['total'] ?></td>
                <td class="num"><?= $pc === null ? '-' : $pc . '%' ?></td>
                <td><?= $pc === null ? '-' : badge(kategori($pc)) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="10" class="empty">Belum ada data.</td></tr><?php endif; ?>
    </table>
    </div>
<?php else:
    $fsw = get('id_siswa'); $fst = get('status'); $dari = get('dari'); $sampai = get('sampai');
    $p = []; $sc = scope($p);
    $where = [$sc]; $par = $p;
    if ($fsw !== '' && !$isSiswa) { $where[] = 'a.id_siswa = ?'; $par[] = $fsw; }
    if ($fst !== '') { $where[] = 'a.status_hadir = ?'; $par[] = $fst; }
    if ($dari !== '') { $where[] = 'a.tanggal >= ?'; $par[] = $dari; }
    if ($sampai !== '') { $where[] = 'a.tanggal <= ?'; $par[] = $sampai; }
    $rows = all("SELECT a.*, s.nama_siswa, s.kelas FROM absensi a JOIN siswa s ON s.id_siswa = a.id_siswa
                 WHERE " . implode(' AND ', $where) . " ORDER BY a.tanggal DESC, s.nama_siswa", $par);
    ?>
    <form method="get" class="filter">
        <?php if (!$isSiswa): ?>
        <div><label>Siswa</label><select name="id_siswa"><option value="">Semua</option><?= siswa_options($fsw) ?></select></div>
        <?php endif; ?>
        <div><label>Status</label><select name="status"><option value="">Semua</option>
            <?php foreach ($statusList as $st): ?><option<?= $fst === $st ? ' selected' : '' ?>><?= $st ?></option><?php endforeach; ?>
        </select></div>
        <div><label>Dari</label><input type="date" name="dari" value="<?= e($dari) ?>"></div>
        <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>"></div>
        <div><button class="btn">Cari</button> <a class="btn btn-gray" href="absensi.php">Reset</a></div>
    </form>
    <div class="tablewrap">
    <table>
        <tr><th>Tanggal</th><?php if (!$isSiswa): ?><th>Siswa</th><th>Kelas</th><?php endif; ?><th>Jam Masuk</th><th>Jam Pulang</th><th>Status</th><?php if ($canEdit): ?><th>Aksi</th><?php endif; ?></tr>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= tgl($r['tanggal']) ?></td>
                <?php if (!$isSiswa): ?><td><?= e($r['nama_siswa']) ?></td><td><?= e($r['kelas']) ?></td><?php endif; ?>
                <td><?= $r['jam_masuk'] ? substr($r['jam_masuk'], 0, 5) : '-' ?></td>
                <td><?= $r['jam_pulang'] ? substr($r['jam_pulang'], 0, 5) : '-' ?></td>
                <td><?= badge($r['status_hadir']) ?></td>
                <?php if ($canEdit): ?>
                <td class="act">
                    <a class="btn btn-sm btn-gray" href="absensi.php?act=edit&id=<?= $r['id_absensi'] ?>">Edit</a>
                    <?= delete_form('absensi.php', $r['id_absensi'], 'Hapus', 'Hapus absensi ' . $r['nama_siswa'] . ' tanggal ' . tgl($r['tanggal']) . '?') ?>
                </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="empty">Belum ada data absensi.</td></tr><?php endif; ?>
    </table>
    </div>
<?php endif; ?>
<?php page_end(); ?>
