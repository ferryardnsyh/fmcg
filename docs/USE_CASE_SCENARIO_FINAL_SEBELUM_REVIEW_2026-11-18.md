# Skenario Use Case Sistem Warehouse Management System (WMS) CV. Putra Sumber Sari

- **Nama Sistem:** PSS Warehouse Management System (PSS WMS)
- **Jenis Dokumen:** Penyempurnaan Skenario Use Case untuk Laporan Kerja Praktik
- **Versi:** 1.0
- **Tanggal:** 17 November 2026
- **Sumber Acuan:** Diagram Use Case pada `docs/USECASE_SKENARIO.md`, kebutuhan fungsional pada `blackbox_testing/report/SRS_WMS_PSS_FILLED.docx`, rute dan layanan backend pada `backend/app`, antarmuka pada `frontend/src`, serta skema basis data pada `database/psswms_db.sql`
- **Status:** Siap untuk validasi dosen pembimbing dan penguji

---

## 1. Pendahuluan

### 1.1 Tujuan Dokumen

Dokumen ini menyajikan skenario use case final dari PSS WMS. Setiap skenario disusun dari tindakan aktor dan respons sistem yang dapat diverifikasi terhadap diagram use case, kebutuhan fungsional, dan implementasi aplikasi. Dokumen ini digunakan sebagai dasar validasi kebutuhan, pengujian fungsional, dan penulisan laporan Kerja Praktik.

### 1.2 Metode Pemeriksaan

Pemeriksaan dilakukan terhadap dokumen laporan dan SRS yang tersedia, daftar kebutuhan fungsional, diagram use case, daftar aktor dan hak akses, rute serta layanan backend, halaman frontend, dan struktur tabel basis data. Apabila ditemukan perbedaan, ketentuan ruang lingkup pada dokumen use case dan implementasi aktual dijadikan acuan. Perbedaan yang ditemukan dicatat pada Bagian 3 tanpa mengubah kode aplikasi.

### 1.3 Ruang Lingkup

Ruang lingkup skenario mencakup autentikasi, dashboard, data master produk, kategori, supplier, rak/lokasi, dan batch; transaksi barang masuk serta barang keluar; manajemen stok dan penyesuaian stok; stock opname; monitoring kedaluwarsa; laporan dan ekspor PDF; administrasi pengguna; serta notifikasi.

Fitur yang tidak dimasukkan karena tidak terdapat pada diagram use case dan tidak diimplementasikan, yaitu Purchase Order, Delivery Order, pemusnahan atau disposal, pengelolaan brand sebagai data master tersendiri, pengelolaan satuan, pengelolaan gudang, multi-gudang, pemindaian barcode, integrasi sistem eksternal, penjualan, pemasaran, pembayaran, serta pembukuan keuangan.

### 1.4 Identitas Aktor dan Hak Akses

- **Super Admin:** memiliki hak akses tertinggi, termasuk pengelolaan data master, transaksi, persetujuan penyesuaian stok, stock opname, laporan, administrasi pengguna, dan generate notifikasi.
- **Admin:** memiliki hak akses operasional gudang dan persetujuan penyesuaian stok, termasuk pengelolaan data master, administrasi pengguna, dan generate notifikasi.
- **Gudang:** memiliki hak akses terbatas. Aktor ini dapat melihat data master, memantau batch, melakukan transaksi barang masuk dan barang keluar, melakukan stock opname, melihat stok, melihat laporan, melihat dan menandai notifikasi, serta membuat penyesuaian stok melalui API. Aktor ini tidak memiliki tombol pengelolaan data master, tombol buat dan persetujuan penyesuaian stok pada antarmuka, menu User Management, dan tombol generate notifikasi.
- **Sistem:** aktor sekunder yang menghasilkan nomor dokumen, menghitung ringkasan dan selisih, mengelola sesi, menyimpan data, serta menampilkan respons dan pesan validasi.

### 1.5 Daftar Use Case

- UC-01 Login — Super Admin, Admin, Gudang
- UC-02 Logout — Super Admin, Admin, Gudang
- UC-03 Melihat Profil Sendiri — Super Admin, Admin, Gudang
- UC-04 Melihat Dashboard — Super Admin, Admin, Gudang
- UC-05 Mengelola Produk — Super Admin, Admin; Gudang hanya melihat
- UC-06 Mengelola Kategori — Super Admin, Admin; Gudang hanya melihat
- UC-07 Mengelola Supplier — Super Admin, Admin; Gudang hanya melihat
- UC-08 Mengelola Rak/Lokasi — Super Admin, Admin; Gudang hanya melihat
- UC-09 Monitoring Batch — Super Admin, Admin, Gudang
- UC-10 Mengelola Barang Masuk — Super Admin, Admin, Gudang
- UC-11 Menyelesaikan Barang Masuk — Super Admin, Admin, Gudang
- UC-12 Mengelola Barang Keluar — Super Admin, Admin, Gudang
- UC-13 Menyelesaikan Barang Keluar — Super Admin, Admin, Gudang
- UC-14 Melihat Stok Realtime — Super Admin, Admin, Gudang
- UC-15 Melihat Mutasi Stok — Super Admin, Admin, Gudang
- UC-16 Melihat Stok Rendah — Super Admin, Admin, Gudang
- UC-17 Membuat Stock Adjustment — Super Admin, Admin, Gudang melalui API
- UC-18 Menyetujui Stock Adjustment — Super Admin, Admin
- UC-19 Menolak Stock Adjustment — Super Admin, Admin
- UC-20 Membuat Sesi Opname — Super Admin, Admin, Gudang
- UC-21 Mengisi Hasil Opname — Super Admin, Admin, Gudang
- UC-22 Menyelesaikan Sesi Opname — Super Admin, Admin, Gudang
- UC-23 Menghapus Sesi Opname — Super Admin, Admin, Gudang
- UC-24 Monitoring Expired — Super Admin, Admin, Gudang
- UC-25 Menampilkan Laporan — Super Admin, Admin, Gudang
- UC-26 Export Laporan PDF — Super Admin, Admin, Gudang
- UC-27 Mengelola User — Super Admin, Admin
- UC-28 Mengubah Password User — Super Admin, Admin
- UC-29 Melihat Notifikasi — Super Admin, Admin, Gudang
- UC-30 Menandai Notifikasi Dibaca — Super Admin, Admin, Gudang
- UC-31 Generate Notifikasi — Super Admin, Admin

---

## 2. Skenario Use Case

### UC-01 Login

- **ID Use Case:** UC-01
- **Nama Use Case:** Login
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Memperoleh sesi terautentikasi untuk mengakses sistem sesuai peran.
- **Prasyarat (Precondition):** Aktor belum memiliki sesi aktif atau sesi sebelumnya telah berakhir.
- **Kondisi Akhir (Postcondition):** Sesi aktif terbentuk dan aktor berada pada halaman dashboard.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka halaman login. | 2. Sistem menampilkan formulir login. |
| 3. Super Admin, Admin, atau Gudang memasukkan username dan password. | |
| 4. Super Admin, Admin, atau Gudang menekan tombol Login. | 5. Sistem memvalidasi bahwa username dan password tidak kosong. |
| | 6. Sistem memverifikasi kredensial dan status aktif pengguna. |
| | 7. Sistem membentuk sesi serta menyimpan identitas dan peran pengguna. |
| | 8. Sistem menampilkan halaman dashboard. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang mengubah tampilan password. | 3a.1. Sistem menampilkan atau menyembunyikan karakter password. |
| | 3a.2. Sistem kembali ke langkah 3. |
| 4a. Super Admin, Admin, atau Gudang memiliki sesi aktif dan membuka halaman login. | 4a.1. Sistem menampilkan halaman dashboard. |
| | 4a.2. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5b. Super Admin, Admin, atau Gudang membiarkan username atau password kosong. | 5b.1. Sistem menolak proses login. |
| | 5b.2. Sistem menampilkan pesan "Username dan Password wajib diisi". |
| | 5b.3. Sistem kembali ke langkah 3. |
| 6b. Super Admin, Admin, atau Gudang memasukkan kredensial yang salah. | 6b.1. Sistem menolak proses login. |
| | 6b.2. Sistem menampilkan pesan "Username atau password salah". |
| | 6b.3. Sistem kembali ke langkah 3. |
| 6c. Akun yang digunakan tidak aktif. | 6c.1. Sistem menolak proses login. |
| | 6c.2. Sistem menampilkan pesan "Akun sudah dinonaktifkan. Silakan hubungi administrator." |
| | 6c.3. Alur berakhir. |

---

### UC-02 Logout

- **ID Use Case:** UC-02
- **Nama Use Case:** Logout
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Mengakhiri sesi dan keluar dari aplikasi.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Sesi aktor berakhir dan halaman terproteksi tidak dapat diakses.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang menekan tombol Logout pada Navbar. | 2. Sistem mengakhiri sesi pengguna. |
| | 3. Sistem menampilkan halaman login. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1a. Super Admin, Admin, atau Gudang menutup browser tanpa menekan tombol Logout. | 1a.1. Sistem mengakhiri sesi sesuai masa berlaku sesi. |
| | 1a.2. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1b. Super Admin, Admin, atau Gudang mengakhiri sesi setelah masa berlaku sesi habis. | 1b.1. Sistem menolak permintaan logout. |
| | 1b.2. Sistem menampilkan halaman login. |
| | 1b.3. Alur berakhir. |

