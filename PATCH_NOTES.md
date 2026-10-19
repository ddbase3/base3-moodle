# ClientStack Markdown smoke test: Node.js 18 compatibility

The previous `MarkdownPluginAmdSmokeTest.mjs` used a direct named import from
`../assets/modularchatbot/plugins/MarkdownPlugin.js`. Node.js 18 treats `.js`
as CommonJS without `package.json` module scope, while modern Node.js may infer
ES module syntax. This prevented the test from executing under Node.js 18.19.1.

The corrected smoke test makes a temporary isolated ES module fixture containing
byte-for-byte copies of the existing `MarkdownPlugin.js`, `loadScript.js` and
`dom.js` and a test-only `package.json` with `"type": "module"`.
It executes the same three checks as before:

- Moodle RequireJS/AMD export (`window.marked` remains undefined).
- ILIAS and WordPress classic global parser.
- AMD host with an unrelated `window.marked` global.

The test still checks that the deployed MarkdownPlugin and `index.js` match
their development source, and that the module import cache key is updated.
The fixture is removed even when tests fail. This patch does **not** change
any production `.js` or PHP file, the browser module format, the Markdown
renderer, or BASE3 configuration. No runtime privacy or storage behavior changes.

Apply in the BASE3 Moodle source root, on top of the existing October 18
Markdown/AMD fix:

```sh
unzip -o base3_clientstack_markdown_node18_test_fix_20261018.zip
node modules/ClientStack/test/MarkdownPluginAmdSmokeTest.mjs
```

The same smoke test can be run inside any full ClientStack checkout with
Node.js 18 or newer. A complete ClientStack installation containing the local
`assets/marked/marked.js` and ModularChatbot utilities is required.

# Moodle-native BASE3 authorization integration (19 October 2026)

Updated Base3Moodle/ Base3MoodleLab and the existing native Moodle transport.
Framework and common feature plugins remain unchanged; do not overwrite or
fork their `IAuthorizationService`, `IFeature`, `IGroup`, `IUserCriterion`, or
`UserCriterionEvaluator` contracts.

Moodle owns native permissions (`moodle/<capability>`, resource type
`context_id`) and uses `has_capability()`. `system/admin` evaluates Moodle
site administrator status. BASE3 feature and group audiences share the one
criterion evaluator, including Moodle native roles and Moodle course groups
through discoverable criteria. PulseCore's existing `PulseCorePlayFeature`
now works through the Moodle authority. The obsolete Moodle
`IUsermanager::can()` path and implicit native-course-group BASE3 group
injection have been removed, not duplicated.

Two shared Moodle-hosted administrative displays edit effective BASE3 group
membership and discovered feature audiences. Writes use only the existing
Moodle-backed settings datasets; code-owned initial group membership cannot
be overwritten by editable rules. The general native Permission inspector is
read-only for direct CAP_ALLOW role grants, with explicit context IDs.

Moodle site administrators without BASE3 `development` see reduced tab
navigation. A source-defined project `development` group in Base3MoodleLab
supports first-time administration from exact trusted logins or verified full
email addresses. Set at least one bootstrap identity before installation.
Direct Groups/Features endpoints require BOTH Moodle site administrator and
BASE3 `development` membership. All writes require Moodle `sesskey`.

Installation is an `unzip -o` update to the BASE3 source directory followed
by `./base3/install.sh` from the Moodle root. No obsolete source files or
new authorization schema migrations are part of this patch. Run the three
new/updated standalone smoke tests listed in `README.md`. Test live Moodle
context/role and direct-endpoint behavior separately before production use.
