<?php
include_once('connection.php');
session_start();

// Fetch user information from the database
$user_id = $_SESSION['user_id'];
$query = $pdo->prepare("SELECT name, email, profile FROM users WHERE user_id = ?");
$query->execute([$user_id]);
$user = $query->fetch(PDO::FETCH_ASSOC);

// Handle form submission for updating user info
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = htmlspecialchars($_POST['name']);
  $email = htmlspecialchars($_POST['email']);
  $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

  // Handle profile picture upload
  $profile = $user['profile']; // Default to current picture
  if (!empty($_FILES['profile']['name'])) {
    $target_dir = "uploads/profiles/";
    $profile = $target_dir . basename($_FILES["profile"]["name"]);
    if (move_uploaded_file($_FILES["profile"]["tmp_name"], $profile)) {
      $profile = htmlspecialchars($profile); // Sanitize the path
    } else {
      echo "Error uploading file.";
    }
  }

  // Update user information in the database
  $update_query = "UPDATE users SET name = ?, email = ?, profile = ?";
  $params = [$name, $email, $profile];

  // Add password to the query if provided
  if ($password) {
    $update_query .= ", password = ?";
    $params[] = $password;
  }

  $update_query .= " WHERE user_id = ?";
  $params[] = $user_id;

  $stmt = $pdo->prepare($update_query);
  if ($stmt->execute($params)) {
    $_SESSION['success'] = "Profile updated successfully.";
    header('Location: profile.php');
    exit;
  } else {
    echo "Error updating profile.";
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['profile'])) {
  $upload_dir = 'uploads/profile/';
  $target_file = $upload_dir . basename($_FILES['profile']['name']);
  $upload_success = false;

  // Check if the file is a valid image
  $check = getimagesize($_FILES['profile']['tmp_name']);
  if ($check !== false) {
      if (move_uploaded_file($_FILES['profile']['tmp_name'], $target_file)) {
          $uploaded_image_path = $target_file; // Path of the uploaded image
          $upload_success = true;

          // Update database
          $query = "UPDATE users SET profile = ? WHERE user_id = ?";
          $stmt = $conn->prepare($query);
          $stmt->bind_param("si", $uploaded_image_path, $_SESSION['user_id']);
          if ($stmt->execute()) {
              $_SESSION['profile'] = $uploaded_image_path; // Update session
              echo "Profile image updated successfully!";
          } else {
              echo "Failed to update profile image in database.";
          }
          $stmt->close();
      } else {
          echo "Error uploading file.";
      }
  } else {
      echo "File is not an image.";
  }
}




?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <h2 class="mb-4">Update Profile</h2>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Back Button -->
        <a href="user-homepage.php" class="btn btn-secondary mb-3">Back to Dashboard</a>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password (leave blank to keep current password)</label>
                <input type="password" class="form-control" id="password" name="password">
            </div>
            <div class="mb-3">
                <label for="profile_picture" class="form-label">Profile Picture</label>
                <input type="file" class="form-control" id="profile" name="profile" accept="image/*">
                <?php if (!empty($user['profile_picture'])): ?>
                    <div class="mt-2">
                        <img src="<?= htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                <?php endif; ?>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>
</body>

</html>
