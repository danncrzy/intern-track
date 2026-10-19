<?php
require __DIR__ . '/../config/config.php';
require_login();
$u = me();
$role = $u['role'];
$canInput = in_array($role, ['admin', 'pembimbing']);
$act = get('act') ?: 'list';
$id = (int)get('id');
$errors = [];
$f = ['id_siswa' => '', 'tanggal_kunjungan' => date('Y-m-d'), 'catatan_feedback' => ''];

// pembimbing hanya boleh mengubah catatan miliknya sendiri
function boleh_ubah($m) {
    $u = me();
    if ($u['role'] === 'admin') return true;
    return $u['role'] === 'pembimbing' && (int)$m['id_pembimbing'] === (int)$u['ref'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$canInput) { flash('Hanya guru pembimbing yang mengisi monitoring.', 'err'); redirect('monitoring.php'); }

    if (post('do') === 'delete') {
        $m = one("SELECT * FROM monitoring WHERE id_monitoring = ?", [(int)post('id')]);
        if ($m && boleh_ubah($m)) { q("DELETE FROM monitoring WHERE id_monitoring = ?", [$m['id_monitoring']]); flash('Catatan monitoring dihapus.'); }
        else flash('Aksi tidak diizinkan.', 'err');
        redirect('monitoring.php');
    }

    $editId = (int)post('id');
    foreach ($f as $k => $v) $f[$k] = post($k);

    $old = null;
    if ($editId) {
        $old = one("SELECT * FROM monitoring WHERE id_monitoring = ?", [$editId]);
        if (!$old || !boleh_ubah($old)) { flash('Aksi tidak diizinkan.', 'err'); redirect('monitoring.php'); }
    }

    $siswa = null;
    if ($f['id_siswa'] === '') $errors[] = 'Siswa wajib dipilih.';
    elseif (!can_access_siswa($f['id_siswa'])) $errors[] = 'Siswa tidak valid.';
    else $siswa = one("SELECT id_pembimbing FROM siswa WHERE id_siswa = ?", [$f['id_siswa']]);

    if ($f['tanggal_kunjungan'] === '') $errors[] = 'Tanggal monitoring wajib diisi.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['tanggal_kunjungan']) || !strtotime($f['tanggal_kunjungan'])) $errors[] = 'Format tanggal tidak valid.';
    elseif ($f['tanggal_kunjungan'] > date('Y-m-d')) $errors[] = 'Tanggal monitoring tidak boleh lebih dari hari ini.';
    if ($f['catatan_feedback'] === '') $errors[] = 'Catatan wajib diisi.';
    elseif (strlen($f['catatan_feedback']) < 10) $errors[] = 'Catatan terlalu singkat, minimal 10 karakter.';

    // pembimbing yang tercatat: diri sendiri, atau pembimbing siswa jika diinput admin
    $idPemb = $role === 'pembimbing' ? $u['ref'] : ($old['id_pembimbing'] ?? ($siswa['id_pembimbing'] ?? null));
    if (!$errors && !$idPemb) $errors[] = 'Siswa ini belum punya pembimbing. Atur dulu di menu Penempatan PKL.';

    if (!$errors) {
        if ($editId) {
            q("UPDATE monitoring SET id_siswa=?, tanggal_kunjungan=?, catatan_feedback=? WHERE id_monitoring=?",
              [$f['id_siswa'], $f['tanggal_kunjungan'], $f['catatan_feedback'], $editId]);
            flash('Catatan monitoring diperbarui.');
        } else {
            q("INSERT INTO monitoring (id_pembimbing, id_siswa, tanggal_kunjungan, catatan_feedback) VALUES (?,?,?,?)",
              [$idPemb, $f['id_siswa'], $f['tanggal_kunjungan'], $f['catatan_feedback']]);
            flash('Catatan monitoring berhasil disimpan.');
        }
        redirect('monitoring.php');
    }
    $act = $editId ? 'edit' : 'add';
    $id = $editId;
}

