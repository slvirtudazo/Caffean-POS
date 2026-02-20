<?php
/**
 * Purge Coffee Shop — Admin Products Management
 * View all products, add new, edit existing, toggle availability.
 */

session_start();
require_once '../php/db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error   = '';

// ── Handle POST actions ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $name        = mysqli_real_escape_string($conn, trim($_POST['name']));
        $category_id = intval($_POST['category_id']);
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $price       = floatval($_POST['price']);

        $stmt = mysqli_prepare($conn,
            "INSERT INTO products (category_id, name, description, price, status) VALUES (?,?,?,?,1)");
        mysqli_stmt_bind_param($stmt, "issd", $category_id, $name, $description, $price);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Product added successfully!";
        } else {
            $error = "Error adding product. Please try again.";
        }
        mysqli_stmt_close($stmt);

    } elseif ($_POST['action'] === 'edit') {
        $product_id  = intval($_POST['product_id']);
        $name        = mysqli_real_escape_string($conn, trim($_POST['name']));
        $category_id = intval($_POST['category_id']);
        $description = mysqli_real_escape_string($conn, trim($_POST['description']));
        $price       = floatval($_POST['price']);

        $stmt = mysqli_prepare($conn,
            "UPDATE products SET name=?, category_id=?, description=?, price=? WHERE product_id=?");
        mysqli_stmt_bind_param($stmt, "sisdi", $name, $category_id, $description, $price, $product_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Product updated successfully!";
        } else {
            $error = "Error updating product. Please try again.";
        }
        mysqli_stmt_close($stmt);

    } elseif ($_POST['action'] === 'toggle_status') {
        $product_id = intval($_POST['product_id']);
        $new_status = intval($_POST['new_status']);

        $stmt = mysqli_prepare($conn, "UPDATE products SET status=? WHERE product_id=?");
        mysqli_stmt_bind_param($stmt, "ii", $new_status, $product_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Product status updated!";
        } else {
            $error = "Error updating status.";
        }
        mysqli_stmt_close($stmt);
    }
}

// ── Fetch data ───────────────────────────────────────────────
$products_result = mysqli_query($conn,
    "SELECT p.*, c.name AS category_name
     FROM products p
     JOIN categories c ON p.category_id = c.category_id
     ORDER BY c.name, p.name");

$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Count totals for toolbar badge
$total_products = mysqli_num_rows($products_result);
mysqli_data_seek($products_result, 0);

define('BASE_URL', '..');
include 'includes/header.php';
?>

<div class="admin-wrapper">

  <div class="page-header">
    <div>
      <h1>Products</h1>
      <p>Manage menu items &amp; categories</p>
    </div>
  </div>

  <!-- Flash messages -->
  <?php if ($message): ?>
    <div class="flash-success"><i class="fas fa-check-circle"></i><?= $message ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="flash-error"><i class="fas fa-exclamation-circle"></i><?= $error ?></div>
  <?php endif; ?>

  <!-- Toolbar -->
  <div class="toolbar">
    <div class="search-box">
      <span class="search-icon"><i class="fas fa-search"></i></span>
      <input type="text" id="productSearch" placeholder="Search products…"
             oninput="filterTable(this.value)"/>
    </div>
    <button class="btn-primary" onclick="openModal('addProductModal')">
      <i class="fas fa-plus"></i> Add New Product
    </button>
  </div>

  <!-- Products table -->
  <div class="card">
    <div class="card-header">
      <h2>All Products</h2>
      <span style="font-size:.72rem;color:var(--muted);font-weight:600;letter-spacing:.08em;">
        <?= $total_products ?> item<?= $total_products !== 1 ? 's' : '' ?>
      </span>
    </div>
    <table class="admin-table" id="productsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>Name</th>
          <th>Category</th>
          <th>Price</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($total_products === 0): ?>
          <tr>
            <td colspan="6">
              <div class="empty-state">
                <div class="empty-state-icon"><i class="fas fa-box-open"></i></div>
                <p>No products found. Add your first product!</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php while ($product = mysqli_fetch_assoc($products_result)): ?>
            <tr>
              <td style="color:var(--muted);">#<?= $product['product_id'] ?></td>
              <td class="product-name-cell"><?= htmlspecialchars($product['name']) ?></td>
              <td><?= htmlspecialchars($product['category_name']) ?></td>
              <td><strong>&#8369;<?= number_format($product['price'], 2) ?></strong></td>
              <td>
                <span class="badge badge-<?= $product['status'] ? 'active' : 'inactive' ?>">
                  <?= $product['status'] ? 'Active' : 'Hidden' ?>
                </span>
              </td>
              <td style="display:flex;gap:6px;align-items:center;">
                <!-- Edit -->
                <button class="btn-icon btn-icon-edit" title="Edit Product"
                        onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)">
                  <i class="fas fa-pen"></i>
                </button>
                <!-- Toggle status -->
                <form method="POST" style="margin:0;">
                  <input type="hidden" name="action"     value="toggle_status"/>
                  <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>"/>
                  <input type="hidden" name="new_status" value="<?= $product['status'] ? 0 : 1 ?>"/>
                  <button type="submit" class="btn-icon btn-icon-toggle"
                          title="<?= $product['status'] ? 'Hide product' : 'Show product' ?>">
                    <i class="fas fa-<?= $product['status'] ? 'eye-slash' : 'eye' ?>"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div><!-- /.card -->

