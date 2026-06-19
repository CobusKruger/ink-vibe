# Administrative Requirements

After discussions with the site owner, several new requirements have surfaced. Some are related to functionality already being planned.

These are the new items. Each has a dedicated section below.

P0: MVP

R1. Automatic collation of challenge entries in a form suitable for anonymized judging.
R2. Automatic ingestion of challenge results and judges' commentary.
R3. Automatic Gradering (Tier) calculation and promotion.
R4. Automatic library entry update.

P1: Ready at launch

R5. Membership management updates.
R6. Account approvals and spam account detection.
R7. Automatic receipts for new posts.
R8. Analytics.

P2: Additional development after launch

R8. Annual competition management.

General

G1: Terminology updates


## R1. Automatic collation of challenge entries

We already allow writers to post their work and link it to a monthly challenge. Presently, the site owner collects all the work for such a challenge, strips out author names and sends an email to the judges. The resulting email contains the following:
* The body text of the challenge.
* The type of writing included in section of the email (gedigte, stories and artikels are listed one below the other).
* One subsection each for every gradering (Brons, Silwer, Goud).
* Each poem with an allocated number and the author information removed.

An example of such an email is available at docs/new-requirements-18-june/INK Mei projek inskrywings.eml, although that only contains Gedigte. The other types will just be appended to the bottom of the Gedigte section.

We need to add the following to the admin interface:
1. User selects a monthly challenge (selection options listed in descending date order).
2. The user is presented with an editable preview of the email. The preview is generated according to the strategy outlined below.
3. The user can edit this, provide the email address(es) and choose to send the message.

The strategy for generating the preview is as follows:
1. Sort the challenge entries according to Gradering and entry type (Gedigte, Stories, Artikels).
2. Number each entry. There are separate number sequences for each entry type. This number must be stored, as it will be used to match the entry when the results and judges' commentary is received. Let's call this the EntryID.
3. Strip the writer's name from the poem (some add it above or below the title) and any copyright notice (because it likely contains the name).
4. Draft the email as follows:
* The full text of the challenge post
* All entries ordered first by entry type, then gradering, then EntryID. For each entry show the EntryID and title (both in bold text, both on one line) followed by an empty line, followed by the full text of the entry.

## R2. Automatic ingestion of challenge results and judges' commentary

Once the judges complete their work, they send the results back as a Word document, like the one at docs/new-requirements-18-june/INK SIA MEI 2026 KOMMENTAAR EN UUTSLAE ES-1.docx.

The document starts with the winners, indicated as follows:

```
Wenners 
BRONS:          (1) No 12    (2) No 16    (3) Geen 
SILWER:         (1) No 17    (2) No 10    (2) Geen 
GOUD:           (1) No 22    (2) No 19    (3) No 11 
PROSA:          Geen 
VERHALE:      Geen 
```

Note that there are three winners announced per Gradering, each identified only by their EntryID. Ink refers to anyone placed in the top 3 in any category as a winner.

After that, all entries are listed in EntryID order. Each entry is shown in the following format: EntryID and title, a line break and the commentary.

When there is no winning entry (sometimes the result of not enough entries in a particular type/gradering group, sometimes because of quality concerns), it will be marked as "Geen", instead of listing a winning EntryID.

