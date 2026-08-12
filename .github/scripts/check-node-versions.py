#!/usr/bin/env python3
"""Assert every workflow runs Node on a version its package supports.

This exists because the same mistake shipped twice in one afternoon:
`node-version: 20` copied between workflows while the package it runs needs 22,
so wrangler refused to start. The `engines` field in package.json states the
requirement, but pnpm only warns about it — the run reaches CI and dies there.

Reads the top-level `defaults.run.working-directory` of each workflow to decide
which package.json applies, then checks every `node-version` in that workflow
against its `engines.node`. Workflows with no working-directory, or packages
with no `engines`, are skipped and reported as such — silence would look
identical to a pass.
"""

import json
import pathlib
import re
import sys

import yaml

ROOT = pathlib.Path(__file__).resolve().parents[2]
WORKFLOWS = ROOT / ".github" / "workflows"

# Only the simple forms actually used here. Anything else is an error rather
# than a silent pass — a range this cannot read is a range it cannot enforce.
RANGE = re.compile(r"^\s*>=\s*(\d+)")


def required_major(engines: str) -> int:
    match = RANGE.match(engines)
    if not match:
        raise ValueError(f"unsupported engines range {engines!r}; teach this script about it")
    return int(match.group(1))


def main() -> int:
    problems: list[str] = []
    checked = 0

    for path in sorted(WORKFLOWS.glob("*.yml")):
        doc = yaml.safe_load(path.read_text()) or {}
        workdir = (doc.get("defaults") or {}).get("run", {}).get("working-directory")
        if not workdir:
            print(f"skip  {path.name}: no top-level working-directory")
            continue

        pkg_path = ROOT / workdir / "package.json"
        if not pkg_path.exists():
            print(f"skip  {path.name}: {workdir}/package.json not found")
            continue

        engines = (json.loads(pkg_path.read_text()).get("engines") or {}).get("node")
        if not engines:
            print(f"skip  {path.name}: {workdir} declares no engines.node")
            continue

        want = required_major(engines)

        # Read the raw text rather than the parsed tree: node-version can sit
        # under any job, and matrix expressions would not resolve anyway.
        found = re.findall(r"node-version:\s*['\"]?(\d+)", path.read_text())
        if not found:
            print(f"skip  {path.name}: pins no node-version")
            continue

        for got in found:
            checked += 1
            if int(got) < want:
                problems.append(
                    f"{path.name}: node-version {got} but {workdir} requires node {engines}"
                )
            else:
                print(f"ok    {path.name}: node {got} satisfies {engines} ({workdir})")

    if problems:
        print("\nnode-version does not satisfy the package's engines:", file=sys.stderr)
        for problem in problems:
            print(f"  {problem}", file=sys.stderr)
        return 1

    print(f"\n{checked} node-version pin(s) checked, all satisfy their package's engines.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
