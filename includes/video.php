<?php
if (!defined('ABSPATH')) { exit; }

function ksem_video_definitions(): array
{
    return array(
        'verified' => array('boolean', 'Exact song video publicly available and verified'),
        'url' => array('string', 'Public video watch/reference URL'),
        'embed_url' => array('string', 'HTTPS iframe player URL'),
        'content_url' => array('string', 'Direct HTTPS video file URL'),
        'name' => array('string', 'Video title (label excerpts and Art Tracks accurately)'),
        'description' => array('string', 'Video description'),
        'thumbnail_url' => array('string', 'Video-specific thumbnail URL'),
        'upload_date' => array('string', 'First public video publication (ISO 8601 with timezone)'),
        'duration_seconds' => array('integer', 'Video duration in seconds'),
        'width' => array('integer', 'Video/player width in pixels'),
        'height' => array('integer', 'Video/player height in pixels'),
        'mime_type' => array('string', 'Direct file MIME type (video/mp4 or video/webm)'),
        'player_card' => array('boolean', 'Request X player card after verifying iframe playback and dimensions'),
        'x_site' => array('string', 'Verified X account attribution (@handle; optional)'),
    );
}

function ksem_video_fields(): array
{
    $fields = array();
    foreach (ksem_video_definitions() as $field => $definition) { $fields['_ksem_video_' . $field] = $definition[0]; }
    return $fields;
}

function ksem_sanitize_video(string $key, $value)
{
    $type = ksem_video_fields()[$key] ?? 'string';
    if ($type === 'boolean') { return rest_sanitize_boolean($value); }
    if ($type === 'integer') { return max(0, (int) $value); }
    if (substr($key, -4) === '_url') { return esc_url_raw(trim((string) $value), array('https')); }
    if ($key === '_ksem_video_description') { return sanitize_textarea_field($value); }
    if ($key === '_ksem_video_x_site') { return preg_match('/^@[A-Za-z0-9_]{1,15}$/', trim((string) $value)) ? trim((string) $value) : ''; }
    if ($key === '_ksem_video_mime_type') { return in_array($value, array('video/mp4', 'video/webm'), true) ? $value : ''; }
    if ($key === '_ksem_video_upload_date') {
        $value = trim((string) $value);
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(Z|[+-](\d{2}):(\d{2}))$/', $value, $m)
            || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])
            || (int) $m[4] > 23 || (int) $m[5] > 59 || (int) $m[6] > 59
            || (isset($m[8]) && ((int) $m[8] > 14 || (int) $m[9] > 59 || ((int) $m[8] === 14 && (int) $m[9] !== 0)))) { return ''; }
        return $value;
    }
    return sanitize_text_field($value);
}

function ksem_render_video_fields(int $post_id): void
{
    echo '<h3>Preferred public video</h3><p class="description">Leave blank when no public video exists. Keep the watch link, iframe player and actual video file separate. Google markup requires the matching video to be embedded here, with its own title, thumbnail and first-publication timestamp. Search and sharing services decide whether to display a play button.</p><table class="form-table" role="presentation">';
    foreach (ksem_video_definitions() as $field => $definition) {
        list($type, $label) = $definition;
        $key = '_ksem_video_' . $field;
        $name = 'video_' . $field;
        $value = get_post_meta($post_id, $key, true);
        echo '<tr><th scope="row"><label for="ksem-' . esc_attr($name) . '">' . esc_html($label) . '</label></th><td>';
        if ($type === 'boolean') {
            echo '<input id="ksem-' . esc_attr($name) . '" name="ksem[' . esc_attr($name) . ']" type="checkbox" value="1" ' . checked((bool) $value, true, false) . '>';
        } else {
            $input_type = $type === 'integer' ? 'number' : (substr($field, -4) === '_url' || $field === 'url' ? 'url' : 'text');
            echo '<input class="widefat" id="ksem-' . esc_attr($name) . '" name="ksem[' . esc_attr($name) . ']" type="' . $input_type . '" ' . ($type === 'integer' ? 'min="0" ' : '') . 'value="' . esc_attr($value) . '">';
        }
        echo '</td></tr>';
    }
    echo '</table>';
}

function ksem_save_video(int $post_id, array $input): void
{
    foreach (ksem_video_fields() as $key => $type) {
        update_post_meta($post_id, $key, ksem_sanitize_video($key, $input[substr($key, 6)] ?? ($type === 'boolean' ? false : '')));
    }
}