---

### UC-03 Melihat Profil Sendiri

- **ID Use Case:** UC-03
- **Nama Use Case:** Melihat Profil Sendiri
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan identitas pengguna yang sedang login.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Nama dan peran aktor tampil pada Navbar.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka aplikasi. | 2. Sistem membaca identitas aktor dari sesi. |
| | 3. Sistem menampilkan nama dan peran aktor pada Navbar. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1a. Super Admin, Admin, atau Gudang berpindah ke halaman lain. | 1a.1. Sistem tetap menampilkan nama dan peran aktor pada Navbar. |
| | 1a.2. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1b. Sesi aktor berakhir saat halaman dibuka. | 1b.1. Sistem menampilkan halaman login. |
| | 1b.2. Alur berakhir. |

---

### UC-04 Melihat Dashboard

- **ID Use Case:** UC-04
- **Nama Use Case:** Melihat Dashboard
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan ringkasan operasional gudang.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Ringkasan operasional gudang terbaru ditampilkan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Dashboard. | 2. Sistem mengambil data ringkasan, grafik tren, produk terlaris, dan produk mendekati expired. |
| | 3. Sistem menghitung total produk aktif, total batch, total stok, jumlah stok rendah, jumlah produk mendekati expired, serta transaksi masuk dan keluar hari ini. |
| | 4. Sistem menampilkan kartu ringkasan, grafik, dan tabel. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2a. Belum terdapat data transaksi pada sistem. | 2a.1. Sistem menampilkan nilai nol dan daftar kosong. |
| | 2a.2. Sistem kembali ke langkah 4. |
| 4a. Super Admin, Admin, atau Gudang menyegarkan halaman Dashboard. | 4a.1. Sistem memperbarui data ringkasan, grafik, dan tabel. |
| | 4a.2. Sistem kembali ke langkah 4. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2b. Sesi aktor berakhir saat data diminta. | 2b.1. Sistem menampilkan halaman login. |
| | 2b.2. Alur berakhir. |
| 2c. Terjadi kegagalan pengambilan data dashboard. | 2c.1. Sistem menampilkan pesan "Gagal memuat data dashboard". |
| | 2c.2. Sistem menampilkan komponen dashboard dalam kondisi kosong. |
| | 2c.3. Alur berakhir. |

---

### UC-05 Mengelola Produk

- **ID Use Case:** UC-05
- **Nama Use Case:** Mengelola Produk
- **Aktor:** Super Admin dan Admin sebagai pengelola; Gudang hanya melihat.
- **Tujuan:** Menampilkan dan memelihara data produk.
- **Prasyarat (Precondition):** Aktor sudah login. Untuk menambah, mengubah, atau menghapus data, aktor harus Super Admin atau Admin.
- **Kondisi Akhir (Postcondition):** Data produk tersaji dan perubahan data tersimpan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Produk. | 2. Sistem menampilkan daftar produk dan statistik produk. |
| 3. Super Admin, Admin, atau Gudang mencari produk atau memilih halaman daftar. | 4. Sistem menampilkan hasil sesuai kata kunci dan halaman yang dipilih. |
| 5. Super Admin atau Admin menekan tombol Tambah Produk. | 6. Sistem menampilkan formulir produk. |
| 7. Super Admin atau Admin mengisi nama, SKU, kategori, supplier, merek, satuan, stok minimum, dan status. | |
| 8. Super Admin atau Admin menekan tombol Simpan. | 9. Sistem memvalidasi kolom wajib dan keunikan SKU. |
| | 10. Sistem menyimpan produk dan menampilkan pesan keberhasilan. |
| | 11. Sistem memperbarui daftar produk. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin atau Admin menekan tombol Ubah pada sebuah produk. | 5a.1. Sistem menampilkan formulir berisi data produk tersebut. |
| 5a.2. Super Admin atau Admin mengubah data dan menekan tombol Update. | 5a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 5a.4. Sistem kembali ke langkah 11. |
| 5b. Super Admin atau Admin menekan tombol Hapus pada produk. | 5b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5b.2. Super Admin atau Admin mengonfirmasi penghapusan. | 5b.3. Sistem menghapus produk dan menampilkan pesan keberhasilan. |
| | 5b.4. Sistem kembali ke langkah 11. |
| 5c. Gudang membuka menu Produk. | 5c.1. Sistem menyembunyikan tombol Tambah, Ubah, dan Hapus. |
| | 5c.2. Sistem mengizinkan Gudang melihat daftar dan statistik produk. |
| | 5c.3. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 9b. Nama, SKU, kategori, atau supplier kosong. | 9b.1. Sistem menolak penyimpanan dan menampilkan pesan field wajib diisi. |
| | 9b.2. Sistem kembali ke langkah 7. |
| 9c. SKU yang dimasukkan sudah digunakan. | 9c.1. Sistem menolak penyimpanan dan menampilkan pesan "SKU code sudah digunakan". |
| | 9c.2. Sistem kembali ke langkah 7. |
| 5b.3b. Produk yang dihapus masih memiliki batch stok. | 5b.3b.1. Sistem memblokir penghapusan dan menyarankan penonaktifan produk. |
| | 5b.3b.2. Alur berakhir. |

---

### UC-06 Mengelola Kategori

- **ID Use Case:** UC-06
- **Nama Use Case:** Mengelola Kategori
- **Aktor:** Super Admin dan Admin sebagai pengelola; Gudang hanya melihat.
- **Tujuan:** Menampilkan dan memelihara data kategori.
- **Prasyarat (Precondition):** Aktor sudah login. Untuk menambah, mengubah, atau menghapus data, aktor harus Super Admin atau Admin.
- **Kondisi Akhir (Postcondition):** Data kategori tersaji dan perubahan data tersimpan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Kategori. | 2. Sistem menampilkan daftar kategori beserta jumlah produk. |
| 3. Super Admin, Admin, atau Gudang mencari kategori, memilih status, atau mengubah urutan daftar. | 4. Sistem menampilkan hasil sesuai kata kunci, status, dan urutan yang dipilih. |
| 5. Super Admin atau Admin menekan tombol Tambah Kategori. | 6. Sistem menampilkan formulir kategori dengan kode otomatis. |
| 7. Super Admin atau Admin mengisi nama dan keterangan kategori. | |
| 8. Super Admin atau Admin menekan tombol Simpan. | 9. Sistem memvalidasi nama wajib, panjang maksimal, dan keunikan nama serta kode. |
| | 10. Sistem menyimpan kategori dan menampilkan pesan keberhasilan. |
| | 11. Sistem memperbarui daftar kategori. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin atau Admin menekan tombol Ubah pada kategori. | 5a.1. Sistem menampilkan formulir berisi data kategori tersebut. |
| 5a.2. Super Admin atau Admin mengubah data dan menekan tombol Update. | 5a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 5a.4. Sistem kembali ke langkah 11. |
| 5b. Super Admin atau Admin menekan tombol Hapus pada kategori. | 5b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5b.2. Super Admin atau Admin mengonfirmasi penghapusan. | 5b.3. Sistem menghapus kategori dan menampilkan pesan keberhasilan. |
| | 5b.4. Sistem kembali ke langkah 11. |
| 5c. Gudang membuka menu Kategori. | 5c.1. Sistem menyembunyikan tombol Tambah, Ubah, dan Hapus. |
| | 5c.2. Sistem mengizinkan Gudang melihat daftar kategori. |
| | 5c.3. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 9b. Nama kategori kosong atau melebihi batas panjang. | 9b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi nama. |
| | 9b.2. Sistem kembali ke langkah 7. |
| 9c. Nama atau kode kategori sudah digunakan. | 9c.1. Sistem menolak penyimpanan dan menampilkan pesan data duplikat. |
| | 9c.2. Sistem kembali ke langkah 7. |
| 5b.3b. Kategori masih digunakan oleh produk. | 5b.3b.1. Sistem memblokir penghapusan dan menyarankan penonaktifan kategori. |
| | 5b.3b.2. Alur berakhir. |

### UC-07 Mengelola Supplier

