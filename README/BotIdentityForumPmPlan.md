# Bot Identity, Forums, and PMs Plan

## Goal

Support richer bot-driven social features on the website without forcing every bot character to be backed by its own real account.

The desired end state is:

- Human players remain account-owned for permissions, moderation, and inbox ownership.
- Forum posting is character-based, not account-name based.
- Bot personas can post as characters in the correct realm or expansion forums.
- PMs can flow between a human account and a bot character identity.
- Guild recruitment, level milestones, and other DB-derived events can produce forum posts or PMs cleanly.

## Core Design Principle

Separate these two concepts:

- Ownership: who controls or is responsible for the action
- Speaking identity: who appears as the poster or sender

For humans:

- Ownership is the player account
- Speaking identity is usually the selected character for forums
- Speaking identity is usually the account for normal human-to-human PMs

For bots:

- Ownership may be `NULL`, a service account, or an internal bot owner
- Speaking identity is always a bot character persona

This avoids trying to make one account pretend to be nine different bot characters.

## Current State In This Codebase

The current forum system is already partly identity-aware:

- `f_posts` stores `poster`, `poster_id`, and `poster_character_id`
- forum posting in `components/forum/forum.post.php` already posts with the selected character name

The current PM system is still account-bound:

- `website_pms` uses `owner_id` and `sender_id`
- PM UI in `components/account/account.pms.php` joins directly to the `account` table

This means the forum side is close to the target model, while PMs need the larger redesign.

## Recommended Data Model

### 1. Add a website identity table

Create a new table such as `website_identities` to represent all visible speakers on the site.

Suggested fields:

- `identity_id` PK
- `identity_type` enum-like string
  - `account`
  - `character`
  - `bot_character`
- `owner_account_id` nullable
- `realm_id` nullable
- `character_guid` nullable
- `display_name`
- `forum_scope_type` nullable
- `forum_scope_value` nullable
- `guild_id` nullable
- `is_bot`
- `is_active`
- `created_at`
- `updated_at`

Notes:

- Human accounts should get an `account` identity row.
- Human characters used for forums should get `character` identity rows.
- Bot personas should get `bot_character` identity rows.

### 2. Keep account ownership for humans

Human account ownership should continue to drive:

- login
- permissions
- moderation
- inbox ownership
- profile ownership

Do not replace the account model. Add identities alongside it.

### 3. Move forums to identity-based posting

Recommended changes:

- `f_topics`
  - add `topic_poster_identity_id`
  - optionally add `topic_poster_account_id`
- `f_posts`
  - add `poster_identity_id`
  - optionally add `poster_account_id`
  - keep `poster_character_id` for compatibility and quick lookups

Suggested behavior:

- Human forum posts are authored by the selected character identity.
- Bot forum posts are authored by a bot character identity.
- The human account is still stored as owner for audit and moderation when applicable.

### 4. Move PMs to identity endpoints

Recommended changes to `website_pms`:

- add `sender_identity_id`
- add `recipient_identity_id`
- optionally keep or rename:
  - `sender_account_id`
  - `recipient_account_id`

Suggested routing rules:

- human -> human: account identity to account identity
- human -> bot: account identity to bot character identity
- bot -> human: bot character identity to account identity

This preserves one inbox per human account while allowing bots to speak as characters.

## Forum Scope and Posting Rules

### Forum scope

Add forum-level scope rules so characters can only post where they belong.

Suggested fields on `f_forums`:

- `scope_type`
  - `all`
  - `realm`
  - `expansion`
  - `guild_recruitment`
  - `event_feed`
- `scope_value`

Examples:

- Classic forum: `scope_type = expansion`, `scope_value = classic`
- TBC forum: `scope_type = expansion`, `scope_value = tbc`
- Realm 1 guild recruitment: `scope_type = guild_recruitment`, `scope_value = 1`

### Validation rules

Before a forum post is accepted:

- user must be logged in for human-authored content
- selected speaking identity must be valid
- if human character identity:
  - character must belong to the logged-in account
  - character must belong to the correct realm or expansion scope
- if guild recruitment:
  - character must be guild leader or an approved guild rank
- if bot identity:
  - bot must be allowed to post in that scoped forum

## PM Rules

### Human PM behavior

Humans should stay account-based for ownership and inbox display.

That means:

- a player sees one inbox for their account
- PM permissions and moderation remain account-driven
- normal player-to-player PMs still behave as account conversations

### Bot PM behavior

Bots should reply as character identities.

That means:

- player sends to bot persona
- bot responds as that named character
- player receives the reply in their account inbox

This gives immersive persona-based conversations without multiplying real accounts.