function ksem_video_data(int $post_id): array
{
    $video = array();
    foreach (ksem_video_fields() as $key => $type) { $video[substr($key, 12)] = ksem_sanitize_video($key, get_post_meta($post_id, $key, true)); }
    if (empty($video['verified'])) { return array(); }
    if (!empty($video['content_url']) && empty($video['mime_type'])) { $video['content_url'] = ''; }
    return empty($video['embed_url']) && empty($video['content_url']) ? array() : $video;
}

function ksem_video_player_url(int $post_id, array $video): string
{
    if (!$video) { return ''; }
    return !empty($video['embed_url']) || !empty($video['content_url']) ? add_query_arg('ksem_player', $post_id, home_url('/')) : '';
}

function ksem_video_player_ready(array $video, string $player): bool
{
    return $video && !empty($video['player_card']) && $player !== '' && !empty($video['thumbnail_url'])
        && !empty($video['name']) && !empty($video['width']) && !empty($video['height']);
}

function ksem_output_video_tags(array $video): void
{
    if (!$video) { return; }
    ksem_meta_tag('property', 'og:video', $video['content_url'] ?: $video['embed_url']);
    ksem_meta_tag('property', 'og:video:type', $video['content_url'] ? $video['mime_type'] : 'text/html');
    ksem_meta_tag('property', 'og:video:width', $video['width'] ? (string) $video['width'] : '');
    ksem_meta_tag('property', 'og:video:height', $video['height'] ? (string) $video['height'] : '');
}

function ksem_video_is_embedded(int $post_id, array $video): bool
{
    $content = html_entity_decode((string) get_post_field('post_content', $post_id), ENT_QUOTES, 'UTF-8');
    // Drop comments/scripts/styles so dormant samples cannot claim a visible player.
    $content = preg_replace('~<!--.*?-->|<(script|style)\b[^>]*>.*?</\1>~is', '', $content);
    if (!empty($video['embed_url']) && preg_match('~<iframe\b[^>]*\bsrc\s*=\s*([\x22\x27])' . preg_quote($video['embed_url'], '~') . '\1~i', $content)) { return true; }
    if (!empty($video['content_url'])) {
        preg_match_all('~<video\b[^>]*>(?:.*?)</video>~is', $content, $matches);
        foreach ($matches[0] as $element) {
            if (preg_match('~<(?:video|source)\b[^>]*\bsrc\s*=\s*([\x22\x27])' . preg_quote($video['content_url'], '~') . '\1~i', $element)) { return true; }
        }
    }
    return false;
}

function ksem_video_schema(int $post_id, array $video, string $url): array
{
    if (!$video || empty($video['name']) || empty($video['thumbnail_url']) || empty($video['upload_date']) || !ksem_video_is_embedded($post_id, $video)) { return array(); }
    return array_filter(array(
        '@type' => 'VideoObject', '@id' => $url . '#preferred-video',
        'name' => $video['name'], 'description' => $video['description'],
        'thumbnailUrl' => array($video['thumbnail_url']), 'uploadDate' => $video['upload_date'],
        'duration' => ksem_iso_duration($video['duration_seconds']), 'url' => $video['url'],
        'embedUrl' => $video['embed_url'], 'contentUrl' => $video['content_url'],
        'encodingFormat' => $video['content_url'] ? $video['mime_type'] : '',
    ));
}

function ksem_native_player_html(array $video): string
{
    if (empty($video['content_url']) && !empty($video['embed_url'])) {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><meta name="referrer" content="strict-origin-when-cross-origin"><title>'
            . esc_html($video['name']) . '</title><style>html,body{margin:0;height:100%;background:#000}iframe{display:block;border:0;width:100%;height:100%}</style></head><body><iframe src="'
            . esc_attr($video['embed_url']) . '" title="' . esc_attr($video['name']) . '" allow="autoplay; encrypted-media; picture-in-picture; fullscreen" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe></body></html>';
    }
    return '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>'
        . esc_html($video['name']) . '</title><style>html,body{margin:0;height:100%;background:#000}video{display:block;width:100%;height:100%;object-fit:contain}video:focus-visible{outline:3px solid white;outline-offset:-3px}</style></head><body><video controls playsinline preload="none" poster="'
        . esc_attr($video['thumbnail_url']) . '" aria-label="' . esc_attr($video['name']) . '"><source src="'
        . esc_attr($video['content_url']) . '" type="' . esc_attr($video['mime_type']) . '"></video></body></html>';
}

