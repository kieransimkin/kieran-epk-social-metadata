# 1.1.0 — verified public video references

Adds canonical video reference fields, Open Graph video metadata, optional X player cards and conditional embedded VideoObject schema. The HTTPS player document supports YouTube iframe identity and native MP4/WebM with controls and no autoplay. Video-only import has target/evidence preflight and preserves existing music facts and page content. The bundled staging seed contains 35 publicly observed and locally playback-verified YouTube videos; it is not automatically imported on activation.

Preserves the 1.0.1 general-WebPage schema fix. Improves per-post editing capability checks and JSON-LD script escaping. Regression contracts cover missing videos, linked-only videos, native sources, encoded URLs, required dates, schema safety and import targets.

Requires PHP 8.0 or later. Google and social services control thumbnail/player presentation. Live deployment, signed-out audit, sharing checks and matching GitHub release are pending action-time confirmation. Light Will Win native video is excluded from this seed until its exact thumbnail upload and publication-date limitations are resolved.
