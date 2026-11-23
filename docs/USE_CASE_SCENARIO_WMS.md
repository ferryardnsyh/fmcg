# USE CASE SCENARIO WAREHOUSE MANAGEMENT SYSTEM

## CV. Putra Sumber Sari

| Atribut | Keterangan |
|---|---|
| Nama Sistem | PSS Warehouse Management System (PSS WMS) |
| Versi Dokumen | 1.0 |
| Tanggal | 17 November 2026 |
| Penyusun | System Analyst |
| Status | Draf untuk validasi pengguna |
| Sumber Acuan | Kode backend (`backend/app`), skema `database/`, dan frontend Vue (`frontend/src`) |
| Jumlah Use Case | 55 (UC-01 s/d UC-55) |

---

## 1. Pendahuluan

### 1.1 Tujuan Dokumen

Dokumen ini menjelaskan aktor, daftar use case, dan skenario rinci setiap use case pada PSS Warehouse Management System. Setiap use case disusun dalam bentuk aksi aktor dan reaksi sistem agar dapat langsung dipakai sebagai dasar validasi kebutuhan, pengujian black-box, serta pengembangan lanjutan. Seluruh isi disusun berdasarkan fitur dan alur yang benar-benar terimplementasi pada kode saat ini, bukan asumsi kebutuhan.

### 1.2 Ruang Lingkup

Dokumen mencakup 14 area fungsional yang sudah diimplementasikan:

1. Autentikasi dan sesi pengguna.
2. Dashboard dan ringkasan operasional gudang.
3. Master data produk.
4. Master data kategori.
5. Master data supplier.
6. Master data rak/lokasi.
7. Monitoring batch.
8. Transaksi barang masuk (inbound).
9. Transaksi barang keluar (outbound).
10. Manajemen stok (stok realtime, mutasi, stok rendah, stock adjustment).
11. Stock opname.
12. Monitoring barang kedaluwarsa (expired).
13. Laporan dan ekspor PDF.
14. Administrasi pengguna dan notifikasi.

Di luar ruang lingkup:

1. Purchase Order (PO) dan Delivery Order (DO) yang masih berupa rencana pada `PLAN_PO_DO.md`.
2. Integrasi sistem eksternal (ERP, akuntansi, marketplace).
3. Multi-gudang dan multi-cabang.
4. Pemindaian barcode/QR.

### 1.3 Aktor dan Hak Akses

| Aktor | Tipe | Deskripsi |
|---|---|---|
| SUPERADMIN | Primer | Hak penuh: master data, transaksi, persetujuan adjustment, opname, laporan, manajemen pengguna, dan generate notifikasi |
| ADMIN | Primer | Hak operasional dan persetujuan, termasuk manajemen pengguna dan generate notifikasi |
| GUDANG | Primer | Transaksi harian, opname, pemantauan stok/expired, dan laporan; tidak dapat mengubah master data, menyetujui adjustment, mengelola pengguna, atau generate notifikasi |
| Sistem | Sekunder | Menghasilkan nomor dokumen otomatis, menghitung stok realtime, menghitung selisih opname, menghitung status expired, dan menyimpan notifikasi |

Catatan: pembeda SUPERADMIN dan ADMIN pada kode adalah aturan hapus pengguna, yaitu akun sendiri dan SUPERADMIN terakhir tidak boleh dihapus. Pembatasan tombol di frontend menggunakan `v-if="isAdmin"` (hanya menyembunyikan tombol); kontrol backend memakai `denyIfNotAdmin()` pada operasi master data dan persetujuan adjustment.

### 1.4 Definisi dan Singkatan

| Istilah | Definisi |
|---|---|
| WMS | Warehouse Management System |
| SKU | Stock Keeping Unit, kode unik produk |
| Batch/Lot | Kelompok stok produk dengan nomor batch dan tanggal kedaluwarsa tertentu |
| Inbound | Barang masuk dari supplier ke gudang |
| Outbound | Barang keluar dari gudang ke tujuan pengiriman |
| DRAFT | Status dokumen transaksi yang masih dapat diubah/dihapus |
| COMPLETED | Status dokumen transaksi yang sudah diselesaikan |
| PENDING | Status stock adjustment yang menunggu persetujuan |
| APPROVED | Status stock adjustment yang disetujui dan sudah mengubah stok |
| REJECTED | Status stock adjustment yang ditolak |
| Opname | Penghitungan fisik stok dan pembandingan dengan stok sistem |
| Near Expiry | Batch dengan tanggal kedaluwarsa dalam 30 hari ke depan |
| Expired | Batch dengan tanggal kedaluwarsa sebelum hari ini |
| Minimum Stock | Batas minimal stok produk untuk memicu status stok rendah |

### 1.5 Asumsi dan Batasan

1. Seluruh endpoint selain `POST /api/login` memerlukan sesi login.
2. Batch hanya dibuat melalui penyelesaian barang masuk, bukan melalui CRUD manual.
3. Stok opname tidak langsung mengubah stok; selisihnya dibuat sebagai adjustment `PENDING`.
4. Ekspor PDF dilakukan di sisi klien menggunakan `jsPDF`, bukan di server.
5. Notifikasi bergantung pada tabel `notifications` yang statusnya perlu dikonfirmasi (lihat Bagian 5).

---

## 2. Daftar Use Case

| ID | Nama Use Case | Aktor Utama | Modul |
|---|---|---|---|
| UC-01 | Login | SUPERADMIN, ADMIN, GUDANG | Autentikasi |
| UC-02 | Logout | SUPERADMIN, ADMIN, GUDANG | Autentikasi |
| UC-03 | Melihat Profil Sendiri | SUPERADMIN, ADMIN, GUDANG | Autentikasi |
| UC-04 | Melihat Dashboard Operasional | SUPERADMIN, ADMIN, GUDANG | Dashboard |
| UC-05 | Melihat Daftar Produk | SUPERADMIN, ADMIN, GUDANG | Produk |
| UC-06 | Menambah Produk | SUPERADMIN, ADMIN | Produk |
| UC-07 | Mengubah Produk | SUPERADMIN, ADMIN | Produk |
| UC-08 | Menghapus Produk | SUPERADMIN, ADMIN | Produk |
| UC-09 | Melihat Statistik Produk | SUPERADMIN, ADMIN, GUDANG | Produk |
| UC-10 | Melihat Daftar Kategori | SUPERADMIN, ADMIN, GUDANG | Kategori |
| UC-11 | Menambah Kategori | SUPERADMIN, ADMIN | Kategori |
| UC-12 | Mengubah Kategori | SUPERADMIN, ADMIN | Kategori |
| UC-13 | Menghapus Kategori | SUPERADMIN, ADMIN | Kategori |
| UC-14 | Melihat Daftar Supplier | SUPERADMIN, ADMIN, GUDANG | Supplier |
| UC-15 | Menambah Supplier | SUPERADMIN, ADMIN | Supplier |
| UC-16 | Mengubah Supplier | SUPERADMIN, ADMIN | Supplier |
| UC-17 | Menghapus Supplier | SUPERADMIN, ADMIN | Supplier |
| UC-18 | Melihat Daftar Rak | SUPERADMIN, ADMIN, GUDANG | Rak/Lokasi |
| UC-19 | Menambah Rak | SUPERADMIN, ADMIN | Rak/Lokasi |
| UC-20 | Mengubah Rak | SUPERADMIN, ADMIN | Rak/Lokasi |
| UC-21 | Menghapus Rak | SUPERADMIN, ADMIN | Rak/Lokasi |
| UC-22 | Melihat Daftar Batch | SUPERADMIN, ADMIN, GUDANG | Batch |
| UC-23 | Melihat Statistik dan Detail Batch | SUPERADMIN, ADMIN, GUDANG | Batch |
| UC-24 | Melihat Daftar Barang Masuk | SUPERADMIN, ADMIN, GUDANG | Barang Masuk |
| UC-25 | Menambah Barang Masuk | SUPERADMIN, ADMIN, GUDANG | Barang Masuk |
| UC-26 | Mengubah Barang Masuk | SUPERADMIN, ADMIN, GUDANG | Barang Masuk |
| UC-27 | Menghapus Barang Masuk | SUPERADMIN, ADMIN, GUDANG | Barang Masuk |
| UC-28 | Menyelesaikan Barang Masuk | SUPERADMIN, ADMIN, GUDANG | Barang Masuk |
| UC-29 | Melihat Daftar Barang Keluar | SUPERADMIN, ADMIN, GUDANG | Barang Keluar |
| UC-30 | Menambah Barang Keluar | SUPERADMIN, ADMIN, GUDANG | Barang Keluar |
| UC-31 | Mengubah Barang Keluar | SUPERADMIN, ADMIN, GUDANG | Barang Keluar |
| UC-32 | Menghapus Barang Keluar | SUPERADMIN, ADMIN, GUDANG | Barang Keluar |
| UC-33 | Menyelesaikan Barang Keluar | SUPERADMIN, ADMIN, GUDANG | Barang Keluar |
| UC-34 | Melihat Stok Realtime | SUPERADMIN, ADMIN, GUDANG | Manajemen Stok |
| UC-35 | Melihat Mutasi Stok | SUPERADMIN, ADMIN, GUDANG | Manajemen Stok |
| UC-36 | Melihat Stok Rendah | SUPERADMIN, ADMIN, GUDANG | Manajemen Stok |
| UC-37 | Membuat Stock Adjustment | SUPERADMIN, ADMIN, GUDANG (API) | Manajemen Stok |
| UC-38 | Menyetujui Stock Adjustment | SUPERADMIN, ADMIN | Manajemen Stok |
| UC-39 | Menolak Stock Adjustment | SUPERADMIN, ADMIN | Manajemen Stok |
| UC-40 | Membuat Sesi Opname | SUPERADMIN, ADMIN, GUDANG | Stock Opname |
| UC-41 | Melihat Detail Sesi Opname | SUPERADMIN, ADMIN, GUDANG | Stock Opname |
| UC-42 | Mengisi Hasil Opname | SUPERADMIN, ADMIN, GUDANG | Stock Opname |
| UC-43 | Menyelesaikan Sesi Opname | SUPERADMIN, ADMIN, GUDANG | Stock Opname |
| UC-44 | Menghapus Sesi Opname | SUPERADMIN, ADMIN, GUDANG | Stock Opname |
| UC-45 | Melihat Barang Mendekati Expired | SUPERADMIN, ADMIN, GUDANG | Monitoring Expired |
| UC-46 | Melihat Barang Expired dan Filter | SUPERADMIN, ADMIN, GUDANG | Monitoring Expired |
| UC-47 | Menampilkan Laporan Barang Masuk | SUPERADMIN, ADMIN, GUDANG | Laporan |
| UC-48 | Menampilkan Laporan Barang Keluar | SUPERADMIN, ADMIN, GUDANG | Laporan |
| UC-49 | Menampilkan Laporan Stok dan Opname | SUPERADMIN, ADMIN, GUDANG | Laporan |
| UC-50 | Menampilkan Laporan Barang Expired | SUPERADMIN, ADMIN, GUDANG | Laporan |
| UC-51 | Mengekspor Laporan ke PDF | SUPERADMIN, ADMIN, GUDANG | Laporan |
| UC-52 | Mengelola User | SUPERADMIN, ADMIN | Administrasi |
| UC-53 | Mengubah dan Menghapus User | SUPERADMIN, ADMIN | Administrasi |
| UC-54 | Mengubah Password User | SUPERADMIN, ADMIN | Administrasi |
| UC-55 | Mengelola Notifikasi | SUPERADMIN, ADMIN, GUDANG | Notifikasi |

