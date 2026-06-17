<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$action  = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================================
// CREATE / UPDATE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_mobil    = htmlspecialchars(trim($_POST['kode_mobil']    ?? ''));
    $nama_mobil    = htmlspecialchars(trim($_POST['nama_mobil']    ?? ''));
    $merek         = htmlspecialchars(trim($_POST['merek']         ?? ''));
    $tahun         = (int)($_POST['tahun']         ?? 0);
    $warna         = htmlspecialchars(trim($_POST['warna']         ?? ''));
    $harga_per_hari= (float)($_POST['harga_per_hari'] ?? 0);
    $kapasitas     = (int)($_POST['kapasitas']     ?? 4);
    $status        = htmlspecialchars(trim($_POST['status']        ?? 'tersedia'));
    $deskripsi     = htmlspecialchars(trim($_POST['deskripsi']     ?? ''));
    $post_id       = (int)($_POST['id'] ?? 0);

    if ($post_id > 0) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE mobil SET kode_mobil=?, nama_mobil=?, merek=?, tahun=?, warna=?, harga_per_hari=?, kapasitas=?, status=?, deskripsi=? WHERE id=?");
        $stmt->bind_param('sssisdissi', $kode_mobil, $nama_mobil, $merek, $tahun, $warna, $harga_per_hari, $kapasitas, $status, $deskripsi, $post_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = 'Data mobil berhasil diperbarui!';
        $_SESSION['flash_type']    = 'success';
    } else {
        // INSERT
        $stmt = $conn->prepare("INSERT INTO mobil (kode_mobil, nama_mobil, merek, tahun, warna, harga_per_hari, kapasitas, status, deskripsi) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssisdiss', $kode_mobil, $nama_mobil, $merek, $tahun, $warna, $harga_per_hari, $kapasitas, $status, $deskripsi);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = 'Mobil baru berhasil ditambahkan!';
        $_SESSION['flash_type']    = 'success';
    }

    header('Location: mobil.php');
    exit;
}

// ============================================================
// DELETE
// ============================================================
if ($action === 'hapus' && $edit_id > 0) {
    // Cek apakah mobil sedang digunakan dalam transaksi aktif
    $cek = $conn->prepare("SELECT COUNT(*) as total FROM transaksi WHERE mobil_id = ? AND status = 'aktif'");
    $cek->bind_param('i', $edit_id);
    $cek->execute();
    $cek_result = $cek->get_result()->fetch_assoc();
    $cek->close();

    if ($cek_result['total'] > 0) {
        $_SESSION['flash_message'] = 'Mobil tidak dapat dihapus karena masih ada transaksi aktif!';
        $_SESSION['flash_type']    = 'danger';
    } else {
        $stmt = $conn->prepare("DELETE FROM mobil WHERE id = ?");
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = 'Data mobil berhasil dihapus.';
        $_SESSION['flash_type']    = 'success';
    }
    header('Location: mobil.php');
    exit;
}

// ============================================================
// AMBIL DATA UNTUK EDIT
// ============================================================
$edit_data = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM mobil WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edit_data) {
        header('Location: mobil.php');
        exit;
    }
}

// ============================================================
// READ dengan Pencarian & Pagination
// ============================================================
$search      = htmlspecialchars(trim($_GET['search'] ?? ''));
$per_page    = 7;
$current_page_num = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($current_page_num - 1) * $per_page;

$where = '';
$params = [];
$types  = '';

if ($search !== '') {
    $like    = '%' . $search . '%';
    $where   = "WHERE nama_mobil LIKE ? OR merek LIKE ? OR kode_mobil LIKE ? OR status LIKE ?";
    $params  = [$like, $like, $like, $like];
    $types   = 'ssss';
}

// Total rows
$count_sql = "SELECT COUNT(*) as total FROM mobil $where";
$count_stmt = $conn->prepare($count_sql);
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();

$total_pages = ceil($total_rows / $per_page);

// Fetch data
$data_sql = "SELECT * FROM mobil $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
$data_stmt = $conn->prepare($data_sql);
if ($types) {
    $bind_params = array_merge([$types . 'ii'], $params, [$per_page, $offset]);
    $data_stmt->bind_param(...$bind_params);
} else {
    $data_stmt->bind_param('ii', $per_page, $offset);
}
$data_stmt->execute();
$mobil_list = $data_stmt->get_result();
$data_stmt->close();

$page_title = 'Data Mobil';
require_once '../includes/header.php';
?>

