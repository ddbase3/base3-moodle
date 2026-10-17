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
