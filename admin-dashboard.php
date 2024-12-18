<?php
include_once('connection.php');
session_start();

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  header('Location: sign-up.php'); // Redirect to login page if not logged in or not an admin
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
    $imageName = 'default.jpg'; // Default image if none is uploaded
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
  header('Location: admin-dashboard.php'); // Redirect back to the admin page
  exit();
}

// Handle Add Category functionality
if (isset($_POST['add_category'])) {
  $name = $_POST['name'];
  $description = $_POST['description'];

  $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
  $stmt->execute(['name' => $name, 'description' => $description]);

  $_SESSION['message'] = 'Category added successfully!';
  header('Location: admin-dashboard.php');
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
  header('Location: admin-dashboard.php');
  exit();
}

// Fetch categories and books for display
$categories = $pdo->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
$books = $pdo->query("SELECT * FROM books")->fetchAll(PDO::FETCH_ASSOC);

// Handle Update Book functionality
if (isset($_POST['update_book'])) {
  $book_id = $_POST['book_id'];
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

    // Update book details including the image
    $stmt = $pdo->prepare("UPDATE books SET title = :title, author = :author, price = :price, 
                               stock = :stock, category_id = :category_id, description = :description, 
                               image = :image WHERE book_id = :book_id");
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
    // Update book details without changing the image
    $stmt = $pdo->prepare("UPDATE books SET title = :title, author = :author, price = :price, 
                               stock = :stock, category_id = :category_id, description = :description 
                               WHERE book_id = :book_id");
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
  header('Location: admin-dashboard.php');
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

  header('Location: admin-dashboard.php');
  exit();
}

// Fetch books for display in the admin panel
$books = $pdo->query("SELECT * FROM books")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_GET['edit_book'])): ?>
  <?php
  $book_id = $_GET['edit_book'];
  $stmt = $pdo->prepare("SELECT * FROM books WHERE book_id = :book_id");
  $stmt->execute(['book_id' => $book_id]);
  $book = $stmt->fetch(PDO::FETCH_ASSOC);
  ?>
<?php endif; ?>