---

## 3. Skenario Use Case

Format setiap use case: nama use case, aktor, deskripsi, prasyarat, kondisi akhir, tabel aksi aktor dan reaksi sistem, serta alur alternatif.

### 3.1 Autentikasi dan Sesi

#### UC-01 Login

**Nama Use Case:** Login
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Pengguna memperoleh sesi terautentikasi untuk mengakses aplikasi.
**Prasyarat:** Pengguna belum login atau sesi sudah berakhir.
**Kondisi Akhir:** Sesi login aktif dan pengguna diarahkan ke dashboard sesuai perannya.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka halaman login dan mengisi username serta password. | Menampilkan form login berisi kolom username dan password. |
| 2. Menekan tombol Login. | Menerima `POST /api/login`; memvalidasi username dan password tidak kosong. |
| 3. Menunggu proses verifikasi. | Mencari pengguna berdasarkan username, memverifikasi password dengan `password_verify`, dan memeriksa status harus `AKTIF`. |
| 4. Menerima hasil login. | Meregenerasi sesi, menyimpan `logged_in`, `id_user`, `name`, `username`, `role`, lalu mengembalikan data pengguna. Frontend mengarahkan ke dashboard. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Username atau password kosong | Sistem menampilkan "Username dan Password wajib diisi". |
| A2 | Pengguna sudah memiliki sesi aktif | Pengguna diarahkan langsung ke dashboard. |
| A3 | Username tidak ditemukan atau password salah | Sistem mengembalikan HTTP 401 "Username atau password salah". |
| A4 | Status pengguna bukan AKTIF | Sistem menampilkan "Akun sudah dinonaktifkan. Silakan hubungi administrator." |
| A5 | Kesalahan server | Sistem mengembalikan HTTP 500 dan pengguna tetap di halaman login. |

#### UC-02 Logout

**Nama Use Case:** Logout
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Pengguna mengakhiri sesi dan keluar dari aplikasi.
**Prasyarat:** Pengguna dalam keadaan login.
**Kondisi Akhir:** Sesi berakhir dan halaman terproteksi tidak dapat diakses.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Logout pada Navbar. | Menerima `POST /api/logout`. |
| 2. Menunggu proses. | Menghancurkan data sesi dan mengembalikan "Logout berhasil". |
| 3. Diarahkan keluar. | Frontend menghapus data sesi lokal dan mengarahkan ke halaman login. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Sesi sudah tidak valid | Sistem mengembalikan HTTP 401; frontend menampilkan "Sesi Berakhir" dan mengarahkan ke login. |
| A2 | Pengguna menutup browser tanpa logout | Sesi berakhir sesuai konfigurasi server. |
| A3 | Kesalahan server | Frontend menampilkan pesan gagal logout. |

#### UC-03 Melihat Profil Sendiri

**Nama Use Case:** Melihat Profil Sendiri
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan identitas pengguna yang sedang login pada Navbar.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Nama dan peran pengguna tampil pada antarmuka.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka aplikasi/halaman. | Frontend meminta data pengguna aktif ke `GET /api/me`. |
| 2. Menunggu data. | Sistem membaca data dari sesi dan mengembalikan nama, username, dan peran. |
| 3. Melihat Navbar. | Frontend menampilkan nama dan peran pengguna. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Data sesi tidak lengkap | Frontend menampilkan nilai default dan tetap mengizinkan logout. |
| A2 | Sesi berakhir | Sistem mengembalikan HTTP 401 dan pengguna diarahkan ke login. |

### 3.2 Dashboard

#### UC-04 Melihat Dashboard Operasional

**Nama Use Case:** Melihat Dashboard Operasional
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan ringkasan kondisi gudang, grafik tren transaksi, produk keluar terbanyak, dan produk mendekati expired.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Ringkasan kondisi gudang terbaru ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Dashboard. | Frontend meminta `GET /api/dashboard/summary`, `/chart`, `/top-outbound`, dan `/expired-products`. |
| 2. Menunggu data dimuat. | Sistem menghitung total produk aktif, total batch, total stok, jumlah stok rendah, jumlah produk mendekati expired (30 hari), serta transaksi masuk dan keluar hari ini. |
| 3. Melihat tampilan. | Sistem menyusun data grafik tren masuk/keluar serta daftar produk outbound tertinggi dan mendekati expired; frontend menampilkan kartu ringkasan, grafik, dan tabel (refresh otomatis tiap 5 detik). |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Belum ada transaksi | Sistem mengembalikan nilai nol atau daftar kosong; dashboard tetap tampil. |
| A2 | Sebagian endpoint gagal | Frontend menampilkan komponen yang berhasil dan mengosongkan komponen yang gagal. |
| A3 | Sesi berakhir | HTTP 401; pengguna diarahkan ke login. |
| A4 | Kesalahan server | HTTP 500; dashboard menampilkan kondisi kosong. |

### 3.3 Produk

#### UC-05 Melihat Daftar Produk

**Nama Use Case:** Melihat Daftar Produk
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan daftar produk beserta pencarian, filter, dan paginasi.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Daftar produk ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Produk. | Frontend meminta `GET /api/products`. |
| 2. Mengetik pencarian atau memilih filter/paginasi. | Sistem mengembalikan data produk (SKU, nama, kategori, supplier, satuan, stok minimum, status). |
| 3. Melihat daftar. | Frontend menampilkan tabel produk sesuai parameter. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Pencarian/filter tidak menemukan data | Sistem menampilkan daftar kosong. |
| A2 | Sesi berakhir | HTTP 401; pengguna diarahkan ke login. |

#### UC-06 Menambah Produk

**Nama Use Case:** Menambah Produk
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menambahkan data produk baru.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN.
**Kondisi Akhir:** Produk baru tersimpan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Tambah Produk dan mengisi formulir (nama, SKU, kategori, supplier, merek, satuan, stok minimum, status). | Menampilkan formulir produk. |
| 2. Menekan Simpan. | Menerima `POST /api/products`; memvalidasi kolom wajib (nama, SKU, kategori, supplier) dan keunikan SKU. |
| 3. Menunggu hasil. | Menyimpan produk dan mengembalikan "Produk berhasil ditambahkan"; frontend memuat ulang daftar. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Kolom wajib kosong | "Field {nama field} wajib diisi". |
| A2 | SKU sudah digunakan | "SKU code sudah digunakan". |
| A3 | Pengguna GUDANG mencoba menambah | HTTP 403 "Forbidden. Hanya ADMIN dan SUPERADMIN yang dapat melakukan operasi ini." |

#### UC-07 Mengubah Produk

**Nama Use Case:** Mengubah Produk
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Mengubah data produk yang sudah ada.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; produk tersedia.
**Kondisi Akhir:** Data produk diperbarui.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Edit pada salah satu produk. | Menampilkan formulir berisi data produk. |
| 2. Mengubah data lalu menekan Update. | Menerima `PUT /api/products/{id}`; memeriksa keberadaan produk dan keunikan SKU terhadap produk lain. |
| 3. Menunggu hasil. | Menyimpan perubahan dan mengembalikan "Produk berhasil diupdate". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Produk tidak ditemukan | HTTP 404 "Produk tidak ditemukan". |
| A2 | Tidak ada perubahan data | "Tidak ada perubahan pada produk". |
| A3 | SKU bentrok dengan produk lain | "SKU code sudah digunakan". |
| A4 | Pengguna GUDANG mencoba mengubah | HTTP 403. |

#### UC-08 Menghapus Produk

**Nama Use Case:** Menghapus Produk
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menghapus produk yang tidak lagi digunakan.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; produk tersedia.
**Kondisi Akhir:** Produk terhapus bila tidak memiliki batch.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Hapus dan mengonfirmasi. | Menerima `DELETE /api/products/{id}`. |
| 2. Menunggu proses. | Memeriksa apakah produk masih memiliki batch stok. |
| 3. Menerima hasil. | Jika tidak ada batch, menghapus produk dan mengembalikan "Produk berhasil dihapus". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Produk masih memiliki batch | "Produk tidak bisa dihapus karena masih memiliki batch stok. Nonaktifkan produk saja." |
| A2 | Produk tidak ditemukan | HTTP 404 "Produk tidak ditemukan". |
| A3 | Pengguna GUDANG mencoba menghapus | HTTP 403. |

