<?php
// Function to query Safaricom STK Push status
function querySTKPushStatus($checkoutRequestID) {
    // Safaricom STK Push Query API endpoint
    $url = 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';

    // Your Business ShortCode and PassKey
    $businessShortCode = "174379";
    $passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";

    // Get current timestamp in the format 'YmdHis' (e.g., 20160216165627)
    $timestamp = date("YmdHis");

    // Generate the password as Base64Encode(BusinessShortCode + PassKey + Timestamp)
    $password = base64_encode($businessShortCode . $passkey . $timestamp);

    // Create the request payload
    $data = array(
        'BusinessShortCode' => $businessShortCode,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'CheckoutRequestID' => $checkoutRequestID
    );

    // Convert the payload to JSON
    $data_string = json_encode($data);

    // Set up the cURL request
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: ' . 'Bearer ' . getAccessToken(), // Replace with function or logic to get OAuth token
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

    // Execute the cURL request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        return 'Error: ' . curl_error($ch);
    }

    // Close the cURL session
    curl_close($ch);

    // Return the response
    return $response;
}

// Dummy function to simulate getting an access token (replace with actual logic)
function getAccessToken() {
	# access token
	$consumerKey = 'WBO40j0jMAthf4Go9hgjAPLr8BtSlnSd';
	$consumerSecret = 'hGF79OCNOSeIPuaQ';
    $access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    // Set up the cURL request to get the access token
    $curl = curl_init($access_token_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json; charset=utf8']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
    
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($status != 200) {
        die("Error fetching access token: HTTP status $status");
    }
    
    // Decode the response and extract the access token
    $result = json_decode($result);
    $access_token = $result->access_token;
    
    curl_close($curl);
    
    return $access_token;
}

// Example usage:
$checkoutRequestID = "ws_CO_260520211133524545";
$response = querySTKPushStatus($checkoutRequestID);
echo $response;
?>