<div class="container">

    <?php if ($action === 'tambah' || $action === 'edit'): ?>
    <!-- ============ FORM TAMBAH / EDIT ============ -->
    <div class="page-header">
        <h2>
            <i class="bi bi-<?php echo $action === 'edit' ? 'pencil' : 'plus-circle'; ?> text-primary me-2"></i>
            <?php echo $action === 'edit' ? 'Edit Data Mobil' : 'Tambah Mobil Baru'; ?>
        </h2>
        <a href="mobil.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="mobil.php" class="needs-validation" novalidate
                  id="formMobil">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <!-- Kode Mobil -->
                    <div class="col-md-4">
                        <label for="kode_mobil" class="form-label">Kode Mobil <span class="text-danger">*</span></label>
                        <input type="text" id="kode_mobil" name="kode_mobil" class="form-control"
                               placeholder="cth: MOB-008"
                               value="<?php echo htmlspecialchars($edit_data['kode_mobil'] ?? ''); ?>"
                               data-validate="required|minlength:3"
                               data-label="Kode Mobil">
                    </div>
                    <!-- Nama Mobil -->
                    <div class="col-md-8">
                        <label for="nama_mobil" class="form-label">Nama Mobil <span class="text-danger">*</span></label>
                        <input type="text" id="nama_mobil" name="nama_mobil" class="form-control"
                               placeholder="cth: Avanza G"
                               value="<?php echo htmlspecialchars($edit_data['nama_mobil'] ?? ''); ?>"
                               data-validate="required|minlength:2"
                               data-label="Nama Mobil">
                    </div>
                    <!-- Merek -->
                    <div class="col-md-4">
                        <label for="merek" class="form-label">Merek <span class="text-danger">*</span></label>
                        <input type="text" id="merek" name="merek" class="form-control"
                               placeholder="cth: Toyota"
                               value="<?php echo htmlspecialchars($edit_data['merek'] ?? ''); ?>"
                               data-validate="required"
                               data-label="Merek">
                    </div>
                    <!-- Tahun -->
                    <div class="col-md-4">
                        <label for="tahun" class="form-label">Tahun <span class="text-danger">*</span></label>
                        <input type="number" id="tahun" name="tahun" class="form-control"
                               placeholder="cth: 2023" min="2000" max="2030"
                               value="<?php echo htmlspecialchars($edit_data['tahun'] ?? ''); ?>"
                               data-validate="required|min:2000"
                               data-label="Tahun">
                    </div>
                    <!-- Warna -->
                    <div class="col-md-4">
                        <label for="warna" class="form-label">Warna <span class="text-danger">*</span></label>
                        <input type="text" id="warna" name="warna" class="form-control"
                               placeholder="cth: Putih"
                               value="<?php echo htmlspecialchars($edit_data['warna'] ?? ''); ?>"
                               data-validate="required"
                               data-label="Warna">
                    </div>
                    <!-- Harga Per Hari -->
                    <div class="col-md-4">
                        <label for="harga_per_hari" class="form-label">Harga / Hari (Rp) <span class="text-danger">*</span></label>
                        <input type="number" id="harga_per_hari" name="harga_per_hari" class="form-control"
                               placeholder="cth: 350000" min="0"
                               value="<?php echo htmlspecialchars($edit_data['harga_per_hari'] ?? ''); ?>"
                               data-validate="required|min:1"
                               data-label="Harga per hari">
                    </div>
                    <!-- Kapasitas -->
                    <div class="col-md-4">
                        <label for="kapasitas" class="form-label">Kapasitas (org)</label>
                        <select id="kapasitas" name="kapasitas" class="form-select">
                            <?php foreach ([4,5,6,7,8] as $k): ?>
                            <option value="<?php echo $k; ?>"
                                <?php echo (($edit_data['kapasitas'] ?? 5) == $k) ? 'selected' : ''; ?>>
                                <?php echo $k; ?> orang
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <!-- Status -->
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="tersedia"    <?php echo (($edit_data['status'] ?? '') === 'tersedia')    ? 'selected' : ''; ?>>Tersedia</option>
                            <option value="disewa"      <?php echo (($edit_data['status'] ?? '') === 'disewa')      ? 'selected' : ''; ?>>Sedang Disewa</option>
                            <option value="maintenance" <?php echo (($edit_data['status'] ?? '') === 'maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                    </div>
                    <!-- Deskripsi -->
                    <div class="col-12">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea id="deskripsi" name="deskripsi" class="form-control" rows="3"
                                  placeholder="Deskripsi singkat tentang mobil..."><?php echo htmlspecialchars($edit_data['deskripsi'] ?? ''); ?></textarea>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2">
                    <?php if ($action === 'edit'): ?>
                    <button type="button" class="btn btn-warning px-4"
                            onclick="if(confirm('Simpan perubahan data mobil ini?')) document.getElementById('formMobil').submit()">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                    <?php else: ?>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-plus-circle me-2"></i>Tambah Mobil
                    </button>
                    <?php endif; ?>
                    <a href="mobil.php" class="btn btn-light px-4">Batal</a>
                </div>
            </form>
  
            <form id="formMobil" method="POST" action="mobil.php" style="display:none;">
                <input type="hidden" name="id" value="<?php echo $edit_data['id'] ?? ''; ?>">
                <input type="hidden" name="kode_mobil"     id="h_kode_mobil">
                <input type="hidden" name="nama_mobil"     id="h_nama_mobil">
                <input type="hidden" name="merek"          id="h_merek">
                <input type="hidden" name="tahun"          id="h_tahun">
                <input type="hidden" name="warna"          id="h_warna">
                <input type="hidden" name="harga_per_hari" id="h_harga">
                <input type="hidden" name="kapasitas"      id="h_kapasitas">
                <input type="hidden" name="status"         id="h_status">
                <input type="hidden" name="deskripsi"      id="h_deskripsi">
            </form>
        </div>
    </div>

    <?php else: ?>
    <!-- ============ DAFTAR MOBIL ============ -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-car-front text-primary me-2"></i>Data Mobil</h2>
            <small class="text-muted"><?php echo $total_rows; ?> total data</small>
        </div>
        <a href="mobil.php?action=tambah" class="btn btn-primary">
            <i class="bi bi-plus-lg me-2"></i>Tambah Mobil
        </a>
    </div>

    <!-- Search -->
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <form method="GET" action="mobil.php" class="search-box">
                        <i class="bi bi-search" id="searchIcon"></i>
                        <input type="text" id="searchInput" name="search" class="form-control"
                               placeholder="Cari nama mobil, merek, kode..."
                               value="<?php echo $search; ?>">
                    </form>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted" id="rowCounter">
                        Menampilkan <?php echo $mobil_list->num_rows; ?> dari <?php echo $total_rows; ?> data
                    </small>
                    <?php if ($search): ?>
                    <a href="mobil.php" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="bi bi-x"></i> Hapus Filter
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-wrapper">
                <table class="table table-hover mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Kode</th>
                            <th>Nama Mobil</th>
                            <th>Merek / Tahun</th>
                            <th>Harga/Hari</th>
                            <th>Kapasitas</th>
                            <th>Status</th>
                            <th style="width:130px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = $offset + 1;
                        if ($mobil_list->num_rows > 0):
                            while ($row = $mobil_list->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="text-muted"><?php echo $no++; ?></td>
                            <td><code class="small"><?php echo htmlspecialchars($row['kode_mobil']); ?></code></td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($row['nama_mobil']); ?></div>
                                <?php if ($row['deskripsi']): ?>
                                <small class="text-muted"><?php echo htmlspecialchars(substr($row['deskripsi'], 0, 50)) . '...'; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['merek']); ?> <span class="text-muted">(<?php echo $row['tahun']; ?>)</span></td>
                            <td class="fw-semibold text-success">
                                Rp <?php echo number_format($row['harga_per_hari'], 0, ',', '.'); ?>
                            </td>
                            <td><span class="badge bg-light text-dark"><?php echo $row['kapasitas']; ?> org</span></td>
                            <td>
                                <span class="badge status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status'] === 'maintenance' ? 'Maintenance' : $row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group-action d-flex gap-1">
                                    <a href="mobil.php?action=edit&id=<?php echo $row['id']; ?>"
                                       class="btn btn-sm btn-outline-warning"
                                       title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" title="Hapus"
                                            onclick="confirmDelete('<?php echo htmlspecialchars($row['nama_mobil']); ?>',
                                            'mobil.php?action=hapus&id=<?php echo $row['id']; ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="8">
                                <div class="empty-state">
                                    <i class="bi bi-car-front"></i>
                                    <?php echo $search ? "Tidak ada hasil untuk \"$search\"" : 'Belum ada data mobil'; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="noResultMsg" class="empty-state" style="display:none;">
                <i class="bi bi-search"></i>
                Data tidak ditemukan
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center py-3">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($current_page_num > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page_num-1; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($i == $current_page_num) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($current_page_num < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $current_page_num+1; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<button id="scrollTopBtn" class="btn btn-primary d-flex" title="Ke atas">
    <i class="bi bi-arrow-up"></i>
</button>

<?php if ($action === 'edit' && $edit_data): ?>
<script>

document.addEventListener('DOMContentLoaded', function() {
    const visibleForm = document.querySelector('.needs-validation');
    if (visibleForm) {
        visibleForm.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    }

    window.confirmEditMobil = function() {
        const fields = ['kode_mobil','nama_mobil','merek','tahun','warna','harga_per_hari','kapasitas','status','deskripsi'];
        fields.forEach(f => {
            const el = document.getElementById(f);
            const hEl = document.getElementById('h_' + (f === 'harga_per_hari' ? 'harga' : f));
            if (el && hEl) hEl.value = el.value;
        });
        if (confirm('Simpan perubahan data mobil ini?')) {
            document.getElementById('formMobil').submit();
        }
    };
  
    const editBtn = document.querySelector('.btn-warning[onclick]');
    if (editBtn) editBtn.setAttribute('onclick', 'confirmEditMobil()');
});
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>