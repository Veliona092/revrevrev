# ERD Rationale Report (Capstone Schema)

## Purpose
This report explains why the ERD in [docs/reviso-core-erd.mmd](docs/reviso-core-erd.mmd) was designed the way it is, what data sources were used, and what was intentionally excluded.

## Primary Design Rule
The ERD was built from the coded schema (Laravel migrations) as the source of truth, not from screenshots, assumptions, or legacy visuals.

Reason:
- Migrations define the actual tables, columns, keys, and constraints used by the app.
- This prevents diagram drift and avoids introducing non-existent entities.

## Data Sources Used
1. Migration definitions under [database/migrations](database/migrations)
2. Current model usage under [app/Models](app/Models)
3. Controller and route references under [app/Http/Controllers](app/Http/Controllers) and [routes/web.php](routes/web.php)

## Why These Entities Were Included
The ERD includes active capstone entities that are present in migrations and used by code paths:
- Core users/classes/enrollment: `users`, `classes`, `class_user`
- Instructional flow: `modules`, `module_progress`, `module_user_visibility`
- Assessment flow: `quiz_questions`, `quiz_attempts`, `quiz_answers`
- Class communication: `announcements`, `announcement_reads`, `chats`, `chat_user`, `chat_messages`
- Supporting features: `lectures`, `signups`, `ai_settings`
- Framework tables relevant to auth/session flow: `sessions`, `password_reset_tokens`

## Why It Does Not Match the Legacy Screenshot Style
The legacy screenshot appears to represent a different domain/schema (maintenance/assets/tickets).
This ERD is for your current capstone LMS codebase and therefore uses your capstone table set.

## Relationship Strategy
Only real database-level relationships were drawn as crow-foot edges.

Included as FK edges:
- Relationships defined via `foreignId(...)->constrained(...)` or equivalent in migrations.

Not drawn as FK edges:
- Logical relationships that exist only in app code or shared keys without FK constraints.

Examples:
1. `ai_settings` is global key-value config and has no FK to `classes` or `users`.
2. `password_reset_tokens` uses `email` and has no `user_id` FK.

Because these are not FK-constrained in schema, they remain visually unconnected in a strict relational ERD.

## Column Selection Policy
The ERD includes columns that are structurally meaningful for analysis:
- Primary keys, foreign keys, uniqueness-related identifiers, status/behavior columns, and timestamps.
- Feature-relevant fields (for example class AI summary/settings, module visibility, quiz scoring fields).

It avoids overloading with framework internals that are not useful for capstone analysis.

## Handling of Migration Evolution
Later migrations that remove/replace fields were respected.
The ERD reflects effective current schema rather than historical intermediate states.

## Why Framework Tables Were Kept
`sessions` and `password_reset_tokens` were retained because:
- They are present in schema.
- They participate in authentication/session behavior.
- They are useful for complete system-level ERD analysis.

## Legacy Safety Check Outcome
No legacy maintenance-domain tables were added.
All entities in the ERD map to current capstone migrations and active code usage.

## Limitations
1. Strict ERD does not visualize non-FK logical links.
2. If local DB is out of sync with migrations, runtime data may differ from diagram.
3. Enum semantics and app-level policy constraints are not fully represented in ERD shape alone.

## Optional Next Variant (If Needed)
A second "logical ERD" can be generated with dashed annotations for non-FK links, such as:
1. `classes.ai_settings` (json) inheriting defaults from `ai_settings`
2. `password_reset_tokens.email` logically mapping to `users.email`

## Prompt You Can Use To Audit This ERD
Use this prompt to validate quality and completeness:

"Audit [docs/reviso-core-erd.mmd](docs/reviso-core-erd.mmd) against all files in [database/migrations](database/migrations). Report:
1. Missing tables
2. Extra tables
3. Missing FK edges
4. Columns in ERD that do not exist
5. Existing columns not represented
6. Non-FK logical links worth annotating
7. Any schema changes after the latest included migration"

## Conclusion
This ERD was intentionally generated as a migration-faithful, code-aligned relational model of the current capstone system, prioritizing correctness over visual similarity to legacy diagrams.