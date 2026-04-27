## What Changed
<!-- Describe the changes made in this PR -->

## Why
<!-- Explain the motivation for these changes -->

## Configuration

```
automerge=false
version_bump=patch
```

**version_bump options:** `none` (no release), `patch` (bug fixes), `minor` (new features), `major` (breaking changes)

## Checklist
- [ ] I have tested my code locally
- [ ] All tests are passing
- [ ] New code has adequate test coverage

> **Note:** the `VERSION` file at the repo root is owned by the release workflow. Do not edit it manually — set `version_bump` above and the bot will open a follow-up `chore(release): ...` PR after this one merges.
