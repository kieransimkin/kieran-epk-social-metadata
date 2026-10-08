<?php
require __DIR__ . '/render-contract.php';
function check_keyword($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . "\n"); exit(1); }
}
function get_post($id) {
    global $keyword_post_overrides;
    $post = new WP_Post;
    $post->post_type = 'page'; $post->post_status = 'publish'; $post->post_password = '';
    foreach ($keyword_post_overrides ?? array() as $key => $value) { $post->$key = $value; }
    return $post;
}
function update_post_meta($id, $key, $value) {
    global $test_meta_values, $keyword_writes;
    $keyword_writes[] = array($id, $key, $value);
    $test_meta_values[$key] = $value;
}
function keyword_output() { ob_start(); ksem_output_metadata(); return ob_get_clean(); }
function keyword_schema($out) {
    preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $out, $m);
    return json_decode($m[1], true, 512, JSON_THROW_ON_ERROR);
}
check_keyword(ksem_clean_keywords(" song , SONG\nبازگشت آزادی, café dreams, <b>song</b>") === 'song, بازگشت آزادی, café dreams', 'Unicode/deduplication/markup contract failed');
check_keyword(ksem_clean_keywords(array('wrong shape')) === '', 'Array accepted as editable keywords');
check_keyword(ksem_clean_keywords(str_repeat('x', 101)) === '', 'Overlong phrase accepted');
check_keyword(count(explode(', ', ksem_clean_keywords('a,b,c,d,e,f,g,h,i'))) === 8, 'Unbounded phrase list');
$sanitizer = $registered_meta['_ksem_keywords']['sanitize_callback'] ?? null;
check_keyword(is_callable($sanitizer) && $sanitizer(' song, SONG ') === 'song', 'REST and editor sanitizer differ');

$test_meta_values['_ksem_keywords'] = '';
$before_output = keyword_output(); $before_schema = keyword_schema($before_output);
check_keyword(!str_contains($before_output, 'name="keywords"') && !isset($before_schema['keywords']), 'Empty keywords emitted');
$test_meta_values['_ksem_keywords'] = 'Golden Gate Bridge song, café dreams, بازگشت آزادی';
$after_output = keyword_output(); $after_schema = keyword_schema($after_output);
check_keyword(substr_count($after_output, 'name="keywords"') === 1, 'Keyword tag duplicated');
check_keyword($after_schema['keywords'] === array('Golden Gate Bridge song', 'café dreams', 'بازگشت آزادی'), 'Schema and HTML keyword lists differ');
unset($after_schema['keywords']); check_keyword($after_schema === $before_schema, 'Unrelated schema facts changed');
$head_after = preg_replace('~<script type="application/ld\+json">.*?</script>~s', 'SCHEMA-CHECKED-SEPARATELY', $after_output);
$head_before = preg_replace('~<script type="application/ld\+json">.*?</script>~s', 'SCHEMA-CHECKED-SEPARATELY', $before_output);
check_keyword(preg_replace('/<meta name="keywords"[^>]+>\n/', '', $head_after) === $head_before, 'Unrelated head fields changed');
$test_meta_values['_ksem_keywords'] = '</script><script>alert("x")</script>, quotation " & café';
check_keyword(substr_count(keyword_output(), '</script>') === 1, 'Keyword escaped JSON-LD script context');

