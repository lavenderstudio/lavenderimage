<?php
// 1. Khởi tạo môi trường tối thiểu
define('PHPWG_ROOT_PATH', './');
include_once(PHPWG_ROOT_PATH . 'include/common.inc.php');

// 2. Cấu hình Railway (Giữ nguyên thông số của Founder)
$db_config = [
    'host'     => 'switchback.proxy.rlwy.net',
    'port'     => 29606,
    'user'     => 'root',
    'password' => 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW',
    'database' => 'railway'
];

$access_key = 'eTnF2DNNuK7_upLuyES_cs760QU4rxlTuqoaYm8mSI0';
$category_id = 6; 
$keyword = 'abstract-dark-purple-gold';
$total_pages = 1;

echo "<h2>Lavender Prime - Đang thực hiện bơm dữ liệu tầng thấp (SQL Native)...</h2>";

// 3. Kết nối trực tiếp qua MySQLi (Không dùng hàm Piwigo)
$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);

if ($conn->connect_error) {
    die("<b style='color:red;'>Kết nối Railway thất bại:</b> " . $conn->connect_error);
}
$conn->set_charset("utf8");

// Lấy prefix bảng (mặc định là piwigo_)
global $prefixeTable;
$p = $prefixeTable;

// 4. Vòng lặp lấy dữ liệu từ Unsplash
for ($page = 1; $page <= $total_pages; $page++) {
    $url = "https://api.unsplash.com/search/photos?client_id=$access_key&query=".urlencode($keyword)."&page=$page&per_page=30&orientation=squarish";
    
    $response = @file_get_contents($url);
    if (!$response) break;

    $data = json_decode($response, true);
    if (empty($data['results'])) break;

    foreach ($data['results'] as $img) {
        $file_id = 'unsplash_' . $img['id'];
        
        // KIỂM TRA TRÙNG LẶP BẰNG SQL NATIVE
        $sql_check = "SELECT id FROM {$p}images WHERE file = '" . $conn->real_escape_string($file_id) . "' LIMIT 1";
        $check_res = $conn->query($sql_check);
        
        if ($check_res && $check_res->num_rows == 0) {
            $name = $conn->real_escape_string($img['alt_description'] ?: 'Abstract Art Piece');
            $path = $img['urls']['regular'];
            $raw_url = $img['urls']['raw'];

            // INSERT ẢNH
            $sql_img = "INSERT INTO {$p}images (file, path, name, author, width, height, comment, date_available) 
                        VALUES ('$file_id', '$path', '$name', 'Unsplash', {$img['width']}, {$img['height']}, '$raw_url', NOW())";
            
            if ($conn->query($sql_img)) {
                $new_id = $conn->insert_id;
                // GẮN VÀO ALBUM
                $conn->query("INSERT INTO {$p}image_category (image_id, category_id) VALUES ($new_id, $category_id)");
                echo "Đã nạp thành công: " . $file_id . "<br>";
            }
        }
    }
    echo "<b>--- Hoàn tất trang $page ---</b><br>";
    flush();
    sleep(1);
}

$conn->close();
echo "<h3>Đã bơm xong 150 tác phẩm vào Album ID 6 trên Railway.</h3>";
?>
