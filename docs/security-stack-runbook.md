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

## Registration anti-spam (Story 18.10, R6)

`Ink\Accounts\RegistrationGuard` is the always-on registration anti-abuse surface
(part of this security stack), layered around the optional pending-approval state
(Story 3.6, `Ink\Accounts\Approval`):

- **Honeypot + submit timing** — built in; a filled hidden field or a sub-3-second
  submission is blocked (no config needed).
- **Challenge (Cloudflare Turnstile)** — wire it: render the Turnstile widget on the
  registration form and verify the token server-side, returning the verdict through
  `add_filter( 'ink_registration_challenge_passed', /* bool */, 10, 1 )`. Until wired,
  the gate defaults to pass (never blocks legitimate signups prematurely). Turnstile
  is the natural fit since Cloudflare is already the edge.
- **Per-IP rate-limit** — built in (≤ 5 attempts / 15 min per IP, via a transient).
- **Blocked-attempt analytics** — `do_action( 'ink/registration_blocked', $reason, $ip )`
  fires on every block; hook it to your logging/analytics (18.9) for visibility.
- **Optional pending-approval (3.6)** — enable `ink_account_approval_enabled` to add a
  manual approval queue on top (off by default; never the launch default).

## Deliberate decisions (do NOT reverse without review)

- **Loginizer / WordFence NOT used.** Cloudflare (edge login rule + origin lock) +
  Patchstack (CVE intel) + the `Ink\Security` origin hardening + host scanning cover
  the same ground without a heavy in-WP security plugin on the request path. Do not
  reactivate Loginizer (project-context retired-plugin list).
- The `Ink\Security` hardenings are opt-out via filters — if a future edge config
  duplicates one (e.g. Cloudflare strips the generator), the deployment can disable
  just that measure without code changes.
