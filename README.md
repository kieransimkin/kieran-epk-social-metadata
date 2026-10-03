# Kieran EPK Social Metadata

Store each page fact once and generate descriptions, Open Graph music/image/audio/video tags, X summary or player-card metadata, and Schema.org MusicRecording, MusicAlbum or WebPage JSON-LD.

## Install

Requires WordPress with registered post-meta REST support and PHP 8.0 or later. Upload the release ZIP through WordPress Plugins → Add Plugin → Upload Plugin, then activate. Edit a page's EPK metadata box and enable output. Keep one owner for social metadata: another SEO plugin emitting the same tags can cause conflicting previews.

The plugin preserves WordPress document titles, canonical links and robots rules. General webpages use WebPage schema and omit music-only fields. No page bodies or templates are changed.

## Public song videos

The Preferred public video fields store a verified watch/reference URL, iframe embed or direct MP4/WebM URL, title, description, video-specific thumbnail, ISO 8601 first-publication timestamp, duration, player dimensions and optional verified X account attribution. Leave fields blank for songs without a publicly available verified video. The verification checkbox controls output; player cards require a separate opt-in after playback checks.

VideoObject is emitted only when the matching iframe/video is present in the saved page content and the required video title, thumbnail and publication date exist. A linked-only video can have sharing metadata but does not receive misleading embedded-video schema. Video dates and durations are independent of song release facts.

The public HTTPS `?ksem_player=PAGE_ID` endpoint provides an iframe-compatible player for enabled published pages. It requires a verified source, title and thumbnail, excludes password-protected pages, does not autoplay, and preserves the referrer required by YouTube. Player card metadata requests a preview; X and other services decide whether to display it. Optional `@handle` attribution should be verified rather than guessed.

Google generally expects a dedicated watch page for video-rich results. An EPK with a supporting video may remain a text result or appear in Google Images. No plugin can guarantee an image or a single-click player wherever a link appears.

## Imports

Tools → EPK metadata catalogue imports the bundled music facts only after its media preflight passes. Tools → EPK video references imports the separately verified video seed, changes video metadata only, and checks published page IDs, exact permalinks, evidence dates, required fields and duplicate targets. Review the records and refresh dated evidence before importing. Songs without verified sources are excluded. Legacy music imports preserve video fields when their seed has no video record.

## Validation

Run PHP lint, `tests/static-contract.php`, `tests/render-contract.php` and `tests/video-contract.php`. After installation, check public head output and player playback, then use independent sharing checkers. Cached previews can lag a metadata change. Metadata validity does not prove search indexing, rich-result eligibility or social-player approval.

Licence: GPL-2.0-or-later.
