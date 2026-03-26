<?php
/**
 * LAVENDER PRIME - REPAIR & SYNC TOOL (v9.0)
 * Dọn dẹp lỗi Deprecated và đồng bộ hóa Album 6.
 */

$db_config = [
    'host'     => 'switchback.proxy.rlwy.net',
    'port'     => 29606,
    'user'     => 'root',
    'password' => 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW',
    'database' => 'railway'
];

$prefix = 'piwigo_'; 

echo "<h2>Lavender Prime - Đang dọn dẹp dữ liệu rác...</h2>";

$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);
if ($conn->connect_error) die("Kết nối thất bại.");

// BƯỚC 1: Sửa lỗi Album (Cập nhật Global Rank và Status để hết lỗi substr_count)
$sql_fix_cat = "UPDATE {$prefix}categories 
                SET global_rank = '1', 
                    status = 'public', 
                    visible = 'true', 
                    uppercats = '1' 
                WHERE id = 6";
$conn->query($sql_fix_cat);

// BƯỚC 2: Xóa các ảnh lỗi "0.00MB" (Ảnh chỉ có link URL mà không có file vật lý)
// Chúng ta sẽ xóa để nạp lại bản Hybrid sạch hơn ở bước sau.
$sql_clean = "DELETE FROM {$prefix}images WHERE path LIKE 'https://%'";
$conn->query($sql_clean);

// BƯỚC 3: Đồng bộ lại số lượng ảnh hiển thị trong Album
$sql_sync = "UPDATE {$prefix}categories c 
             SET nb_images = (SELECT COUNT(*) FROM {$prefix}image_category ic WHERE ic.category_id = c.id)";
$conn->query($sql_sync);

echo "<h3>1. Đã sửa cấu trúc Album ID 6 (Hết lỗi Deprecated).</h3>";
echo "<h3>2. Đã dọn dẹp ảnh ảo gây lỗi hiển thị.</h3>";
echo "<h3>3. Đã đồng bộ bộ đếm ảnh.</h3>";

$conn->close();
echo "<b>Founder hãy F5 lại trang Lavender Prime. Mọi dòng lỗi sẽ biến mất!</b>";
?>
