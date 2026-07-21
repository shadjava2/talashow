<?php

// Debug script: test TLS connectivity to PayPal from PHP/cURL (no real credentials needed).
// Usage: php tools/paypal_tls_test.php

$info = curl_version();
echo "curl=" . ($info['version'] ?? '') . PHP_EOL;
echo "ssl=" . ($info['ssl_version'] ?? '') . PHP_EOL;

$ch = curl_init('https://api-m.paypal.com/v1/oauth2/token');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
    CURLOPT_TIMEOUT => 20,
    CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    // dummy basic auth; we only care about TLS handshake
    CURLOPT_USERPWD => 'x:y',
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
    ],
]);

$out = curl_exec($ch);
$errno = curl_errno($ch);
$err = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
curl_close($ch);

echo "http=" . (int) $code . PHP_EOL;
if ($errno) {
    echo "errno=" . (int) $errno . PHP_EOL;
    echo "err=" . $err . PHP_EOL;
} else {
    echo "body=" . substr((string) $out, 0, 200) . PHP_EOL;
}