#### UC-09 Melihat Statistik Produk

**Nama Use Case:** Melihat Statistik Produk
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan ringkasan jumlah produk untuk kartu statistik.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Statistik produk ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Produk. | Frontend meminta `GET /api/products/statistic`. |
| 2. Menunggu data. | Sistem mengembalikan `total_produk`, `produk_aktif`, `minimum_stock`, dan `non_aktif`. |
| 3. Melihat kartu statistik. | Frontend menampilkan ringkasan angka. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Gagal memuat statistik | Kartu statistik menampilkan nilai kosong/nol. |

### 3.4 Kategori

#### UC-10 Melihat Daftar Kategori

**Nama Use Case:** Melihat Daftar Kategori
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan daftar kategori beserta jumlah produk per kategori.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Daftar kategori ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Kategori. | Frontend meminta `GET /api/categories` dengan parameter halaman, batas, pencarian, status, dan pengurutan. |
| 2. Menyesuaikan filter/paginasi. | Sistem memvalidasi batas maksimal 100 data per halaman dan mengembalikan kategori beserta jumlah produk. |
| 3. Melihat daftar. | Frontend menampilkan tabel kategori. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada data | Daftar kosong ditampilkan. |

#### UC-11 Menambah Kategori

**Nama Use Case:** Menambah Kategori
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menambahkan kategori produk baru.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN.
**Kondisi Akhir:** Kategori baru tersimpan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Tambah Kategori. | Menampilkan formulir; frontend meminta kode berikutnya via `GET /api/categories/next-code`. |
| 2. Mengisi nama dan kode lalu menekan Simpan. | Menerima `POST /api/categories`; memvalidasi nama wajib, maksimal 200 karakter, serta keunikan nama dan kode. |
| 3. Menunggu hasil. | Menyimpan kategori dan mengembalikan "Kategori berhasil ditambahkan". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Nama kosong | "Nama kategori wajib diisi". |
| A2 | Nama lebih dari 200 karakter | "Nama kategori maksimal 200 karakter". |
| A3 | Nama sudah dipakai | HTTP 409 "Nama kategori sudah digunakan". |
| A4 | Kode sudah dipakai | HTTP 409 "Kode kategori sudah digunakan". |
| A5 | Kode kosong | Sistem menghasilkan kode berikutnya otomatis. |

#### UC-12 Mengubah Kategori

**Nama Use Case:** Mengubah Kategori
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Mengubah data kategori yang sudah ada.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; kategori tersedia.
**Kondisi Akhir:** Data kategori diperbarui.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Edit pada kategori. | Menampilkan formulir berisi data kategori. |
| 2. Mengubah data lalu menekan Update. | Menerima `PUT /api/categories/{id}`; memvalidasi data dan keunikan nama/kode. |
| 3. Menunggu hasil. | Menyimpan perubahan dan mengembalikan "Kategori berhasil diupdate". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Kategori tidak ditemukan | HTTP 404 "Kategori tidak ditemukan". |
| A2 | Nama/kode bentrok | HTTP 409 sesuai field. |
| A3 | Pengguna GUDANG mencoba mengubah | HTTP 403. |

#### UC-13 Menghapus Kategori

**Nama Use Case:** Menghapus Kategori
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menghapus kategori yang tidak digunakan.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; kategori tersedia.
**Kondisi Akhir:** Kategori terhapus bila tidak dipakai produk.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Hapus dan mengonfirmasi. | Menerima `DELETE /api/categories/{id}`. |
| 2. Menunggu proses. | Menghitung jumlah produk yang menggunakan kategori. |
| 3. Menerima hasil. | Jika tidak digunakan, menghapus kategori dan mengembalikan "Kategori berhasil dihapus". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Kategori dipakai produk | HTTP 409 "Kategori tidak bisa dihapus karena sedang digunakan oleh {jumlah} produk. Nonaktifkan kategori saja." |
| A2 | Kategori tidak ditemukan | HTTP 404. |
| A3 | Pengguna GUDANG mencoba menghapus | HTTP 403. |

### 3.5 Supplier

#### UC-14 Melihat Daftar Supplier

**Nama Use Case:** Melihat Daftar Supplier
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan daftar supplier beserta pencarian dan filter.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Daftar supplier ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Supplier. | Frontend meminta `GET /api/suppliers`. |
| 2. Mengetik pencarian/filter. | Sistem mengembalikan data supplier (kode, nama, alamat, telepon, email, status). |
| 3. Melihat daftar. | Frontend menampilkan tabel supplier. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Pencarian tidak menemukan data | Daftar kosong ditampilkan. |

#### UC-15 Menambah Supplier

**Nama Use Case:** Menambah Supplier
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menambahkan data supplier baru.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN.
**Kondisi Akhir:** Supplier baru tersimpan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Tambah Supplier dan mengisi kode, nama, alamat, telepon, email, status. | Menampilkan formulir supplier. |
| 2. Menekan Simpan. | Menerima `POST /api/suppliers`; memvalidasi kode dan nama wajib, format email, serta keunikan kode. |
| 3. Menunggu hasil. | Menghasilkan ID `SUP-######`, menyimpan supplier, dan mengembalikan "Supplier berhasil ditambahkan". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Kode atau nama kosong | "Field supplier_code dan name wajib diisi". |
| A2 | Format email tidak valid | "Format email tidak valid". |
| A3 | Kode sudah dipakai | HTTP 409 "Kode supplier sudah digunakan". |
| A4 | Request bukan JSON valid | "Data request tidak valid. Pastikan mengirimkan JSON." |

#### UC-16 Mengubah Supplier

**Nama Use Case:** Mengubah Supplier
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Mengubah data supplier yang sudah ada.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; supplier tersedia.
**Kondisi Akhir:** Data supplier diperbarui.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Edit pada supplier. | Menampilkan formulir berisi data supplier. |
| 2. Mengubah data lalu menekan Update. | Menerima `PUT /api/suppliers/{id}`; memvalidasi data dan keunikan kode. |
| 3. Menunggu hasil. | Menyimpan perubahan dan mengembalikan "Supplier berhasil diupdate". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Supplier tidak ditemukan | HTTP 404 "Supplier tidak ditemukan". |
| A2 | Kode bentrok | HTTP 409 "Kode supplier sudah digunakan". |
| A3 | Pengguna GUDANG mencoba mengubah | HTTP 403. |

#### UC-17 Menghapus Supplier

**Nama Use Case:** Menghapus Supplier
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menghapus supplier yang tidak memiliki transaksi inbound.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; supplier tersedia.
**Kondisi Akhir:** Supplier terhapus bila tidak memiliki transaksi inbound.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Hapus dan mengonfirmasi. | Menerima `DELETE /api/suppliers/{id}`. |
| 2. Menunggu proses. | Memeriksa apakah supplier masih memiliki transaksi inbound. |
| 3. Menerima hasil. | Jika tidak ada, menghapus supplier dan mengembalikan "Supplier berhasil dihapus". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Supplier memiliki transaksi inbound | HTTP 409 "Supplier tidak bisa dihapus karena masih memiliki transaksi inbound. Nonaktifkan saja." |
| A2 | Supplier tidak ditemukan | HTTP 404. |
| A3 | Pengguna GUDANG mencoba menghapus | HTTP 403. |

### 3.6 Rak/Lokasi

#### UC-18 Melihat Daftar Rak

**Nama Use Case:** Melihat Daftar Rak
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan daftar rak/lokasi penyimpanan.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Daftar rak ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Rak/Lokasi. | Frontend meminta `GET /api/rack`. |
| 2. Mengetik pencarian/filter. | Sistem mengembalikan data rak (kode rak, nama, kode shelf, status). |
| 3. Melihat daftar. | Frontend menampilkan tabel rak. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada data | Daftar kosong ditampilkan. |

#### UC-19 Menambah Rak

**Nama Use Case:** Menambah Rak
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menambahkan data rak penyimpanan baru.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN.
**Kondisi Akhir:** Rak baru tersimpan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Tambah Rak dan mengisi kode rak, nama, dan kode shelf. | Menampilkan formulir rak. |
| 2. Menekan Simpan. | Menerima `POST /api/rack`; memvalidasi kode wajib (maks. 50 karakter), nama (maks. 100 karakter), status `AKTIF`/`NONAKTIF`, dan keunikan kode. |
| 3. Menunggu hasil. | Menyimpan rak dan mengembalikan "Rak berhasil ditambahkan". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Kode rak kosong | "Kode rak wajib diisi". |
| A2 | Kode lebih dari 50 karakter | "Kode rak maksimal 50 karakter". |
| A3 | Nama lebih dari 100 karakter | "Nama rak maksimal 100 karakter". |
| A4 | Kode sudah dipakai | HTTP 409 "Kode rak sudah digunakan". |

#### UC-20 Mengubah Rak

**Nama Use Case:** Mengubah Rak
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Mengubah data rak yang sudah ada.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; rak tersedia.
**Kondisi Akhir:** Data rak diperbarui.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Edit pada rak. | Menampilkan formulir berisi data rak. |
| 2. Mengubah data lalu menekan Update. | Menerima `PUT /api/rack/{id}`; memvalidasi data dan keunikan kode. |
| 3. Menunggu hasil. | Menyimpan perubahan dan mengembalikan "Rak berhasil diupdate". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Rak tidak ditemukan | HTTP 404 "Rak tidak ditemukan". |
| A2 | Kode bentrok | HTTP 409 "Kode rak sudah digunakan". |
| A3 | Pengguna GUDANG mencoba mengubah | HTTP 403. |

#### UC-21 Menghapus Rak

