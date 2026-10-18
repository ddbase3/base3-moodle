# BASE3 Moodle: processing and deployment notes

This describes behavior verified in the provided BASE3 Moodle source snapshot
(17 October 2026). It is not a deployment privacy notice or a legal compliance
assertion.

## Data and purposes

The Moodle integration uses the active Moodle session and its `sesskey` to
control requests. The native AJAX handler forwards generic BASE3 output
requests. In the chatbot administration, FileManager actions process the
current user's technical ID, the configured storage identity, file names,
paths, upload metadata and file contents to manage chatbot resources.

## Storage and retention

`local_base3` sets `DIR_LOCAL` to `$CFG->dataroot/base3/`. The Moodle host
storage adapter stores owner-scoped files in `DIR_LOCAL/storage/`.
The existing chunk-upload service uses `DIR_LOCAL/filemanager-upload/` with
per-upload `meta.json` and chunk files. Successful finish or explicit abort
removes an upload session. No automatic cleanup of abandoned sessions is
verified in the provided code; operators must assess and define a retention
policy. Other BASE3 plugins can persist additional data outside this scope.

## Access and security

The new FileManager output requires an authenticated BASE3 user identity,
checks `moodle/site:config` via `IUsermanager`, and validates Moodle's current
`sesskey`. The generic AJAX transport also requires a sesskey for POSTs.
The underlying owner-scoped file storage, upload validation and private
Moodle dataroot location are unchanged by this patch. Actual file-system and
web-server permissions depend on deployment configuration.

## Recipients, transfers and deletion

The FileManager dispatch change does not add a recipient, remote transfer, or
new persistence backend. Other configured chatbot/AI services are outside its
scope and require a separate deployment-specific inventory. File and directory
operations of the existing storage backend implement deletion; they are not a
substitute for deletion of backups and other independently stored data.

## Known deployment checks

Review abandoned upload-session retention, log retention, backup deletion,
third-party services, and the site's access control policy with the operator.
Validate keyboard, screen-reader, and HTTP error feedback in the deployed
ClientStack FileManager UI; no client UI was modified in this patch.

## Floating chatbot language rendering

The chatbot iframe writes the existing BASE3 `ILanguage::getLanguage()` value
into the HTML `lang` attribute instead of Moodle's raw language-pack identifier.
This change affects only language formatting in the browser. It adds no new
data collection, persistence, recipient, transfer, or deletion mechanism.

## Shared global chatbot launcher (18 October 2026)

The Moodle footer hook now renders the shared ClientStack launcher instead of
the native Moodle `bubble.js`/`bubble.css` widget and the iframe served from
`/local/base3/chatbot.php`. The global chatbot is bound to the already
configured BASE3 `chatbot/default` record. Existing BASE3 backend transport,
AI-provider choices, file-management authorization and Moodle session checks
are unchanged; chatbot requests still flow through the generic BASE3 AJAX
endpoint.

The launcher reads its presentation flags from the BASE3 `SettingsStore`
dataset `global-chatbot-launcher/default`. On the first render, previously
stored Moodle launcher flags are migrated to that dataset only if it is absent.
The old native flags are not deleted from Moodle configuration by this patch,
but are no longer read after the canonical dataset exists. Moodle's separate
native enabled/visibility configuration remains in use.

The shared ClientStack script uses browser `localStorage` to retain a selected
window mode and individual window sizes, and `sessionStorage` to restore an
open launcher after navigation, subject to administrator-configured flags.
These values are scoped by the global chatbot group and name. The launcher
requests the configured chatbot HTML using same-origin credentials. Browser
storage lifetimes and deletion are controlled by the browser/user and the
client-side launcher code; this patch does not introduce additional server-side
history or a new external recipient.

Operators must assess public launch visibility, model/backend permissions,
provider data transfers, logs, retention, and user-facing notices for their
actual deployment. The module's keyboard interactions and focus handling
should be verified with the site's Moodle theme against BASE3's WCAG 2.2 AA
baseline; source-level checks do not constitute a full accessibility audit.
