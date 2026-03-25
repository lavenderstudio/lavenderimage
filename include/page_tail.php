<?php
// +-----------------------------------------------------------------------+
// | File này là một phần của Piwigo.                                      |
// |                                                                       |
// | Để biết thông tin về bản quyền và giấy phép, vui lòng xem file        |
// | COPYING.txt đi kèm với mã nguồn này.                                  |
// +-----------------------------------------------------------------------+

// Thiết lập file giao diện cho phần chân trang
$template->set_filenames(array('tail'=>'footer.tpl'));

// Kích hoạt hook bắt đầu xử lý chân trang
trigger_notify('loc_begin_page_tail');

$template->assign(
  array(
    'VERSION' => $conf['show_version'] ? PHPWG_VERSION : '',
    'PHPWG_URL' => defined('PHPWG_URL') ? str_replace('http:', 'https:', PHPWG_URL) : '',
    ));

//--------------------------------------------------------- Thông tin liên hệ

if (!is_a_guest())
{
  $template->assign(
    'CONTACT_MAIL', get_webmaster_mail_address()
    );
}

//--------------------------------------------------------- Thông báo cập nhật
if ($conf['update_notify_check_period'] > 0)
{
  $check_for_updates = false;
  if (isset($conf['update_notify_last_check']))
  {
    if (strtotime($conf['update_notify_last_check']) < strtotime($conf['update_notify_check_period'].' seconds ago'))
    {
      $check_for_updates = true;
    }
  }
  else
  {
    $check_for_updates = true;
  }

  if ($check_for_updates)
  {
    $exec_id = pwg_unique_exec_begins('check_for_updates');
    if (false !== $exec_id)
    {
      include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
      include_once(PHPWG_ROOT_PATH.'admin/include/updates.class.php');
      $updates = new updates();
      $updates->notify_piwigo_new_versions();

      pwg_unique_exec_ends('check_for_updates', $exec_id);
    }
  }
}

// Gửi thông tin Piwigo tới template
send_piwigo_infos();

//--------------------------------------------------------- Thời gian tạo trang
$debug_vars = array();

if ($conf['show_queries'])
{
  $debug_vars = array_merge($debug_vars, array('QUERIES_LIST' => $debug) );
}

if ($conf['show_gt'])
{
  if (!isset($page['count_queries']))
  {
    $page['count_queries'] = 0;
    $page['queries_time'] = 0;
  }
  $time = get_elapsed_time($t2, get_moment());

  $debug_vars = array_merge($debug_vars,
    array('TIME' => $time,
          'NB_QUERIES' => $page['count_queries'],
          'SQL_TIME' => number_format($page['queries_time'],3,'.',' ').' s')
          );
}

$template->assign('debug', $debug_vars );

//--------------------------------------------------------- Phiên bản di động
if ( !empty($conf['mobile_theme']) && (get_device() != 'desktop' || mobile_theme()))
{
  $template->assign('TOGGLE_MOBILE_THEME_URL',
      add_url_params(
        htmlspecialchars($_SERVER['REQUEST_URI']),
        array('mobile' => mobile_theme() ? 'false' : 'true')
      )
    );
}

// Kích hoạt hook kết thúc xử lý chân trang
trigger_notify('loc_end_page_tail');

// Xử lý và in trang ra trình duyệt
$template->parse('tail');
$template->p();

// =========================================================================
// CHÈN CODE LAVENDER PRIME TẠI ĐÂY (DƯỚI CÙNG ĐỂ ĐÈ LÊN MỌI THỨ CŨ)
// =========================================================================
echo '
<style>
  /* Xóa bỏ hoàn toàn dấu vết chân trang mặc định */
  #copyright, .footer, footer, #footer, .footer_content { 
    display: none !important; 
    height: 0 !important; 
    visibility: hidden !important;
  }

  /* Thiết kế Footer Bảo tàng cho Lavender Prime */
  #lavender-museum-footer {
    width: 100vw !important;
    position: relative !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    background: #ffffff !important;
    border-top: 1px solid #f2f2f2 !important;
    padding: 120px 0 !important;
    text-align: center !important;
    z-index: 9999 !important;
    margin-top: 40px !important;
    display: block !important;
    box-sizing: border-box !important;
  }

  #lavender-museum-footer .nav-museum {
    margin-bottom: 50px !important;
  }

  #lavender-museum-footer a {
    color: #888 !important;
    text-decoration: none !important;
    margin: 0 15px !important;
    font-family: sans-serif !important;
    font-size: 11px !important;
    letter-spacing: 3px !important;
    transition: 0.3s !important;
  }

  #lavender-museum-footer a:hover { color: #000 !important; }

  #lavender-museum-footer h2 {
    font-family: serif !important;
    font-size: 35px !important;
    letter-spacing: 15px !important;
    color: #111 !important;
    font-weight: 200 !important;
    text-transform: uppercase !important;
    margin: 20px 0 !important;
    border: none !important;
  }

  #lavender-museum-footer p {
    font-size: 10px !important;
    letter-spacing: 6px !important;
    color: #bbb !important;
    text-transform: uppercase !important;
    font-family: sans-serif !important;
  }
</style>

<footer id="lavender-museum-footer">
    <div class="nav-museum">
        <a href="index.php">ALBUMS</a> • 
        <a href="index.php?/recent_pics">LATEST</a> • 
        <a href="index.php?/most_visited">POPULAR</a> • 
        <a href="index.php?/tags">TAGS</a>
    </div>
    <h2>LAVENDER PRIME</h2>
    <p>EST. 2026 | FINE ART DIGITAL GALLERY</p>
</footer>
';
// =========================================================================

?>
