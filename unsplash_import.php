<?php
// 1. Khởi tạo môi trường Piwigo để lấy thông số cấu hình
define('PHPWG_ROOT_PATH', './');
include_once(PHPWG_ROOT_PATH . 'include/common.inc.php');

// Lấy thông số kết nối từ file config của Piwigo
global $conf, $prefixeTable;

// 2. Thông tin Unsplash của Founder
$access_key = 'eTnF2DNNuK7_upLuyES_cs760QU4rxlTuqoaYm8mSI0';
$album_name = 'Abstract Ether'; 
$keyword = 'abstract-dark-purple-gold'; 
$total_pages = 5; 

echo "<h2>Lavender Prime - Khởi tạo quy trình nạp dữ liệu trực tiếp...</h2>";

// 3. Tự tạo kết nối Database (Bỏ qua hàm pwg_query đang lỗi)
$conn = new mysqli($conf['db_host'], $conf['db_user'], $conf['db_password'], $conf['db_base']);

if ($conn->connect_error) {
    die("Kết nối database thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8");

// 4. Kiểm tra hoặc Tạo Album
$sql_cat = "SELECT id FROM {$prefixeTable}categories WHERE name = '" . $conn->real_escape_string($album_name) . "' LIMIT 1";
$res_cat = $conn->query($sql_cat);
$row_cat = $res_cat->fetch_assoc();

if ($row_cat) {
    $category_id = $row_cat['id'];
    echo "Sử dụng Album hiện có ID: $category_id <br>";
} else {
    $conn->query("INSERT INTO {$prefixeTable}categories (name, permalink) VALUES ('" . $conn->real_escape_string($album_name) . "', 'abstract-ether')");
    $category_id = $conn->insert_id;
    echo "Đã tạo Album mới ID: $category_id <br>";
}

// 5. Vòng lặp nạp dữ liệu từ Unsplash
for ($page = 1; $page <= $total_pages; $page++) {
    $url = "https://api.unsplash.com/search/photos?client_id=$access_key&query=".urlencode($keyword)."&page=$page&per_page=30&orientation=squarish";
    
    $response = @file_get_contents($url);
    if (!$response) break;

    $data = json_decode($response, true);
    if (empty($data['results'])) break;

    foreach ($data['results'] as $img) {
        $file_id = 'unsplash_' . $img['id'];
        
        // Kiểm tra ảnh đã tồn tại chưa
        $check = $conn->query("SELECT id FROM {$prefixeTable}images WHERE file = '$file_id'");
        if ($check->num_rows == 0) {
            $name = $conn->real_escape_string($img['alt_description'] ?: 'Abstract Art Piece');
            $path = $img['urls']['regular'];
            $raw_url = $img['urls']['raw'];

            // Chèn vào bảng ảnh (Lưu link RAW vào comment để truy xuất in 60x60 sau này)
            $sql_img = "INSERT INTO {$prefixeTable}images (file, path, name, author, width, height, comment, date_available) 
                        VALUES ('$file_id', '$path', '$name', 'Unsplash', {$img['width']}, {$img['height']}, '$raw_url', NOW())";
            
            if ($conn->query($sql_img)) {
                $new_id = $conn->insert_id;
                // Gắn ảnh vào Album
                $conn->query("INSERT INTO {$prefixeTable}image_category (image_id, category_id) VALUES ($new_id, $category_id)");
                echo "Đã nạp: " . $file_id . "<br>";
            }
        }
    }
    echo "<b>--- Hoàn tất trang $page ---</b><br>";
    flush(); 
    sleep(1); 
}

$conn->close();
echo "<h3>Hoàn tất quy trình! Founder hãy kiểm tra Album ngay bây giờ.</h3>";
?>
