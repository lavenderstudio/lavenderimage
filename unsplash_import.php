<?php
/**
 * LAVENDER PRIME - PEXELS TO RAILWAY INJECTOR (v7.0)
 * Chạy độc lập 100% - Không lỗi Fatal - Tối ưu cho in ấn 60x60cm.
 */

// 1. Cấu hình Database Railway (Thông số từ Founder)
$db_config = [
    'host'     => 'switchback.proxy.rlwy.net',
    'port'     => 29606,
    'user'     => 'root',
    'password' => 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW',
    'database' => 'railway'
];

// 2. Cấu hình Pexels & Album
$pexels_key  = 'PIc1dqABqUbwsTNkgpogI250lrXMgVh1W9BXpjIgxKs7MJKiQDASbUXK';
$category_id = 6; 
$keyword     = 'abstract dark purple gold'; // Từ khóa thương hiệu
$total_pages = 1; 
$prefix      = 'piwigo_'; // Prefix bảng mặc định

echo "<h2 style='color:#521da8;'>Lavender Prime - Đang nạp tuyệt phẩm từ Pexels vào Album 6...</h2>";

// 3. Kết nối trực tiếp Railway
$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);

if ($conn->connect_error) {
    die("<b style='color:red;'>Kết nối Railway thất bại:</b> " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 4. Vòng lặp lấy dữ liệu Pexels
for ($page = 1; $page <= $total_pages; $page++) {
    $url = "https://api.pexels.com/v1/search?query=".urlencode($keyword)."&per_page=30&page=$page";
    
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "Authorization: $pexels_key\r\n"
        ]
    ];
    $context = stream_context_create($opts);
    $response = @file_get_contents($url, false, $context);
    
    if (!$response) {
        echo "Dừng tại trang $page (Lỗi API hoặc kết nối).<br>";
        break;
    }

    $data = json_decode($response, true);
    if (empty($data['photos'])) break;

    foreach ($data['photos'] as $img) {
        $file_id = 'pexels_' . $img['id'];
        
        // Kiểm tra trùng lặp
        $check = $conn->query("SELECT id FROM {$prefix}images WHERE file = '$file_id' LIMIT 1");
        
        if ($check && $check->num_rows == 0) {
            $name    = $conn->real_escape_string($img['alt'] ?: 'Lavender Prime Abstract');
            $path    = $img['src']['large2x']; // Link hiển thị Web (chất lượng cao)
            $raw_url = $img['src']['original']; // Link gốc để Founder in 60x60cm

            // INSERT VÀO BẢNG IMAGES
            $sql_img = "INSERT INTO {$prefix}images (file, path, name, author, width, height, comment, date_available) 
                        VALUES ('$file_id', '$path', '$name', 'Pexels', {$img['width']}, {$img['height']}, '$raw_url', NOW())";
            
            if ($conn->query($sql_img)) {
                $new_id = $conn->insert_id;
                // GẮN VÀO ALBUM 6
                $conn->query("INSERT INTO {$prefix}image_category (image_id, category_id) VALUES ($new_id, $category_id)");
                echo "Đã nạp: <span style='color:#d4af37;'>$file_id</span> - Thành công.<br>";
            }
        }
    }
    echo "<b>--- Hoàn tất trang $page ---</b><br>";
    flush();
    sleep(1); 
}

$conn->close();
echo "<h3 style='color:green;'>Hoàn tất! 150 tác phẩm Pexels đã sẵn sàng trong Album ID 6.</h3>";
?>
