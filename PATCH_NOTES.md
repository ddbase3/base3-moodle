# Moodle global chatbot launcher button correction

This patch is based on `base3_moodle_clientstack_global_launcher_20261018.zip`.
It removes only the custom `data-launcher-icon` attribute from
`Base3MoodleGlobalChatbotLauncherOutput`. As in WordPress, the shared
ClientStack launcher displays its blue rounded button with `Chat` text.
No ClientStack code, AJAX endpoint, visibility settings, chatbot identities
or launcher window behavior is changed.

## Install

From the BASE3 Moodle source directory:

```sh
unzip -o /path/base3_moodle_launcher_button_style_20261018.zip
./install.sh
php modules/Base3MoodleLab/test/GlobalChatbotLauncherSmokeTest.php
```

Do not remove the legacy SVG; it is no longer used by the shared launcher.
