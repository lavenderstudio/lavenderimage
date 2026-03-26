<?php
// 1. Khởi tạo môi trường Piwigo (để lấy các hằng số bảng)
define('PHPWG_ROOT_PATH', './');
include_once(PHPWG_ROOT_PATH . 'include/common.inc.php');

// 2. Cấu hình kết nối Railway của Founder
$db_config = [
    'host'     => 'switchback.proxy.rlwy.net',
    'port'     => 29606,
    'user'     => 'root',
    'password' => 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW',
    'database' => 'railway'
];

$access_key = 'eTnF2DNNuK7_upLuyES_cs760QU4rxlTuqoaYm8mSI0';
$category_id = 6; // Album Trừu tượng đã có
$keyword = 'abstract-dark-purple-gold';
$total_pages = 5;

echo "<h2>Lavender Prime - Đang nạp dữ liệu trực tiếp vào Railway...</h2>";

// 3. Thiết lập kết nối trực tiếp (Bỏ qua pwg_query)
$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);

if ($conn->connect_error) {
    die("<b style='color:red;'>Kết nối Railway thất bại:</b> " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 4. Vòng lặp lấy dữ liệu từ Unsplash
for ($page = 1; $page <= $total_pages; $page++) {
    $url = "https://api.unsplash.com/search/photos?client_id=$access_key&query=".urlencode($keyword)."&page=$page&per_page=30&orientation=squarish";
    
    $response = @file_get_contents($url);
    if (!$response) break;

    $data = json_decode($response, true);
    if (empty($data['results'])) break;

    foreach ($data['results'] as $img) {
        $file_id = 'unsplash_' . $img['id'];
        
        // Kiểm tra ảnh đã tồn tại chưa (Dùng prefix piwigo_ nếu có)
        $p = $prefixeTable;
        $check = $conn->query("SELECT id FROM {$p}images WHERE file = '$file_id'");
        
        if ($check->num_rows == 0) {
            $name = $conn->real_escape_string($img['alt_description'] ?: 'Abstract Art Piece');
            $path = $img['urls']['regular'];
            $raw_url = $img['urls']['raw'];

            // Chèn vào bảng ảnh (Lưu RAW URL vào comment để in 60x60)
            $sql_img = "INSERT INTO {$p}images (file, path, name, author, width, height, comment, date_available) 
                        VALUES ('$file_id', '$path', '$name', 'Unsplash', {$img['width']}, {$img['height']}, '$raw_url', NOW())";
            
            if ($conn->query($sql_img)) {
                $new_id = $conn->insert_id;
                // Gắn vào Album ID 6
                $conn->query("INSERT INTO {$p}image_category (image_id, category_id) VALUES ($new_id, $category_id)");
                echo "Đã nạp: " . $file_id . " - " . $img['user']['name'] . "<br>";
            }
        }
    }
    echo "<b>--- Hoàn tất trang $page ---</b><br>";
    flush();
    sleep(1);
}

$conn->close();
echo "<h3>Hoàn tất! 150 tác phẩm đã nằm gọn trong Album ID 6.</h3>";
?>
