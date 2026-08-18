# Report Correction Plan

**Date:** March 31, 2026  
**Target File:** `docs/reportreviso.md`  
**Scope:** Correct the confirmed documentation issues without changing application code

---

## Objective

This plan covers the confirmed problems in `reportreviso.md`:

1. Wrong database listed
2. Livewire framed as active when it appears unused
3. Incomplete route table
4. Route-name mismatch
5. Legacy lecture/debug pieces mixed into active inventory

---

## 1. Fix the Database Statement

### Problem
The report currently says the development database is SQLite.

### Why It Is Wrong
The app is configured to use MySQL/MariaDB in `.env`:
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_DATABASE=reviso`

The SQLite value only exists as Laravel's fallback default in `config/database.php` if no environment variable is set.

### Planned Change
Update the Technology Stack table entry from:
- `SQLite (dev) / configurable`

to something like:
- `MySQL/MariaDB (current dev environment); Laravel fallback default is SQLite if env is unset`

### Validation
After editing, the database line should accurately describe the actual active environment rather than the framework default.

---

## 2. Reframe Livewire as Installed, Not Active

### Problem
The report currently lists Livewire as if it is part of the active frontend implementation.

### Why It Is Misleading
- `livewire/livewire` is installed in `composer.json`
- No app-level Livewire usage was confirmed in `app/` or `resources/views/`
- The UI appears to be implemented with Blade, jQuery, and AJAX instead

### Planned Change
Replace the current stack wording so it no longer implies active Livewire usage.

### Recommended Wording
Use one of these approaches:
- `Livewire | Installed dependency, no confirmed active usage in current app flows | v4.1`
- or remove it from the main stack table and mention it in a note below the table as an installed but currently unused package

### Validation
The report should distinguish between:
- installed dependencies
- actively used application architecture

---

## 3. Expand the Route Table or Reframe It as Partial

### Problem
The route summary omits several active AJAX and management routes.

### Confirmed Missing Routes
These exist in `routes/web.php` and should be reflected if the report claims to summarize the system routes:

- `DELETE /classes/{class}/students/{student}`
- `GET /classes/{class}/students`
- `GET /classes/{class}/modules/list`
- `GET /classes/{class}/announcements/feed`
- `DELETE /modules/{module}`
- `GET /modules/{module}/quiz/questions`
- `GET /student-performance/{class}/students/{student}`

### Planned Change
Choose one of these two approaches:

### Option A — Make the table more complete
Add the missing AJAX and teacher workflow routes into the existing route summary.

### Option B — Reframe the section honestly
Rename the section to something like:
- `Representative Route Summary`
- `Key Route Summary`
- `Selected Route Summary`

Then explicitly note that the list is not exhaustive and that the full route count is 122.

### Recommended Approach
Use Option B unless you want a much longer report. The current route section is clearly selective, so it should be labeled that way.

### Validation
The section should no longer imply completeness unless it actually includes the missing active routes.

---

## 4. Correct the Route-Name Mismatch

### Problem
The report lists the route name for refreshing AI summary as:
- `student.performance.refresh-ai`

### Why It Is Wrong
The actual route name in `routes/web.php` is:
- `student.performance.refresh`

### Planned Change
Update the route table and any descriptive text so the route name matches the actual named route.

### Validation
Every route name shown in the report should match the registered route definitions exactly.

---

## 5. Separate Legacy Inventory from Active Inventory

### Problem
The report mixes legacy components into the main active system inventory.

### Affected Items
- `Lecture` model
- `LectureController`
- lecture routes
- `TestAiController`
- `/test-ai-laravel` debug route

### Clarified Context
- `Lecture` and `LectureController` are from the old system
- `TestAiController` is a debug/dev utility, not core product functionality

### Planned Change
Move these items out of the main active controller/model descriptions and into a dedicated section such as:
- `Legacy / Transitional Components`
- `Legacy and Debug Artifacts`
- `Non-Core Components Still Present in Repository`

### Recommended Structure
Keep the active system inventory focused on the current Reviso architecture:
- `Module` as the current learning-content model
- active teacher/student/admin flows
- production-relevant controllers and pages

Then add a smaller section noting that the repository still contains older lecture-era code and a dev AI test endpoint.

### Validation
A reader should be able to distinguish:
- current product architecture
- old code still present in the repository
- debug-only artifacts

---

## Suggested Edit Order

1. Correct the database row
2. Reword Livewire in the stack section
3. Fix the route-name mismatch
4. Reframe or expand the route summary section
5. Move lecture/debug items into a legacy/debug section

This order minimizes inconsistency while editing.

---

## Recommended End State

After the report is revised, it should communicate:

- the actual running database environment
- which dependencies are installed versus actively used
- whether the route list is complete or representative
- exact route names where referenced
- a clear separation between current architecture and legacy/debug leftovers

---

## Out of Scope

This plan does **not** include:
- deleting legacy files
- removing routes
- changing controllers or models
- refactoring any application code

This is a documentation correction plan only.
