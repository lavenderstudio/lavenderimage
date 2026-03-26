<?php
/*
Plugin Name: Lavender Prime Social Pulse
Version: 1.0.0
Author: OpenAI
Description: Community notification bell, badge and dropdown for Piwigo.
*/

if (!defined('PHPWG_ROOT_PATH')) {
    die('Hacking attempt!');
}

if (!defined('LPSP_VERSION')) {
    define('LPSP_VERSION', '1.0.0');
}

if (!function_exists('lpsp_table')) {
    function lpsp_table()
    {
        global $prefixeTable;
        return $prefixeTable . 'lpsp_notifications';
    }

    function lpsp_current_user_id()
    {
        global $user;
        return isset($user['id']) ? (int) $user['id'] : 0;
    }

    function lpsp_username_from_id($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return 'System';
        }
        if (function_exists('get_username')) {
            $name = get_username($user_id);
            if (!empty($name)) {
                return $name;
            }
        }
        return 'User #' . $user_id;
    }

    function lpsp_json(array $payload, $status = 200)
    {
        if (!headers_sent()) {
            if (function_exists('http_response_code')) {
                http_response_code($status);
            }
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    function lpsp_clean_message($text, $maxLen = 255)
    {
        $text = trim((string) $text);
        $text = strip_tags($text);
        if (function_exists('mb_substr')) {
            $text = mb_substr($text, 0, $maxLen, 'UTF-8');
        } else {
            $text = substr($text, 0, $maxLen);
        }
        return $text;
    }

    function lpsp_ensure_schema()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        $table = lpsp_table();
        $sql = "
            CREATE TABLE IF NOT EXISTS `$table` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `from_user_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `type` VARCHAR(32) NOT NULL DEFAULT 'info',
                `photo_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `message` VARCHAR(255) NOT NULL DEFAULT '',
                `url` VARCHAR(255) NOT NULL DEFAULT '',
                `is_read` TINYINT(1) NOT NULL DEFAULT 0,
                `created_on` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_user_read_date` (`user_id`, `is_read`, `created_on`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        pwg_query($sql);
    }

    function lpsp_count_notifications($user_id, $onlyUnread = false)
    {
        $user_id = (int) $user_id;
        $table = lpsp_table();
        $where = 'WHERE user_id = ' . $user_id;
        if ($onlyUnread) {
            $where .= ' AND is_read = 0';
        }
        $query = "SELECT COUNT(*) AS total FROM `$table` $where";
        $result = pwg_query($query);
        $row = pwg_db_fetch_assoc($result);
        return (int) ($row['total'] ?? 0);
    }

    function lpsp_insert_notification($to_user_id, $type, $message, $url = '', $from_user_id = 0, $photo_id = 0, $is_read = 0)
    {
        $to_user_id = (int) $to_user_id;
        $from_user_id = (int) $from_user_id;
        $photo_id = (int) $photo_id;
        $is_read = (int) $is_read;

        if ($to_user_id <= 0) {
            return false;
        }

        $type = lpsp_clean_message($type, 32);
        $message = lpsp_clean_message($message, 255);
        $url = lpsp_clean_message($url, 255);

        if ($message === '') {
            $message = 'New notification';
        }

        $table = lpsp_table();
        $query = sprintf(
            "INSERT INTO `%s`
                (`user_id`, `from_user_id`, `type`, `photo_id`, `message`, `url`, `is_read`, `created_on`)
             VALUES
                (%d, %d, '%s', %d, '%s', '%s', %d, NOW())",
            $table,
            $to_user_id,
            $from_user_id,
            pwg_db_real_escape_string($type),
            $photo_id,
            pwg_db_real_escape_string($message),
            pwg_db_real_escape_string($url),
            $is_read
        );

        return pwg_query($query);
    }

    function lpsp_seed_welcome_once($user_id)
    {
        $user_id = (int) $user_id;
        if ($user_id <= 0) {
            return;
        }

        if (lpsp_count_notifications($user_id, false) > 0) {
            return;
        }

        lpsp_insert_notification(
            $user_id,
            'welcome',
            'Chào mừng bạn đến Lavender Prime Community — thông báo mới sẽ xuất hiện ở đây.',
            get_root_url(),
            0,
            0,
            0
        );
    }

    function lpsp_fetch_notifications($user_id, $limit = 8)
    {
        $user_id = (int) $user_id;
        $limit = max(1, min(50, (int) $limit));
        $table = lpsp_table();

        $query = sprintf(
            "SELECT `id`, `from_user_id`, `type`, `photo_id`, `message`, `url`, `is_read`, `created_on`
             FROM `%s`
             WHERE `user_id` = %d
             ORDER BY `is_read` ASC, `created_on` DESC
             LIMIT %d",
            $table,
            $user_id,
            $limit
        );

        $result = pwg_query($query);
        $items = array();

        while ($row = pwg_db_fetch_assoc($result)) {
            $items[] = array(
                'id' => (int) $row['id'],
                'from_user_id' => (int) $row['from_user_id'],
                'from_user' => lpsp_username_from_id($row['from_user_id']),
                'type' => $row['type'],
                'photo_id' => (int) $row['photo_id'],
                'message' => $row['message'],
                'url' => $row['url'],
                'is_read' => (int) $row['is_read'] === 1,
                'created_on' => $row['created_on'],
                'time_ago' => lpsp_time_ago($row['created_on']),
            );
        }

        return $items;
    }

    function lpsp_mark_all_read($user_id)
    {
        $user_id = (int) $user_id;
        $table = lpsp_table();
        $query = sprintf(
            "UPDATE `%s` SET `is_read` = 1 WHERE `user_id` = %d",
            $table,
            $user_id
        );
        return pwg_query($query);
    }

    function lpsp_time_ago($datetime)
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '';
        }

        $delta = time() - $ts;
        if ($delta < 0) {
            $delta = 0;
        }

        if ($delta < 60) {
            return 'vừa xong';
        }

        if ($delta < 3600) {
            $m = floor($delta / 60);
            return $m . ' phút trước';
        }

        if ($delta < 86400) {
            $h = floor($delta / 3600);
            return $h . ' giờ trước';
        }

        if ($delta < 604800) {
            $d = floor($delta / 86400);
            return $d . ' ngày trước';
        }

        return date('d/m/Y', $ts);
    }

    function lpsp_api_dispatch()
    {
        if (empty($_GET['lpsp_api'])) {
            return;
        }

        lpsp_ensure_schema();

        $uid = lpsp_current_user_id();
        $action = isset($_REQUEST['action']) ? trim((string) $_REQUEST['action']) : 'list';

        if ($uid <= 0) {
            lpsp_json(array(
                'ok' => true,
                'unread_count' => 0,
                'notifications' => array(),
            ));
        }

        if ($action === 'read_all') {
            check_pwg_token();
            lpsp_mark_all_read($uid);
            lpsp_json(array(
                'ok' => true,
                'unread_count' => 0,
                'notifications' => lpsp_fetch_notifications($uid, 8),
            ));
        }

        if ($action === 'seed_demo') {
            check_pwg_token();
            lpsp_insert_notification(
                $uid,
                'comment',
                lpsp_username_from_id(0) . ': “Đây là bản demo notification mượt.”',
                get_root_url(),
                0,
                0,
                0
            );
            lpsp_json(array(
                'ok' => true,
                'unread_count' => lpsp_count_notifications($uid, true),
                'notifications' => lpsp_fetch_notifications($uid, 8),
            ));
        }

        lpsp_json(array(
            'ok' => true,
            'unread_count' => lpsp_count_notifications($uid, true),
            'notifications' => lpsp_fetch_notifications($uid, 8),
        ));
    }

    function lpsp_emit_assets()
    {
        if (defined('IN_ADMIN') && IN_ADMIN) {
            return;
        }

        $uid = lpsp_current_user_id();
        if ($uid <= 0) {
            return;
        }

        $username = lpsp_username_from_id($uid);
        $token = function_exists('get_pwg_token') ? get_pwg_token() : '';

        $config = array(
            'enabled' => true,
            'token' => $token,
            'userId' => $uid,
            'username' => $username,
            'apiParam' => 'lpsp_api',
        );

        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        echo <<<HTML
<style>
#lpsp-root, #lpsp-root * { box-sizing: border-box; }

#lpsp-root {
    position: fixed;
    top: calc(env(safe-area-inset-top, 0px) + 16px);
    right: calc(env(safe-area-inset-right, 0px) + 16px);
    z-index: 99999;
    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
}

.lpsp-toggle {
    width: 48px;
    height: 48px;
    border: 0;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(18, 18, 22, 0.78);
    color: #fff;
    box-shadow: 0 18px 50px rgba(0, 0, 0, 0.28);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    cursor: pointer;
    transition: transform .18s ease, background .18s ease, box-shadow .18s ease;
    position: relative;
}

.lpsp-toggle:hover {
    transform: translateY(-1px) scale(1.02);
    background: rgba(18, 18, 22, 0.88);
    box-shadow: 0 22px 60px rgba(0, 0, 0, 0.34);
}

.lpsp-toggle:focus-visible {
    outline: 2px solid rgba(255,255,255,.8);
    outline-offset: 2px;
}

.lpsp-icon {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.lpsp-icon svg {
    width: 22px;
    height: 22px;
    display: block;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.lpsp-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 22px;
    height: 22px;
    padding: 0 6px;
    border-radius: 999px;
    display: none;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #ff4d4f, #ff9f43);
    color: #fff;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .2px;
    box-shadow: 0 10px 24px rgba(255, 77, 79, .35);
    animation: lpspPulse 1.8s ease-in-out infinite;
}

.lpsp-panel {
    position: absolute;
    top: 60px;
    right: 0;
    width: min(92vw, 380px);
    max-height: min(70vh, 720px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border-radius: 22px;
    background: rgba(12, 12, 15, 0.92);
    border: 1px solid rgba(255,255,255,0.10);
    box-shadow: 0 30px 90px rgba(0, 0, 0, 0.45);
    color: #f2f2f2;
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    opacity: 0;
    pointer-events: none;
    transform: translateY(-10px) scale(0.98);
    transform-origin: top right;
    transition: opacity .18s ease, transform .18s ease;
}

#lpsp-root.is-open .lpsp-panel {
    opacity: 1;
    pointer-events: auto;
    transform: translateY(0) scale(1);
}

.lpsp-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 16px 16px 12px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}

.lpsp-head__title {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.lpsp-head__title strong {
    font-size: 15px;
    line-height: 1.2;
}

.lpsp-head__title span {
    font-size: 12px;
    color: rgba(255,255,255,0.62);
}

.lpsp-action {
    border: 0;
    border-radius: 999px;
    padding: 8px 12px;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
    background: rgba(255,255,255,0.10);
    cursor: pointer;
    transition: background .18s ease, transform .18s ease;
}
.lpsp-action:hover { transform: translateY(-1px); background: rgba(255,255,255,0.16); }

.lpsp-list {
    overflow: auto;
    max-height: calc(min(70vh, 720px) - 110px);
    padding: 8px;
}

.lpsp-empty {
    margin: 10px 8px 14px;
    padding: 16px;
    border-radius: 18px;
    background: rgba(255,255,255,0.04);
    color: rgba(255,255,255,0.74);
    font-size: 14px;
    line-height: 1.55;
}

.lpsp-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    width: 100%;
    text-decoration: none;
    color: inherit;
    padding: 12px 12px;
    border-radius: 18px;
    transition: transform .18s ease, background .18s ease, opacity .18s ease;
}

.lpsp-item:hover {
    background: rgba(255,255,255,0.05);
    transform: translateY(-1px);
}

.lpsp-item + .lpsp-item { margin-top: 4px; }

.lpsp-dot {
    width: 9px;
    height: 9px;
    border-radius: 999px;
    margin-top: 7px;
    flex: 0 0 auto;
    background: transparent;
    box-shadow: 0 0 0 0 transparent;
}

.lpsp-item.is-unread .lpsp-dot {
    background: linear-gradient(135deg, #ff4d4f, #ff9f43);
    box-shadow: 0 0 0 6px rgba(255, 159, 67, 0.08);
}

.lpsp-body {
    min-width: 0;
    flex: 1 1 auto;
}

.lpsp-message {
    display: block;
    font-size: 14px;
    line-height: 1.45;
    font-weight: 600;
    color: #f5f5f5;
    word-break: break-word;
}

.lpsp-item.is-unread .lpsp-message {
    color: #ffffff;
}

.lpsp-meta {
    margin-top: 4px;
    font-size: 12px;
    line-height: 1.35;
    color: rgba(255,255,255,0.58);
}

.lpsp-item.enter {
    animation: lpspItemIn .28s ease both;
}

@keyframes lpspItemIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes lpspPulse {
    0%,100% { transform: scale(1); }
    50% { transform: scale(1.06); }
}

@media (max-width: 480px) {
    #lpsp-root {
        top: auto;
        right: 12px;
        bottom: 12px;
    }
    .lpsp-panel {
        top: auto;
        bottom: 60px;
        right: 0;
        width: min(94vw, 360px);
    }
}
</style>

<script>
window.LPSP = {$json};

document.addEventListener('DOMContentLoaded', function () {
    if (!window.LPSP || !window.LPSP.enabled) return;
    if (document.getElementById('lpsp-root')) return;

    const root = document.createElement('div');
    root.id = 'lpsp-root';
    root.innerHTML = `
        <button class="lpsp-toggle" type="button" aria-label="Thông báo" aria-expanded="false" aria-controls="lpsp-panel">
            <span class="lpsp-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M12 4a4 4 0 0 0-4 4v2.1c0 .9-.28 1.78-.8 2.53L5.7 14.2A1.8 1.8 0 0 0 7.2 17h9.6a1.8 1.8 0 0 0 1.5-2.8l-1.5-2.57a4.6 4.6 0 0 1-.8-2.53V8a4 4 0 0 0-4-4Z"></path>
                    <path d="M9.5 18a2.5 2.5 0 0 0 5 0"></path>
                </svg>
            </span>
            <span class="lpsp-badge" hidden>0</span>
        </button>

        <div class="lpsp-panel" id="lpsp-panel" role="dialog" aria-label="Thông báo cộng đồng" aria-modal="false">
            <div class="lpsp-head">
                <div class="lpsp-head__title">
                    <strong>Thông báo</strong>
                    <span>Cập nhật mới từ cộng đồng</span>
                </div>
                <button class="lpsp-action lpsp-markread" type="button">Đã đọc</button>
            </div>
            <div class="lpsp-list" aria-live="polite"></div>
        </div>
    `;
    document.body.appendChild(root);

    const btn = root.querySelector('.lpsp-toggle');
    const badge = root.querySelector('.lpsp-badge');
    const panel = root.querySelector('.lpsp-panel');
    const list = root.querySelector('.lpsp-list');
    const markReadBtn = root.querySelector('.lpsp-markread');

    let lastUnread = 0;
    let isLoading = false;

    function apiUrl(action) {
        const url = new URL(window.location.href);
        url.searchParams.set(window.LPSP.apiParam, '1');
        url.searchParams.set('action', action);
        return url.toString();
    }

    function updateBadge(count) {
        count = Number(count || 0);
        lastUnread = count;
        badge.textContent = count;
        badge.hidden = count <= 0;
        badge.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    function escText(value) {
        return String(value == null ? '' : value);
    }

    function render(items) {
        list.innerHTML = '';
        if (!items || !items.length) {
            const empty = document.createElement('div');
            empty.className = 'lpsp-empty';
            empty.textContent = 'Chưa có thông báo mới. Khi có lượt thích, bình luận hoặc nhắc đến bạn, chúng sẽ xuất hiện ở đây.';
            list.appendChild(empty);
            return;
        }

        items.forEach((item, index) => {
            const el = item.url ? document.createElement('a') : document.createElement('div');
            if (item.url) {
                el.href = item.url;
            }
            el.className = 'lpsp-item' + (item.is_read ? '' : ' is-unread');
            el.style.animationDelay = (index * 28) + 'ms';

            const dot = document.createElement('span');
            dot.className = 'lpsp-dot';

            const body = document.createElement('span');
            body.className = 'lpsp-body';

            const msg = document.createElement('span');
            msg.className = 'lpsp-message';
            msg.textContent = escText(item.message);

            const meta = document.createElement('span');
            meta.className = 'lpsp-meta';
            const fromName = item.from_user ? (' • ' + item.from_user) : '';
            meta.textContent = escText(item.time_ago || '') + fromName;

            body.appendChild(msg);
            body.appendChild(meta);
            el.appendChild(dot);
            el.appendChild(body);

            if (item.url) {
                el.addEventListener('click', function () {
                    root.classList.remove('is-open');
                    btn.setAttribute('aria-expanded', 'false');
                });
            }

            list.appendChild(el);
            requestAnimationFrame(function () {
                el.classList.add('enter');
            });
        });
    }

    async function fetchNotifications() {
        if (isLoading) return;
        isLoading = true;
        try {
            const res = await fetch(apiUrl('list'), {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            updateBadge(data.unread_count || 0);
            render(data.notifications || []);
        } catch (e) {
            // no-op
        } finally {
            isLoading = false;
        }
    }

    async function markAllRead() {
        if (lastUnread <= 0) {
            root.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            return;
        }
        try {
            const body = new URLSearchParams();
            body.set('pwg_token', window.LPSP.token || '');
            const res = await fetch(apiUrl('read_all'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            });
            const data = await res.json();
            updateBadge(data.unread_count || 0);
            render(data.notifications || []);
        } catch (e) {
            // no-op
        }
    }

    function closePanel() {
        root.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
    }

    function togglePanel() {
        const open = root.classList.toggle('is-open');
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            fetchNotifications();
        }
    }

    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        togglePanel();
    });

    markReadBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        markAllRead();
    });

    document.addEventListener('click', function (e) {
        if (!root.contains(e.target)) closePanel();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closePanel();
    });

    fetchNotifications();
    setInterval(fetchNotifications, 15000);
});
</script>
HTML;
    }
}

lpsp_ensure_schema();
lpsp_seed_welcome_once(lpsp_current_user_id());
lpsp_api_dispatch();
add_event_handler('loc_end_page_header', 'lpsp_emit_assets');
