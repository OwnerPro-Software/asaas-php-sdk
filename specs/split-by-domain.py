#!/usr/bin/env python3
"""Split the monolithic Asaas OpenAPI spec into per-domain files.

Each output file is a self-contained, valid OpenAPI document containing only
the operations for a single tag plus the transitive closure of referenced
components. Designed for LLM ingestion — no cross-file $ref resolution needed.

Usage:
    python3 specs/split-by-domain.py
        [--input specs/asaas_openapi.json]
        [--out-dir specs/domains]
"""
from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path
from typing import Any

TAG_TO_SLUG: dict[str, str] = {
    "Cobranças": "payments",
    "Cobranças com dados resumidos": "lean-payments",
    "Assinaturas": "subscriptions",
    "Link de pagamentos": "payment-links",
    "Parcelamentos": "installments",
    "Informações fiscais": "fiscal-info",
    "Informações e personalização da conta": "my-account",
    "Negativações": "payment-dunnings",
    "Antecipações": "anticipations",
    "Clientes": "customers",
    "Pix": "pix",
    "Subcontas Asaas": "accounts",
    "Conta Escrow": "escrow",
    "Notas fiscais": "invoices",
    "Pix Automático": "pix-automatic",
    "Configurações de Webhooks": "webhooks",
    "Documentos de cobranças": "payment-documents",
    "Transações Pix": "pix-transactions",
    "Pix Recorrente": "pix-recurring",
    "Transferências": "transfers",
    "Pagamento de contas": "bill-payments",
    "Recargas de celular": "mobile-phone-recharges",
    "Envio de documentos White Label": "my-account-documents",
    "Splits": "payment-splits",
    "Cartão de crédito": "credit-card",
    "Chargeback": "chargebacks",
    "Consulta Serasa": "credit-bureau-report",
    "Checkout": "checkouts",
    "Estornos": "payment-refunds",
    "Notificações": "notifications",
    "Ações em sandbox": "sandbox",
    "Extrato": "financial-transactions",
    "Informações financeiras": "finance",
}

HTTP_METHODS = {"get", "put", "post", "delete", "options", "head", "patch", "trace"}

REF_RE = re.compile(r"^#/components/([^/]+)/(.+)$")


def collect_refs(node: Any, refs: set[tuple[str, str]]) -> None:
    """Walk node recursively and record every internal component $ref."""
    if isinstance(node, dict):
        for key, value in node.items():
            if key == "$ref" and isinstance(value, str):
                match = REF_RE.match(value)
                if match:
                    refs.add((match.group(1), match.group(2)))
            else:
                collect_refs(value, refs)
    elif isinstance(node, list):
        for item in node:
            collect_refs(item, refs)


def transitive_closure(
    seed: set[tuple[str, str]],
    components: dict[str, dict[str, Any]],
) -> dict[str, set[str]]:
    """Expand component refs until fixed point. Returns {bucket: {names...}}."""
    keep: dict[str, set[str]] = {}
    queue: list[tuple[str, str]] = list(seed)
    seen: set[tuple[str, str]] = set()

    while queue:
        bucket, name = queue.pop()
        if (bucket, name) in seen:
            continue
        seen.add((bucket, name))
        keep.setdefault(bucket, set()).add(name)

        bucket_map = components.get(bucket)
        if not bucket_map or name not in bucket_map:
            continue

        nested: set[tuple[str, str]] = set()
        collect_refs(bucket_map[name], nested)
        for ref in nested:
            if ref not in seen:
                queue.append(ref)
    return keep


def build_domain_doc(
    spec: dict[str, Any],
    tag: str,
) -> dict[str, Any]:
    paths_in: dict[str, dict[str, Any]] = spec.get("paths", {})
    components_in: dict[str, dict[str, Any]] = spec.get("components", {})

    filtered_paths: dict[str, dict[str, Any]] = {}
    for path, item in paths_in.items():
        if not isinstance(item, dict):
            continue
        kept_ops: dict[str, Any] = {}
        shared: dict[str, Any] = {}
        for key, value in item.items():
            if key in HTTP_METHODS:
                if isinstance(value, dict) and tag in (value.get("tags") or []):
                    kept_ops[key] = value
            else:
                shared[key] = value
        if kept_ops:
            filtered_paths[path] = {**shared, **kept_ops}

    seed_refs: set[tuple[str, str]] = set()
    collect_refs(filtered_paths, seed_refs)

    kept_components_names = transitive_closure(seed_refs, components_in)

    filtered_components: dict[str, dict[str, Any]] = {}
    for bucket, names in kept_components_names.items():
        source = components_in.get(bucket, {})
        filtered_components[bucket] = {n: source[n] for n in sorted(names) if n in source}

    if "securitySchemes" in components_in:
        filtered_components.setdefault("securitySchemes", {}).update(
            components_in["securitySchemes"]
        )

    matching_tag_objs = [t for t in spec.get("tags", []) if t.get("name") == tag]

    doc: dict[str, Any] = {
        "openapi": spec.get("openapi", "3.0.1"),
        "info": spec.get("info", {}),
    }
    if "servers" in spec:
        doc["servers"] = spec["servers"]
    if matching_tag_objs:
        doc["tags"] = matching_tag_objs
    if "security" in spec:
        doc["security"] = spec["security"]
    doc["paths"] = filtered_paths
    if filtered_components:
        doc["components"] = filtered_components
    return doc


def slug_for(tag: str) -> str:
    if tag in TAG_TO_SLUG:
        return TAG_TO_SLUG[tag]
    fallback = re.sub(r"[^a-z0-9]+", "-", tag.lower()).strip("-")
    return fallback or "untagged"


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--input", default="specs/asaas_openapi.json")
    parser.add_argument("--out-dir", default="specs/domains")
    args = parser.parse_args()

    spec_path = Path(args.input)
    out_dir = Path(args.out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)

    spec = json.loads(spec_path.read_text(encoding="utf-8"))

    tags_in_use: dict[str, int] = {}
    for path, item in spec.get("paths", {}).items():
        if not isinstance(item, dict):
            continue
        for method, op in item.items():
            if method in HTTP_METHODS and isinstance(op, dict):
                for t in op.get("tags") or ["<untagged>"]:
                    tags_in_use[t] = tags_in_use.get(t, 0) + 1

    unmapped = [t for t in tags_in_use if t not in TAG_TO_SLUG]
    if unmapped:
        print(f"WARN: tags without explicit slug (using fallback): {unmapped}", file=sys.stderr)

    index: list[dict[str, Any]] = []
    for tag, op_count in sorted(tags_in_use.items(), key=lambda kv: -kv[1]):
        slug = slug_for(tag)
        doc = build_domain_doc(spec, tag)
        out_path = out_dir / f"{slug}.json"
        out_path.write_text(json.dumps(doc, ensure_ascii=False, indent=2), encoding="utf-8")
        size_kb = out_path.stat().st_size / 1024
        schemas = len(doc.get("components", {}).get("schemas", {}))
        index.append({
            "slug": slug,
            "tag": tag,
            "file": out_path.name,
            "operations": op_count,
            "paths": len(doc.get("paths", {})),
            "schemas": schemas,
            "size_kb": round(size_kb, 1),
        })
        print(f"  {slug:28s} {op_count:3d} ops  {schemas:4d} schemas  {size_kb:7.1f} KB")

    index_path = out_dir / "index.json"
    index_path.write_text(
        json.dumps(
            {
                "source": str(spec_path),
                "openapi": spec.get("openapi"),
                "info": spec.get("info", {}),
                "domains": index,
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )
    print(f"\nindex: {index_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
