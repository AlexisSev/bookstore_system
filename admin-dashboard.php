<?php
  include_once('connection.php');
  session_start();

  // Ensure the user is logged in and is an admin
  if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: signup.php'); // Redirect to login page if not logged in or not an admin
    exit();
  }

  try {
    // Fetch books with a JOIN on categories, sorted by category_id
    $stmt = $pdo->prepare(" SELECT 
            b.book_id, 
            b.title, 
            b.author, 
            b.book_number, 
            b.price, 
            b.stock, 
            b.description, 
            b.image, 
            c.name AS category 
        FROM books b 
        LEFT JOIN categories c ON b.category_id = c.category_id 
        ORDER BY b.category_id ASC
    ");
    $stmt->execute();
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
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


  if (isset($_GET['edit_book'])): ?>
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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.lineicons.com/4.0/lineicons.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
      integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/line-awesome/dist/line-awesome/css/line-awesome.min.css">

    <style>
      @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');

      ::after,
      ::before {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      a {
        text-decoration: none;
      }

      li {
        list-style: none;
      }

      h1 {
        font-weight: 600;
        font-size: 1.5rem;
        color: #2d6a4f;
        /* Added a green color for the header */
      }

      body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4f9f4;
        /* Lighter greenish background for a fresh look */
      }

      .wrapper {
        display: flex;

      }

      #sidebar {
        width: 70px;
        min-width: 70px;
        height: 100vh;
        z-index: 1000;
        transition: all .25s ease-in-out;
        background-color: #503B31;
        display: flex;
        flex-direction: column;
        position: fixed;
      }

      #sidebar.expand {
        width: 260px;
        min-width: 260px;
      }

      #sidebar.expand+.main-content {
        margin-left: 260px;
        /* Adjust margin for expanded sidebar */
      }

        #Allbooks{
          margin-left: 70px;
        }

      #viewBooksContent {
        display: block;
        /* Initially show this if it’s the default section */
      }

      .main-content {
        transition: margin-left 0.3s ease-in-out;
        margin-left: 10px;
        padding: 10px;
        background-color: #F9F7F3;
        min-height: 100vh;
        width: 100%;
        overflow: hidden;
        transition: all 0.35s ease-in-out;
      }

      .toggle-btn {
        background-color: transparent;
        cursor: pointer;
        border: 0;
        padding: 1rem 1.5rem;
      }

      .toggle-btn i {
        font-size: 1.5rem;
        color: #FFF;
      }

      .sidebar-logo {
        margin: auto 0;
      }

      .sidebar-logo a {
        color: #fff;
        font-size: 1.15rem;
        font-weight: 600;
        text-transform: uppercase;
        /* Added uppercase for a more professional look */
      }

      #sidebar:not(.expand) .sidebar-logo,
      #sidebar:not(.expand) a.sidebar-link span {
        display: none;
      }

      .sidebar-nav {
        padding: 2rem 0;
        flex: 1 1 auto;
      }

      .sidebar-nav .sidebar-link {
        font-size: 16px;
        /* Adjust the font size of the sidebar link text */
      }

      a.sidebar-link {
        padding: .625rem 1.625rem;
        color: #fff;
        display: block;
        font-size: 0.9rem;
        white-space: nowrap;
        border-left: 3px solid transparent;
        transition: background-color 0.3s, color 0.3s;
      }

      .sidebar-link i {
        font-size: 1.1rem;
        margin-right: .75rem;
      }

      a.sidebar-link:hover {
        background-color: rgba(255, 255, 255, .075);
        border-left: 3px solid #3b7ddd;
        text-decoration: none;
        color: #3b7ddd;
        /* Changed link color on hover */
      }

      .sidebar-item {
        position: relative;
      }

      #sidebar:not(.expand) .sidebar-item .sidebar-dropdown {
        position: absolute;
        top: 0;
        left: 70px;
        background-color: #1a4d2e;
        /* Keep dropdown color consistent with sidebar */
        padding: 0;
        min-width: 15rem;
        display: none;
      }

      #sidebar:not(.expand) .sidebar-item:hover .has-dropdown+.sidebar-dropdown {
        display: block;
        max-height: 15em;
        width: 100%;
        opacity: 1;
      }

      #sidebar.expand .sidebar-link[data-bs-toggle="collapse"]::after {
        border: solid;
        border-width: 0 .075rem .075rem 0;
        content: "";
        display: inline-block;
        padding: 2px;
        position: absolute;
        right: 1.5rem;
        top: 1.4rem;
        transform: rotate(-135deg);
        transition: all .2s ease-out;
      }

      #sidebar.expand .sidebar-link[data-bs-toggle="collapse"].collapsed::after {
        transform: rotate(45deg);
        transition: all .2s ease-out;
      }


      /* Additional styles for buttons */
      .btn {
        color: #fff;
        padding: 10px 20px;
        border-radius: 5px;
        transition: background-color 0.3s;
      }

      .btn:hover {
        background-color: #1b4d32;
        /* Darker green on hover */
      }

      /* Enhancing table headers */
      .report-table th {
        background-color: #2d6a4f;
        color: white;
        font-weight: 600;
        padding: 10px;
      }

      .report-table td {
        padding: 10px;
      }

      .badge {
        position: absolute;
        top: 5px;
        right: 10px;
        background-color: #ff5722;
        /* A bright color for better visibility */
        color: white;
        font-size: 0.75rem;
        font-weight: bold;
        padding: 5px 8px;
        border-radius: 50%;
        display: inline-block;
        min-width: 20px;
        text-align: center;
      }

      .badge-warning {
        background-color: #ffc107;
        /* Yellow color for Collection Status */
      }

      .badge-danger {
        background-color: #dc3545;
        /* Red color for Reports & Feedbacks */
      }

      .profile-image {
        width: 100px;
        /* Fixed width */
        height: 100px;
        /* Fixed height */
        object-fit: cover;
        /* Ensures the image fits within the circle */
        border-radius: 50%;
        /* Make the image circular */
      }

      .no-image {
        width: 100px;
        /* Fixed width */
        height: 100px;
        /* Fixed height */
        border-radius: 50%;
        /* Circular placeholder */
        font-size: 1.2rem;
        color: #aaa;
      }

      .collector-box-container-parent {
        overflow-x: hidden;
      }

      .admin-box-container-parent {
        overflow-x: hidden;
      }

      .collector-box {
        position: relative;
        background: #ffffff;
        border: 1px solid #eaeaea;
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
      }

      .collector-box:hover {
        transform: translateY(-5px);
        box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1);
      }

      .status-label-top-left {
        font-size: 0.85rem;
        font-weight: 600;
      }

      .collector-image img {
        border: 3px solid #2d6a4f;
        background: #ffffff;
      }


      .card-body {
        padding: 15px;
      }

      .card-body-sched {
        padding: 15px;
        width: 100%;
        min-width: 1250px;
      }

      .card-title {
        font-size: 1.2rem;
      }

      .btn {
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 5px;
        transition: background-color 0.3s ease, color 0.3s ease;
      }

      .btn-success {
        background-color: #28a745;
        border: 0;
      }

      .btn-success:hover {
        background-color: #218838;
      }

      .btn-warning {
        background-color: #ffc107;
        border: 0;
        color: #000;
      }

      .btn-warning:hover {
        background-color: #e0a800;
      }

      .custom-form {
        max-width: 600px;
        margin: 0 auto;
        background-color: #f8f9fa;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      }

      .custom-form-content {
        padding: 15px;
      }

      .form-group {
        margin-bottom: 1.5rem;
      }

      .form-label {
        font-weight: bold;
      }

      .btn {
        font-size: 16px;
        padding: 12px;
        margin-top: 20px;
      }

      .text-primary {
        color: #007bff !important;
      }

      /* Custom Button Hover Effect */
      .btn:hover {
        background-color: #0056b3;
        border-color: #0056b3;
      }

      /* Custom Field Focus Effect */
      .form-control:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(38, 143, 255, 0.5);
      }
    </style>

  </head>

  <body>

    <div class="wrapper">
      <aside id="sidebar" class="sidebar">
      <div class="d-flex align-items-center">
  <!-- Sidebar Toggle Button -->
  <button class="toggle-btn" type="button" aria-label="Toggle Sidebar">
    <img src="./images/Bookify System Logo.webp" alt="Book Icon" width="24" height="24">
  </button>

  <!-- Sidebar Logo, Clicking on this will Show the View Books Section -->
  <div class="sidebar-logo ms-3">
    <a href="javascript:void(0);" onclick="showViewBooks()" class="fs-4 fw-bold text-decoration-none text-dark">BOOKIFY SYSTEM</a>
  </div>
