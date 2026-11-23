# Skenario Use Case Sistem Warehouse Management System (WMS) CV. Putra Sumber Sari

- **Nama Sistem:** PSS Warehouse Management System (PSS WMS)
- **Jenis Dokumen:** Final Use Case Scenario untuk Laporan Kerja Praktik
- **Versi:** 1.1
- **Tanggal:** 18 November 2026
- **Sumber Acuan:** `docs/USECASE_SKENARIO.md`, `blackbox_testing/report/SRS_WMS_PSS_FILLED.docx`, `backend/app`, `frontend/src`, dan `database/psswms_db.sql`
- **Status:** Hasil final review dan revisi; masalah implementasi dicatat terpisah tanpa mengubah aplikasi

---

## 1. Pendahuluan

### 1.1 Tujuan Dokumen

Dokumen ini menyajikan skenario use case final PSS WMS. Setiap skenario disusun dari tindakan aktor dan respons sistem yang dapat diverifikasi terhadap Use Case Diagram, kebutuhan fungsional, frontend, backend, dan skema basis data. Basic Flow disajikan untuk seluruh use case. Alternative Flow dan Exception Flow hanya dicantumkan apabila kondisinya memang relevan dan dapat dibuktikan dari sumber acuan.

### 1.2 Metode Pemeriksaan

Pemeriksaan dilakukan dengan membandingkan dokumen sebelumnya terhadap rute dan layanan backend, komponen frontend, skema basis data, Use Case Diagram, dan SRS. Penomoran dan referensi langkah ditelusuri ulang. Penulisan yang bersifat dugaan, pesan validasi yang tidak ada pada kode, serta kondisi database yang tidak didefinisikan dihapus atau dipindahkan ke daftar temuan.

### 1.3 Ruang Lingkup

Ruang lingkup mencakup autentikasi, dashboard, data master produk, kategori, supplier, dan rak/lokasi, monitoring batch, barang masuk, barang keluar, manajemen stok, penyesuaian stok, stock opname, monitoring kedaluwarsa, laporan, ekspor PDF, administrasi pengguna, dan notifikasi.

Fitur yang tidak dijadikan use case karena tidak terdapat pada Use Case Diagram final dan tidak diimplementasikan adalah Purchase Order, Delivery Order, pemusnahan atau disposal, pengelolaan brand sebagai data master, pengelolaan satuan, pengelolaan gudang, multi-gudang, pemindaian barcode, reserved stock, integrasi sistem eksternal, penjualan, pemasaran, pembayaran, dan pembukuan keuangan.

### 1.4 Asumsi Umum

- Semua use case selain UC-01 diawali dalam kondisi aktor sudah memiliki sesi aktif. Penanganan sesi tidak aktif dilakukan secara lintas modul oleh lapisan autentikasi dan tidak diulang sebagai Exception Flow pada setiap use case.
- Status master data dan pengguna yang digunakan sistem adalah `AKTIF` dan `NONAKTIF`.
- Status penerimaan, pengiriman, dan sesi opname adalah `DRAFT` dan `COMPLETED`.
- Status penyesuaian stok adalah `PENDING`, `APPROVED`, dan `REJECTED`.
- Tipe penyesuaian stok pada antarmuka adalah `INCREASE` dan `DECREASE`.
- Nilai status dituliskan apa adanya sesuai implementasi; terjemahan seperti Draft, Selesai, Menunggu, Disetujui, dan Ditolak merupakan label tampilan.

### 1.5 Identitas Aktor dan Hak Akses

- **Super Admin:** memiliki hak akses tertinggi, termasuk pengelolaan data master, transaksi, persetujuan penyesuaian stok, stock opname, laporan, administrasi pengguna, dan generate notifikasi.
- **Admin:** memiliki hak akses operasional gudang dan persetujuan penyesuaian stok, termasuk pengelolaan data master, administrasi pengguna, dan generate notifikasi.
- **Gudang:** dapat melihat data master, memantau batch, melakukan transaksi barang masuk dan barang keluar, melakukan stock opname, melihat stok, melihat laporan, serta melihat dan menandai notifikasi. Tombol pengelolaan data master, tombol buat dan persetujuan penyesuaian stok, menu User Management, dan tombol generate notifikasi tidak tersedia pada antarmuka. Kemampuan Gudang melalui API untuk membuat penyesuaian stok dicatat sebagai temuan terpisah.
- **Sistem:** aktor sekunder yang menghasilkan nomor dokumen, menghitung ringkasan dan selisih, mengelola sesi, menyimpan data, serta menampilkan respons dan pesan validasi.

### 1.6 Daftar Use Case

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
- UC-17 Membuat Stock Adjustment — Super Admin, Admin
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
- **Tujuan:** Memberikan akses kepada pengguna yang memiliki kredensial valid dan status aktif.
- **Prasyarat (Precondition):** Akun pengguna sudah terdaftar.
- **Kondisi Akhir (Postcondition):** Sesi pengguna terbentuk.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka halaman login. | 2. Sistem menampilkan formulir username dan password. |
| 3. Super Admin, Admin, atau Gudang memasukkan username dan password lalu menekan tombol LOGIN. | 4. Sistem memeriksa bahwa username dan password tidak kosong. |
| | 5. Sistem memverifikasi kredensial dan status aktif akun. |
| | 6. Sistem membentuk sesi dan menyimpan `logged_in`, `id_user`, `name`, `username`, dan `role`. |
| | 7. Sistem menampilkan halaman dashboard. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang mengosongkan username. | 3a.1. Sistem menampilkan pesan "Username masih kosong". |
| | 3a.2. Sistem kembali ke langkah 3. |
| 3b. Super Admin, Admin, atau Gudang mengosongkan password. | 3b.1. Sistem menampilkan pesan "Password masih kosong". |
| | 3b.2. Sistem kembali ke langkah 3. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5b. Super Admin, Admin, atau Gudang memasukkan kredensial yang salah. | 5b.1. Sistem menolak login dan menampilkan pesan "Username atau password salah". |
| | 5b.2. Sistem kembali ke langkah 3. |
| 5c. Super Admin, Admin, atau Gudang menggunakan akun dengan status `NONAKTIF`. | 5c.1. Sistem menolak login dan menampilkan pesan "Akun sudah dinonaktifkan. Silakan hubungi administrator." |
| | 5c.2. Alur berakhir. |

---

### UC-02 Logout

- **ID Use Case:** UC-02
- **Nama Use Case:** Logout
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Mengakhiri sesi aktor.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Sesi aktor dihapus.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang menekan tombol Logout pada navbar. | 2. Sistem menghapus data sesi. |
| | 3. Sistem menampilkan halaman login. |

---

### UC-03 Melihat Profil Sendiri

- **ID Use Case:** UC-03
- **Nama Use Case:** Melihat Profil Sendiri
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan identitas aktor yang sedang login.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Identitas aktor tersaji pada navbar.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka aplikasi. | 2. Sistem membaca identitas dari sesi dan data pengguna. |
| | 3. Sistem menampilkan nama dan peran aktor pada navbar. |

---

### UC-04 Melihat Dashboard

- **ID Use Case:** UC-04
- **Nama Use Case:** Melihat Dashboard
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan ringkasan kondisi gudang.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Data ringkasan dashboard tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Dashboard. | 2. Sistem mengambil data ringkasan, grafik tren, produk terlaris, dan produk mendekati expired. |
| | 3. Sistem menampilkan kartu ringkasan, grafik, dan tabel. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2a. Super Admin, Admin, atau Gudang membuka dashboard sebelum terdapat transaksi selesai. | 2a.1. Sistem menampilkan nilai nol dan daftar kosong pada bagian yang terkait. |
| | 2a.2. Alur berakhir. |

---

### UC-05 Mengelola Produk

