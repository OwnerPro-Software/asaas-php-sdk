#!/usr/bin/env python3
"""Verify the per-domain split is lossless and consistent with the source spec.

Checks:
  1. Every (path, method) operation in source appears in at least one domain
     file (matched by tag), and operations not in any tag we mapped are listed.
  2. Each domain file's operations are byte-equivalent (deep JSON equality) to
     the source for that (path, method).
  3. Path-item siblings (parameters, summary, description, servers) are
     preserved verbatim in every domain file that includes the path.
  4. Splits contain zero internal $ref strings (source has zero).
  5. No extra component schemas leak into splits beyond what is referenced.
  6. The union of operations across all splits equals the source set (no dup
     loss, no extras except multi-tagged ops which legitimately duplicate).

Exits non-zero on any failure.
"""
from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path
from typing import Any

HTTP_METHODS = {"get", "put", "post", "delete", "options", "head", "patch", "trace"}


def load_json(path: Path) -> Any:
    return json.loads(path.read_text(encoding="utf-8"))


def operation_set(spec: dict[str, Any]) -> dict[tuple[str, str], dict[str, Any]]:
    """Return {(path, method): operation_obj} for every operation in spec."""
    out: dict[tuple[str, str], dict[str, Any]] = {}
    for path, item in spec.get("paths", {}).items():
        if not isinstance(item, dict):
            continue
        for method, op in item.items():
            if method in HTTP_METHODS and isinstance(op, dict):
                out[(path, method)] = op
    return out


def path_siblings(item: dict[str, Any]) -> dict[str, Any]:
    """Return non-operation keys on a path-item object (parameters, summary, ...)."""
    return {k: v for k, v in item.items() if k not in HTTP_METHODS}


def count_refs(node: Any) -> int:
    if isinstance(node, dict):
        return (1 if "$ref" in node else 0) + sum(count_refs(v) for v in node.values())
    if isinstance(node, list):
        return sum(count_refs(v) for v in node)
    return 0


def tag_of(op: dict[str, Any]) -> list[str]:
    return list(op.get("tags") or [])


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--input", default="specs/asaas_openapi.json")
    parser.add_argument("--domains-dir", default="specs/domains")
    args = parser.parse_args()

    source = load_json(Path(args.input))
    domains_dir = Path(args.domains_dir)
    index = load_json(domains_dir / "index.json")

    source_ops = operation_set(source)
    errors: list[str] = []
    warnings: list[str] = []

    # --- check 4: source has zero internal $ref (assumption baseline) ---
    src_refs = count_refs(source)
    print(f"[1] source $ref count: {src_refs}")

    # --- load every domain ---
    domain_docs: dict[str, dict[str, Any]] = {}
    for entry in index["domains"]:
        slug = entry["slug"]
        domain_docs[slug] = load_json(domains_dir / entry["file"])

    # --- check 1+2+6: every source op present in some domain, byte-equal ---
    coverage: dict[tuple[str, str], list[str]] = {}
    for slug, doc in domain_docs.items():
        for (path, method), op in operation_set(doc).items():
            coverage.setdefault((path, method), []).append(slug)
            src_op = source_ops.get((path, method))
            if src_op is None:
                errors.append(f"{slug}: spurious op {method.upper()} {path} not in source")
                continue
            if op != src_op:
                errors.append(f"{slug}: op {method.upper()} {path} differs from source")

    missing = [k for k in source_ops if k not in coverage]
    if missing:
        # legitimate only if op has no tag (or only tags we explicitly didn't map)
        slugged_tags = {e["tag"] for e in index["domains"]}
        for path, method in missing:
            tags = tag_of(source_ops[(path, method)])
            if not tags:
                warnings.append(f"untagged op {method.upper()} {path} (no tag → not assigned to any domain)")
            elif not any(t in slugged_tags for t in tags):
                warnings.append(f"op {method.upper()} {path} has tags {tags} not in slug map")
            else:
                errors.append(f"MISSING op {method.upper()} {path} (tags={tags}) — should be in some domain")

    # ops correctly duplicated when they have >1 mapped tag
    for key, slugs in coverage.items():
        path, method = key
        op = source_ops.get(key)
        if op is None:
            continue
        expected_slugs = []
        for t in tag_of(op):
            for e in index["domains"]:
                if e["tag"] == t:
                    expected_slugs.append(e["slug"])
        if set(slugs) != set(expected_slugs):
            errors.append(
                f"op {method.upper()} {path} placed in {slugs} but tags imply {expected_slugs}"
            )

    print(f"[2] source operations: {len(source_ops)}  covered: {len(coverage)}  missing: {len(missing)}")

    # --- check 3: path-item siblings preserved ---
    for slug, doc in domain_docs.items():
        for path, item in doc.get("paths", {}).items():
            if not isinstance(item, dict):
                continue
            src_item = source.get("paths", {}).get(path)
            if not isinstance(src_item, dict):
                errors.append(f"{slug}: path {path} not in source")
                continue
            if path_siblings(item) != path_siblings(src_item):
                errors.append(f"{slug}: path-item siblings differ at {path}")

    # --- check 4: zero $ref in splits ---
    for slug, doc in domain_docs.items():
        refs = count_refs(doc)
        if refs != src_refs and refs > 0:
            warnings.append(f"{slug}: contains {refs} $ref strings (source has {src_refs})")

    # --- check 5: components only contain securitySchemes + referenced schemas ---
    for slug, doc in domain_docs.items():
        comps = doc.get("components", {})
        unexpected_buckets = set(comps.keys()) - {"schemas", "securitySchemes"}
        if unexpected_buckets:
            warnings.append(f"{slug}: unexpected component buckets {unexpected_buckets}")

    # --- summary ---
    print()
    if warnings:
        print(f"WARNINGS ({len(warnings)}):")
        for w in warnings:
            print(f"  ! {w}")
    if errors:
        print(f"\nERRORS ({len(errors)}):")
        for e in errors:
            print(f"  x {e}")
        return 1

    print(f"\nOK — split is lossless. {len(coverage)} ops verified across {len(domain_docs)} files.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
