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
