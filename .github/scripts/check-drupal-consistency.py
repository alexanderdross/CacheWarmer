#!/usr/bin/env python3
"""Static consistency checks for the Drupal module.

This module has repeatedly shipped code that reads a config key or injects a
service that exists nowhere:

  * cdn.custom_headers and four siblings were read but never declared, so five
    Enterprise settings resolved to NULL and did nothing.
  * pinterest was a target with no pinterest.enabled key, so it was skipped on
    every job.
  * @logger.channel.cachewarmer was injected but never defined, so the webhooks
    service could not be instantiated.

Each was fixed by hand. These two checks keep the whole class out:

  A. Every config key read against `cachewarmer.settings` is declared in
     config/install/cachewarmer.settings.yml.
  B. Every service reference in cachewarmer.services.yml resolves — to a
     service defined in that file, or to a core service on an explicit
     allowlist.

No YAML library: PyYAML is not on the runner (the node-version check had to
drop it), so the small, regular structures here are parsed by hand — the same
choice, for the same reason.
"""

import pathlib
import re
import sys

ROOT = pathlib.Path(__file__).resolve().parents[2]
MODULE = ROOT / "drupal-module" / "cachewarmer"
SRC = MODULE / "src"
SETTINGS = MODULE / "config" / "install" / "cachewarmer.settings.yml"
SERVICES = MODULE / "cachewarmer.services.yml"

# Core services this module legitimately depends on. The point of an explicit
# allowlist is that a NEW core dependency has to be added here consciously,
# while a typo'd or missing custom service — which looks just like a core one —
# fails loudly. `logger.channel.cachewarmer` looked core but was neither core
# nor defined; that is exactly what this catches.
CORE_SERVICES = {
    "database",
    "http_client",
    "config.factory",
    "logger.factory",
    "plugin.manager.mail",
    "logger.channel_base",  # used as a `parent:`
}

# A config key literal, single- or double-quoted. Interpolated keys
# (->get("{$target}.enabled")) contain { or $ and are intentionally not matched.
KEY = r"['\"]([a-z0-9_.-]+)['\"]"


def declared_config_keys(text: str) -> set[str]:
    """Dotted paths declared in the settings YAML, by indent, without a parser.

    Tracks a stack of (indent, key). Two-space indentation, `key:` or
    `key: value`; a scalar and a mapping open the same way, so every key that
    appears becomes a path — which is exactly what config->get() addresses.
    """
    keys: set[str] = set()
    stack: list[tuple[int, str]] = []
    for raw in text.splitlines():
        if not raw.strip() or raw.lstrip().startswith("#"):
            continue
        indent = len(raw) - len(raw.lstrip(" "))
        match = re.match(r"([A-Za-z0-9_.-]+):", raw.strip())
        if not match:
            continue  # list item, continuation, etc.
        key = match.group(1)
        while stack and stack[-1][0] >= indent:
            stack.pop()
        stack.append((indent, key))
        keys.add(".".join(k for _, k in stack))
    return keys


def settings_reads(text: str) -> set[str]:
    """Config keys read against cachewarmer.settings in one PHP file.

    Receiver-bound on purpose. A line like

        $to = $config->get('notification_email')
              ?: \\Drupal::config('system.site')->get('mail');

    reads `mail` from system.site, not from settings. Matching `->get('mail')`
    blindly would report it as a missing settings key. So only `->get()` on a
    variable proven to hold cachewarmer.settings counts, plus the inline
    ->get('cachewarmer.settings')->get('KEY') form.
    """
    reads: set[str] = set()

    # Inline: ...get('cachewarmer.settings')->get('KEY') / ...config('...')->get('KEY')
    for m in re.finditer(
        r"(?:get|config)\('cachewarmer\.settings'\)->get\(\s*" + KEY,
        text,
    ):
        reads.add(m.group(1))

    # Variables bound to the settings config object.
    bound = set(
        re.findall(
            r"(\$\w+)\s*=\s*[^;]*->(?:get|config)\('cachewarmer\.settings'\)",
            text,
        )
    )
    for var in bound:
        for m in re.finditer(re.escape(var) + r"->get\(\s*" + KEY, text):
            reads.add(m.group(1))

    return reads


def check_config_drift() -> list[str]:
    declared = declared_config_keys(SETTINGS.read_text())
    problems: list[str] = []
    checked = 0
    for path in sorted(SRC.rglob("*.php")):
        for key in sorted(settings_reads(path.read_text())):
            checked += 1
            if key not in declared:
                problems.append(
                    f"{path.relative_to(MODULE)}: reads '{key}', "
                    f"not declared in {SETTINGS.relative_to(MODULE)}"
                )
    print(f"config: {checked} settings read(s) checked against {len(declared)} declared keys")
    return problems


def defined_service_ids(text: str) -> set[str]:
    """Top-level service IDs: keys indented exactly two spaces under `services:`."""
    ids: set[str] = set()
    in_services = False
    for raw in text.splitlines():
        if re.match(r"^services:\s*(#.*)?$", raw):
            in_services = True
            continue
        if in_services and re.match(r"^\S", raw):  # next top-level block
            break
        if in_services:
            match = re.match(r"^  ([A-Za-z0-9_.]+):\s*$", raw)
            if match:
                ids.add(match.group(1))
    return ids


def check_service_references() -> list[str]:
    text = SERVICES.read_text()
    defined = defined_service_ids(text)
    known = defined | CORE_SERVICES

    referenced = set(re.findall(r"@([A-Za-z0-9_.]+)", text))  # '@service' arguments
    referenced |= set(re.findall(r"parent:\s*([A-Za-z0-9_.]+)", text))  # parent: ids

    problems = [
        f"cachewarmer.services.yml: references '{ref}', "
        f"neither defined nor a known core service"
        for ref in sorted(referenced)
        if ref not in known
    ]
    print(f"services: {len(referenced)} reference(s) checked against {len(defined)} defined + {len(CORE_SERVICES)} core")
    return problems


def main() -> int:
    problems = check_config_drift() + check_service_references()
    if problems:
        print("\nDrupal consistency problems:", file=sys.stderr)
        for problem in problems:
            print(f"  {problem}", file=sys.stderr)
        return 1
    print("\nAll config reads are declared and all service references resolve.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