- **ID Use Case:** UC-05
- **Nama Use Case:** Mengelola Produk
- **Aktor:** Super Admin dan Admin; Gudang hanya melihat
- **Tujuan:** Mengelola data produk gudang.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif dan kategori serta supplier tersedia.
- **Kondisi Akhir (Postcondition):** Data produk tersimpan sesuai perubahan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Produk. | 2. Sistem menampilkan daftar produk dan statistik produk. |
| 3. Super Admin, Admin, atau Gudang mencari produk atau memilih halaman daftar. | 4. Sistem menampilkan hasil sesuai kata kunci dan halaman yang dipilih. |
| 5. Super Admin atau Admin menekan tombol Tambah Produk. | 6. Sistem menampilkan formulir produk. |
| 7. Super Admin atau Admin mengisi nama, SKU, deskripsi, supplier, merek, kategori, satuan, stok minimum, dan status. | 8. Sistem menampilkan formulir yang telah terisi. |
| 9. Super Admin atau Admin menekan tombol Simpan. | 10. Sistem memvalidasi nama, SKU, kategori, dan supplier. |
| | 11. Sistem memeriksa keunikan SKU. |
| | 12. Sistem menyimpan produk dan menampilkan pesan keberhasilan. |
| | 13. Sistem memperbarui daftar produk. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin atau Admin menekan tombol Ubah pada sebuah produk. | 5a.1. Sistem menampilkan formulir berisi data produk tersebut. |
| 5a.2. Super Admin atau Admin mengubah data dan menekan tombol Simpan. | 5a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 5a.4. Sistem kembali ke langkah 13. |
| 5b. Super Admin atau Admin menekan tombol Hapus pada produk. | 5b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5b.2. Super Admin atau Admin mengonfirmasi penghapusan. | 5b.3. Sistem menghapus produk apabila tidak memiliki batch stok. |
| | 5b.4. Sistem kembali ke langkah 13. |
| 5c. Gudang membuka menu Produk. | 5c.1. Sistem menyembunyikan tombol Tambah, Ubah, dan Hapus. |
| | 5c.2. Sistem mengizinkan Gudang melihat daftar dan statistik produk. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 10b. Nama, SKU, kategori, atau supplier kosong. | 10b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi field wajib. |
| | 10b.2. Sistem kembali ke langkah 7. |
| 11b. SKU yang dimasukkan sudah digunakan. | 11b.1. Sistem menolak penyimpanan dan menampilkan pesan "SKU code sudah digunakan". |
| | 11b.2. Sistem kembali ke langkah 7. |
| 5b.3b. Produk yang akan dihapus masih memiliki batch stok. | 5b.3b.1. Sistem memblokir penghapusan dan menampilkan pesan "Produk tidak bisa dihapus karena masih memiliki batch stok. Nonaktifkan produk saja." |
| | 5b.3b.2. Alur berakhir. |

---

### UC-06 Mengelola Kategori

- **ID Use Case:** UC-06
- **Nama Use Case:** Mengelola Kategori
- **Aktor:** Super Admin dan Admin; Gudang hanya melihat
- **Tujuan:** Mengelola data kategori produk.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Data kategori tersimpan sesuai perubahan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Kategori. | 2. Sistem menampilkan daftar kategori. |
| 3. Super Admin, Admin, atau Gudang mencari kategori atau mengubah urutan daftar. | 4. Sistem menampilkan hasil sesuai kata kunci dan urutan yang dipilih. |
| 5. Super Admin atau Admin menekan tombol Tambah Kategori. | 6. Sistem menampilkan formulir dengan kode kategori otomatis. |
| 7. Super Admin atau Admin mengisi nama dan status kategori. | 8. Sistem menampilkan formulir yang telah terisi. |
| 9. Super Admin atau Admin menekan tombol Simpan. | 10. Sistem memvalidasi nama, batas panjang nama, dan keunikan nama serta kode. |
| | 11. Sistem menyimpan kategori dan menampilkan pesan keberhasilan. |
| | 12. Sistem memperbarui daftar kategori. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin atau Admin menekan tombol Ubah pada kategori. | 5a.1. Sistem menampilkan formulir berisi data kategori tersebut. |
| 5a.2. Super Admin atau Admin mengubah data dan menekan tombol Simpan. | 5a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 5a.4. Sistem kembali ke langkah 12. |
| 5b. Super Admin atau Admin menekan tombol Hapus pada kategori. | 5b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5b.2. Super Admin atau Admin mengonfirmasi penghapusan. | 5b.3. Sistem menghapus kategori apabila tidak digunakan produk. |
| | 5b.4. Sistem kembali ke langkah 12. |
| 5c. Gudang membuka menu Kategori. | 5c.1. Sistem menyembunyikan tombol Tambah, Ubah, dan Hapus. |
| | 5c.2. Sistem mengizinkan Gudang melihat daftar kategori. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 10b. Nama kategori kosong atau melebihi batas panjang. | 10b.1. Sistem menolak penyimpanan dan menampilkan pesan "Nama kategori wajib diisi" atau "Nama kategori maksimal 200 karakter". |
| | 10b.2. Sistem kembali ke langkah 7. |
| 10c. Nama atau kode kategori sudah digunakan. | 10c.1. Sistem menolak penyimpanan dan menampilkan pesan "Nama kategori sudah digunakan" atau "Kode kategori sudah digunakan". |
| | 10c.2. Sistem kembali ke langkah 7. |
| 5b.3b. Kategori masih digunakan oleh produk. | 5b.3b.1. Sistem memblokir penghapusan dan menampilkan pesan bahwa kategori harus dinonaktifkan. |
| | 5b.3b.2. Alur berakhir. |

---

### UC-07 Mengelola Supplier

- **ID Use Case:** UC-07
- **Nama Use Case:** Mengelola Supplier
- **Aktor:** Super Admin dan Admin; Gudang hanya melihat
- **Tujuan:** Mengelola data supplier.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Data supplier tersimpan sesuai perubahan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Supplier. | 2. Sistem menampilkan daftar supplier. |
| 3. Super Admin, Admin, atau Gudang mencari supplier pada daftar. | 4. Sistem menampilkan hasil sesuai kata kunci. |
| 5. Super Admin atau Admin menekan tombol Tambah Supplier. | 6. Sistem menampilkan formulir supplier. |
| 7. Super Admin atau Admin mengisi kode, nama, telepon, alamat, dan status aktif supplier. | 8. Sistem menampilkan formulir yang telah terisi. |
| 9. Super Admin atau Admin menekan tombol Simpan. | 10. Sistem memvalidasi kode dan nama wajib serta keunikan kode. |
| | 11. Sistem menyimpan supplier dan menampilkan pesan keberhasilan. |
| | 12. Sistem memperbarui daftar supplier. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin atau Admin menekan tombol Ubah pada supplier. | 5a.1. Sistem menampilkan formulir berisi data supplier tersebut. |
| 5a.2. Super Admin atau Admin mengubah data dan menekan tombol Simpan. | 5a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 5a.4. Sistem kembali ke langkah 12. |
| 5b. Super Admin atau Admin menekan tombol Hapus pada supplier. | 5b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5b.2. Super Admin atau Admin mengonfirmasi penghapusan. | 5b.3. Sistem menghapus supplier apabila tidak memiliki transaksi inbound. |
| | 5b.4. Sistem kembali ke langkah 12. |
| 5c. Gudang membuka menu Supplier. | 5c.1. Sistem menyembunyikan tombol Tambah, Ubah, dan Hapus. |
| | 5c.2. Sistem mengizinkan Gudang melihat daftar supplier. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 10b. Kode atau nama supplier kosong. | 10b.1. Sistem menolak penyimpanan dan menampilkan pesan "Field supplier_code dan name wajib diisi". |
| | 10b.2. Sistem kembali ke langkah 7. |
| 10c. Kode supplier sudah digunakan. | 10c.1. Sistem menolak penyimpanan dan menampilkan pesan "Kode supplier sudah digunakan". |
| | 10c.2. Sistem kembali ke langkah 7. |
| 5b.3b. Supplier masih memiliki transaksi inbound. | 5b.3b.1. Sistem memblokir penghapusan dan menampilkan pesan "Supplier tidak bisa dihapus karena masih memiliki transaksi inbound. Nonaktifkan saja." |
| | 5b.3b.2. Alur berakhir. |

---

### UC-08 Mengelola Rak/Lokasi