**Nama Use Case:** Menghapus Rak
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menghapus rak yang tidak digunakan batch.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; rak tersedia.
**Kondisi Akhir:** Rak terhapus bila tidak dipakai batch.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Hapus dan mengonfirmasi. | Menerima `DELETE /api/rack/{id}`. |
| 2. Menunggu proses. | Menghitung jumlah batch yang menggunakan rak. |
| 3. Menerima hasil. | Jika tidak digunakan, menghapus rak dan mengembalikan "Rak berhasil dihapus". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Rak dipakai batch | HTTP 409 "Rak tidak bisa dihapus karena sedang digunakan oleh {jumlah} batch produk. Nonaktifkan rak saja." |
| A2 | Rak tidak ditemukan | HTTP 404. |
| A3 | Pengguna GUDANG mencoba menghapus | HTTP 403. |

### 3.7 Batch

#### UC-22 Melihat Daftar Batch

**Nama Use Case:** Melihat Daftar Batch
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Memantau batch stok beserta sisa umur simpan.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Daftar batch ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Batch. | Frontend meminta `GET /api/batches`. |
| 2. Mengetik pencarian atau filter. | Sistem mengembalikan data batch beserta nama produk, rak, dan sisa hari menuju kedaluwarsa. |
| 3. Melihat daftar. | Frontend menampilkan tabel batch. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada batch | Daftar kosong ditampilkan. |
| A2 | Batch tanpa rak | Kolom rak menampilkan "-". |

#### UC-23 Melihat Statistik dan Detail Batch

**Nama Use Case:** Melihat Statistik dan Detail Batch
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan statistik batch dan detail satu batch.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Statistik dan/atau detail batch ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Batch. | Frontend meminta `GET /api/batches/statistic`. |
| 2. Membuka detail salah satu batch. | Frontend meminta `GET /api/batches/{id}`. |
| 3. Melihat informasi. | Sistem mengembalikan detail batch; frontend menampilkan statistik dan detail. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Batch tidak ditemukan | HTTP 404 "Batch tidak ditemukan". |
| A2 | Kesalahan server | HTTP 500 "Gagal memuat data batch". |

### 3.8 Barang Masuk

#### UC-24 Melihat Daftar Barang Masuk

**Nama Use Case:** Melihat Daftar Barang Masuk
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan daftar penerimaan barang beserta statistik.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Daftar barang masuk ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Barang Masuk. | Frontend meminta `GET /api/inbound`. |
| 2. Menyesuaikan filter/paginasi. | Sistem mengembalikan nomor, supplier, tanggal, status, serta statistik (hari ini, draft, selesai). |
| 3. Melihat daftar. | Frontend menampilkan tabel barang masuk. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada data | Daftar kosong dengan statistik nol. |

#### UC-25 Menambah Barang Masuk

**Nama Use Case:** Menambah Barang Masuk
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Mencatat rencana dan detail penerimaan barang dari supplier.
**Prasyarat:** Pengguna sudah login; supplier, produk, dan rak tersedia.
**Kondisi Akhir:** Dokumen inbound tersimpan berstatus `DRAFT`.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Tambah. | Menampilkan formulir penerimaan. |
| 2. Memilih supplier. | Memuat produk milik supplier via `GET /api/inbound/products-by-supplier/{id_supplier}`. |
| 3. Mengisi nomor (opsional), tanggal (default hari ini), catatan, lalu menambah baris detail (produk, batch/lot, quantity, tanggal expired, rak). | Frontend memvalidasi supplier, tanggal, minimal satu detail, produk, batch, quantity ≥ 1, dan tanggal expired. |
| 4. Menekan Simpan. | Menerima `POST /api/inbound`; memvalidasi tanggal, supplier aktif, dan tiap baris; menghasilkan nomor `INB-######` bila kosong. |
| 5. Menunggu hasil. | Menyimpan header dan detail berstatus `DRAFT` dalam satu transaksi dan mengembalikan "Inbound berhasil dibuat". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Nomor dikosongkan | Sistem membuat nomor otomatis `INB-######`. |
| A2 | Tanggal kosong | "Tanggal penerimaan wajib diisi". |
| A3 | Supplier belum dipilih/tidak aktif | "Supplier wajib dipilih" atau "Supplier tidak ditemukan atau tidak aktif". |
| A4 | Detail kosong | "Detail barang wajib diisi". |
| A5 | Produk belum dipilih | "Baris {n}: Produk wajib dipilih". |
| A6 | Quantity tidak valid | "Baris {n}: Quantity harus lebih dari 0". |
| A7 | Validasi frontend gagal | SweetAlert "Validasi" tampil; data tidak dikirim. |

#### UC-26 Mengubah Barang Masuk

**Nama Use Case:** Mengubah Barang Masuk
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Mengubah header/detail dokumen inbound berstatus `DRAFT`.
**Prasyarat:** Dokumen berstatus `DRAFT`.
**Kondisi Akhir:** Dokumen inbound diperbarui.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Edit pada data `DRAFT`. | Menampilkan formulir berisi data inbound. |
| 2. Mengubah header/detail lalu menekan Update. | Menerima `PUT /api/inbound/{id}`; memastikan data ada dan berstatus `DRAFT`. |
| 3. Menunggu hasil. | Memperbarui header, mengganti detail, dan mengembalikan "Inbound berhasil diupdate". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Data tidak ditemukan | "Inbound tidak ditemukan". |
| A2 | Status bukan DRAFT | "Hanya inbound dengan status DRAFT yang bisa diubah". |
| A3 | Mengganti supplier | Daftar produk pada detail direset. |

#### UC-27 Menghapus Barang Masuk

**Nama Use Case:** Menghapus Barang Masuk
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menghapus dokumen inbound berstatus `DRAFT`.
**Prasyarat:** Dokumen berstatus `DRAFT`.
**Kondisi Akhir:** Header dan detail inbound terhapus.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Hapus dan mengonfirmasi. | Menerima `DELETE /api/inbound/{id}`; memastikan data ada dan berstatus `DRAFT`. |
| 2. Menunggu proses. | Menghapus header dan detail. |
| 3. Menerima hasil. | Mengembalikan "Inbound berhasil dihapus". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Data tidak ditemukan | "Inbound tidak ditemukan". |
| A2 | Status bukan DRAFT | "Hanya inbound dengan status DRAFT yang bisa dihapus". |

#### UC-28 Menyelesaikan Barang Masuk

**Nama Use Case:** Menyelesaikan Barang Masuk
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menambahkan stok ke batch dan menutup dokumen penerimaan.
**Prasyarat:** Dokumen `DRAFT` dan memiliki detail.
**Kondisi Akhir:** Stok bertambah pada batch; dokumen menjadi `COMPLETED`.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Selesai pada data `DRAFT` dan mengonfirmasi. | Menerima `POST /api/inbound/{id}/complete`; mengunci header dan memastikan status `DRAFT`. |
| 2. Menunggu proses. | Mengambil seluruh detail; memvalidasi tiap baris (batch wajib, quantity > 0, produk milik supplier header). |
| 3. Menunggu hasil. | Mencari batch (produk + nomor batch): jika ada, memastikan expired/rak konsisten lalu menambah quantity; jika belum, membuat batch baru. Menyimpan ID batch ke detail dan mengubah status menjadi `COMPLETED`. |
| 4. Menerima hasil. | Mengembalikan "Inbound berhasil diselesaikan dan stok telah ditambahkan". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada detail | "Tidak ada detail barang untuk inbound ini". |
| A2 | Batch kosong | "Baris {n}: Batch/Lot wajib diisi sebelum menyelesaikan inbound". |
| A3 | Produk bukan milik supplier | "Baris {n}: Produk "{nama}" tidak sesuai dengan supplier "{supplier}"". |
| A4 | Expired/rak tidak cocok dengan batch lama | Pesan ketidakcocokan expired/rak ("Rak tidak cocok dengan batch ..."). |
| A5 | Expired pada detail kosong | Batch baru disimpan tanpa tanggal expired. |
| A6 | Kesalahan basis data | Transaksi dibatalkan; "Gagal menyelesaikan inbound: ...". |

### 3.9 Barang Keluar

#### UC-29 Melihat Daftar Barang Keluar

**Nama Use Case:** Melihat Daftar Barang Keluar
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan daftar pengeluaran barang beserta statistik.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Daftar barang keluar ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Barang Keluar. | Frontend meminta `GET /api/outbound`. |
| 2. Menyesuaikan filter/paginasi. | Sistem mengembalikan nomor, tanggal, status, serta statistik (hari ini, draft, selesai). |
| 3. Melihat daftar. | Frontend menampilkan tabel barang keluar. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada data | Daftar kosong dengan statistik nol. |

#### UC-30 Menambah Barang Keluar

**Nama Use Case:** Menambah Barang Keluar
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Mencatat rencana dan detail pengeluaran barang dari gudang.
**Prasyarat:** Pengguna sudah login; produk dan batch dengan stok tersedia.
**Kondisi Akhir:** Dokumen outbound tersimpan berstatus `DRAFT` dan stok batch berkurang.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Tambah. | Menampilkan formulir pengeluaran. |
| 2. Mengisi nomor (opsional), tanggal, catatan, lalu menambah baris detail (produk, batch dengan stok > 0, quantity). | Frontend membatasi quantity maksimal sesuai stok batch. |
| 3. Menekan Simpan. | Menerima `POST /api/outbound`; memvalidasi tanggal, detail, keberadaan batch, quantity > 0, dan kecukupan stok. |
| 4. Menunggu hasil. | Mengurangi quantity batch, menyimpan header `DRAFT` dan detail dalam satu transaksi, menghasilkan `OUT-######` bila kosong, lalu mengembalikan "Outbound berhasil dibuat". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tanggal kosong | "Tanggal pengiriman wajib diisi". |
| A2 | Detail kosong | "Detail barang wajib diisi". |
| A3 | Batch tidak ditemukan | "Batch dengan ID {id} tidak ditemukan". |
| A4 | Quantity melebihi stok | "Stok tidak mencukupi untuk batch {nomor}. Stok tersedia: {stok}, diminta: {jumlah}". |
| A5 | Quantity tidak valid | "Gagal membuat outbound: Quantity harus lebih dari 0". |
| A6 | Validasi frontend gagal | "Melebihi stok tersedia (max: ...)"; data tidak dikirim. |

