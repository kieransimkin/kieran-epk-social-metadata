# WordPress.org publication

Run the official Plugin Check against the exact production ZIP with the intended directory slug. Do not scan tests as installed runtime code, ignore an entire category or treat a passing static scan as a security audit. Review all warnings and document each contextual finding. Keep the readme stable tag, main PHP version and GitHub release aligned. After directory approval, publish the same verified source through the assigned SVN repository; verify the downloaded directory ZIP independently. No directory password belongs in source.

## Potential problems

On 8 October 2026 the official checker found missing directory readme fields and source-domain/output warnings. The complete report is retained privately by the release operator. WordPress common-issues guidance requires matching stable versions, GPL-compatible declarations and source links for compiled code. Source: https://developer.wordpress.org/plugins/wordpress-org/common-issues/ (accessed 8 October 2026).

The complete player document escapes dynamic fields within its builder; escaping its return again would break its intended markup. This exception is annotated at the exact operation rather than hidden by broad exclusions.

The remaining nonce warnings concern read-only admin result notices and the public published-page player endpoint. They do not save data; requiring a user nonce would prevent public sharing services from reading the player. The save handler verifies its nonce and permissions before unslashing the submitted field array, then applies the registered field-specific sanitizer to each value. The array-level warning does not recognise those typed callbacks. Keep these warnings visible and re-review whenever the affected handlers change.