- **ID Use Case:** UC-07
- **Nama Use Case:** Mengelola Supplier
- **Aktor:** Super Admin dan Admin sebagai pengelola; Gudang hanya melihat.
- **Tujuan:** Menampilkan dan memelihara data supplier.
- **Prasyarat (Precondition):** Aktor sudah login. Untuk menambah, mengubah, atau menghapus data, aktor harus Super Admin atau Admin.
- **Kondisi Akhir (Postcondition):** Data supplier tersaji dan perubahan data tersimpan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Supplier. | 2. Sistem menampilkan daftar supplier. |
| 3. Super Admin, Admin, atau Gudang mencari supplier pada daftar. | 4. Sistem menampilkan hasil sesuai kata kunci. |
| 5. Super Admin atau Admin menekan tombol Tambah Supplier. | 6. Sistem menampilkan formulir supplier dengan kode otomatis. |
| 7. Super Admin atau Admin mengisi kode, nama, kontak, telepon, email, alamat, dan status supplier. | |
| 8. Super Admin atau Admin menekan tombol Simpan. | 9. Sistem memvalidasi kode dan nama wajib, format email, serta keunikan kode. |
| | 10. Sistem menyimpan supplier dan menampilkan pesan keberhasilan. |
| | 11. Sistem memperbarui daftar supplier. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin atau Admin menekan tombol Ubah pada supplier. | 5a.1. Sistem menampilkan formulir berisi data supplier tersebut. |
| 5a.2. Super Admin atau Admin mengubah data dan menekan tombol Update. | 5a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 5a.4. Sistem kembali ke langkah 11. |
| 5b. Super Admin atau Admin menekan tombol Hapus pada supplier. | 5b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5b.2. Super Admin atau Admin mengonfirmasi penghapusan. | 5b.3. Sistem menghapus supplier dan menampilkan pesan keberhasilan. |
| | 5b.4. Sistem kembali ke langkah 11. |
| 5c. Gudang membuka menu Supplier. | 5c.1. Sistem menyembunyikan tombol Tambah, Ubah, dan Hapus. |
| | 5c.2. Sistem mengizinkan Gudang melihat daftar supplier. |
| | 5c.3. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 9b. Kode atau nama supplier kosong, atau email tidak valid. | 9b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 9b.2. Sistem kembali ke langkah 7. |
| 9c. Kode supplier sudah digunakan. | 9c.1. Sistem menolak penyimpanan dan menampilkan pesan data duplikat. |
| | 9c.2. Sistem kembali ke langkah 7. |
| 5b.3b. Supplier masih memiliki transaksi barang masuk. | 5b.3b.1. Sistem memblokir penghapusan dan menyarankan penonaktifan supplier. |
| | 5b.3b.2. Alur berakhir. |

---

### UC-08 Mengelola Rak/Lokasi

- **ID Use Case:** UC-08
- **Nama Use Case:** Mengelola Rak/Lokasi
- **Aktor:** Super Admin dan Admin sebagai pengelola; Gudang hanya melihat.
- **Tujuan:** Menampilkan dan memelihara data rak atau lokasi penyimpanan.
- **Prasyarat (Precondition):** Aktor sudah login. Untuk menambah, mengubah, atau menghapus data, aktor harus Super Admin atau Admin.
- **Kondisi Akhir (Postcondition):** Data rak/lokasi tersaji dan perubahan data tersimpan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Rak / Lokasi. | 2. Sistem menampilkan daftar rak/lokasi. |
| 3. Super Admin, Admin, atau Gudang mencari rak atau memilih status daftar. | 4. Sistem menampilkan hasil sesuai kata kunci dan status. |
| 5. Super Admin atau Admin menekan tombol Tambah Rak. | 6. Sistem menampilkan formulir rak/lokasi. |
| 7. Super Admin atau Admin mengisi kode rak, nama, dan status. | |
| 8. Super Admin atau Admin menekan tombol Simpan. | 9. Sistem memvalidasi kode wajib, batas panjang, status, dan keunikan kode. |
| | 10. Sistem menyimpan rak/lokasi dan menampilkan pesan keberhasilan. |
| | 11. Sistem memperbarui daftar rak/lokasi. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin atau Admin menekan tombol Ubah pada rak/lokasi. | 5a.1. Sistem menampilkan formulir berisi data rak/lokasi tersebut. |
| 5a.2. Super Admin atau Admin mengubah data dan menekan tombol Update. | 5a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 5a.4. Sistem kembali ke langkah 11. |
| 5b. Super Admin atau Admin menekan tombol Hapus pada rak/lokasi. | 5b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5b.2. Super Admin atau Admin mengonfirmasi penghapusan. | 5b.3. Sistem menghapus rak/lokasi dan menampilkan pesan keberhasilan. |
| | 5b.4. Sistem kembali ke langkah 11. |
| 5c. Gudang membuka menu Rak / Lokasi. | 5c.1. Sistem menyembunyikan tombol Tambah, Ubah, dan Hapus. |
| | 5c.2. Sistem mengizinkan Gudang melihat daftar rak/lokasi. |
| | 5c.3. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 9b. Kode rak kosong, panjang melebihi batas, atau status tidak valid. | 9b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 9b.2. Sistem kembali ke langkah 7. |
| 9c. Kode rak sudah digunakan. | 9c.1. Sistem menolak penyimpanan dan menampilkan pesan data duplikat. |
| | 9c.2. Sistem kembali ke langkah 7. |
| 5b.3b. Rak/lokasi masih digunakan oleh batch produk. | 5b.3b.1. Sistem memblokir penghapusan dan menyarankan penonaktifan rak. |
| | 5b.3b.2. Alur berakhir. |

---

### UC-09 Monitoring Batch

- **ID Use Case:** UC-09
- **Nama Use Case:** Monitoring Batch
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Memantau daftar batch, statistik batch, dan detail batch.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Informasi batch tersaji sesuai pilihan aktor.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Batch. | 2. Sistem menampilkan statistik dan daftar batch. |
| 3. Super Admin, Admin, atau Gudang mencari batch atau memilih halaman daftar. | 4. Sistem menampilkan hasil sesuai kata kunci dan halaman yang dipilih. |
| 5. Super Admin, Admin, atau Gudang memilih satu batch. | 6. Sistem menampilkan detail batch beserta sisa hari menuju kedaluwarsa. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Belum terdapat batch pada sistem. | 3a.1. Sistem menampilkan statistik bernilai nol dan daftar kosong. |
| | 3a.2. Alur berakhir. |
| 5a. Super Admin, Admin, atau Gudang kembali ke daftar batch. | 5a.1. Sistem menampilkan daftar batch. |
| | 5a.2. Sistem kembali ke langkah 3. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2b. Sesi aktor berakhir saat data batch diminta. | 2b.1. Sistem menampilkan halaman login. |
| | 2b.2. Alur berakhir. |
| 2c. Terjadi kegagalan pengambilan data batch. | 2c.1. Sistem menampilkan pesan bahwa data batch gagal dimuat. |
| | 2c.2. Alur berakhir. |

---

### UC-10 Mengelola Barang Masuk

- **ID Use Case:** UC-10
- **Nama Use Case:** Mengelola Barang Masuk
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Mencatat, melihat, mengubah, dan menghapus transaksi barang masuk berstatus DRAFT.
- **Prasyarat (Precondition):** Aktor sudah login dan data supplier, produk, serta rak tersedia.
- **Kondisi Akhir (Postcondition):** Transaksi barang masuk tersimpan berstatus DRAFT atau daftar transaksi diperbarui.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Barang Masuk. | 2. Sistem menampilkan daftar penerimaan barang. |
| 3. Super Admin, Admin, atau Gudang menekan tombol Tambah Barang Masuk. | 4. Sistem menampilkan formulir dengan nomor penerimaan otomatis. |
| 5. Super Admin, Admin, atau Gudang memilih supplier dan tanggal masuk. | 6. Sistem menampilkan produk yang sesuai dengan supplier tersebut. |
| 7. Super Admin, Admin, atau Gudang mengisi detail barang berupa produk, nomor batch, jumlah, tanggal kedaluwarsa, dan rak. | |
| 8. Super Admin, Admin, atau Gudang menekan tombol Simpan. | 9. Sistem memvalidasi supplier aktif, tanggal, detail barang, kesesuaian produk dengan supplier, jumlah positif, dan keunikan nomor batch. |
| | 10. Sistem menyimpan penerimaan berstatus DRAFT dan menampilkan pesan keberhasilan. |
| | 11. Sistem memperbarui daftar penerimaan barang. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang menekan tombol Ubah pada penerimaan berstatus DRAFT. | 3a.1. Sistem menampilkan formulir berisi data penerimaan tersebut. |
| 3a.2. Super Admin, Admin, atau Gudang mengubah data dan menekan tombol Update. | 3a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 3a.4. Sistem kembali ke langkah 11. |
| 3b. Super Admin, Admin, atau Gudang menekan tombol Hapus pada penerimaan berstatus DRAFT. | 3b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 3b.2. Super Admin, Admin, atau Gudang mengonfirmasi penghapusan. | 3b.3. Sistem menghapus penerimaan dan menampilkan pesan keberhasilan. |
| | 3b.4. Sistem kembali ke langkah 11. |
| 3c. Super Admin, Admin, atau Gudang membatalkan pengisian formulir. | 3c.1. Sistem menutup formulir tanpa menyimpan data. |
| | 3c.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 9b. Supplier tidak aktif, tanggal kosong, detail kosong, atau jumlah tidak positif. | 9b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 9b.2. Sistem kembali ke langkah 5. |
| 9c. Nomor batch pada sebuah produk sudah digunakan. | 9c.1. Sistem menolak penyimpanan dan menampilkan pesan data duplikat. |
| | 9c.2. Sistem kembali ke langkah 7. |
| 3a.3b. Penerimaan yang diubah atau dihapus sudah berstatus COMPLETED. | 3a.3b.1. Sistem memblokir perubahan dan menampilkan pesan bahwa hanya penerimaan DRAFT yang dapat diubah. |
| | 3a.3b.2. Alur berakhir. |

