<?php
/**
 * Plugin Name: Kieran EPK Social Metadata
 * Description: Adds one canonical set of per-page music metadata and emits Open Graph, X/Twitter compatibility tags and Schema.org JSON-LD for EPK pages.
 * Version: 1.0.0
 * Author: Kieran Simkin
 * License: GPL-2.0-or-later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('KSEM_VERSION', '1.0.0');
define('KSEM_FILE', __FILE__);
define('KSEM_DIR', plugin_dir_path(__FILE__));

/**
 * The editor stores each fact once. Network-specific tags are derived aliases,
 * not separate fields which can drift apart.
 */
function ksem_meta_fields(): array
{
    return array(
        '_ksem_enabled'          => 'boolean',
        '_ksem_title'            => 'string',
        '_ksem_description'      => 'string',
        '_ksem_object_type'      => 'string',
        '_ksem_image_url'        => 'string',
        '_ksem_image_alt'        => 'string',
        '_ksem_audio_urls'       => 'string',
        '_ksem_release_date'     => 'string',
        '_ksem_duration_seconds' => 'integer',
        '_ksem_isrcs'            => 'string',
        '_ksem_upc'              => 'string',
        '_ksem_same_as'          => 'string',
        '_ksem_site_name'        => 'string',
        '_ksem_locale'           => 'string',
    );
}

function ksem_register_meta(): void
{
    foreach (ksem_meta_fields() as $key => $type) {
        $sanitize_callback = static function ($value) use ($key, $type) {
            if ($type === 'integer') {
                return absint($value);
            }
            if ($type === 'boolean') {
                return rest_sanitize_boolean($value);
            }
            if ($key === '_ksem_description') {
                return sanitize_textarea_field($value);
            }
            if ($key === '_ksem_image_url') {
                return esc_url_raw(trim((string) $value), array('https'));
            }
            if ($key === '_ksem_audio_urls' || $key === '_ksem_same_as') {
                return ksem_clean_url_lines((string) $value);
            }
            return sanitize_text_field($value);
        };

        register_post_meta('page', $key, array(
            'type'              => $type,
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => $sanitize_callback,
            'auth_callback'     => static function (): bool {
                return current_user_can('edit_pages');
            },
        ));
    }
}
add_action('init', 'ksem_register_meta');

