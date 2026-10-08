<?php
if (!defined('ABSPATH')) { exit; }
/** Optional descriptive keywords; no search-ranking claim or automatic import. */

function ksem_clean_keywords($value): string
{
    if (!is_scalar($value)) { return ''; }
    $phrases = array();
    $seen = array();
    foreach (preg_split('/[,\r\n]+/u', (string) $value) ?: array() as $phrase) {
        $phrase = sanitize_text_field(wp_strip_all_tags($phrase));
        $phrase = preg_replace('/\s+/u', ' ', trim($phrase)) ?? '';
        if ($phrase === '' || preg_match_all('/./us', $phrase) > 100) { continue; }
        $key = function_exists('mb_strtolower') ? mb_strtolower($phrase, 'UTF-8') : strtolower($phrase);
        if (isset($seen[$key])) { continue; }
        $seen[$key] = true;
        $phrases[] = $phrase;
        if (count($phrases) === 8) { break; }
    }
    return implode(', ', $phrases);
}

function ksem_render_keyword_field(int $post_id): void
{
    echo '<p><label for="ksem-keywords"><strong>Keyword phrases (optional)</strong></label></p>';
    echo '<textarea class="widefat" id="ksem-keywords" name="ksem[keywords]" rows="2">' . esc_textarea(ksem_value($post_id, '_ksem_keywords')) . '</textarea>';
    echo '<p class="description">Up to eight concise, truthful comma-separated phrases. One canonical list feeds the HTML keywords tag and Schema.org keywords. Google ignores the HTML tag for indexing and ranking. Keep the useful wording in the description and page content; this list does not prove keyword demand.</p>';
}

function ksem_keyword_seed(): array
{
    $path = KSEM_DIR . 'data/keyword-seed.json';
    $seed = is_readable($path) ? json_decode((string) file_get_contents($path), true) : null;
    return is_array($seed) ? $seed : array();
}

function ksem_keyword_record_ready(array $record): bool
{
    $keywords = $record['keywords'] ?? null;
    $evidence_date = (string) ($record['evidence_date'] ?? '');
    $parsed_date = DateTimeImmutable::createFromFormat('!Y-m-d', $evidence_date);
    if (!is_string($keywords) || $keywords === '' || ksem_clean_keywords($keywords) !== $keywords
        || !is_int($record['post_id'] ?? null) || $record['post_id'] < 1
        || !is_string($record['page_url'] ?? null) || $record['page_url'] === ''
        || !is_string($record['song'] ?? null) || $record['song'] === ''
        || !is_string($record['keywords_before'] ?? '')
        || !$parsed_date || $parsed_date->format('Y-m-d') !== $evidence_date
        || !is_array($record['expected'] ?? null)) { return false; }
    foreach (array('_ksem_title', '_ksem_description', '_ksem_image_url', '_ksem_image_alt', '_ksem_audio_urls') as $key) {
        if (!array_key_exists($key, $record['expected']) || !is_string($record['expected'][$key])) { return false; }
    }
    if (array_key_exists('duration_update', $record)) {
        $d = $record['duration_update'];
        if (!is_array($d) || !is_int($d['seconds'] ?? null) || $d['seconds'] < 1 || $d['seconds'] > 86400
            || !is_numeric($d['observed_seconds'] ?? null) || !is_finite((float) $d['observed_seconds'])
            || (int) floor((float) $d['observed_seconds'] + 0.5) !== $d['seconds']
            || ($d['source_scope'] ?? '') !== 'browser-decoded public MP3'
            || ($record['expected']['_ksem_object_type'] ?? '') !== 'music.song'
            || !is_string($record['expected']['_ksem_duration_seconds'] ?? null)
            || !in_array($record['expected']['_ksem_duration_seconds'], array('', '0'), true)
            || ksem_lines($record['expected']['_ksem_audio_urls']) !== array($d['mp3_url'] ?? '')
            || ($d['evidence_date'] ?? '') !== $record['evidence_date']) { return false; }
    }
    return true;
}

function ksem_keyword_target_ready(array $record): bool
{
    if (!ksem_keyword_record_ready($record)) { return false; }
    $post_id = absint($record['post_id']);
    $post = get_post($post_id);
    if (!$post instanceof WP_Post || $post->post_type !== 'page' || $post->post_status !== 'publish'
        || !empty($post->post_password) || !get_post_meta($post_id, '_ksem_enabled', true)
        || get_permalink($post_id) !== $record['page_url']) { return false; }
    foreach ($record['expected'] as $key => $value) {
        $duration_repeat = $key === '_ksem_duration_seconds' && isset($record['duration_update'])
            && ksem_value($post_id, $key) === (string) $record['duration_update']['seconds'];
        if (!in_array($key, array('_ksem_title', '_ksem_description', '_ksem_image_url', '_ksem_image_alt', '_ksem_audio_urls', '_ksem_duration_seconds', '_ksem_object_type'), true)
            || (ksem_value($post_id, $key) !== $value && !$duration_repeat)) { return false; }
    }
    foreach (array('_ksem_title', '_ksem_description', '_ksem_image_alt') as $key) {
        if (trim(ksem_value($post_id, $key)) === '') { return false; }
    }
    $image = ksem_value($post_id, '_ksem_image_url');
    if ($image === '' || esc_url_raw($image, array('https')) !== $image) { return false; }
    $audio = ksem_lines(ksem_value($post_id, '_ksem_audio_urls'));
    $named_exception = $post_id === 300
        && $record['page_url'] === 'https://kieransimkin.co.uk/edie-rose-feat-darthtwisty101s-music/'
        && ($record['audio_exception'] ?? '') === 'existing-authorised-edie-rose-no-audio-state';
    if (!$audio && !$named_exception) { return false; }
    foreach ($audio as $url) {
        if (esc_url_raw($url, array('https')) !== $url || !preg_match('/\.mp3(?:\?|$)/i', $url)) { return false; }
    }
    $current = ksem_value($post_id, '_ksem_keywords');
    return $current === ($record['keywords_before'] ?? '') || $current === $record['keywords'];
}