- **ID Use Case:** UC-08
- **Nama Use Case:** Mengelola Rak/Lokasi
- **Aktor:** Super Admin dan Admin; Gudang hanya melihat
- **Tujuan:** Mengelola data rak atau lokasi penyimpanan.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Data rak/lokasi tersimpan sesuai perubahan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Rak / Lokasi. | 2. Sistem menampilkan daftar rak/lokasi. |
| 3. Super Admin, Admin, atau Gudang mencari rak pada daftar. | 4. Sistem menampilkan hasil sesuai kata kunci. |
| 5. Super Admin atau Admin menekan tombol Tambah Rak. | 6. Sistem menampilkan formulir rak/lokasi. |
| 7. Super Admin atau Admin mengisi kode rak, nama, dan status. | 8. Sistem menampilkan formulir yang telah terisi. |
| 9. Super Admin atau Admin menekan tombol Simpan. | 10. Sistem memvalidasi kode wajib, batas panjang kode dan nama, serta keunikan kode. |
| | 11. Sistem menyimpan rak/lokasi dan menampilkan pesan keberhasilan. |
| | 12. Sistem memperbarui daftar rak/lokasi. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin atau Admin menekan tombol Ubah pada rak/lokasi. | 5a.1. Sistem menampilkan formulir berisi data rak/lokasi tersebut. |
| 5a.2. Super Admin atau Admin mengubah data dan menekan tombol Simpan. | 5a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 5a.4. Sistem kembali ke langkah 12. |
| 5b. Super Admin atau Admin menekan tombol Hapus pada rak/lokasi. | 5b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5b.2. Super Admin atau Admin mengonfirmasi penghapusan. | 5b.3. Sistem menghapus rak/lokasi apabila tidak digunakan batch produk. |
| | 5b.4. Sistem kembali ke langkah 12. |
| 5c. Gudang membuka menu Rak / Lokasi. | 5c.1. Sistem menyembunyikan tombol Tambah, Ubah, dan Hapus. |
| | 5c.2. Sistem mengizinkan Gudang melihat daftar rak/lokasi. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 10b. Kode rak kosong, panjang melebihi batas, atau kode sudah digunakan. | 10b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi kode rak. |
| | 10b.2. Sistem kembali ke langkah 7. |
| 5b.3b. Rak/lokasi masih digunakan oleh batch produk. | 5b.3b.1. Sistem memblokir penghapusan dan menampilkan pesan bahwa rak harus dinonaktifkan. |
| | 5b.3b.2. Alur berakhir. |

---

### UC-09 Monitoring Batch

- **ID Use Case:** UC-09
- **Nama Use Case:** Monitoring Batch
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Memantau batch produk dan sisa hari menuju kedaluwarsa.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Data batch tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Batch. | 2. Sistem menampilkan statistik dan daftar batch. |
| 3. Super Admin, Admin, atau Gudang mencari batch atau memilih halaman daftar. | 4. Sistem menampilkan hasil sesuai kata kunci dan halaman yang dipilih. |
| 5. Super Admin, Admin, atau Gudang memilih satu batch. | 6. Sistem menampilkan detail batch beserta sisa hari menuju kedaluwarsa. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2a. Super Admin, Admin, atau Gudang membuka menu Batch sebelum ada data. | 2a.1. Sistem menampilkan statistik bernilai nol dan daftar kosong. |
| | 2a.2. Alur berakhir. |
| 5a. Super Admin, Admin, atau Gudang kembali ke daftar batch. | 5a.1. Sistem menampilkan daftar batch. |
| | 5a.2. Sistem kembali ke langkah 3. |

---

### UC-10 Mengelola Barang Masuk

- **ID Use Case:** UC-10
- **Nama Use Case:** Mengelola Barang Masuk
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Membuat dan mengelola penerimaan barang berstatus `DRAFT`.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif, supplier aktif, dan produk tersedia.
- **Kondisi Akhir (Postcondition):** Penerimaan barang tersimpan berstatus `DRAFT`.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Barang Masuk. | 2. Sistem menampilkan daftar penerimaan barang. |
| 3. Super Admin, Admin, atau Gudang menekan tombol Tambah Barang Masuk. | 4. Sistem menampilkan formulir dengan nomor penerimaan otomatis. |
| 5. Super Admin, Admin, atau Gudang memilih supplier dan tanggal masuk. | 6. Sistem menampilkan produk yang sesuai dengan supplier tersebut. |
| 7. Super Admin, Admin, atau Gudang mengisi detail barang berupa produk, nomor batch, jumlah, tanggal kedaluwarsa, dan rak. | 8. Sistem menampilkan detail yang telah diisi. |
| 9. Super Admin, Admin, atau Gudang menekan tombol Simpan. | 10. Sistem memvalidasi tanggal, supplier aktif, detail barang, jumlah positif, dan kesesuaian produk dengan supplier. |
| | 11. Sistem memeriksa duplikasi nomor batch untuk produk yang sama di dalam satu penerimaan. |
| | 12. Sistem menyimpan penerimaan berstatus `DRAFT` dan menampilkan pesan "Inbound berhasil dibuat". |
| | 13. Sistem memperbarui daftar penerimaan barang. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang menekan tombol Ubah pada penerimaan berstatus `DRAFT`. | 3a.1. Sistem menampilkan formulir berisi data penerimaan tersebut. |
| 3a.2. Super Admin, Admin, atau Gudang mengubah data dan menekan tombol Simpan. | 3a.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 3a.4. Sistem kembali ke langkah 13. |
| 3b. Super Admin, Admin, atau Gudang menekan tombol Hapus pada penerimaan berstatus `DRAFT`. | 3b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 3b.2. Super Admin, Admin, atau Gudang mengonfirmasi penghapusan. | 3b.3. Sistem menghapus penerimaan dan menampilkan pesan keberhasilan. |
| | 3b.4. Sistem kembali ke langkah 13. |
| 3c. Super Admin, Admin, atau Gudang membatalkan pengisian formulir. | 3c.1. Sistem menutup formulir tanpa menyimpan data. |
| | 3c.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 10b. Tanggal kosong, supplier tidak aktif, detail kosong, atau jumlah tidak positif. | 10b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 10b.2. Sistem kembali ke langkah 5. |
| 11b. Nomor batch untuk produk yang sama digunakan lebih dari sekali di dalam satu penerimaan. | 11b.1. Sistem menolak penyimpanan dan menampilkan pesan duplikat batch untuk produk yang sama. |
| | 11b.2. Sistem kembali ke langkah 7. |
| 3a.3b. Super Admin, Admin, atau Gudang mengubah penerimaan yang sudah `COMPLETED`. | 3a.3b.1. Sistem memblokir perubahan dan menampilkan pesan "Hanya inbound dengan status DRAFT yang bisa diubah". |
| | 3a.3b.2. Alur berakhir. |
| 3b.3b. Super Admin, Admin, atau Gudang menghapus penerimaan yang sudah `COMPLETED`. | 3b.3b.1. Sistem memblokir penghapusan dan menampilkan pesan "Hanya inbound dengan status DRAFT yang bisa dihapus". |
| | 3b.3b.2. Alur berakhir. |

---

### UC-11 Menyelesaikan Barang Masuk

