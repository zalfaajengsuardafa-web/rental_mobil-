<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$action  = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

function generateKodeTransaksi($conn) {
    $date   = date('Ymd');
    $prefix = 'TRX-' . $date . '-';
    $stmt   = $conn->prepare("SELECT COUNT(*) as total FROM transaksi WHERE kode_transaksi LIKE ?");
    $like   = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc()['total'] + 1;
    $stmt->close();
    return $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);
}

// ============================================================
// CREATE / UPDATE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pelanggan_id    = (int)($_POST['pelanggan_id']    ?? 0);
    $mobil_id        = (int)($_POST['mobil_id']        ?? 0);
    $tanggal_mulai   = htmlspecialchars(trim($_POST['tanggal_mulai']   ?? ''));
    $tanggal_selesai = htmlspecialchars(trim($_POST['tanggal_selesai'] ?? ''));

    if (empty($tanggal_mulai) || empty($tanggal_selesai)) {
        $_SESSION['flash_message'] = 'Tanggal wajib diisi!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: transaksi.php');
        exit;   
    }

    if ($tanggal_selesai < $tanggal_mulai) {
        $_SESSION['flash_message'] = 'Tanggal selesai harus setelah tanggal mulai!';
        $_SESSION['flash_type'] = 'danger';
        header('Location: transaksi.php');
        exit;
    }
   
    $jumlah_hari = max(1, (strtotime($tanggal_selesai) - strtotime($tanggal_mulai)) / 86400 + 1);

    $stmtHarga = $conn->prepare("SELECT harga_per_hari FROM mobil WHERE id=?");
    $stmtHarga->bind_param('i', $mobil_id);
    $stmtHarga->execute();
    $harga = $stmtHarga->get_result()->fetch_assoc()['harga_per_hari'] ?? 0;
    $stmtHarga->close();

    $harga_per_hari = $harga;  
    $total_harga = $jumlah_hari * $harga;

    $status  = htmlspecialchars(trim($_POST['status']  ?? 'aktif'));
    $catatan = htmlspecialchars(trim($_POST['catatan'] ?? ''));
    $post_id = (int)($_POST['id'] ?? 0);

    if ($post_id > 0) {
        $stmt = $conn->prepare("UPDATE transaksi SET pelanggan_id=?, mobil_id=?, tanggal_mulai=?, tanggal_selesai=?, jumlah_hari=?, harga_per_hari=?, total_harga=?, status=?, catatan=? WHERE id=?");
        $stmt->bind_param('iissiddssi', $pelanggan_id, $mobil_id, $tanggal_mulai, $tanggal_selesai, $jumlah_hari, $harga_per_hari, $total_harga, $status, $catatan, $post_id);
        $stmt->execute();
        $stmt->close();

        $mobil_status = ($status === 'aktif') ? 'disewa' : 'tersedia';
        $stm2 = $conn->prepare("UPDATE mobil SET status=? WHERE id=?");
        $stm2->bind_param('si', $mobil_status, $mobil_id);
        $stm2->execute();
        $stm2->close();

        $_SESSION['flash_message'] = 'Transaksi berhasil diperbarui!';
        $_SESSION['flash_type']    = 'success';
    } else {
        $kode = generateKodeTransaksi($conn);
        $stmt = $conn->prepare("INSERT INTO transaksi (kode_transaksi, pelanggan_id, mobil_id, tanggal_mulai, tanggal_selesai, jumlah_hari, harga_per_hari, total_harga, status, catatan) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('siissiidss', $kode, $pelanggan_id, $mobil_id, $tanggal_mulai, $tanggal_selesai, $jumlah_hari, $harga_per_hari, $total_harga, $status, $catatan);
        $stmt->execute();
        $stmt->close();

        $stm2 = $conn->prepare("UPDATE mobil SET status='disewa' WHERE id=?");
        $stm2->bind_param('i', $mobil_id);
        $stm2->execute();
        $stm2->close();

        $_SESSION['flash_message'] = "Transaksi $kode berhasil dibuat!";
        $_SESSION['flash_type']    = 'success';
    }
    header('Location: transaksi.php');
    exit;
}