<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Manage Books</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


  <style>
    body {
      background-color: #f8f9fa;
      font-family: 'Arial', sans-serif;
    }

    .navbar {
      margin-bottom: 30px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .card {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border: none;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    .card-title {
      color: #495057;
      font-weight: bold;
    }

    .btn {
      margin-top: 10px;
    }

    h2,
    h3 {
      color: #343a40;
      font-weight: 600;
    }

    .container {
      max-width: 1200px;
    }

    .form-label {
      font-weight: 500;
      color: #495057;
    }

    .form-control,
    .btn {
      border-radius: 8px;
    }

    .btn-primary {
      background-color: #4CAF50;
      border: none;
    }

    .btn-primary:hover {
      background-color: #45a049;
    }

    .btn-secondary {
      background-color: #007BFF;
      border: none;
    }

    .btn-secondary:hover {
      background-color: #0056b3;
    }

    .btn-danger {
      background-color: #DC3545;
      border: none;
    }

    .btn-danger:hover {
      background-color: #c82333;
    }

    .alert {
      border-radius: 8px;
    }

    .navbar .dropdown-menu {
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}
.navbar .dropdown-item {
  padding: 10px 20px;
}


  </style>
</head>

<body>

 <!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Admin Panel</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item">
          <a class="nav-link active" href="#addBook">Manage Books</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#addCategory">Manage Categories</a>
        </li>
      </ul>
      <ul class="navbar-nav">
        <!-- Profile Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="https://via.placeholder.com/45" alt="Profile" class="rounded-circle me-2" style="width: 45px; height: 45px;">
          </a>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
            <li><a class="dropdown-item" href="profile.php">Profile Settings</a></li>
            <li><a class="dropdown-item" href="#" id="logoutButton">Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>



  <div class="container">
    <h2 class="mb-4 text-center">Admin Panel - Manage Books</h2>

    <?php if (isset($_SESSION['message'])): ?>
      <script>
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: '<?php echo $_SESSION['message']; ?>',
          showConfirmButton: false,
          timer: 1500
        });
      </script>
      <?php unset($_SESSION['message']); ?>
    <?php endif; ?>


    <!-- Add Book Form -->
    <section id="addBook" class="mb-5">
      <h3>Add Book</h3>
      <form method="POST" enctype="multipart/form-data" class="bg-white p-4 rounded shadow-sm">
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
          <label for="category_id" class="form-label">Category</label>
          <select class="form-control" id="category_id" name="category_id" required>
            <?php foreach ($categories as $category): ?>
              <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
        </div>
        <div class="mb-3">
          <label for="image" class="form-label">Book Image</label>
          <input type="file" class="form-control" id="image" name="image" accept="image/*">
        </div>
        <form id="addBookForm" method="POST">
          <button type="submit" name="add_book" class="btn btn-primary">Add Book</button>
        </form>
      </form>
    </section>

    <!-- Add Category Form -->
    <section id="addCategory" class="mb-5">
      <h3>Add Category</h3>
      <form method="POST" class="bg-white p-4 rounded shadow-sm">
        <div class="mb-3">
          <label for="name" class="form-label">Category Name</label>
          <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
          <label for="description" class="form-label">Description</label>
          <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
        </div>
        <button type="submit" name="add_category" class="btn btn-secondary">Add Category</button>
      </form>
    </section>

    <!-- Display Categories -->
    <h3>Categories List</h3>
    <div class="row">
      <?php foreach ($categories as $category): ?>
        <div class="col-md-4 mb-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title text-center">
                <?= htmlspecialchars($category['name']) ?>
              </h5>
              <p class="card-text"> <?= htmlspecialchars($category['description']) ?> </p>
              <div class="text-center">
                <a href="#" class="btn btn-danger delete-category" data-id="<?= $category['category_id'] ?>">Delete</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Display Books -->
    <h3 class="mt-5">Books List</h3>
    <div class="row">
      <?php foreach ($books as $book): ?>
        <div class="col-md-4 mb-4">
          <div class="card">
            <img src="./images/books/<?= $book['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($book['title']) ?>">
            <div class="card-body">
              <h5 class="card-title text-center"> <?= htmlspecialchars($book['title']) ?> </h5>
              <p class="card-text">Author: <?= htmlspecialchars($book['author']) ?></p>
              <p class="card-text">Price: ₱<?= htmlspecialchars($book['price']) ?></p>
              <p class="card-text">Stock: <?= htmlspecialchars($book['stock']) ?></p>
              <div class="text-center">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editBookModal">
                  Edit Book
                </button>
                <a href="#" class="btn btn-danger delete-book" data-id="<?= $book['book_id'] ?>">Delete</a>
              </div>

            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1" aria-labelledby="editBookModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editBookModalLabel">Edit Book</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" class="p-4">
              <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
              <div class="mb-3">
                <label for="title" class="form-label">Book Title</label>
                <input type="text" class="form-control" id="title" name="title" value="<?= htmlspecialchars($book['title']) ?>" required>
              </div>
              <div class="mb-3">
                <label for="author" class="form-label">Author</label>
                <input type="text" class="form-control" id="author" name="author" value="<?= htmlspecialchars($book['author']) ?>" required>
              </div>
              <div class="mb-3">
                <label for="price" class="form-label">Price</label>
                <input type="number" class="form-control" id="price" name="price" step="0.01" value="<?= htmlspecialchars($book['price']) ?>" required>
              </div>
              <div class="mb-3">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" class="form-control" id="stock" name="stock" value="<?= htmlspecialchars($book['stock']) ?>" required>
              </div>
              <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-control" id="category_id" name="category_id" required>
                  <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['category_id'] ?>" <?= $category['category_id'] == $book['category_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($category['name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?= htmlspecialchars($book['description']) ?></textarea>
              </div>
              <div class="mb-3">
                <label for="image" class="form-label">Book Image</label>
                <input type="file" class="form-control" id="image" name="image" accept="image/*">
                <small>Current Image: <?= htmlspecialchars($book['image']) ?></small>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" name="update_book" class="btn btn-primary">Update Book</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>


  <?php if (isset($_SESSION['success_message'])): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: '<?= $_SESSION['success_message'] ?>',
          confirmButtonText: 'OK'
        });
      });
    </script>
    <?php unset($_SESSION['success_message']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error_message'])): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: '<?= $_SESSION['error_message'] ?>',
          confirmButtonText: 'OK'
        });
      });
    </script>
    <?php unset($_SESSION['error_message']); ?>
  <?php endif; ?>


  <script>
    document.getElementById('addBookForm').addEventListener('submit', function(e) {
      e.preventDefault(); // Prevent form from submitting immediately

      // Show SweetAlert confirmation
      Swal.fire({
        title: 'Are you sure?',
        text: "Do you want to add this book?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Add it!',
        cancelButtonText: 'Cancel',
      }).then((result) => {
        if (result.isConfirmed) {
          // If confirmed, submit the form
          document.getElementById('addBookForm').submit();
        }
      });
    });
  </script>

  <script>
    document.querySelector("form[method='POST'][name='update_book']").addEventListener('submit', function(e) {
      e.preventDefault();

      Swal.fire({
        title: 'Update Book?',
        text: "Are you sure you want to save changes to this book?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Update it!'
      }).then((result) => {
        if (result.isConfirmed) {
          this.submit();
        }
      });
    });
  </script>

  <script>
    document.querySelectorAll('.delete-book').forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();

        const bookId = this.getAttribute('data-id');

        Swal.fire({
          title: 'Delete Book?',
          text: "Are you sure you want to delete this book? This action cannot be undone.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, Delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = `admin-dashboard.php?delete_book=${bookId}`;
          }
        });
      });
    });

    document.querySelectorAll('.delete-category').forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();

        const categoryId = this.getAttribute('data-id');

        Swal.fire({
          title: 'Delete Category?',
          text: "Are you sure you want to delete this category? This action cannot be undone.",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#3085d6',
          confirmButtonText: 'Yes, Delete it!'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = `admin-dashboard.php?delete_category=${categoryId}`;
          }
        });
      });
    });
  </script>

  <script>
    function confirmLogout() {
      // Display SweetAlert confirmation
      Swal.fire({
        title: 'Are you sure?',
        text: "Do you really want to log out?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'logout.php';
        }
      });
    }
  </script>


</body>

</html>