- **ID Use Case:** UC-11
- **Nama Use Case:** Menyelesaikan Barang Masuk
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menyelesaikan penerimaan barang dan menambah stok batch.
- **Prasyarat (Precondition):** Terdapat penerimaan berstatus `DRAFT`.
- **Kondisi Akhir (Postcondition):** Penerimaan berstatus `COMPLETED` dan stok batch bertambah.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Barang Masuk. | 2. Sistem menampilkan daftar penerimaan beserta statusnya. |
| 3. Super Admin, Admin, atau Gudang memilih penerimaan berstatus `DRAFT` dan menekan tombol Selesaikan. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin, Admin, atau Gudang mengonfirmasi penyelesaian. | 6. Sistem memvalidasi detail, nomor batch, jumlah, tanggal kedaluwarsa, dan rak. |
| | 7. Sistem mencari batch dengan produk dan nomor batch yang sama. |
| | 8. Sistem menambah jumlah batch yang sudah ada atau membuat batch baru apabila belum ada. |
| | 9. Sistem menghubungkan detail penerimaan dengan batch yang bersangkutan. |
| | 10. Sistem mengubah status penerimaan menjadi `COMPLETED`. |
| | 11. Sistem menampilkan pesan "Inbound berhasil diselesaikan dan stok telah ditambahkan". |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan konfirmasi. | 3a.1. Sistem membatalkan proses penyelesaian. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Super Admin, Admin, atau Gudang memilih penerimaan yang sudah `COMPLETED`. | 6b.1. Sistem memblokir penyelesaian dan menampilkan pesan "Hanya inbound dengan status DRAFT yang bisa diselesaikan". |
| | 6b.2. Alur berakhir. |
| 6c. Detail penerimaan kosong atau nomor batch belum diisi. | 6c.1. Sistem menolak penyelesaian dan menampilkan pesan validasi detail atau nomor batch. |
| | 6c.2. Sistem kembali ke langkah 3. |
| 6d. Tanggal kedaluwarsa atau rak tidak sesuai dengan batch existing. | 6d.1. Sistem menolak penyelesaian dan menampilkan pesan ketidaksesuaian data batch. |
| | 6d.2. Sistem kembali ke langkah 3. |

---

### UC-12 Mengelola Barang Keluar

- **ID Use Case:** UC-12
- **Nama Use Case:** Mengelola Barang Keluar
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Membuat dan mengelola pengiriman barang berstatus `DRAFT`.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif dan batch dengan stok tersedia.
- **Kondisi Akhir (Postcondition):** Pengiriman tersimpan berstatus `DRAFT` dan stok batch telah disesuaikan pada saat penyimpanan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Barang Keluar. | 2. Sistem menampilkan daftar pengiriman barang. |
| 3. Super Admin, Admin, atau Gudang menekan tombol Tambah Barang Keluar. | 4. Sistem menampilkan formulir dengan nomor pengiriman otomatis. |
| 5. Super Admin, Admin, atau Gudang mengisi tanggal keluar dan catatan. | 6. Sistem menampilkan daftar batch yang tersedia. |
| 7. Super Admin, Admin, atau Gudang menambahkan detail berupa batch dan jumlah. | 8. Sistem menampilkan detail yang telah diisi. |
| 9. Super Admin, Admin, atau Gudang menekan tombol Simpan. | 10. Sistem memvalidasi tanggal, detail, keberadaan batch, jumlah positif, dan kecukupan stok. |
| | 11. Sistem menyimpan pengiriman berstatus `DRAFT`, mengurangi stok batch sesuai detail, dan menampilkan pesan "Outbound berhasil dibuat". |
| | 12. Sistem memperbarui daftar pengiriman barang. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang mengubah pengiriman berstatus `DRAFT`. | 3a.1. Sistem memulihkan stok sesuai detail lama. |
| | 3a.2. Sistem menyimpan detail baru dan mengurangi stok sesuai detail tersebut. |
| | 3a.3. Sistem kembali ke langkah 12. |
| 3b. Super Admin, Admin, atau Gudang menghapus pengiriman berstatus `DRAFT`. | 3b.1. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 3b.2. Super Admin, Admin, atau Gudang mengonfirmasi penghapusan. | 3b.3. Sistem memulihkan stok, menghapus pengiriman, dan menampilkan pesan "Outbound berhasil dihapus". |
| | 3b.4. Sistem kembali ke langkah 12. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 10b. Tanggal atau detail kosong, batch tidak ditemukan, atau jumlah tidak positif. | 10b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 10b.2. Sistem kembali ke langkah 7. |
| 10c. Jumlah barang melebihi stok batch yang tersedia. | 10c.1. Sistem menolak penyimpanan dan menampilkan pesan stok tidak mencukupi yang menyertakan nomor batch, stok tersedia, dan jumlah yang diminta. |
| | 10c.2. Sistem kembali ke langkah 7. |
| 3a.3b. Super Admin, Admin, atau Gudang mengubah pengiriman yang sudah `COMPLETED`. | 3a.3b.1. Sistem memblokir perubahan dan menampilkan pesan "Hanya outbound dengan status DRAFT yang bisa diubah". |
| | 3a.3b.2. Alur berakhir. |
| 3b.3b. Super Admin, Admin, atau Gudang menghapus pengiriman yang sudah `COMPLETED`. | 3b.3b.1. Sistem memblokir penghapusan dan menampilkan pesan "Hanya outbound dengan status DRAFT yang bisa dihapus". |
| | 3b.3b.2. Alur berakhir. |

---

### UC-13 Menyelesaikan Barang Keluar

- **ID Use Case:** UC-13
- **Nama Use Case:** Menyelesaikan Barang Keluar
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menyelesaikan pengiriman barang berstatus `DRAFT`.
- **Prasyarat (Precondition):** Terdapat pengiriman berstatus `DRAFT`.
- **Kondisi Akhir (Postcondition):** Pengiriman berstatus `COMPLETED`.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Barang Keluar. | 2. Sistem menampilkan daftar pengiriman beserta statusnya. |
| 3. Super Admin, Admin, atau Gudang memilih pengiriman berstatus `DRAFT` dan menekan tombol Selesaikan. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin, Admin, atau Gudang mengonfirmasi penyelesaian. | 6. Sistem memvalidasi status pengiriman. |
| | 7. Sistem mengubah status pengiriman menjadi `COMPLETED`. |
| | 8. Sistem menampilkan pesan keberhasilan penyelesaian. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan konfirmasi. | 3a.1. Sistem membatalkan proses penyelesaian. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Super Admin, Admin, atau Gudang memilih pengiriman yang sudah `COMPLETED`. | 6b.1. Sistem memblokir penyelesaian dan menampilkan pesan "Hanya outbound dengan status DRAFT yang bisa diselesaikan". |
| | 6b.2. Alur berakhir. |

**Catatan implementasi UC-13:** pemeriksaan kode menunjukkan proses penyelesaian juga memanggil pembaruan stok sehingga pengurangan stok terjadi lagi setelah pengurangan saat pembuatan pada UC-12. Perilaku ini dicatat sebagai masalah implementasi pada Bagian 3 dan tidak dijadikan langkah alur normal di atas.

---

### UC-14 Melihat Stok Realtime

- **ID Use Case:** UC-14
- **Nama Use Case:** Melihat Stok Realtime
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan total stok terkini per produk.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Informasi stok realtime tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Manajemen Stok. | 2. Sistem menampilkan tab Stok Real-time berisi produk aktif dengan total stok dan status stok. |
| 3. Super Admin, Admin, atau Gudang menekan tombol muat ulang. | 4. Sistem memperbarui data stok realtime. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 2a. Super Admin, Admin, atau Gudang membuka tab Stok Real-time sebelum ada produk aktif dengan stok. | 2a.1. Sistem menampilkan daftar kosong. |
| | 2a.2. Alur berakhir. |

---

### UC-15 Melihat Mutasi Stok

- **ID Use Case:** UC-15
- **Nama Use Case:** Melihat Mutasi Stok
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan riwayat mutasi stok.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Riwayat mutasi tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Manajemen Stok. | 2. Sistem menampilkan halaman Manajemen Stok. |
| 3. Super Admin, Admin, atau Gudang memilih tab Transaksi Stok. | 4. Sistem menampilkan mutasi dari data barang masuk, barang keluar, dan penyesuaian berstatus `APPROVED`. |
| 5. Super Admin, Admin, atau Gudang menekan tombol muat ulang. | 6. Sistem memperbarui daftar mutasi stok. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Super Admin, Admin, atau Gudang membuka tab Transaksi Stok sebelum ada mutasi. | 4a.1. Sistem menampilkan pesan "Tidak ada data mutasi." |
| | 4a.2. Alur berakhir. |

---

### UC-16 Melihat Stok Rendah