/* ---------- Form ---------- */
if ($canInput && ($act === 'add' || $act === 'edit')) {
    if ($act === 'edit' && !$_POST) {
        $m = one("SELECT * FROM monitoring WHERE id_monitoring = ?", [$id]);
        if (!$m || !boleh_ubah($m)) { flash('Data tidak ditemukan.', 'err'); redirect('monitoring.php'); }
        $f = ['id_siswa' => $m['id_siswa'], 'tanggal_kunjungan' => $m['tanggal_kunjungan'], 'catatan_feedback' => $m['catatan_feedback']];
    }
    page_start($act === 'edit' ? 'Edit Monitoring' : 'Tambah Monitoring', 'monitoring.php');
    show_errors($errors);
    ?>
    <form method="post" class="form">
        <input type="hidden" name="id" value="<?= $act === 'edit' ? $id : 0 ?>">
        <div class="row"><label>Siswa</label><select name="id_siswa"><option value="">- pilih siswa -</option><?= siswa_options($f['id_siswa']) ?></select></div>
        <div class="row"><label>Tanggal monitoring</label><input type="date" name="tanggal_kunjungan" value="<?= e($f['tanggal_kunjungan']) ?>"></div>
        <div class="row"><label>Catatan / feedback</label><textarea name="catatan_feedback" placeholder="Hasil pengamatan, saran, dan rekomendasi untuk siswa."><?= e($f['catatan_feedback']) ?></textarea>
            <div class="hint">Tulis catatan, penilaian, dan rekomendasi dalam satu kolom.</div></div>
        <div class="btns"><button class="btn">Simpan</button> <a class="btn btn-gray" href="monitoring.php">Batal</a></div>
    </form>
    <?php
    page_end(); exit;
}

/* ---------- Daftar ---------- */
$qs = get('q'); $fsw = get('id_siswa'); $dari = get('dari'); $sampai = get('sampai');
$p = []; $sc = scope($p);
$where = [$sc]; $par = $p;
if ($qs !== '') { $where[] = '(m.catatan_feedback LIKE ? OR s.nama_siswa LIKE ?)'; $par[] = "%$qs%"; $par[] = "%$qs%"; }
if ($fsw !== '' && $role !== 'siswa') { $where[] = 'm.id_siswa = ?'; $par[] = $fsw; }
if ($dari !== '') { $where[] = 'm.tanggal_kunjungan >= ?'; $par[] = $dari; }
if ($sampai !== '') { $where[] = 'm.tanggal_kunjungan <= ?'; $par[] = $sampai; }
$rows = all("SELECT m.*, s.nama_siswa, s.kelas, b.nama_pembimbing
             FROM monitoring m JOIN siswa s ON s.id_siswa = m.id_siswa JOIN pembimbing b ON b.id_pembimbing = m.id_pembimbing
             WHERE " . implode(' AND ', $where) . " ORDER BY m.tanggal_kunjungan DESC, s.nama_siswa", $par);

page_start('Monitoring Pembimbing', 'monitoring.php');
?>
<div class="top">
    <span><?= count($rows) ?> catatan</span>
    <?php if ($canInput): ?><a class="btn" href="monitoring.php?act=add">+ Tambah Monitoring</a><?php endif; ?>
</div>
<form method="get" class="filter">
    <div><label>Cari catatan / nama</label><input type="text" name="q" value="<?= e($qs) ?>"></div>
    <?php if ($role !== 'siswa'): ?>
    <div><label>Siswa</label><select name="id_siswa"><option value="">Semua</option><?= siswa_options($fsw) ?></select></div>
    <?php endif; ?>
    <div><label>Dari</label><input type="date" name="dari" value="<?= e($dari) ?>"></div>
    <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>"></div>
    <div><button class="btn">Cari</button> <a class="btn btn-gray" href="monitoring.php">Reset</a></div>
</form>
<div class="tablewrap">
<table>
    <tr><th width="90">Tanggal</th><th>Siswa</th><th>Pembimbing</th><th>Catatan</th><?php if ($canInput): ?><th>Aksi</th><?php endif; ?></tr>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?= tgl($r['tanggal_kunjungan']) ?></td>
            <td><?= e($r['nama_siswa']) ?><br><small><?= e($r['kelas']) ?></small></td>
            <td><?= e($r['nama_pembimbing']) ?></td>
            <td><?= nl2br(e($r['catatan_feedback'])) ?></td>
            <?php if ($canInput): ?>
            <td class="act">
                <?php if (boleh_ubah($r)): ?>
                    <a class="btn btn-sm btn-gray" href="monitoring.php?act=edit&id=<?= $r['id_monitoring'] ?>">Edit</a>
                    <?= delete_form('monitoring.php', $r['id_monitoring'], 'Hapus', 'Hapus catatan monitoring ' . $r['nama_siswa'] . '?') ?>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="5" class="empty">Belum ada catatan monitoring.</td></tr><?php endif; ?>
</table>
</div>
<?php page_end(); ?>