---

### UC-11 Menyelesaikan Barang Masuk

- **ID Use Case:** UC-11
- **Nama Use Case:** Menyelesaikan Barang Masuk
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menyelesaikan penerimaan barang dan menambah stok sesuai detail penerimaan.
- **Prasyarat (Precondition):** Aktor sudah login dan terdapat penerimaan berstatus DRAFT.
- **Kondisi Akhir (Postcondition):** Penerimaan berstatus COMPLETED dan stok batch bertambah.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Barang Masuk. | 2. Sistem menampilkan daftar penerimaan beserta statusnya. |
| 3. Super Admin, Admin, atau Gudang memilih penerimaan berstatus DRAFT dan menekan tombol Selesaikan. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin, Admin, atau Gudang mengonfirmasi penyelesaian. | 6. Sistem memvalidasi detail penerimaan, nomor batch, supplier, dan jumlah barang. |
| | 7. Sistem membuat batch baru atau memperbarui jumlah batch yang sudah ada. |
| | 8. Sistem mengubah status penerimaan menjadi COMPLETED. |
| | 9. Sistem menampilkan pesan "Inbound berhasil diselesaikan dan stok telah ditambahkan". |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan konfirmasi. | 3a.1. Sistem membatalkan proses penyelesaian. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Penerimaan yang dipilih sudah berstatus COMPLETED. | 6b.1. Sistem memblokir penyelesaian dan menampilkan pesan bahwa penerimaan tidak dapat diselesaikan. |
| | 6b.2. Alur berakhir. |
| 6c. Nomor batch atau jumlah barang tidak valid. | 6c.1. Sistem menolak penyelesaian dan menampilkan pesan validasi. |
| | 6c.2. Sistem kembali ke langkah 2. |

---

### UC-12 Mengelola Barang Keluar

- **ID Use Case:** UC-12
- **Nama Use Case:** Mengelola Barang Keluar
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Mencatat, melihat, mengubah, dan menghapus transaksi barang keluar berstatus DRAFT.
- **Prasyarat (Precondition):** Aktor sudah login dan batch dengan stok tersedia.
- **Kondisi Akhir (Postcondition):** Transaksi barang keluar tersimpan berstatus DRAFT atau daftar transaksi diperbarui.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Barang Keluar. | 2. Sistem menampilkan daftar pengiriman barang. |
| 3. Super Admin, Admin, atau Gudang menekan tombol Tambah Barang Keluar. | 4. Sistem menampilkan formulir dengan nomor pengiriman otomatis. |
| 5. Super Admin, Admin, atau Gudang mengisi tanggal keluar dan catatan. | 6. Sistem menampilkan daftar batch yang tersedia. |
| 7. Super Admin, Admin, atau Gudang menambahkan detail barang berupa batch dan jumlah. | |
| 8. Super Admin, Admin, atau Gudang menekan tombol Simpan. | 9. Sistem memvalidasi tanggal, detail barang, keberadaan batch, jumlah positif, dan kecukupan stok. |
| | 10. Sistem menyimpan pengiriman berstatus DRAFT, memperbarui stok, dan menampilkan pesan keberhasilan. |
| | 11. Sistem memperbarui daftar pengiriman barang. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang menekan tombol Ubah pada pengiriman berstatus DRAFT. | 3a.1. Sistem menampilkan formulir berisi data pengiriman tersebut. |
| 3a.2. Super Admin, Admin, atau Gudang mengubah detail dan menekan tombol Update. | 3a.3. Sistem memvalidasi perubahan, memperbarui stok, dan menyimpan data. |
| | 3a.4. Sistem kembali ke langkah 11. |
| 3b. Super Admin, Admin, atau Gudang menekan tombol Hapus pada pengiriman berstatus DRAFT. | 3b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 3b.2. Super Admin, Admin, atau Gudang mengonfirmasi penghapusan. | 3b.3. Sistem menghapus pengiriman, mengembalikan stok, dan menampilkan pesan keberhasilan. |
| | 3b.4. Sistem kembali ke langkah 11. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 9b. Tanggal kosong, detail kosong, atau jumlah tidak positif. | 9b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 9b.2. Sistem kembali ke langkah 5. |
| 9c. Jumlah barang melebihi stok batch yang tersedia. | 9c.1. Sistem menolak penyimpanan dan menampilkan pesan stok tidak mencukupi. |
| | 9c.2. Sistem kembali ke langkah 7. |
| 3a.3b. Pengiriman yang diubah atau dihapus sudah berstatus COMPLETED. | 3a.3b.1. Sistem memblokir perubahan dan menampilkan pesan bahwa hanya pengiriman DRAFT yang dapat diubah. |
| | 3a.3b.2. Alur berakhir. |

---

### UC-13 Menyelesaikan Barang Keluar

- **ID Use Case:** UC-13
- **Nama Use Case:** Menyelesaikan Barang Keluar
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menyelesaikan pengiriman barang dan memperbarui catatan stok akhir.
- **Prasyarat (Precondition):** Aktor sudah login dan terdapat pengiriman berstatus DRAFT.
- **Kondisi Akhir (Postcondition):** Pengiriman berstatus COMPLETED dan catatan stok diperbarui.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Barang Keluar. | 2. Sistem menampilkan daftar pengiriman beserta statusnya. |
| 3. Super Admin, Admin, atau Gudang memilih pengiriman berstatus DRAFT dan menekan tombol Selesaikan. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin, Admin, atau Gudang mengonfirmasi penyelesaian. | 6. Sistem memvalidasi status dan detail pengiriman. |
| | 7. Sistem memperbarui stok sesuai detail pengiriman. |
| | 8. Sistem mengubah status pengiriman menjadi COMPLETED. |
| | 9. Sistem menampilkan pesan keberhasilan penyelesaian. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan konfirmasi. | 3a.1. Sistem membatalkan proses penyelesaian. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Pengiriman yang dipilih sudah berstatus COMPLETED. | 6b.1. Sistem memblokir penyelesaian dan menampilkan pesan bahwa pengiriman tidak dapat diselesaikan. |
| | 6b.2. Alur berakhir. |
| 6c. Detail pengiriman tidak valid. | 6c.1. Sistem menolak penyelesaian dan menampilkan pesan validasi. |
| | 6c.2. Sistem kembali ke langkah 2. |

### UC-14 Melihat Stok Realtime

- **ID Use Case:** UC-14
- **Nama Use Case:** Melihat Stok Realtime
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan jumlah stok terkini per produk.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Informasi stok realtime tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Manajemen Stok. | 2. Sistem menampilkan tab Stok Real-time berisi daftar produk aktif. |
| 3. Super Admin, Admin, atau Gudang mencari produk pada tab Stok Real-time. | 4. Sistem menampilkan produk yang sesuai kata kunci beserta total stok dan jumlah batch. |
| 5. Super Admin, Admin, atau Gudang menekan tombol muat ulang. | 6. Sistem memperbarui data stok realtime. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2a. Belum terdapat produk aktif dengan stok. | 2a.1. Sistem menampilkan daftar kosong. |
| | 2a.2. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2b. Sesi aktor berakhir saat data diminta. | 2b.1. Sistem menampilkan halaman login. |
| | 2b.2. Alur berakhir. |
| 2c. Terjadi kegagalan pengambilan data stok. | 2c.1. Sistem menampilkan pesan bahwa data stok gagal dimuat. |
| | 2c.2. Alur berakhir. |

---

### UC-15 Melihat Mutasi Stok

- **ID Use Case:** UC-15
- **Nama Use Case:** Melihat Mutasi Stok
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan riwayat mutasi stok yang berasal dari barang masuk, barang keluar, dan penyesuaian stok.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Riwayat mutasi stok tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Manajemen Stok. | 2. Sistem menampilkan halaman Manajemen Stok. |
| 3. Super Admin, Admin, atau Gudang memilih tab Transaksi Stok. | 4. Sistem menampilkan daftar mutasi stok dari barang masuk, barang keluar, dan penyesuaian yang disetujui. |
| 5. Super Admin, Admin, atau Gudang menekan tombol muat ulang. | 6. Sistem memperbarui daftar mutasi stok. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Belum terdapat mutasi stok. | 4a.1. Sistem menampilkan pesan "Tidak ada data mutasi." |
| | 4a.2. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4b. Sesi aktor berakhir saat data diminta. | 4b.1. Sistem menampilkan halaman login. |
| | 4b.2. Alur berakhir. |
| 4c. Terjadi kegagalan pengambilan data mutasi. | 4c.1. Sistem menampilkan pesan bahwa data mutasi gagal dimuat. |
| | 4c.2. Alur berakhir. |

---

### UC-16 Melihat Stok Rendah

