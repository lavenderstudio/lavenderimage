<?php
$db_config = [
    'host' => 'switchback.proxy.rlwy.net', 'port' => 29606,
    'user' => 'root', 'password' => 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW', 'database' => 'railway'
];
$prefix = 'piwigo_';
$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);

echo "<h2>Lavender Prime - Đang kích hoạt hiển thị 157 ảnh...</h2>";

// Cập nhật lại Checksum giả để Piwigo không bỏ qua ảnh
$sql = "UPDATE {$prefix}images 
        SET md5sum = MD5(file), 
            representative_ext = 'jpg' 
        WHERE path LIKE 'https://%'";
$conn->query($sql);

// Đồng bộ lại kích thước cho các ảnh chưa có size
$sql_size = "UPDATE {$prefix}images SET width = 1000, height = 1000 WHERE width IS NULL OR width = 0";
$conn->query($sql_size);

echo "<h3>Xong! Founder hãy F5 lại trang Admin.</h3>";
$conn->close();
?>
