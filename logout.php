<?php
session_start();
require_once 'db.php'; // Hubungkan ke pangkalan data untuk hantar log

// 1. REKOD AKTIVITI LOG OUT KE DALAM SYSTEM LOGS (Sebelum session dipadamkan)
if (isset($_SESSION['fullname'])) {
    $nama = $_SESSION['fullname'];
    $role = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Pengguna';
    
    $log_msg = "$role $nama telah log keluar daripada sistem.";
    $conn->query("INSERT INTO system_logs (log_message) VALUES ('$log_msg')");
}

// 2. PADAM SEMUA DATA SESSION
$_SESSION = array(); // Kosongkan array session

// Jika menggunakan cookie session, padamkan cookie tersebut juga
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan session sepenuhnya
session_destroy();

// 3. HALA TUJU (REDIRECT) SELEPAS LOGOUT
// Kita bawa pengguna balik ke fail login atau index utama
echo "<script>
    alert('Anda telah berjaya log keluar.');
    window.location.href = 'Auth/login.php'; 
</script>";
exit();
?>