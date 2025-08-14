<?php include 'include/koneksi.php'; ?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Transaksi Harian</title>
</head>
<body>
    <h2>Laporan Transaksi Harian</h2>
    <form method="GET">
        Tanggal: <input type="date" name="tanggal" value="<?= $_GET['tanggal'] ?? date('Y-m-d') ?>">
        <button type="submit">Tampilkan</button>
        <?php if (!empty($_GET['tanggal'])): ?>
            <a href="export_excel.php?tanggal=<?= $_GET['tanggal'] ?>">Export ke Excel</a>
        <?php endif; ?>
    </form>

    <br>
    <table border="1" cellpadding="8">
        <tr>
            <th>No</th>
            <th>Nama Pelanggan</th>
            <th>Tanggal</th>
            <th>Total</th>
        </tr>

        <?php
        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        $sql = "SELECT * FROM transaksi WHERE DATE(tanggal) = '$tanggal'";
        $result = $koneksi->query($sql);
        $no = 1;
        $total_harian = 0;

        while ($row = $result->fetch_assoc()):
            $total_harian += $row['total'];
        ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= $row['nama_pelanggan'] ?></td>
                <td><?= $row['tanggal'] ?></td>
                <td><?= number_format($row['total'], 0, ',', '.') ?></td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <td colspan="3"><strong>Total Harian</strong></td>
            <td><strong><?= number_format($total_harian, 0, ',', '.') ?></strong></td>
        </tr>
    </table>
</body>
</html>
