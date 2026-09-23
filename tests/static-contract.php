<?php
$plugin = file_get_contents(dirname(__DIR__) . '/kieran-epk-social-metadata.php');
$required = array(
    "'og:title'",
    "'og:type'",
    "'og:url'",
    "'og:image'",
    "'og:audio'",
    "'twitter:card'",
    "'twitter:image:alt'",
    "'MusicRecording'",
    "'MusicAlbum'",
    "'isrcCode'",
    "'identifier'",
    "'datePublished'",
    "'audio'",
    "\$key === '_ksem_image_url'",
    "\$key === '_ksem_audio_urls' || \$key === '_ksem_same_as'",
    "return ksem_clean_url_lines((string) \$value);",
);
foreach ($required as $needle) {
    if (strpos($plugin, $needle) === false) {
        fwrite(STDERR, "Missing contract token: {$needle}\n");
        exit(1);
    }
}
foreach (array('instagram:', 'tiktok:', 'fb:app_id', 'og:image:url', 'og:image:secure_url', 'og:audio:secure_url') as $forbidden) {
    if (strpos($plugin, $forbidden) !== false) {
        fwrite(STDERR, "Unnecessary or invented tag present: {$forbidden}\n");
        exit(1);
    }
}
if (strpos($plugin, "'sanitize_callback' => \$type === 'integer' ? 'absint' : 'sanitize_text_field'") !== false) {
    fwrite(STDERR, "URL-list metadata still uses the percent-stripping generic text sanitizer.\n");
    exit(1);
}
echo "Static metadata contract passed.\n";
