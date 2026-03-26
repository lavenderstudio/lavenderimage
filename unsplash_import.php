<?php
/**
 * LAVENDER PRIME - CLEAN STREAM (v12.0)
 * Sửa lỗi 'Unknown column nb_images' - Nạp ảnh Pexels trực tiếp.
 */

$db_config = [
    'host'     => 'switchback.proxy.rlwy.net',
    'port'     => 29606,
    'user'     => 'root',
    'password' => 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW',
    'database' => 'railway'
];

$pexels_key  = 'PIc1dqABqUbwsTNkgpogI250lrXMgVh1W9BXpjIgxKs7MJKiQDASbUXK';
$category_id = 6; 
$keyword     = 'abstract dark purple gold'; 
$prefix      = 'piwigo_'; 

$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);
$conn->set_charset("utf8");

echo "<h2>Lavender Prime - Đang dọn rác và nạp dữ liệu chuẩn...</h2>";

// BƯỚC 1: DỌN DẸP SẠCH SẼ ALBUM 6
$conn->query("DELETE FROM {$prefix}image_category WHERE category_id = $category_id");
$conn->query("DELETE FROM {$prefix}images WHERE file LIKE 'px_%' OR file LIKE 'unsplash_%'");

// BƯỚC 2: NẠP DỮ LIỆU MỚI TỪ PEXELS
for ($page = 1; $page <= 2; $page++) {
    $url = "https://api.pexels.com/v1/search?query=".urlencode($keyword)."&per_page=40&page=$page";
    $opts = ["http" => ["header" => "Authorization: $pexels_key\r\n"]];
    $context = stream_context_create($opts);
    $data = json_decode(@file_get_contents($url, false, $context), true);

    if (empty($data['photos'])) break;

    foreach ($data['photos'] as $img) {
        $file_id = 'px_' . $img['id'];
        $display_url = $img['src']['large2x'];
        $raw_url = $img['src']['original']; // Link in ấn 60x60
        $name = $conn->real_escape_string($img['alt'] ?: 'Lavender Prime Art');

        // CHÈN VÀO BẢNG IMAGES (Chỉ dùng các cột chắc chắn tồn tại)
        $sql = "INSERT INTO {$prefix}images (file, path, name, author, width, height, comment, date_available) 
                VALUES ('$file_id', '$display_url', '$name', 'Pexels', {$img['width']}, {$img['height']}, '$raw_url', NOW())";
        
        if ($conn->query($sql)) {
            $new_id = $conn->insert_id;
            // GẮN VÀO ALBUM 6
            $conn->query("INSERT INTO {$prefix}image_category (image_id, category_id) VALUES ($new_id, $category_id)");
            echo "Nạp thành công: $file_id <br>";
        }
    }
    flush();
}

$conn->close();
echo "<h3>Hoàn tất! Founder hãy vào 'Quản lý -> Công cụ -> Bảo trì' để Piwigo tự cập nhật số lượng ảnh.</h3>";
?>
