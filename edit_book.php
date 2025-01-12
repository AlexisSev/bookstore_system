<?php
require_once('connection.php');

if (isset($_GET['edit_book'])) {
    $editBookId = $_GET['edit_book'];

    // Fetch the book details from the database
    $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = :book_id");
    $stmt->execute(['book_id' => $editBookId]);
    $bookToEdit = $stmt->fetch();
}
if (isset($_POST['update_book'])) {
    $book_id = $_POST['book_id'];
    $title = $_POST['title'];
    $author = $_POST['author'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageDestination = './images/books/' . $imageName;
        move_uploaded_file($imageTmpName, $imageDestination);

        // Update with new image
        $stmt = $pdo->prepare("UPDATE books SET title = :title, author = :author, price = :price, stock = :stock, category_id = :category_id, description = :description, image = :image WHERE book_id = :book_id");
        $stmt->execute([
            'title' => $title,
            'author' => $author,
            'price' => $price,
            'stock' => $stock,
            'category_id' => $category_id,
            'description' => $description,
            'image' => $imageName,
            'book_id' => $book_id
        ]);
    } else {
        // Update without changing the image
        $stmt = $pdo->prepare("UPDATE books SET title = :title, author = :author, price = :price, stock = :stock, category_id = :category_id, description = :description WHERE book_id = :book_id");
        $stmt->execute([
            'title' => $title,
            'author' => $author,
            'price' => $price,
            'stock' => $stock,
            'category_id' => $category_id,
            'description' => $description,
            'book_id' => $book_id
        ]);
    }

    $_SESSION['message'] = 'Book updated successfully!';
    header('Location: admin_dashboard.php#books');
    exit();
}
    $categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Book</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
        <style>
            /* Make the modal occupy most of the screen */
            .modal-dialog {
                max-width: 90%;
                width: 90%;
            }

            .modal-content {
                border-radius: 0;
                border: none;
            }

            /* Adjust form fields for a full-screen experience */
            .form-label {
                font-weight: bold;
            }

            .form-control,
            .form-select {
                padding: 10px;
                font-size: 1rem;
            }

            .modal-header,
            .modal-footer {
                padding: 1rem 1.5rem;
            }

            .modal-footer {
                justify-content: space-between;
            }
        </style>
    </head>

    <body>
        <!-- Edit Book Modal -->
        <div class="modal show d-block" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editBookModalLabel">Edit Book Details</h5>
                            <a href="admin_dashboard.php" class="btn-close" aria-label="Close"></a>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="book_id" value="<?= htmlspecialchars($bookToEdit['book_id']) ?>">

                            <!-- Title and Author -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Book Title</label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($bookToEdit['title']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="author" class="form-label">Author</label>
                                    <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($bookToEdit['author']) ?>" required>
                                </div>
                            </div>

                            <!-- Price and Stock -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label for="price" class="form-label">Price (₱)</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= htmlspecialchars($bookToEdit['price']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="stock" name="stock" value="<?= htmlspecialchars($bookToEdit['stock']) ?>" required>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category['category_id']) ?>" <?= $category['category_id'] == $bookToEdit['category_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?= htmlspecialchars($bookToEdit['description']) ?></textarea>
                            </div>

                            <!-- Book Image -->
                            <div class="mb-3">
                                <label for="image" class="form-label">Book Image (optional)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                                <small class="text-muted">If you don't select a new image, the current one will be retained.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" name="update_book">Save Changes</button>
                            <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>

    </html>
