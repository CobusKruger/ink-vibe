# Analytics-provider decision (Story 18.9, FR-44b / R8)

INK had no analytics provider; read counts (Story 9.12, on My Profiel) needed a real
data source, and the naive per-request counter (Story 8.3) counted bots and self-views.
This story selects a **vetted analytics plugin** and wires a provider-agnostic **seam**
(`Ink\Discovery\Analytics`) — analytics is **not** reimplemented in ink-core.

> Source of record: `_bmad-output/planning-artifacts/epics.md` Story 18.9;
> `_bmad-output/project-context.md` (don't reimplement plugin capability); OQ-3 (POPIA).

## Decision

**Provider: a privacy-respecting, cookieless, self-hosted analytics plugin** (e.g.
**Burst Statistics** or **Independent Analytics**) — chosen for POPIA fit (OQ-3):

- **Cookieless / no personal-data storage by default** — no consent banner needed for
  basic counts; IP is anonymised/hashed, not stored raw.
- **Self-hosted** — data stays on the INK origin (no third-party data export, unlike
  Google Analytics), which keeps the POPIA cross-border-transfer question closed.
- **WordPress-native** — exposes per-post view data ink-core can read via the seam.

The final pick between the two candidates is confirmed on staging against real traffic;
both satisfy the constraints. **Not** Google Analytics / Jetpack Stats (third-party data
export sharpens, rather than answers, OQ-3).

## The seam (`Ink\Discovery\Analytics`)

ink-core stays provider-agnostic — the chosen plugin wires in via filters/actions:

| Concern | Seam | Default (no provider) |
|---|---|---|
| Is a provider wired? | `apply_filters( 'ink_analytics_provider_active', false )` | false → ink-core fallback counter |
| Record a read view | `do_action( 'ink/analytics_record_view', $post_id, $author_id )` | `ReadCount` increments `_ink_read_count` + author total |
| Read a work's count | `apply_filters( 'ink_analytics_view_count', $meta_count, $post_id )` | the `_ink_read_count` meta |

**Hardening (Story 8.3 deferred → done here):** `Analytics::shouldRecordView()` drops
obvious bots (user-agent tripwire) and an author viewing their **own** work before any
view is recorded, so counts mean something even on the fallback counter.

### Wiring the chosen plugin (on staging)

1. Install + activate the vetted plugin; configure cookieless / IP-anonymised mode.
2. `add_filter( 'ink_analytics_provider_active', '__return_true' );`
3. `add_action( 'ink/analytics_record_view', /* forward to the plugin's record API */ );`
4. `add_filter( 'ink_analytics_view_count', /* return the plugin's per-post count */, 10, 2 );`

With those wired, the My Profiel read-count surface (9.12) and the discovery
"Meeste gelees" sort transparently read the provider's data; with them absent, the
ink-core fallback keeps working. The bot/self-view hardening applies either way.

## POPIA (OQ-3) — sharpened

- Basic read counts via a cookieless, self-hosted, IP-anonymised provider are **low
  POPIA risk** and need no consent banner.
- If a future requirement needs per-user analytics (identifying who read what), that
  **does** trigger POPIA processing/consent obligations — flagged for the POPIA review
  (OQ-3) before any such feature ships. The seam keeps that decision in one place.
