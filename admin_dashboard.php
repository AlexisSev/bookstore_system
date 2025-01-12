<?php
include_once('connection.php');
session_start();

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: sign-up.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Add Category functionality
if (isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    $description = $_POST['category_description'];

    $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
    $stmt->execute(['name' => $name, 'description' => $description]);

    $_SESSION['message'] = 'Category added successfully!';
    header('Location: admin_dashboard.php#categories');
    exit();
}

// Handle Delete Category functionality
if (isset($_GET['delete_category'])) {
    $category_id = $_GET['delete_category'];

    // Check if there are books in this category
    $bookCheckStmt = $pdo->prepare("SELECT COUNT(*) AS count FROM books WHERE category_id = :category_id");
    $bookCheckStmt->execute(['category_id' => $category_id]);
    $bookCount = $bookCheckStmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($bookCount > 0) {
        $_SESSION['message'] = 'Cannot delete category as it contains books.';
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = :category_id");
        $stmt->execute(['category_id' => $category_id]);
        $_SESSION['message'] = 'Category deleted successfully!';
    }
    header('Location: admin_dashboard.php#categories');
    exit();
}

// Handling Add Book functionality
if (isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];

    // Handling Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageName = $_FILES['image']['name'];
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageDestination = './images/books/' . $imageName;
        move_uploaded_file($imageTmpName, $imageDestination);
    } else {
        $imageName = 'default.jpg';
    }

    // Insert the new book into the database
    $stmt = $pdo->prepare("INSERT INTO books (title, author, price, stock, category_id, description, image) 
                           VALUES (:title, :author, :price, :stock, :category_id, :description, :image)");
    $stmt->execute([
        'title' => $title,
        'author' => $author,
        'price' => $price,
        'stock' => $stock,
        'category_id' => $category_id,
        'description' => $description,
        'image' => $imageName
    ]);

    $_SESSION['message'] = 'Book added successfully!';
    header('Location: admin_dashboard.php');
    exit();
}

// Handling Delete Book functionality
if (isset($_GET['delete_book'])) {
    $book_id = $_GET['delete_book'];

    // Get the image name from the database to delete the file
    $stmt = $pdo->prepare("SELECT image FROM books WHERE book_id = :book_id");
    $stmt->execute(['book_id' => $book_id]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($book) {
        $imagePath = './images/books/' . $book['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // Delete the image file
        }

        // Delete the book from the database
        $deleteStmt = $pdo->prepare("DELETE FROM books WHERE book_id = :book_id");
        $deleteStmt->execute(['book_id' => $book_id]);

        $_SESSION['message'] = 'Book deleted successfully!';
    }

    header('Location: admin_dashboard.php');
    exit();
}

// Fetch categories and books for display
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$books = $pdo->query("SELECT * FROM books")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookstore Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="dashboard.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Bookstore Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="#categories">Categories</a></li>
                    <li class="nav-item"><a class="nav-link" href="#books">Books</a></li>
                    <li class="nav-item"><a class="nav-link" href="#sales">Sales</a></li>
                    <li class="nav-item"><a class="nav-link" href="#messages">Messages</a></li>
                </ul>
            </div>
        </div>
    </nav>


    <!-- Main Container -->
    <div class="container-fluid">
        <!-- Dashboard Section -->
        <div id="dashboard" class="mb-5">
            <h2 class="text-center">Dashboard</h2>
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Sales</h5>
                            <p class="card-text">$10,000</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Books</h5>
                            <p class="card-text"><?= count($books) ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Out of Stock</h5>
                            <p class="card-text">50</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-danger mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Unread Messages</h5>
                            <p class="card-text">10</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div id="categories" class="mb-5">
            <h2 class="mb-4">Categories</h2>

            <form method="POST" action="" class="p-4 border rounded shadow-sm bg-light">
                <div class="mb-3">
                    <label for="category_name" class="form-label">Category Name</label>
                    <input type="text" id="category_name" name="category_name" class="form-control" placeholder="Enter category name" required>
                </div>
                <div class="mb-3">
                    <label for="category_description" class="form-label">Category Description</label>
                    <textarea id="category_description" name="category_description" class="form-control" placeholder="Enter category description" rows="3" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100" name="add_category">Add Category</button>
            </form>

            <table class="table table-striped mt-4">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?= htmlspecialchars($category['category_id']) ?></td>
                            <td><?= htmlspecialchars($category['name']) ?></td>
                            <td>
                                <a href="?delete_category=<?= $category['category_id'] ?>" class="btn btn-danger btn-sm">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>


        <div id="add-book" class="mb-5">
            <h2>Add New Book</h2>
            <form method="POST" enctype="multipart/form-data">
                <!-- First Row: Title and Author -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Book Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="col-md-6">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" class="form-control" id="author" name="author" required>
                    </div>
                </div>

                <!-- Second Row: Price and Stock -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="price" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                    </div>
                    <div class="col-md-6">
                        <label for="stock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" required>
                    </div>
                </div>

                <!-- Third Row: Category and Image -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= htmlspecialchars($category['category_id']) ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Book Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                    </div>
                </div>

                <!-- Fourth Row: Description -->
                <div class="row mb-3">
                    <div class="col">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" required></textarea>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center">
                    <button type="submit" class="btn btn-success" name="add_book">Add Book</button>
                </div>
            </form>
        </div>

        <div id="books" class="mb-5">
            <h2>Books</h2>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($books as $book): ?>
                    <div class="col">
                        <div class="card h-100">
                            <img src="./images/books/<?= htmlspecialchars($book['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>">
                            <div class="card-body">
                                <h5 class="card-title text-center"><?= htmlspecialchars($book['title']) ?></h5>
                                <p class="card-text">Author: <?= htmlspecialchars($book['author']) ?></p>
                                <p class="card-text">Price: ₱<?= htmlspecialchars($book['price']) ?></p>
                                <p class="card-text">Stock: <?= htmlspecialchars($book['stock']) ?></p>
                                <div class="text-center">
                                    <a href="edit_book.php?edit_book=<?= $book['book_id'] ?>" class="btn btn-primary">Edit Book</a>

                                    <a href="?delete_book=<?= $book['book_id'] ?>" class="btn btn-danger">Delete</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Sales Section -->
        <div id="sales" class="mb-5">
            <h2>Sales</h2>
            <p>Total Sales: $10,000</p>
        </div>

        <!-- Messages Section -->
        <div id="messages" class="mb-5">
            <h2>Messages</h2>
            <p>You have 10 unread messages.</p>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>