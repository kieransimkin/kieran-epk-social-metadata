# Kieran EPK Social Metadata

Stores one canonical set of facts on each WordPress page and derives:

- standard `description` metadata;
- Open Graph core, image, audio and music-extension metadata;
- X/Twitter summary-card compatibility aliases;
- Schema.org `MusicRecording` or `MusicAlbum` JSON-LD.

The plugin deliberately does **not** create separate Facebook, LinkedIn,
Instagram, TikTok or Pinterest editor fields. Facebook and LinkedIn consume the
Open Graph values; Instagram and TikTok publish sharing APIs rather than an
HTML-page metadata vocabulary; Pinterest has no music Rich Pin type. It also
does not duplicate WordPress's document title, canonical link or robots tags.

## Catalogue import

The bundled `data/catalogue-seed.json` is a deterministic staging artefact.
Tools → EPK metadata catalogue enables its import only when every ordinary
record has a verified public MP3 URL, every missing-audio record has an explicit
`waived-no-source` status, and no unresolved local upload path remains. The
importer changes post metadata only; it does not edit EPK bodies, templates or
media.

## Potential problems

### Internationalised media URLs can disappear or be rewritten after import

- **Symptom:** the catalogue import reports success and the page receives its
  title, image and other metadata, but `og:audio` and Schema `audio` are absent;
  the editor's MP3 field is empty even though the seed contained a URL.
- **Cause:** a raw internationalised path is not a portable URI. After that was
  percent-encoded, the plugin's registered generic `sanitize_text_field`
  callback removed every `%HH` sequence during `update_post_meta`, leaving a
  plausible-looking but non-existent dash-only URL. WordPress documents that
  its text-field sanitizer removes percent-encoded characters.
- **Action:** percent-encode UTF-8 path characters in the canonical seed and
  use field-aware registered-meta callbacks: `esc_url_raw` for one URL and
  `ksem_clean_url_lines` for URL lists. Do not pass stored URL fields through
  `sanitize_text_field`. Verify the encoded source URL before rebuilding and
  re-importing.
- **Verification:** an unauthenticated `HEAD` request to the encoded URL returns
  HTTP 200 with the expected MIME type and byte length; signed-out page source
  contains the URL in both `og:audio` and Schema `AudioObject.contentUrl`.
- **Sources checked 23 September 2026:**
  <https://developer.wordpress.org/reference/functions/_sanitize_text_fields/>
  and <https://developer.wordpress.org/reference/functions/esc_url_raw/>.

### An uploaded ZIP can install but fail activation with `Plugin file does not exist`

- **Symptom:** WordPress reports `Plugin installed successfully`, but the
  activation link returns `Plugin file does not exist` and names a path with an
  archive directory above the actual plugin directory.
- **Cause:** the package contains an extra or mismatched directory layer, or
  Windows-style ZIP entry separators, so the main plugin file ends up more than
  one directory below `wp-content/plugins`. WordPress only discovers main
  plugin files in the plugins directory or one directory beneath it.
- **Action:** build with `package_kieran_epk_social_metadata.py`. The packager
  writes one `kieran-epk-social-metadata/` root, uses forward slashes, requires
  `kieran-epk-social-metadata.php` directly inside that root, checks the ZIP
  CRC, and refuses to overwrite an existing release artefact. Remove any
  inactive broken installation only after specific confirmation, then upload
  the newly hashed package.
- **Verification:** inspect the archive entry names before upload; after
  installation, the activation link must identify
  `kieran-epk-social-metadata/kieran-epk-social-metadata.php`, and the Installed
  Plugins page must show version 1.0.0 as active. WordPress's Plugin Handbook
  says the main plugin file belongs at the root of the plugin directory:
  https://developer.wordpress.org/plugins/plugin-basics/best-practices/ .

### Another SEO plugin can emit a second set of social tags

- **Symptom:** a page source or checker shows duplicate `og:*` or `twitter:*`
  tags with conflicting values.
- **Cause:** Yoast, Rank Math, All in One SEO or another social plugin also owns
  the page head.
- **Action:** disable that plugin's social output for the EPK pages, or disable
  this plugin for those pages. Do not keep two editable copies of each fact.
- **Verification:** signed-out page source contains one Kieran EPK marker and
  exactly one value for every single-valued social property.

### OpenGraph.to can temporarily rate-limit repeated new scans

- **Symptom:** the checker says `Too many new scans from your IP` and gives a
  retry time.
- **Cause:** rapid scans of many different URLs from one address.
- **Action:** use a representative sample in the external checker and retain the
  complete deterministic source audit for the catalogue.
- **Verification:** after the stated window, a fresh representative URL reaches
  a normal result page; the source audit still covers all published EPKs.

### A staged catalogue must never guess public media URLs

- **Symptom:** Tools → EPK metadata catalogue reports that the seed is blocked.
- **Cause:** at least one MP3 still has only a local path, or the seed records an
  unresolved source.
- **Action:** upload the exact hashed file through the WordPress media library,
  verify its attachment identity and public URL, then rebuild and re-hash the
  package before importing.
- **Verification:** all records have at least one public HTTPS MP3 URL and the
  import page says `ready to import`.

### Host-level capacity is required before a media batch

- **Symptom:** WordPress Site Health lists directory sizes but no remaining disk
  space or quota percentage.
- **Cause:** Site Health's directory table measures use, not free capacity.
- **Action:** obtain free bytes or quota utilisation from the host control panel,
  filesystem monitor or server command. Keep uploads blocked if this is
  unavailable, below 1 GiB/10%, or otherwise near the documented site limits.
- **Verification:** record the host-level value and timestamp before uploading;
  after capacity is safe, verify one small upload before the full batch.