- **ID Use Case:** UC-16
- **Nama Use Case:** Melihat Stok Rendah
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan produk dengan stok tidak melebihi batas minimum.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Daftar produk dengan stok rendah tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Manajemen Stok. | 2. Sistem menampilkan halaman Manajemen Stok. |
| 3. Super Admin, Admin, atau Gudang memilih tab Stok Menipis. | 4. Sistem menampilkan produk aktif dengan total stok kurang dari atau sama dengan stok minimum. |
| 5. Super Admin, Admin, atau Gudang menekan tombol muat ulang. | 6. Sistem memperbarui daftar produk dengan stok rendah. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Super Admin, Admin, atau Gudang membuka tab Stok Menipis ketika seluruh stok aman. | 4a.1. Sistem menampilkan pesan "Semua stok dalam kondisi aman." |
| | 4a.2. Alur berakhir. |

---

### UC-17 Membuat Stock Adjustment

- **ID Use Case:** UC-17
- **Nama Use Case:** Membuat Stock Adjustment
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Mencatat penyesuaian stok berstatus `PENDING`.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif dan batch produk tersedia.
- **Kondisi Akhir (Postcondition):** Penyesuaian stok tersimpan berstatus `PENDING`.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu Stock Adjustment. | 2. Sistem menampilkan daftar penyesuaian stok beserta statusnya. |
| 3. Super Admin atau Admin menekan tombol Buat Penyesuaian. | 4. Sistem menampilkan formulir batch, tipe, jumlah, dan alasan. |
| 5. Super Admin atau Admin memilih batch, tipe `INCREASE` atau `DECREASE`, jumlah, dan alasan. | 6. Sistem menampilkan formulir yang telah terisi. |
| 7. Super Admin atau Admin menekan tombol Simpan. | 8. Sistem memvalidasi tipe, jumlah bulat positif, dan alasan wajib. |
| | 9. Sistem menyimpan penyesuaian berstatus `PENDING`. |
| | 10. Sistem menampilkan pesan "Penyesuaian stok dibuat dan menunggu persetujuan". |
| | 11. Sistem memperbarui daftar penyesuaian. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin membatalkan pengisian formulir. | 3a.1. Sistem menutup formulir tanpa menyimpan data. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 8b. Batch tidak tersedia untuk produk yang dipilih. | 8b.1. Sistem menolak penyimpanan dan menampilkan pesan "Tidak ditemukan batch untuk produk ini". |
| | 8b.2. Sistem kembali ke langkah 5. |
| 8c. Tipe, jumlah, atau alasan tidak valid. | 8c.1. Sistem menolak penyimpanan dan menampilkan pesan validasi tipe, quantity, atau alasan. |
| | 8c.2. Sistem kembali ke langkah 5. |

**Catatan ruang lingkup UC-17:** Gudang tidak memiliki tombol Buat Penyesuaian pada antarmuka. Kemampuan Gudang untuk mengirim permintaan langsung ke endpoint API merupakan temuan implementasi dan tidak dijadikan bagian dari skenario aktor antarmuka.

---

### UC-18 Menyetujui Stock Adjustment

- **ID Use Case:** UC-18
- **Nama Use Case:** Menyetujui Stock Adjustment
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Menyetujui penyesuaian dan memperbarui stok batch.
- **Prasyarat (Precondition):** Terdapat penyesuaian berstatus `PENDING`.
- **Kondisi Akhir (Postcondition):** Penyesuaian berstatus `APPROVED` dan stok diperbarui.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu Stock Adjustment. | 2. Sistem menampilkan daftar penyesuaian berstatus `PENDING`. |
| 3. Super Admin atau Admin memilih penyesuaian dan menekan tombol Setujui. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin atau Admin mengonfirmasi persetujuan. | 6. Sistem memvalidasi status penyesuaian dan hasil stok. |
| | 7. Sistem menerapkan perubahan jumlah pada batch. |
| | 8. Sistem mengubah status menjadi `APPROVED` dan mencatat penyetuju. |
| | 9. Sistem menampilkan pesan "Adjustment disetujui dan stok berhasil diperbarui". |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin membatalkan konfirmasi. | 3a.1. Sistem membatalkan persetujuan. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Penyesuaian tidak berstatus `PENDING`. | 6b.1. Sistem memblokir persetujuan dan menampilkan pesan "Hanya adjustment dengan status PENDING yang bisa disetujui". |
| | 6b.2. Alur berakhir. |
| 6c. Hasil penyesuaian menghasilkan stok negatif. | 6c.1. Sistem memblokir persetujuan dan menampilkan pesan "Persetujuan dibatalkan: stok hasil adjustment tidak boleh negatif". |
| | 6c.2. Alur berakhir. |

---

### UC-19 Menolak Stock Adjustment

- **ID Use Case:** UC-19
- **Nama Use Case:** Menolak Stock Adjustment
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Menolak penyesuaian tanpa mengubah stok.
- **Prasyarat (Precondition):** Terdapat penyesuaian berstatus `PENDING`.
- **Kondisi Akhir (Postcondition):** Penyesuaian berstatus `REJECTED`.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu Stock Adjustment. | 2. Sistem menampilkan daftar penyesuaian berstatus `PENDING`. |
| 3. Super Admin atau Admin memilih penyesuaian dan menekan tombol Tolak. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin atau Admin mengonfirmasi penolakan. | 6. Sistem memvalidasi status penyesuaian. |
| | 7. Sistem mengubah status menjadi `REJECTED` dan mencatat pengguna yang menolak. |
| | 8. Sistem menampilkan pesan "Adjustment ditolak". |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin membatalkan konfirmasi. | 3a.1. Sistem membatalkan penolakan. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Penyesuaian tidak berstatus `PENDING`. | 6b.1. Sistem memblokir penolakan dan menampilkan pesan "Hanya adjustment dengan status PENDING yang bisa ditolak". |
| | 6b.2. Alur berakhir. |

---

### UC-20 Membuat Sesi Opname

- **ID Use Case:** UC-20
- **Nama Use Case:** Membuat Sesi Opname
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Membuka sesi opname beserta detail batch awal.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Sesi berstatus `DRAFT` beserta detail batch terbentuk.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Stock Opname. | 2. Sistem menampilkan daftar sesi opname. |
| 3. Super Admin, Admin, atau Gudang menekan tombol Buat Sesi Baru. | 4. Sistem menampilkan formulir tanggal dan catatan opname. |
| 5. Super Admin, Admin, atau Gudang memilih tanggal, mengisi catatan, dan menekan tombol Buat Sesi. | 6. Sistem memvalidasi tanggal opname dan menghasilkan nomor sesi otomatis. |
| | 7. Sistem membuat sesi berstatus `DRAFT`. |
| | 8. Sistem menghasilkan detail untuk setiap batch produk aktif dengan stok fisik awal bernilai nol. |
| | 9. Sistem menampilkan pesan "Sesi opname berhasil dibuat" dan memperbarui daftar sesi. |

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

---

### UC-21 Mengisi Hasil Opname

- **ID Use Case:** UC-21
- **Nama Use Case:** Mengisi Hasil Opname
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Mencatat stok fisik hasil penghitungan per batch.
- **Prasyarat (Precondition):** Terdapat sesi opname berstatus `DRAFT`.
- **Kondisi Akhir (Postcondition):** Detail hasil opname tersimpan beserta selisihnya.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Stock Opname. | 2. Sistem menampilkan daftar sesi opname. |
| 3. Super Admin, Admin, atau Gudang memilih sesi berstatus `DRAFT`. | 4. Sistem menampilkan detail opname berisi stok sistem, stok fisik, selisih, dan catatan. |
| 5. Super Admin, Admin, atau Gudang mengisi stok fisik dan catatan pada satu batch lalu menekan tombol Simpan Hasil. | 6. Sistem memvalidasi identifier sesi dan batch serta status sesi. |
| | 7. Sistem menghitung ulang stok sistem dan selisih berdasarkan batch saat ini. |
| | 8. Sistem menyimpan atau memperbarui detail opname dan menampilkan pesan "Detail opname berhasil ditambahkan". |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 5a. Super Admin, Admin, atau Gudang mengubah stok fisik batch yang sebelumnya tersimpan. | 5a.1. Sistem memperbarui detail yang sama dan menghitung ulang selisih. |
| | 5a.2. Sistem kembali ke langkah 4. |
| 5b. Super Admin, Admin, atau Gudang menekan tombol Simpan Hasil pada sesi tanpa detail. | 5b.1. Sistem menampilkan pesan "Tidak ada detail untuk disimpan." |
| | 5b.2. Alur berakhir. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Identifier sesi atau batch tidak lengkap. | 6b.1. Sistem menolak penyimpanan dan menampilkan pesan "Opname ID dan Batch ID wajib diisi". |
| | 6b.2. Sistem kembali ke langkah 5. |
| 6c. Sesi tidak berstatus `DRAFT`. | 6c.1. Sistem menolak penyimpanan dan menampilkan pesan "Hanya sesi dengan status DRAFT yang bisa ditambahkan detail". |
| | 6c.2. Alur berakhir. |

