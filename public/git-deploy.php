<?php
$secret = 'VpsWadi$hukr4n2025!';

$input = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!$signature) { http_response_code(400); exit('No signature'); }

$hash = 'sha256=' . hash_hmac('sha256', $input, $secret);
if (!hash_equals($hash, $signature)) { http_response_code(403); exit('Invalid signature'); }

$output = shell_exec('bash /home/wadishukran.com/public_html/deploy.sh 2>&1');
echo "<pre>$output</pre>";