function ksem_add_meta_box(): void
{
    add_meta_box(
        'ksem-page-metadata',
        'EPK social and music metadata',
        'ksem_render_meta_box',
        'page',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes_page', 'ksem_add_meta_box');

function ksem_value(int $post_id, string $key, string $default = ''): string
{
    $value = get_post_meta($post_id, $key, true);
    return $value === '' ? $default : (string) $value;
}

function ksem_render_meta_box(WP_Post $post): void
{
    wp_nonce_field('ksem_save_page_metadata', 'ksem_nonce');
    $enabled = (bool) get_post_meta($post->ID, '_ksem_enabled', true);
    $type = ksem_value($post->ID, '_ksem_object_type', 'music.song');
    ?>
    <p>
        <label>
            <input type="checkbox" name="ksem[enabled]" value="1" <?php checked($enabled); ?>>
            Publish metadata for this page
        </label>
    </p>
    <p class="description">
        Enter each fact once. Open Graph and X/Twitter duplicates are generated automatically.
        WordPress continues to own the document title, canonical link and robots tags.
    </p>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><label for="ksem-title">Song or release title</label></th>
            <td><input class="widefat" id="ksem-title" name="ksem[title]" type="text" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_title', get_the_title($post))); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-description">Description</label></th>
            <td><textarea class="widefat" id="ksem-description" name="ksem[description]" rows="4"><?php echo esc_textarea(ksem_value($post->ID, '_ksem_description')); ?></textarea></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-object-type">Music object</label></th>
            <td>
                <select id="ksem-object-type" name="ksem[object_type]">
                    <option value="music.song" <?php selected($type, 'music.song'); ?>>Single recording</option>
                    <option value="music.album" <?php selected($type, 'music.album'); ?>>Multi-track release</option>
                    <option value="website" <?php selected($type, 'website'); ?>>General webpage</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-image-url">Cover artwork URL</label></th>
            <td><input class="widefat" id="ksem-image-url" name="ksem[image_url]" type="url" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_image_url')); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-image-alt">Cover alt text</label></th>
            <td><input class="widefat" id="ksem-image-alt" name="ksem[image_alt]" type="text" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_image_alt')); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-audio-urls">MP3 URLs</label></th>
            <td>
                <textarea class="widefat code" id="ksem-audio-urls" name="ksem[audio_urls]" rows="3"><?php echo esc_textarea(ksem_value($post->ID, '_ksem_audio_urls')); ?></textarea>
                <p class="description">One public HTTPS MP3 URL per line; the first is preferred.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-release-date">Release date</label></th>
            <td><input id="ksem-release-date" name="ksem[release_date]" type="date" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_release_date')); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-duration">Duration</label></th>
            <td><input id="ksem-duration" min="1" name="ksem[duration_seconds]" type="number" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_duration_seconds')); ?>"> seconds</td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-isrcs">ISRCs</label></th>
            <td><input class="widefat" id="ksem-isrcs" name="ksem[isrcs]" type="text" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_isrcs')); ?>"><p class="description">Comma-separated.</p></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-upc">UPC</label></th>
            <td><input id="ksem-upc" name="ksem[upc]" type="text" inputmode="numeric" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_upc')); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-same-as">Official release links</label></th>
            <td><textarea class="widefat code" id="ksem-same-as" name="ksem[same_as]" rows="4"><?php echo esc_textarea(ksem_value($post->ID, '_ksem_same_as')); ?></textarea><p class="description">One Spotify, Apple Music, YouTube or official release URL per line.</p></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-site-name">Site name</label></th>
            <td><input id="ksem-site-name" name="ksem[site_name]" type="text" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_site_name', 'Kieran Simkin')); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="ksem-locale">Locale</label></th>
            <td><input id="ksem-locale" name="ksem[locale]" type="text" value="<?php echo esc_attr(ksem_value($post->ID, '_ksem_locale', 'en_GB')); ?>"></td>
        </tr>
    </table>
    <?php
}

function ksem_clean_url_lines(string $value): string
{
    $clean = array();
    foreach (preg_split('/\R+/', $value) ?: array() as $line) {
        $url = esc_url_raw(trim($line), array('https'));
        if ($url !== '') {
            $clean[] = $url;
        }
    }
    return implode("\n", array_values(array_unique($clean)));
}

function ksem_save_meta_box(int $post_id, WP_Post $post): void
{
    if ($post->post_type !== 'page'
        || !isset($_POST['ksem_nonce'])
        || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ksem_nonce'])), 'ksem_save_page_metadata')
        || !current_user_can('edit_page', $post_id)
        || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        || !isset($_POST['ksem'])
        || !is_array($_POST['ksem'])) {
        return;
    }

    $input = wp_unslash($_POST['ksem']);
    update_post_meta($post_id, '_ksem_enabled', isset($input['enabled']) ? 1 : 0);
    update_post_meta($post_id, '_ksem_title', sanitize_text_field($input['title'] ?? ''));
    update_post_meta($post_id, '_ksem_description', sanitize_textarea_field($input['description'] ?? ''));

    $type = sanitize_text_field($input['object_type'] ?? 'music.song');
    update_post_meta($post_id, '_ksem_object_type', in_array($type, array('music.song', 'music.album', 'website'), true) ? $type : 'music.song');
    update_post_meta($post_id, '_ksem_image_url', esc_url_raw($input['image_url'] ?? '', array('https')));
    update_post_meta($post_id, '_ksem_image_alt', sanitize_text_field($input['image_alt'] ?? ''));
    update_post_meta($post_id, '_ksem_audio_urls', ksem_clean_url_lines((string) ($input['audio_urls'] ?? '')));
    update_post_meta($post_id, '_ksem_release_date', preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($input['release_date'] ?? '')) ? $input['release_date'] : '');
    update_post_meta($post_id, '_ksem_duration_seconds', absint($input['duration_seconds'] ?? 0));
    update_post_meta($post_id, '_ksem_isrcs', sanitize_text_field($input['isrcs'] ?? ''));
    update_post_meta($post_id, '_ksem_upc', preg_replace('/\D+/', '', (string) ($input['upc'] ?? '')));
    update_post_meta($post_id, '_ksem_same_as', ksem_clean_url_lines((string) ($input['same_as'] ?? '')));
    update_post_meta($post_id, '_ksem_site_name', sanitize_text_field($input['site_name'] ?? 'Kieran Simkin'));
    update_post_meta($post_id, '_ksem_locale', sanitize_text_field($input['locale'] ?? 'en_GB'));
}
add_action('save_post_page', 'ksem_save_meta_box', 10, 2);

function ksem_lines(string $value): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R+/', $value) ?: array())));
}

