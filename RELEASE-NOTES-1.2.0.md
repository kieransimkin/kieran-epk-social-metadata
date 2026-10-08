# 1.2.0

Adds one optional keyword field for descriptive HTML and Schema.org metadata. Empty lists are omitted; concise UTF-8 phrases are sanitised and deduplicated. Google ignores the HTML keywords tag for indexing and ranking, so this release makes no ranking claim.

A dedicated reviewed keyword import checks published IDs/permalinks, required current facts, the exact seed hash and existing keyword state. It updates only keyword metadata, preserving descriptions, video facts, content and motion. The existing named Edie Rose no-audio state remains explicit. Legacy clients/imports that omit the new field preserve it.

The bundled music catalogue is refreshed to the current 57 public EPKs and verified descriptions. Activation performs no import. The keyword import does not invoke the full music importer.

Validation: PHP lint and static, rendered, video and keyword contracts; Unicode/escaping, blank omission, bounded lists, duplicate/incorrect/stale target rejection, missing ordinary audio rejection, exact exception and unrelated-property preservation. Production deployment and independent public audit are recorded separately from these package tests.
