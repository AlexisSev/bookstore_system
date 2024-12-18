<?php
include_once('connection.php');
session_start();  // Start the session to access user data

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-up.php');  // Redirect to login page if not logged in
    exit();
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

// Function to fetch cart items
function getCartItems($pdo, $user_id)
{
    try {
        $stmt = $pdo->prepare("SELECT b.title, b.price, b.image, c.quantity
                               FROM cart c
                               JOIN books b ON c.book_id = b.book_id
                               WHERE c.user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC); // Return all items as an associative array
    } catch (PDOException $e) {
        echo "Error fetching cart items: " . $e->getMessage();
        return [];
    }
}

// Retrieve cart items using the function
$cartItems = getCartItems($pdo, $user_id);

$successMessage = $_SESSION['checkout_success'] ?? null;
$insufficientStockItems = $_SESSION['insufficient_stock_items'] ?? [];  // Items with insufficient stock
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .receipt-table th, .receipt-table td {
            text-align: left;
        }
        .order-total {
            font-size: 1.5em;
            font-weight: bold;
            margin-top: 20px;
        }
        .continue-btn {
            margin-top: 20px;
            text-align: center;
        }
        .receipt-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.9em;
            color: #888;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Success Message -->
            <?php if ($successMessage): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
                <?php unset($_SESSION['checkout_success']); ?>
            <?php endif; ?>

            <!-- Order Confirmation Header -->
            <div class="receipt-header text-center">
                <h3>Order Confirmation</h3>
                <p>Thank you for your purchase! Here is your order summary:</p>
            </div>

            <!-- Order Summary -->
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($cartItems)): ?>
                        <table class="table table-striped receipt-table">
                            <thead>
                                <tr>
                                    <th>Book Title</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalOrder = 0;
                                foreach ($cartItems as $item):
                                    $itemTotal = $item['quantity'] * $item['price'];
                                    $totalOrder += $itemTotal;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['title']); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>₱<?php echo number_format($itemTotal, 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <p class="order-total text-center">
                            <strong>Total Amount: ₱<?php echo number_format($totalOrder, 2); ?></strong>
                        </p>
                    <?php else: ?>
                        <p class="text-center">Your cart is empty. Please add items to your cart.</p>
                    <?php endif; ?>

                    <!-- Insufficient Stock -->
                    <?php if (!empty($insufficientStockItems)): ?>
                        <div class="alert alert-warning">
                            The following items were not processed due to insufficient stock: 
                            <strong><?php echo implode(', ', $insufficientStockItems); ?></strong>
                        </div>
                    <?php endif; ?>

                    <!-- Place Order Button -->
                    <form action="place-order.php" method="POST">
                        <button type="submit" class="btn btn-success w-100">Place Order</button>
                    </form>

                    <!-- Continue Shopping Button -->
                    <div class="continue-btn">
                        <a href="user-homepage.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>

            <!-- Footer Information -->
            <div class="receipt-footer">
                <p>We hope you enjoy your purchase! If you have any questions, please contact our support team.</p>
                <p>Thank you for choosing our store!</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
