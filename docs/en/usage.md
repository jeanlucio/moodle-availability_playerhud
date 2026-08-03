# 📖 Usage

1. Add the **PlayerHUD Block** to the course (see [Installation](#installation)) — the
   restriction option only appears in courses where a PlayerHUD block instance exists.
2. Turn **Edit mode on** in the course.
3. Edit an activity, resource, or section.
4. Open the **Restrict access** section and click **Add restriction…**
5. Select **PlayerHUD**.
6. Choose the restriction type and its parameters (see the reference table below).

Students only gain access once the defined condition is met; the description shown to them is
generated dynamically from the same condition.

## Restriction Type Reference

| Type | Parameters | Access granted when |
|------|-----------|----------------------|
| **Minimum Level** | Required level | The student's current PlayerHUD level is **greater than or equal to** the configured level. |
| **Own Item** | Item, operator (`at least`, `more than`, `less than`, `exactly`), quantity | The student's inventory count for the selected item satisfies the chosen operator against the configured quantity. |
| **Character** | RPG class | The student has the selected RPG class assigned in their PlayerHUD progress. |
| **Gamification Enabled** | — | The student has not paused/opted out of gamification in the course's PlayerHUD block. |

## Notes

* The restriction is always re-evaluated server-side on every page load — there is no
  client-side caching of the "unlocked" state to go stale.
* If the PlayerHUD block is removed from the course after a restriction was configured, the
  condition safely evaluates to unavailable rather than erroring.
* Editing restrictions one activity at a time is the default flow. For a course-wide dashboard
  with inline editing and bulk removal, see the optional [Report Unlocker](#unlocker)
  integration.