// ============================================================
// DELETE
// ============================================================
if ($action === 'hapus' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT mobil_id FROM transaksi WHERE id=?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $del_trx = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM transaksi WHERE id=?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $stmt->close();

    if ($del_trx) {
        $stm2 = $conn->prepare("UPDATE mobil SET status='tersedia' WHERE id=?");
        $stm2->bind_param('i', $del_trx['mobil_id']);
        $stm2->execute();
        $stm2->close();
    }

    $_SESSION['flash_message'] = 'Transaksi berhasil dihapus.';
    $_SESSION['flash_type']    = 'success';
    header('Location: transaksi.php');
    exit;
}

// ============================================================
// FETCH EDIT DATA
// ============================================================
$edit_data = null;
if (($action === 'edit') && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM transaksi WHERE id=?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edit_data) { header('Location: transaksi.php'); exit; }
}

$pelanggan_list = $conn->query("SELECT id, nama, telepon FROM pelanggan ORDER BY nama");
$mobil_list_sel = $conn->query("SELECT id, nama_mobil, kode_mobil, harga_per_hari, status FROM mobil ORDER BY nama_mobil");

// ============================================================
// READ & PAGINATION
// ============================================================
$search   = htmlspecialchars(trim($_GET['search'] ?? ''));
$per_page = 8;
$page_num = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page_num - 1) * $per_page;

$where = ''; $params = []; $types = '';
if ($search !== '') {
    $like   = '%' . $search . '%';
    $where  = "WHERE t.kode_transaksi LIKE ? OR p.nama LIKE ? OR m.nama_mobil LIKE ? OR t.status LIKE ?";
    $params = [$like, $like, $like, $like];
    $types  = 'ssss';
}

$base_sql = "FROM transaksi t JOIN pelanggan p ON t.pelanggan_id=p.id JOIN mobil m ON t.mobil_id=m.id $where";

$count_stmt = $conn->prepare("SELECT COUNT(*) as total $base_sql");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows  = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = ceil($total_rows / $per_page);

// Menghapus m.gambar dari SQL karena sistem Anda menggunakan mapping array kode_mobil
$data_sql = "SELECT t.*, p.nama AS nama_pelanggan, m.nama_mobil, m.kode_mobil, m.merek $base_sql ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
$data_stmt = $conn->prepare($data_sql);
if ($types) {
    $bp = array_merge([$types . 'ii'], $params, [$per_page, $offset]);
    $data_stmt->bind_param(...$bp);
} else {
    $data_stmt->bind_param('ii', $per_page, $offset);
}
$data_stmt->execute();
$list = $data_stmt->get_result();
$data_stmt->close();

$page_title = 'Transaksi Rental';
require_once '../includes/header.php';
?>

<div class="container mt-4">

