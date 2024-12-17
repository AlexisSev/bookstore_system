<?php
include_once('connection.php');
session_start();  // Start the session to access user data

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: sign-up.php');  // Redirect to login page if not logged in
  exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

$books = $pdo->query("SELECT * FROM books")->fetchAll(PDO::FETCH_ASSOC);

// If "book_id" is set in the URL, show the book details
$book_id = isset($_GET['book_id']) ? $_GET['book_id'] : null;

$userCheckStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
$userCheckStmt->execute(['user_id' => $user_id]);

if ($userCheckStmt->rowCount() > 0) {
  // Proceed with adding to the cart
  if (isset($_POST['add_to_cart'])) {
      $book_id = $_POST['book_id'];

      // Check if the book already exists in the user's cart
      $checkCartStmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = :user_id AND book_id = :book_id");
      $checkCartStmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);

      if ($checkCartStmt->rowCount() > 0) {
          // If the book is already in the cart, update the quantity
          $updateCartStmt = $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE user_id = :user_id AND book_id = :book_id");
          $updateCartStmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
      } else {
          // If the book is not in the cart, insert it
          $insertCartStmt = $pdo->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (:user_id, :book_id, 1)");
          $insertCartStmt->execute(['user_id' => $user_id, 'book_id' => $book_id]);
      }

      // Decrease the stock quantity in the books table by 1
      $updateBookStmt = $pdo->prepare("UPDATE books SET stock = stock - 1 WHERE book_id = :book_id AND stock > 0");
      $updateBookStmt->execute(['book_id' => $book_id]);

      // Set the session message indicating the product was successfully added
      $_SESSION['cart_success'] = 'The book has been added to your cart.';

      // Redirect to refresh the page and prevent resubmitting the form
      header("Location: " . $_SERVER['PHP_SELF'] . "?view_books=true");
      exit();
  }
} else {
  echo "<div class='alert alert-danger'>Error: User does not exist. Please log in again.</div>";
}

// View Cart function
function viewCart($pdo, $user_id)
{
  $stmt = $pdo->prepare("SELECT books.title, books.price, cart.quantity FROM cart JOIN books ON cart.book_id = books.book_id WHERE cart.user_id = :user_id");
  $stmt->execute(['user_id' => $user_id]);

  $total = 0;
  $cartContent = '';
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $subtotal = $row['price'] * $row['quantity'];
    $total += $subtotal;
    $cartContent .= "<div class='mb-3'>";
    $cartContent .= "<h5>" . htmlspecialchars($row['title']) . "</h5>";
    $cartContent .= "<p>Price: ₱" . htmlspecialchars($row['price']) . "</p>";
    $cartContent .= "<p>Quantity: " . htmlspecialchars($row['quantity']) . "</p>";
    $cartContent .= "<p>Subtotal: ₱" . htmlspecialchars($subtotal) . "</p>";
    $cartContent .= "</div><hr>";
  }
  $cartContent .= "<h4>Total: ₱" . htmlspecialchars($total) . "</h4>";

  return $cartContent;
}

$stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$cartCount = $stmt->fetch(PDO::FETCH_ASSOC)['total_items'] ?? 0;