## Content Sources

There should be a clear source tag on generated or assisted content.

Suggested field on forum topics or posts:

- `content_source`
  - `player`
  - `player_assisted`
  - `system_event`
  - `bot_generated`

This helps with:

- transparency
- moderation
- filtering
- debugging

## Bot Posting Categories

Two major categories make sense.

### 1. Player-authored or player-owned content

Examples:

- guild recruitment posts
- guild update posts
- player-authored classifieds or notices

Suggested rule:

- the post is visibly authored by a character identity
- the owning human account is recorded under the hood

### 2. System-authored event content

Examples:

- level milestone posts
- major quest chain completion posts
- dungeon or raid first clears
- profession milestone posts
- realm activity summaries

Suggested rule:

- the post is generated from DB events
- it is spoken by a bot character identity
- it is tagged as `system_event` or `bot_generated`

## Guild Recruitment Plan

Guild recruitment is a good first feature because it mixes account ownership with character-based authorship cleanly.

Recommended behavior:

- only guild leader characters can create or manage guild recruitment threads
- the visible author is the selected guild leader character
- the owning account is stored for moderation and edit permissions
- one active recruitment thread per guild per scope is recommended

Helpful extra fields on recruitment threads:

- `guild_id`
- `managed_by_account_id`
- `recruitment_status`
- `last_bumped_at`

Recommended validations:

- verify the selected character is in the guild
- verify the selected character rank is guild leader or approved recruiter
- verify the forum is the correct guild recruitment forum for that realm or expansion

## DB-Derived Event Posting Plan

Do not let the website directly create posts from raw DB reads in ad hoc code paths.

Instead add an event pipeline.

### Event pipeline

1. Detect interesting game events from the DB
2. Insert normalized rows into a `website_bot_events` table
3. Deduplicate events
4. Choose a target channel
   - forum post
   - PM
   - no action
5. Choose a speaking identity
6. Generate content
7. Store result and mark event processed

### Suggested `website_bot_events` fields

- `event_id`
- `event_type`
  - `level_up`
  - `quest_complete`
  - `guild_recruitment`
  - `raid_clear`
  - `profession_milestone`
- `realm_id`
- `account_id` nullable
- `character_guid` nullable
- `guild_id` nullable
- `payload_json`
- `dedupe_key`
- `occurred_at`
- `processed_at`
- `status`

### Dedupe examples

- `level_up:realm1:guid123:level40`
- `quest_complete:realm1:guid123:quest778`
- `guild_recruitment:realm1:guild55`

## Event Selection Rules

Avoid noisy automation. Not every event should make a post.

### Good candidates

- level milestones like 10, 20, 40, 60
- major quest chain completions
- dungeon or raid first clears
- guild recruitment refreshes
- profession milestones
- curated realm news summaries

### Avoid by default

- every single quest completion
- every level gain
- every item pickup
- repetitive event spam

The content should feel socially meaningful, not like a combat log dumped into the forum.

## Content Generation Strategy

Use templating first. Add LLM generation later as a formatting layer.

Recommended order:

1. Detect event
2. Build a structured payload
3. Render from deterministic template
4. Optionally let an LLM rewrite or polish
5. Save final approved content

This keeps the system reliable even when the LLM is offline, rate-limited, or producing weak output.

## Suggested Implementation Phases

### Phase 1: Identity foundation

- add `website_identities`
- backfill account identities
- backfill character identities
- add bot character identities

### Phase 2: Forum cleanup

- add identity columns to `f_topics` and `f_posts`
- update forum posting to write identity IDs
- add forum scope validation
- keep compatibility with current `poster_character_id`

### Phase 3: PM redesign

- add sender and recipient identity IDs to `website_pms`
- update PM listing and thread queries
- preserve account inbox ownership for humans
- support bot character speakers

### Phase 4: Guild recruitment

- add guild leader validation
- add recruitment forum scope
- add one-thread-per-guild safeguards if desired

### Phase 5: Bot event pipeline

- add `website_bot_events`
- add scanners or import jobs for DB-derived events
- add templated posting

### Phase 6: LLM enhancement

- use LLM only to improve tone and variation
- do not make the LLM responsible for core routing or permissions

## Minimal Viable First Slice

If this should be rolled out in the smallest practical slice, start here:

1. Add `website_identities`
2. Make forum posts explicitly identity-based
3. Restrict forum posting by expansion or realm scope
4. Implement guild recruitment as character-authored, account-owned threads

This delivers visible value quickly and keeps PM redesign separate until the forum model is stable.

## Key Risks

### Data consistency

