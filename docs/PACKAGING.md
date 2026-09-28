# Building a distributable ZIP

Run from the repository root:

```
bash tests/build-zip.sh
```

This produces `dist/ev-charging-experience.zip`, containing only:

```
ev-charging-experience/
├── ev-charging-experience.php
├── composer.json
├── src/
├── assets/
├── templates/
├── element-studio/        (empty save-location folders + .gitkeep)
├── docs/
├── README.md
└── CHANGELOG.md
```

Explicitly excluded: `.git`, `tests/` (Docker QA harness — dev-only),
`.claude/`, `.agents/`, `skills-lock.json` (this repository's Claude Code
tooling, unrelated to the WordPress plugin), and any OS/editor cruft
(`.DS_Store`, `*.log`). There is no `node_modules` or `vendor/` to exclude
in the first place — this plugin has no build step and no runtime PHP
dependencies (the autoloader is hand-rolled specifically so a plain ZIP
upload always works).

Install the resulting ZIP the normal way: WordPress Admin → Plugins → Add
New → Upload Plugin.
