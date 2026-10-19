# InternTrack - Sistem Informasi Monitoring PKL

## Struktur folder
```
InternTrack/
  config/      config.php (koneksi DB + fungsi bersama), database.sql
  assets/      style.css
  auth/        login.php, logout.php
  admin/       dashboard, siswa, perusahaan, pembimbing, penempatan, jurnal, absensi, monitoring
               (dipakai admin, guru pembimbing, dan perusahaan sesuai hak akses)
  siswa/       dashboard, jurnal, absensi, monitoring (halaman untuk siswa)
  laporan/     laporan.php (laporan siswa, kehadiran, monitoring)
  index.php    pintu masuk, mengarahkan ke login atau dashboard
```
`siswa/jurnal.php`, `siswa/absensi.php`, dan `siswa/monitoring.php` memakai modul yang sama dengan folder `admin/`; hak akses dibatasi di dalam modul.

PHP + MySQL/MariaDB, HTML, CSS, dan sedikit JavaScript (konfirmasi hapus dan tombol cetak).

## Cara menjalankan
1. Salin folder `InternTrack` ke `htdocs` (XAMPP) atau `www` (Laragon).
2. Buka phpMyAdmin, buat database baru bernama `interntrack`, lalu import `database.sql` (file SQL asli, tidak diubah).
3. Kalau user/password MySQL kamu berbeda, ubah bagian atas `config/config.php`.
4. Buka `http://localhost/InternTrack/`.

## Akun untuk login (semua password: `password123`)
| Role | Username |
|---|---|
| Admin | `admin` |
| Guru pembimbing | `budi.santoso` |
| Perusahaan | `ptmajujaya` |
| Siswa | `rizky.ramadhan` |

Password akun baru yang dibuat dari aplikasi disimpan dalam bentuk hash. Password data awal (teks biasa) tetap bisa dipakai login.

## Hak akses
- **Admin**: semua menu, termasuk CRUD siswa, perusahaan, pembimbing, dan penempatan.
- **Guru pembimbing**: melihat siswa bimbingannya, validasi jurnal, absensi, input monitoring, laporan.
- **Perusahaan**: melihat siswa yang PKL di perusahaannya, validasi jurnal, absensi, laporan.
- **Siswa**: mengisi jurnal dan absensi sendiri, melihat catatan monitoring.

## Penyesuaian terhadap SQL
Aplikasi mengikuti struktur tabel di SQL apa adanya. Beberapa isian di soal tidak ada kolomnya di SQL, jadi diatur begini:
- NIS memakai kolom `nisn`. Jurusan diambil dari kata kedua kolom `kelas` (XII **RPL** 1).
- Tidak ada kolom No. HP, bidang, kontak, hasil, kendala, nilai, rekomendasi, tanggal mulai/selesai. Kegiatan, hasil, dan kendala ditulis dalam satu kolom `kegiatan`; catatan, nilai, dan rekomendasi dalam `catatan_feedback`.
- Status PKL dihitung dari penempatan: Aktif (perusahaan dan pembimbing terisi), Belum Lengkap, Belum Ditempatkan. Status "Selesai" tidak bisa dihitung tanpa tanggal.
- Persentase kehadiran = (Hadir + Terlambat) / total hari tercatat. 90%+ Sangat Baik, 80-89% Baik, 70-79% Cukup, di bawah itu Kurang.
- Siswa perlu perhatian: pernah alpa, kehadiran di bawah 75%, ada jurnal berstatus Revisi, atau belum ditempatkan.

Kalau mau kolom tambahan itu ada, tinggal bilang dan SQL-nya kita tambah (ALTER TABLE) sekalian halamannya.

## Test case
| No | Fitur | Skenario | Expected result |
|---|---|---|---|
| 1 | Login | Username dan password benar | Berhasil login, masuk dashboard |
| 2 | Login | Password salah | Muncul "Username atau password salah." |
| 3 | Login | Username dikosongkan | Muncul "Username wajib diisi." |
| 4 | Siswa | Tambah data lengkap | Data tersimpan dan tampil di daftar |
| 5 | Siswa | NISN kosong | Muncul validasi NISN tidak boleh kosong |
| 6 | Siswa | NISN duplikat (mis. 0061234501) | Data ditolak |
| 7 | Siswa | Hapus siswa | Muncul konfirmasi, data hilang setelah OK |
| 8 | Perusahaan | Tambah, edit, hapus perusahaan | Data berubah sesuai aksi |
| 9 | Penempatan | Atur siswa ke perusahaan dan pembimbing | Status siswa menjadi Aktif |
| 10 | Jurnal | Siswa input jurnal | Data tersimpan, status Pending |
| 11 | Jurnal | Tanggal atau kegiatan kosong | Muncul validasi |
| 12 | Jurnal | Pembimbing klik Setujui | Status jadi Disetujui |
| 13 | Absensi | Input hadir dengan jam masuk | Data tersimpan |
| 14 | Absensi | Rekap kehadiran (Rizky: 3 dari 3) | 100%, Sangat Baik |
| 15 | Monitoring | Pembimbing input catatan | Data tersimpan |
| 16 | Search | Cari nama siswa "putri" | Hanya Putri Andini tampil |
| 17 | Filter | Filter kelas XII TKJ 1 | Hanya siswa kelas itu tampil |
| 18 | Laporan | Buka laporan kehadiran lalu cetak | Tabel dan persentase tampil, menu tidak ikut tercetak |
| 19 | Dashboard | Angka dibanding isi database | Sama dengan data di tabel |
| 20 | Hak akses | Login siswa lalu buka admin/siswa.php | Dialihkan ke dashboard |
