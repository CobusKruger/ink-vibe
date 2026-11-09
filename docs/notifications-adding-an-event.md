# Adding an email event

Events are defined in code. The admin edits their copy at **Instellings → INK e-posse** (subject, basic-HTML body, on/off, per-term switches, test send, reset). Members opt out of **social** events on **My Profiel → Kennisgewings**, or from the link in the email footer. **System** events always send.

## 1. Register the event (once, in the owning module's `register()`)

```php
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Template;

Notifications::registerTemplate(
	new Template(
		'ink_reaksie_ontvang_email',                       // key, ink_-prefixed
		__( 'Iemand het op jou werk gereageer', 'ink-core' ),   // default subject (Afrikaans, literal __())
		__( '<p>Hallo {skrywer},</p><p>{kommentator} het op "{werk}" gereageer.</p>', 'ink-core' ),
		false,                                              // OFF until the owner approves the copy
		kind: Template::KIND_SOCIAL,                        // or KIND_SYSTEM (default)
		group: __( 'Sosiaal', 'ink-core' ),                 // admin heading
		label: __( 'Reaksie op my werk', 'ink-core' ),      // admin event name
		tokens: array( 'skrywer', 'kommentator', 'werk' ),  // shown in the admin
		category: 'reaksies',                               // social only: opt-out category
		categoryLabel: __( 'reaksies op my werk', 'ink-core' ),
		terms: array(),                                     // lidmaatskap only: e.g. array( 6, 12 )
	)
);
```

## 2. Fire it where it happens

```php
Notifications::fire( 'ink_reaksie_ontvang_email', $user_id, array(
	'kommentator' => $name,
	'werk'        => $title,
) );
```

`fire()` resolves the member's address and name (`{skrywer}` defaults to display name), applies the on/off switch and, for social events, the member's opt-out, then sends. Social mail is queued via Action Scheduler and carries an unsubscribe footer. Context values must be scalars.

## Rules

- New events default to **off**; the owner enables them once the copy is approved.
- Merge is plain `{token}` substitution only (AD-9: no logic, no loops). Values are HTML-escaped.
- Modules may not call `Ink\Entitlement` / `Ink\Tiers` from Notifications. Fire from the owning module, or listen to its `ink/...` action.
- A new social category appears in the member's preferences automatically.
- Existing events that still call `Notifications::send( $key, $email, … )` keep working; they show in the admin too.

## Current events

| Key | Kind | Fired from |
|---|---|---|
| `ink_reaksie_ontvang_email` | social (`reaksies`) | `Notifications\Events::onComment` |
| `ink_uitdaging_wen_email` | system | `Challenges\WinEmail` (`ink/uitdaging_wen`, from `Ingestion::commit`) |
| `ink_membership_activated_email` | system, per-term | `Entitlement\PurchaseActivation` |
| `ink_membership_deactivated_email` | system | `Entitlement\PurchaseActivation` (active → expired/cancelled) |
| `ink_membership_expiry_1week_email` / `_1month_email` | system, per-term | `Entitlement\LifecycleEmails` |
| `ink_tier_promoted_silwer_email` / `_goud_email` | system | `Tiers\PromotionEmails` |
| `ink_welcome_*`, approval, rejection | system | `Accounts\*` |
