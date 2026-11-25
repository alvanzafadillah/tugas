<?php
include 'koneksi.php';

// Proses Logout Admin
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_login']);
    unset($_SESSION['admin_nama']);
    header("Location: admin.php");
    exit;
}

// Proses Login Admin ke Tabel users di phpMyAdmin
$error_login = "";
if (isset($_POST['login_admin'])) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));

    $q_user = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' AND password = '$password' LIMIT 1");
    if ($q_user && mysqli_num_rows($q_user) > 0) {
        $data_user = mysqli_fetch_assoc($q_user);
        $_SESSION['admin_login'] = true;
        $_SESSION['admin_nama']  = $data_user['nama_lengkap'];
        header("Location: admin.php");
        exit;
    } else {
        $error_login = "Username atau password salah! Cek kembali data di tabel users phpMyAdmin.";
    }
}

// Jika belum login, tampilkan Halaman Login Admin
if (!isset($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true):
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Admin - Panja GeoCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f766e 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 18px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
<div class="card login-card p-4 bg-white">
    <div class="text-center mb-3">
        <i class="fa-solid fa-compass-drafting fa-2x text-success mb-2"></i>
        <h4 class="fw-bold mb-0">Login Admin Panja GeoCraft</h4>
        <small class="text-muted">Terhubung ke MySQL / phpMyAdmin (`db_geocraft`)</small>
    </div>

    <?php if (!empty($error_login)): ?>
        <div class="alert alert-danger small py-2"><?= htmlspecialchars($error_login) ?></div>
    <?php endif; ?>

    <form method="POST" action="admin.php">
        <div class="mb-3">
            <label class="form-label small fw-bold">Username</label>
            <input type="text" name="username" class="form-control" required placeholder="Masukkan username admin">
        </div>
        <div class="mb-3">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" class="form-control" required placeholder="Masukkan password admin">
        </div>
        <button type="submit" name="login_admin" class="btn btn-dark w-100 rounded-pill fw-semibold py-2">
            <i class="fa-solid fa-right-to-bracket me-1"></i> Masuk ke Dashboard Admin
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="PANJA_GEOCRAFT.php" class="small text-decoration-none text-muted">
            <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Toko Panja GeoCraft
        </a>
    </div>
</div>
</body>
</html>
<?php
exit;
endif;

// Tambah Produk Baru
if (isset($_POST['tambah_produk'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama_produk']);
    $kat  = mysqli_real_escape_string($conn, $_POST['kategori']);
    $wil  = mysqli_real_escape_string($conn, $_POST['wilayah']);
    $hrg  = (int)$_POST['harga'];
    $stk  = (int)$_POST['stok'];
    $fkt  = mysqli_real_escape_string($conn, $_POST['fakta_geografi']);
    $img  = mysqli_real_escape_string($conn, $_POST['gambar']);
    
    mysqli_query($conn, "INSERT INTO products (nama_produk, kategori, wilayah, harga, stok, fakta_geografi, gambar) 
                         VALUES ('$nama', '$kat', '$wil', $hrg, $stk, '$fkt', '$img')");
    header("Location: admin.php");
    exit;
}

// Update Cepat Stok & Harga Produk
if (isset($_POST['update_produk'])) {
    $id_p = (int)$_POST['id_produk'];
    $hrg  = (int)$_POST['harga'];
    $stk  = (int)$_POST['stok'];
    
    mysqli_query($conn, "UPDATE products SET harga = $hrg, stok = $stk WHERE id_produk = $id_p");
    header("Location: admin.php");
    exit;
}

// Hapus Produk
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($conn, "DELETE FROM products WHERE id_produk = $id");
    header("Location: admin.php");
    exit;
}

// Selesaikan / Kirim Pesanan
if (isset($_GET['selesai_order'])) {
    $id_o = (int)$_GET['selesai_order'];
    mysqli_query($conn, "UPDATE orders SET status_pesanan = 'Selesai Dikirim' WHERE id_order = $id_o");
    header("Location: admin.php");
    exit;
}

$q_omzet = mysqli_query($conn, "SELECT SUM(total_bayar) as omzet, COUNT(*) as jml_order FROM orders");
$stat = mysqli_fetch_assoc($q_omzet);
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id_produk DESC");
$orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY id_order DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin - Panja GeoCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">
        <span class="navbar-brand fw-bold">
            <i class="fa-solid fa-user-shield me-2 text-warning"></i>Panel Admin Panja GeoCraft
            <small class="text-light opacity-75 fs-6 ms-2">(<?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?>)</small>
        </span>
        <div class="d-flex gap-2">
            <a href="PANJA_GEOCRAFT.php" class="btn btn-outline-light btn-sm rounded-pill px-3">
                <i class="fa-solid fa-store me-1"></i> Toko
            </a>
            <a href="admin.php?logout=1" class="btn btn-danger btn-sm rounded-pill px-3">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-success text-white p-3 rounded-4">
                <div class="small opacity-75">Total Pendapatan Masuk (Omzet)</div>
                <h3 class="fw-bold mb-0"><?= rupiah($stat['omzet'] ?? 0) ?></h3>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm bg-primary text-white p-3 rounded-4">
                <div class="small opacity-75">Total Transaksi Tercatat</div>
                <h3 class="fw-bold mb-0"><?= (int)($stat['jml_order'] ?? 0) ?> Pesanan</h3>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <h6 class="fw-bold border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-plus-circle me-1 text-success"></i> Tambah Produk Baru
                </h6>
                <form method="POST" action="admin.php">
                    <div class="mb-2">
                        <label class="small fw-semibold">Nama Produk</label>
                        <input type="text" name="nama_produk" class="form-control form-control-sm" required placeholder="Contoh: Peta Hand-Drawn Aceh">
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="small fw-semibold">Kategori</label>
                            <select name="kategori" class="form-select form-select-sm">
                                <option value="Peta Provinsi">Peta Provinsi</option>
                                <option value="Bendera Dunia">Bendera Dunia</option>
                                <option value="Eco-Merch">Eco-Merch</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="small fw-semibold">Wilayah/Benua</label>
                            <input type="text" name="wilayah" class="form-control form-control-sm" placeholder="Sumatera / Asia" required>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="small fw-semibold">Harga (Rp)</label>
                            <input type="number" name="harga" class="form-control form-control-sm" required min="0">
                        </div>
                        <div class="col-6">
                            <label class="small fw-semibold">Stok</label>
                            <input type="number" name="stok" class="form-control form-control-sm" required min="0">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="small fw-semibold">Catatan / Fakta Geografi</label>
                        <textarea name="fakta_geografi" class="form-control form-control-sm" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="small fw-semibold">Path / URL Gambar</label>
                        <input type="text" name="gambar" class="form-control form-control-sm" value="https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&w=600&q=80" required>
                    </div>
                    <button type="submit" name="tambah_produk" class="btn btn-dark btn-sm w-100 rounded-pill fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Simpan ke Database
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card border-0 shadow-sm rounded-4 p-3 mb-4">
                <h6 class="fw-bold border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-receipt me-1 text-primary"></i> Daftar Pesanan Masuk (Real-Time)
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Pembeli & Tujuan</th>
                                <th>Total Bayar</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($o = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td><a href="struk.php?id=<?= $o['id_order'] ?>" class="fw-bold text-decoration-none">#INV-00<?= $o['id_order'] ?></a></td>
                                <td>
                                    <strong><?= htmlspecialchars($o['nama_pembeli']) ?></strong><br>
                                    <span class="text-muted"><?= htmlspecialchars($o['provinsi_tujuan']) ?></span>
                                </td>
                                <td class="fw-bold"><?= rupiah($o['total_bayar']) ?></td>
                                <td><span class="badge bg-info text-dark"><?= htmlspecialchars($o['status_pesanan']) ?></span></td>
                                <td>
                                    <?php if ($o['status_pesanan'] != 'Selesai Dikirim'): ?>
                                        <a href="admin.php?selesai_order=<?= $o['id_order'] ?>" class="btn btn-success btn-sm py-0 px-2">Kirim</a>
                                    <?php else: ?>
                                        <span class="text-success fw-semibold">Terkirim</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 p-3">
                <h6 class="fw-bold border-bottom pb-2 mb-3">
                    <i class="fa-solid fa-boxes-stacked me-1 text-warning"></i> Kelola Harga, Stok & Hapus Produk
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle small">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th>Harga (Rp)</th>
                                <th>Stok</th>
                                <th>Simpan</th>
                                <th>Hapus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($p['nama_produk']) ?><br>
                                    <span class="text-muted"><?= htmlspecialchars($p['kategori']) ?> • <?= htmlspecialchars($p['wilayah']) ?></span>
                                </td>
                                <form method="POST" action="admin.php">
                                    <input type="hidden" name="id_produk" value="<?= $p['id_produk'] ?>">
                                    <td style="width: 120px;">
                                        <input type="number" name="harga" value="<?= $p['harga'] ?>" class="form-control form-control-sm" min="0">
                                    </td>
                                    <td style="width: 85px;">
                                        <input type="number" name="stok" value="<?= $p['stok'] ?>" class="form-control form-control-sm" min="0">
                                    </td>
                                    <td>
                                        <button type="submit" name="update_produk" class="btn btn-primary btn-sm py-0 px-2">Update</button>
                                    </td>
                                </form>
                                <td>
                                    <a href="admin.php?hapus=<?= $p['id_produk'] ?>" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Hapus produk ini?')">X</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>