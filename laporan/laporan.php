<?php
require __DIR__ . '/../config/config.php';
require_role(['admin', 'pembimbing', 'perusahaan']);
$r = get('r');
if (!in_array($r, ['siswa', 'kehadiran', 'monitoring'])) $r = 'siswa';
$fk = get('kelas'); $fp = get('id_perusahaan'); $dari = get('dari'); $sampai = get('sampai');

$kelasList = all("SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL ORDER BY kelas");
$perusahaanList = all("SELECT id_perusahaan, nama_perusahaan FROM perusahaan ORDER BY nama_perusahaan");

$where = ['1=1']; $par = [];
if ($fk !== '') { $where[] = 's.kelas = ?'; $par[] = $fk; }
if ($fp !== '' && me()['role'] !== 'perusahaan') { $where[] = 's.id_perusahaan = ?'; $par[] = $fp; }

$judul = ['siswa' => 'Laporan Data Siswa PKL', 'kehadiran' => 'Laporan Kehadiran', 'monitoring' => 'Laporan Monitoring'][$r];
page_start($judul, 'laporan.php');
?>
<div class="tabs">
    <a href="laporan.php?r=siswa" class="<?= $r === 'siswa' ? 'on' : '' ?>">Data Siswa PKL</a><a href="laporan.php?r=kehadiran" class="<?= $r === 'kehadiran' ? 'on' : '' ?>">Kehadiran</a><a href="laporan.php?r=monitoring" class="<?= $r === 'monitoring' ? 'on' : '' ?>">Monitoring</a>
</div>
<form method="get" class="filter">
    <input type="hidden" name="r" value="<?= e($r) ?>">
    <div><label>Kelas</label><select name="kelas"><option value="">Semua</option><?= options($kelasList, 'kelas', 'kelas', $fk) ?></select></div>
    <?php if (me()['role'] !== 'perusahaan'): ?>
    <div><label>Perusahaan</label><select name="id_perusahaan"><option value="">Semua</option><?= options($perusahaanList, 'id_perusahaan', 'nama_perusahaan', $fp) ?></select></div>
    <?php endif; ?>
    <?php if ($r === 'monitoring'): ?>
    <div><label>Dari</label><input type="date" name="dari" value="<?= e($dari) ?>"></div>
    <div><label>Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>"></div>
    <?php endif; ?>
    <div><button class="btn">Tampilkan</button> <a class="btn btn-gray" href="laporan.php?r=<?= e($r) ?>">Reset</a></div>
</form>
<p class="noprint"><button class="btn btn-gray" onclick="window.print()">Cetak laporan</button></p>
<p>Dicetak: <?= date('d-m-Y H:i') ?> oleh <?= e(me()['name']) ?></p>

<?php if ($r === 'siswa'):
    $rows = siswa_stats(implode(' AND ', $where), $par); ?>
    <table>
        <tr><th>No</th><th>NISN</th><th>Nama</th><th>Kelas</th><th>Jurusan</th><th>Perusahaan</th><th>Pembimbing</th><th>Status PKL</th><th class="num">Jurnal</th><th class="num">Kehadiran</th></tr>
        <?php foreach ($rows as $i => $s): $pc = pct($s['hadir'], $s['total']); ?>
            <tr>
                <td><?= $i + 1 ?></td><td><?= e($s['nisn']) ?></td><td><?= e($s['nama_siswa']) ?></td><td><?= e($s['kelas']) ?></td>
                <td><?= e(jurusan($s['kelas'])) ?></td><td><?= e($s['nama_perusahaan'] ?: '-') ?></td><td><?= e($s['nama_pembimbing'] ?: '-') ?></td>
                <td><?= e(status_pkl($s)) ?></td><td class="num"><?= $s['jml_jurnal'] ?></td><td class="num"><?= $pc === null ? '-' : $pc . '%' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="10" class="empty">Tidak ada data.</td></tr><?php endif; ?>
    </table>
    <p>Total: <?= count($rows) ?> siswa</p>

<?php elseif ($r === 'kehadiran'):
    $rows = siswa_stats(implode(' AND ', $where), $par); ?>
    <table>
        <tr><th>No</th><th>Nama</th><th>Kelas</th><th>Perusahaan</th><th class="num">Hadir</th><th class="num">Terlambat</th><th class="num">Izin</th><th class="num">Sakit</th><th class="num">Alpa</th><th class="num">Total Hari</th><th class="num">Persentase</th><th>Kategori</th></tr>
        <?php foreach ($rows as $i => $s): $pc = pct($s['hadir'], $s['total']); ?>
            <tr>
                <td><?= $i + 1 ?></td><td><?= e($s['nama_siswa']) ?></td><td><?= e($s['kelas']) ?></td><td><?= e($s['nama_perusahaan'] ?: '-') ?></td>
                <td class="num"><?= $s['hadir'] - $s['terlambat'] ?></td><td class="num"><?= $s['terlambat'] ?></td><td class="num"><?= $s['izin'] ?></td>
                <td class="num"><?= $s['sakit'] ?></td><td class="num"><?= $s['alpa'] ?></td><td class="num"><?= $s['total'] ?></td>
                <td class="num"><?= $pc === null ? '-' : $pc . '%' ?></td><td><?= e(kategori($pc)) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="12" class="empty">Tidak ada data.</td></tr><?php endif; ?>
    </table>
    <p>Persentase = (Hadir + Terlambat) / Total Hari. Sangat Baik &ge; 90%, Baik &ge; 80%, Cukup &ge; 70%, selain itu Kurang.</p>

<?php else:
    $p2 = []; $sc = scope($p2);
    $w = array_merge([$sc], $where); $pp = array_merge($p2, $par);
    if ($dari !== '') { $w[] = 'm.tanggal_kunjungan >= ?'; $pp[] = $dari; }
    if ($sampai !== '') { $w[] = 'm.tanggal_kunjungan <= ?'; $pp[] = $sampai; }
    $rows = all("SELECT m.*, s.nama_siswa, s.kelas, pr.nama_perusahaan, b.nama_pembimbing
                 FROM monitoring m JOIN siswa s ON s.id_siswa = m.id_siswa
                 JOIN pembimbing b ON b.id_pembimbing = m.id_pembimbing
                 LEFT JOIN perusahaan pr ON pr.id_perusahaan = s.id_perusahaan
                 WHERE " . implode(' AND ', $w) . " ORDER BY m.tanggal_kunjungan DESC", $pp); ?>
    <table>
        <tr><th>No</th><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Perusahaan</th><th>Pembimbing</th><th>Catatan</th></tr>
        <?php foreach ($rows as $i => $m): ?>
            <tr>
                <td><?= $i + 1 ?></td><td><?= tgl($m['tanggal_kunjungan']) ?></td><td><?= e($m['nama_siswa']) ?></td><td><?= e($m['kelas']) ?></td>
                <td><?= e($m['nama_perusahaan'] ?: '-') ?></td><td><?= e($m['nama_pembimbing']) ?></td><td><?= e($m['catatan_feedback']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="7" class="empty">Tidak ada data.</td></tr><?php endif; ?>
    </table>
    <p>Total: <?= count($rows) ?> kunjungan monitoring</p>
<?php endif; ?>
<?php page_end(); ?>
