# Agent Rules for be-amunisi

## General Development Guidelines

1. **Framework Conventions:** This is a Laravel 12 application using PHP 8.2+. Always follow Laravel conventions (e.g., proper use of Eloquent ORM, Form Requests for validation, API Resources for JSON responses).
2. **Authentication:** Ensure endpoints are properly secured with Sanctum middleware (`auth:sanctum`) where required.
3. **Payments:** When interacting with the Midtrans integration, follow the existing patterns and ensure webhook handlers properly verify signatures.
4. **Error Handling:** Use standard Laravel exception handling to ensure consistent JSON API error responses.

## Graphify Knowledge Graph

This repository uses [graphify](https://github.com/safishamsi/graphify) to maintain a knowledge graph of its codebase and architecture. The graph is stored in `graphify-out/`.

When tasked with questions about the codebase architecture, relationships, or complex tracing:
1. Always check if `graphify-out/graph.json` exists.
2. If it exists, use the Graphify skill to query the graph (`graphify query "<question>"`) instead of blindly grepping the entire repository.
3. This graph provides rich contextual relationships including community detection and semantic tracing.
4. After significant changes to the codebase, run `/graphify . --update` to keep the graph in sync, or run `graphify update .` using the CLI.