If identity data is split between account, character, and bot rows without clear ownership rules, moderation and editing permissions will become messy.

### Spam volume

If DB-derived event posting is not throttled and deduplicated, forums will become unreadable.

### Cross-realm confusion

If forum scope is not enforced strictly, characters may end up posting in the wrong expansion or realm forum.

### Backward compatibility

The existing forum code still expects account joins in several places, so migration should be additive first, then cleanup later.

## Recommended Next Build Step

Implement the identity layer first and wire forums to it before touching PMs or LLM posting.

That gives the project a stable base for:

- guild recruitment by guild leader characters
- scoped realm or expansion forum posting
- later bot persona PM support
- future DB-derived event posting

## Guild Roster Update Plan

This should be implemented as a reply-style bot event, not as a new topic.

Recommended event name:

- `guild_roster_update`

Recommended behavior:

- reply into the guild's existing active recruitment thread
- use the guild leader as the visible speaker by default
- allow marked officers to speak for the guild when needed
- keep the forum reply readable, but keep the full roster delta in JSON for later LLM use

### Trigger Rules

Trigger a roster update event when:

- roughly `8-13` new members have joined since the last roster forum update
- any members have left since the last roster forum update
- or joins exceed `13`, in which case the batch should still post once rather than waiting forever

Recommended cooldown:

- `12-24` hours per guild thread

This keeps recruitment threads active without turning them into spam feeds.

### Central JSON Storage

Store guild summary JSON centrally under:

- [jsons](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/jsons)

Recommended sub-structure:

- [jsons/guilds](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/jsons/guilds)
- [jsons/guilds/realm-1](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/jsons/guilds/realm-1)
- [jsons/guilds/realm-2](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/jsons/guilds/realm-2)
- [jsons/guilds/realm-3](/C:/Users/japte/Downloads/SPP_Classics_V2/SPP_Server/Server/website/jsons/guilds/realm-3)

Recommended file naming:

- `guild-{guild_id}.summary.json`

Why keep this in JSON:

- easy to diff roster snapshots
- easy to feed into a future LLM prompt
- easy to inspect manually during admin/debug work
- avoids scattering guild-summary state across multiple partial tables

### Guild Summary JSON Shape

One current summary file per guild is enough to start.

Example:

```json
{
  "realm_id": 1,
  "guild_id": 55,
  "guild_name": "Shadowborn",
  "thread_topic_id": 123,
  "recruitment_status": "active",
  "posting_identity": {
    "mode": "guild_leader_or_marked_officer",
    "leader_guid": 1001,
    "leader_name": "Puhen",
    "officer_guids": [1002, 1003],
    "officer_names": ["Officerone", "Officertwo"]
  },
  "roster": {
    "member_count": 42,
    "member_guids": [1001, 1002, 1003],
    "captured_at": "2026-03-28 19:00:00"
  },
  "last_forum_roster_post": {
    "post_id": 456,
    "posted_at": "2026-03-28 18:00:00",
    "joined_count": 9,
    "left_count": 2
  },
  "pending_delta": {
    "joined_guids": [1008, 1009],
    "left_guids": [1010]
  }
}
```

### Officer Marking

Guild leader should be the default allowed poster.

In addition:

- officers may be explicitly marked as allowed recruitment posters
- those officer GUIDs should live in the guild summary JSON
- this makes the posting rule explicit and easy to reuse later for PMs or LLM-generated updates

Recommended rule:

- leader may always post
- marked officers may also post
- everyone else is read-only from the recruitment-management side

### Scanner and Processor Split

Scanner responsibilities:

- load the current guild roster from `guild_member`
- load the current guild summary JSON if it exists
- compare current member GUIDs to the previous snapshot
- compute joined and left member GUIDs
- create a `guild_roster_update` event when threshold and cooldown rules are met
- update the guild summary JSON snapshot

Processor responsibilities:

- find the guild's active recruitment thread
- choose the speaking identity from the guild summary JSON
- reply into the thread instead of creating a new topic
- update `last_forum_roster_post` in the guild summary JSON

### Forum Copy Guidance

Keep the forum reply readable:

- if only a few members joined or left, names can be listed directly
- if the change set is larger, the forum reply should stay compact
- the full member delta should still be preserved in the guild summary JSON

This keeps the public thread easy to read while preserving richer structured state for future LLM writing.

### Recommended Next Build Slice For This Feature

1. Create `jsons/guilds/realm-{id}` folders and guild summary writer
2. Add `guild_roster_update` to the bot event scanner
3. Add reply-post support to `process_bot_events.php`
4. Add officer-marking support inside the guild summary JSON
5. Use the guild summary JSON as the future LLM input source
