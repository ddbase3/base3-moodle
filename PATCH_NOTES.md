# Moodle global chatbot: shared ClientStack launcher

## Change scope

- Replaces the native Moodle bubble JavaScript/CSS with the already maintained
  `ClientStack/assets/globalchatbotlauncher` ES module and stylesheet.
- Leaves `local_base3` enablement and visibility checks native to Moodle.
- Adds the ClientStack **Launcher** settings tab between **Configuration** and
  **Extensions** in Base3MoodleLab. The existing `chatbot/default` configuration
  and 13 generic FileManager endpoints are preserved.
- Reuses the generic `local/base3/ajax.php` dispatcher and BASE3 display
  `chatbotdisplay`, with no new AJAX adapter or independent chatbot backend.
- Imports legacy native Moodle window flags once when no canonical BASE3
  launcher settings record exists. Fixed global identity: `chatbot/default`.
- Uses Moodle `#page-wrapper` and fixed-navbar selectors for docked layout.

## Important: ClientStack is not included

Update `modules/ClientStack` from Git before installation. Its
`assets/globalchatbotlauncher/index.js`, stylesheet and existing assetloader
must be present. The current WordPress sidebar layering correction is already
part of that shared CSS.

## Installation

From the Moodle BASE3 source root (`.../html/base3`):

```sh
unzip -o /path/base3_moodle_clientstack_global_launcher_20261018.zip
sh ./remove-obsolete-moodle-global-chatbot-launcher.sh
./install.sh
php modules/Base3MoodleLab/test/GlobalChatbotLauncherSmokeTest.php
```

The removal script deletes only the obsolete source `moodle/local/base3/chatbot.php`,
`moodle/local/base3/assets/chatbot/bubble.js`, and `bubble.css`.
`./install.sh` propagates these removals to the public Moodle plugin directory
through its existing `rsync --delete` flow. The icon SVG is intentionally kept.
No core files, migrations, new transport or Foundation interfaces are changed.

## Verification and limitations

- Isolated PHP launcher smoke test: visibility, identity, canonical settings,
  legacy import precedence, generic routing, admin tab order and FileManager
  endpoint count.
- Existing FileManager smoke test is included in the host sources and can be
  rerun unchanged.
- PHP syntax checks and patch application rehearsal.
- A real browser integration test under the target Moodle theme, including
  focus management, resize, sidebar docking and navigation reopening, remains
  necessary after deployment.

## Compatibility

The previous native Moodle window options are removed from the Moodle plugin
settings screen; their stored values are preserved for the one-time import.
The former Moodle group/name selectors are no longer used, because the global
launcher must match the BASE3 Chatbot Configuration tab: `chatbot/default`.
Content chatbots keep their own independent configuration records.
