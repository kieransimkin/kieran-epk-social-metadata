<?php
require __DIR__ . '/render-contract.php';
function wp_unslash($value) { return $value; }
function wp_verify_nonce($value, $action) { return $value === 'synthetic-valid'; }
function current_user_can() { return true; }
function update_post_meta($id, $key, $value) { global $editor_saved; $editor_saved[$key]=$value; }
$post=new WP_Post(); $post->post_type='page';
$url='https://example.invalid/audio%20space.mp3';
$_POST=array('ksem_nonce'=>'synthetic-valid','ksem'=>array('title'=>array('nested'=>'unexpected'),'audio_urls'=>$url,'description'=>"First line\nSecond line"));
$editor_saved=array();
ksem_save_meta_box(1,$post);
if (($editor_saved['_ksem_title']??null)!=='' || ($editor_saved['_ksem_audio_urls']??null)!==$url || ($editor_saved['_ksem_description']??null)!=="First line\nSecond line") {
    throw new RuntimeException('Editor field types, encoded URL or multiline text regressed');
}
$_POST['ksem_nonce']='invalid'; $editor_saved=array();
ksem_save_meta_box(1,$post);
if ($editor_saved) { throw new RuntimeException('Invalid nonce saved metadata'); }
echo "Editor nested-input rejection, encoded URL preservation and nonce contracts passed.\n";
