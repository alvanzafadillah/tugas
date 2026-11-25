<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_geocraft";

$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Gagal terhubung ke MySQL localhost: " . mysqli_connect_error());
}

$sql_db = "CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
if (!mysqli_query($conn, $sql_db)) {
    die("Gagal membuat database $db: " . mysqli_error($conn));
}

mysqli_select_db($conn, $db);

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin'
)");

$cek_user = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM users");
$row_user = mysqli_fetch_assoc($cek_user);
if ((int)$row_user["jml"] === 0) {
    mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES
        ('adminpanja', '12345', 'Alvanza Fadillah (Panja)', 'admin')");
}

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS products (
    id_produk INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(150) NOT NULL,
    kategori VARCHAR(50) NOT NULL,
    wilayah VARCHAR(50) NOT NULL,
    harga INT NOT NULL,
    stok INT NOT NULL,
    fakta_geografi TEXT NOT NULL,
    gambar VARCHAR(255) NOT NULL
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    id_order INT AUTO_INCREMENT PRIMARY KEY,
    nama_pembeli VARCHAR(100) NOT NULL,
    provinsi_tujuan VARCHAR(50) NOT NULL,
    ongkir INT NOT NULL,
    total_bayar INT NOT NULL,
    metode_pembayaran VARCHAR(80) NOT NULL,
    status_pesanan VARCHAR(30) DEFAULT 'Diproses',
    tanggal_pesan TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_items (
    id_detail INT AUTO_INCREMENT PRIMARY KEY,
    id_order INT NOT NULL,
    id_produk INT NOT NULL,
    opsi_kustom VARCHAR(100) NOT NULL,
    jumlah INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (id_order) REFERENCES orders(id_order) ON DELETE CASCADE
)");

$cek_prod = mysqli_query($conn, "SELECT COUNT(*) AS jml FROM products");
$row_prod = mysqli_fetch_assoc($cek_prod);
if ((int)$row_prod["jml"] === 0) {
    $produk_awal = [
        [
            "Peta Hand-Drawn Sumatera Utara (Full 33 Kab/Kota)",
            "Peta Provinsi",
            "Sumatera",
            125000,
            15,
            "Sumatera Utara terdiri dari 25 kabupaten dan 8 kota dengan pusat pemerintahan di Kota Medan serta memiliki Danau Toba sebagai danau vulkanik terbesar di dunia.",
            "https://images.unsplash.com/photo-1524661135-423995f22d0b?auto=format&fit=crop&w=600&q=80"
        ],
        [
            "Peta Hand-Drawn Provinsi Jawa Barat & Topografi",
            "Peta Provinsi",
            "Jawa",
            120000,
            10,
            "Jawa Barat memiliki 18 kabupaten dan 9 kota, didominasi bentang alam pegunungan vulkanik aktif di bagian tengah dan selatan.",
            "https://images.unsplash.com/photo-1569336415962-a4bd9f69cd83?auto=format&fit=crop&w=600&q=80"
        ],
        [
            "Paket Bendera Meja & Pin Enamel Negara ASEAN",
            "Bendera Dunia",
            "Asia",
            85000,
            25,
            "ASEAN didirikan pada 8 Agustus 1967 di Bangkok. Lambang ikatan padi melambangkan persahabatan dan solidaritas negara-negara Asia Tenggara.",
            "https://images.unsplash.com/photo-1532375810709-75b1da00537c?auto=format&fit=crop&w=600&q=80"
        ],
        [
            "Bendera Dinding Rasio Unik Nepal & Swiss (Kolektor)",
            "Bendera Dunia",
            "Dunia",
            95000,
            8,
            "Nepal adalah negara dengan bentuk bendera unik dua segitiga bertumpuk, sedangkan bendera Swiss berbentuk persegi simetris.",
            "https://images.unsplash.com/photo-1518709268805-4e9042af9f23?auto=format&fit=crop&w=600&q=80"
        ],
        [
            "Totebag Kanvas Peta Garis Pantai Nusantara (Bebas Plastik)",
            "Eco-Merch",
            "Sumatera",
            65000,
            30,
            "Indonesia memiliki garis pantai panjang lebih dari 99.000 km. Satu totebag kanvas ini membantu mengurangi pemakaian kantong plastik sekali pakai.",
            "https://images.unsplash.com/photo-1544816155-12df9643f363?auto=format&fit=crop&w=600&q=80"
        ],
        [
            "Tumbler Stainless Kontur Peta Pulau Kalimantan",
            "Eco-Merch",
            "Kalimantan",
            110000,
            12,
            "Pulau Kalimantan (Borneo) adalah pulau terbesar ketiga di dunia yang wilayahnya terbagi menjadi tiga negara: Indonesia, Malaysia, dan Brunei.",
            "https://images.unsplash.com/photo-1602143407151-7111542de6e8?auto=format&fit=crop&w=600&q=80"
        ]
    ];

    foreach ($produk_awal as $item) {
        $n = mysqli_real_escape_string($conn, $item[0]);
        $k = mysqli_real_escape_string($conn, $item[1]);
        $w = mysqli_real_escape_string($conn, $item[2]);
        $h = (int)$item[3];
        $s = (int)$item[4];
        $f = mysqli_real_escape_string($conn, $item[5]);
        $g = mysqli_real_escape_string($conn, $item[6]);

        mysqli_query($conn, "INSERT INTO products (nama_produk, kategori, wilayah, harga, stok, fakta_geografi, gambar)
                             VALUES ('$n', '$k', '$w', $h, $s, '$f', '$g')");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Database.php - Panja GeoCraft</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
<div class="container" style="max-width: 650px;">
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h4 class="fw-bold text-success mb-2">Database Localhost Berhasil Dibuat via Database.php!</h4>
        <p class="text-muted small">Database <strong><?= htmlspecialchars($db) ?></strong> di server <strong><?= htmlspecialchars($host) ?></strong> sudah siap beserta tabel <code>users</code>, <code>products</code>, <code>orders</code>, dan <code>order_items</code>.</p>
        <div class="d-flex gap-2 mt-2">
            <a href="PANJA_GEOCRAFT.php" class="btn btn-success rounded-pill px-4">Buka Toko Panja GeoCraft</a>
            <a href="admin.php" class="btn btn-dark rounded-pill px-4">Login Admin</a>
        </div>
    </div>
</div>
</body>
</html>