#### UC-31 Mengubah Barang Keluar

**Nama Use Case:** Mengubah Barang Keluar
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Mengubah header/detail dokumen outbound berstatus `DRAFT`.
**Prasyarat:** Dokumen berstatus `DRAFT`.
**Kondisi Akhir:** Dokumen outbound diperbarui dan stok disesuaikan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Edit pada data `DRAFT`. | Menampilkan formulir berisi data outbound. |
| 2. Mengubah header/detail lalu menekan Update. | Menerima `PUT /api/outbound/{id}`; memastikan data ada dan berstatus `DRAFT`. |
| 3. Menunggu proses. | Mengembalikan quantity batch lama, menghapus detail lama, lalu mengurangi stok sesuai detail baru. |
| 4. Menerima hasil. | Mengembalikan "Outbound berhasil diupdate". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Data tidak ditemukan | "Outbound tidak ditemukan". |
| A2 | Status bukan DRAFT | "Hanya outbound dengan status DRAFT yang bisa diubah". |
| A3 | Stok tidak cukup | Pesan stok tidak mencukupi. |

#### UC-32 Menghapus Barang Keluar

**Nama Use Case:** Menghapus Barang Keluar
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menghapus dokumen outbound berstatus `DRAFT` dan mengembalikan stok.
**Prasyarat:** Dokumen berstatus `DRAFT`.
**Kondisi Akhir:** Header dan detail terhapus; stok batch dikembalikan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Hapus dan mengonfirmasi. | Menerima `DELETE /api/outbound/{id}`; memastikan data ada dan berstatus `DRAFT`. |
| 2. Menunggu proses. | Mengembalikan quantity batch untuk setiap detail, lalu menghapus detail dan header. |
| 3. Menerima hasil. | Mengembalikan "Outbound berhasil dihapus". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Data tidak ditemukan | "Outbound tidak ditemukan". |
| A2 | Status bukan DRAFT | "Hanya outbound dengan status DRAFT yang bisa dihapus". |

#### UC-33 Menyelesaikan Barang Keluar

**Nama Use Case:** Menyelesaikan Barang Keluar
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menutup dokumen pengeluaran barang.
**Prasyarat:** Dokumen outbound berstatus `DRAFT`.
**Kondisi Akhir:** Dokumen menjadi `COMPLETED`.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Selesai pada data `DRAFT` dan mengonfirmasi. | Menerima `POST /api/outbound/{id}/complete`; memastikan dokumen ada dan berstatus `DRAFT`. |
| 2. Menunggu proses. | Mengambil seluruh detail dan mengurangi quantity batch untuk setiap detail. |
| 3. Menunggu hasil. | Mengubah status menjadi `COMPLETED` dan mengembalikan "Outbound berhasil diselesaikan". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada detail | Penyelesaian tetap mengubah status menjadi `COMPLETED` tanpa perubahan stok. |
| A2 | Dokumen tidak ditemukan | "Outbound tidak ditemukan". |
| A3 | Status bukan DRAFT | "Hanya outbound dengan status DRAFT yang bisa diselesaikan". |
| A4 | Kesalahan basis data | Transaksi dibatalkan; "Gagal menyelesaikan outbound: ...". |

> Catatan: stok batch sudah dikurangi saat create/update, lalu dikurangi lagi saat complete. Indikasi pengurangan ganda ini perlu dikonfirmasi (lihat Bagian 5).

### 3.10 Manajemen Stok

#### UC-34 Melihat Stok Realtime

**Nama Use Case:** Melihat Stok Realtime
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Mengetahui total stok tersedia per produk hasil agregasi seluruh batch.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Stok realtime per produk ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Stok. | Frontend meminta `GET /api/stock/realtime`. |
| 2. Menunggu data. | Sistem menjumlahkan quantity seluruh batch untuk setiap produk aktif dan mengembalikan `id_product`, SKU, nama, satuan, stok minimum, `total_available`, `total_quantity`, dan `total_batches`. |
| 3. Melihat daftar. | Frontend menampilkan tabel stok; pengguna dapat mencari dan memfilter. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Produk belum memiliki batch | Total stok ditampilkan 0. |
| A2 | Sesi berakhir | HTTP 401; pengguna diarahkan ke login. |
| A3 | Kesalahan server | HTTP 500 "Gagal memuat data stok realtime". |

#### UC-35 Melihat Mutasi Stok

**Nama Use Case:** Melihat Mutasi Stok
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Melihat riwayat pergerakan stok.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Riwayat mutasi stok ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka tab Mutasi pada menu Stok. | Frontend meminta `GET /api/stock/mutations`. |
| 2. Menunggu data. | Sistem menggabungkan data inbound, outbound, dan adjustment berstatus `APPROVED`, lalu mengurutkan berdasarkan waktu terbaru. |
| 3. Melihat daftar. | Sistem mengembalikan jenis mutasi, nomor dokumen, tanggal mutasi, status, dan waktu pembuatan; frontend menampilkan daftar. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Belum ada transaksi | Daftar kosong ditampilkan. |
| A2 | Kesalahan server | HTTP 500 "Gagal memuat data mutasi stok". |

#### UC-36 Melihat Stok Rendah

**Nama Use Case:** Melihat Stok Rendah
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Mengidentifikasi produk yang perlu segera dipesan ulang.
**Prasyarat:** Pengguna sudah login; produk memiliki nilai stok minimum.
**Kondisi Akhir:** Daftar produk stok rendah ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka tab Stok Rendah. | Frontend meminta `GET /api/stock/low-stock`. |
| 2. Menunggu data. | Sistem menjumlahkan stok per produk aktif dan membandingkan dengan stok minimum; mengembalikan produk dengan total stok ≤ stok minimum. |
| 3. Melihat daftar. | Frontend menampilkan produk beserta stok tersedia dan stok minimum. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Semua stok aman | Daftar kosong ditampilkan. |
| A2 | Kesalahan server | HTTP 500 "Gagal memuat data stok rendah". |

#### UC-37 Membuat Stock Adjustment

**Nama Use Case:** Membuat Stock Adjustment
**Aktor:** SUPERADMIN, ADMIN (tombol UI hanya untuk admin); API dapat diakses semua peran login
**Deskripsi:** Mengajukan penyesuaian stok manual.
**Prasyarat:** Pengguna sudah login; produk dan batch tersedia.
**Kondisi Akhir:** Adjustment tersimpan berstatus `PENDING`; stok belum berubah.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Buat Penyesuaian. | Menampilkan formulir penyesuaian. |
| 2. Memilih produk dan batch, tipe `INCREASE`/`DECREASE`, quantity, dan alasan. | — |
| 3. Menekan Simpan. | Menerima `POST /api/stock/adjustments`; memvalidasi `product_id`, tipe, quantity bilangan bulat positif, dan alasan. |
| 4. Menunggu proses. | Menentukan batch (jika kosong dipakai batch terbaru), menghitung quantity baru (tipe+quantity atau `new_quantity`), menolak bila negatif/tidak berubah. |
| 5. Menerima hasil. | Menyimpan adjustment `PENDING` dan mengembalikan "Penyesuaian stok dibuat dan menunggu persetujuan". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Product ID kosong | "Product ID wajib diisi". |
| A2 | Tipe tidak valid | "Tipe adjustment harus INCREASE atau DECREASE". |
| A3 | Quantity tidak valid | "Quantity adjustment wajib diisi berupa bilangan bulat positif". |
| A4 | Alasan kosong | "Alasan adjustment wajib diisi". |
| A5 | Produk belum memiliki batch | "Tidak ditemukan batch untuk produk ini". |
| A6 | Batch tidak ditemukan | "Batch tidak ditemukan". |
| A7 | Quantity baru negatif/sama | "Quantity tidak boleh negatif" atau "Quantity tidak berubah". |
| A8 | Kesalahan server | "Gagal membuat penyesuaian stock: ...". |

#### UC-38 Menyetujui Stock Adjustment

**Nama Use Case:** Menyetujui Stock Adjustment
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menyetujui penyesuaian dan memperbarui stok.
**Prasyarat:** Adjustment berstatus `PENDING`.
**Kondisi Akhir:** Stok batch diperbarui; adjustment menjadi `APPROVED`.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Setujui pada adjustment `PENDING` dan mengonfirmasi. | Menerima `POST /api/stock/adjustments/{id}/approve`. |
| 2. Menunggu proses. | Memastikan adjustment ada dan `PENDING`; mengunci baris batch dan menghitung stok baru. |
| 3. Menunggu hasil. | Memastikan stok baru tidak negatif, memperbarui quantity batch, mengubah status menjadi `APPROVED`, dan mencatat `approved_by`. |
| 4. Menerima hasil. | Mengembalikan "Adjustment disetujui dan stok berhasil diperbarui". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Adjustment tidak ditemukan | "Adjustment tidak ditemukan". |
| A2 | Status bukan PENDING | "Hanya adjustment dengan status PENDING yang bisa disetujui". |
| A3 | Stok akan negatif | "Persetujuan dibatalkan: stok hasil adjustment tidak boleh negatif". |
| A4 | Pengguna bukan ADMIN/SUPERADMIN | HTTP 403. |
| A5 | Kesalahan server | "Gagal menyetujui adjustment: ...". |

#### UC-39 Menolak Stock Adjustment

