# BASE3 chatbot embedding in Moodle

This is an extension of `local_base3`. The BASE3 `Chatbot`, `ClientStack` and
other platform-neutral modules remain unchanged. The existing Moodle
`block_base3` and `filter_base3` plugins also remain unchanged.

## Installation

Extract the patch into the Moodle source root (`html/`), retaining the paths.
Then run the regular Moodle CLI upgrade and purge Moodle caches:

```sh
cd /srv/sites/base3.de/moodle.base3.de/html
unzip -o /tmp/base3-moodle-chatbot-0.1.1.zip

cd /srv/docker/sites/base3.de/moodle.base3.de
docker compose exec -T -u www-data apache php /var/www/moodle/admin/cli/upgrade.php --non-interactive
docker compose exec -T -u www-data apache php /var/www/moodle/admin/cli/purge_caches.php
```

## Configure the floating chatbot

1. Open `Site administration > Plugins > BASE3 Moodle > BASE3 tools`.
2. Under `Chatbot > Configuration`, configure and save a chatbot record.
   For the initial demo, use group `chatbot` and name `default`.
3. Open `Site administration > Plugins > BASE3 Moodle > Floating chatbot`.
4. Keep `Chatbot configuration group` and `Chatbot configuration name`
   consistent with the record you just created.
5. Choose visibility. Default is logged-in users; public use should be a
   deliberate decision because AI-provider costs and access permissions apply.
6. Enable `Show floating chatbot` and save changes.
7. Test on a regular course page as a user in the selected visibility group.

The launcher appears at the bottom right. On first opening it loads
`/local/base3/chatbot.php` inside the window. Its content is the unmodified
BASE3 `chatbotdisplay`. Available window modes are configurable in Moodle.
The global footer display configuration is independent and can remain empty.

## Chatbots elsewhere in Moodle

The existing block and filter integrations already accept any BASE3 display.
Use `chatbotdisplay`, not `modularchatbotdisplay`, so BASE3 can resolve the
stored chatbot configuration, backend and service endpoints.

For a **course block**, enable editing on a page, add `BASE3 Display`, and set:

- Display name: `chatbotdisplay`
- Display data (JSON):

```json
{"config_group":"chatbot","config_name":"default"}
```

For a **Moodle Page or Label**, enable the `BASE3 Display Embeds` filter
in Moodle Site administration > Plugins > Filters > Manage filters. Insert
this text in a content area that applies the filter:

```text
[[base3 name="chatbotdisplay" data='{"config_group":"chatbot","config_name":"default"}']]
```

For an **individual Moodle page**, use the existing generic display endpoint:

```text
/local/base3/display.php?name=chatbotdisplay&data=%7B%22config_group%22%3A%22chatbot%22%2C%22config_name%22%3A%22default%22%7D
```

To demonstrate a distinct chatbot in a different course, create another
configuration record in the BASE3 Chatbot tools and change only `config_name`
in that block, filter, or link. All placements use the same original
Chatbot implementation and BASE3 service configuration.

## Diagnostics

- Missing launcher: verify `Show floating chatbot`, visibility, Moodle
  cache purge and the footer hook callback.
- Launcher opens but chatbot cannot initialize: open the iframe URL
  `/local/base3/chatbot.php` while signed in. Verify the selected BASE3
  chatbot configuration and corresponding model/backend services.
- Missing styling/scripts: check the existing ClientStack asset symlink under
  `public/local/base3/assets/ClientStack` and the browser Network tab.
- Chat messages fail: inspect `/local/base3/ajax.php` responses and the
  Moodle PHP error log. POST requests need a valid Moodle `sesskey`.

No new router, session storage, backend profile or duplicated BASE3 runtime
has been introduced by this change.
