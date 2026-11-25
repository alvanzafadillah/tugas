<?php
include 'koneksi.php';

$id_order = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$q_order = mysqli_query($conn, "SELECT * FROM orders WHERE id_order = $id_order");
$order = mysqli_fetch_assoc($q_order);

if (!$order) {
    die("Pesanan dengan ID #$id_order tidak ditemukan di database.");
}

$items = mysqli_query($conn, "
    SELECT oi.*, p.nama_produk
    FROM order_items oi
    JOIN products p ON oi.id_produk = p.id_produk
    WHERE oi.id_order = $id_order
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Pembelian #INV-00<?= $id_order ?> - Panja GeoCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #e2e8f0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .invoice-card {
            max-width: 680px;
            margin: 35px auto;
            background: #fff;
            border-radius: 16px;
            padding: 35px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .invoice-card { box-shadow: none; margin: 0; padding: 15px; }
        }
    </style>
</head>
<body>
<div class="invoice-card">
    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
        <div>
            <h4 class="fw-bold text-success mb-0">
                <i class="fa-solid fa-compass-drafting me-1"></i> PANJA GEOCRAFT
            </h4>
            <small class="text-muted">Studio Peta Hand-Drawn, Bendera Dunia & Eco-Merch</small>
        </div>
        <div class="text-end">
            <span class="badge bg-success fs-6">#INV-00<?= $order['id_order'] ?></span><br>
            <small class="text-muted"><?= htmlspecialchars($order['tanggal_pesan']) ?></small>
        </div>
    </div>

    <div class="row mb-3 small">
        <div class="col-6">
            <strong>Pembeli:</strong><br><?= htmlspecialchars($order['nama_pembeli']) ?><br>
            <strong>Tujuan:</strong> <?= htmlspecialchars($order['provinsi_tujuan']) ?>
        </div>
        <div class="col-6 text-end">
            <strong>Metode Pembayaran:</strong><br><?= htmlspecialchars($order['metode_pembayaran']) ?><br>
            <strong>Status:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($order['status_pesanan']) ?></span>
        </div>
    </div>

    <table class="table table-bordered small align-middle">
        <thead class="table-light">
            <tr>
                <th>Produk & Kustomisasi</th>
                <th class="text-center">Jml</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($it = mysqli_fetch_assoc($items)): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($it['nama_produk']) ?></strong><br>
                    <span class="text-muted"><?= htmlspecialchars($it['opsi_kustom']) ?></span>
                </td>
                <td class="text-center"><?= (int)$it['jumlah'] ?></td>
                <td class="text-end"><?= rupiah($it['subtotal']) ?></td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <td colspan="2" class="text-end">Ongkos Kirim Antar-Provinsi</td>
                <td class="text-end"><?= rupiah($order['ongkir']) ?></td>
            </tr>
            <tr class="table-success fw-bold fs-6">
                <td colspan="2" class="text-end">TOTAL PEMBAYARAN</td>
                <td class="text-end"><?= rupiah($order['total_bayar']) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="alert alert-info small mb-4">
        Terima kasih telah mendukung karya kartografi Panja GeoCraft (Alvanza Fadillah - NIM 25012096) dan gerakan bebas plastik!
    </div>

    <div class="d-flex justify-content-between no-print">
        <a href="PANJA_GEOCRAFT.php" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="fa-solid fa-store me-1"></i> Kembali ke Toko
        </a>
        <button onclick="window.print()" class="btn btn-dark rounded-pill px-4">
            <i class="fa-solid fa-print me-1"></i> Cetak Struk (PDF/Print)
        </button>
    </div>
</div>
</body>
</html>