---

### UC-22 Menyelesaikan Sesi Opname

- **ID Use Case:** UC-22
- **Nama Use Case:** Menyelesaikan Sesi Opname
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menutup sesi opname dan membuat penyesuaian untuk detail yang berselisih.
- **Prasyarat (Precondition):** Terdapat sesi opname berstatus `DRAFT`.
- **Kondisi Akhir (Postcondition):** Sesi berstatus `COMPLETED`; penyesuaian berstatus `PENDING` dibuat hanya untuk detail dengan selisih.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Stock Opname. | 2. Sistem menampilkan daftar sesi opname beserta statusnya. |
| 3. Super Admin, Admin, atau Gudang memilih sesi berstatus `DRAFT` dan menekan tombol Selesaikan. | 4. Sistem menampilkan permintaan konfirmasi. |
| 5. Super Admin, Admin, atau Gudang mengonfirmasi penyelesaian. | 6. Sistem memvalidasi status sesi dan menghitung selisih tiap detail. |
| | 7. Sistem membuat penyesuaian berstatus `PENDING` untuk detail dengan selisih dan alasan dengan awalan `Stock Opname:` diikuti nomor sesi opname. |
| | 8. Sistem mengubah status sesi menjadi `COMPLETED`. |
| | 9. Sistem menampilkan pesan "Sesi opname berhasil diselesaikan. Adjustment menunggu persetujuan." |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan konfirmasi. | 3a.1. Sistem membatalkan penyelesaian. |
| | 3a.2. Sistem kembali ke langkah 2. |
| 6a. Super Admin, Admin, atau Gudang menyelesaikan sesi tanpa selisih pada detail. | 6a.1. Sistem tidak membuat penyesuaian. |
| | 6a.2. Sistem kembali ke langkah 8. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Sesi sudah berstatus `COMPLETED`. | 6b.1. Sistem memblokir penyelesaian dan menampilkan pesan "Sesi opname sudah selesai atau dibatalkan". |
| | 6b.2. Alur berakhir. |
| 6c. Sesi tidak memiliki detail opname. | 6c.1. Sistem menolak penyelesaian dan menampilkan pesan "Tidak ada detail opname untuk diselesaikan". |
| | 6c.2. Alur berakhir. |

---

### UC-23 Menghapus Sesi Opname

- **ID Use Case:** UC-23
- **Nama Use Case:** Menghapus Sesi Opname
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menghapus sesi opname yang belum selesai.
- **Prasyarat (Precondition):** Terdapat sesi opname berstatus `DRAFT`.
- **Kondisi Akhir (Postcondition):** Sesi dan detailnya terhapus.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Stock Opname. | 2. Sistem menampilkan daftar sesi opname beserta statusnya. |
| 3. Super Admin, Admin, atau Gudang memilih sesi berstatus `DRAFT` dan menekan tombol Hapus. | 4. Sistem menampilkan permintaan konfirmasi penghapusan. |
| 5. Super Admin, Admin, atau Gudang mengonfirmasi penghapusan. | 6. Sistem memvalidasi status sesi. |
| | 7. Sistem menghapus detail dan sesi opname. |
| | 8. Sistem menampilkan pesan "Sesi opname berhasil dihapus" dan memperbarui daftar sesi. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang membatalkan konfirmasi. | 3a.1. Sistem membatalkan penghapusan. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Sesi sudah berstatus `COMPLETED`. | 6b.1. Sistem memblokir penghapusan dan menampilkan pesan "Sesi opname yang sudah selesai tidak bisa dihapus". |
| | 6b.2. Alur berakhir. |

---

### UC-24 Monitoring Expired

- **ID Use Case:** UC-24
- **Nama Use Case:** Monitoring Expired
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Memantau batch mendekati expired, expired hari ini, dan sudah expired.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Data monitoring expired tersaji sesuai kategori.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Monitoring Expired. | 2. Sistem menampilkan tab Mendekati Expired, Expired Hari Ini, Sudah Expired, dan Total Batch. |
| 3. Super Admin, Admin, atau Gudang memilih salah satu tab. | 4. Sistem menampilkan batch sesuai kategori yang dipilih. |
| 5. Super Admin, Admin, atau Gudang mengisi tanggal mulai dan tanggal akhir lalu menerapkan filter. | 6. Sistem menampilkan data pada kategori semua sesuai rentang tanggal. |
| 7. Super Admin, Admin, atau Gudang menekan Reset. | 8. Sistem mengosongkan filter dan menampilkan kembali data awal. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Super Admin, Admin, atau Gudang memilih tab yang tidak memiliki batch. | 4a.1. Sistem menampilkan daftar kosong. |
| | 4a.2. Alur berakhir. |

---

### UC-25 Menampilkan Laporan

- **ID Use Case:** UC-25
- **Nama Use Case:** Menampilkan Laporan
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menampilkan laporan yang tersedia pada aplikasi.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Laporan yang dipilih tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Laporan. | 2. Sistem menampilkan tab Barang Masuk, Barang Keluar, Stok, Stock Opname, dan Barang Expired. |
| 3. Super Admin, Admin, atau Gudang memilih salah satu tab. | 4. Sistem menampilkan data laporan sesuai tab yang dipilih. |
| 5. Super Admin, Admin, atau Gudang mengisi tanggal mulai dan tanggal akhir pada tab Barang Masuk, Barang Keluar, atau Barang Expired. | 6. Sistem memperbarui laporan sesuai rentang tanggal. |
| 7. Super Admin, Admin, atau Gudang memilih sesi pada tab Stock Opname. | 8. Sistem menampilkan detail sesi tersebut atau pesan "Pilih sesi opname untuk melihat detail laporan" apabila sesi belum dipilih. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Super Admin, Admin, atau Gudang memilih tab sebelum ada data. | 4a.1. Sistem menampilkan tabel kosong. |
| | 4a.2. Alur berakhir. |
| 5a. Super Admin, Admin, atau Gudang mengosongkan tanggal mulai dan tanggal akhir. | 5a.1. Sistem menampilkan seluruh data pada tab tersebut. |
| | 5a.2. Sistem kembali ke langkah 4. |

---

### UC-26 Export Laporan PDF

- **ID Use Case:** UC-26
- **Nama Use Case:** Export Laporan PDF
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Menghasilkan berkas PDF dari laporan yang ditampilkan pada halaman Laporan.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif dan menu Laporan dapat diakses.
- **Kondisi Akhir (Postcondition):** Berkas PDF diunduh apabila laporan memiliki data.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang memilih tab dan filter laporan pada halaman Laporan. | 2. Sistem menampilkan data laporan sesuai pilihan. |
| 3. Super Admin, Admin, atau Gudang menekan tombol Export PDF. | 4. Sistem menyusun berkas PDF di sisi klien berisi judul laporan, tanggal cetak, dan tabel data. |
| | 5. Sistem mengunduh berkas dengan pola nama `laporan-{tab}-{tanggal}.pdf` dan tanggal berformat `YYYY-MM-DD`. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin, Admin, atau Gudang menekan tombol Export PDF pada laporan tanpa baris data. | 3a.1. Sistem tidak mengunduh berkas. |
| | 3a.2. Alur berakhir. |

