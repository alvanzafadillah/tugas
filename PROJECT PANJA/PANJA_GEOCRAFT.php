<?php
include 'koneksi.php';

// Inisialisasi Keranjang
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Tambah ke Keranjang dengan Opsi Kustom
if (isset($_POST['add_to_cart'])) {
    $id_produk = (int)$_POST['id_produk'];
    $opsi = mysqli_real_escape_string($conn, $_POST['opsi_kustom']);
    $biaya_tambahan = (int)$_POST['biaya_tambahan'];
    
    $q = mysqli_query($conn, "SELECT * FROM products WHERE id_produk = $id_produk");
    $prod = mysqli_fetch_assoc($q);
    
    if ($prod && $prod['stok'] > 0) {
        $cart_key = $id_produk . '_' . md5($opsi);
        if (isset($_SESSION['cart'][$cart_key])) {
            if ($_SESSION['cart'][$cart_key]['jumlah'] < $prod['stok']) {
                $_SESSION['cart'][$cart_key]['jumlah']++;
            }
        } else {
            $_SESSION['cart'][$cart_key] = [
                'id_produk' => $id_produk,
                'nama_produk' => $prod['nama_produk'],
                'harga_satuan' => $prod['harga'] + $biaya_tambahan,
                'opsi_kustom' => $opsi,
                'jumlah' => 1
            ];
        }
    }
    header("Location: PANJA_GEOCRAFT.php");
    exit;
}

// Hapus Item Keranjang
if (isset($_GET['hapus_item'])) {
    $key = $_GET['hapus_item'];
    unset($_SESSION['cart'][$key]);
    header("Location: PANJA_GEOCRAFT.php");
    exit;
}

