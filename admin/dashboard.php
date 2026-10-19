<?php
require __DIR__ . '/../config/config.php';
require_role(['admin', 'pembimbing', 'perusahaan']);
$u = me();

page_start('Dashboard', 'dashboard.php');

/* ================= Dashboard admin / pembimbing / perusahaan ================= */
$p = []; $sc = scope($p);

$totalSiswa = val("SELECT COUNT(*) FROM siswa s WHERE $sc", $p);
$aktif      = val("SELECT COUNT(*) FROM siswa s WHERE $sc AND s.id_perusahaan IS NOT NULL AND s.id_pembimbing IS NOT NULL", $p);
$belum      = val("SELECT COUNT(*) FROM siswa s WHERE $sc AND s.id_perusahaan IS NULL AND s.id_pembimbing IS NULL", $p);
if ($u['role'] === 'admin') {
    $jmlPerusahaan = val("SELECT COUNT(*) FROM perusahaan");
    $jmlPembimbing = val("SELECT COUNT(*) FROM pembimbing");
} else {
    $jmlPerusahaan = val("SELECT COUNT(DISTINCT s.id_perusahaan) FROM siswa s WHERE $sc", $p);
    $jmlPembimbing = val("SELECT COUNT(DISTINCT s.id_pembimbing) FROM siswa s WHERE $sc", $p);
}
$jurnalHariIni = val("SELECT COUNT(*) FROM jurnal j JOIN siswa s ON s.id_siswa = j.id_siswa WHERE $sc AND j.tanggal = CURDATE()", $p);
$jurnalPending = val("SELECT COUNT(*) FROM jurnal j JOIN siswa s ON s.id_siswa = j.id_siswa WHERE $sc AND j.status_validasi = 'Pending'", $p);

$perhatian = [];
foreach (siswa_stats() as $r) {
    $a = alasan_perhatian($r);
    if ($a) $perhatian[] = [$r, $a];
}

$pending = all("SELECT j.*, s.nama_siswa, s.kelas FROM jurnal j JOIN siswa s ON s.id_siswa = j.id_siswa
                WHERE $sc AND j.status_validasi = 'Pending' ORDER BY j.tanggal DESC LIMIT 6", $p);
?>
<div class="stats">
    <div class="stat"><div class="n"><?= $totalSiswa ?></div><div class="l">Total Siswa PKL</div></div>
    <div class="stat"><div class="n"><?= $jmlPerusahaan ?></div><div class="l">Perusahaan</div></div>
    <div class="stat"><div class="n"><?= $jmlPembimbing ?></div><div class="l">Pembimbing</div></div>
    <div class="stat"><div class="n"><?= $aktif ?></div><div class="l">PKL Aktif</div></div>
    <div class="stat y"><div class="n"><?= $belum ?></div><div class="l">Belum Ditempatkan</div></div>
</div>
<div class="stats">
    <div class="stat"><div class="n"><?= $jurnalHariIni ?></div><div class="l">Jurnal Hari Ini (<?= date('d-m-Y') ?>)</div></div>
    <div class="stat y"><div class="n"><?= $jurnalPending ?></div><div class="l">Jurnal Menunggu Validasi</div></div>
    <div class="stat warn"><div class="n"><?= count($perhatian) ?></div><div class="l">Siswa Perlu Perhatian</div></div>
</div>

<h2>Jurnal menunggu validasi</h2>
<table>
    <tr><th>Tanggal</th><th>Siswa</th><th>Kegiatan</th><th></th></tr>
    <?php foreach ($pending as $j): ?>
        <tr>
            <td><?= tgl($j['tanggal']) ?></td>
            <td><?= e($j['nama_siswa']) ?><br><small><?= e($j['kelas']) ?></small></td>
            <td><?= e($j['kegiatan']) ?></td>
            <td class="act"><a class="btn btn-sm btn-gray" href="jurnal.php?status=Pending">Validasi</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$pending): ?><tr><td colspan="4" class="empty">Tidak ada jurnal yang menunggu.</td></tr><?php endif; ?>
</table>

<h2>Siswa perlu perhatian</h2>
<table>
    <tr><th>Nama</th><th>Kelas</th><th>Perusahaan</th><th>Alasan</th></tr>
    <?php foreach ($perhatian as [$r, $a]): ?>
        <tr>
            <td><a href="siswa.php?act=detail&id=<?= $r['id_siswa'] ?>"><?= e($r['nama_siswa']) ?></a></td>
            <td><?= e($r['kelas']) ?></td>
            <td><?= e($r['nama_perusahaan'] ?: '-') ?></td>
            <td><?= e(implode(', ', $a)) ?></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$perhatian): ?><tr><td colspan="4" class="empty">Semua siswa aman.</td></tr><?php endif; ?>
</table>
<?php page_end(); ?>