function ksem_csv(string $value): array
{
    return array_values(array_filter(array_map('trim', explode(',', $value))));
}

function ksem_image_data(string $url, string $fallback_alt): array
{
    $data = array('url' => $url, 'alt' => $fallback_alt, 'type' => '', 'width' => 0, 'height' => 0);
    $attachment_id = attachment_url_to_postid($url);
    if ($attachment_id) {
        $metadata = wp_get_attachment_metadata($attachment_id);
        $data['width'] = isset($metadata['width']) ? absint($metadata['width']) : 0;
        $data['height'] = isset($metadata['height']) ? absint($metadata['height']) : 0;
        $attachment_alt = trim((string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true));
        if ($attachment_alt !== '') {
            $data['alt'] = $attachment_alt;
        }
    }
    $filetype = wp_check_filetype((string) wp_parse_url($url, PHP_URL_PATH));
    $data['type'] = (string) ($filetype['type'] ?? '');
    return $data;
}

function ksem_meta_tag(string $attribute, string $name, string $content): void
{
    if ($content !== '') {
        printf("<meta %s=\"%s\" content=\"%s\">\n", esc_attr($attribute), esc_attr($name), esc_attr($content));
    }
}

function ksem_iso_duration(int $seconds): string
{
    if ($seconds <= 0) {
        return '';
    }
    $hours = intdiv($seconds, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $remaining = $seconds % 60;
    return 'PT' . ($hours ? $hours . 'H' : '') . ($minutes ? $minutes . 'M' : '') . $remaining . 'S';
}

function ksem_output_metadata(): void
{
    if (!is_singular('page')) {
        return;
    }
    $post_id = get_queried_object_id();
    if (!$post_id || !get_post_meta($post_id, '_ksem_enabled', true)) {
        return;
    }

    $title = ksem_value($post_id, '_ksem_title', get_the_title($post_id));
    $description = ksem_value($post_id, '_ksem_description');
    $object_type = ksem_value($post_id, '_ksem_object_type', 'music.song');
    $url = get_permalink($post_id);
    $image_url = ksem_value($post_id, '_ksem_image_url');
    $image = ksem_image_data($image_url, ksem_value($post_id, '_ksem_image_alt', $title . ' cover artwork'));
    $audio_urls = ksem_lines(ksem_value($post_id, '_ksem_audio_urls'));
    $release_date = ksem_value($post_id, '_ksem_release_date');
    $duration = absint(get_post_meta($post_id, '_ksem_duration_seconds', true));
    $isrcs = ksem_csv(ksem_value($post_id, '_ksem_isrcs'));
    $upc = ksem_value($post_id, '_ksem_upc');
    $same_as = ksem_lines(ksem_value($post_id, '_ksem_same_as'));
    $site_name = ksem_value($post_id, '_ksem_site_name', 'Kieran Simkin');
    $locale = ksem_value($post_id, '_ksem_locale', 'en_GB');

    echo "<!-- Kieran EPK Social Metadata " . esc_html(KSEM_VERSION) . " -->\n";
    ksem_meta_tag('name', 'description', $description);
    ksem_meta_tag('property', 'og:title', $title);
    ksem_meta_tag('property', 'og:type', $object_type);
    ksem_meta_tag('property', 'og:url', $url);
    ksem_meta_tag('property', 'og:description', $description);
    ksem_meta_tag('property', 'og:site_name', $site_name);
    ksem_meta_tag('property', 'og:locale', $locale);
    ksem_meta_tag('property', 'og:image', $image['url']);
    ksem_meta_tag('property', 'og:image:type', $image['type']);
    ksem_meta_tag('property', 'og:image:width', $image['width'] ? (string) $image['width'] : '');
    ksem_meta_tag('property', 'og:image:height', $image['height'] ? (string) $image['height'] : '');
    ksem_meta_tag('property', 'og:image:alt', $image['alt']);
    foreach ($audio_urls as $audio_url) {
        ksem_meta_tag('property', 'og:audio', $audio_url);
        ksem_meta_tag('property', 'og:audio:type', 'audio/mpeg');
    }
    if ($object_type === 'music.song' && $duration) {
        ksem_meta_tag('property', 'music:duration', (string) $duration);
    }
    if ($object_type === 'music.album' && $release_date !== '') {
        ksem_meta_tag('property', 'music:release_date', $release_date);
    }

    // X/Twitter uses its own names for the same canonical facts.
    ksem_meta_tag('name', 'twitter:card', 'summary_large_image');
    ksem_meta_tag('name', 'twitter:title', $title);
    ksem_meta_tag('name', 'twitter:description', $description);
    ksem_meta_tag('name', 'twitter:image', $image['url']);
    ksem_meta_tag('name', 'twitter:image:alt', $image['alt']);

    $artist = array(
        '@type'  => 'MusicGroup',
        'name'   => 'Kieran Simkin',
        'url'    => 'https://kieransimkin.co.uk/',
        'sameAs' => array(
            'https://www.youtube.com/@TheSlinq',
            'https://www.instagram.com/kieransimkin/',
        ),
    );
    $schema = array(
        '@context'         => 'https://schema.org',
        '@type'            => $object_type === 'music.album' ? 'MusicAlbum' : 'MusicRecording',
        'name'             => $title,
        'description'      => $description,
        'url'              => $url,
        'mainEntityOfPage' => $url,
        'byArtist'         => $artist,
        'image'            => array_filter(array(
            '@type'   => 'ImageObject',
            'url'     => $image['url'],
            'width'   => $image['width'] ?: null,
            'height'  => $image['height'] ?: null,
            'caption' => $image['alt'],
        )),
        'datePublished'    => $release_date,
        'sameAs'           => $same_as,
    );
    if ($audio_urls) {
        $schema['audio'] = array_map(static function (string $audio_url) use ($title, $duration): array {
            return array_filter(array(
                '@type'          => 'AudioObject',
                'name'           => $title,
                'contentUrl'     => $audio_url,
                'encodingFormat' => 'audio/mpeg',
                'duration'       => ksem_iso_duration($duration),
            ));
        }, $audio_urls);
    }
    if ($object_type === 'music.song' && $isrcs) {
        $schema['isrcCode'] = count($isrcs) === 1 ? $isrcs[0] : $isrcs;
    }
    if ($object_type === 'music.album' && $isrcs) {
        $schema['numTracks'] = count($isrcs);
    }
    if ($upc !== '') {
        $schema['identifier'] = array('@type' => 'PropertyValue', 'propertyID' => 'UPC', 'value' => $upc);
    }
    $schema = array_filter($schema, static function ($value): bool {
        return !($value === '' || $value === array() || $value === null);
    });
    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";
}
add_action('wp_head', 'ksem_output_metadata', 2);

function ksem_admin_menu(): void
{
    add_management_page(
        'EPK metadata catalogue',
        'EPK metadata catalogue',
        'manage_options',
        'ksem-catalogue',
        'ksem_render_catalogue_page'
    );
}
add_action('admin_menu', 'ksem_admin_menu');

function ksem_load_seed(): array
{
    $path = KSEM_DIR . 'data/catalogue-seed.json';
    if (!is_readable($path)) {
        return array();
    }
    $decoded = json_decode((string) file_get_contents($path), true);
    return is_array($decoded) ? $decoded : array();
}

function ksem_seed_is_ready(array $seed): bool
{
    if (!empty($seed['unresolved']) || empty($seed['records'])) {
        return false;
    }
    foreach ($seed['records'] as $record) {
        $is_waived = ($record['audio_status'] ?? '') === 'waived-no-source';
        if ((!$is_waived && empty($record['audio_urls'])) || !empty($record['audio_local_paths'])) {
            return false;
        }
    }
    return true;
}

function ksem_import_record(array $record): bool
{
    $post_id = absint($record['post_id'] ?? 0);
    $post = get_post($post_id);
    if (!$post instanceof WP_Post || $post->post_type !== 'page' || $post->post_status !== 'publish') {
        return false;
    }
    update_post_meta($post_id, '_ksem_enabled', 1);
    update_post_meta($post_id, '_ksem_title', sanitize_text_field($record['title'] ?? ''));
    update_post_meta($post_id, '_ksem_description', sanitize_textarea_field($record['description'] ?? ''));
    update_post_meta($post_id, '_ksem_object_type', in_array($record['object_type'] ?? '', array('music.song', 'music.album', 'website'), true) ? $record['object_type'] : 'music.song');
    update_post_meta($post_id, '_ksem_image_url', esc_url_raw($record['image_url'] ?? '', array('https')));
    update_post_meta($post_id, '_ksem_image_alt', sanitize_text_field($record['image_alt'] ?? ''));
    update_post_meta($post_id, '_ksem_audio_urls', ksem_clean_url_lines(implode("\n", $record['audio_urls'] ?? array())));
    update_post_meta($post_id, '_ksem_release_date', sanitize_text_field($record['release_date'] ?? ''));
    update_post_meta($post_id, '_ksem_duration_seconds', absint($record['duration_seconds'] ?? 0));
    update_post_meta($post_id, '_ksem_isrcs', sanitize_text_field(implode(',', $record['isrcs'] ?? array())));
    update_post_meta($post_id, '_ksem_upc', preg_replace('/\D+/', '', (string) ($record['upc'] ?? '')));
    update_post_meta($post_id, '_ksem_same_as', ksem_clean_url_lines(implode("\n", $record['same_as'] ?? array())));
    update_post_meta($post_id, '_ksem_site_name', sanitize_text_field($record['site_name'] ?? 'Kieran Simkin'));
    update_post_meta($post_id, '_ksem_locale', sanitize_text_field($record['locale'] ?? 'en_GB'));
    return true;
}

function ksem_handle_import(): void
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You are not allowed to import this catalogue.', 'ksem'));
    }
    check_admin_referer('ksem_import_catalogue');
    $seed = ksem_load_seed();
    if (!ksem_seed_is_ready($seed)) {
        wp_safe_redirect(add_query_arg('ksem_result', 'not-ready', admin_url('tools.php?page=ksem-catalogue')));
        exit;
    }
    $updated = 0;
    foreach ($seed['records'] as $record) {
        if (ksem_import_record($record)) {
            $updated++;
        }
    }
    wp_safe_redirect(add_query_arg(array('page' => 'ksem-catalogue', 'ksem_result' => 'imported', 'updated' => $updated), admin_url('tools.php')));
    exit;
}
add_action('admin_post_ksem_import_catalogue', 'ksem_handle_import');

