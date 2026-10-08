# Kieran EPK Social Metadata

Store each page fact once and generate descriptions, Open Graph music/image/audio/video tags, X summary or player-card metadata, and Schema.org MusicRecording, MusicAlbum or WebPage JSON-LD.

## Install

Requires WordPress with registered post-meta REST support and PHP 8.0 or later. Upload the release ZIP through WordPress Plugins → Add Plugin → Upload Plugin, then activate. Edit a page's EPK metadata box and enable output. Keep one owner for social metadata: another SEO plugin emitting the same tags can cause conflicting previews.

The plugin preserves WordPress document titles, canonical links and robots rules. General webpages use WebPage schema and omit music-only fields. No page bodies or templates are changed.

## Descriptive keywords and SEO fields

The optional Keyword phrases field stores one concise list, emitting a single HTML `keywords` tag and the same phrases in Schema.org `keywords`. It accepts up to eight comma- or newline-separated phrases, removes duplicates and markup, preserves UTF-8 and omits empty output. Google ignores the HTML keywords tag for indexing and ranking; descriptive keyword use does not demonstrate search demand or a ranking benefit.

References: [Google supported metadata](https://developers.google.com/search/docs/crawling-indexing/special-tags) and [Schema.org keywords](https://schema.org/keywords).

Useful SEO information is already generated from canonical facts: unique description, OG title/description/image and applicable audio/video properties, X summary/player aliases, and music or webpage JSON-LD. WordPress owns the document title, canonical link, robots directives and sitemap. Avoid adding duplicate owners or invented network-specific tags. Unknown duration, video publication facts, account handles and audio remain unavailable rather than guessed.

Tools → EPK keyword metadata imports the separately reviewed keyword seed. It checks exact published IDs/permalinks, existing required facts, the seed hash and current keyword state. It writes keywords plus any explicitly listed missing single-recording duration, supported by a dated browser-decoded observation of the exact sole MP3 URL. Durations are rounded to the nearest whole second; existing nonzero values and album or multiple-recording scopes are preserved. A missing MP3 blocks ordinary pages. The seed records the existing explicitly authorised Edie Rose no-audio state for that named page alone. Activation performs no import. The legacy catalogue importer preserves keyword metadata if its seed omits that field.

When attachment metadata lacks artwork dimensions, the plugin reads the exact existing public-upload file through WordPress's image-size helper. It checks the configured uploads URL and resolved local path, rejects traversal or external sources, and performs no remote fetch or media edit. Existing known attachment dimensions remain authoritative.

## Public song videos

The Preferred public video fields store a verified watch/reference URL, iframe embed or direct MP4/WebM URL, title, description, video-specific thumbnail, ISO 8601 first-publication timestamp, duration, player dimensions and optional verified X account attribution. Leave fields blank for songs without a publicly available verified video. The verification checkbox controls output; player cards require a separate opt-in after playback checks.

VideoObject is emitted only when the matching iframe/video is present in the saved page content and the required video title, thumbnail and publication date exist. A linked-only video can have sharing metadata but does not receive misleading embedded-video schema. Video dates and durations are independent of song release facts.

The public HTTPS `?ksem_player=PAGE_ID` endpoint provides an iframe-compatible player for enabled published pages. It requires a verified source, title and thumbnail, excludes password-protected pages, does not autoplay, and preserves the referrer required by YouTube. Player card metadata requests a preview; X and other services decide whether to display it. Optional `@handle` attribution should be verified rather than guessed.

Google generally expects a dedicated watch page for video-rich results. An EPK with a supporting video may remain a text result or appear in Google Images. No plugin can guarantee an image or a single-click player wherever a link appears.

## Imports

Tools → EPK metadata catalogue imports the bundled music facts only after its media preflight passes. Tools → EPK video references imports the separately verified video seed, changes video metadata only, and checks published page IDs, exact permalinks, evidence dates, required fields and duplicate targets. Review the records and refresh dated evidence before importing. Songs without verified sources are excluded. Legacy music imports preserve video fields when their seed has no video record.

## Validation

Run PHP lint, `tests/static-contract.php`, `tests/render-contract.php`, `tests/video-contract.php`, `tests/keyword-contract.php` and `tests/image-contract.php`. After installation, audit all EPK head fields and preserved bodies, check representative player playback, then use independent sharing checkers. Cached previews can lag a metadata change. Metadata validity does not prove search indexing, rich-result eligibility or social-player approval.

Licence: GPL-2.0-or-later.
