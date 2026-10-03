# Kieran EPK Social Metadata 1.0.1

## Fix

- `General webpage` now emits Schema.org `WebPage` JSON-LD with a `Person` author instead of falling through to `MusicRecording` with a `MusicGroup` artist.
- General pages no longer emit music-only Open Graph audio/release fields or Schema audio, release-date, ISRC and UPC properties, even when stale music values exist in the record.
- Song and album mappings remain unchanged.

## Verification

- The render contract covers both a complete song and a general webpage populated with stale music fields.
- The static contract requires the `WebPage` and `Person` mappings.
