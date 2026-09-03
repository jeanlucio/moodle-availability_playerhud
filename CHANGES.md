# Changes

## [v1.5.0] — 2026-09-03

- Fix: the "owns item" restriction now recognises items granted through block_playerhud's item-quantity engine (drops with a quantity value, quest item rewards, trades). Previously an item held only through the new balance was invisible to the restriction, silently blocking access it should have allowed (or, for a negated restriction, allowing access it should have blocked). Revoked or spent inventory rows are no longer counted as still owned.
- Requires block_playerhud 2026090301 (v1.9.0) or later.

## [v1.4.2] — 2026-08-03

- Fix: restriction descriptions no longer render the level/quantity values unescaped, closing a stored XSS vector via a forged access restriction.
- Fix: the PlayerHUD block configuration is now read with a safe deserializer, preventing arbitrary object instantiation from a tampered block configuration.
- Fix: item and character-class restrictions are now scoped to the course's own PlayerHUD block, preventing a restriction from referencing or leaking data from another course.
- Fix: the "Student must not match the following" (negated) restriction now correctly inverts the result and its description, instead of behaving identically to the non-negated restriction.
- Fix: item and character-class restrictions now correctly follow their referenced item/class when a course is backed up and restored, instead of silently pointing at the original course.

## [v1.4.1] — 2026-06-09

- Fix: pass explicit context to `format_string()` in `get_description()` to avoid `$PAGE->context` notice when condition descriptions are loaded outside a fully initialised page context.

## [v1.4.0] — 2026-05-15

- New: "Gamification Enabled" condition — restricts access to students with gamification active in the PlayerHUD block.

## [v1.3.3] — 2026-05-13

- Update: plugin icon.

## [v1.3.2] — 2026-04-28

- Initial stable release with Moodle Plugin Directory approval.