// Proses Checkout, Kode Promo & Kurangi Stok Otomatis
if (isset($_POST['checkout']) && !empty($_SESSION['cart'])) {
    $nama_pembeli = mysqli_real_escape_string($conn, $_POST['nama_pembeli']);
    $data_provinsi = explode('|', $_POST['provinsi_ongkir']);
    $provinsi = mysqli_real_escape_string($conn, $data_provinsi[0]);
    $ongkir = (int)$data_provinsi[1];
    $metode = mysqli_real_escape_string($conn, $_POST['metode_pembayaran']);
    $kode_promo = strtoupper(trim($_POST['kode_promo'] ?? ''));
    
    $total_barang = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_barang += ($item['harga_satuan'] * $item['jumlah']);
    }
    
    // Logika Diskon Voucher Khas Panja
    $diskon = 0;
    if ($kode_promo === 'PANJAGEO' || $kode_promo === 'BEBASPLASTIK') {
        $diskon = 15000;
        $metode .= " (Promo $kode_promo -Rp 15.000)";
    }
    
    $total_bayar = max(0, ($total_barang - $diskon) + $ongkir);
    
    mysqli_query($conn, "INSERT INTO orders (nama_pembeli, provinsi_tujuan, ongkir, total_bayar, metode_pembayaran) 
                         VALUES ('$nama_pembeli', '$provinsi', $ongkir, $total_bayar, '$metode')");
    $id_order = mysqli_insert_id($conn);
    
    foreach ($_SESSION['cart'] as $item) {
        $id_p = $item['id_produk'];
        $opsi = $item['opsi_kustom'];
        $jml  = $item['jumlah'];
        $sub  = $item['harga_satuan'] * $jml;
        
        mysqli_query($conn, "INSERT INTO order_items (id_order, id_produk, opsi_kustom, jumlah, subtotal) 
                             VALUES ($id_order, $id_p, '$opsi', $jml, $sub)");
        mysqli_query($conn, "UPDATE products SET stok = stok - $jml WHERE id_produk = $id_p");
    }
    
    $_SESSION['cart'] = [];
    header("Location: struk.php?id=" . $id_order);
    exit;
}

// Filter & Pencarian
$where = "WHERE 1=1";
if (!empty($_GET['kategori'])) {
    $kat = mysqli_real_escape_string($conn, $_GET['kategori']);
    $where .= " AND kategori = '$kat'";
}
if (!empty($_GET['cari'])) {
    $cari = mysqli_real_escape_string($conn, $_GET['cari']);
    $where .= " AND (nama_produk LIKE '%$cari%' OR wilayah LIKE '%$cari%')";
}
$products = mysqli_query($conn, "SELECT * FROM products $where ORDER BY id_produk DESC");

$total_item_cart = 0;
foreach ($_SESSION['cart'] as $c) { $total_item_cart += $c['jumlah']; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Panja GeoCraft - Studio Peta Hand-Drawn, Bendera & Eco-Merch</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .hero-banner {
            background: linear-gradient(135deg, #0f766e 0%, #1e3a8a 100%);
            color: white; border-radius: 16px; padding: 35px; margin-bottom: 25px;
            box-shadow: 0 10px 25px rgba(15, 118, 110, 0.2);
        }
        .card-product {
            border: none; border-radius: 14px; overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s; background: #fff;
        }
        .card-product:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.08); }
        .product-img { height: 190px; object-fit: cover; width: 100%; }
        .badge-wilayah { position: absolute; top: 12px; left: 12px; background: rgba(15, 23, 42, 0.85); color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; }
        .fact-box { background: #eff6ff; border-left: 4px solid #2563eb; padding: 10px; border-radius: 6px; font-size: 0.82rem; color: #1e3a8a; }
    </style>
</head>
<body>

<!-- Navbar Panja GeoCraft -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="PANJA_GEOCRAFT.php" style="color: #0f766e; font-size: 1.35rem;">
            <i class="fa-solid fa-compass-drafting me-2"></i>Panja GeoCraft
        </a>
        <form class="d-flex mx-auto w-50" method="GET" action="PANJA_GEOCRAFT.php">
            <input class="form-control me-2 rounded-pill" type="search" name="cari" placeholder="Cari karya peta Panja, bendera negara, atau wilayah..." value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
            <button class="btn btn-outline-success rounded-pill px-4" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-dark rounded-pill position-relative px-3" data-bs-toggle="modal" data-bs-target="#modalCart">
                <i class="fa-solid fa-cart-shopping me-1"></i> Keranjang
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $total_item_cart ?>
                </span>
            </button>
            <a href="admin.php" class="btn btn-outline-secondary rounded-pill btn-sm px-3"><i class="fa-solid fa-user-gear me-1"></i> Admin</a>
        </div>
    </div>
</nav>

<div class="container">
    <!-- Hero Banner Khas Panja GeoCraft -->
    <div class="hero-banner d-flex justify-content-between align-items-center flex-wrap">
        <div class="col-md-8">
            <span class="badge bg-warning text-dark mb-2 px-3 py-1 rounded-pill fw-bold">
                <i class="fa-solid fa-crown me-1"></i> OFFICIAL STUDIO BY PANJA (ALVANZA FADILLAH)
            </span>
            <h2 class="fw-bold mb-2">PANJA GEOCRAFT: KARYA PETA & BENDERA MANTAP!</h2>
            <p class="mb-2 text-light opacity-90">
                Pusat koleksi peta provinsi <em>hand-drawn</em> (detail batas kabupaten/kota), bendera dunia, dan <em>eco-merch</em> bebas plastik. Gunakan kode promo <span class="badge bg-light text-dark fw-bold">PANJAGEO</span> untuk diskon Rp 15.000!
            </p>
        </div>
        <div class="col-md-4 text-end d-none d-md-block">
            <i class="fa-solid fa-map-location-dot" style="font-size: 5.5rem; opacity: 0.25;"></i>
        </div>
    </div>

    <!-- Filter Kategori Pills -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
        <a href="PANJA_GEOCRAFT.php" class="btn <?= empty($_GET['kategori']) ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-4 btn-sm">
            <i class="fa-solid fa-border-all me-1"></i> Semua Koleksi Panja
        </a>
        <a href="PANJA_GEOCRAFT.php?kategori=Peta Provinsi" class="btn <?= ($_GET['kategori'] ?? '') == 'Peta Provinsi' ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-4 btn-sm">
            <i class="fa-solid fa-map me-1"></i> Peta Hand-Drawn
        </a>
        <a href="PANJA_GEOCRAFT.php?kategori=Bendera Dunia" class="btn <?= ($_GET['kategori'] ?? '') == 'Bendera Dunia' ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-4 btn-sm">
            <i class="fa-solid fa-flag me-1"></i> Bendera & Pin Negara
        </a>
        <a href="PANJA_GEOCRAFT.php?kategori=Eco-Merch" class="btn <?= ($_GET['kategori'] ?? '') == 'Eco-Merch' ? 'btn-dark' : 'btn-outline-dark' ?> rounded-pill px-4 btn-sm">
            <i class="fa-solid fa-leaf me-1"></i> Eco-Geo Merch
        </a>
    </div>

    <!-- Grid Katalog Produk -->
    <div class="row g-4 mb-5">
        <?php while ($row = mysqli_fetch_assoc($products)): ?>
        <div class="col-md-4">
            <div class="card card-product h-100 shadow-sm position-relative">
                <span class="badge-wilayah"><i class="fa-solid fa-location-dot me-1 text-warning"></i> <?= htmlspecialchars($row['wilayah']) ?></span>
                <img src="<?= htmlspecialchars($row['gambar']) ?>" class="product-img" alt="<?= htmlspecialchars($row['nama_produk']) ?>">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="badge bg-success-subtle text-success"><?= htmlspecialchars($row['kategori']) ?></span>
                        <small class="text-muted fw-semibold">Stok: <?= $row['stok'] ?></small>
                    </div>
                    <h5 class="card-title fw-bold text-dark"><?= htmlspecialchars($row['nama_produk']) ?></h5>
                    <h5 class="text-primary fw-bold mb-3"><?= rupiah($row['harga']) ?></h5>

                    <!-- Box Fakta Geografi Singkat -->
                    <div class="fact-box mb-3">
                        <strong><i class="fa-solid fa-earth-asia me-1"></i> Catatan Geografi Panja:</strong><br>
                        <?= htmlspecialchars($row['fakta_geografi']) ?>
                    </div>

                    <!-- Form Kustomisasi & Beli -->
                    <form method="POST" action="PANJA_GEOCRAFT.php" class="mt-auto">
                        <input type="hidden" name="id_produk" value="<?= $row['id_produk'] ?>">
                        <label class="form-label small fw-semibold mb-1">Pilih Varian / Kustomisasi:</label>
                        <select name="opsi_kustom" class="form-select form-select-sm mb-2" onchange="updateBiaya(this, <?= $row['id_produk'] ?>)">
                            <option value="Standar (Tanpa Tambahan)" data-biaya="0">Varian Standar (+Rp 0)</option>
                            <option value="Bingkai Kayu Eksklusif / Ukuran Besar" data-biaya="25000">Upgrade Bingkai / Ukuran A3 (+Rp 25.000)</option>
                            <option value="Paket Gift Box + Tanda Tangan Kartografer" data-biaya="15000">Gift Box + Signed by Panja (+Rp 15.000)</option>
                        </select>
                        <input type="hidden" name="biaya_tambahan" id="biaya_<?= $row['id_produk'] ?>" value="0">

                        <?php if ($row['stok'] > 0): ?>
                            <button type="submit" name="add_to_cart" class="btn btn-success w-100 rounded-pill fw-semibold">
                                <i class="fa-solid fa-cart-plus me-1"></i> Tambah ke Keranjang
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary w-100 rounded-pill" disabled>Stok Habis (Sold Out)</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Footer Identitas Mahasiswa -->
<footer class="bg-white border-top py-4 mt-5">
    <div class="container d-flex justify-content-between align-items-center flex-wrap small text-muted">
        <div>
            <strong class="text-dark">Panja GeoCraft Studio</strong> &copy; 2026 — E-Commerce Kartografi, Vexillology & Eco-Merch.
        </div>
        <div>
            Dikembangkan oleh: <strong>Alvanza Fadillah Buyong (Panja)</strong> | NIM: <strong>25012096</strong> | Kelas: <strong>25M11</strong> (D3 Manajemen Informatika - Politeknik Ganesha Medan)
        </div>
    </div>
</footer>

<!-- Modal Keranjang & Checkout -->
<div class="modal fade" id="modalCart" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fa-solid fa-bag-shopping me-2"></i>Keranjang Belanja - Panja GeoCraft</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($_SESSION['cart'])): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-cart-arrow-down fa-3x mb-3"></i>
                        <p>Keranjang belanja kamu masih kosong. Yuk koleksi peta buatan Panja atau bendera negara favoritmu!</p>
                    </div>
                <?php else: ?>
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Produk & Varian</th>
                                <th>Harga</th>
                                <th>Jml</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            foreach ($_SESSION['cart'] as $key => $item): 
                                $sub = $item['harga_satuan'] * $item['jumlah'];
                                $grand_total += $sub;
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($item['nama_produk']) ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($item['opsi_kustom']) ?></small>
                                </td>
                                <td><?= rupiah($item['harga_satuan']) ?></td>
                                <td><?= $item['jumlah'] ?></td>
                                <td class="fw-bold"><?= rupiah($sub) ?></td>
                                <td>
                                    <a href="PANJA_GEOCRAFT.php?hapus_item=<?= $key ?>" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <hr>
                    <form method="POST" action="PANJA_GEOCRAFT.php">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-truck-fast me-2"></i>Data Pengiriman, Ongkir & Voucher</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Lengkap Pembeli</label>
                                <input type="text" name="nama_pembeli" class="form-control" required placeholder="Masukkan nama pembeli...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Provinsi Tujuan (Otomatis Hitung Ongkir)</label>
                                <select name="provinsi_ongkir" class="form-select" required>
                                    <option value="Sumatera Utara (Medan & Sekitarnya)|10000">Sumatera Utara - Rp 10.000</option>
                                    <option value="Sumatera Lainnya (Aceh/Riau/Sumbar)|20000">Provinsi Sumatera Lainnya - Rp 20.000</option>
                                    <option value="Pulau Jawa (Jabodetabek/Jabar/Jatim)|28000">Pulau Jawa - Rp 28.000</option>
                                    <option value="Kalimantan / Sulawesi / Bali|38000">Kalimantan / Sulawesi / Bali - Rp 38.000</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Metode Pembayaran</label>
                                <select name="metode_pembayaran" class="form-select" required>
                                    <option value="QRIS Panja GeoCraft">Scan QRIS (Instan)</option>
                                    <option value="Transfer Virtual Account">Transfer Bank / Virtual Account</option>
                                    <option value="COD (Bayar di Tempat)">COD (Bayar di Tempat)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Kode Voucher Promo (Opsional)</label>
                                <input type="text" name="kode_promo" class="form-control" placeholder="Ketik: PANJAGEO atau BEBASPLASTIK">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <div>
                                <span class="text-muted small">Subtotal Barang (Sebelum Ongkir & Diskon):</span>
                                <h4 class="fw-bold text-success mb-0"><?= rupiah($grand_total) ?></h4>
                            </div>
                            <button type="submit" name="checkout" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold">
                                Proses Checkout <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function updateBiaya(selectElem, idProduk) {
    var biaya = selectElem.options[selectElem.selectedIndex].getAttribute('data-biaya');
    document.getElementById('biaya_' + idProduk).value = biaya;
}
</script>
</body>
</html>