---

### UC-27 Mengelola User

- **ID Use Case:** UC-27
- **Nama Use Case:** Mengelola User
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Melihat, menambah, mengubah, dan menghapus akun pengguna.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif dengan peran Super Admin atau Admin.
- **Kondisi Akhir (Postcondition):** Data user tersimpan sesuai perubahan.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu User Management. | 2. Sistem menampilkan daftar user. |
| 3. Super Admin atau Admin menekan tombol Tambah User. | 4. Sistem menampilkan formulir nama lengkap, username, password, peran, dan status. |
| 5. Super Admin atau Admin mengisi formulir dan menekan tombol Simpan. | 6. Sistem memvalidasi nama, username, password, dan peran. |
| | 7. Sistem memeriksa panjang password minimal enam karakter dan keunikan username. |
| | 8. Sistem menyimpan user dengan hash password. |
| | 9. Sistem menampilkan pesan "User berhasil dibuat" dan memperbarui daftar user. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin mencari user. | 3a.1. Sistem menampilkan user yang cocok dengan kata kunci. |
| | 3a.2. Sistem kembali ke langkah 2. |
| 3b. Super Admin atau Admin menekan tombol Ubah pada user. | 3b.1. Sistem menampilkan formulir berisi data user tersebut. |
| 3b.2. Super Admin atau Admin mengubah data dan menekan tombol Simpan. | 3b.3. Sistem memvalidasi dan menyimpan perubahan. |
| | 3b.4. Sistem menampilkan pesan "User berhasil diupdate" dan memperbarui daftar user. |
| 3c. Super Admin atau Admin menekan tombol Hapus pada user. | 3c.1. Sistem menampilkan konfirmasi "Hapus User?" dengan pertanyaan penghapusan user tersebut. |
| 3c.2. Super Admin atau Admin membatalkan konfirmasi. | 3c.3. Sistem membatalkan penghapusan. |
| | 3c.4. Sistem kembali ke langkah 2. |
| 3c.5. Super Admin atau Admin mengonfirmasi penghapusan. | 3c.6. Sistem memvalidasi bahwa user yang dihapus bukan akun sendiri dan bukan Super Admin terakhir. |
| | 3c.7. Sistem menghapus user dan menampilkan pesan "User berhasil dihapus". |
| | 3c.8. Sistem memperbarui daftar user. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Nama, username, atau password kosong; peran tidak valid; atau password kurang dari enam karakter. | 6b.1. Sistem menolak penyimpanan dan menampilkan pesan validasi. |
| | 6b.2. Sistem kembali ke langkah 5. |
| 7b. Username sudah digunakan. | 7b.1. Sistem menolak penyimpanan dan menampilkan pesan "Username sudah digunakan". |
| | 7b.2. Sistem kembali ke langkah 5. |
| 3c.6b. Super Admin atau Admin menghapus akun sendiri. | 3c.6b.1. Sistem memblokir penghapusan dan menampilkan pesan "Tidak bisa menghapus akun sendiri". |
| | 3c.6b.2. Alur berakhir. |
| 3c.6c. Super Admin atau Admin menghapus Super Admin terakhir. | 3c.6c.1. Sistem memblokir penghapusan dan menampilkan pesan "Tidak bisa menghapus superadmin terakhir". |
| | 3c.6c.2. Alur berakhir. |

---

### UC-28 Mengubah Password User

- **ID Use Case:** UC-28
- **Nama Use Case:** Mengubah Password User
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Mengganti password akun pengguna tertentu.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif dengan peran Super Admin atau Admin dan user target tersedia.
- **Kondisi Akhir (Postcondition):** Hash password user target diperbarui.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu User Management. | 2. Sistem menampilkan daftar user. |
| 3. Super Admin atau Admin memilih user dan menekan tombol Ubah Password. | 4. Sistem menampilkan formulir Password Lama dan Password Baru untuk user target. |
| 5. Super Admin atau Admin mengisi kedua kolom dan menekan tombol Ubah Password. | 6. Sistem memeriksa bahwa kedua kolom tidak kosong. |
| | 7. Sistem memeriksa bahwa password baru minimal enam karakter. |
| | 8. Sistem memastikan user target ada. |
| | 9. Sistem mencocokkan password lama dengan hash password user target. |
| | 10. Sistem mengganti hash password user target dan menampilkan pesan "Password berhasil diubah". |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin membatalkan formulir. | 3a.1. Sistem menutup formulir tanpa mengubah password. |
| | 3a.2. Sistem kembali ke langkah 2. |

#### Skenario Pengecualian (Exception Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 6b. Password lama atau password baru kosong. | 6b.1. Sistem menolak perubahan dan menampilkan pesan "Password lama dan password baru wajib diisi". |
| | 6b.2. Sistem kembali ke langkah 5. |
| 7b. Password baru kurang dari enam karakter. | 7b.1. Sistem menolak perubahan dan menampilkan pesan "Password baru minimal 6 karakter". |
| | 7b.2. Sistem kembali ke langkah 5. |
| 9b. Password lama tidak sesuai dengan password user target. | 9b.1. Sistem menolak perubahan dan menampilkan pesan "Password lama salah". |
| | 9b.2. Sistem kembali ke langkah 5. |

---

### UC-29 Melihat Notifikasi

- **ID Use Case:** UC-29
- **Nama Use Case:** Melihat Notifikasi
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Melihat notifikasi yang tersedia bagi aktor.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif.
- **Kondisi Akhir (Postcondition):** Daftar notifikasi tersaji.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang membuka menu Riwayat Notifikasi. | 2. Sistem menampilkan daftar notifikasi sesuai peran dari sesi dan jumlah notifikasi belum dibaca. |
| 3. Super Admin, Admin, atau Gudang memilih filter tipe atau status baca. | 4. Sistem menampilkan notifikasi sesuai filter. |
| 5. Super Admin, Admin, atau Gudang mengetik kata kunci pada kolom pencarian. | 6. Sistem menampilkan notifikasi yang cocok dengan kata kunci. |
| 7. Super Admin, Admin, atau Gudang berpindah halaman. | 8. Sistem menampilkan halaman notifikasi berikutnya. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 4a. Super Admin, Admin, atau Gudang memilih filter yang tidak memiliki notifikasi. | 4a.1. Sistem menampilkan pesan "Belum ada notifikasi yang sesuai dengan filter." |
| | 4a.2. Alur berakhir. |

---

### UC-30 Menandai Notifikasi Dibaca

- **ID Use Case:** UC-30
- **Nama Use Case:** Menandai Notifikasi Dibaca
- **Aktor:** Super Admin, Admin, dan Gudang
- **Tujuan:** Mengubah notifikasi menjadi sudah dibaca.
- **Prasyarat (Precondition):** Terdapat notifikasi belum dibaca.
- **Kondisi Akhir (Postcondition):** Status baca notifikasi berubah menjadi `1`.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin, Admin, atau Gudang memilih notifikasi belum dibaca pada Riwayat Notifikasi atau lonceng navbar. | 2. Sistem mengubah status baca notifikasi menjadi `1`. |
| | 3. Sistem memperbarui jumlah notifikasi belum dibaca. |
| | 4. Sistem menampilkan halaman tujuan notifikasi apabila dibuka dari kartu notifikasi. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1a. Super Admin, Admin, atau Gudang menekan tombol Tandai Semua Dibaca. | 1a.1. Sistem mengubah status baca seluruh notifikasi sesuai peran aktor menjadi `1`. |
| | 1a.2. Sistem menampilkan pesan "Semua notifikasi ditandai sudah dibaca". |
| | 1a.3. Sistem memperbarui jumlah notifikasi belum dibaca. |

---

### UC-31 Generate Notifikasi

- **ID Use Case:** UC-31
- **Nama Use Case:** Generate Notifikasi
- **Aktor:** Super Admin dan Admin
- **Tujuan:** Menghasilkan notifikasi otomatis dari kondisi data saat ini.
- **Prasyarat (Precondition):** Aktor memiliki sesi aktif dengan peran Super Admin atau Admin.
- **Kondisi Akhir (Postcondition):** Notifikasi baru tersimpan sesuai kondisi yang memenuhi.

