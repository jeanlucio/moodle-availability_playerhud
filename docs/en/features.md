# ✨ Features

* 🎯 **Restrict by Minimum Level:** unlock content only once a student's PlayerHUD level reaches
  a configured threshold.
* 🎒 **Restrict by Owned Item:** unlock content based on how many units of a PlayerHUD item a
  student currently holds, with a comparison operator (`>=`, `>`, `<`, `=`).
* 🧙 **Restrict by Character Class:** unlock content only for students who have a specific RPG
  class assigned in PlayerHUD.
* 🎮 **Restrict by Gamification Status:** unlock content only for students who have gamification
  active (not paused/opted out) in the course's PlayerHUD block.
* 🧠 **Native Restrict Access Integration:** appears as a regular option inside Moodle's own
  "Restrict access" panel — no separate configuration screen, no extra capability to manage.
* 🧩 **Context-Aware Availability:** the restriction option is only offered when a PlayerHUD
  block instance actually exists in the course; it stays hidden otherwise.
* ⚡ **Real-Time Evaluation:** the condition is recomputed on every page load directly from the
  student's current PlayerHUD data — no caching, no stale "unlocked" state.
* 📝 **Dynamic Description Text:** the "Not available unless..." message shown to students is
  built from the live requirement (level, item name and quantity, class name, or gamification
  status), not a static string.
