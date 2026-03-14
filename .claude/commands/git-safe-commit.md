---
name: git-safe-commit
description: Create Conventional Commit (only feat/fix/chore) from staged files – very conservative
allowed-tools: [Bash, Read]
---

Generate **one** commit message in English (Conventional Commits style).

**Steps:**

1. Run `git diff --staged --stat` and `git diff --staged` to see changes.
   - If Git returns "detected dubious ownership", rerun with:
     - `git -c safe.directory=* diff --staged --stat`
     - `git -c safe.directory=* diff --staged`
2. Analyze ONLY staged changes (ignore unstaged/untracked files)
3. Generate commit message following rules below

**Rules – must follow 100%:**

- Only types: `feat:` `fix:` `chore:`
- Describe **exclusively** real changes in `git diff --staged`
- Imperative present tense (add/fix/update, not added/fixed/updated)
- **Never** mention/imply: breaking, security, integrity, corruption, stability, critical impact
- If not clearly feat or fix → use `chore:`
- No exclamation mark (!), no BREAKING CHANGE
- Prefer one line when possible (no body unless necessary)

**Output:**
Only the commit message – nothing else (no explanation, no options, just the message).

**Examples:**
```
feat: add employee vacation balance calculation
fix: correct overtime rate for night shifts
chore: update README with installation steps
```
