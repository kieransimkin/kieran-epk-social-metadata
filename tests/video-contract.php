<?php
require __DIR__ . '/render-contract.php';
function get_post_field($field, $id) { global $test_content; return $test_content ?? ''; }
function update_post_meta($id, $key, $value) { global $test_meta_values; $test_meta_values[$key] = $value; }
function home_url($path) { return 'https://kieransimkin.co.uk' . $path; }
function add_query_arg($key, $value, $url) { return $url . '?' . $key . '=' . $value; }
function get_post($id) { $p = new WP_Post; $p->post_type = 'page'; $p->post_status = 'publish'; $p->post_password = ''; return $p; }
function assert_video($condition, $message) { if (!$condition) { fwrite(STDERR, $message . "\n"); exit(1); } }
function render_video_output() { ob_start(); ksem_output_metadata(); return ob_get_clean(); }
function schema_from_output($output) { preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $output, $m); return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR); }

$test_meta_values['_ksem_object_type'] = 'music.song';
assert_video(!str_contains(render_video_output(), 'og:video'), 'No-video page emitted video tags');
$record = array(
    'video_verified' => true, 'video_url' => 'https://www.youtube.com/watch?v=example',
    'video_embed_url' => 'https://www.youtube.com/embed/example', 'video_content_url' => '',
    'video_name' => 'Example official video', 'video_description' => '</script><script>alert(1)</script>',
    'video_thumbnail_url' => 'https://kieransimkin.co.uk/thumbnail.jpg',
    'video_upload_date' => '2026-09-23T13:29:50Z', 'video_duration_seconds' => 264,
    'video_width' => 1920, 'video_height' => 1080, 'video_mime_type' => '', 'video_player_card' => true,
);
ksem_save_video(839, $record);
$test_content = '<a href="https://www.youtube.com/embed/example">Watch</a>';
$out = render_video_output();
assert_video(str_contains($out, 'content="player"') && str_contains($out, 'name="twitter:player"'), 'Complete opted-in video did not emit player card');
assert_video(substr_count($out, 'property="og:video"') === 1, 'Video OG singleton failed');
assert_video(!isset(schema_from_output($out)['video']), 'Link-only EPK claimed an embedded VideoObject');
$test_content = '<iframe src="https://www.youtube.com/embed/example"></iframe>';
$schema = schema_from_output(render_video_output());
assert_video(($schema['video']['duration'] ?? '') === 'PT4M24S', 'Video reused song duration');
assert_video(!isset($schema['video']['contentUrl']), 'YouTube watch/player link labelled direct video bytes');
assert_video(($schema['video']['uploadDate'] ?? '') === $record['video_upload_date'], 'Video reused release date');
assert_video(substr_count(render_video_output(), '</script>') === 1, 'Video description escaped JSON-LD script context');
assert_video(!ksem_video_is_embedded(839, array('embed_url' => 'https://elsewhere.test/embed')), 'Unrelated player matched');
$test_content = '<!-- <iframe src="https://www.youtube.com/embed/example"></iframe> -->';
assert_video(!ksem_video_is_embedded(839, ksem_video_data(839)), 'Commented-out video claimed on-page presence');
foreach (array('2026-02-30T10:00:00Z', '2026-09-23', '2026-09-23T25:00:00Z', '2026-09-23T10:00:00+14:30') as $bad) {
    assert_video(ksem_sanitize_video('_ksem_video_upload_date', $bad) === '', 'Invalid date accepted: ' . $bad);
}
$encoded = 'https://kieransimkin.co.uk/%D9%85%D9%88%D8%B4.mp4';
assert_video($registered_meta['_ksem_video_content_url']['sanitize_callback']($encoded) === $encoded, 'Video URL percent-encoding lost through REST sanitizer');
assert_video(ksem_sanitize_video('_ksem_video_content_url', 'javascript:alert(1)') === '', 'Unsafe protocol accepted');
assert_video(ksem_sanitize_video('_ksem_video_mime_type', 'text/html') === '', 'HTML accepted as direct video');
ksem_save_video(839, array_merge($record, array('video_upload_date' => '')));
$test_content = '<iframe src="https://www.youtube.com/embed/example"></iframe>';
assert_video(!isset(schema_from_output(render_video_output())['video']), 'Incomplete Google video emitted');
ksem_save_video(839, array_merge($record, array('video_verified' => false)));
assert_video(!str_contains(render_video_output(), 'og:video'), 'Unverified video emitted');
ksem_save_video(839, array_merge($record, array('video_embed_url' => '', 'video_content_url' => $encoded, 'video_mime_type' => 'video/mp4')));
$test_content = '<video controls><source src="' . $encoded . '" type="video/mp4"></video>';
$schema = schema_from_output(render_video_output());
assert_video(($schema['video']['contentUrl'] ?? '') === $encoded, 'Native video content URL missing');
$test_content = '<audio><source src="' . $encoded . '"></audio>';
assert_video(!ksem_video_is_embedded(839, ksem_video_data(839)), 'Audio source claimed to be an embedded video');
$html = ksem_native_player_html(ksem_video_data(839));
assert_video(str_contains($html, 'controls playsinline preload="none"') && !str_contains($html, ' autoplay'), 'Native player autoplay/control contract failed');
assert_video(str_contains(ksem_video_player_url(839, ksem_video_data(839)), '?ksem_player=839'), 'Same-site iframe URL missing');
$iframe_html = ksem_native_player_html(array_merge(ksem_video_data(839), array('content_url' => '', 'embed_url' => $record['video_embed_url'])));
assert_video(str_contains($iframe_html, 'referrerpolicy="strict-origin-when-cross-origin"'), 'YouTube referrer contract missing');
$test_meta_values['_ksem_enabled'] = 1;
assert_video(ksem_sanitize_video('_ksem_video_x_site', '@valid_handle') === '@valid_handle', 'Valid X attribution rejected');
assert_video(ksem_sanitize_video('_ksem_video_x_site', 'https://x.com/guess') === '', 'Unverified X URL treated as a handle');
$seed = ksem_video_seed();
assert_video(count($seed['records']) === 35, 'Unexpected staged video catalogue size');
foreach ($seed['records'] as $r) { assert_video(ksem_video_record_ready($r), 'Video seed record incomplete: ' . $r['post_id']); }
assert_video(!ksem_video_import_ready(array('records' => array($seed['records'][0], $seed['records'][0]))), 'Duplicate/incorrect import target accepted');
echo "Video metadata, omission, sanitization, native/iframe player and staging contracts passed.\n";
