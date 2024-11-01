<?php
// Database connection details
$host = 'localhost';
$user = 'hnxhybwqsy';
$password = 'p3YCzqWxjP';
$database = 'hnxhybwqsy';
$logFile = '/hnxhybwqsy/public_html/db_maintenance.log';

// Log function
function log_message($message, $logFile) {
    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "$date - $message\n", FILE_APPEND);
}

// Create MySQL connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    log_message("Connection failed: " . $conn->connect_error, $logFile);
    exit(1);
}

// SQL query to update `purchase_orders` for eligible records in `sales_orders`
$sql = "
    UPDATE purchase_orders AS po
    INNER JOIN sales_orders AS so ON po.id = so.id_purchase_order
    SET po.stock_status = 'potential'
    WHERE so.next_step_status_sales = 'Hired'
      AND CURRENT_DATE() >= DATE(so.contract_start_date) + INTERVAL so.term_months MONTH - INTERVAL 2 MONTH
      AND CURRENT_DATE() <= DATE(so.contract_start_date) + INTERVAL so.term_months MONTH
";

// Execute the update query and log the outcome
if ($conn->query($sql) === TRUE) {
    $affectedRows = $conn->affected_rows;
    log_message("Updated $affectedRows rows in `purchase_orders` to 'potential'.", $logFile);
    echo "Updated $affectedRows rows in `purchase_orders` to 'potential'.\n";
} else {
    log_message("Error updating records: " . $conn->error, $logFile);
    echo "Error updating records: " . $conn->error . "\n";
}

// Close connection
$conn->close();

log_message("Database update process completed.", $logFile);
?>
