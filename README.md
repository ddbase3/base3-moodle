# BASE3 Moodle

One Git repository for the three native Moodle integration plugins.
`modules/` contains separately versioned BASE3 modules and is Git-ignored.

## Layout

- `moodle/local/base3/`: Administration, runtime, displays and floating chatbot
- `moodle/blocks/base3/`: Configurable BASE3 display blocks
- `moodle/filter/base3/`: Inline BASE3 display placeholders
- `modules/`: Installed BASE3 modules (not part of this repository)
- `install.sh`: Installs all three plugins and copies module assets to Moodle's public directory

## Install or update

From the Moodle root, run `./base3/install.sh`. This synchronizes only
`public/local/base3`, `public/blocks/base3` and `public/filter/base3`
using `rsync --delete`. Old plugin files are removed. It publishes module
assets as regular files, not symbolic links. No BASE3 module is changed.

The installer only deploys files. It does not invoke Moodle CLI, Docker, or
BASE3 bootstrap commands. Moodle itself handles installation and upgrades of
native Moodle plugins. On first installation or after changing a native Moodle
plugin version, open Moodle's Site administration and complete any pending
plugin upgrade. Moodle also offers `admin/cli/upgrade.php` for operators who
prefer its CLI. Neither action is normally needed merely because a BASE3
module was added or updated.

BASE3 automatically refreshes its class map on the next bootstrap when the
installed modules change. A manual `--rebuild` is not normally required.
Moodle cache purging is not a mandatory step for BASE3 module additions;
use Moodle's own cache management if changes are not visible when expected.

Changes to the native Moodle plugins belong under `moodle/` and are deployed
with `./install.sh`, not edited in `public/`. Re-run `./install.sh` whenever
an installed BASE3 module's public assets change. The installer must run in
an environment with filesystem write access to the Moodle installation and
`rsync` available; it does not assume a particular web server or deployment
system.

## FileManager dispatch

The chatbot configuration's 13 FileManager actions use the existing generic
`local/base3/ajax.php` BASE3 output dispatcher, with output name
`base3moodlefilemanageroutput`. `Base3MoodleFileManagerOutput` verifies the
current user has Moodle's `moodle/site:config` capability through the existing
BASE3 `IUsermanager` and validates the Moodle `sesskey` for both uploads and
downloads. It forwards requests to the existing `MoodleFileManagerHttpService`.
The file storage owner remains `chatbot/default`. Upload protocol and storage
are unchanged.

When applying the October 2026 cleanup patch, remove the obsolete
`moodle/local/base3/filemanager.php` source with the included script, then run
`./install.sh` from the BASE3 Moodle source directory. The installer synchronizes
`public/local/base3/` with `rsync --delete`, which also removes the old public
endpoint. No native Moodle plugin version change or database migration is
required for this routing change. See `PRIVACY.md` for the affected data flow.

## Floating chatbot language

`moodle/local/base3/chatbot.php` obtains the HTML `lang` attribute from the
existing BASE3 `ILanguage` service. The Moodle adapter converts Moodle language
pack identifiers such as `de_du` to the base language (`de`). This provides a
valid BCP 47 language tag for ClientStack's message timestamp formatters, which
otherwise throw a browser `RangeError` before the user message is displayed.
The generic AJAX dispatch, chat transport and the shared ClientStack script
are unchanged. Run `./install.sh` after applying changes to the Moodle sources.

## Unified global chatbot launcher (ClientStack)

The global chatbot now uses the same `ClientStack` ES module and CSS as ILIAS
and WordPress. Pull the current ClientStack module first. The Moodle native
footer hook renders `base3moodleglobalchatbotlauncheroutput` through the normal
BASE3 `IOutput`/`Runtime::renderEmbedded` mechanism; content is loaded through
`local/base3/ajax.php` as `chatbotdisplay`. No Moodle-specific launcher
JavaScript/CSS or custom chatbot HTTP endpoint remains.

In BASE3 tools > Chatbot, the **Configuration** tab owns the global
`chatbot/default` instance, **Launcher** owns the shared presentation settings
(`global-chatbot-launcher/default`), and **Extensions** remains unchanged.
Moodle's native plugin settings control only launcher enablement and viewer
visibility. Previously configured native window options are imported once
when the central BASE3 dataset is absent; the old native group/name selector
is no longer used. Individual content chatbot displays retain independent
configuration identities.