**Nama Use Case:** Menolak Stock Adjustment
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menolak penyesuaian tanpa mengubah stok.
**Prasyarat:** Adjustment berstatus `PENDING`.
**Kondisi Akhir:** Adjustment menjadi `REJECTED`; stok tidak berubah.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Tolak dan mengonfirmasi. | Menerima `POST /api/stock/adjustments/{id}/reject`. |
| 2. Menunggu proses. | Memastikan adjustment ada dan `PENDING`. |
| 3. Menerima hasil. | Mengubah status menjadi `REJECTED`, mencatat `approved_by`, dan mengembalikan "Adjustment ditolak". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Adjustment tidak ditemukan | "Adjustment tidak ditemukan". |
| A2 | Status bukan PENDING | "Hanya adjustment dengan status PENDING yang bisa ditolak". |
| A3 | Pengguna bukan ADMIN/SUPERADMIN | HTTP 403. |

### 3.11 Stock Opname

#### UC-40 Membuat Sesi Opname

**Nama Use Case:** Membuat Sesi Opname
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Memulai sesi penghitungan fisik stok.
**Prasyarat:** Pengguna sudah login; produk aktif dan batch tersedia.
**Kondisi Akhir:** Sesi opname `DRAFT` dengan detail otomatis tersimpan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Buat Sesi Opname dan memilih tanggal. | Menampilkan formulir tanggal opname. |
| 2. Menekan Simpan. | Menerima `POST /api/opname/sessions`; memvalidasi tanggal wajib diisi. |
| 3. Menunggu proses. | Menghasilkan nomor `OPN-YYYYMMDD-###`, menyimpan sesi `DRAFT`, dan otomatis membuat detail untuk setiap batch produk aktif (`system_quantity` dari stok batch, `physical_quantity` 0, `difference` 0). |
| 4. Menerima hasil. | Mengembalikan "Sesi opname berhasil dibuat". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tanggal kosong | "Tanggal opname wajib diisi". |
| A2 | Produk aktif tanpa batch | Detail untuk produk tersebut tidak dibuat. |
| A3 | Kesalahan basis data | Transaksi dibatalkan; "Gagal membuat sesi opname: ...". |

#### UC-41 Melihat Detail Sesi Opname

**Nama Use Case:** Melihat Detail Sesi Opname
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menampilkan daftar sesi opname dan detail batch di dalamnya.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Sesi dan detail opname ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Stock Opname. | Frontend meminta `GET /api/opname/sessions`. |
| 2. Membuka salah satu sesi. | Frontend meminta `GET /api/opname/sessions/{id}`. |
| 3. Melihat detail. | Sistem mengembalikan detail batch beserta stok sistem, quantity fisik, dan selisih. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Sesi tidak ditemukan | "Sesi opname tidak ditemukan". |
| A2 | Sesi belum memiliki detail | Daftar detail kosong ditampilkan. |

#### UC-42 Mengisi Hasil Opname

**Nama Use Case:** Mengisi Hasil Opname
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Mencatat quantity fisik hasil penghitungan.
**Prasyarat:** Sesi opname berstatus `DRAFT`.
**Kondisi Akhir:** Quantity fisik dan selisih tersimpan pada detail opname.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka sesi opname `DRAFT`. | Menampilkan daftar detail batch beserta stok sistem. |
| 2. Mengisi quantity fisik untuk satu atau lebih batch lalu menyimpan. | Menerima `POST /api/opname/details`; memvalidasi `id_opname` dan `id_batch`. |
| 3. Menunggu proses. | Memastikan sesi `DRAFT` dan batch ada; mengambil stok sistem terbaru; menghitung selisih = fisik − stok sistem. |
| 4. Menerima hasil. | Memperbarui detail (bila sudah ada) atau menambah detail baru, lalu mengembalikan "Detail opname berhasil ditambahkan" beserta selisih. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | `id_opname`/`id_batch` kosong | "Opname ID dan Batch ID wajib diisi". |
| A2 | Sesi tidak ditemukan | "Sesi opname tidak ditemukan". |
| A3 | Status sesi bukan DRAFT | "Hanya sesi dengan status DRAFT yang bisa ditambahkan detail". |
| A4 | Batch tidak ditemukan | "Batch tidak ditemukan". |
| A5 | Quantity fisik dikosongkan | Dianggap 0. |
| A6 | Detail batch sudah ada | Data diperbarui, bukan diduplikasi. |

#### UC-43 Menyelesaikan Sesi Opname

**Nama Use Case:** Menyelesaikan Sesi Opname
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menutup sesi opname dan membuat adjustment selisih.
**Prasyarat:** Sesi `DRAFT` dan memiliki detail.
**Kondisi Akhir:** Sesi menjadi `COMPLETED`; adjustment selisih berstatus `PENDING`.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Selesaikan pada sesi `DRAFT` dan mengonfirmasi. | Menerima `POST /api/opname/sessions/{id}/complete`; memastikan sesi ada, `DRAFT`, dan detail tidak kosong. |
| 2. Menunggu proses. | Untuk setiap detail dengan selisih tidak nol, membuat stock adjustment `PENDING` (tipe `INCREASE` bila positif, `DECREASE` bila negatif) dengan alasan "Stock Opname: {nomor}". |
| 3. Menerima hasil. | Mengubah status sesi menjadi `COMPLETED` dan mengembalikan "Sesi opname berhasil diselesaikan. Adjustment menunggu persetujuan.". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Semua selisih nol | Sesi tetap diselesaikan tanpa membuat adjustment. |
| A2 | Batch pada detail sudah tidak ada | Detail tersebut dilewati. |
| A3 | Sesi tidak ditemukan | "Sesi opname tidak ditemukan". |
| A4 | Status bukan DRAFT | "Sesi opname sudah selesai atau dibatalkan". |
| A5 | Detail kosong | "Tidak ada detail opname untuk diselesaikan". |
| A6 | Kesalahan basis data | "Gagal menyelesaikan opname: ...". |

#### UC-44 Menghapus Sesi Opname

**Nama Use Case:** Menghapus Sesi Opname
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menghapus sesi opname yang belum selesai.
**Prasyarat:** Sesi opname berstatus `DRAFT`.
**Kondisi Akhir:** Sesi dan detailnya terhapus.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Hapus dan mengonfirmasi. | Menerima `DELETE /api/opname/sessions/{id}`; memastikan sesi ada dan belum selesai. |
| 2. Menunggu proses. | Menghapus sesi beserta detailnya. |
| 3. Menerima hasil. | Mengembalikan "Sesi opname berhasil dihapus". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Sesi tidak ditemukan | "Sesi opname tidak ditemukan". |
| A2 | Sesi sudah selesai | "Sesi opname yang sudah selesai tidak bisa dihapus". |
| A3 | Kesalahan basis data | "Gagal menghapus sesi opname: ...". |

### 3.12 Monitoring Expired

#### UC-45 Melihat Barang Mendekati Expired

**Nama Use Case:** Melihat Barang Mendekati Expired
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Memantau batch yang mendekati kedaluwarsa (≤ 30 hari).
**Prasyarat:** Pengguna sudah login; batch memiliki tanggal expired.
**Kondisi Akhir:** Daftar barang mendekati expired ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Monitoring Expired. | Frontend meminta `GET /api/expired/near-expiry`. |
| 2. Menunggu data. | Sistem menghitung selisih hari dan mengembalikan batch dengan `hari ini ≤ expiry_date ≤ hari ini + 30 hari` dan quantity > 0. |
| 3. Melihat daftar. | Frontend menampilkan tabel dan badge jumlah. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada data | Badge menampilkan 0 dan daftar kosong. |
| A2 | Batch tanpa tanggal expired | Tidak masuk kategori. |
| A3 | Kesalahan server | HTTP 500; tab menampilkan data kosong. |

#### UC-46 Melihat Barang Expired dan Filter

**Nama Use Case:** Melihat Barang Expired dan Filter
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Memantau barang yang sudah expired dan memfilter berdasarkan rentang tanggal.
**Prasyarat:** Pengguna sudah login; batch memiliki tanggal expired.
**Kondisi Akhir:** Daftar barang expired/semua data sesuai filter ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Monitoring Expired. | Frontend meminta `GET /api/expired`, `/today`, dan/atau `/expired-products` sesuai tab. |
| 2. Memilih tab expired/semua atau mengisi rentang tanggal lalu Filter/Reset. | Sistem mengelompokkan data menjadi sudah expired (`expiry_date < hari ini`), expired hari ini, dan seluruh data; hanya menampilkan batch dengan quantity > 0. |
| 3. Melihat hasil. | Frontend menampilkan tab, badge jumlah, dan tabel sesuai filter. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Filter tanggal aktif | Sistem menampilkan data pada rentang tersebut. |
| A2 | Reset filter | Filter dikosongkan dan semua data ditampilkan. |
| A3 | Kesalahan server | HTTP 500; data kosong. |

### 3.13 Laporan dan Ekspor

#### UC-47 Menampilkan Laporan Barang Masuk

**Nama Use Case:** Menampilkan Laporan Barang Masuk
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menyajikan laporan transaksi barang masuk pada rentang tanggal.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Laporan barang masuk ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu Laporan dan memilih tab Barang Masuk. | Menampilkan tab dan filter rentang tanggal. |
| 2. Mengisi rentang tanggal (opsional) lalu menerapkan filter. | Frontend meminta `GET /api/laporan/barang-masuk` dengan `date_from`/`date_to`. |
| 3. Menunggu data. | Sistem mengambil data transaksi dan menghitung total keseluruhan. |
| 4. Melihat laporan. | Frontend menampilkan tabel beserta total. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Filter tanggal kosong | Laporan menampilkan seluruh data. |
| A2 | Tidak ada data | Tabel kosong dengan total 0. |
| A3 | Kesalahan server | HTTP 500 dengan pesan gagal memuat laporan. |

#### UC-48 Menampilkan Laporan Barang Keluar

