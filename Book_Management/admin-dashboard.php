<?php
include_once('connection.php');
session_start();

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: sign-up.php');  // Redirect to login page if not logged in or not an admin
  exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

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
    $imageName = 'default.jpg';  // Default image if none is uploaded
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
  header('Location: admin-dashboard.php');  // Redirect back to the admin page
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
      unlink($imagePath);  // Delete the image file
    }
    
    // Delete the book from the database
    $deleteStmt = $pdo->prepare("DELETE FROM books WHERE book_id = :book_id");
    $deleteStmt->execute(['book_id' => $book_id]);

    $_SESSION['message'] = 'Book deleted successfully!';
  }
  
  header('Location: admin.php');
  exit();
}

// Fetch books for display in the admin panel
$books = $pdo->query("SELECT * FROM books")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Manage Books</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

  <div class="container mt-5">
    <h2>Admin Panel - Manage Books</h2>

    <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-success">
        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
      </div>
    <?php endif; ?>

    <!-- Add Book Form -->
    <form method="POST" enctype="multipart/form-data" class="mb-5">
      <div class="mb-3">
        <label for="title" class="form-label">Book Title</label>
        <input type="text" class="form-control" id="title" name="title" required>
      </div>
      <div class="mb-3">
        <label for="author" class="form-label">Author</label>
        <input type="text" class="form-control" id="author" name="author" required>
      </div>
      <div class="mb-3">
        <label for="price" class="form-label">Price</label>
        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
      </div>
      <div class="mb-3">
        <label for="stock" class="form-label">Stock</label>
        <input type="number" class="form-control" id="stock" name="stock" required>
      </div>
      <div class="mb-3">
        <label for="category_id" class="form-label">Category ID</label>
        <input type="number" class="form-control" id="category_id" name="category_id" required>
      </div>
      <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
      </div>
      <div class="mb-3">
        <label for="image" class="form-label">Book Image</label>
        <input type="file" class="form-control" id="image" name="image" accept="image/*">
      </div>
      <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
    </form>

    <!-- Display Books -->
    <h3>Books List</h3>
    <div class="row">
      <?php foreach ($books as $book): ?>
        <div class="col-md-4 mb-4">
          <div class="card">
            <img src="./images/books/<?= $book['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>">
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
              <p class="card-text">Author: <?= htmlspecialchars($book['author']) ?></p>
              <p class="card-text">Price: ₱<?= htmlspecialchars($book['price']) ?></p>
              <p class="card-text">Stock: <?= htmlspecialchars($book['stock']) ?></p>
              <a href="admin.php?delete_book=<?= $book['book_id'] ?>" class="btn btn-danger">Delete</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
