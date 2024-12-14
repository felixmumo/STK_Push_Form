<?php
// Define an array of allowed IP addresses
$allowedIPs = array(
    '196.201.214.200',
    '196.201.214.206',
    '196.201.213.114',
    '196.201.214.207',
    '196.201.214.208',
    '196.201.213.44',
    '196.201.212.127',
    '196.201.212.138',
    '196.201.212.129',
    '196.201.212.136',
    '196.201.212.74',
    '196.201.212.69',
);

// Get the client's IP address securely
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';

// Check if the client's IP is in the allowed list
if (!in_array($clientIP, $allowedIPs)) {
    error_log('Unauthorized access attempt from IP: ' . $clientIP);
    http_response_code(403); // Send a 403 Forbidden response
    exit('Access denied.');
}

// Get the invoice number from the query string
$invoice = filter_input(INPUT_GET, 'orderid', FILTER_SANITIZE_STRING);

if (empty($invoice)) {
    error_log('Missing or invalid invoice number.');
    exit('Invoice number is required.');
}

// Read the raw POST data from the request
$callbackJSONData = file_get_contents('php://input');

// Log the raw data for debugging
error_log('Raw callback data: ' . $callbackJSONData);

// Decode the JSON data into a PHP array
$callbackData = json_decode($callbackJSONData, true);

// Check if the JSON decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log('Invalid JSON input: ' . json_last_error_msg());
    exit('Invalid JSON format.');
}

// Check if the necessary callback structure exists
if (!isset($callbackData['Body']['stkCallback'])) {
    error_log('Unexpected callback data structure.');
    exit('Invalid callback data.');
}

$stkCallback = $callbackData['Body']['stkCallback'];

// Extract the necessary values from the callback data
$resultCode = $stkCallback['ResultCode'] ?? null;
$amount = null;
$mpesaCode = null;

// Extract Amount and MpesaReceiptNumber from CallbackMetadata
if (isset($stkCallback['CallbackMetadata']['Item'])) {
    foreach ($stkCallback['CallbackMetadata']['Item'] as $item) {
        if ($item['Name'] === 'Amount') {
            $amount = $item['Value'];
        }
        if ($item['Name'] === 'MpesaReceiptNumber') {
            $mpesaCode = $item['Value'];
        }
    }
}

// Validate extracted data
if ($resultCode !== 0 || empty($amount) || empty($mpesaCode)) {
    error_log("Transaction failed or missing data for invoice: " . $invoice);
    exit('Invalid or incomplete transaction data.');
}

// Database credentials
$db_host = 'localhost';
$db_name = 'greenlix_payment';
$db_username = 'greenlix_felix';
$db_password = '~-FGm.Z^7ed!';

try {
    // Establish a database connection using PDO
    $conn = new PDO("mysql:host=$db_host;port=3306;dbname=$db_name", $db_username, $db_password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare the insert query
    $sql = "INSERT INTO payment (mpesacode, status, invoice_num) VALUES (:mpesa_code, 'paid', :invoice_number)";
    $stmt = $conn->prepare($sql);

    // Bind the parameters
    $stmt->bindParam(':mpesa_code', $mpesaCode);
    $stmt->bindParam(':invoice_number', $invoice);

    // Execute the query
    if ($stmt->execute()) {
        error_log("Payment recorded successfully for invoice: " . $invoice);
        echo "Payment recorded successfully.";
    } else {
        error_log("Error recording payment for invoice: " . $invoice);
        echo "Error recording payment.";
    }

    // Close the connection
    $conn = null;
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    exit("A database error occurred.");
}
?>
