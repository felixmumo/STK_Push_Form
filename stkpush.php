<?php
// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve user inputs from form
    $phone = $_POST['phone'] ?? '';
    $amount = $_POST['amount'] ?? '';

    // Input validation
    function validateInput($phone, $amount) {
        if (!preg_match('/^(?:\+254|254|07)\d{8}$/', $phone)) {
            return "Invalid phone number. Use format 07XXXXXXXX or +2547XXXXXXXX.";
        }
        if (!is_numeric($amount) || $amount <= 0) {
            return "Amount must be a positive number.";
        }
        return true;
    }

    // M-PESA STK Push function
    function mpesa($phone, $amount, $ordernum) {
        # Callback url
        define('CALLBACK_URL', 'https://www.greenlixtechnologies.co.ke/darajaapp/callback_url.php?orderid=');

        # Access token credentials
        $consumerKey = '7ztrAodZNaMn9M2hZx7M73aNWT5QN2FoT5usqhIETdRE54ph'; 
        $consumerSecret = '47rpf9f2oxZcdAGGQCcTlfXGNWglyLyjAT3AGCyutBmpL5LkrGVqO6xOE1zereM1';
        $BusinessShortCode = '174379';
        $Passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';

        # Format phone number
        $phone = preg_replace('/^0/', '254', str_replace("+", "", $phone));
        $PartyA = $phone;
        $PartyB = '174379';
        $TransactionDesc = 'Pay Order';
        $Timestamp = date('YmdHis');
        $Password = base64_encode($BusinessShortCode . $Passkey . $Timestamp);

        # M-PESA endpoint URLs
        $access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        $initiate_url = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        // Request access token
        $headers = ['Content-Type:application/json; charset=utf8'];
        $curl = curl_init($access_token_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
        $result = json_decode(curl_exec($curl));
        curl_close($curl);

        if (!isset($result->access_token)) {
            return "Failed to fetch access token.";
        }
        $access_token = $result->access_token;

        # STK Push transaction data
        $stkheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $access_token];
        $curl_post_data = [
            'BusinessShortCode' => $BusinessShortCode,
            'Password' => $Password,
            'Timestamp' => $Timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $PartyA,
            'PartyB' => $PartyB,
            'PhoneNumber' => $PartyA,
            'CallBackURL' => CALLBACK_URL . $ordernum,
            'AccountReference' => $ordernum,
            'TransactionDesc' => $TransactionDesc
        ];

        # Initiate transaction
        $curl = curl_init($initiate_url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $stkheader);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
        $curl_response = curl_exec($curl);
        curl_close($curl);

        $response_data = json_decode($curl_response, true);
        if (isset($response_data['ResponseCode']) && $response_data['ResponseCode'] == "0") {
            return "STK Push successfully initiated.";
        } else {
            return "Error: " . ($response_data['errorMessage'] ?? 'Unknown error.');
        }
    }

    // Validate inputs
    $validationResult = validateInput($phone, $amount);
    if ($validationResult === true) {
        $invoice = date('YmdHis');
        $response = mpesa($phone, $amount, $invoice);
    } else {
        $response = $validationResult;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STK Push Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .form-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        label {
            display: block;
            margin: 0.5rem 0 0.2rem;
            text-align: left;
            color: #555;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .response {
            margin-top: 1rem;
            color: #28a745;
        }
        .error {
            margin-top: 1rem;
            color: #d9534f;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Enter Payment Details</h2>
        <form method="POST" action="">
            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" placeholder="07XXXXXXXX or +2547XXXXXXXX" required>

            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" placeholder="Enter Amount" min="1" required>

            <button type="submit">Submit</button>
        </form>

        <?php if (isset($response)): ?>
            <div class="<?php echo (strpos($response, 'Error') === false) ? 'response' : 'error'; ?>">
                <p><?php echo htmlspecialchars($response); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
