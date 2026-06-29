# Security-stack runbook (Story 18.3, §14.16)

A layered stack: the **edge** (Cloudflare), **monitoring** (Patchstack), **host**
(malware scanning), **identity** (staff 2FA) and **origin** (the `Ink\Security`
module). This runbook is the deploy/verify checklist for the external layers; the
origin layer is in code.

> Source of record: `_bmad-output/planning-artifacts/epics.md` Story 18.3;
> `_bmad-output/project-context.md` (Cloudflare-locked origin, staff 2FA, PayFast
> off-site → low PCI, don't reactivate Loginizer).

## What the code does (`Ink\Security`)

- `Hardening` (origin surface reduction, each behind an `ink_security_*` opt-out filter):
  - XML-RPC disabled (`xmlrpc_enabled` → false) — brute-force-amplification + pingback-DDoS vector INK never uses.
  - Username-enumeration blocked — anonymous `?author=N` archive probing redirects home; the public REST `/wp/v2/users` collection is removed for anonymous callers (kept for logged-in editors).
  - Version disclosure removed (`the_generator` emptied, `wp_generator` meta dropped).
- `TwoFactorAudit` — `wp ink audit-2fa` reports editors/administrators lacking a second factor (verifies "staff 2FA in place"; binds to the installed 2FA plugin via the `ink_security_user_has_2fa` filter). ink-core does **not** implement 2FA.

## Cloudflare (edge + login rule + origin lock)

1. Proxy the site through Cloudflare (orange-cloud DNS).
2. **Origin lock:** configure the host firewall to accept HTTP/S **only from
   Cloudflare IP ranges** — no direct origin traffic. This is what makes the edge
   non-bypassable.
3. **Login rule:** a WAF/rate-limit rule on `/wp-login.php` + `/wp-admin` (challenge
   or rate-limit; optionally country/ASN scoping). This replaces Loginizer/WordFence
   login protection.
4. Enable Cloudflare's managed WAF ruleset + bot fight mode; set security level.

## Patchstack (CVE alerts)

5. Install Patchstack; connect the site. It monitors installed plugins/themes for
   disclosed CVEs and alerts (and virtual-patches where available). This is the
   *vulnerability-intelligence* layer — it does not replace staging-gated updates
   (18.7), it prioritises them.

## Host malware scanning

6. Enable the host's server-side malware scanning (file-integrity + signature scan).
   Origin-level, independent of the WP runtime.

## Staff 2FA (identity)

7. Install the chosen 2FA plugin; **require** 2FA for `administrator` + `editor`.
8. Wire `ink_security_user_has_2fa` to that plugin's per-user status, then run
   `wp ink audit-2fa` — it must report `Alle redakteurs en administrateurs het 2FA
   aktief`. Re-run after adding any staff account.

## Updates & PCI

9. **Staging-gated updates** — see `docs/` 18.7 (update governance). Major updates go
   through staging; minor/security rely on the leak scan + Patchstack alerts.
10. **PCI scope:** PayFast is **off-site** (redirect/hosted) — INK never sees or
    stores card data, keeping PCI scope to SAQ-A. Never add an on-site card form.

## Deliberate decisions (do NOT reverse without review)

- **Loginizer / WordFence NOT used.** Cloudflare (edge login rule + origin lock) +
  Patchstack (CVE intel) + the `Ink\Security` origin hardening + host scanning cover
  the same ground without a heavy in-WP security plugin on the request path. Do not
  reactivate Loginizer (project-context retired-plugin list).
- The `Ink\Security` hardenings are opt-out via filters — if a future edge config
  duplicates one (e.g. Cloudflare strips the generator), the deployment can disable
  just that measure without code changes.
