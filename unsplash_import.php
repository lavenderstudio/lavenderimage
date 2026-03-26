<?php
/**
 * LAVENDER PRIME - PURE STREAMER (v11.0)
 * Nạp ảnh trực tiếp từ Pexels - Hiển thị ngay lập tức - Không tốn dung lượng.
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
$total_pages = 3; // Nạp trước 90 ảnh cực phẩm
$prefix      = 'piwigo_'; 

$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);
$conn->set_charset("utf8");

echo "<h2>Lavender Prime - Đang khởi tạo dòng chảy nghệ thuật...</h2>";

for ($page = 1; $page <= $total_pages; $page++) {
    $url = "https://api.pexels.com/v1/search?query=".urlencode($keyword)."&per_page=30&page=$page";
    $opts = ["http" => ["header" => "Authorization: $pexels_key\r\n"]];
    $context = stream_context_create($opts);
    $data = json_decode(file_get_contents($url, false, $context), true);

    if (empty($data['photos'])) break;

    foreach ($data['photos'] as $img) {
        $file_id = 'px_' . $img['id'];
        
        // Lấy link ảnh chất lượng cao để hiển thị (large2x)
        $display_url = $img['src']['large2x'];
        $raw_url = $img['src']['original']; // Giữ lại để in 60x60cm

        $name = $conn->real_escape_string($img['alt'] ?: 'Lavender Prime Abstract Art');

        // Bơm thẳng vào Database - Ép tham số để Piwigo không quét Thumbnail nội bộ
        $sql = "INSERT INTO {$prefix}images (file, path, name, author, width, height, comment, date_available, representative_ext) 
                VALUES ('$file_id', '$display_url', '$name', 'Pexels', {$img['width']}, {$img['height']}, '$raw_url', NOW(), 'jpg')";
        
        if ($conn->query($sql)) {
            $new_id = $conn->insert_id;
            $conn->query("INSERT INTO {$prefix}image_category (image_id, category_id) VALUES ($new_id, $category_id)");
            echo "Đã nạp: <span style='color:#d4af37;'>$file_id</span> - Thành công.<br>";
        }
    }
    flush();
    sleep(1);
}

// CẬP NHẬT CUỐI: Đồng bộ số lượng ảnh để Album hiện đúng
$conn->query("UPDATE {$prefix}categories SET nb_images = (SELECT COUNT(*) FROM {$prefix}image_category WHERE category_id = 6) WHERE id = 6");

$conn->close();
echo "<h3>Quy trình hoàn tất! Founder hãy F5 trang chủ Lavender Prime.</h3>";
?>
