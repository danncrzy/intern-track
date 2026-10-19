<?php
require __DIR__ . '/../config/config.php';
require_login();
$u = me();
$role = $u['role'];
$isSiswa = $role === 'siswa';
$canValidate = in_array($role, ['admin', 'pembimbing', 'perusahaan']);
$act = get('act') ?: 'list';
$id = (int)get('id');
$errors = [];
$f = ['tanggal' => date('Y-m-d'), 'kegiatan' => ''];

/* ---------- POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = post('do');

    // validasi oleh pembimbing / perusahaan / admin
    if ($do === 'status') {
        $j = one("SELECT id_siswa FROM jurnal WHERE id_jurnal = ?", [(int)post('id')]);
        $st = post('status');
        if (!$canValidate || !$j || !can_access_siswa($j['id_siswa']) || !in_array($st, ['Disetujui', 'Revisi', 'Pending'])) {
            flash('Aksi tidak diizinkan.', 'err');
        } else {
            q("UPDATE jurnal SET status_validasi = ? WHERE id_jurnal = ?", [$st, (int)post('id')]);
            flash('Status jurnal diubah menjadi ' . $st . '.');
        }
        redirect('jurnal.php' . (post('back') ? '?' . post('back') : ''));
    }

    if ($do === 'delete') {
        $j = one("SELECT * FROM jurnal WHERE id_jurnal = ?", [(int)post('id')]);
        $boleh = $j && can_access_siswa($j['id_siswa']) && ($role === 'admin' || ($isSiswa && $j['status_validasi'] !== 'Disetujui'));
        if ($boleh) { q("DELETE FROM jurnal WHERE id_jurnal = ?", [$j['id_jurnal']]); flash('Jurnal dihapus.'); }
        else flash('Jurnal ini tidak bisa dihapus.', 'err');
        redirect('jurnal.php');
    }

    // tambah / edit (siswa mengisi jurnalnya sendiri)
    if ($do === 'save') {
        if (!$isSiswa) { flash('Jurnal diisi oleh siswa.', 'err'); redirect('jurnal.php'); }
        $editId = (int)post('id');
        $f['tanggal'] = post('tanggal');
        $f['kegiatan'] = post('kegiatan');

        if ($editId) {
            $old = one("SELECT * FROM jurnal WHERE id_jurnal = ? AND id_siswa = ?", [$editId, $u['ref']]);
            if (!$old || $old['status_validasi'] === 'Disetujui') { flash('Jurnal ini tidak bisa diubah.', 'err'); redirect('jurnal.php'); }
        }
        if ($f['tanggal'] === '') $errors[] = 'Tanggal wajib diisi.';
        elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['tanggal']) || !strtotime($f['tanggal'])) $errors[] = 'Format tanggal tidak valid.';
        elseif ($f['tanggal'] > date('Y-m-d')) $errors[] = 'Tanggal tidak boleh lebih dari hari ini.';
        elseif (val("SELECT COUNT(*) FROM jurnal WHERE id_siswa = ? AND tanggal = ? AND id_jurnal <> ?", [$u['ref'], $f['tanggal'], $editId]))
            $errors[] = 'Jurnal untuk tanggal ini sudah ada. Edit jurnal yang lama saja.';
        if ($f['kegiatan'] === '') $errors[] = 'Kegiatan wajib diisi.';
        elseif (strlen($f['kegiatan']) < 10) $errors[] = 'Kegiatan terlalu singkat, jelaskan minimal 10 karakter.';

        if (!$errors) {
            if ($editId) {
                // setelah direvisi siswa, status kembali menunggu validasi
                q("UPDATE jurnal SET tanggal = ?, kegiatan = ?, status_validasi = 'Pending' WHERE id_jurnal = ?", [$f['tanggal'], $f['kegiatan'], $editId]);
                flash('Jurnal diperbarui dan menunggu validasi ulang.');
            } else {
                q("INSERT INTO jurnal (id_siswa, tanggal, kegiatan) VALUES (?,?,?)", [$u['ref'], $f['tanggal'], $f['kegiatan']]);
                flash('Jurnal berhasil disimpan.');
            }
            redirect('jurnal.php');
        }
        $act = $editId ? 'edit' : 'add';
        $id = $editId;
    }
}

/* ---------- Form (siswa) ---------- */
if ($isSiswa && ($act === 'add' || $act === 'edit')) {
    if ($act === 'edit' && !$_POST) {
        $old = one("SELECT * FROM jurnal WHERE id_jurnal = ? AND id_siswa = ?", [$id, $u['ref']]);
        if (!$old || $old['status_validasi'] === 'Disetujui') { flash('Jurnal ini tidak bisa diubah.', 'err'); redirect('jurnal.php'); }
        $f = ['tanggal' => $old['tanggal'], 'kegiatan' => $old['kegiatan']];
    }
    page_start($act === 'edit' ? 'Edit Jurnal' : 'Isi Jurnal Kegiatan', 'jurnal.php');
    show_errors($errors);
    ?>
    <form method="post" class="form">
        <input type="hidden" name="do" value="save">
        <input type="hidden" name="id" value="<?= $act === 'edit' ? $id : 0 ?>">
        <div class="row"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($f['tanggal']) ?>"></div>
        <div class="row"><label>Kegiatan</label><textarea name="kegiatan" placeholder="Apa yang dikerjakan hari ini, hasilnya, dan kendala jika ada."><?= e($f['kegiatan']) ?></textarea>
            <div class="hint">Tulis kegiatan, hasil, dan kendala dalam satu catatan.</div></div>
        <div class="btns"><button class="btn">Simpan</button> <a class="btn btn-gray" href="jurnal.php">Batal</a></div>
    </form>
    <?php
    page_end(); exit;
}