function ksem_render_catalogue_page(): void
{
    $seed = ksem_load_seed();
    $ready = ksem_seed_is_ready($seed);
    $count = isset($seed['records']) && is_array($seed['records']) ? count($seed['records']) : 0;
    $result = sanitize_text_field(wp_unslash($_GET['ksem_result'] ?? ''));
    ?>
    <div class="wrap">
        <h1>EPK metadata catalogue</h1>
        <?php if ($result === 'imported') : ?>
            <div class="notice notice-success"><p><?php echo esc_html(absint($_GET['updated'] ?? 0)); ?> published pages updated.</p></div>
        <?php elseif ($result === 'not-ready') : ?>
            <div class="notice notice-error"><p>The bundled catalogue is not ready: at least one page lacks a verified public MP3 URL.</p></div>
        <?php endif; ?>
        <p>Bundled records: <strong><?php echo esc_html($count); ?></strong></p>
        <p>State: <strong><?php echo $ready ? 'ready to import' : 'blocked until every staged MP3 has a verified public URL'; ?></strong></p>
        <p>The import updates metadata only. It does not alter page content, templates, permalinks or media files.</p>
        <?php if ($ready) : ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="ksem_import_catalogue">
                <?php wp_nonce_field('ksem_import_catalogue'); ?>
                <?php submit_button('Import staged catalogue'); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

function ksem_conflict_notice(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }
    if (defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION') || defined('AIOSEO_VERSION')) {
        echo '<div class="notice notice-warning"><p><strong>Kieran EPK Social Metadata:</strong> another SEO/social metadata plugin is active. Disable its social output for EPK pages before enabling this plugin to avoid duplicate tags.</p></div>';
    }
}
add_action('admin_notices', 'ksem_conflict_notice');
