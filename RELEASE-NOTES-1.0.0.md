# Kieran EPK Social Metadata 1.0.0

Initial release.

- Adds one canonical per-page editor for EPK title, description, music object,
  cover artwork, MP3 URL(s), release date, optional duration, ISRCs, UPC,
  official release links, site name and locale.
- Emits Open Graph core, image, audio and music-extension metadata.
- Emits X/Twitter summary-card compatibility aliases from the same fields.
- Emits Schema.org `MusicRecording` or `MusicAlbum` JSON-LD.
- Includes a deterministic 54-page catalogue importer.
- Stores internationalised media paths as percent-encoded URIs, with
  URL-specific registered-meta callbacks that preserve `%HH` sequences for
  Open Graph and Schema.org output.
- Preserves WordPress ownership of the document title, canonical link and
  robots tags.
- Does not create unsupported Instagram, TikTok or Pinterest music metadata,
  an unverified Facebook app ID, redundant HTTPS URL aliases or public rights
  metadata.

Verification before packaging:

- PHP syntax check passed.
- Static metadata contract passed.
- Rendered metadata contract passed.
- Catalogue validation passed for 54 unique pages.
- 53 records contain a verified public WordPress MP3; Edie Rose is the one
  user-authorised no-audio exception because no source master or derivative is
  available.
- The 28 newly uploaded MP3 URLs returned HTTP 200 with `audio/mpeg`.

The adjacent checksum manifest is authoritative for the packaged ZIP hash.
