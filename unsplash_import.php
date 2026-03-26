<?php
define('PHPWG_ROOT_PATH', './');
include_once(PHPWG_ROOT_PATH . 'include/common.inc.php');

// Cấu hình từ Founder
$access_key = 'eTnF2DNNuK7_upLuyES_cs760QU4rxlTuqoaYm8mSI0';
$album_name = 'Abstract Ether'; // Tên Album trừu tượng
$keyword = 'abstract-dark-purple-gold'; // Từ khóa thương mại cho Lavender Prime
$per_page = 1; // Số ảnh mỗi lượt gọi
$total_pages = 2; // Tổng 150 ảnh cho lần thử nghiệm đầu

echo "<h2>Lavender Prime - Đang khởi tạo Album ảo...</h2>";

// 1. Tạo hoặc lấy ID của Album
$query = "SELECT id FROM " . $prefixeTable . "categories WHERE name = '" . $album_name . "' LIMIT 1";
$result = pwg_query($query);
$row = pwg_db_fetch_assoc($result);

if (!$row) {
    pwg_query("INSERT INTO " . $prefixeTable . "categories (name, permalink) VALUES ('$album_name', 'abstract-ether')");
    $category_id = pwg_db_insert_id();
    echo "Đã tạo Album mới ID: $category_id <br>";
} else {
    $category_id = $row['id'];
    echo "Sử dụng Album cũ ID: $category_id <br>";
}

// 2. Vòng lặp lấy dữ liệu từ Unsplash
for ($page = 1; $page <= $total_pages; $page++) {
    $url = "https://api.unsplash.com/search/photos?client_id=$access_key&query=$keyword&page=$page&per_page=$per_page&orientation=squarish";
    
    $response = file_get_contents($url);
    $data = json_decode($response, true);

    if (empty($data['results'])) break;

    foreach ($data['results'] as $img) {
        $file_id = 'unsplash_' . $img['id'];
        $path = $img['urls']['regular']; // Dùng ảnh Regular cho Web
        $raw_url = $img['urls']['raw']; // Lưu link gốc để in ấn sau này
        $name = pwg_db_real_escape_string($img['alt_description'] ?: 'Abstract Piece');

        // Kiểm tra trùng lặp
        $check = pwg_query("SELECT id FROM " . $prefixeTable . "images WHERE file = '$file_id'");
        if (pwg_db_num_rows($check) == 0) {
            // Chèn vào bảng ảnh (Trick: Lưu link Unsplash vào trường path)
            $sql = "INSERT INTO " . $prefixeTable . "images (file, path, name, author, width, height, comment) 
                    VALUES ('$file_id', '$path', '$name', 'Unsplash', {$img['width']}, {$img['height']}, '$raw_url')";
            pwg_query($sql);
            $image_id = pwg_db_insert_id();

            // Gắn vào Album
            pwg_query("INSERT INTO " . $prefixeTable . "image_category (image_id, category_id) VALUES ($image_id, $category_id)");
            echo "Successfully added: $file_id <br>";
        }
    }
    echo "--- Hoàn thành trang $page ---<br>";
    flush(); // Đẩy dữ liệu ra màn hình ngay lập tức
}

echo "<h3>Hoàn tất thử nghiệm. Founder hãy vào trang quản trị Piwigo để kiểm tra Album '$album_name'.</h3>";
?>
