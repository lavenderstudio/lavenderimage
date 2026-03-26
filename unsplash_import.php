<?php
/**
 * LAVENDER PRIME - ULTIMATE UNSPLASH INJECTOR (v6.0)
 * Giải pháp: Kết nối trực tiếp Railway, không thông qua nhân Piwigo để tránh lỗi Scalar.
 */

// 1. Cấu hình Database Railway của Founder
$db_config = [
    'host'     => 'switchback.proxy.rlwy.net',
    'port'     => 29606,
    'user'     => 'root',
    'password' => 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW',
    'database' => 'railway'
];

// 2. Cấu hình Unsplash & Album
$access_key  = 'eTnF2DNNuK7_upLuyES_cs760QU4rxlTuqoaYm8mSI0';
$category_id = 6; // Album Trừu tượng của Founder
$keyword     = 'abstract-dark-purple-gold';
$total_pages = 5; 
$prefix      = 'piwigo_'; // Prefix mặc định của Piwigo

echo "<h2>Lavender Prime - Đang bơm 150 tuyệt phẩm vào Album ID 6...</h2>";

// 3. Khởi tạo kết nối thuần MySQLi
$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);

if ($conn->connect_error) {
    die("<b style='color:red;'>Kết nối Railway thất bại:</b> " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 4. Vòng lặp lấy dữ liệu và Insert trực tiếp
for ($page = 1; $page <= $total_pages; $page++) {
    $url = "https://api.unsplash.com/search/photos?client_id=$access_key&query=".urlencode($keyword)."&page=$page&per_page=30&orientation=squarish";
    
    $response = @file_get_contents($url);
    if (!$response) {
        echo "Dừng tại trang $page (Hết hạn ngạch API hoặc lỗi kết nối).<br>";
        break;
    }

    $data = json_decode($response, true);
    if (empty($data['results'])) break;

    foreach ($data['results'] as $img) {
        $file_id = 'unsplash_' . $img['id'];
        
        // Kiểm tra trùng bằng SQL thuần (Bypass hoàn toàn Piwigo Core)
        $check = $conn->query("SELECT id FROM {$prefix}images WHERE file = '$file_id' LIMIT 1");
        
        if ($check && $check->num_rows == 0) {
            $name    = $conn->real_escape_string($img['alt_description'] ?: 'Abstract Art');
            $path    = $img['urls']['regular'];
            $raw_url = $img['urls']['raw']; // Giữ link RAW để Founder in 60x60cm

            // INSERT VÀO BẢNG IMAGES
            $sql_img = "INSERT INTO {$prefix}images (file, path, name, author, width, height, comment, date_available) 
                        VALUES ('$file_id', '$path', '$name', 'Unsplash', {$img['width']}, {$img['height']}, '$raw_url', NOW())";
            
            if ($conn->query($sql_img)) {
                $new_id = $conn->insert_id;
                // GẮN VÀO ALBUM 6
                $conn->query("INSERT INTO {$prefix}image_category (image_id, category_id) VALUES ($new_id, $category_id)");
                echo "Đã nạp: <span style='color:#521da8;'>$file_id</span> - Done.<br>";
            }
        }
    }
    echo "<b>--- Hoàn tất trang $page ---</b><br>";
    flush();
    sleep(1); 
}

$conn->close();
echo "<h3>Thành công! Founder hãy vào lại Album 6 để chiêm ngưỡng.</h3>";
?>
