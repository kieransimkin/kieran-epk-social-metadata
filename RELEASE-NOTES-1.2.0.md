# 1.2.0

Adds one optional keyword field for descriptive HTML and Schema.org metadata. Empty lists are omitted; concise UTF-8 phrases are sanitised and deduplicated. Google ignores the HTML keywords tag for indexing and ranking, so this release makes no ranking claim.

A dedicated reviewed import checks published IDs/permalinks, required current facts, the exact seed hash and existing keyword state. It updates keyword metadata and 16 explicitly evidenced missing single-recording durations, preserving descriptions, video facts, content and motion. Duration observations match the exact sole public MP3 and are rounded to the nearest second. Existing values, album scopes and multiple-recording facts are preserved. The existing named Edie Rose no-audio state remains explicit. Legacy clients/imports that omit the new keyword field preserve it.

Missing artwork dimensions can be derived from the exact local public-upload file when the WordPress attachment record is unavailable. This uses WordPress's image-size helper with URL/path boundary checks; it performs no remote fetch or media edit.

The bundled music catalogue is refreshed to the current 57 public EPKs and verified descriptions. Activation performs no import. The keyword import does not invoke the full music importer.

Validation: PHP lint and static, rendered, video, keyword and image contracts; Unicode/escaping, blank omission, bounded lists, duplicate/incorrect/stale target rejection, missing ordinary audio rejection, exact exception, source-matched duration/rounding, safe local image paths and unrelated-property preservation. Production deployment and independent public audit are recorded separately from these package tests.
