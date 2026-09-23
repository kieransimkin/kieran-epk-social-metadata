<?php
define('ABSPATH', __DIR__);
class WP_Post {}
function plugin_dir_path($file) { return dirname($file) . DIRECTORY_SEPARATOR; }
function add_action() {}
function add_filter() {}
$registered_meta = array();
function register_post_meta($post_type, $key, $args) {
    global $registered_meta;
    $registered_meta[$key] = $args;
}
function is_singular($type) { return $type === 'page'; }
function get_queried_object_id() { return 839; }
function get_the_title() { return "California Screamin’"; }
function get_permalink() { return 'https://kieransimkin.co.uk/california-screamin/'; }
function get_post_meta($post_id, $key, $single = false) {
    $values = array(
        '_ksem_enabled' => 1,
        '_ksem_title' => "California Screamin’",
        '_ksem_description' => 'Warm, cinematic pop/electro with laid-back South Coast UK-rap storytelling.',
        '_ksem_object_type' => 'music.song',
        '_ksem_image_url' => 'https://kieransimkin.co.uk/wp-content/uploads/cover.jpg',
        '_ksem_image_alt' => "California Screamin’ cover artwork",
        '_ksem_audio_urls' => "https://kieransimkin.co.uk/wp-content/uploads/song.mp3",
        '_ksem_release_date' => '2027-01-15',
        '_ksem_duration_seconds' => 181,
        '_ksem_isrcs' => 'QT1234567890',
        '_ksem_upc' => '700904445807',
        '_ksem_same_as' => "https://open.spotify.com/album/example\nhttps://music.apple.com/album/example",
        '_ksem_site_name' => 'Kieran Simkin',
        '_ksem_locale' => 'en_GB',
    );
    return $values[$key] ?? '';
}
function attachment_url_to_postid() { return 42; }
function wp_get_attachment_metadata() { return array('width' => 2400, 'height' => 2400); }
function wp_check_filetype() { return array('type' => 'image/jpeg'); }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function esc_attr($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_url_raw($value, $protocols = null) {
    $value = trim((string) $value);
    return str_starts_with($value, 'https://') ? $value : '';
}
function sanitize_text_field($value) { return preg_replace('/%[a-f0-9]{2}/i', '', trim((string) $value)); }
function sanitize_textarea_field($value) { return trim((string) $value); }
function rest_sanitize_boolean($value) { return filter_var($value, FILTER_VALIDATE_BOOLEAN); }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function absint($value) { return abs((int) $value); }

require dirname(__DIR__) . '/kieran-epk-social-metadata.php';

ksem_register_meta();
$encoded_audio = 'https://kieransimkin.co.uk/wp-content/uploads/2026/09/01-Mouse-Ali-Bye-Bye-%D9%85%D9%88%D8%B4-%D8%B9%D9%84%DB%8C-320kbps.mp3';
$audio_sanitizer = $registered_meta['_ksem_audio_urls']['sanitize_callback'] ?? null;
if (!is_callable($audio_sanitizer) || $audio_sanitizer($encoded_audio) !== $encoded_audio) {
    fwrite(STDERR, "Registered audio sanitizer does not preserve the encoded media URI.\n");
    exit(1);
}

ob_start();
ksem_output_metadata();
$output = ob_get_clean();

$singletons = array(
    'property="og:title"',
    'property="og:url"',
    'property="og:image"',
    'name="twitter:card"',
    'name="twitter:title"',
    'name="twitter:image"',
    'type="application/ld+json"',
);
foreach ($singletons as $needle) {
    if (substr_count($output, $needle) !== 1) {
        fwrite(STDERR, "Expected exactly one {$needle}\n");
        exit(1);
    }
}
foreach (array('instagram:', 'tiktok:', 'fb:app_id', 'og:image:url', 'og:image:secure_url') as $forbidden) {
    if (strpos($output, $forbidden) !== false) {
        fwrite(STDERR, "Forbidden output: {$forbidden}\n");
        exit(1);
    }
}
if (!preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $output, $match)) {
    fwrite(STDERR, "JSON-LD block missing.\n");
    exit(1);
}
$schema = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
if (($schema['@type'] ?? '') !== 'MusicRecording'
    || ($schema['isrcCode'] ?? '') !== 'QT1234567890'
    || ($schema['audio'][0]['duration'] ?? '') !== 'PT3M1S') {
    fwrite(STDERR, "JSON-LD values do not match the canonical fields.\n");
    exit(1);
}
echo "Rendered metadata contract passed.\n";
