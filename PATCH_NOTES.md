# BASE3 Moodle: floating chatbot language fix

## Cause

The floating chatbot iframe wrote `current_language()` directly into the HTML `lang` attribute. The Moodle German informal language pack reports `de_du`, which is not a valid BCP 47 language tag. ClientStack's `Chatbot.js` reads this attribute and calls `Intl.DateTimeFormat`, which throws `RangeError: Invalid language tag: de_du`. This happens before the message is appended and before transport starts, leaving the send button in its stop state.

## Change

Only `moodle/local/base3/chatbot.php` runtime behavior changes. The iframe now obtains its HTML language attribute from the existing BASE3 `ILanguage` binding, whose `MoodleLanguage` implementation normalizes `de_du` to `de`.

The generic AJAX dispatcher, FileManager, transport, ClientStack, framework and project module are unchanged. The root README and PRIVACY notes document the rendering change; `test/ChatbotLocaleSmokeTest.php` exercises the actual iframe template with `de_du`.

## Install

From the BASE3 Moodle source checkout:

```sh
unzip -o /path/base3_moodle_chatbot_locale_fix_20261017.zip
./install.sh
```

Optional smoke test (installed BASE3 modules are required in `modules/`):

```sh
php test/ChatbotLocaleSmokeTest.php
```

Open the global chatbot again and send a message. This patch removes the demonstrated synchronous browser exception. It does not, on its own, verify that the subsequent chat backend request succeeds.
