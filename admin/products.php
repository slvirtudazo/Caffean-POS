<?php
/**
 * Purge Coffee Shop - Admin Products Management
 * This page allows administrators to view all products, add new products,
 * edit existing products, and toggle product availability status.
 */

require_once '../php/db_connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: ../login.php");
    exit();
}

$message = '';
$error = '';

// Handle product operations (add, edit, delete, toggle status)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        
        // Add new product
        if ($_POST['action'] == 'add') {
            $name = mysqli_real_escape_string($conn, trim($_POST['name']));
            $category_id = intval($_POST['category_id']);
            $description = mysqli_real_escape_string($conn, trim($_POST['description']));
            $price = floatval($_POST['price']);
            
            $insert_query = "INSERT INTO products (category_id, name, description, price, status) 
                           VALUES (?, ?, ?, ?, 1)";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "issd", $category_id, $name, $description, $price);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Product added successfully!";
            } else {
                $error = "Error adding product.";
            }
            mysqli_stmt_close($stmt);
        }
        
        // Edit existing product
        elseif ($_POST['action'] == 'edit') {
            $product_id = intval($_POST['product_id']);
            $name = mysqli_real_escape_string($conn, trim($_POST['name']));
            $category_id = intval($_POST['category_id']);
            $description = mysqli_real_escape_string($conn, trim($_POST['description']));
            $price = floatval($_POST['price']);
            
            $update_query = "UPDATE products SET name = ?, category_id = ?, description = ?, price = ? 
                           WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "sisdi", $name, $category_id, $description, $price, $product_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Product updated successfully!";
            } else {
                $error = "Error updating product.";
            }
            mysqli_stmt_close($stmt);
        }
        
        // Toggle product status (active/inactive)
        elseif ($_POST['action'] == 'toggle_status') {
            $product_id = intval($_POST['product_id']);
            $new_status = intval($_POST['new_status']);
            
            $update_query = "UPDATE products SET status = ? WHERE product_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "ii", $new_status, $product_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Product status updated!";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch all products with category information
$products_query = "SELECT p.*, c.name as category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   ORDER BY p.category_id, p.name";
$products_result = mysqli_query($conn, $products_query);

// Fetch all categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = mysqli_query($conn, $categories_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Purge Coffee Admin</title>
    
    <link rel="icon" type="image/png" href="../images/coffee_beans_logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Admin panel styling */
        :root {
            --ivory-cream: #F5F1E8;
            --deep-maroon: #2A0000;
            --burgundy-wine: #5B1312;
            --dark-brown: #3C1518;
        }
        
        body {
            background-color: var(--ivory-cream);
            font-family: 'Inter', sans-serif;
        }
        
        .admin-header {
            background-color: var(--deep-maroon);
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .btn-admin {
            background-color: var(--deep-maroon);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-admin:hover {
            background-color: var(--burgundy-wine);
            color: white;
        }
        
        .product-table {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(42, 0, 0, 0.08);
        }
        
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }
        
        .modal-content {
            border-radius: 12px;
        }
        
        .modal-header {
            background-color: var(--deep-maroon);
            color: white;
            border-radius: 12px 12px 0 0;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-brown);
        }
        
        .alert {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    
    <!-- Admin header -->
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="h3 mb-0">Products Management</h1>
                <div>
                    <a href="dashboard.php" class="btn-admin me-2">
                        <i class="fas fa-arrow-left me-2"></i>Dashboard
                    </a>
                    <a href="../php/logout.php" class="btn-admin">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Success/Error messages -->
        <?php if($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Add Product Button -->
        <div class="mb-4">
            <button class="btn-admin" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus me-2"></i>Add New Product
            </button>
        </div>

        <!-- Products Table -->
        <div class="product-table">
            <h2 class="h4 mb-4" style="color: var(--deep-maroon);">All Products</h2>
            <div class="table-responsive">
                <table class="table table-hover">
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
                        <?php while($product = mysqli_fetch_assoc($products_result)): ?>
                            <tr>
                                <td><?php echo $product['product_id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td>₱<?php echo number_format($product['price'], 2); ?></td>
                                <td>
                                    <span class="<?php echo $product['status'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $product['status'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Edit button -->
                                    <button class="btn btn-sm btn-primary" 
                                            onclick="editProduct(<?php echo htmlspecialchars(json_encode($product)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <!-- Toggle status button -->
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $product['status'] ? 0 : 1; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $product['status'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <i class="fas fa-<?php echo $product['status'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <?php 
                                mysqli_data_seek($categories_result, 0);
                                while($cat = mysqli_fetch_assoc($categories_result)): 
                                ?>
                                    <option value="<?php echo $cat['category_id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-admin">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="editProductForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="product_id" id="edit_product_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                <?php 
                                mysqli_data_seek($categories_result, 0);
                                while($cat = mysqli_fetch_assoc($categories_result)): 
                                ?>
                                    <option value="<?php echo $cat['category_id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price (₱)</label>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn-admin">Update Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to populate edit modal with product data
        function editProduct(product) {
            document.getElementById('edit_product_id').value = product.product_id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category_id').value = product.category_id;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('edit_price').value = product.price;
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('editProductModal')).show();
        }
    </script>
    
</body>
</html>