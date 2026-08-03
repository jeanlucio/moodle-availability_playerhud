# 🧩 Optional Integration: Report Unlocker

PlayerHUD restrictions are normally configured and edited one activity at a time, through each
activity's own **Restrict access** panel (see [Usage](#usage)). The separate **Report Unlocker**
report plugin (`report_unlocker`, by the same author, but **not** part of the PlayerGames
family) adds a course-wide dashboard that lists every access restriction in the course —
including PlayerHUD Level, Item, Character, and Gamification conditions — with inline editing,
bulk removal, and an AI assistant for natural-language changes. Report Unlocker reads and writes
the same `{course_modules}.availability` JSON this plugin produces, always scoped to the
course's own PlayerHUD block instance, so nothing changes in how the restriction is stored.
Without Report Unlocker installed, restrictions still work exactly as documented in
[Usage](#usage) — only the bulk, course-wide dashboard is unavailable.

👉 https://github.com/jeanlucio/moodle-report_unlocker