**Nama Use Case:** Menampilkan Laporan Barang Keluar
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menyajikan laporan transaksi barang keluar pada rentang tanggal.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Laporan barang keluar ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka tab Barang Keluar. | Menampilkan filter rentang tanggal. |
| 2. Mengisi rentang tanggal lalu menerapkan filter. | Frontend meminta `GET /api/laporan/barang-keluar`. |
| 3. Menunggu data. | Sistem mengambil data transaksi dan menghitung total. |
| 4. Melihat laporan. | Frontend menampilkan tabel beserta total. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Filter tanggal kosong | Laporan menampilkan seluruh data. |
| A2 | Tidak ada data | Tabel kosong dengan total 0. |
| A3 | Kesalahan server | HTTP 500 dengan pesan gagal memuat laporan. |

#### UC-49 Menampilkan Laporan Stok dan Opname

**Nama Use Case:** Menampilkan Laporan Stok dan Opname
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menyajikan laporan stok saat ini dan detail hasil stock opname.
**Prasyarat:** Pengguna sudah login; untuk laporan opname, sesi opname tersedia.
**Kondisi Akhir:** Laporan stok atau opname ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Memilih tab Stok atau Stock Opname. | Tab Stok tidak menampilkan filter tanggal; tab Stock Opname menampilkan pilihan sesi. |
| 2. (Opname) memilih sesi opname lalu menerapkan filter; (Stok) langsung memuat data. | Frontend meminta `GET /api/laporan/stok` atau `GET /api/laporan/stok-opname` dengan `id_opname`. |
| 3. Menunggu data. | Sistem mengambil data sesuai tab dan menghitung total. |
| 4. Melihat laporan. | Frontend menampilkan tabel beserta total. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tab Stok | Tidak ada filter tanggal. |
| A2 | Sesi opname belum dipilih | "Pilih sesi opname untuk melihat detail laporan". |
| A3 | Sesi opname tidak ditemukan | "Sesi opname tidak ditemukan". |
| A4 | Tidak ada data | Tabel kosong dengan total 0. |

#### UC-50 Menampilkan Laporan Barang Expired

**Nama Use Case:** Menampilkan Laporan Barang Expired
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Menyajikan laporan batch yang sudah/mendekati kedaluwarsa.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Laporan barang expired ditampilkan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Memilih tab Barang Expired. | Menampilkan filter rentang tanggal. |
| 2. Menerapkan filter (opsional). | Frontend meminta `GET /api/laporan/expired`. |
| 3. Menunggu data. | Sistem mengambil data expired dan menghitung total. |
| 4. Melihat laporan. | Frontend menampilkan tabel beserta total. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada data | Tabel kosong dengan total 0. |
| A2 | Tipe laporan tidak valid | "Tipe laporan tidak valid. Pilihan: barang_masuk, barang_keluar, stok, stok_opname, adjustment, expired". |

#### UC-51 Mengekspor Laporan ke PDF

**Nama Use Case:** Mengekspor Laporan ke PDF
**Aktor:** SUPERADMIN, ADMIN, GUDANG
**Deskripsi:** Mengunduh laporan aktif dalam format PDF.
**Prasyarat:** Pengguna sudah login dan laporan sedang ditampilkan.
**Kondisi Akhir:** Berkas PDF laporan terunduh.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Export PDF. | Frontend membuat dokumen PDF landscape A4 menggunakan `jsPDF` dari data laporan aktif. |
| 2. Menunggu proses. | Frontend mengisi judul, kolom, dan baris data. |
| 3. Menerima unduhan. | Frontend menyimpan berkas `laporan-{tab}-{tanggal}.pdf` dan browser mengunduh berkas. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada data | PDF tetap dibuat dengan tabel kosong. |
| A2 | Pembuatan PDF gagal | Frontend menampilkan pesan kesalahan. |
| A3 | Endpoint backend gagal | Frontend tetap dapat membuat PDF dari data yang sudah dimuat. |

> Catatan: endpoint `GET /api/laporan/export-pdf` di backend mengembalikan JSON, bukan berkas PDF; pembuatan PDF dilakukan di sisi klien (lihat Bagian 5).

### 3.14 Administrasi dan Notifikasi

#### UC-52 Mengelola User

**Nama Use Case:** Mengelola User
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Menampilkan daftar pengguna dan menambah akun pengguna baru.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN.
**Kondisi Akhir:** Daftar pengguna tampil atau akun baru tersimpan.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka menu User. | Frontend meminta `GET /api/users`; sistem menampilkan nama, username, peran, status, dan statistik aktif/nonaktif. |
| 2. Menekan Tambah User dan mengisi nama, username, password, peran, status. | Menampilkan formulir pengguna. |
| 3. Menekan Simpan. | Menerima `POST /api/users`; memvalidasi kolom wajib, password minimal 6 karakter, peran valid, dan keunikan username. |
| 4. Menerima hasil. | Menyimpan pengguna dan mengembalikan "User berhasil dibuat". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Kolom wajib kosong | "Nama, username, dan password wajib diisi". |
| A2 | Password < 6 karakter | "Password minimal 6 karakter". |
| A3 | Peran tidak valid | "Role tidak valid. Pilihan: SUPERADMIN, ADMIN, GUDANG". |
| A4 | Username sudah dipakai | "Username sudah digunakan". |
| A5 | Pengguna GUDANG | Menu tidak tampil dan endpoint mengembalikan HTTP 403. |

#### UC-53 Mengubah dan Menghapus User

**Nama Use Case:** Mengubah dan Menghapus User
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Mengubah data pengguna dan menghapus akun yang tidak diperlukan.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; akun target tersedia.
**Kondisi Akhir:** Data pengguna diperbarui atau akun terhapus.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan Edit, mengubah nama/username/peran/status, lalu Update. | Menerima `PUT /api/users/{id}`; memvalidasi keberadaan user, keunikan username, dan peran. |
| 2. Menunggu hasil. | Menyimpan perubahan dan mengembalikan "User berhasil diupdate". |
| 3. Menekan Hapus pada akun lain dan mengonfirmasi. | Menerima `DELETE /api/users/{id}`; menolak penghapusan akun sendiri dan SUPERADMIN terakhir. |
| 4. Menerima hasil. | Menghapus pengguna dan mengembalikan "User berhasil dihapus". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | User tidak ditemukan | "User tidak ditemukan". |
| A2 | Tidak ada perubahan | "Tidak ada data yang diupdate". |
| A3 | Password dikosongkan saat edit | Password lama dipertahankan. |
| A4 | Menghapus akun sendiri | "Tidak bisa menghapus akun sendiri". |
| A5 | Menghapus SUPERADMIN terakhir | "Tidak bisa menghapus superadmin terakhir". |
| A6 | Pengguna GUDANG | HTTP 403. |

#### UC-54 Mengubah Password User

**Nama Use Case:** Mengubah Password User
**Aktor:** SUPERADMIN, ADMIN
**Deskripsi:** Mengubah password akun pengguna.
**Prasyarat:** Pengguna login sebagai SUPERADMIN/ADMIN; akun target tersedia.
**Kondisi Akhir:** Password pengguna diperbarui.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Menekan tombol Ubah Password pada salah satu user. | Menampilkan formulir password lama dan password baru. |
| 2. Mengisi kedua kolom lalu menekan Ubah Password. | Menerima `POST /api/users/{id}/change-password`; memvalidasi kedua password wajib dan password baru minimal 6 karakter. |
| 3. Menunggu proses. | Memastikan user ada dan memverifikasi password lama. |
| 4. Menerima hasil. | Menyimpan password baru dan mengembalikan "Password berhasil diubah". |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Password lama/baru kosong | "Password lama dan password baru wajib diisi". |
| A2 | Password baru < 6 karakter | "Password baru minimal 6 karakter". |
| A3 | User tidak ditemukan | "User tidak ditemukan". |
| A4 | Password lama salah | "Password lama salah". |
| A5 | Pengguna GUDANG | HTTP 403. |

#### UC-55 Mengelola Notifikasi

**Nama Use Case:** Mengelola Notifikasi
**Aktor:** SUPERADMIN, ADMIN, GUDANG (generate hanya SUPERADMIN/ADMIN)
**Deskripsi:** Melihat notifikasi, menandai dibaca, dan menghasilkan notifikasi operasional.
**Prasyarat:** Pengguna sudah login.
**Kondisi Akhir:** Notifikasi tampil/ditandai; notifikasi baru tersimpan bila generate.

| Aksi Aktor | Reaksi Sistem |
|---|---|
| 1. Membuka panel notifikasi pada Navbar atau menu Notifikasi. | Frontend meminta `GET /api/notifications/summary` dan `/unread`; sistem memfilter berdasarkan peran atau notifikasi umum dan mengembalikan jumlah belum dibaca serta daftar terbaru. |
| 2. Membuka notifikasi atau menekan Tandai Semua Dibaca. | Menerima `PUT /api/notifications/{id}/read` atau `/read-all`; mengubah `is_read` dan mengembalikan pesan berhasil. |
| 3. (ADMIN/SUPERADMIN) menekan Generate. | Menerima `POST /api/notifications/generate`; memeriksa peran, membuat notifikasi untuk stok menipis/habis, mendekati/sudah expired, inbound DRAFT, dan adjustment PENDING tanpa duplikasi. |
| 4. Menerima hasil. | Mengembalikan "Berhasil menghasilkan {jumlah} notifikasi baru"; frontend memperbarui badge. |

**Alur Alternatif**

| Kode | Kondisi | Penanganan |
|---|---|---|
| A1 | Tidak ada notifikasi | Badge tidak tampil dan daftar kosong. |
| A2 | Prioritas otomatis | `critical` (out_of_stock, expired, pending_approval), `high` (low_stock, near_expiry, stock_adjustment), `low` (opname_selisih, inbound/outbound_completed, user_activity). |
| A3 | GUDANG memanggil generate | HTTP 403 "Forbidden. Hanya ADMIN/SUPERADMIN yang dapat menghasilkan notifikasi." |
| A4 | Tabel notifikasi tidak tersedia | Proses gagal dan pesan "Gagal memuat notifikasi". |
| A5 | Semua notifikasi sudah ada | Jumlah notifikasi baru 0. |

