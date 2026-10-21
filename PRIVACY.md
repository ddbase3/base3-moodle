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
but are no longer read after the canonical dataset exists. The separate native enabled/visibility configuration is no longer used after the global-launcher feature change (21 October 2026).

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

## Moodle JavaScript loader registration (18 October 2026)

The embedded-display runtime now registers the existing local ClientStack
`assetloader.min.js` via Moodle's built-in page requirements manager rather
than inserting a second executable script tag. This is a client resource
loading correction only. It adds no new data processing, storage, retention,
recipient, transfer, or deletion behavior. The previously documented
chatbot-related data handling and deployment checks remain unchanged.


## Moodle Markdown AMD and duplicate loader correction (18 October 2026)

The local ClientStack Markdown parser is now resolved through the already
loaded RequireJS/AMD registry when Moodle is the host. The Moodle runtime
coordinates the same previously deployed assetloader script across its
entry points so the resource is registered once per request. Both changes
concern the handling of locally served scripts; no personal data categories,
storage, retention period, recipient, external transfer, authorization
rule, or deletion behavior are added or altered. The existing chatbot
processing and host log considerations documented above remain applicable.

## BASE3 authorization in native Moodle (19 October 2026)

The Moodle integration now evaluates BASE3 feature visibility and logical
group membership against current Moodle identity metadata. Native Moodle
context-specific permissions are checked with `has_capability()`, role
membership with `get_user_roles()`, and course-group membership with Moodle
native group services. No independent copy of native Moodle roles,
capabilities, course-group memberships or user accounts is created.

Moodle-backed `ISettingsStore` persists optional custom BASE3 group names,
descriptions and membership expressions under `base3moodle/groups`, and
configured feature audiences under `base3_authorization_features/catalog`.
These records may include native Moodle user IDs, role and context IDs,
course-group IDs, group names, exact login names, complete email addresses,
domains and other fields declared by enabled third-party criteria. A code
allowlist of bootstrap developer logins or verified emails may also appear in
`Base3MoodleLab` project source and its repository/deployment backups.

The UI writes through the existing authenticated same-origin Moodle AJAX
endpoint and checks Moodle session keys. Only current Moodle site
administrators belonging to BASE3 `development` can edit group and feature
assignments. A BASE3 group membership never grants native Moodle
administrator privileges. The generic rights inspector is read-only and
shows only direct CAP_ALLOW grants: no native Moodle role records are
modified by that inspector.

Managed group deletion removes the current configuration record only when
no feature references the group. Native Moodle roles and course-group
memberships are neither deleted nor rewritten. This change adds no separate
network recipient or new authorization data table; other configured BASE3
and AI services may have additional recipients not covered by this review.
The source does not specify retention periods or automatic deletion of
host settings, logs, source backups or database backups. Host operators must
assess access, retention, correction, erasure, backups and the specific
attributes of optional custom criteria in their deployment privacy notice.

The shared audience UIs provide labelled inputs, keyboard-usable actions,
server-side validation, session-key checks and live feedback. Their actual
screen-reader/focus behavior must be verified with the deployed Moodle 5.3
theme; isolated syntax and smoke tests do not establish WCAG 2.2 AA compliance.
See `modules/Base3Moodle/PRIVACY.md` and
`modules/Base3MoodleLab/PRIVACY.md` for ownership of specific processing.

## Global chatbot feature and native setting removal (21 October 2026)

The global floating chatbot is controlled by the BASE3
`feature/globalchatbotlauncherfeature` audience in `Base3MoodleLab`. Its
default `authenticated` membership uses the existing Moodle adapter and may
be overridden by the existing BASE3 Feature administration. Launcher display
and AJAX loading of `chatbot/default` require an ALLOW decision. Independent
embedded chatbot identities and the general conversation backends are not
automatically covered by the launcher feature.

The previous `local_base3/chatbotbubble_enabled` and
`local_base3/chatbotbubble_visibility` values are no longer read or exposed
in native Moodle settings; existing values are not automatically erased from
the host database or backups. No new standalone user store, external recipient,
transfer path or retention period is introduced. The deployment operator must
assess access logs, historical config retention, model-provider processing,
feature-audience personal data and accessibility of denied AJAX feedback.
