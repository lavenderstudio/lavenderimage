<?php
// 1. Khởi tạo môi trường Piwigo chuẩn
define('PHPWG_ROOT_PATH', './');
include_once(PHPWG_ROOT_PATH . 'include/common.inc.php');

// Ngắt session để tránh lỗi ghi đè khi import số lượng lớn
session_write_close();

// 2. Cấu hình Access Key của Founder
$access_key = 'eTnF2DNNuK7_upLuyES_cs760QU4rxlTuqoaYm8mSI0';
$album_name = 'Abstract Ether'; 
$keyword = 'abstract-dark-purple-gold'; 
$total_pages = 5; // Nhập 150 ảnh thử nghiệm

echo "<h2>Lavender Prime - Đang đồng bộ Album ảo...</h2>";

// 3. Logic lấy hoặc tạo Album (Sử dụng hàm chuẩn để tránh lỗi SQL)
$category_id = null;
$query = 'SELECT id FROM '.CATEGORIES_TABLE.' WHERE name = "'.pwg_db_real_escape_string($album_name).'" LIMIT 1;';
$result = pwg_query($query);

if ($row = pwg_db_fetch_assoc($result)) {
    $category_id = $row['id'];
    echo "Sử dụng Album hiện có ID: $category_id <br>";
} else {
    // Tạo Album mới nếu chưa có
    $insert_cat = array('name' => $album_name, 'permalink' => 'abstract-ether');
    $category_id = create_category($album_name); // Dùng hàm nội bộ của Piwigo
    echo "Đã khởi tạo Album mới thành công. <br>";
}

// 4. Vòng lặp lấy dữ liệu từ Unsplash
for ($page = 1; $page <= $total_pages; $page++) {
    $url = "https://api.unsplash.com/search/photos?client_id=$access_key&query=".urlencode($keyword)."&page=$page&per_page=30&orientation=squarish";
    
    $ctx = stream_context_create(array('http' => array('timeout' => 15)));
    $response = @file_get_contents($url, false, $ctx);
    
    if (!$response) {
        echo "<b style='color:red;'>Lỗi: Không thể kết nối tới Unsplash API tại trang $page.</b><br>";
        break;
    }

    $data = json_decode($response, true);
    if (empty($data['results'])) break;

    foreach ($data['results'] as $img) {
        $file_id = 'unsplash_' . $img['id'];
        
        // Kiểm tra xem ảnh đã tồn tại trong database chưa
        $check_query = 'SELECT id FROM '.IMAGES_TABLE.' WHERE file = "'.$file_id.'" LIMIT 1;';
        $check_res = pwg_query($check_query);
        
        if (pwg_db_num_rows($check_res) == 0) {
            // Chuẩn bị dữ liệu ảnh
            $insert_data = array(
                'file' => $file_id,
                'path' => $img['urls']['regular'], // Link hiển thị Web
                'name' => pwg_db_real_escape_string($img['alt_description'] ?: 'Abstract Art'),
                'author' => 'Unsplash',
                'width' => $img['width'],
                'height' => $img['height'],
                'comment' => $img['urls']['raw'], // Lưu link GỐC để in 60x60
                'date_available' => CURRENT_DATE
            );

            mass_inserts(IMAGES_TABLE, array_keys($insert_data), array(array_values($insert_data)));
            $new_image_id = pwg_db_insert_id();

            // Gắn ảnh vào Album
            $assoc_data = array('image_id' => $new_image_id, 'category_id' => $category_id);
            mass_inserts(IMAGE_CATEGORY_TABLE, array_keys($assoc_data), array(array_values($assoc_data)));
            
            echo "Đã nạp: " . $file_id . " - " . $img['user']['name'] . "<br>";
        }
    }
    echo "<b>--- Hoàn tất trang $page ---</b><br>";
    flush(); 
    sleep(1); // Tránh bị Unsplash chặn
}

echo "<h3>Đã đồng bộ xong 150 ảnh trừu tượng vào Lavender Prime.</h3>";
?>
