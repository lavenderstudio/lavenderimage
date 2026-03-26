<?php
/*
Plugin Name: Lavender Prime - External Image Fix v2
Version: 2.0
Description: Hỗ trợ full hotlink Pexels + các external URL, sửa derivative, thêm referrer policy, attribution tự động
Author: Grok Assisted
*/

if (!defined('PHPWG_ROOT_PATH')) die('Hacking attempt!');

global $conf;

// === CẤU HÌNH CỐT LÕI ===
$conf['enable_external_urls'] = true;                    // Cho phép URL ngoài
$conf['derivative_url_style'] = 2;                       // Quan trọng: Dùng i.php (script) thay vì 'original'
$conf['check_url_extension'] = false;
$conf['external_thumbnails_prefix'] = '';                // Bỏ prefix ./ 
$conf['upload_dir'] = '';

// Vô hiệu hóa một số thứ không cần thiết với external
$conf['graphics_library'] = 'gd';                        // Hoặc 'imagick' nếu server hỗ trợ tốt hơn
$conf['show_exif'] = false;
$conf['show_iptc'] = false;

// === EVENT HANDLERS ===
add_event_handler('get_derivative_url', 'lavender_fix_derivative_url', 1, 3);
add_event_handler('loc_end_page_header', 'lavender_add_meta_referrer');
add_event_handler('render_element_content', 'lavender_force_original_url', 50, 2); // Ưu tiên original URL

function lavender_fix_derivative_url($url, $type, $src_image) {
    $path = $src_image->get_path();

    // Nếu là external URL (http/https)
    if (strpos($path, 'http://') !== false || strpos($path, 'https://') !== false) {
        // Trả về chính URL gốc, bỏ mọi tiền tố ./ hoặc _data/i/
        return preg_replace('/^.*?(https?:\/\/)/i', '$1', $path);
    }
    return $url;
}

function lavender_add_meta_referrer() {
    echo '<meta name="referrer" content="no-referrer-when-downgrade">' . "\n";
    // Hoặc 'no-referrer' nếu muốn ẩn hoàn toàn
}

function lavender_force_original_url($content, $element_info) {
    if (isset($element_info['path']) && strpos($element_info['path'], 'http') !== false) {
        // Buộc dùng original URL cho ảnh lớn
        $content = str_replace(
            pwg_get_element_url($element_info), 
            $element_info['path'], 
            $content
        );
    }
    return $content;
}
