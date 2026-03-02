# OpenClaw Reference

This document provides implementation references for BLB. OpenClaw serves as a point of reference when deciding how to implement features. It does not serve as a blueprint to copy.

## Relationship

- **BLB does not need to build exactly like OpenClaw.** We take what works best in the BLB context.
- **We can modify OpenClaw’s approaches** to suit our purposes.
- **We do not blindly copy.** Use OpenClaw as a reference for patterns and decisions; adapt or reject based on fit.

## References

1. LLM Providers
    - list of providers: /home/kiat/repo/openclaw/docs/providers/
    - setup code: /home/kiat/repo/openclaw/src/commands/auth-choice.apply.*.ts
    - /home/kiat/repo/openclaw/docs/reference/api-usage-costs.md
    - /home/kiat/repo/openclaw/docs/reference/prompt-caching.md
    - model params, context window, max tokens, etc.: /home/kiat/repo/openclaw/src/agents/models-config.providers.ts
2. LLM Runtime
    - model fallback logic: /home/kiat/repo/openclaw/src/agents/model-fallback.ts
2. openclaw-managed-browser: /home/kiat/repo/openclaw/src/browser/
