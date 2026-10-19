<?php
require __DIR__ . '/../config/config.php';
require_role(['siswa']);
$u = me();

page_start('Dashboard', 'dashboard.php');

/* ================= Dashboard siswa ================= */
{
    $rows = siswa_stats();
    $s = $rows[0] ?? null;
    if (!$s) { echo '<p>Data siswa untuk akun ini tidak ditemukan.</p>'; page_end(); exit; }
    $p = pct($s['hadir'], $s['total']);
    $last = one("SELECT m.*, b.nama_pembimbing FROM monitoring m JOIN pembimbing b ON b.id_pembimbing = m.id_pembimbing WHERE m.id_siswa = ? ORDER BY m.tanggal_kunjungan DESC LIMIT 1", [$s['id_siswa']]);
    ?>
    <div class="stats">
        <div class="stat"><div class="n"><?= $s['jml_jurnal'] ?></div><div class="l">Jurnal yang sudah diisi</div></div>
        <div class="stat"><div class="n"><?= $s['total'] ?></div><div class="l">Hari tercatat di absensi</div></div>
        <div class="stat y"><div class="n"><?= $p === null ? '-' : $p . '%' ?></div><div class="l">Persentase kehadiran (<?= kategori($p) ?>)</div></div>
        <div class="stat"><div class="n"><?= $s['jml_monitoring'] ?></div><div class="l">Kunjungan monitoring</div></div>
    </div>
    <div class="dl">
        <div><span>NISN</span><span><?= e($s['nisn']) ?></span></div>
        <div><span>Nama</span><span><?= e($s['nama_siswa']) ?></span></div>
        <div><span>Kelas</span><span><?= e($s['kelas']) ?></span></div>
        <div><span>Tempat PKL</span><span><?= e($s['nama_perusahaan'] ?: 'Belum ditempatkan') ?></span></div>
        <div><span>Guru Pembimbing</span><span><?= e($s['nama_pembimbing'] ?: '-') ?></span></div>
        <div><span>Status PKL</span><span><?= badge(status_pkl($s)) ?></span></div>
    </div>
    <?php if ($last): ?>
        <h2>Catatan monitoring terakhir</h2>
        <div class="dl"><div><span>Tanggal</span><span><?= tgl($last['tanggal_kunjungan']) ?></span></div>
        <div><span>Pembimbing</span><span><?= e($last['nama_pembimbing']) ?></span></div>
        <div><span>Catatan</span><span><?= nl2br(e($last['catatan_feedback'])) ?></span></div></div>
    <?php endif; ?>
    <p><a class="btn" href="jurnal.php?act=add">Isi jurnal hari ini</a> <a class="btn btn-gray" href="absensi.php?act=add">Isi absensi</a></p>
    <?php
    page_end();
    exit;
}