#### Skenario Utama (Basic Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Super Admin atau Admin membuka menu Riwayat Notifikasi. | 2. Sistem menampilkan daftar notifikasi dan tombol Generate. |
| 3. Super Admin atau Admin menekan tombol Generate. | 4. Sistem memeriksa peran aktor. |
| | 5. Sistem memeriksa stok menipis, stok habis, batch mendekati expired, batch expired, penerimaan `DRAFT`, dan penyesuaian `PENDING`. |
| | 6. Sistem menyimpan notifikasi baru untuk kondisi yang memenuhi dan melewati referensi yang sudah pernah dihasilkan. |
| | 7. Sistem menampilkan pesan `Berhasil menghasilkan $count notifikasi baru` dengan `$count` sebagai jumlah notifikasi yang dihasilkan. |
| | 8. Sistem memperbarui daftar notifikasi. |

#### Skenario Alternatif (Alternative Flow)

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 3a. Super Admin atau Admin menekan tombol Generate saat tidak ada kondisi baru yang memenuhi. | 3a.1. Sistem tidak menyimpan notifikasi baru. |
| | 3a.2. Sistem menampilkan pesan `Berhasil menghasilkan $count notifikasi baru` dengan `$count` bernilai 0. |
| | 3a.3. Sistem kembali ke langkah 8. |

---

## 3. Ringkasan Review dan Temuan Validasi

### 3.1 Ringkasan Perubahan

- Versi dokumen dinaikkan ke 1.1 dan dokumen sebelumnya disimpan sebagai cadangan dengan nama `USE_CASE_SCENARIO_FINAL_SEBELUM_REVIEW_2026-11-18.md` dan `.docx`.
- Seluruh 31 use case dipertahankan sesuai Use Case Diagram; tidak ada use case yang ditambah atau dihapus.
- Basic Flow tetap wajib, tetapi Alternative Flow dan Exception Flow hanya dipertahankan jika kondisinya relevan dan didukung acuan. Beberapa use case kini hanya memiliki Basic Flow.
- Kondisi data kosong ditempatkan sebagai kondisi normal atau alternatif, bukan pengecualian.
- Penomoran dan referensi langkah ditelusuri ulang, termasuk UC-10, UC-11, UC-21, UC-24, dan UC-27.
- UC-12 dan UC-13 dipisahkan: pengurangan stok saat pembuatan dan pengubahan pengiriman dicatat pada UC-12. Pengurangan kedua yang terjadi pada saat penyelesaian tidak dijadikan alur normal dan dipindahkan ke daftar temuan.
- UC-11 diperjelas: duplikasi nomor batch hanya dicegah di dalam satu penerimaan; pada saat penyelesaian, batch existing untuk produk dan nomor batch yang sama ditambah jumlahnya, bukan ditolak.
- UC-17 hanya menempatkan Super Admin dan Admin sebagai aktor antarmuka. Kemampuan Gudang melalui API tidak lagi disamakan dengan tombol antarmuka.
- Atribut produk, kategori, supplier, dan rak/lokasi diselaraskan dengan formulir dan skema basis data aktual.
- UC-27 dilengkapi dengan konfirmasi penghapusan, pembatalan, konfirmasi, validasi akun sendiri dan Super Admin terakhir, pesan, serta pembaruan daftar.
- UC-28 diperjelas: password lama adalah password user target, bukan password aktor, dan penyimpanan menggunakan hash password.
- Status `DRAFT`, `COMPLETED`, `PENDING`, `APPROVED`, `REJECTED`, `AKTIF`, dan `NONAKTIF` digunakan secara konsisten.
- Pesan validasi yang tidak ditemukan pada kode dihapus. Pesan yang tercantum hanya yang memang tersedia pada frontend atau backend.
- Tindakan internal sistem tidak lagi dituliskan pada kolom Aksi Aktor.
- Tabel skenario tetap menggunakan tepat dua kolom, yaitu Aksi Aktor dan Reaksi Sistem.

### 3.2 Masalah Implementasi yang Masih Membutuhkan Validasi

- Autentikasi menyimpan `id_user` saat login, tetapi `AuthFilter` masih memeriksa `session('id')` untuk pemeriksaan status akun tambahan.
- Pesan kosong pada login di frontend berbeda dengan pesan agregat pada backend; validasi langsung ke API dapat menghasilkan pesan yang berbeda dari tampilan.
- Barang keluar mengurangi stok saat pembuatan dan pengubahan, lalu menguranginya kembali saat penyelesaian. Perlu ditetapkan apakah pengurangan terjadi sekali saat pembuatan atau sekali saat penyelesaian.
- Status sesi opname pada backend adalah `COMPLETED`, tetapi sebagian kondisi tombol di frontend masih memeriksa `completed` atau `Complete`, sehingga tombol pengubahan, penghapusan, dan penyimpanan hasil dapat tetap muncul.
- `POST opname/details` menyimpan satu detail per permintaan; tidak ditemukan endpoint Simpan Hasil massal untuk seluruh detail sekaligus.
- Endpoint penyesuaian stok menerima permintaan dari Gudang meskipun tombol pembuatan disembunyikan pada antarmuka. Hak akses API perlu disamakan dengan kebijakan antarmuka.
- Kolom `type` pada `stock_adjustments` diberi komentar `IN / OUT`, tetapi kode menyimpan `INCREASE` dan `DECREASE`.
- Halaman Laporan tidak memiliki tab Adjustment meskipun endpoint laporan adjustment tersedia.
- Tombol Export PDF menggunakan `jsPDF` dan `html2canvas` di sisi klien, tetapi keduanya tidak tercantum sebagai dependensi langsung pada `frontend/package.json`. Endpoint `laporan/export-pdf` hanya mengembalikan JSON dan tidak menghasilkan berkas PDF.
- Komponen `ReportSignature.vue` tersedia tetapi tidak digunakan.
- Formulir Produk menampilkan deskripsi, merek, satuan, dan stok minimum, tetapi struktur basis data memakai kolom `category`, `brand_name`, `unit`, dan `minimum_stock` tanpa referensi foreign key untuk merek dan satuan.
- Formulir Supplier tidak memiliki kolom email dan kontak orang, sedangkan SRS menyebut keduanya; model supplier juga tidak memasukkan keduanya pada `allowedFields`.
- Formulir Kategori tidak memiliki field keterangan meskipun tabel basis data memiliki kolom `description`.
- Penerapan pembatasan penghapusan data master dilakukan di backend, tetapi tidak semua tombol frontend melakukan pemeriksaan yang sama sebelum permintaan dikirim.
- `User.vue` menggunakan `form.name`, sementara inisialisasi dan beberapa bagian lain menggunakan `nama`; perlu diverifikasi konsistensi field sebelum integrasi akhir.
- Pemeriksaan Super Admin terakhir tersedia di backend, tetapi tidak ditemukan pada logika konfirmasi frontend.
- `markRead` untuk satu notifikasi tidak menyaring data berdasarkan peran atau pemilik notifikasi, sedangkan `markAllRead` menyaring berdasarkan peran.
- Generator notifikasi yang tersedia hanya memproses stok menipis, stok habis, mendekati expired, expired, penerimaan `DRAFT`, dan penyesuaian `PENDING`. Fungsi notifikasi penerimaan selesai, pengiriman selesai, dan selisih opname tidak dipanggil dari alur generate.
- Tabel `notifications` tidak ditemukan pada dump skema basis data final meskipun model dan endpoint notifikasi menggunakannya.
- Fitur SRS yang belum tersedia pada implementasi akhir antara lain brand master, unit master, gudang master, multi-gudang, CRUD batch, barcode, reserved stock, tanggal pemusnahan, disposal, laporan pemusnahan, Purchase Order, dan Delivery Order.
- SRS menyebut sejumlah metrik dan struktur data yang berbeda dari kode aktual, termasuk jumlah endpoint, jumlah tabel, field produk, dan format identifier. Data pada dokumen ini mengikuti kode dan skema aktual, bukan angka-angka tersebut.
