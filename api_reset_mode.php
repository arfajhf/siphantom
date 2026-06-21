<?php
// Simpan sebagai api_reset_mode.php
file_put_contents('mode.txt', 'auto');
echo json_encode(['status' => 'success', 'message' => 'Mode Reset ke Auto']);
?>