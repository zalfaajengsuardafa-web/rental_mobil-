<?php
require_once '../includes/config.php';
require_once '../includes/auth_check.php';

$action  = $_GET['action'] ?? 'list';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================================
// CREATE / UPDATE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = htmlspecialchars(trim($_POST['nama']    ?? ''));
    $nik     = htmlspecialchars(trim($_POST['nik']     ?? ''));
    $telepon = htmlspecialchars(trim($_POST['telepon'] ?? ''));
    $email   = htmlspecialchars(trim($_POST['email']   ?? ''));
    $alamat  = htmlspecialchars(trim($_POST['alamat']  ?? ''));
    $post_id = (int)($_POST['id'] ?? 0);

    if ($post_id > 0) {
        $stmt = $conn->prepare("UPDATE pelanggan SET nama=?, nik=?, telepon=?, email=?, alamat=? WHERE id=?");
        $stmt->bind_param('sssssi', $nama, $nik, $telepon, $email, $alamat, $post_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = 'Data pelanggan berhasil diperbarui!';
        $_SESSION['flash_type']    = 'success';
    } else {
        $stmt = $conn->prepare("INSERT INTO pelanggan (nama, nik, telepon, email, alamat) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss', $nama, $nik, $telepon, $email, $alamat);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = 'Pelanggan baru berhasil didaftarkan!';
        $_SESSION['flash_type']    = 'success';
    }
    header('Location: pelanggan.php');
    exit;
}

// ============================================================
// DELETE
// ============================================================
if ($action === 'hapus' && $edit_id > 0) {
    $cek = $conn->prepare("SELECT COUNT(*) as total FROM transaksi WHERE pelanggan_id = ?");
    $cek->bind_param('i', $edit_id);
    $cek->execute();
    $cek_result = $cek->get_result()->fetch_assoc();
    $cek->close();

    if ($cek_result['total'] > 0) {
        $_SESSION['flash_message'] = 'Pelanggan tidak dapat dihapus karena memiliki riwayat transaksi!';
        $_SESSION['flash_type']    = 'danger';
    } else {
        $stmt = $conn->prepare("DELETE FROM pelanggan WHERE id = ?");
        $stmt->bind_param('i', $edit_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['flash_message'] = 'Data pelanggan berhasil dihapus.';
        $_SESSION['flash_type']    = 'success';
    }
    header('Location: pelanggan.php');
    exit;
}

// ============================================================
// FETCH EDIT DATA
// ============================================================
$edit_data = null;
if ($action === 'edit' && $edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM pelanggan WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$edit_data) { header('Location: pelanggan.php'); exit; }
}

// ============================================================
// READ dengan Search & Pagination
// ============================================================
$search   = htmlspecialchars(trim($_GET['search'] ?? ''));
$per_page = 8;
$page_num = max(1, (int)($_GET['page'] ?? 1));
$offset   = ($page_num - 1) * $per_page;

$where = ''; $params = []; $types = '';
if ($search !== '') {
    $like   = '%' . $search . '%';
    $where  = "WHERE nama LIKE ? OR nik LIKE ? OR telepon LIKE ? OR email LIKE ?";
    $params = [$like, $like, $like, $like];
    $types  = 'ssss';
}

$count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM pelanggan $where");
if ($types) $count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()['total'];
$count_stmt->close();
$total_pages = ceil($total_rows / $per_page);

$data_stmt = $conn->prepare("SELECT * FROM pelanggan $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
if ($types) {
    $bind_params = array_merge([$types . 'ii'], $params, [$per_page, $offset]);
    $data_stmt->bind_param(...$bind_params);
} else {
    $data_stmt->bind_param('ii', $per_page, $offset);
}
$data_stmt->execute();
$list = $data_stmt->get_result();
$data_stmt->close();

$page_title = 'Data Pelanggan';
require_once '../includes/header.php';
?>

<div class="container">

<?php if ($action === 'tambah' || $action === 'edit'): ?>
    <!-- FORM -->
    <div class="page-header">
        <h2>
            <i class="bi bi-person-<?php echo $action === 'edit' ? 'gear' : 'plus'; ?> text-primary me-2"></i>
            <?php echo $action === 'edit' ? 'Edit Pelanggan' : 'Daftarkan Pelanggan'; ?>
        </h2>
        <a href="pelanggan.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Kembali
        </a>
    </div>

    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="pelanggan.php" class="needs-validation" novalidate
                  <?php echo $action === 'edit' ? 'id="mainFormPelanggan"' : ''; ?>>
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" id="nama" name="nama" class="form-control"
                               placeholder="cth: Budi Santoso"
                               value="<?php echo htmlspecialchars($edit_data['nama'] ?? ''); ?>"
                               data-validate="required|minlength:3"
                               data-label="Nama">
                    </div>
                    <div class="col-md-6">
                        <label for="nik" class="form-label">NIK (KTP) <span class="text-danger">*</span></label>
                        <input type="text" id="nik" name="nik" class="form-control"
                               placeholder="16 digit NIK"
                               value="<?php echo htmlspecialchars($edit_data['nik'] ?? ''); ?>"
                               data-validate="required|minlength:16"
                               data-label="NIK"
                               maxlength="16">
                    </div>
                    <div class="col-md-6">
                        <label for="telepon" class="form-label">No. Telepon <span class="text-danger">*</span></label>
                        <input type="text" id="telepon" name="telepon" class="form-control"
                               placeholder="cth: 081234567890"
                               value="<?php echo htmlspecialchars($edit_data['telepon'] ?? ''); ?>"
                               data-validate="required|phone"
                               data-label="Telepon">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email" class="form-control"
                               placeholder="cth: nama@email.com"
                               value="<?php echo htmlspecialchars($edit_data['email'] ?? ''); ?>"
                               data-validate="email"
                               data-label="Email">
                    </div>
                    <div class="col-12">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea id="alamat" name="alamat" class="form-control" rows="3"
                                  placeholder="Alamat lengkap pelanggan..."><?php echo htmlspecialchars($edit_data['alamat'] ?? ''); ?></textarea>
                    </div>
                </div>

                <hr class="my-4">
                <div class="d-flex gap-2">
                    <?php if ($action === 'edit'): ?>
                    <button type="button" class="btn btn-warning px-4"
                            onclick="if(confirm('Simpan perubahan data pelanggan ini?')) document.getElementById('mainFormPelanggan').submit()">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                    <?php else: ?>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-person-check me-2"></i>Daftarkan Pelanggan
                    </button>
                    <?php endif; ?>
                    <a href="pelanggan.php" class="btn btn-light px-4">Batal</a>
                </div>
            </form>
        </div>
    </div>

<?php else: ?>
    <!-- DAFTAR -->
    <div class="page-header">
        <div>
            <h2><i class="bi bi-people text-primary me-2"></i>Data Pelanggan</h2>
            <small class="text-muted"><?php echo $total_rows; ?> total data</small>
        </div>
        <a href="pelanggan.php?action=tambah" class="btn btn-primary">
            <i class="bi bi-person-plus me-2"></i>Tambah Pelanggan
        </a>
    </div>

    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-6">
                    <form method="GET" action="pelanggan.php" class="search-box">
                        <i class="bi bi-search"></i>
                        <input type="text" id="searchInput" name="search" class="form-control"
                               placeholder="Cari nama, NIK, telepon, email..."
                               value="<?php echo $search; ?>">
                    </form>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted" id="rowCounter">
                        Menampilkan <?php echo $list->num_rows; ?> dari <?php echo $total_rows; ?> data
                    </small>
                    <?php if ($search): ?>
                    <a href="pelanggan.php" class="btn btn-sm btn-outline-secondary ms-2">
                        <i class="bi bi-x"></i> Reset
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-wrapper">
                <table class="table table-hover mb-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>NIK</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = $offset + 1;
                        if ($list->num_rows > 0):
                            while ($row = $list->fetch_assoc()):
                        ?>
                        <tr>
                            <td class="text-muted"><?php echo $no++; ?></td>
                            <td class="fw-semibold"><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td><code class="small"><?php echo htmlspecialchars($row['nik']); ?></code></td>
                            <td><?php echo htmlspecialchars($row['telepon']); ?></td>
                            <td class="small"><?php echo htmlspecialchars($row['email'] ?: '-'); ?></td>
                            <td class="small text-muted" style="max-width:150px;">
                                <?php echo htmlspecialchars(substr($row['alamat'] ?? '-', 0, 60)); ?>
                            </td>
                            <td>
                                <div class="btn-group-action d-flex gap-1">
                                    <a href="pelanggan.php?action=edit&id=<?php echo $row['id']; ?>"
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger" title="Hapus"
                                            onclick="confirmDelete('<?php echo htmlspecialchars($row['nama']); ?>',
                                            'pelanggan.php?action=hapus&id=<?php echo $row['id']; ?>')">
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
                            <td colspan="7">
                                <div class="empty-state">
                                    <i class="bi bi-people"></i>
                                    <?php echo $search ? "Tidak ada hasil untuk \"$search\"" : 'Belum ada data pelanggan'; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div id="noResultMsg" class="empty-state" style="display:none;">
                <i class="bi bi-search"></i>Data tidak ditemukan
            </div>

            <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center py-3">
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page_num > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page_num-1; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page_num ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        <?php if ($page_num < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page_num+1; ?>&search=<?php echo urlencode($search); ?>">
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

<?php require_once '../includes/footer.php'; ?>
