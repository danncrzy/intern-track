CREATE TABLE users (
    id_user INT AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL,
    PRIMARY KEY (id_user)
);

CREATE TABLE perusahaan (
    id_perusahaan INT AUTO_INCREMENT,
    nama_perusahaan VARCHAR(100) NOT NULL,
    alamat TEXT,
    id_user INT UNIQUE,
    PRIMARY KEY (id_perusahaan),
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE pembimbing (
    id_pembimbing INT AUTO_INCREMENT,
    nip VARCHAR(20) UNIQUE,
    nama_pembimbing VARCHAR(100) NOT NULL,
    id_user INT UNIQUE,
    PRIMARY KEY (id_pembimbing),
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE siswa (
    id_siswa INT AUTO_INCREMENT,
    nisn VARCHAR(10) NOT NULL UNIQUE,
    nama_siswa VARCHAR(100) NOT NULL,
    kelas VARCHAR(20),
    id_user INT UNIQUE,
    id_perusahaan INT,
    id_pembimbing INT,
    PRIMARY KEY (id_siswa),
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (id_perusahaan) REFERENCES perusahaan(id_perusahaan) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (id_pembimbing) REFERENCES pembimbing(id_pembimbing) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE jurnal (
    id_jurnal INT AUTO_INCREMENT,
    id_siswa INT NOT NULL,
    tanggal DATE NOT NULL,
    kegiatan TEXT NOT NULL,
    status_validasi VARCHAR(20) DEFAULT 'Pending',
    PRIMARY KEY (id_jurnal),
    FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE absensi (
    id_absensi INT AUTO_INCREMENT,
    id_siswa INT NOT NULL,
    tanggal DATE NOT NULL,
    jam_masuk TIME,
    jam_pulang TIME,
    status_hadir VARCHAR(20) NOT NULL,
    PRIMARY KEY (id_absensi),
    FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE monitoring (
    id_monitoring INT AUTO_INCREMENT,
    id_pembimbing INT NOT NULL,
    id_siswa INT NOT NULL,
    tanggal_kunjungan DATE NOT NULL,
    catatan_feedback TEXT NOT NULL,
    PRIMARY KEY (id_monitoring),
    FOREIGN KEY (id_pembimbing) REFERENCES pembimbing(id_pembimbing) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_siswa) REFERENCES siswa(id_siswa) ON DELETE CASCADE ON UPDATE CASCADE
);


INSERT INTO users (id_user, username, password, role) VALUES
(1,  'admin',             'password123', 'admin'),
(2,  'ptmajujaya',        'password123', 'perusahaan'),
(3,  'cvkreatifmedia',    'password123', 'perusahaan'),
(4,  'ptsentosaabadi',    'password123', 'perusahaan'),
(5,  'banksejahtera',     'password123', 'perusahaan'),
(6,  'rsharapanbunda',    'password123', 'perusahaan'),
(7,  'ptniagaserbaada',   'password123', 'perusahaan'),
(8,  'hotelgrandanggrek', 'password123', 'perusahaan'),
(9,  'ptagrolestari',     'password123', 'perusahaan'),
(10, 'cvbengkelmotor',    'password123', 'perusahaan'),
(11, 'ptlogistikprima',   'password123', 'perusahaan'),
(12, 'budi.santoso',      'password123', 'pembimbing'),
(13, 'siti.rahayu',       'password123', 'pembimbing'),
(14, 'ahmad.fauzi',       'password123', 'pembimbing'),
(15, 'dewi.lestari',      'password123', 'pembimbing'),
(16, 'joko.prasetyo',     'password123', 'pembimbing'),
(17, 'rina.wulandari',    'password123', 'pembimbing'),
(18, 'agus.setiawan',     'password123', 'pembimbing'),
(19, 'sri.handayani',     'password123', 'pembimbing'),
(20, 'bambang.sutrisno',  'password123', 'pembimbing'),
(21, 'maya.anggraini',    'password123', 'pembimbing'),
(22, 'rizky.ramadhan',    'password123', 'siswa'),
(23, 'putri.andini',      'password123', 'siswa'),
(24, 'fajar.nugroho',     'password123', 'siswa'),
(25, 'nadia.salsabila',   'password123', 'siswa'),
(26, 'dimas.prakoso',     'password123', 'siswa'),
(27, 'ayu.kartika',       'password123', 'siswa'),
(28, 'rendi.saputra',     'password123', 'siswa'),
(29, 'sinta.maharani',    'password123', 'siswa'),
(30, 'bayu.setiawan',     'password123', 'siswa'),
(31, 'dinda.puspita',     'password123', 'siswa'),
(32, 'andi.kurniawan',    'password123', 'siswa'),
(33, 'mega.lestari',      'password123', 'siswa'),
(34, 'yoga.pratama',      'password123', 'siswa'),
(35, 'citra.dewi',        'password123', 'siswa'),
(36, 'ilham.maulana',     'password123', 'siswa'),
(37, 'ratna.juwita',      'password123', 'siswa'),
(38, 'fikri.akbar',       'password123', 'siswa'),
(39, 'wulan.ramadhani',   'password123', 'siswa'),
(40, 'teguh.santoso',     'password123', 'siswa'),
(41, 'anisa.fitriani',    'password123', 'siswa'),
(42, 'galih.permana',     'password123', 'siswa'),
(43, 'vina.oktaviani',    'password123', 'siswa'),
(44, 'hasan.basri',       'password123', 'siswa'),
(45, 'nur.aisyah',        'password123', 'siswa'),
(46, 'danu.wicaksono',    'password123', 'siswa');

INSERT INTO perusahaan (id_perusahaan, nama_perusahaan, alamat, id_user) VALUES
(1,  'PT Maju Jaya Teknologi',      'Jl. Jend. Sudirman No. 45, Jakarta Pusat',  2),
(2,  'CV Kreatif Media Digital',    'Jl. Asia Afrika No. 88, Bandung',           3),
(3,  'PT Sentosa Abadi Manufaktur', 'Jl. Industri Raya No. 12, Surabaya',        4),
(4,  'PT Bank Sejahtera Nusantara', 'Jl. M.H. Thamrin No. 20, Jakarta Pusat',    5),
(5,  'RS Harapan Bunda',            'Jl. Kaliurang KM 5, Sleman, Yogyakarta',    6),
(6,  'PT Niaga Serba Ada',          'Jl. Pemuda No. 7, Semarang',                7),
(7,  'Hotel Grand Anggrek',         'Jl. Sunset Road No. 99, Denpasar, Bali',    8),
(8,  'PT Agro Lestari Sejahtera',   'Jl. Raya Puncak No. 3, Bogor',              9),
(9,  'CV Bengkel Motor Jaya',       'Jl. Soekarno Hatta No. 55, Malang',         10),
(10, 'PT Logistik Prima Ekspedisi', 'Jl. Gatot Subroto No. 8, Medan',            11);

INSERT INTO pembimbing (id_pembimbing, nip, nama_pembimbing, id_user) VALUES
(1,  '198001052005011002', 'Budi Santoso',     12),
(2,  '198203122006012003', 'Siti Rahayu',      13),
(3,  '197907202004041004', 'Ahmad Fauzi',      14),
(4,  '198411152008012005', 'Dewi Lestari',     15),
(5,  '198205232007011006', 'Joko Prasetyo',    16),
(6,  '198609082009012007', 'Rina Wulandari',   17),
(7,  '198103182006041008', 'Agus Setiawan',    18),
(8,  '198510242010012009', 'Sri Handayani',    19),
(9,  '198202142007011010', 'Bambang Sutrisno', 20),
(10, '198706302010012011', 'Maya Anggraini',   21);

INSERT INTO siswa (id_siswa, nisn, nama_siswa, kelas, id_user, id_perusahaan, id_pembimbing) VALUES
(1,  '0061234501', 'Rizky Ramadhan',  'XII RPL 1',  22, 1,  1),
(2,  '0061234502', 'Putri Andini',    'XII RPL 1',  23, 1,  1),
(3,  '0061234503', 'Fajar Nugroho',   'XII RPL 2',  24, 1,  1),
(4,  '0061234504', 'Nadia Salsabila', 'XII DKV 1',  25, 2,  2),
(5,  '0061234505', 'Dimas Prakoso',   'XII DKV 1',  26, 2,  2),
(6,  '0061234506', 'Ayu Kartika',     'XII RPL 1',  27, 2,  2),
(7,  '0061234507', 'Rendi Saputra',   'XII TKJ 1',  28, 3,  3),
(8,  '0061234508', 'Sinta Maharani',  'XII TKJ 1',  29, 3,  3),
(9,  '0061234509', 'Bayu Setiawan',   'XII TKJ 2',  30, 3,  3),
(10, '0061234510', 'Dinda Puspita',   'XII AKL 1',  31, 4,  4),
(11, '0061234511', 'Andi Kurniawan',  'XII AKL 1',  32, 4,  4),
(12, '0061234512', 'Mega Lestari',    'XII AKL 2',  33, 4,  4),
(13, '0061234513', 'Yoga Pratama',    'XII RPL 1',  34, 5,  5),
(14, '0061234514', 'Citra Dewi',      'XII OTKP 1', 35, 5,  5),
(15, '0061234515', 'Ilham Maulana',   'XII OTKP 1', 36, 5,  5),
(16, '0061234516', 'Ratna Juwita',    'XII OTKP 1', 37, 6,  6),
(17, '0061234517', 'Fikri Akbar',     'XII AKL 1',  38, 6,  6),
(18, '0061234518', 'Wulan Ramadhani', 'XII OTKP 1', 39, 7,  7),
(19, '0061234519', 'Teguh Santoso',   'XII AKL 1',  40, 7,  7),
(20, '0061234520', 'Anisa Fitriani',  'XII AKL 2',  41, 8,  8),
(21, '0061234521', 'Galih Permana',   'XII AKL 2',  42, 8,  8),
(22, '0061234522', 'Vina Oktaviani',  'XII TKJ 1',  43, 9,  9),
(23, '0061234523', 'Hasan Basri',     'XII TKJ 2',  44, 9,  9),
(24, '0061234524', 'Nur Aisyah',      'XII OTKP 1', 45, 10, 10),
(25, '0061234525', 'Danu Wicaksono',  'XII RPL 2',  46, 10, 10);

INSERT INTO jurnal (id_jurnal, id_siswa, tanggal, kegiatan, status_validasi) VALUES
(1,  1,  '2026-10-05', 'Mengikuti orientasi PKL, perkenalan dengan mentor, serta pengenalan lingkungan kerja dan tools pengembangan di perusahaan.', 'Disetujui'),
(2,  1,  '2026-10-06', 'Mempelajari struktur database sistem internal perusahaan dan membuat halaman login sederhana menggunakan PHP dan MySQL.', 'Disetujui'),
(3,  2,  '2026-10-05', 'Mengikuti daily meeting tim programmer, instalasi XAMPP dan VS Code, serta clone repository project tim.', 'Disetujui'),
(4,  2,  '2026-10-06', 'Membuat fitur CRUD data pelanggan pada aplikasi internal dan melakukan unit testing dasar bersama mentor.', 'Pending'),
(5,  3,  '2026-10-05', 'Mempelajari dokumentasi API perusahaan dan membantu menuliskan catatan perubahan (changelog) modul HRIS.', 'Revisi'),
(6,  3,  '2026-10-06', 'Membantu membuat laporan bug aplikasi internal serta menguji alur kerja form pengajuan cuti karyawan.', 'Disetujui'),
(7,  4,  '2026-10-05', 'Orientasi divisi creative, pengenalan brand guideline klien, dan latihan dasar Adobe Photoshop.', 'Disetujui'),
(8,  4,  '2026-10-06', 'Membuat tiga desain feed Instagram untuk promosi produk klien dan merevisi desain sesuai arahan art director.', 'Pending'),
(9,  5,  '2026-10-05', 'Mempelajari alur produksi konten video pendek dan membantu proses shooting iklan produk UMKM.', 'Disetujui'),
(10, 5,  '2026-10-06', 'Membuat storyboard konten reel promosi dan mempelajari teknik editing cepat menggunakan CapCut dan Premiere.', 'Pending'),
(11, 6,  '2026-10-05', 'Editing video behind the scene iklan klien dan melakukan rendering untuk keperluan review internal.', 'Disetujui'),
(12, 7,  '2026-10-05', 'Mengikuti safety induction K3, pengenalan area produksi, dan pengamatan proses produksi pada line 1.', 'Disetujui'),
(13, 8,  '2026-10-06', 'Membantu teknisi melakukan perawatan rutin mesin CNC serta mencatat hasil check sheet harian produksi.', 'Disetujui'),
(14, 9,  '2026-10-06', 'Quality control produk keluar: mengukur dimensi produk, mencatat hasil QC, dan mengisolasi produk cacat.', 'Pending'),
(15, 10, '2026-10-07', 'Orientasi layanan perbankan, pembekalan customer service, dan pengamatan proses pelayanan teller.', 'Disetujui'),
(16, 11, '2026-10-07', 'Membantu CS menginput data pembukaan rekening nasabah serta menjelaskan produk tabungan pelajar.', 'Disetujui'),
(17, 12, '2026-10-08', 'Mengarsipkan bukti transaksi harian dan membantu merekap laporan mutasi rekening di bagian back office.', 'Disetujui'),
(18, 13, '2026-10-08', 'Pengenalan unit rekam medis, alur pendaftaran pasien, dan sistem informasi manajemen rumah sakit (SIMRS).', 'Disetujui'),
(19, 14, '2026-10-09', 'Menginput data pasien rawat jalan ke SIMRS dan membantu petugas loket melayani pendaftaran pasien.', 'Disetujui'),
(20, 15, '2026-10-09', 'Membantu staf administrasi mengarsipkan berkas rekam medis pasien sesuai nomor urut dan tanggal kunjungan.', 'Revisi'),
(21, 16, '2026-10-12', 'Mengikuti briefing pagi cabang, belajar penataan display merchandise, dan pendampingan kasir pada jam sepi.', 'Disetujui'),
(22, 17, '2026-10-12', 'Membantu input data barang masuk dan melakukan pengecekan harga produk pada sistem kasir toko.', 'Disetujui'),
(23, 18, '2026-10-13', 'Belajar standar pelayanan front office hotel, tata cara menyambut tamu, dan mengamati proses check-in tamu.', 'Pending'),
(24, 19, '2026-10-13', 'Membantu reporting housekeeping: memeriksa kebersihan kamar dan merekap status kamar harian.', 'Disetujui'),
(25, 20, '2026-10-14', 'Pengenalan greenhouse, jenis tanaman hortikultura, dan sistem irigasi tetes otomatis di kebun.', 'Disetujui'),
(26, 21, '2026-10-14', 'Membantu panen sayur hidroponik, grading hasil panen, serta pencatatan hasil produksi ke logbook kebun.', 'Disetujui'),
(27, 22, '2026-10-15', 'Membantu mekanik melakukan servis berkala sepeda motor: ganti oli, pemeriksaan busi, dan tune up ringan.', 'Disetujui'),
(28, 23, '2026-10-15', 'Belajar diagnosa kerusakan mesin motor matic menggunakan alat scanner dan menyusun laporan servis.', 'Pending'),
(29, 24, '2026-10-16', 'Mengenal proses sorting paket di hub, penggunaan barcode scanner, dan input data kiriman ke sistem ekspedisi.', 'Disetujui'),
(30, 25, '2026-10-16', 'Membantu tim gudang proses loading paket ekspedisi serta merekap data pengiriman wilayah untuk kurir.', 'Disetujui');

INSERT INTO absensi (id_absensi, id_siswa, tanggal, jam_masuk, jam_pulang, status_hadir) VALUES
(1,  1,  '2026-10-12', '07:20:00', '16:00:00', 'Hadir'),
(2,  1,  '2026-10-13', '07:15:00', '16:05:00', 'Hadir'),
(3,  1,  '2026-10-14', '07:25:00', '16:00:00', 'Hadir'),
(4,  2,  '2026-10-12', '07:30:00', '16:00:00', 'Hadir'),
(5,  2,  '2026-10-13', '08:45:00', '16:00:00', 'Terlambat'),
(6,  2,  '2026-10-14', '07:20:00', '16:00:00', 'Hadir'),
(7,  3,  '2026-10-12', '07:10:00', '16:00:00', 'Hadir'),
(8,  3,  '2026-10-13', '07:35:00', '16:10:00', 'Hadir'),
(9,  3,  '2026-10-14', NULL,       NULL,       'Izin'),
(10, 4,  '2026-10-12', '07:25:00', '16:00:00', 'Hadir'),
(11, 4,  '2026-10-13', '07:30:00', '16:00:00', 'Hadir'),
(12, 4,  '2026-10-14', '07:40:00', '16:00:00', 'Hadir'),
(13, 5,  '2026-10-12', NULL,       NULL,       'Sakit'),
(14, 5,  '2026-10-13', '07:15:00', '16:00:00', 'Hadir'),
(15, 5,  '2026-10-14', '07:20:00', '16:05:00', 'Hadir'),
(16, 6,  '2026-10-12', '07:45:00', '16:00:00', 'Hadir'),
(17, 6,  '2026-10-13', '07:25:00', '16:00:00', 'Hadir'),
(18, 6,  '2026-10-14', '09:10:00', '16:00:00', 'Terlambat'),
(19, 7,  '2026-10-12', '07:05:00', '16:00:00', 'Hadir'),
(20, 7,  '2026-10-13', NULL,       NULL,       'Izin'),
(21, 7,  '2026-10-14', '07:30:00', '16:00:00', 'Hadir'),
(22, 8,  '2026-10-12', '07:50:00', '16:00:00', 'Hadir'),
(23, 8,  '2026-10-13', '07:20:00', '16:00:00', 'Hadir'),
(24, 8,  '2026-10-14', '07:35:00', '16:00:00', 'Hadir'),
(25, 9,  '2026-10-12', '07:30:00', '16:05:00', 'Hadir'),
(26, 9,  '2026-10-13', '07:40:00', '16:00:00', 'Hadir'),
(27, 9,  '2026-10-14', '07:25:00', '16:00:00', 'Hadir'),
(28, 10, '2026-10-12', NULL,       NULL,       'Alpa'),
(29, 10, '2026-10-13', '07:30:00', '16:00:00', 'Hadir'),
(30, 10, '2026-10-14', '07:15:00', '16:00:00', 'Hadir');


INSERT INTO monitoring (id_monitoring, id_pembimbing, id_siswa, tanggal_kunjungan, catatan_feedback) VALUES
(1,  1,  1,  '2026-10-06', 'Kunjungan pertama bulan Oktober. Siswa bersemangat, sudah diberikan akses development environment, dan mulai mengerjakan modul login. Koordinasi dengan mentor berjalan baik.'),
(2,  2,  4,  '2026-10-06', 'Siswa aktif mengikuti brief desain dan diberi kesempatan mengerjakan konten untuk klien nyata. Saran: tingkatkan ketelitian saat revisi desain.'),
(3,  3,  7,  '2026-10-07', 'Siswa sudah memahami prosedur K3 dan mengikuti kegiatan produksi dengan tertib. Disarankan lebih proaktif bertanya kepada teknisi senior.'),
(4,  4,  10, '2026-10-08', 'Kunjungan monitoring bulanan. Siswa tampil rapi dan sopan saat melayani nasabah. Perusahaan memuji kedisiplinan dan kerapian kerja siswa.'),
(5,  5,  13, '2026-10-09', 'Siswa cepat beradaptasi dengan SIMRS dan sudah bisa menginput data pasien secara mandiri. Disarankan mempelajari alur rekam medis lebih dalam.'),
(6,  6,  16, '2026-10-12', 'Siswa komunikatif dan berani melayani pelanggan. Perusahaan mengusulkan pelatihan tambahan pengoperasian sistem kasir pekan depan.'),
(7,  7,  18, '2026-10-12', 'Kunjungan rutin. Siswa memahami standar pelayanan front office. Perlu penguatan pada pengisian laporan harian housekeeping.'),
(8,  8,  20, '2026-10-13', 'Siswa antusias mempelajari budidaya hidroponik dan sistem irigasi tetes. Catatan: konsistensi pencatatan logbook perlu ditingkatkan.'),
(9,  9,  22, '2026-10-14', 'Siswa terampil membantu servis ringan dan kooperatif dengan mekanik senior. Disarankan berlatih diagnosa kerusakan dengan scanner lebih intensif.'),
(10, 10, 24, '2026-10-15', 'Siswa disiplin pada shift sorting pagi dan ketelitian scanning barcode sudah baik. Perusahaan berharap performa tetap dipertahankan.');