// Check if "show_home" is set in the query string to show the homepage sections
$show_home = isset($_GET['show_home']) && $_GET['show_home'] == 'true';
// If "view_cart" parameter is set, show the cart content
$show_cart = isset($_GET['view_cart']) && $_GET['view_cart'] == 'true';
// Check if the 'view_books' query parameter is set
$show_books = isset($_GET['view_books']) && $_GET['view_books'] == 'true';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Books and Cart</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body {
      background: #f4f7fa;
      font-family: 'Arial', sans-serif;
      color: #333;
    }

    .container {
      margin-top: 50px;
    }

    .card-img-top {
      height: 300px;
      object-fit: cover;
    }

    .btn-primary {
      background-color: #3498db;
      border-color: #3498db;
    }

    .btn-primary:hover {
      background-color: #2980b9;
      border-color: #2980b9;
    }

    .cart-summary {
      background-color: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      margin-top: 30px;
    }

    .navbar-custom {
      background-color: #3498db;
    }

    .hero-section {
      background: #2980b9;
      /* Hero section background color */
      color: white;
      padding: 100px 0;
    }

    .hero-title {
      font-size: 3.5rem;
      font-weight: bold;
      margin-bottom: 20px;
    }

    .hero-description {
      font-size: 1.25rem;
      margin-bottom: 30px;
    }

    .hero-btn {
      background-color: #f39c12;
      border-color: #f39c12;
      padding: 12px 30px;
      font-size: 1.1rem;
      border-radius: 5px;
    }

    .hero-btn:hover {
      background-color: #e67e22;
      border-color: #e67e22;
      text-decoration: none;
    }

    .categories-section {
      background-color: #f7f7f7;
      /* Light background for categories */
      padding: 60px 0;
    }

    .categories-title {
      font-size: 2.5rem;
      color: #2c3e50;
      font-weight: 600;
    }

    .category-card {
      background-color: #fff;
      padding: 30px;
      border-radius: 10px;
      text-align: center;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .category-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    .category-card h4 {
      font-size: 1.5rem;
      margin-bottom: 15px;
    }

    .category-card p {
      font-size: 1rem;
      margin-bottom: 20px;
    }

    .category-card .btn-outline-primary {
      font-size: 1rem;
      padding: 8px 20px;
      border-radius: 5px;
    }

    .category-card .btn-outline-primary:hover {
      background-color: #2980b9;
      color: white;
      border-color: #2980b9;
    }

    .cart-counter {
      background-color: red;
      color: white;
      padding: 5px 10px;
      border-radius: 50%;
      font-weight: bold;
      font-size: 1rem;
      position: relative;
      top: -5px;
      left: 5px;
    }


    @media (max-width: 767px) {
      .hero-title {
        font-size: 2.5rem;
      }

      .hero-description {
        font-size: 1.1rem;
      }

      .category-card {
        padding: 20px;
      }

      .categories-title {
        font-size: 2rem;
      }
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-custom navbar-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Bookstore</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link" href="?show_home=true">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="?view_books=true">Books</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="?view_cart=true">Cart <span class="cart-counter"><?= $cartCount ?></span></a>
          </li>

          <!-- Profile Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <?php echo htmlspecialchars($_SESSION['name']); ?>
            </a>
            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
              <li><a class="dropdown-item" href="profile.php">Profile</a></li>
              <li><a class="dropdown-item" href="#" onclick="confirmLogout()">Logout</a></li>
            </ul>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Hero Section (Initially Hidden) -->
  <?php if ($show_home): ?>
    <div class="hero-section">
      <div class="container text-center text-white">
        <h1 class="hero-title">Welcome to Our Bookstore</h1>
        <p class="hero-description">Your one-stop destination for books across various genres</p>
        <a href="?view_books=true" class="btn btn-light btn-lg hero-btn">Browse Books</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- Books Section -->
  <?php if ($show_books): ?>
    <div class="container">
      <div class="row">
        <?php foreach ($books as $book) { ?>
          <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-light">
            <img src="./images/books/<?= $book['image'] ?>" class="card-img-top" style="object-fit: fill;" alt="Book Image">
                <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
                <p class="card-text">Author: <?= htmlspecialchars($book['author']) ?></p>
                <p class="card-text">Price: ₱<?= htmlspecialchars($book['price']) ?></p>
                <p class="card-text">Stock: <?= htmlspecialchars($book['stock']) ?></p>
                <form method="post">
                  <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
                  <button type="submit" name="add_to_cart" class="btn btn-primary w-100">Add to Cart</button>
                </form>
              </div>
            </div>  
          </div>
        <?php } ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($show_cart): ?>
    <div class="cart-summary">
      <h2>Your Cart</h2>
      <p>Total Items: <?= $cartCount ?> </p> <!-- Display cart count here -->
      <?php echo viewCart($pdo, $user_id); ?>
      <form method="POST" action="">
        <button type="submit" name="checkout" class="btn btn-success w-100 mt-3">Checkout</button>
      </form>
    </div>
  <?php endif; ?>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Display SweetAlert2 if a product is added -->
<?php if (isset($_SESSION['cart_success'])): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Product Added!',
            text: '<?= $_SESSION['cart_success'] ?>',
            showConfirmButton: false,
            timer: 2000,
            position: 'center'
        });
    </script>
    <?php unset($_SESSION['cart_success']); ?>
<?php endif; ?>



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