- **ID Use Case:** UC-16
- **Nama Use Case:** Melihat Stok Rendah
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan produk dengan stok di bawah atau sama dengan batas minimum.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Daftar produk dengan stok rendah tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Manajemen Stok. | 2. Sistem menampilkan halaman Manajemen Stok. |
| 3. Super Admin, Admin, atau Gudang memilih tab Stok Menipis. | 4. Sistem menampilkan produk yang total stoknya tidak melebihi stok minimum. |
| 5. Super Admin, Admin, atau Gudang menekan tombol muat ulang. | 6. Sistem memperbarui daftar produk dengan stok rendah. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Tidak ada produk dengan stok rendah. | 4a.1. Sistem menampilkan daftar kosong. |
| | 4a.2. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4b. Sesi aktor berakhir saat data diminta. | 4b.1. Sistem menampilkan halaman login. |
| | 4b.2. Alur berakhir. |
| 4c. Terjadi kegagalan pengambilan data stok rendah. | 4c.1. Sistem menampilkan pesan bahwa data stok rendah gagal dimuat. |
| | 4c.2. Alur berakhir. |

---

### UC-17 Membuat Stock Adjustment

- **ID Use Case:** UC-17
- **Nama Use Case:** Membuat Stock Adjustment
- **Aktor:** Super Admin dan Admin; Gudang hanya melalui API karena tombol pembuatan disembunyikan pada antarmuka.
- **Tujuan:** Mencatat penyesuaian stok yang menunggu persetujuan.
- **Prasyarat (Precondition):** Aktor sudah login dan produk yang akan disesuaikan tersedia.
- **Kondisi Akhir (Postcondition):** Penyesuaian stok tersimpan berstatus PENDING.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu Stock Adjustment. | 2. Sistem menampilkan daftar penyesuaian stok beserta statusnya. |
| 3. Super Admin atau Admin menekan tombol Buat Penyesuaian. | 4. Sistem menampilkan formulir penyesuaian stok. |
| 5. Super Admin atau Admin memilih produk, jenis penyesuaian, jumlah, dan alasan. | 6. Sistem menampilkan stok saat ini sebagai acuan. |
| 7. Super Admin atau Admin menekan tombol Simpan. | 8. Sistem memvalidasi produk, jenis, jumlah positif, alasan wajib, dan hasil stok tidak negatif. |
| | 9. Sistem menyimpan penyesuaian berstatus PENDING. |
| | 10. Sistem menampilkan pesan "Penyesuaian stok dibuat dan menunggu persetujuan". |
| | 11. Sistem memperbarui daftar penyesuaian. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Gudang melihat menu Stock Adjustment. | 3a.1. Sistem menyembunyikan tombol Buat Penyesuaian dan tombol persetujuan. |
| | 3a.2. Sistem mengizinkan Gudang melihat daftar penyesuaian. |
| | 3a.3. Alur berakhir. |
| 3b. Gudang mengajukan penyesuaian stok melalui API. | 3b.1. Sistem memvalidasi dan menyimpan penyesuaian berstatus PENDING. |
| | 3b.2. Sistem kembali ke langkah 11. |
| 3c. Super Admin atau Admin membatalkan pengisian formulir. | 3c.1. Sistem menutup formulir tanpa menyimpan data. |
| | 3c.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 8b. Produk, jenis, jumlah, atau alasan tidak valid. | 8b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 8b.2. Sistem kembali ke langkah 5. |
| 8c. Hasil penyesuaian menghasilkan stok negatif. | 8c.1. Sistem menolak penyimpanan dan menampilkan pesan stok tidak boleh negatif. |
| | 8c.2. Sistem kembali ke langkah 5. |

---

### UC-18 Menyetujui Stock Adjustment

- **ID Use Case:** UC-18
- **Nama Use Case:** Menyetujui Stock Adjustment
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Menyetujui penyesuaian stok dan menerapkan perubahan stok.
- **Prasyarat (Precondition):** Aktor sudah login dan terdapat penyesuaian berstatus PENDING.
- **Kondisi Akhir (Postcondition):** Penyesuaian berstatus APPROVED dan stok diperbarui.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu Stock Adjustment. | 2. Sistem menampilkan daftar penyesuaian yang menunggu persetujuan. |
| 3. Super Admin atau Admin memilih penyesuaian berstatus PENDING dan menekan tombol Setujui. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin atau Admin mengonfirmasi persetujuan. | 6. Sistem memvalidasi status penyesuaian dan stok saat ini. |
| | 7. Sistem memperbarui jumlah stok sesuai penyesuaian. |
| | 8. Sistem mengubah status penyesuaian menjadi APPROVED. |
| | 9. Sistem menampilkan pesan "Adjustment disetujui dan stok berhasil diperbarui". |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin membatalkan konfirmasi. | 3a.1. Sistem membatalkan persetujuan. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Penyesuaian sudah disetujui atau ditolak. | 6b.1. Sistem memblokir persetujuan dan menampilkan pesan bahwa hanya penyesuaian PENDING yang dapat diproses. |
| | 6b.2. Alur berakhir. |
| 6c. Hasil pembaruan stok menghasilkan nilai negatif. | 6c.1. Sistem memblokir persetujuan dan menampilkan pesan stok tidak boleh negatif. |
| | 6c.2. Alur berakhir. |

---

### UC-19 Menolak Stock Adjustment

- **ID Use Case:** UC-19
- **Nama Use Case:** Menolak Stock Adjustment
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Menolak penyesuaian stok tanpa mengubah stok.
- **Prasyarat (Precondition):** Aktor sudah login dan terdapat penyesuaian berstatus PENDING.
- **Kondisi Akhir (Postcondition):** Penyesuaian berstatus REJECTED dan stok tidak berubah.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu Stock Adjustment. | 2. Sistem menampilkan daftar penyesuaian yang menunggu persetujuan. |
| 3. Super Admin atau Admin memilih penyesuaian berstatus PENDING dan menekan tombol Tolak. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin atau Admin mengonfirmasi penolakan. | 6. Sistem memvalidasi status penyesuaian. |
| | 7. Sistem mengubah status penyesuaian menjadi REJECTED. |
| | 8. Sistem menampilkan pesan keberhasilan dan memperbarui daftar. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin membatalkan konfirmasi. | 3a.1. Sistem membatalkan penolakan. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Penyesuaian sudah diproses. | 6b.1. Sistem memblokir penolakan dan menampilkan pesan bahwa hanya penyesuaian PENDING yang dapat diproses. |
| | 6b.2. Alur berakhir. |

---

### UC-20 Membuat Sesi Opname

- **ID Use Case:** UC-20
- **Nama Use Case:** Membuat Sesi Opname
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Membuka sesi stock opname beserta detail produk awal.
- **Prasyarat (Precondition):** Aktor sudah login dan produk aktif tersedia.
- **Kondisi Akhir (Postcondition):** Sesi opname berstatus DRAFT beserta detail produk terbentuk.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Stock Opname. | 2. Sistem menampilkan daftar sesi opname. |
| 3. Super Admin, Admin, atau Gudang menekan tombol Buat Sesi. | 4. Sistem menampilkan formulir tanggal opname. |
| 5. Super Admin, Admin, atau Gudang memilih tanggal opname dan menekan tombol Simpan. | 6. Sistem memvalidasi tanggal opname dan menghasilkan nomor sesi otomatis. |
| | 7. Sistem membuat sesi berstatus DRAFT. |
| | 8. Sistem menghasilkan detail opname untuk produk aktif dan batch terkait. |
| | 9. Sistem menampilkan pesan keberhasilan dan memperbarui daftar sesi. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan pembuatan sesi. | 3a.1. Sistem menutup formulir tanpa menyimpan data. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Tanggal opname kosong. | 6b.1. Sistem menolak pembuatan sesi dan menampilkan pesan "Tanggal opname wajib diisi". |
| | 6b.2. Sistem kembali ke langkah 4. |
| 6c. Terjadi kegagalan penyimpanan sesi. | 6c.1. Sistem membatalkan pembuatan sesi dan menampilkan pesan kegagalan. |
| | 6c.2. Alur berakhir. |

---

### UC-21 Mengisi Hasil Opname

- **ID Use Case:** UC-21
- **Nama Use Case:** Mengisi Hasil Opname
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Mencatat jumlah fisik hasil penghitungan dan selisih stok.
- **Prasyarat (Precondition):** Aktor sudah login dan terdapat sesi opname berstatus DRAFT.
- **Kondisi Akhir (Postcondition):** Hasil opname tersimpan beserta selisihnya.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Stock Opname. | 2. Sistem menampilkan daftar sesi opname. |
| 3. Super Admin, Admin, atau Gudang memilih sesi berstatus DRAFT. | 4. Sistem menampilkan detail opname berisi stok sistem dan stok fisik. |
| 5. Super Admin, Admin, atau Gudang mengisi jumlah fisik per produk atau batch. | 6. Sistem menghitung selisih antara stok fisik dan stok sistem. |
| 7. Super Admin, Admin, atau Gudang menekan tombol Simpan Hasil. | 8. Sistem menyimpan hasil opname dan menampilkan pesan keberhasilan. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin, Admin, atau Gudang mengubah jumlah fisik yang sebelumnya tersimpan. | 5a.1. Sistem memperbarui nilai dan menghitung ulang selisih. |
| | 5a.2. Sistem kembali ke langkah 7. |
| 4a. Sesi opname belum memiliki detail produk. | 4a.1. Sistem menampilkan pesan bahwa tidak ada detail untuk disimpan. |
| | 4a.2. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3b. Sesi opname sudah berstatus COMPLETED. | 3b.1. Sistem memblokir pengisian hasil dan menampilkan pesan bahwa sesi tidak dapat diubah. |
| | 3b.2. Alur berakhir. |
| 5b. Jumlah fisik yang dimasukkan tidak valid. | 5b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 5b.2. Sistem kembali ke langkah 5. |