function ksem_keyword_import_ready(array $seed): bool
{
    if (!is_array($seed['records'] ?? null) || !$seed['records'] || !empty($seed['unresolved'])) { return false; }
    $seen = array();
    foreach ($seed['records'] as $record) {
        if (!is_array($record)) { return false; }
        $id = absint($record['post_id'] ?? 0);
        if (isset($seen[$id]) || !ksem_keyword_target_ready($record)) { return false; }
        $seen[$id] = true;
    }
    return true;
}

function ksem_import_keyword_record(array $record): bool
{
    if (!ksem_keyword_target_ready($record)) { return false; }
    $id = absint($record['post_id']);
    update_post_meta($id, '_ksem_keywords', $record['keywords']);
    if (isset($record['duration_update'])) {
        update_post_meta($id, '_ksem_duration_seconds', $record['duration_update']['seconds']);
        if (ksem_value($id, '_ksem_duration_seconds') !== (string) $record['duration_update']['seconds']) { return false; }
    }
    return ksem_value($id, '_ksem_keywords') === $record['keywords'];
}

function ksem_keyword_import_page(): void
{
    $seed = ksem_keyword_seed();
    $ready = ksem_keyword_import_ready($seed);
    echo '<div class="wrap"><h1>EPK keyword metadata</h1><p>This import writes reviewed keyword phrases and separately evidenced missing single-recording durations. Google ignores the HTML keywords tag for ranking. Existing descriptions, titles, canonical URLs, artwork, audio URLs, video fields, page bodies and motion remain untouched.</p>';
    if (isset($_GET['ksem_keywords_imported'])) {
        echo '<div class="notice notice-info"><p>Keyword import attempted: ' . esc_html(absint($_GET['updated'] ?? 0)) . ' verified, ' . esc_html(absint($_GET['failed'] ?? 0)) . ' failed. Verify anonymous public metadata independently before reporting completion.</p></div>';
    }
    echo '<p>Records: ' . esc_html(count($seed['records'] ?? array())) . '. State: ' . ($ready ? 'ready' : 'blocked: refresh exact targets, required facts or keywords') . '.</p><table class="widefat"><thead><tr><th>Song EPK</th><th>Keyword phrases</th><th>Missing duration update</th><th>Evidence date</th></tr></thead><tbody>';
    foreach ($seed['records'] ?? array() as $r) {
        echo '<tr><td>' . esc_html($r['song'] ?? '') . '</td><td>' . esc_html($r['keywords'] ?? '') . '</td><td>' . esc_html(isset($r['duration_update']) ? $r['duration_update']['seconds'] . ' seconds (exact public MP3)' : 'No duration change') . '</td><td>' . esc_html($r['evidence_date'] ?? '') . '</td></tr>';
    }
    echo '</tbody></table>';
    if ($ready) {
        echo '<form method="post" action="' . esc_attr(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="ksem_import_keywords">';
        echo '<input type="hidden" name="seed_sha256" value="' . esc_attr(hash_file('sha256', KSEM_DIR . 'data/keyword-seed.json')) . '">';
        wp_nonce_field('ksem_import_keywords');
        submit_button('Import reviewed keyword phrases');
        echo '</form>';
    }
    echo '</div>';
}

add_action('admin_menu', static function (): void {
    add_management_page('EPK keyword metadata', 'EPK keyword metadata', 'manage_options', 'ksem-keywords', 'ksem_keyword_import_page');
});

add_action('admin_post_ksem_import_keywords', static function (): void {
    if (!current_user_can('manage_options')) { wp_die('Not allowed.'); }
    check_admin_referer('ksem_import_keywords');
    $seed = ksem_keyword_seed();
    $submitted = sanitize_text_field(wp_unslash($_POST['seed_sha256'] ?? ''));
    if (!hash_equals(hash_file('sha256', KSEM_DIR . 'data/keyword-seed.json'), $submitted) || !ksem_keyword_import_ready($seed)) {
        wp_die('Keyword import blocked: refresh exact seed, evidence and required facts.');
    }
    $updated = 0; $failed = 0;
    foreach ($seed['records'] as $record) {
        if (ksem_import_keyword_record($record)) { $updated++; } else { $failed++; }
    }
    wp_safe_redirect(add_query_arg(array('page' => 'ksem-keywords', 'ksem_keywords_imported' => 1, 'updated' => $updated, 'failed' => $failed), admin_url('tools.php')));
    exit;
});