</div>

        <ul class="sidebar-nav">

          <!-- Manage Books with Dropdown -->
          <li class="sidebar-item">
            <a href="#" onclick="toggleDropdown('bookDropdown', this)" class="sidebar-link collapsed" data-bs-toggle="collapse" aria-expanded="false">
              <i class="lni lni-book"></i> <!-- Book Icon -->
              <span>Manage Books</span>
            </a>
            <ul id="bookDropdown" class="sidebar-dropdown list-unstyled collapse">
              <!-- View Books with Icon -->
              <li class="sidebar-item">
                <a href="#" onclick="showContent('viewBooksContent', this)" class="sidebar-link">
                  <i class="fas fa-book-open"></i> <!-- View Books Icon -->
                  View Books
                </a>
              </li>
              <!-- Out of Stock Books with Icon -->
              <li class="sidebar-item">
                <a href="#" onclick="showContent('outOfStockBooks', this)" class="sidebar-link">
                  <i class="fas fa-box-open"></i> <!-- Out of Stock Books Icon -->
                  Out of Stock Books
                </a>
              </li>
            </ul>
          </li>


          <!-- View Sales with Dropdown -->
          <li class="sidebar-item">
            <a href="#" onclick="toggleDropdown('salesDropdown', this)" class="sidebar-link collapsed" data-bs-toggle="collapse" aria-expanded="false">
              <i class="lni lni-cart"></i> <!-- Sales Icon -->
              <span>View Sales</span>
            </a>
            <ul id="salesDropdown" class="sidebar-dropdown list-unstyled collapse">
              <!-- Daily Sales with Icon -->
              <li class="sidebar-item">
                <a href="#" onclick="showContent('dailySales', this)" class="sidebar-link">
                  <i class="fas fa-calendar-day"></i> <!-- Daily Sales Icon -->
                  Daily Sales
                </a>
              </li>
              <!-- Monthly Sales with Icon -->
              <li class="sidebar-item">
                <a href="#" onclick="showContent('monthlySales', this)" class="sidebar-link">
                  <i class="lni lni-calendar"></i> <!-- Monthly Sales Icon -->
                  Monthly Sales
                </a>
              </li>
              <!-- Yearly Sales with Icon -->
              <li class="sidebar-item">
                <a href="#" onclick="showContent('yearlySales', this)" class="sidebar-link">
                  <i class="fas fa-calendar-alt"></i> <!-- Yearly Sales Icon -->
                  Yearly Sales
                </a>
              </li>
            </ul>
          </li>



          <!-- Generate Sales Reports with Dropdown -->
          <li class="sidebar-item">
            <a href="#" onclick="toggleDropdown('reportDropdown', this)" class="sidebar-link collapsed" data-bs-toggle="collapse" aria-expanded="false">
              <i class="fas fa-file-alt"></i> <!-- Report Icon -->
              <span>Generate Sales Reports</span>
            </a>
            <ul id="reportDropdown" class="sidebar-dropdown list-unstyled collapse">
              <li class="sidebar-item">
                <a href="#" onclick="showContent('dailyReport', this)" class="sidebar-link">
                  <i class="lni lni-calendar"></i> <!-- Daily Report Icon -->
                  Generate Daily Report
                </a>
              </li>
              <li class="sidebar-item">
                <a href="#" onclick="showContent('monthlyReport', this)" class="sidebar-link">
                  <i class="fas fa-calendar-week"></i> <!-- Monthly Report Icon -->
                  Generate Monthly Report
                </a>
              </li>
              <li class="sidebar-item">
                <a href="#" onclick="showContent('yearlyReport', this)" class="sidebar-link">
                  <i class="fas fa-calendar-alt"></i> <!-- Yearly Report Icon -->
                  Generate Yearly Report
                </a>
              </li>
            </ul>
          </li>

          <!-- Manage Notifications -->
          <li class="sidebar-item">
            <a href="#" onclick="showContent('notifications', this)" class="sidebar-link">
              <i class="fas fa-bell"></i> <!-- Notification Icon -->
              <span>Notifications</span>
            </a>
          </li>

        </ul>


        <div class="sidebar-footer">
          <a href="javascript:void(0)" id="logoutBtn" class="sidebar-link">
            <i class="lni lni-exit"></i>
            <span>Logout</span>
          </a>
        </div>

      </aside>

      <!-- Main Content -->
      <div class="main-content" id="Allbooks">
        <div id="viewBooks" class="content-section">
          <h2>All Books</h2>
          <div class="table-responsive" style="width: 100%; overflow-x: auto;">
            <table id="booksTable" class="table table-bordered table-striped" style="width: 100%;">
              <thead class="table-dark">
                <tr>
                  <th>Book ID</th>
                  <th>Title</th>
                  <th>Author</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Category</th>
                  <th>Description</th>
                  <th>Image</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="booksList">
                <?php foreach ($books as $book): ?>
                  <tr>
                    <td><?= htmlspecialchars($book['book_id']); ?></td>
                    <td><?= htmlspecialchars($book['title']); ?></td>
                    <td><?= htmlspecialchars($book['author']); ?></td>
                    <td><?= htmlspecialchars($book['price']); ?></td>
                    <td><?= htmlspecialchars($book['stock']); ?></td>
                    <td><?= htmlspecialchars($book['category']); ?></td>
                    <td><?= htmlspecialchars($book['description']); ?></td>
                    <td>
                      <img src="./images/books/<?= htmlspecialchars($book['image']); ?>" alt="<?= htmlspecialchars($book['title']); ?>" style="width: 50px; height: auto;">
                    </td>
                    <td>
                      <a href="?edit_book=<?= $book['book_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                      <a href="?delete_book=<?= $book['book_id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Main Content for View Books -->
        <div id="viewBooksContent" class="main-content" style="display: none;">
          <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <!-- PHP loop to fetch and display books will go here -->
            <?php foreach ($books as $book): ?>
              <div class="col">
                <div class="card">
                  <img src="./images/books/<?= htmlspecialchars($book['image']); ?>" alt="<?= htmlspecialchars($book['title']); ?>" class="card-img-top" style="height: 250px; object-fit: cover;">
                  <div class="card-body">
                    <h5 class="card-title"><?= htmlspecialchars($book['title']); ?></h5>
                    <p class="card-text"><strong>Author:</strong> <?= htmlspecialchars($book['author']); ?></p>
                    <p class="card-text"><strong>Price:</strong> <?= htmlspecialchars($book['price']); ?></p>
                    <p class="card-text"><strong>Stock:</strong> <?= htmlspecialchars($book['stock']); ?></p>
                    <p class="card-text"><strong>Category:</strong> <?= htmlspecialchars($book['category']); ?></p>
                    <p class="card-text"><strong>Description:</strong> <?= htmlspecialchars($book['description']); ?></p>
                  </div>
                  <div class="card-footer text-center">
                    <a href="?edit_book=<?= $book['book_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                    <a href="?delete_book=<?= $book['book_id']; ?>" class="btn btn-sm btn-danger">Delete</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>



      </div>
    </div>




    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
      integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
      crossorigin="anonymous"></script>
    <script src="script.js"></script>


    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet-routing-machine/3.2.12/leaflet-routing-machine.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>

  </body>

  </html>

<script>
  function showViewBooks() {
  // Get the View Books section
  var viewBooksSection = document.getElementById('viewBooks');
  
  // Toggle visibility of the View Books section
  if (viewBooksSection.style.display === 'none' || viewBooksSection.style.display === '') {
    viewBooksSection.style.display = 'block';  // Show the content
  } else {
    viewBooksSection.style.display = 'none';  // Hide the content
  }
}

</script>

  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Fetch books from the PHP script
      fetch('fetch_books.php')
        .then(response => response.json())
        .then(books => {
          const booksList = document.getElementById('booksList');
          booksList.innerHTML = ''; // Clear any existing content

          books.forEach(book => {
            const row = document.createElement('tr');
            row.innerHTML = `
          <td>${book.book_id}</td>
          <td>${book.title}</td>
          <td>${book.author}</td>
          <td>${book.price}</td>
          <td>${book.stock}</td>
          <td>${book.category || 'N/A'}</td>
          <td>${book.description}</td>
          <td><img src="${book.image}" alt="${book.title}" style="width:50px;height:auto;"></td>
          <td>
            <button class="edit-book" data-id="${book.book_id}">Edit</button>
            <button class="delete-book" data-id="${book.book_id}">Delete</button>
          </td>
        `;
            booksList.appendChild(row);
          });
        })
        .catch(error => console.error('Error fetching books:', error));
    });
  </script>


  <script>
    // Function to toggle content visibility
    function showContent(contentId, link) {
      const allContents = document.querySelectorAll('.main-content > div');
      allContents.forEach(content => content.style.display = 'none'); // Hide all content

      const content = document.getElementById(contentId);
      content.style.display = 'block'; // Show the selected content

      if (contentId === 'viewBooks') {
        fetchBooks(); // Fetch books when the 'View Books' content is shown
      }
    }

    // Toggle dropdown visibility
    function toggleDropdown(dropdownId, link) {
      const dropdown = document.getElementById(dropdownId);
      dropdown.classList.toggle('collapse');
      const expanded = link.getAttribute('aria-expanded') === 'true' ? 'false' : 'true';
      link.setAttribute('aria-expanded', expanded);
    }
  </script>

  <script>
    const hamBurger = document.querySelector(".toggle-btn");

    hamBurger.addEventListener("click", function() {
      document.querySelector("#sidebar").classList.toggle("expand");
    });
  </script>

  <script>
    // Check if the success message is set for Admin or Collector
    <?php if (isset($_SESSION['success_message'])): ?>
      Swal.fire({
        title: 'Success!',
        text: '<?php echo $_SESSION['success_message']; ?>',
        icon: 'success',
        confirmButtonText: 'OK',
        showConfirmButton: true,
        position: 'center', // Ensures it's centered on the screen
        willClose: () => {
          // Optional: Redirect user after clicking OK (can be done after success)
          // window.location.href = "admin_dashboard.php"; // Example redirect
        }
      });
      <?php unset($_SESSION['success_message']); ?> // Clear the success message after showing the alert
    <?php elseif (isset($_SESSION['error_message'])): ?>
      Swal.fire({
        title: 'Error!',
        text: '<?php echo $_SESSION['error_message']; ?>',
        icon: 'error',
        confirmButtonText: 'OK',
        showConfirmButton: true,
        position: 'center',
        willClose: () => {
          // Optional: Handle action after error
        }
      });
      <?php unset($_SESSION['error_message']); ?> // Clear the error message after showing the alert
    <?php endif; ?>
  </script>

  <script>
    // Toggle dropdown visibility
    function toggleDropdown(dropdownId, link) {
      var dropdown = document.getElementById(dropdownId);
      var isCollapsed = dropdown.classList.contains('collapse');

      // Collapse all dropdowns before opening the new one
      var allDropdowns = document.querySelectorAll('.sidebar-dropdown');
      allDropdowns.forEach(function(d) {
        d.classList.add('collapse');
      });

      // Toggle current dropdown
      if (isCollapsed) {
        dropdown.classList.remove('collapse');
      } else {
        dropdown.classList.add('collapse');
      }
    }
  </script>

  <script>
    // Function to show and hide content based on clicked sidebar button
    function showContent(section, element) {
      // Hide all sections
      var sections = document.querySelectorAll('.content-section');
      sections.forEach(function(section) {
        section.style.display = 'none';
      });

      // Highlight the active sidebar link
      var links = document.querySelectorAll('.nav a');
      links.forEach(function(link) {
        link.classList.remove('active');
      });
      element.classList.add('active');

      // Show the selected section
      var selectedSection = document.getElementById(section);
      if (selectedSection) {
        selectedSection.style.display = 'block';
      }
    }

    // Function to handle logout confirmation (replace with actual logout functionality)
    function confirmLogout() {
      var logoutConfirmed = confirm("Are you sure you want to logout?");
      if (logoutConfirmed) {
        // Handle actual logout (e.g., redirect to a logout page or clear session)
        window.location.href = "logout.php"; // Replace with actual logout URL
      }
    }
  </script>

  <script>
    // Add an event listener to the logout button
    document.getElementById('logoutBtn').addEventListener('click', function(e) {
      e.preventDefault(); // Prevent the default action of the link (to not navigate immediately)

      // Show SweetAlert confirmation dialog
      Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out.",
        icon: 'warning',
        showCancelButton: true, // Show cancel button
        confirmButtonColor: '#d33', // Confirm button color
        cancelButtonColor: '#3085d6', // Cancel button color
        confirmButtonText: 'Yes, log me out!', // Text for confirm button
        cancelButtonText: 'Cancel' // Text for cancel button
      }).then((result) => {
        // If the user confirms the logout action
        if (result.isConfirmed) {
          // Redirect to the logout page
          window.location.href = 'logout.php'; // Adjust the logout URL accordingly
        }
      });
    });
  </script>