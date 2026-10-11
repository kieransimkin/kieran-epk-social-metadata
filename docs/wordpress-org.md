# WordPress.org publication

Run the official Plugin Check against the exact production ZIP with the intended directory slug. Do not scan tests as installed runtime code, ignore an entire category or treat a passing static scan as a security audit. Review all warnings and document each contextual finding. Keep the readme stable tag, main PHP version and GitHub release aligned. After directory approval, publish the same verified source through the assigned SVN repository; verify the downloaded directory ZIP independently. No directory password belongs in source.

## Potential problems

### Direct CSS in the isolated player fails directory review

The isolated video document originally printed CSS directly in `<style>` tags for both native video and iframe variants. Version 1.2.4 moves the same presentation to `assets/player.css`, enqueues the `ksem-player` handle and prints that handle through WordPress's style loader. The production package includes the stylesheet, and regression checks reject direct runtime style blocks. Verify both variants on a clean WordPress installation with debugging enabled; a static checker alone cannot prove the endpoint rendered the queued style. References: https://developer.wordpress.org/reference/functions/wp_enqueue_style/ and https://developer.wordpress.org/reference/functions/wp_print_styles/ (accessed 11 October 2026).

On 8 October 2026 the official checker found missing directory readme fields and source-domain/output warnings. The complete report is retained privately by the release operator. WordPress common-issues guidance requires matching stable versions, GPL-compatible declarations and source links for compiled code. Source: https://developer.wordpress.org/plugins/wordpress-org/common-issues/ (accessed 8 October 2026).

The complete player document escapes dynamic fields within its builder; escaping its return again would break its intended markup. This exception is annotated at the exact operation rather than hidden by broad exclusions.

Read-only admin result notices and the public published-page player endpoint do not save data; requiring a user nonce would prevent public sharing services from reading the player. These exceptions are annotated only at the relevant reads, and must be re-reviewed whenever handlers change. Save/import handlers still verify nonces and permissions. The editor rejects nested values and sanitizes every field according to its type before storage, preserving percent-encoded media URLs and multiline descriptions. The array-level scanner cannot infer that field-specific sanitation; its narrow annotation explains the order instead of applying a URL-damaging generic sanitizer. Regression tests check nested-input rejection, encoded URLs and invalid-nonce rejection. `AGENTS.md` remains in the source repository but is not shipped as installed runtime content.
