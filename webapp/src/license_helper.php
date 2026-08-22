<?php
define('LICENSE_SECRET', 'OpsBuddy_Secret_2026_SecureKey!');

function get_license_info() {
    $lic_path = '/var/www/conf/license.json';
    if (!file_exists($lic_path)) return ['valid' => false, 'reason' => 'No license found'];
    
    $data = @json_decode(file_get_contents($lic_path), true);
    if (!$data || !isset($data['signature'])) return ['valid' => false, 'reason' => 'Invalid license format'];
    
    $payload = $data['client_name'] . '|' . $data['client_id'] . '|' . $data['expiration_date'];
    $expected_sig = hash_hmac('sha256', $payload, LICENSE_SECRET);
    
    if (!hash_equals($expected_sig, $data['signature'])) {
        return ['valid' => false, 'reason' => 'License signature is invalid or tampered'];
    }
    
    $exp = strtotime($data['expiration_date'] . ' 23:59:59');
    $now = time();
    $days_left = floor(($exp - $now) / 86400);
    
    if ($days_left < 0) {
        return ['valid' => false, 'reason' => 'License expired on ' . $data['expiration_date'], 'client_name' => $data['client_name']];
    }
    
    return [
        'valid' => true, 
        'client_name' => $data['client_name'], 
        'client_id' => $data['client_id'], 
        'expiration_date' => $data['expiration_date'],
        'days_left' => $days_left
    ];
}

$license_info = get_license_info();
?>