---

### UC-22 Menyelesaikan Sesi Opname

- **ID Use Case:** UC-22
- **Nama Use Case:** Menyelesaikan Sesi Opname
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menutup sesi opname dan membuat penyesuaian stok untuk selisih yang ditemukan.
- **Prasyarat (Precondition):** Aktor sudah login dan terdapat sesi opname berstatus DRAFT.
- **Kondisi Akhir (Postcondition):** Sesi berstatus COMPLETED dan penyesuaian stok berstatus PENDING terbentuk.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Stock Opname. | 2. Sistem menampilkan daftar sesi opname beserta statusnya. |
| 3. Super Admin, Admin, atau Gudang memilih sesi berstatus DRAFT dan menekan tombol Selesaikan. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin, Admin, atau Gudang mengonfirmasi penyelesaian. | 6. Sistem memvalidasi status sesi dan menghitung selisih tiap detail. |
| | 7. Sistem membuat penyesuaian stok berstatus PENDING untuk setiap selisih. |
| | 8. Sistem mengubah status sesi menjadi COMPLETED. |
| | 9. Sistem menampilkan pesan "Sesi opname berhasil diselesaikan. Adjustment menunggu persetujuan." |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan konfirmasi. | 3a.1. Sistem membatalkan penyelesaian. |
| | 3a.2. Sistem kembali ke langkah 2. |
| 6a. Tidak terdapat selisih pada hasil opname. | 6a.1. Sistem tidak membuat penyesuaian stok. |
| | 6a.2. Sistem kembali ke langkah 8. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Sesi yang dipilih sudah berstatus COMPLETED. | 6b.1. Sistem memblokir penyelesaian dan menampilkan pesan bahwa sesi tidak dapat diselesaikan. |
| | 6b.2. Alur berakhir. |
| 6c. Terjadi kegagalan pembuatan penyesuaian. | 6c.1. Sistem membatalkan penyelesaian dan menampilkan pesan kegagalan. |
| | 6c.2. Alur berakhir. |

### UC-23 Menghapus Sesi Opname

- **ID Use Case:** UC-23
- **Nama Use Case:** Menghapus Sesi Opname
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menghapus sesi opname yang belum selesai beserta detailnya.
- **Prasyarat (Precondition):** Aktor sudah login dan sesi opname berstatus DRAFT tersedia.
- **Kondisi Akhir (Postcondition):** Sesi opname dan detailnya terhapus dari database.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Stock Opname. | 2. Sistem menampilkan daftar sesi opname beserta statusnya. |
| 3. Super Admin, Admin, atau Gudang memilih sesi berstatus DRAFT dan menekan tombol Hapus. | 4. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5. Super Admin, Admin, atau Gudang mengonfirmasi penghapusan. | 6. Sistem memvalidasi status sesi. |
| | 7. Sistem menghapus sesi beserta seluruh detail opname yang terkait. |
| | 8. Sistem menampilkan pesan "Sesi opname berhasil dihapus" dan memperbarui daftar sesi. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan konfirmasi. | 3a.1. Sistem membatalkan penghapusan. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Sesi sudah berstatus COMPLETED. | 6b.1. Sistem memblokir penghapusan dan menampilkan pesan "Sesi opname yang sudah selesai tidak bisa dihapus". |
| | 6b.2. Alur berakhir. |
| 6c. Sesi tidak ditemukan. | 6c.1. Sistem menampilkan pesan "Sesi opname tidak ditemukan". |
| | 6c.2. Alur berakhir. |

---

### UC-24 Monitoring Expired

- **ID Use Case:** UC-24
- **Nama Use Case:** Monitoring Expired
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Memantau batch produk yang sudah kedaluwarsa, akan kedaluwarsa, atau kedaluwarsa pada hari ini.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Data monitoring expired tersaji sesuai filter.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Monitoring Expired. | 2. Sistem menampilkan ringkasan batch mendekati expired, expired hari ini, dan batch yang sudah expired. |
| 3. Super Admin, Admin, atau Gudang memilih kategori monitoring atau mengisi tanggal mulai dan tanggal akhir. | 4. Sistem menampilkan daftar batch sesuai kategori dan rentang tanggal yang dipilih. |
| 5. Super Admin, Admin, atau Gudang mengosongkan filter. | 6. Sistem menampilkan kembali seluruh data expired. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Tidak ada batch yang sesuai filter. | 4a.1. Sistem menampilkan daftar kosong. |
| | 4a.2. Alur berakhir. |
| 4b. Aktor memilih kategori Mendekati Expired. | 4b.1. Sistem menampilkan batch dengan tanggal kedaluwarsa dalam rentang mendekati. |
| | 4b.2. Sistem kembali ke langkah 5. |
| 4c. Aktor memilih kategori Expired Hari Ini. | 4c.1. Sistem menampilkan batch yang tanggal kedaluwarsanya sama dengan tanggal hari ini. |
| | 4c.2. Sistem kembali ke langkah 5. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2b. Sesi aktor berakhir saat data diminta. | 2b.1. Sistem menampilkan halaman login. |
| | 2b.2. Alur berakhir. |
| 2c. Terjadi kegagalan pengambilan data expired. | 2c.1. Sistem menampilkan pesan bahwa data expired gagal dimuat. |
| | 2c.2. Alur berakhir. |

---

### UC-25 Menampilkan Laporan

- **ID Use Case:** UC-25
- **Nama Use Case:** Menampilkan Laporan
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan laporan barang masuk, barang keluar, stok, stok opname, penyesuaian stok, dan expired.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Laporan yang dipilih tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Laporan. | 2. Sistem menampilkan tab laporan Barang Masuk, Barang Keluar, Stok, Stok Opname, Adjustment, dan Expired. |
| 3. Super Admin, Admin, atau Gudang memilih salah satu tab laporan. | 4. Sistem menampilkan data laporan sesuai tab yang dipilih. |
| 5. Super Admin, Admin, atau Gudang mengisi tanggal mulai dan tanggal akhir. | 6. Sistem memperbarui laporan sesuai rentang tanggal. |
| 7. Super Admin, Admin, atau Gudang memilih sesi opname pada tab Stok Opname. | 8. Sistem menampilkan detail stok opname untuk sesi yang dipilih. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Tidak ada data pada tab yang dipilih. | 4a.1. Sistem menampilkan tabel kosong. |
| | 4a.2. Alur berakhir. |
| 5a. Aktor mengosongkan tanggal mulai dan tanggal akhir. | 5a.1. Sistem menampilkan seluruh data pada tab tersebut. |
| | 5a.2. Sistem kembali ke langkah 4. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4b. Sesi aktor berakhir saat laporan diminta. | 4b.1. Sistem menampilkan halaman login. |
| | 4b.2. Alur berakhir. |
| 4c. Terjadi kegagalan pengambilan data laporan. | 4c.1. Sistem menampilkan pesan bahwa laporan gagal dimuat. |
| | 4c.2. Alur berakhir. |

---

### UC-26 Export Laporan PDF

- **ID Use Case:** UC-26
- **Nama Use Case:** Export Laporan PDF
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menghasilkan berkas PDF dari laporan yang sedang ditampilkan.
- **Prasyarat (Precondition):** Aktor sudah login dan menu Laporan dapat diakses.
- **Kondisi Akhir (Postcondition):** Berkas PDF laporan berhasil diunduh.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Laporan. | 2. Sistem menampilkan tab laporan dan data yang sedang aktif. |
| 3. Super Admin, Admin, atau Gudang memilih tab dan filter laporan yang akan diekspor. | 4. Sistem memperbarui data laporan sesuai pilihan. |
| 5. Super Admin, Admin, atau Gudang menekan tombol Export PDF. | 6. Sistem menyusun berkas PDF berisi judul laporan, tanggal cetak, dan tabel data. |
| | 7. Sistem mengunduh berkas dengan nama `laporan-<jenis laporan>-<tanggal>.pdf`. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Laporan yang dipilih tidak memiliki data. | 4a.1. Sistem tetap menyiapkan berkas PDF dengan tabel kosong. |
| | 4a.2. Sistem kembali ke langkah 7. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Terjadi kegagalan pembuatan berkas PDF. | 6b.1. Sistem menampilkan pesan bahwa ekspor laporan gagal. |
| | 6b.2. Alur berakhir. |

---

### UC-27 Mengelola User