---

## 4. Matriks Aktor terhadap Use Case

Keterangan: ✓ = berhak, – = tidak berhak, ◐ = hanya melalui API (tombol UI disembunyikan).

| ID | Use Case | SUPERADMIN | ADMIN | GUDANG |
|---|---|---|---|---|
| UC-01 | Login | ✓ | ✓ | ✓ |
| UC-02 | Logout | ✓ | ✓ | ✓ |
| UC-03 | Melihat Profil Sendiri | ✓ | ✓ | ✓ |
| UC-04 | Melihat Dashboard Operasional | ✓ | ✓ | ✓ |
| UC-05 | Melihat Daftar Produk | ✓ | ✓ | ✓ |
| UC-06 | Menambah Produk | ✓ | ✓ | – |
| UC-07 | Mengubah Produk | ✓ | ✓ | – |
| UC-08 | Menghapus Produk | ✓ | ✓ | – |
| UC-09 | Melihat Statistik Produk | ✓ | ✓ | ✓ |
| UC-10 | Melihat Daftar Kategori | ✓ | ✓ | ✓ |
| UC-11 | Menambah Kategori | ✓ | ✓ | – |
| UC-12 | Mengubah Kategori | ✓ | ✓ | – |
| UC-13 | Menghapus Kategori | ✓ | ✓ | – |
| UC-14 | Melihat Daftar Supplier | ✓ | ✓ | ✓ |
| UC-15 | Menambah Supplier | ✓ | ✓ | – |
| UC-16 | Mengubah Supplier | ✓ | ✓ | – |
| UC-17 | Menghapus Supplier | ✓ | ✓ | – |
| UC-18 | Melihat Daftar Rak | ✓ | ✓ | ✓ |
| UC-19 | Menambah Rak | ✓ | ✓ | – |
| UC-20 | Mengubah Rak | ✓ | ✓ | – |
| UC-21 | Menghapus Rak | ✓ | ✓ | – |
| UC-22 | Melihat Daftar Batch | ✓ | ✓ | ✓ |
| UC-23 | Melihat Statistik dan Detail Batch | ✓ | ✓ | ✓ |
| UC-24 | Melihat Daftar Barang Masuk | ✓ | ✓ | ✓ |
| UC-25 | Menambah Barang Masuk | ✓ | ✓ | ✓ |
| UC-26 | Mengubah Barang Masuk | ✓ | ✓ | ✓ |
| UC-27 | Menghapus Barang Masuk | ✓ | ✓ | ✓ |
| UC-28 | Menyelesaikan Barang Masuk | ✓ | ✓ | ✓ |
| UC-29 | Melihat Daftar Barang Keluar | ✓ | ✓ | ✓ |
| UC-30 | Menambah Barang Keluar | ✓ | ✓ | ✓ |
| UC-31 | Mengubah Barang Keluar | ✓ | ✓ | ✓ |
| UC-32 | Menghapus Barang Keluar | ✓ | ✓ | ✓ |
| UC-33 | Menyelesaikan Barang Keluar | ✓ | ✓ | ✓ |
| UC-34 | Melihat Stok Realtime | ✓ | ✓ | ✓ |
| UC-35 | Melihat Mutasi Stok | ✓ | ✓ | ✓ |
| UC-36 | Melihat Stok Rendah | ✓ | ✓ | ✓ |
| UC-37 | Membuat Stock Adjustment | ✓ | ✓ | ◐ |
| UC-38 | Menyetujui Stock Adjustment | ✓ | ✓ | – |
| UC-39 | Menolak Stock Adjustment | ✓ | ✓ | – |
| UC-40 | Membuat Sesi Opname | ✓ | ✓ | ✓ |
| UC-41 | Melihat Detail Sesi Opname | ✓ | ✓ | ✓ |
| UC-42 | Mengisi Hasil Opname | ✓ | ✓ | ✓ |
| UC-43 | Menyelesaikan Sesi Opname | ✓ | ✓ | ✓ |
| UC-44 | Menghapus Sesi Opname | ✓ | ✓ | ✓ |
| UC-45 | Melihat Barang Mendekati Expired | ✓ | ✓ | ✓ |
| UC-46 | Melihat Barang Expired dan Filter | ✓ | ✓ | ✓ |
| UC-47 | Menampilkan Laporan Barang Masuk | ✓ | ✓ | ✓ |
| UC-48 | Menampilkan Laporan Barang Keluar | ✓ | ✓ | ✓ |
| UC-49 | Menampilkan Laporan Stok dan Opname | ✓ | ✓ | ✓ |
| UC-50 | Menampilkan Laporan Barang Expired | ✓ | ✓ | ✓ |
| UC-51 | Mengekspor Laporan ke PDF | ✓ | ✓ | ✓ |
| UC-52 | Mengelola User | ✓ | ✓ | – |
| UC-53 | Mengubah dan Menghapus User | ✓ | ✓ | – |
| UC-54 | Mengubah Password User | ✓ | ✓ | – |
| UC-55 | Mengelola Notifikasi (lihat/tandai) | ✓ | ✓ | ✓ |
| UC-55 | Mengelola Notifikasi (generate) | ✓ | ✓ | – |

---

## 5. Catatan Implementasi dan Temuan

Temuan berikut berasal dari perbandingan backend dan frontend. Semua bertanda **[PERLU KONFIRMASI]** dan tidak mengubah isi skenario di atas.

| No | Topik | Temuan | Dampak |
|---|---|---|---|
| 1 | Tabel notifikasi | Modul notifikasi (model, service, controller, route, UI) masih aktif, tetapi tabel `notifications` sudah dihapus pada `migration_03_drop_tables.sql`. | Endpoint notifikasi berpotensi gagal; UC-55 tidak dapat berjalan pada basis data saat ini. |
| 2 | Kunci sesi pengguna | `AuthService` menyimpan `id_user`, sedangkan `AuthFilter` membaca `session('id')`. | Pemeriksaan status pengguna aktif pada filter dapat terlewat; pengguna nonaktif berpotensi tetap memiliki sesi. |
| 3 | Field produk | UI Produk mengirim `supplier_id`, `category_id`, dan `description`, sedangkan backend meminta `id_supplier` dan `id_category`; `ProductModel` tidak memiliki field `description`. | Sebagian data produk (supplier/kategori/deskripsi) berpotensi tidak tersimpan. |
| 4 | ID pada daftar produk | Backend mengembalikan `id_product` dan tidak mengembalikan `supplier_id`/`category_id`, sedangkan UI memakai `item.id`. | Aksi edit/hapus dan tampilan relasi produk berpotensi gagal. |
| 5 | Kode kategori | `next-code` mengembalikan `code`, sedangkan UI membaca `category_code`. | Kode kategori otomatis tidak terisi pada formulir. |
| 6 | ID kategori dan user | UI Kategori dan User memakai `item.id`, sedangkan backend mengembalikan `id_category`/`id_user`. | Aksi edit/hapus berpotensi tidak menemukan ID yang benar. |
| 7 | Nama field user | Form tambah user mengirim `nama`, sedangkan backend memakai `name`. | Nama pengguna berpotensi tidak tersimpan saat pembuatan akun. |
| 8 | Stock adjustment | UI mengirim `batch_id` tanpa `product_id`, sedangkan backend mewajibkan `product_id`. | Pembuatan adjustment berpotensi gagal validasi. |
| 9 | ID barang keluar | UI memakai `item.id`/`id` batch, sedangkan backend mengembalikan `id_outbound`/`id_batch`. | Aksi edit/hapus/complete berpotensi gagal. |
| 10 | Pengurangan stok outbound | Stok dikurangi saat create/update, lalu dikurangi lagi saat complete. | Stok dapat berkurang dua kali untuk dokumen yang sama. |
| 11 | Field stock opname | UI mengirim `session_id`/`product_id`, sedangkan backend membaca `id_opname`/`id_batch`. | Pengisian hasil opname berpotensi gagal. |
| 12 | Filter laporan opname | UI mengirim `session_id`, sedangkan controller membaca `id_opname`. | Laporan opname berpotensi tidak menampilkan data. |
| 13 | Export PDF | Endpoint `laporan/export-pdf` mengembalikan JSON; PDF dibuat di frontend dengan `jsPDF`. | Bila kebutuhan mengharuskan PDF di server, implementasi perlu ditambah. |
| 14 | Notifikasi otomatis | Method `createInboundCompletedNotification` dan `createOutboundCompletedNotification` ada, tetapi tidak dipanggil oleh service/controller inbound/outbound. | Notifikasi penyelesaian transaksi tidak muncul otomatis. |

### 5.1 Fitur yang Tidak Ditemukan

1. Purchase Order (PO) dan Delivery Order (DO) — baru berupa rencana pada `PLAN_PO_DO.md`.
2. Multi-gudang, multi-cabang, dan transfer antar gudang.
3. Disposal/penghapusan stok expired secara otomatis.
4. Profil mandiri dan ubah password mandiri untuk peran non-admin.
5. Pembuatan PDF di sisi server.
6. Notifikasi otomatis terjadwal (cron) selain generate manual.

---

## 6. Penutup

1. Dokumen ini disusun berdasarkan implementasi kode saat ini; perbedaan antara kebutuhan dan implementasi ditandai pada Bagian 5.
2. Seluruh pesan validasi dan pengecualian mengacu pada teks yang benar-benar digunakan pada service/controller.
3. Use case dan temuan bertanda `[PERLU KONFIRMASI]` perlu diputuskan sebelum dokumen dinyatakan final.
4. Perubahan kode setelah dokumen ini dibuat harus disertai pembaruan pada skenario, daftar use case, dan matriks aktor.