After extracting the launcher patch into the BASE3 Moodle source directory:

```sh
sh ./remove-obsolete-moodle-global-chatbot-launcher.sh
./install.sh
php modules/Base3MoodleLab/test/GlobalChatbotLauncherSmokeTest.php
```

The removal script deletes the old `chatbot.php` iframe document and the two
Moodle-specific launcher asset files **from the source tree only**. The existing
installer synchronizes the deletion to Moodle's `public/` directory and
publishes ClientStack assets. The old chatbot icon SVG is retained.

See `moodle/local/base3/README_CHATBOT.md` and `PRIVACY.md` for details.

## Embedded ClientStack loader in Moodle

Moodle-owned JavaScript dependencies for embedded BASE3 displays use
`$PAGE->requires->js(..., false)` rather than injecting duplicate raw
`<script>` tags. This shares Moodle's per-page duplicate elimination with
the administration and generic display endpoints. ClientStack's separate
`marked.js` Markdown renderer remains managed by the modular chatbot plugin.
To check Markdown failures, inspect the marked script in the browser network
panel and test `typeof window.marked?.parse` in the console.


## Moodle Markdown and ClientStack loader correction (18 October 2026)

The modular chatbot's local `marked.js` UMD bundle registers as an AMD module
when Moodle RequireJS is present. ClientStack's shared `MarkdownPlugin` now
resolves that module after the script loads. ILIAS and WordPress still use the
original global parser path. This correction is made in both
`modules/ClientStack/dev/ModularChatbot/src/plugins/MarkdownPlugin.js` and
`modules/ClientStack/assets/modularchatbot/plugins/MarkdownPlugin.js`.

All three Moodle entry points (`Runtime::renderEmbedded`,
`AdminTools::requireAssets`, and `display.php`) now use the same
`Runtime::requireClientStackAssetLoader()` registration, ensuring one Moodle
requirement per request even if entry points overlap. No separate route or
parser is introduced.

Checks: `php test/MoodleAssetLoaderSmokeTest.php` and
`node modules/ClientStack/test/MarkdownPluginAmdSmokeTest.mjs`.

## BASE3 authorization and Moodle RBAC (19 October 2026)

The embedded integration now binds `IAuthorizationService` under the native
Moodle authority, shares the framework's `IUserCriterion` evaluation for
BASE3 Groups and Features, and evaluates host capabilities in native Moodle
contexts. Moodle role and course-group assignment remain Moodle's own data;
no second RBAC storage or compatibility `IUsermanager::can()` path is used.

The BASE3 administration exposes Groups and Features to native Moodle site
administrators who also belong to BASE3 `development`. Regular Moodle
administrators have reduced BASE3 navigation, while games are governed by
their declared features. Native Permissions are displayed read-only using
Moodle context IDs; edit Moodle-native role-capability grants in Moodle itself.

**Before restricting the administration**, enter at least one trusted Moodle
login name in
`modules/Base3MoodleLab/src/Group/MoodleLabDevelopmentGroup.php`, under
`BOOTSTRAP_USERNAMES`. This project-owned fixed membership is independent of
Moodle user IDs and cannot be removed through BASE3 Groups. Full email
addresses may be used only where verification and account-change security
are guaranteed; a bare domain is not suitable for bootstrap admin access.

After applying the authorization patch, run `./base3/install.sh` from the
Moodle root to synchronize the native integration and new Moodle authorization
CSS/JS assets. The project-owned class map refreshes on the following BASE3
bootstrap. No replacement of the native Moodle role/capability system or
additional BASE3 authorization migration tables is needed.

Run the following smoke tests from the BASE3 source directory:

```sh
php modules/Base3Moodle/test/Authorization/MoodleAuthorizationSmokeTest.php
php modules/Base3Moodle/test/Adapter/Base3MoodleFileManagerOutputSmokeTest.php
php modules/Base3MoodleLab/test/GlobalChatbotLauncherSmokeTest.php
```

The native Moodle 5.3 runtime remains the authoritative target for integration
and accessibility verification. See `modules/Base3Moodle/README.md` for the
exact authority/criterion mapping, scope boundaries, and deployment tests.