- **ID Use Case:** UC-27
- **Nama Use Case:** Mengelola User
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Melihat, menambah, mengubah, dan menghapus akun pengguna sistem.
- **Prasyarat (Precondition):** Aktor sudah login dengan peran Super Admin atau Admin.
- **Kondisi Akhir (Postcondition):** Data user tersimpan sesuai perubahan yang dilakukan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu User Management. | 2. Sistem menampilkan daftar user beserta nama, username, peran, dan status. |
| 3. Super Admin atau Admin menekan tombol Tambah User. | 4. Sistem menampilkan formulir user. |
| 5. Super Admin atau Admin mengisi nama, username, password, peran, dan status. | 6. Sistem menampilkan formulir yang telah terisi. |
| 7. Super Admin atau Admin menekan tombol Simpan. | 8. Sistem memvalidasi kelengkapan data, panjang password minimal enam karakter, keunikan username, dan keabsahan peran. |
| | 9. Sistem menyimpan data user dengan password yang dienkripsi. |
| | 10. Sistem menampilkan pesan "User berhasil dibuat" dan memperbarui daftar user. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin mencari user. | 3a.1. Sistem menampilkan user yang cocok dengan kata kunci. |
| | 3a.2. Sistem kembali ke langkah 2. |
| 3b. Super Admin atau Admin menekan tombol Ubah pada salah satu user. | 3b.1. Sistem menampilkan formulir berisi data user tersebut. |
| | 3b.2. Sistem kembali ke langkah 5. |
| 3c. Super Admin atau Admin menekan tombol Hapus pada salah satu user. | 3c.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| | 3c.2. Sistem kembali ke langkah 5a bila konfirmasi dibatalkan. |
| 5a. Super Admin atau Admin membatalkan pengisian formulir. | 5a.1. Sistem menutup formulir tanpa menyimpan data. |
| | 5a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 8b. Username sudah digunakan. | 8b.1. Sistem menolak penyimpanan dan menampilkan pesan "Username sudah digunakan". |
| | 8b.2. Sistem kembali ke langkah 5. |
| 8c. Password kurang dari enam karakter. | 8c.1. Sistem menolak penyimpanan dan menampilkan pesan "Password minimal 6 karakter". |
| | 8c.2. Sistem kembali ke langkah 5. |
| 8d. Super Admin atau Admin menghapus akun sendiri. | 8d.1. Sistem memblokir penghapusan dan menampilkan pesan "Tidak bisa menghapus akun sendiri". |
| | 8d.2. Alur berakhir. |
| 8e. Super Admin atau Admin menghapus Super Admin terakhir. | 8e.1. Sistem memblokir penghapusan dan menampilkan pesan "Tidak bisa menghapus superadmin terakhir". |
| | 8e.2. Alur berakhir. |

---

### UC-28 Mengubah Password User

- **ID Use Case:** UC-28
- **Nama Use Case:** Mengubah Password User
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Mengganti password akun pengguna tertentu.
- **Prasyarat (Precondition):** Aktor sudah login dengan peran Super Admin atau Admin dan user yang akan diubah tersedia.
- **Kondisi Akhir (Postcondition):** Password user berhasil diperbarui.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu User Management. | 2. Sistem menampilkan daftar user. |
| 3. Super Admin atau Admin memilih user dan menekan tombol Ubah Password. | 4. Sistem menampilkan formulir password lama dan password baru. |
| 5. Super Admin atau Admin mengisi password lama dan password baru. | 6. Sistem menampilkan formulir yang telah terisi. |
| 7. Super Admin atau Admin menekan tombol Ubah Password. | 8. Sistem memvalidasi keberadaan user, kecocokan password lama, dan panjang password baru minimal enam karakter. |
| | 9. Sistem menyimpan password baru dalam bentuk terenkripsi. |
| | 10. Sistem menampilkan pesan "Password berhasil diubah" dan menutup formulir. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin membatalkan formulir. | 3a.1. Sistem menutup formulir tanpa mengubah password. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 8b. Password lama dan password baru kosong. | 8b.1. Sistem menolak perubahan dan menampilkan pesan "Password lama dan password baru wajib diisi". |
| | 8b.2. Sistem kembali ke langkah 5. |
| 8c. Password lama tidak sesuai. | 8c.1. Sistem menolak perubahan dan menampilkan pesan "Password lama salah". |
| | 8c.2. Sistem kembali ke langkah 5. |
| 8d. Password baru kurang dari enam karakter. | 8d.1. Sistem menolak perubahan dan menampilkan pesan "Password baru minimal 6 karakter". |
| | 8d.2. Sistem kembali ke langkah 5. |

---

### UC-29 Melihat Notifikasi

- **ID Use Case:** UC-29
- **Nama Use Case:** Melihat Notifikasi
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Melihat notifikasi yang relevan dengan peran aktor.
- **Prasyarat (Precondition):** Aktor sudah login.
- **Kondisi Akhir (Postcondition):** Daftar notifikasi sesuai peran tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Riwayat Notifikasi. | 2. Sistem menampilkan daftar notifikasi sesuai peran aktor beserta jumlah notifikasi belum dibaca. |
| 3. Super Admin, Admin, atau Gudang memilih filter tipe notifikasi atau status baca. | 4. Sistem menampilkan notifikasi sesuai filter yang dipilih. |
| 5. Super Admin, Admin, atau Gudang mengetik kata kunci pada kolom pencarian. | 6. Sistem menampilkan notifikasi yang cocok dengan kata kunci. |
| 7. Super Admin, Admin, atau Gudang berpindah halaman. | 8. Sistem menampilkan halaman notifikasi berikutnya. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Tidak ada notifikasi yang sesuai filter. | 4a.1. Sistem menampilkan pesan "Belum ada notifikasi yang sesuai dengan filter." |
| | 4a.2. Alur berakhir. |
| 5a. Aktor mengosongkan kata kunci pencarian. | 5a.1. Sistem menampilkan kembali seluruh notifikasi sesuai filter aktif. |
| | 5a.2. Sistem kembali ke langkah 4. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2b. Sesi aktor berakhir saat notifikasi diminta. | 2b.1. Sistem menampilkan halaman login. |
| | 2b.2. Alur berakhir. |
| 2c. Terjadi kegagalan pengambilan notifikasi. | 2c.1. Sistem menampilkan pesan bahwa notifikasi gagal dimuat. |
| | 2c.2. Alur berakhir. |

---

### UC-30 Menandai Notifikasi Dibaca

- **ID Use Case:** UC-30
- **Nama Use Case:** Menandai Notifikasi Dibaca
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Mengubah status notifikasi dari belum dibaca menjadi sudah dibaca.
- **Prasyarat (Precondition):** Aktor sudah login dan terdapat notifikasi belum dibaca.
- **Kondisi Akhir (Postcondition):** Notifikasi yang dipilih atau seluruh notifikasi berstatus sudah dibaca.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Riwayat Notifikasi. | 2. Sistem menampilkan daftar notifikasi belum dibaca. |
| 3. Super Admin, Admin, atau Gudang memilih notifikasi belum dibaca. | 4. Sistem menandai notifikasi tersebut sebagai sudah dibaca. |
| | 5. Sistem menampilkan pesan "Notifikasi ditandai sudah dibaca". |
| | 6. Sistem memperbarui status notifikasi dan jumlah notifikasi belum dibaca. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang menekan tombol Tandai Semua Dibaca. | 3a.1. Sistem menandai seluruh notifikasi milik peran aktor sebagai sudah dibaca. |
| | 3a.2. Sistem menampilkan pesan "Semua notifikasi ditandai sudah dibaca". |
| | 3a.3. Sistem kembali ke langkah 6. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4b. Notifikasi sudah berstatus dibaca. | 4b.1. Sistem tidak mengubah status notifikasi. |
| | 4b.2. Alur berakhir. |
| 4c. Terjadi kegagalan saat menandai notifikasi. | 4c.1. Sistem menampilkan pesan "Gagal menandai notifikasi". |
| | 4c.2. Alur berakhir. |

---

### UC-31 Generate Notifikasi

- **ID Use Case:** UC-31
- **Nama Use Case:** Generate Notifikasi
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Menghasilkan notifikasi otomatis dari kondisi stok, expired, persetujuan, dan penyesuaian stok.
- **Prasyarat (Precondition):** Aktor sudah login dengan peran Super Admin atau Admin.
- **Kondisi Akhir (Postcondition):** Notifikasi baru tersimpan dan daftar notifikasi diperbarui.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu Riwayat Notifikasi. | 2. Sistem menampilkan daftar notifikasi dan tombol Generate. |
| 3. Super Admin atau Admin menekan tombol Generate. | 4. Sistem memvalidasi peran aktor. |
| | 5. Sistem memeriksa stok menipis, stok habis, batch mendekati expired, batch expired, persetujuan yang menunggu, dan penyesuaian yang menunggu. |
| | 6. Sistem menyimpan notifikasi baru untuk kondisi yang memenuhi. |
| | 7. Sistem menampilkan pesan "Berhasil menghasilkan <jumlah> notifikasi baru". |
| | 8. Sistem memperbarui daftar notifikasi. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6a. Tidak ada kondisi baru yang memenuhi. | 6a.1. Sistem tidak menyimpan notifikasi baru. |
| | 6a.2. Sistem menampilkan pesan "Berhasil menghasilkan 0 notifikasi baru". |
| | 6a.3. Sistem kembali ke langkah 8. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4b. Gudang mencoba menghasilkan notifikasi melalui API. | 4b.1. Sistem menolak permintaan dan menampilkan pesan bahwa hanya Super Admin dan Admin yang dapat menghasilkan notifikasi. |
| | 4b.2. Alur berakhir. |
| 6c. Terjadi kegagalan saat menghasilkan notifikasi. | 6c.1. Sistem menampilkan pesan "Gagal menghasilkan notifikasi". |
| | 6c.2. Alur berakhir. |

