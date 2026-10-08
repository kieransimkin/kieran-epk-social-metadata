<?php
require __DIR__ . '/render-contract.php';
function check_image($condition, $message) {
    if (!$condition) { fwrite(STDERR, $message . "\n"); exit(1); }
}
function wp_get_upload_dir() { global $image_uploads; return $image_uploads; }
function wp_getimagesize($path) { return @getimagesize($path); }
$image_folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ksem-image-' . bin2hex(random_bytes(8));
mkdir($image_folder);
$image_path = $image_folder . DIRECTORY_SEPARATOR . 'pixel space.png';
file_put_contents($image_path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a0ioAAAAASUVORK5CYII='));
$image_uploads = array('baseurl' => 'https://example.com/wp-content/uploads', 'basedir' => $image_folder, 'error' => false);
try {
    $url = $image_uploads['baseurl'] . '/pixel%20space.png';
    check_image(ksem_local_upload_dimensions($url) === array(1, 1), 'Exact encoded local upload dimensions unavailable');
    check_image(ksem_local_upload_dimensions($url . '?v=1#artwork') === array(1, 1), 'Query or fragment altered file identity');
    foreach (array('https://other.example/pixel%20space.png', $image_uploads['baseurl'] . '-other/pixel%20space.png', $image_uploads['baseurl'] . '/missing.png', $image_uploads['baseurl'] . '/../pixel.png', $image_uploads['baseurl'] . '/%2e%2e/pixel.png', $image_uploads['baseurl'] . '/%00pixel.png', $image_uploads['baseurl'] . '/%5cprivate.png') as $bad) {
        check_image(ksem_local_upload_dimensions($bad) === array(), 'Non-local, nonexistent or unsafe image source accepted');
    }
    check_image(ksem_image_data($url, 'Pixel')['width'] === 2400, 'Known attachment metadata overwritten');
    $test_attachment_id = 0;
    $data = ksem_image_data($url, 'Pixel');
    check_image($data['width'] === 1 && $data['height'] === 1 && $data['alt'] === 'Pixel', 'Missing attachment fallback or canonical alt failed');
    $image_uploads['error'] = 'Unavailable uploads';
    check_image(ksem_local_upload_dimensions($url) === array(), 'Unavailable upload directory accepted');
} finally {
    unlink($image_path);
    rmdir($image_folder);
}
echo "Exact local upload image, attachment preservation, encoded path and source-boundary contracts passed.\n";