$test_meta_values['_ksem_object_type'] = 'music.song';
$test_meta_values['_ksem_keywords'] = '';
$record = array('post_id' => 839, 'song' => 'Example recording', 'page_url' => get_permalink(839), 'evidence_date' => '2026-10-08', 'keywords' => 'Golden Gate Bridge song, Kieran Simkin', 'keywords_before' => '', 'expected' => array());
foreach (array('_ksem_title', '_ksem_description', '_ksem_image_url', '_ksem_image_alt', '_ksem_audio_urls') as $key) { $record['expected'][$key] = ksem_value(839, $key); }
check_keyword(ksem_keyword_import_ready(array('records' => array($record))), 'Valid published target blocked');
check_keyword(!ksem_keyword_import_ready(array('records' => array($record, $record))), 'Duplicate target accepted');
check_keyword(!ksem_keyword_import_ready(array('records' => 'wrong shape')), 'Non-array records accepted');
check_keyword(!ksem_keyword_import_ready(array('records' => array('wrong shape'))), 'Non-array record accepted');
$bad = $record; $bad['post_id'] = '839junk'; check_keyword(!ksem_keyword_target_ready($bad), 'Coerced page ID accepted');
$bad = $record; $bad['post_id'] = -839; check_keyword(!ksem_keyword_target_ready($bad), 'Negative page ID accepted');
$bad = $record; $bad['evidence_date'] = '2026-02-31'; check_keyword(!ksem_keyword_target_ready($bad), 'Invalid calendar date accepted');
$bad = $record; unset($bad['expected']['_ksem_description']); check_keyword(!ksem_keyword_target_ready($bad), 'Missing expected fact accepted');
$bad = $record; $bad['page_url'] .= 'wrong/'; check_keyword(!ksem_keyword_target_ready($bad), 'Wrong permalink accepted');
$bad = $record; $bad['expected']['_ksem_title'] = 'Old title'; check_keyword(!ksem_keyword_target_ready($bad), 'Stale canonical fact accepted');
$keyword_post_overrides = array('post_status' => 'draft'); check_keyword(!ksem_keyword_target_ready($record), 'Draft accepted');
$keyword_post_overrides = array('post_password' => 'secret'); check_keyword(!ksem_keyword_target_ready($record), 'Password-protected page accepted');
$keyword_post_overrides = array();
$test_meta_values['_ksem_keywords'] = 'Concurrent keywords'; check_keyword(!ksem_keyword_target_ready($record), 'Concurrent keyword edit accepted');
$test_meta_values['_ksem_keywords'] = '';
$preserved = $test_meta_values; $keyword_writes = array();
check_keyword(ksem_import_keyword_record($record), 'Keyword Save/readback failed');
check_keyword($keyword_writes === array(array(839, '_ksem_keywords', $record['keywords'])), 'Importer wrote another field');
$reversed = $test_meta_values; $reversed['_ksem_keywords'] = '';
check_keyword($reversed === $preserved, 'Other music/video facts changed');
check_keyword(ksem_keyword_target_ready($record), 'Exact repeat is not idempotent');

$test_meta_values['_ksem_keywords'] = '';
$test_meta_values['_ksem_audio_urls'] = ''; $record['expected']['_ksem_audio_urls'] = '';
check_keyword(!ksem_keyword_target_ready($record), 'Missing ordinary audio accepted');
$record['audio_exception'] = 'existing-authorised-edie-rose-no-audio-state';
check_keyword(!ksem_keyword_target_ready($record), 'Named audio exception generalised');
$record['post_id'] = 300; $record['page_url'] = 'https://kieransimkin.co.uk/edie-rose-feat-darthtwisty101s-music/';
$test_permalinks[300] = $record['page_url'];
check_keyword(ksem_keyword_target_ready($record), 'Exact recorded Edie exception blocked');
$bad = $record; $bad['audio_exception'] = ''; check_keyword(!ksem_keyword_target_ready($bad), 'Unrecorded Edie exception accepted');

$seed = ksem_keyword_seed();
check_keyword(count($seed['records'] ?? array()) === 57, 'Current EPK seed coverage incomplete');
$seen = array();
foreach ($seed['records'] as $r) {
    check_keyword(ksem_keyword_record_ready($r), 'Malformed bundled keyword record');
    check_keyword(!isset($seen[$r['post_id']]), 'Duplicate bundled keyword target');
    $seen[$r['post_id']] = true;
}
echo "Keyword Unicode, sanitization, omission, exact target, stale-data, field-preservation and named-exception contracts passed.\n";
