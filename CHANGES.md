# Changes

## v1.4.2 (2026080300)

- Fix: restriction descriptions no longer render the level/quantity values unescaped, closing a stored XSS vector via a forged access restriction.
- Fix: the PlayerHUD block configuration is now read with a safe deserializer, preventing arbitrary object instantiation from a tampered block configuration.
- Fix: item and character-class restrictions are now scoped to the course's own PlayerHUD block, preventing a restriction from referencing or leaking data from another course.
- Fix: the "Student must not match the following" (negated) restriction now correctly inverts the result and its description, instead of behaving identically to the non-negated restriction.
- Fix: item and character-class restrictions now correctly follow their referenced item/class when a course is backed up and restored, instead of silently pointing at the original course.

## v1.4.1 (2026060900)

- Fix: pass explicit context to `format_string()` in `get_description()` to avoid `$PAGE->context` notice when condition descriptions are loaded outside a fully initialised page context.

## v1.4.0 (2026051500)

- New: "Gamification Enabled" condition — restricts access to students with gamification active in the PlayerHUD block.

## v1.3.3 (2026051301)

- Update: plugin icon.

## v1.3.2 (2026042800)

- Initial stable release with Moodle Plugin Directory approval.