The user will select a monthly challenge from the list and either upload the Word document on a special area of the admin panel, or just copy the text in (that's probably much easier).

The page this happens on must then give an indication of how successful it was parsing. It will need to indicate whether it was able to identify all winners and also whether it was able to resolve commentary for every entry based on EntryID.

In order to do so, it will have to retrieve all the stored EntryIDs and match them with those from the document.

Once confirmed that all entries in all categories have been identified and have been accounted for in both the winners lists and the commentary, do the following:

1. Generate a post to announce the challenge winners and congratulate them. This must be done using a template that may be configured from time to time. Push this post to a featured position on the home page (usually the right-hand entry above the fold). At the bottom of this post, give a full index of entries for each category, with a link to each entry.
2. For each entry, add a comment (marked "Terugvoer van die moderator) containing the full text of the judges' commentary for that work. ASIDE: On the writer profile page (Skrywersprofiel) that comment will only display for the post if the writer has enabled it to.
3. For each entry, we need to update the Biblioteek page for that writer. Since the Biblioteek area still need proper analysis, this must be noted as a requirement, but not fleshed out until we address the entire biblioteek. This is the same as requirement R4.
4. Each entry must then be updated to mark it as a challenge winner. When the entry is then featured anywhere on the website, it must carry a banner (there is a Lovable design for this) to announce its status as winner. To clarify, for a December challenge, the winners must be indicated as follows:
    * Any entry that finished second or third must be identified as "Desember wenner" with text and icon color identifying it as Goud, Silwer or Brons. This is the same whether the entry is a Gedig, Story, or Artikel.
    * Any entry that finished first must be identified as "Desember algehele wenner" with the same color rules applied.
5. Each winning entry must then be added to the featured feed on the home page, with "algehele wenner" entries being placed before the rest (in other words, better position in the challenge results ensure more prominent placement).
6. The Gradering calculation (shown below) must be performed.

## R3. Automatic Gradering (Tier) calculation and promotion

The Gradering mechanism is fundamental to the way Ink supports writer development. Everybody starts out with a Brons gradering and moves on to Silwer and Goud based on their performance in competitions. Writers only compete in the same pool as writers with the same gradering.

Therefore, after the process already outlined, it is essential to update the gradering of each winning writer, e.g. every writer that was placed in the top 3 of any entry type for the writer's current gradering. For the purposes of the calculation, there is no difference between being placed first, second or third. It is also allowable for a writer to occupy several positions. In fact, theoretically, one writer could take all three winning positions in all three entry types in one month. This matters for the calculation below.

These are the thresholds used:
1. All writers start out Brons
2. After 5 total wins in Brons categories, a writer is promoted to Silwer.
3. After 15 total wins in Silwer categories, a writer is promoted to Goud.

Promotion can also be done manually by editorial staff.

Directly after promotion, the writer is considered to have zero wins on the new gradering. For example, if a writer has already achieved top 3 placement four times in Brons and now is placed in the top 3 in Brons simultaneously for Gedigte and Stories, the writer is promoted to Silwer. The fact that he had a total of six Brons wins is of no consequence.

It is also important to note that a writer may place multiple times in the top 3 of a single category, and those all count as separate wins for the calculation. For example, a writer is placed both first and third for two separate Gedigte submitted for the monthly challenge. Additionally, the writer is also placed second for Stories. The calculation must then take all three wins into account.

This information is stored on the writer's profile.

On the public profile (Skrywersprofiel), the status is simply reflected as Brons, Silwer or Goud. On the writer's private page, there is a line of text directly below the gradering to show how many wins are needed to reach the next gradering. For example:
    * Brons, with the subtext, "4 top 3 uitslae nodig om Silwer te bereik"
    * Silwer, with the subtext, "8 top 3 uitslae nodig om Goud te bereik"
    * Goud will have no subtext, because there is no next level to be promoted to.

When a writer is promoted, we need to send them an email to congratulate them, using a template that may be adjusted from time to time.

As a final note, there is actually one gradering above Goud, but a writer can only be promoted to that graderiing manually through the admin panel. The gradering is called "Meester" and is indicated in red text (as opposed to the gold, silver and bronze text for the lower levels). Presently there are no Meester writers, but it is an option the site owner wants to have available for special recognition of publication success.

## R4. Automatic library entry update

This will be fleshed out later, along with the rest of the biblioteek analysis.

## R5: Membership Management Updates

Presently, the memberships are managed by WooCommerce memberships, which will expire the membership according to its own rules. Memberships are activated manually by the site owner once payments are received by EFT.

The new requirement is to automatically activate the membership when a member pays using Payfast. We also want to give them the option to setup recurring payment.

On a six-month membership, we want to send an email (again, templated) to warn them that their membership will expire in a month. If the user has set up recurring payment, we only warn them that their membership will renew in one month. 

On any membership, we also send an email (templated) to warn them a week before the membership expires or renews.

When a membership is activated, whether manually through the admin panel, as a new Payfast payment, or after a recurring payment, we want to send a member an email (templated) to thank them for their continued support.

All of these emails must be configurable in the following ways:
1. Template used
2. Whether to send it or not, distinguishing between monthly, 3-month and 6-month memberships.

We should also have the option of offering a discount to members signing up for a recurring renewal.

## R6. Account approvals and spam account detection

When a new account is created (we're not talking about paid subscriptions here, creating just an account is free), we need to prevent spammers taking over.

1. Evaluate technological ways to block spam account creation. I know nothing about this.
2. I think social logins may help ameliorate the problem, but I don't know how accurate that is.
3. As a backstop, we may need to mandate manual account approval by an admin user.

## R7. Automatic receipts for new posts

Right now, the site owner explicitly places a comment on every new Gedig, Storie or Artikel to thank the writer for submission. The goal is to acknowledge receipt and also let a new user feel like their work is being seen.

There are several problems with this:

1. Workload. We definitely want to automate this.
2. No variation in the message. Obviously, this is related to the workload, but we can probably send the user a message randomly drawn from a preconfigured list.
3. We previously decided to disable WordPress comments. If that is not used, I guess we can send a notification to the writer's private profile (My Profiel).

## R8. Analytics

The current site has no (known) analytics provider configured. We need to choose one that will work well with the WordPress site.

Aside from the regular analytics (or perhaps built from it), we still need to show some statistics like read count on the writer's private profile (My profiel).

## General

### Terminology refinements

1. Whenever the terms "public profile" and "private profile" these refer, respectively, to the writer's public profile (shown to other people) and the Skrywersprofiel page, shown only to the writer.
2. Any reference in provided documents to a membership, subscription or intekening refers to the writer's "lidmaatskap". And members are referred to as 'n "lid" (singular) or "lede" (plural). There should also be a distinction between a gratis lid (unpaid member), who has an account and is free to read, comment, follow, and maintain a reading list, and a betaalde lid (paid member, or subscriber) who may additionally post and access all the training material. For P2, we may want to explore teaser material to try to convert gratis leded to betaalde lede.
3. Any reference to a writer's tier or level refers to the Gradering.