<?php if ($action === 'tambah' || $action === 'edit'): ?>
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-receipt text-primary me-2"></i>
            <?php echo $action === 'edit' ? 'Edit Transaksi' : 'Buat Transaksi Baru'; ?>
        </h2>
        <a href="transaksi.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="transaksi.php" id="formTransaksi">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="pelanggan_id" class="form-label">Pelanggan <span class="text-danger">*</span></label>
                                <select id="pelanggan_id" name="pelanggan_id" class="form-select" required>
                                    <option value="">-- Pilih Pelanggan --</option>
                                    <?php while ($p = $pelanggan_list->fetch_assoc()): ?>
                                    <option value="<?php echo $p['id']; ?>" <?php echo (($edit_data['pelanggan_id'] ?? 0) == $p['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($p['nama'] . ' - ' . $p['telepon']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="mobil_id" class="form-label">Mobil <span class="text-danger">*</span></label>
                                <select id="mobil_id" name="mobil_id" class="form-select" required>
                                    <option value="">-- Pilih Mobil --</option>
                                    <?php while ($m = $mobil_list_sel->fetch_assoc()): 
                                        $disabled = ($m['status'] === 'maintenance' && ($edit_data['mobil_id'] ?? 0) != $m['id']) ? 'disabled' : '';
                                    ?>
                                    <option value="<?php echo $m['id']; ?>" <?php echo $disabled; ?> <?php echo (($edit_data['mobil_id'] ?? 0) == $m['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($m['nama_mobil'] . ' (' . $m['kode_mobil'] . ') - Rp ' . number_format($m['harga_per_hari'], 0, ',', '.') . '/hari'); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="tanggal_mulai" class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                                <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control" value="<?php echo htmlspecialchars($edit_data['tanggal_mulai'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="tanggal_selesai" class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                                <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control" value="<?php echo htmlspecialchars($edit_data['tanggal_selesai'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="aktif" <?php echo (($edit_data['status'] ?? 'aktif') === 'aktif') ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="selesai" <?php echo (($edit_data['status'] ?? '') === 'selesai') ? 'selected' : ''; ?>>Selesai</option>
                                    <option value="dibatalkan" <?php echo (($edit_data['status'] ?? '') === 'dibatalkan') ? 'selected' : ''; ?>>Dibatalkan</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label for="catatan" class="form-label">Catatan</label>
                                <textarea id="catatan" name="catatan" class="form-control" rows="2"><?php echo htmlspecialchars($edit_data['catatan'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">Simpan Transaksi</button>
                            <a href="transaksi.php" class="btn btn-light px-4">Batal</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0"><i class="bi bi-calculator me-2"></i>Ringkasan Biaya</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="text-muted">Harga / Hari</td>
                            <td class="fw-semibold text-end">Rp <?= number_format($edit_data['harga_per_hari'] ?? 0, 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Jumlah Hari</td>
                            <td class="fw-semibold text-end"><?= $edit_data['jumlah_hari'] ?? 0 ?> Hari</td>
                        </tr>
                        <tr class="table-primary">
                            <td class="fw-bold">Total Harga</td>
                            <td class="fw-bold text-end text-primary">Rp <?= number_format($edit_data['total_harga'] ?? 0, 0, ',', '.') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <div class="page-header d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="bi bi-receipt text-primary me-2"></i>Daftar Transaksi</h2>
            <small class="text-muted"><?php echo $total_rows; ?> total transaksi ditemukan</small>
        </div>
        <a href="transaksi.php?action=tambah" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Transaksi
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Pelanggan</th>
                        <th>Mobil</th>
                        <th>Periode</th>
                        <th>Total Biaya</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($list && $list->num_rows > 0): ?>
                        <?php while ($row = $list->fetch_assoc()): 
                            $fotoMobil = [
                                'MOB-001' => 'Avanza.jpg',
                                'MOB-002' => 'brio.jpg',
                                'MOB-003' => 'ertiga.jpg',
                                'MOB-004' => 'xenia.jpg',
                                'MOB-005' => 'xpander.jpg',
                                'MOB-006' => 'inova.jpg',
                                'MOB-007' => 'jazz.jpg',
                                'MOB-008' => 'fortuner.jpg',
                
                            ];
                            
                            $kode = $row['kode_mobil'];
                            if (array_key_exists($kode, $fotoMobil)) {
                                $gambar_mobil = '../assets/img/' . $fotoMobil[$kode];
                            } else {
                                $gambar_mobil = 'https://placehold.co/120x80/0d6efd/fff?text=' . urlencode($row['merek']);
                            }
                        ?>
                        <tr>
                            <td class="fw-bold text-primary"><?= $row['kode_transaksi'] ?></td>
                            <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?= $gambar_mobil ?>" 
                                         alt="Mobil" 
                                         class="img-thumbnail" 
                                         style="width: 70px; height: 45px; object-fit: cover;"
                                         onerror="this.onerror=null; this.src='https://placehold.co/120x80/0d6efd/fff?text=<?= urlencode($row['merek']) ?>';">
                                    <div>
                                        <?= htmlspecialchars($row['merek'] . ' ' . $row['nama_mobil']) ?>
                                        <br><small class="text-muted"><?= $row['kode_mobil'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= date('d M Y', strtotime($row['tanggal_mulai'])) ?> - <?= date('d M Y', strtotime($row['tanggal_selesai'])) ?></td>
                            <td class="fw-bold">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                            <td><span class="badge bg-secondary"><?= ucfirst($row['status']) ?></span></td>
                            <td>
                                <a href="transaksi.php?action=edit&id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="transaksi.php?action=hapus&id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus transaksi?')">Hapus</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4">Data tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>