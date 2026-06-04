<?php
session_start();
require_once '../db.php'; // Hubungkan ke pangkalan data

// Ambil 3 log sistem terbaharu
$logs_result = $conn->query("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 3");

if ($logs_result && $logs_result->num_rows > 0) {
    while ($log = $logs_result->fetch_assoc()) {
        $masa = date('H:i:s', strtotime($log['created_at']));
        $mesej = htmlspecialchars($log['log_message']);
        
        // Cetak baris log demi baris
        echo '<div class="log-line">';
        echo '  <span class="log-time">[' . $masa . ']</span> ' . $mesej;
        echo '</div>';
    }
} else {
    echo '<div class="log-line"><span class="log-time">[' . date('H:i:s') . ']</span> Terminal bersedia. Tiada log baharu.</div>';
}
?>