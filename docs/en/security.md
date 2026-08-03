# 🔐 Security & Compliance

* **Instance isolation:** item/class name lookups in `get_description()` and the inventory count
  in `is_available()` are always scoped to the course's own PlayerHUD block instance
  (`blockinstanceid`) — a forged `availabilityconditionsjson` payload pointing at another
  course's item or class cannot leak its name, nor can it be satisfied by inventory granted
  through a different course's block.
* **XSS-hardened rendering:** the `levelval` and `itemqty` values from the availability tree are
  cast to `int` before being persisted (`save()`) and before being rendered
  (`get_description()`) — a forged payload cannot inject HTML/JS into the "Not available
  unless..." message shown to every student, teacher, and admin viewing the course.
* **Safe deserialization:** the block's stored `configdata` is read with `unserialize_object()`,
  restricting the payload to `stdClass` — a tampered configuration can never trigger arbitrary
  object instantiation or a POP-gadget chain the way a bare `unserialize()` could.
* **Server-side re-evaluation:** `is_available()` is always recomputed from the student's live
  PlayerHUD data on every check; nothing about the "unlocked" state is trusted from the client.
* **No external API calls:** the condition only ever reads Moodle's own database.
* **Privacy-aware:** implements Moodle's `null_provider` — this plugin stores no personal data of
  its own; it only evaluates access rules against data owned by the PlayerHUD Block.

## Privacy Provider

The PlayerHUD Availability Condition implements Moodle's `null_provider` — it only **reads**
data owned and stored by the PlayerHUD Block (level, inventory, RPG class, gamification status)
to compute an access decision; it never persists any personal data of its own. See the block's
own documentation for its full GDPR export/delete coverage.
