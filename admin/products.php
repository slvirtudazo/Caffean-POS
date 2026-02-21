<?php

/**
 * Purge Coffee Shop — Admin Products Management  (products.php)
 * Full CRUD: Add, View, Edit, Delete.
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: ../login.php');
  exit();
}

// ── POST Handlers (PRG) ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

  if ($_POST['action'] === 'add') {
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $stmt = mysqli_prepare(
      $conn,
      "INSERT INTO products (category_id, name, description, price, status) VALUES (?,?,?,?,1)"
    );
    mysqli_stmt_bind_param($stmt, "issd", $category_id, $name, $description, $price);
    $_SESSION['flash'] = mysqli_stmt_execute($stmt)
      ? ['type' => 'success', 'msg' => "Product '$name' added successfully!"]
      : ['type' => 'error',   'msg' => 'Error adding product. Please try again.'];
    mysqli_stmt_close($stmt);
  } elseif ($_POST['action'] === 'edit') {
    $product_id  = (int)$_POST['product_id'];
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $status      = (int)$_POST['status'];
    $stmt = mysqli_prepare(
      $conn,
      "UPDATE products SET name=?, category_id=?, description=?, price=?, status=? WHERE product_id=?"
    );
    mysqli_stmt_bind_param($stmt, "sisdii", $name, $category_id, $description, $price, $status, $product_id);
    $_SESSION['flash'] = mysqli_stmt_execute($stmt)
      ? ['type' => 'success', 'msg' => 'Product updated successfully!']
      : ['type' => 'error',   'msg' => 'Error updating product. Please try again.'];
    mysqli_stmt_close($stmt);
  } elseif ($_POST['action'] === 'delete') {
    $product_id = (int)$_POST['product_id'];
    $stmt = mysqli_prepare($conn, "DELETE FROM products WHERE product_id=?");
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    $_SESSION['flash'] = mysqli_stmt_execute($stmt)
      ? ['type' => 'success', 'msg' => 'Product deleted successfully!']
      : ['type' => 'error',   'msg' => 'Error deleting product.'];
    mysqli_stmt_close($stmt);
  }

  header('Location: products.php');
  exit();
}

// ── Session Flash ─────────────────────────────────────────────
$flash   = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$message = ($flash && $flash['type'] === 'success') ? $flash['msg'] : '';
$error   = ($flash && $flash['type'] === 'error')   ? $flash['msg'] : '';

// ── Fetch data ───────────────────────────────────────────────
$products_result = mysqli_query(
  $conn,
  "SELECT p.*, c.name AS category_name
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     ORDER BY c.name, p.name"
);

$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

$total_products = mysqli_num_rows($products_result);
mysqli_data_seek($products_result, 0);

define('BASE_URL', '..');
include 'includes/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1>Products</h1>
    <p>Add, edit, and manage menu items, prices, and availability</p>
  </div>
</div>

<?php if ($message): ?>
  <div class="flash-success"><i class="fas fa-check-circle"></i><?= $message ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="flash-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
<?php endif; ?>

<div class="toolbar">
  <div class="search-box">
    <span class="search-icon"><i class="fas fa-search"></i></span>
    <input type="text" id="productSearch" placeholder="Search products..."
      oninput="filterTable(this.value)" />
  </div>
  <button class="btn-primary" onclick="openModal('addProductModal')">
    <i class="fas fa-plus"></i> Add New Product
  </button>
</div>

<div class="card">
  <div class="card-header">
    <h2>All Products</h2>
    <span class="card-count"><?= $total_products ?> item<?= $total_products !== 1 ? 's' : '' ?></span>
  </div>
  <table class="admin-table" id="productsTable">
    <thead>
      <tr>
        <th data-sort="number">ID</th>
        <th data-sort="text">Name</th>
        <th data-sort="text">Category</th>
        <th data-sort="number">Price</th>
        <th data-sort="status">Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($total_products === 0): ?>
        <tr>
          <td colspan="6">
            <div class="empty-state">
              <i class="fas fa-box-open"></i>
              <p>No products found. Add your first product!</p>
            </div>
          </td>
        </tr>
      <?php else: ?>
        <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
          <tr>
            <td class="td-id">#<?= $product['product_id'] ?></td>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= htmlspecialchars($product['category_name']) ?></td>
            <td>&#8369;<?= number_format($product['price'], 2) ?></td>
            <td>
              <span class="badge badge-<?= $product['status'] ? 'active' : 'inactive' ?>">
                <?= $product['status'] ? 'Active' : 'Hidden' ?>
              </span>
            </td>
            <td class="td-actions">
              <button class="btn-icon btn-icon-view" title="View Product"
                onclick="viewProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                <i class="fas fa-eye"></i>
              </button>
              <button class="btn-icon btn-icon-update" title="Update Product"
                onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                <i class="fas fa-pen"></i>
              </button>
              <button class="btn-icon btn-icon-delete" title="Delete Product"
                onclick="confirmDelete(<?= $product['product_id'] ?>, '<?= addslashes(htmlspecialchars($product['name'])) ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- ══ VIEW PRODUCT MODAL ════════════════════════════════════ -->
<div class="modal-overlay" id="viewProductModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-eye modal-icon"></i>Product Details</h3>
      <button class="modal-close" onclick="closeModal('viewProductModal')">&#x2715;</button>
    </div>
    <div class="modal-body">
      <div class="view-detail-group">
        <span class="view-label">Product ID</span>
        <span class="view-value" id="view_product_id"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Name</span>
        <span class="view-value" id="view_name"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Category</span>
        <span class="view-value" id="view_category"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Price</span>
        <span class="view-value" id="view_price"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Description</span>
        <span class="view-value" id="view_description"></span>
      </div>
      <div class="view-detail-group">
        <span class="view-label">Status</span>
        <span class="view-value" id="view_status"></span>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-cancel" onclick="closeModal('viewProductModal')">Close</button>
    </div>
  </div>
</div>

<!-- ══ ADD PRODUCT MODAL ════════════════════════════════════ -->
<div class="modal-overlay" id="addProductModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-plus modal-icon"></i>Add New Product</h3>
      <button class="modal-close" onclick="closeModal('addProductModal')">&#x2715;</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add" />

        <div class="form-group">
          <label class="form-label">Product Name</label>
          <input type="text" name="name" class="form-control"
            placeholder="e.g. Espresso" required />
        </div>

        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-control" required>
            <?php mysqli_data_seek($categories_result, 0);
            while ($cat = mysqli_fetch_assoc($categories_result)): ?>
              <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"
            placeholder="Brief product description"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Price (&#8369;)</label>
          <input type="number" name="price" class="form-control"
            step="0.01" min="0" placeholder="0.00" required />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('addProductModal')">Cancel</button>
        <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Add Product</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ EDIT PRODUCT MODAL ═══════════════════════════════════ -->
<div class="modal-overlay" id="editProductModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-pen modal-icon"></i>Edit Product</h3>
      <button class="modal-close" onclick="closeModal('editProductModal')">&#x2715;</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="edit" />
        <input type="hidden" name="product_id" id="edit_product_id" />

        <div class="form-group">
          <label class="form-label">Product Name</label>
          <input type="text" name="name" id="edit_name" class="form-control"
            placeholder="e.g. Espresso" required />
        </div>

        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category_id" id="edit_category_id" class="form-control" required>
            <?php mysqli_data_seek($categories_result, 0);
            while ($cat = mysqli_fetch_assoc($categories_result)): ?>
              <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="edit_description" class="form-control"
            placeholder="Brief product description"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Price (&#8369;)</label>
          <input type="number" name="price" id="edit_price" class="form-control"
            step="0.01" min="0" placeholder="0.00" required />
        </div>

        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" id="edit_status" class="form-control">
            <option value="1">Active</option>
            <option value="0">Hidden</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('editProductModal')">Cancel</button>
        <button type="submit" class="btn-update"><i class="fas fa-floppy-disk"></i> Update Product</button>
      </div>
    </form>
  </div>
</div>

<!-- ══ DELETE PRODUCT MODAL ══════════════════════════════════ -->
<div class="modal-overlay" id="deleteProductModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-trash modal-icon"></i>Delete Product</h3>
      <button class="modal-close" onclick="closeModal('deleteProductModal')">&#x2715;</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <p class="modal-subtitle">
          Are you sure you want to delete <strong id="delete_product_name"></strong>?
          This action cannot be undone.
        </p>
        <input type="hidden" name="action" value="delete" />
        <input type="hidden" name="product_id" id="delete_product_id" />
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('deleteProductModal')">Cancel</button>
        <button type="submit" class="btn-delete"><i class="fas fa-trash"></i> Delete Product</button>
      </div>
    </form>
  </div>
</div>

<script>
  /* ── Modal helpers ─────────────────────────────────────────── */
  function openModal(id) {
    document.getElementById(id).style.display = 'flex';
  }

  function closeModal(id) {
    document.getElementById(id).style.display = 'none';
  }

  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) overlay.style.display = 'none';
    });
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape')
      document.querySelectorAll('.modal-overlay').forEach(function(o) {
        o.style.display = 'none';
      });
  });

  /* ── Product view/edit/delete ──────────────────────────────── */
  function viewProduct(p) {
    document.getElementById('view_product_id').textContent = '#' + p.product_id;
    document.getElementById('view_name').textContent = p.name;
    document.getElementById('view_category').textContent = p.category_name;
    document.getElementById('view_price').textContent = '\u20B1' + parseFloat(p.price).toFixed(2);
    document.getElementById('view_description').textContent = p.description || '\u2014';
    document.getElementById('view_status').innerHTML = p.status == 1 ?
      '<span class="badge badge-active">Active</span>' :
      '<span class="badge badge-inactive">Hidden</span>';
    openModal('viewProductModal');
  }

  function editProduct(p) {
    document.getElementById('edit_product_id').value = p.product_id;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_category_id').value = p.category_id;
    document.getElementById('edit_description').value = p.description;
    document.getElementById('edit_price').value = p.price;
    document.getElementById('edit_status').value = p.status;
    openModal('editProductModal');
  }

  function confirmDelete(id, name) {
    document.getElementById('delete_product_id').value = id;
    document.getElementById('delete_product_name').textContent = name;
    openModal('deleteProductModal');
  }

  /* ── Live search filter ────────────────────────────────────── */
  function filterTable(term) {
    var rows = document.querySelectorAll('#productsTable tbody tr');
    var q = term.toLowerCase();
    rows.forEach(function(row) {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  }

  /* ── Sorting ───────────────────────────────────────────────── */
  initSortableTable('productsTable');
</script>

<?php include 'includes/footer.php'; ?>