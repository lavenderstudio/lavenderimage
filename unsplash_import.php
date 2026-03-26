<?php
/**
 * LAVENDER PRIME - HYBRID INJECTOR (v8.0)
 * Tự động tải Thumbnail về Host - Giữ link Gốc trên Cloud.
 */

// 1. Cấu hình Database Railway
$db_config = [
    'host'     => 'switchback.proxy.rlwy.net',
    'port'     => 29606,
    'user'     => 'root',
    'password' => 'yEaKItfAreoFBaWShRQAhOvZaBZiqgvW',
    'database' => 'railway'
];

$pexels_key  = 'PIc1dqABqUbwsTNkgpogI250lrXMgVh1W9BXpjIgxKs7MJKiQDASbUXK';
$category_id = 6; 
$keyword     = 'abstract gold purple';
$prefix      = 'piwigo_';

// 2. Tạo thư mục chứa ảnh tạm nếu chưa có
$upload_dir = 'upload/remote/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

echo "<h2>Lavender Prime - Đang tối ưu hiển thị Hybrid...</h2>";

$conn = new mysqli($db_config['host'], $db_config['user'], $db_config['password'], $db_config['database'], $db_config['port']);
$conn->set_charset("utf8");

for ($page = 1; $page <= 2; $page++) { // Thử nghiệm 60 ảnh
    $url = "https://api.pexels.com/v1/search?query=".urlencode($keyword)."&per_page=30&page=$page";
    $opts = ["http" => ["header" => "Authorization: $pexels_key\r\n"]];
    $context = stream_context_create($opts);
    $data = json_decode(file_get_contents($url, false, $context), true);

    foreach ($data['photos'] as $img) {
        $file_id = 'px_' . $img['id'];
        $local_path = $upload_dir . $file_id . '.jpg';

        // Chỉ tải ảnh thumbnail nếu chưa có trên host
        if (!file_exists($local_path)) {
            $thumb_content = file_get_contents($img['src']['medium']);
            file_put_contents($local_path, $thumb_content);
        }

        $check = $conn->query("SELECT id FROM {$prefix}images WHERE file = '$file_id' LIMIT 1");
        if ($check->num_rows == 0) {
            $name = $conn->real_escape_string($img['alt'] ?: 'Abstract Art');
            $raw_url = $img['src']['original']; // Link in ấn 60x60
            
            // QUAN TRỌNG: Lưu path là file nội bộ để Piwigo hiện hình
            $sql = "INSERT INTO {$prefix}images (file, path, name, author, width, height, comment, date_available) 
                    VALUES ('$file_id', '$local_path', '$name', 'Pexels', {$img['width']}, {$img['height']}, '$raw_url', NOW())";
            
            if ($conn->query($sql)) {
                $new_id = $conn->insert_id;
                $conn->query("INSERT INTO {$prefix}image_category (image_id, category_id) VALUES ($new_id, $category_id)");
                echo "Đã tối ưu hiển thị cho: $file_id <br>";
            }
        }
    }
    flush();
}
$conn->close();
echo "<h3>Xong! Founder hãy kiểm tra lại Album 6. Hình ảnh sẽ hiện lên hoàn hảo.</h3>";
?>