## 3. Ringkasan Hasil Pekerjaan

### 3.1 Rekap Umum

- Jumlah use case final: 31 use case, terdiri dari UC-01 sampai UC-31.
- Seluruh use case memiliki `Skenario Utama (Basic Flow)`, `Skenario Alternatif (Alternative Flow)`, dan `Skenario Pengecualian (Exception Flow)`.
- Jumlah aktor: tiga, yaitu Super Admin, Admin, dan Gudang.
- Use case dengan akses Super Admin dan Admin saja: UC-18, UC-19, UC-27, UC-28, dan UC-31.
- Use case dengan keterbatasan Gudang: UC-05, UC-06, UC-07, UC-08, dan UC-17.
- Pekerjaan ini hanya menghasilkan dokumen final ini; tidak ada perubahan pada kode frontend, backend, database, Use Case Diagram, maupun kebutuhan fungsional.

### 3.2 Daftar Use Case dan Aktor

| ID | Nama Use Case | Aktor | Skenario Alternatif | Skenario Pengecualian |
|---|---|---|---|---|
| UC-01 | Login | Super Admin, Admin, Gudang | Ya | Ya |
| UC-02 | Logout | Super Admin, Admin, Gudang | Ya | Ya |
| UC-03 | Melihat Profil Sendiri | Super Admin, Admin, Gudang | Ya | Ya |
| UC-04 | Melihat Dashboard | Super Admin, Admin, Gudang | Ya | Ya |
| UC-05 | Mengelola Produk | Super Admin, Admin; Gudang hanya melihat | Ya | Ya |
| UC-06 | Mengelola Kategori | Super Admin, Admin; Gudang hanya melihat | Ya | Ya |
| UC-07 | Mengelola Supplier | Super Admin, Admin; Gudang hanya melihat | Ya | Ya |
| UC-08 | Mengelola Rak/Lokasi | Super Admin, Admin; Gudang hanya melihat | Ya | Ya |
| UC-09 | Monitoring Batch | Super Admin, Admin, Gudang | Ya | Ya |
| UC-10 | Mengelola Barang Masuk | Super Admin, Admin, Gudang | Ya | Ya |
| UC-11 | Menyelesaikan Barang Masuk | Super Admin, Admin, Gudang | Ya | Ya |
| UC-12 | Mengelola Barang Keluar | Super Admin, Admin, Gudang | Ya | Ya |
| UC-13 | Menyelesaikan Barang Keluar | Super Admin, Admin, Gudang | Ya | Ya |
| UC-14 | Melihat Stok Realtime | Super Admin, Admin, Gudang | Ya | Ya |
| UC-15 | Melihat Mutasi Stok | Super Admin, Admin, Gudang | Ya | Ya |
| UC-16 | Melihat Stok Rendah | Super Admin, Admin, Gudang | Ya | Ya |
| UC-17 | Membuat Stock Adjustment | Super Admin, Admin; Gudang melalui API | Ya | Ya |
| UC-18 | Menyetujui Stock Adjustment | Super Admin, Admin | Ya | Ya |
| UC-19 | Menolak Stock Adjustment | Super Admin, Admin | Ya | Ya |
| UC-20 | Membuat Sesi Opname | Super Admin, Admin, Gudang | Ya | Ya |
| UC-21 | Mengisi Hasil Opname | Super Admin, Admin, Gudang | Ya | Ya |
| UC-22 | Menyelesaikan Sesi Opname | Super Admin, Admin, Gudang | Ya | Ya |
| UC-23 | Menghapus Sesi Opname | Super Admin, Admin, Gudang | Ya | Ya |
| UC-24 | Monitoring Expired | Super Admin, Admin, Gudang | Ya | Ya |
| UC-25 | Menampilkan Laporan | Super Admin, Admin, Gudang | Ya | Ya |
| UC-26 | Export Laporan PDF | Super Admin, Admin, Gudang | Ya | Ya |
| UC-27 | Mengelola User | Super Admin, Admin | Ya | Ya |
| UC-28 | Mengubah Password User | Super Admin, Admin | Ya | Ya |
| UC-29 | Melihat Notifikasi | Super Admin, Admin, Gudang | Ya | Ya |
| UC-30 | Menandai Notifikasi Dibaca | Super Admin, Admin, Gudang | Ya | Ya |
| UC-31 | Generate Notifikasi | Super Admin, Admin | Ya | Ya |

### 3.3 Ringkasan Skenario Alternatif dan Pengecualian

- Skenario alternatif yang paling umum adalah pembatalan konfirmasi, pencarian atau filter tanpa hasil, perubahan data yang sudah ada, mode edit, dan keterbatasan Gudang yang hanya dapat melihat data.
- Skenario pengecualian yang paling umum adalah sesi berakhir, data tidak ditemukan, validasi gagal, status data tidak memungkinkan, dan kegagalan pengambilan atau penyimpanan data.
- Pengecualian khusus yang tercatat antara lain:
  - UC-10 dan UC-17: duplikasi nomor batch atau data.
  - UC-11, UC-13, UC-17, UC-18, dan UC-21: stok tidak boleh negatif atau jumlah tidak valid.
  - UC-21, UC-22, dan UC-23: sesi opname sudah COMPLETED sehingga tidak dapat diubah, diselesaikan, atau dihapus.
  - UC-27: Super Admin atau Admin tidak dapat menghapus akun sendiri atau Super Admin terakhir.
  - UC-28: password lama salah atau password baru terlalu pendek.
  - UC-31: permintaan generate oleh Gudang ditolak.

### 3.4 Ketidaksesuaian Dokumen dan Implementasi

- **Fitur pada SRS yang belum terimplementasi:**
  - Modul Disposal/Pemusnahan tidak ditemukan pada rute, pengontrol, tampilan, maupun skema database.
  - Brand CRUD tidak tersedia sebagai modul tersendiri.
  - Warehouse CRUD dan Satuan CRUD tidak tersedia; data master yang ada hanya Produk, Kategori, Supplier, Rak/Lokasi, dan Batch.
  - Multi-gudang belum didukung karena stok masih dicatat per produk dan batch tanpa pembeda lokasi gudang utama.
  - Purchase Order dan Delivery Order hanya tercantum dalam `PLAN_PO_DO.md` sebagai rencana, bukan modul operasional.
- **Autentikasi dan otorisasi:**
  - `AuthService::login()` menyimpan identitas sesi sebagai `id_user`, sedangkan `AuthFilter` masih memeriksa `session('id')`.
  - `LoginController` tidak meneruskan kode status dari `AuthService`, sehingga respons gagal login berisiko tampil sebagai respons sukses.
  - Penerapan peran belum merata: grup rute `users` memakai `auth:admin`, sementara rute lain sebagian hanya memakai `auth` dan melakukan pengecekan admin secara manual di dalam metode.
- **Kesesuaian hak akses tampilan dan backend:**
  - Gudang dapat membuat stock adjustment melalui API, tetapi tombol pembuatan tidak tersedia pada antarmuka.
  - Tombol Tambah, Ubah, dan Hapus pada data master sudah disembunyikan dari Gudang, tetapi pembatasan backend tidak konsisten di semua modul.
  - Tombol approve, reject, dan Generate notifikasi sudah disembunyikan dari Gudang; Generate notifikasi juga diblokir secara eksplisit oleh backend.
  - Menu User Management hanya tersedia untuk Super Admin dan Admin, sesuai grup rute `users`.
- **Status dan konsistensi data:**
  - Backend memakai status `COMPLETED` untuk sesi opname selesai, tetapi beberapa kondisi tampilan masih memeriksa `'completed'` atau `'Complete'`, sehingga tombol pengubahan atau penghapusan berisiko tetap muncul.
  - `User.vue` tidak konsisten antara `form.name` dan `nama` saat inisialisasi, pengisian, dan pengiriman data.
- **Ekspor laporan dan komponen:**
  - `Laporan.vue` mengimpor `jsPDF` dan `html2canvas`, tetapi keduanya tidak tercantum sebagai dependensi langsung pada `frontend/package.json`; `html2pdf.js` tercantum tetapi tidak dipakai pada alur ekspor tersebut.
  - Endpoint backend `laporan/export-pdf` tersedia, tetapi ekspor pada antarmuka dilakukan di sisi klien sehingga endpoint tersebut tidak menjadi bagian dari alur utama.
  - `ReportSignature.vue` tersedia di proyek tetapi tidak digunakan pada halaman laporan.
- **Dokumen lama:**
  - `docs/USE_CASE_SCENARIO_WMS.md` memuat 55 use case dan tabel alternatif tiga kolom, sehingga tidak sesuai dengan finalisasi 31 use case dan format dua kolom pada dokumen ini.
  - `docs/USECASE_SKENARIO.md` memuat 31 use case dan matriks akses yang menjadi dasar daftar final, tetapi beberapa tabel skenario masih perlu diseragamkan ke format final.