</div><!-- /.admin-wrapper -->

<!-- ══ ADD PRODUCT MODAL ════════════════════════════════════ -->
<div class="modal-overlay" id="addProductModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <h3><i class="fas fa-plus" style="margin-right:8px;font-size:.9rem;opacity:.8;"></i>Add New Product</h3>
      <button class="modal-close" onclick="closeModal('addProductModal')">&#x2715;</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action" value="add"/>

        <div class="form-group">
          <label class="form-label">Product Name</label>
          <input type="text" name="name" class="form-control" placeholder="e.g. Caramel Latte" required/>
        </div>

        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category_id" class="form-control" required>
            <?php
            mysqli_data_seek($categories_result, 0);
            while ($cat = mysqli_fetch_assoc($categories_result)): ?>
              <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"
                    placeholder="Short description for the menu…"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Price (&#8369;)</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0"
                 placeholder="0.00" required/>
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
      <h3><i class="fas fa-pen" style="margin-right:8px;font-size:.9rem;opacity:.8;"></i>Edit Product</h3>
      <button class="modal-close" onclick="closeModal('editProductModal')">&#x2715;</button>
    </div>
    <form method="POST">
      <div class="modal-body">
        <input type="hidden" name="action"     value="edit"/>
        <input type="hidden" name="product_id" id="edit_product_id"/>

        <div class="form-group">
          <label class="form-label">Product Name</label>
          <input type="text" name="name" id="edit_name" class="form-control" required/>
        </div>

        <div class="form-group">
          <label class="form-label">Category</label>
          <select name="category_id" id="edit_category_id" class="form-control" required>
            <?php
            mysqli_data_seek($categories_result, 0);
            while ($cat = mysqli_fetch_assoc($categories_result)): ?>
              <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">Price (&#8369;)</label>
          <input type="number" name="price" id="edit_price" class="form-control"
                 step="0.01" min="0" required/>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeModal('editProductModal')">Cancel</button>
        <button type="submit" class="btn-primary"><i class="fas fa-floppy-disk"></i> Update Product</button>
      </div>
    </form>
  </div>
</div>

<script>
  function openModal(id)  { document.getElementById(id).style.display = 'flex'; }
  function closeModal(id) { document.getElementById(id).style.display = 'none'; }

  // Close on backdrop click
  document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) overlay.style.display = 'none';
    });
  });

  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay').forEach(function(o) {
        o.style.display = 'none';
      });
    }
  });

  function editProduct(product) {
    document.getElementById('edit_product_id').value  = product.product_id;
    document.getElementById('edit_name').value        = product.name;
    document.getElementById('edit_category_id').value = product.category_id;
    document.getElementById('edit_description').value = product.description;
    document.getElementById('edit_price').value       = product.price;
    openModal('editProductModal');
  }

  function filterTable(term) {
    const rows = document.querySelectorAll('#productsTable tbody tr');
    const q = term.toLowerCase();
    rows.forEach(function(row) {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  }
</script>

<?php include 'footer.php'; ?>