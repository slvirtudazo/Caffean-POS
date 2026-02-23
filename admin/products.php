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

  /* ── Image upload helper ──────────────────────────────── */
  function handleProductImageUpload($file_key)
  {
    if (empty($_FILES[$file_key]['name'])) return null;
    $file    = $_FILES[$file_key];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null; // 5 MB cap
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . uniqid() . '.' . $ext;
    $upload_dir = __DIR__ . '/../images/products/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $dest = $upload_dir . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
      return 'images/products/' . $filename;
    }
    return null;
  }

  if ($_POST['action'] === 'add') {
    $name        = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $image_path  = handleProductImageUpload('image');
    $stmt = mysqli_prepare(
      $conn,
      "INSERT INTO products (category_id, name, description, price, image_path, status) VALUES (?,?,?,?,?,1)"
    );
    mysqli_stmt_bind_param($stmt, "issds", $category_id, $name, $description, $price, $image_path);
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
    $new_image   = handleProductImageUpload('image');

    if ($new_image) {
      // Delete old image if it's a product-uploaded one
      $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image_path FROM products WHERE product_id=$product_id"));
      if ($old && $old['image_path'] && strpos($old['image_path'], 'images/products/') === 0) {
        $old_file = __DIR__ . '/../' . $old['image_path'];
        if (file_exists($old_file)) @unlink($old_file);
      }
      $stmt = mysqli_prepare(
        $conn,
        "UPDATE products SET name=?, category_id=?, description=?, price=?, status=?, image_path=? WHERE product_id=?"
      );
      mysqli_stmt_bind_param($stmt, "sisdisi", $name, $category_id, $description, $price, $status, $new_image, $product_id);
    } else {
      $stmt = mysqli_prepare(
        $conn,
        "UPDATE products SET name=?, category_id=?, description=?, price=?, status=? WHERE product_id=?"
      );
      mysqli_stmt_bind_param($stmt, "sisdii", $name, $category_id, $description, $price, $status, $product_id);
    }
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
      <!-- Product Image Preview -->
      <div id="view_img_wrap" class="view-img-wrap" style="display:none;">
        <img id="view_img" class="view-product-img" src="" alt="Product Image">
      </div>
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
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="action" value="add" />

        <!-- ── Image thumbnail at top ── -->
        <div class="img-thumb-row">
          <div class="img-thumb-wrap" id="add_thumb_wrap"
            onclick="document.getElementById('add_image_input').click()"
            title="Click to upload product image">
            <img id="add_img_preview" class="img-thumb-preview" src="" alt="Preview" style="display:none;">
            <div class="img-thumb-placeholder" id="add_img_placeholder">
              <i class="fas fa-camera"></i>
            </div>
            <div class="img-thumb-hover-overlay">
              <i class="fas fa-camera"></i>
            </div>
          </div>
          <div class="img-thumb-meta">
            <span class="img-thumb-label">Upload Product Image</span>
            <span class="img-thumb-hint">Accepted formats: JPG, PNG, WEBP (Max 5MB)</span>
            <button type="button" class="img-remove-btn" id="add_img_remove" style="display:none;"
              onclick="removeImage('add_image_input','add_img_preview','add_img_placeholder','add_img_remove', 'add_thumb_wrap')">
              <i class="fas fa-times"></i> Remove
            </button>
          </div>
          <input type="file" id="add_image_input" name="image"
            accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;"
            onchange="previewImage(this,'add_img_preview','add_img_placeholder','add_img_remove','add_thumb_wrap')" />
        </div>

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
    <form method="POST" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="action" value="edit" />
        <input type="hidden" name="product_id" id="edit_product_id" />

        <!-- ── Image thumbnail at top ── -->
        <div class="img-thumb-row">
          <div class="img-thumb-wrap" id="edit_thumb_wrap"
            onclick="document.getElementById('edit_image_input').click()"
            title="Click to change product image">
            <img id="edit_img_preview" class="img-thumb-preview" src="" alt="Preview" style="display:none;">
            <div class="img-thumb-placeholder" id="edit_img_placeholder">
              <i class="fas fa-camera"></i>
            </div>
            <div class="img-thumb-hover-overlay">
              <i class="fas fa-camera"></i>
            </div>
          </div>
          <div class="img-thumb-meta">
            <span class="img-thumb-label">Upload Product Image</span>
            <span class="img-thumb-hint">Accepted formats: JPG, PNG, WEBP (Max 5MB)</span>
            <button type="button" class="img-remove-btn" id="edit_img_remove" style="display:none;"
              onclick="removeImage('edit_image_input','edit_img_preview','edit_img_placeholder','edit_img_remove','edit_thumb_wrap')">
              <i class="fas fa-times"></i> Remove
            </button>
          </div>
          <input type="file" id="edit_image_input" name="image"
            accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;"
            onchange="previewImage(this,'edit_img_preview','edit_img_placeholder','edit_img_remove','edit_thumb_wrap')" />
        </div>

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

  /* ── Image upload helpers ──────────────────────────────────── */
  function previewImage(input, previewId, placeholderId, removeId, wrapId) {
    var preview = document.getElementById(previewId);
    var placeholder = document.getElementById(placeholderId);
    var removeBtn = document.getElementById(removeId);
    var wrap = wrapId ? document.getElementById(wrapId) : null;
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
        removeBtn.style.display = 'inline-flex';
        if (wrap) wrap.classList.add('has-image');
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  function removeImage(inputId, previewId, placeholderId, removeId, wrapId) {
    document.getElementById(inputId).value = '';
    var preview = document.getElementById(previewId);
    var placeholder = document.getElementById(placeholderId);
    var removeBtn = document.getElementById(removeId);
    var wrap = wrapId ? document.getElementById(wrapId) : null;
    preview.src = '';
    preview.style.display = 'none';
    placeholder.style.display = 'flex';
    removeBtn.style.display = 'none';
    if (wrap) wrap.classList.remove('has-image');
  }

  /* ── Product view/edit/delete ──────────────────────────────── */
  var _baseUrl = '../'; // path from admin/ back to root for images

  function viewProduct(p) {
    document.getElementById('view_product_id').textContent = '#' + p.product_id;
    document.getElementById('view_name').textContent = p.name;
    document.getElementById('view_category').textContent = p.category_name;
    document.getElementById('view_price').textContent = '\u20B1' + parseFloat(p.price).toFixed(2);
    document.getElementById('view_description').textContent = p.description || '\u2014';
    document.getElementById('view_status').innerHTML = p.status == 1 ?
      '<span class="badge badge-active">Active</span>' :
      '<span class="badge badge-inactive">Hidden</span>';

    // Show product image if available
    var imgWrap = document.getElementById('view_img_wrap');
    var imgEl = document.getElementById('view_img');
    if (p.image_path) {
      imgEl.src = _baseUrl + p.image_path;
      imgWrap.style.display = 'block';
    } else {
      imgWrap.style.display = 'none';
    }

    openModal('viewProductModal');
  }

  function editProduct(p) {
    document.getElementById('edit_product_id').value = p.product_id;
    document.getElementById('edit_name').value = p.name;
    document.getElementById('edit_category_id').value = p.category_id;
    document.getElementById('edit_description').value = p.description;
    document.getElementById('edit_price').value = p.price;
    document.getElementById('edit_status').value = p.status;

    // Reset file input
    document.getElementById('edit_image_input').value = '';
    var preview = document.getElementById('edit_img_preview');
    var placeholder = document.getElementById('edit_img_placeholder');
    var removeBtn = document.getElementById('edit_img_remove');
    var wrap = document.getElementById('edit_thumb_wrap');

    if (p.image_path) {
      preview.src = _baseUrl + p.image_path;
      preview.style.display = 'block';
      placeholder.style.display = 'none';
      removeBtn.style.display = 'none';
      wrap.classList.add('has-image');
    } else {
      preview.src = '';
      preview.style.display = 'none';
      placeholder.style.display = 'flex';
      removeBtn.style.display = 'none';
      wrap.classList.remove('has-image');
    }

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