function ksem_serve_player(): void
{
    if (!isset($_GET['ksem_player'])) { return; }
    $post_id = absint($_GET['ksem_player']);
    $post = get_post($post_id);
    $video = ksem_video_data($post_id);
    if (!$post instanceof WP_Post || $post->post_type !== 'page' || $post->post_status !== 'publish'
        || !empty($post->post_password) || !get_post_meta($post_id, '_ksem_enabled', true)
        || (empty($video['content_url']) && empty($video['embed_url'])) || empty($video['thumbnail_url']) || empty($video['name'])) {
        status_header(404); exit;
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    echo ksem_native_player_html($video); exit;
}
add_action('template_redirect', 'ksem_serve_player', 0);

function ksem_video_seed(): array
{
    $path = KSEM_DIR . 'data/video-seed.json';
    $seed = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
    return is_array($seed) ? $seed : array();
}

function ksem_video_record_ready(array $record): bool
{
    if (empty($record['post_id']) || empty($record['video_verified']) || empty($record['evidence_source']) || empty($record['evidence_date'])) { return false; }
    $input = array();
    foreach (ksem_video_fields() as $key => $type) { $input[substr($key, 12)] = ksem_sanitize_video($key, $record[substr($key, 6)] ?? ''); }
    return !empty($input['name']) && !empty($input['thumbnail_url']) && !empty($input['upload_date'])
        && (!empty($input['embed_url']) || (!empty($input['content_url']) && !empty($input['mime_type'])))
        && (empty($input['player_card']) || (!empty($input['width']) && !empty($input['height'])));
}

function ksem_video_import_ready(array $seed): bool
{
    if (empty($seed['records']) || !empty($seed['unresolved'])) { return false; }
    $ids = array();
    foreach ($seed['records'] as $record) {
        $post_id = absint($record['post_id'] ?? 0);
        $post = get_post($post_id);
        if (!ksem_video_record_ready($record) || isset($ids[$post_id]) || !$post instanceof WP_Post
            || $post->post_type !== 'page' || $post->post_status !== 'publish' || !empty($post->post_password)
            || !get_post_meta($post_id, '_ksem_enabled', true)
            || get_permalink($post_id) !== ($record['page_url'] ?? '')) { return false; }
        $ids[$post_id] = true;
    }
    return true;
}

function ksem_video_import_page(): void
{
    $seed = ksem_video_seed();
    $ready = ksem_video_import_ready($seed);
    echo '<div class="wrap"><h1>EPK verified video references</h1><p>This import changes video metadata only. It preserves existing music metadata and page bodies. Songs without a verified video are excluded.</p>';
    if (isset($_GET['ksem_video_imported'])) { echo '<div class="notice notice-success"><p>Verified video fields saved. Verify public output independently.</p></div>'; }
    echo '<p>Records: ' . count($seed['records'] ?? array()) . '. State: ' . ($ready ? 'ready' : 'blocked: inspect evidence, targets and required fields') . '.</p><table class="widefat"><thead><tr><th>Song EPK</th><th>Video</th><th>Evidence date</th></tr></thead><tbody>';
    foreach ($seed['records'] ?? array() as $r) {
        echo '<tr><td>' . esc_html($r['song'] ?? '') . '</td><td>' . esc_html($r['video_name'] ?? '') . '</td><td>' . esc_html($r['evidence_date'] ?? '') . '</td></tr>';
    }
    echo '</tbody></table>';
    if ($ready) {
        echo '<form method="post" action="' . esc_attr(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ksem_import_videos">';
        wp_nonce_field('ksem_import_videos');
        submit_button('Import verified video references');
        echo '</form>';
    }
    echo '</div>';
}

add_action('admin_menu', static function (): void {
    add_management_page('EPK verified video references', 'EPK video references', 'manage_options', 'ksem-videos', 'ksem_video_import_page');
});

add_action('admin_post_ksem_import_videos', static function (): void {
    if (!current_user_can('manage_options')) { wp_die('Not allowed.'); }
    check_admin_referer('ksem_import_videos');
    $seed = ksem_video_seed();
    if (!ksem_video_import_ready($seed)) { wp_die('Video import blocked: refresh evidence and targets.'); }
    foreach ($seed['records'] as $record) { ksem_save_video(absint($record['post_id']), $record); }
    wp_safe_redirect(add_query_arg(array('page' => 'ksem-videos', 'ksem_video_imported' => 1), admin_url('tools.php')));
    exit;
});