/* ---------- Daftar ---------- */
$qs = get('q'); $fsw = get('id_siswa'); $fst = get('status'); $dari = get('dari'); $sampai = get('sampai');
$p = []; $sc = scope($p);
$where = [$sc]; $par = $p;
if ($qs !== '') { $where[] = '(j.kegiatan LIKE ? OR s.nama_siswa LIKE ?)'; $par[] = "%$qs%"; $par[] = "%$qs%"; }
if ($fsw !== '' && !$isSiswa) { $where[] = 'j.id_siswa = ?'; $par[] = $fsw; }
if ($fst !== '') { $where[] = 'j.status_validasi = ?'; $par[] = $fst; }
if ($dari !== '') { $where[] = 'j.tanggal >= ?'; $par[] = $dari; }
if ($sampai !== '') { $where[] = 'j.tanggal <= ?'; $par[] = $sampai; }
$rows = all("SELECT j.*, s.nama_siswa, s.kelas, pr.nama_perusahaan
             FROM jurnal j JOIN siswa s ON s.id_siswa = j.id_siswa
             LEFT JOIN perusahaan pr ON pr.id_perusahaan = s.id_perusahaan
             WHERE " . implode(' AND ', $where) . " ORDER BY j.tanggal DESC, s.nama_siswa", $par);
$back = $_SERVER['QUERY_STRING'] ?? '';

page_start($isSiswa ? 'Jurnal Saya' : 'Jurnal Kegiatan', 'jurnal.php');
?>
<div class="top">
    <span><?= count($rows) ?> jurnal</span>
    <?php if ($isSiswa): ?><a class="btn" href="jurnal.php?act=add">+ Isi Jurnal</a><?php endif; ?>
</div>
<form method="get" class="filter">
    <div><label>Cari kegiatan<?= $isSiswa ? '' : ' / nama' ?></label><input type="text" name="q" value="<?= e($qs) ?>"></div>
    <?php if (!$isSiswa): ?>
    <div><label>Siswa</label><select name="id_siswa"><option value="">Semua</option><?= siswa_options($fsw) ?></select></div>
    <?php endif; ?>
    <div><label>Status</label><select name="status"><option value="">Semua</option>
        <?php foreach (['Pending', 'Disetujui', 'Revisi'] as $st): ?><option<?= $fst === $st ? ' selected' : '' ?>><?= $st ?></option><?php endforeach; ?>
    </select></div>
    <div><label>Dari</label><input type="date" name="dari" value="<?= e($dari) ?>"></div>
    <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>"></div>
    <div><button class="btn">Cari</button> <a class="btn btn-gray" href="jurnal.php">Reset</a></div>
</form>
<div class="tablewrap">
<table>
    <tr><th width="90">Tanggal</th><?php if (!$isSiswa): ?><th>Siswa</th><?php endif; ?><th>Kegiatan</th><th width="90">Status</th><th width="170">Aksi</th></tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= tgl($r['tanggal']) ?></td>
            <?php if (!$isSiswa): ?><td><?= e($r['nama_siswa']) ?><br><small><?= e($r['kelas']) ?> - <?= e($r['nama_perusahaan'] ?: '-') ?></small></td><?php endif; ?>
            <td><?= nl2br(e($r['kegiatan'])) ?></td>
            <td><?= badge($r['status_validasi']) ?></td>
            <td class="act">
                <?php if ($canValidate): ?>
                    <?php foreach (['Disetujui' => 'Setujui', 'Revisi' => 'Revisi'] as $st => $lbl): if ($r['status_validasi'] === $st) continue; ?>
                    <form method="post" class="inline">
                        <input type="hidden" name="do" value="status"><input type="hidden" name="id" value="<?= $r['id_jurnal'] ?>">
                        <input type="hidden" name="status" value="<?= $st ?>"><input type="hidden" name="back" value="<?= e($back) ?>">
                        <button class="btn btn-sm btn-gray"><?= $lbl ?></button>
                    </form>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if ($isSiswa && $r['status_validasi'] !== 'Disetujui'): ?>
                    <a class="btn btn-sm btn-gray" href="jurnal.php?act=edit&id=<?= $r['id_jurnal'] ?>">Edit</a>
                    <?= delete_form('jurnal.php', $r['id_jurnal'], 'Hapus', 'Hapus jurnal tanggal ' . tgl($r['tanggal']) . '?') ?>
                <?php elseif ($role === 'admin'): ?>
                    <?= delete_form('jurnal.php', $r['id_jurnal'], 'Hapus', 'Hapus jurnal ini?') ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="empty">Belum ada jurnal.</td></tr><?php endif; ?>
</table>
</div>
<?php page_end(); ?>
