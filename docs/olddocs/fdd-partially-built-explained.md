# PARTIALLY BUILT Explained

Last updated: March 30, 2026

This file explains what "PARTIALLY BUILT" means for each affected item in the FDD matrix.

## 1) Password reset capability

Why it is PARTIALLY BUILT:
- Reset routes and pages exist.
- But implementation behavior mixes a standard reset flow with a temporary-password email path.

What exists now:
- Users can request password reset actions.
- Reset-related views and endpoints are available.

What is still missing for FULLY BUILT:
- One clearly defined reset approach that fully matches the FDD contract.
- Consistent behavior and messaging across all reset paths.

Definition of done:
- Password reset follows one approved flow end-to-end and passes acceptance tests.

---

## 2) AI runtime generation (quiz/insights/summary)

Why it is PARTIALLY BUILT:
- AI service usage is present.
- But strict FDD expectations may require more explicit, separated runtime contracts per use case.

What exists now:
- AI generation is used in quiz and analysis-related flows.
- Settings and toggles are integrated.

What is still missing for FULLY BUILT:
- Confirmed one-to-one mapping between each FDD AI capability and implementation contract.
- Clear method-level contract alignment where required by spec.

Definition of done:
- Every FDD AI capability maps cleanly to implemented runtime behavior and acceptance checks.

---

## 3) Email notifications for approval/rejection/profile

Why it is PARTIALLY BUILT:
- Email service is present.
- But controllers call `sendMail(...)` while the service defines `send(...)`.

What exists now:
- Email integration wiring and trigger points in approval/profile flows.

What is still missing for FULLY BUILT:
- Method-call alignment so email dispatch works reliably in real execution.
- Verified successful sends for approval, rejection, and profile-triggered email events.

Definition of done:
- All relevant flows send emails successfully with no runtime method mismatch.

---

## 4) Layout consistency by role

Why it is PARTIALLY BUILT:
- New role-based layouts are in use.
- But legacy layout structure still exists and is not fully normalized.

What exists now:
- Teacher/student/admin layout shells are available and used by many pages.

What is still missing for FULLY BUILT:
- Consistent layout strategy across all active pages.
- Removal or formal deprecation of legacy layout usage paths.

Definition of done:
- All active role pages render through one consistent layout standard.

---

## 5) Migration contract fidelity to FDD wording

Why it is PARTIALLY BUILT:
- Core schema exists and works.
- But some names/columns do not exactly match strict FDD wording.

What exists now:
- Required domain tables are available and functional.

What is still missing for FULLY BUILT:
- Exact schema contract alignment where strict naming/column parity is required.
- Formal sign-off if spec is updated instead of code.

Definition of done:
- Either schema matches strict FDD contract exactly, or deviations are officially approved and documented.

---

## 6) Model API naming fidelity to FDD wording

Why it is PARTIALLY BUILT:
- Core relationships are implemented.
- But some expected alias/convenience names differ from FDD naming expectations.

What exists now:
- Functional model relations used by current features.

What is still missing for FULLY BUILT:
- Relation naming alignment (or documented canonical naming agreement).
- Alias/convenience relations where external expectations require them.

Definition of done:
- Model relation contracts are either aligned to FDD names or formally documented as accepted equivalents.

---

## 7) Route contract fidelity to FDD URL patterns

Why it is PARTIALLY BUILT:
- Routes are functional.
- But some paths/names differ from strict FDD URL pattern wording.

What exists now:
- Endpoints required for feature behavior are available.

What is still missing for FULLY BUILT:
- Exact route naming/path parity where required by integration, QA scripts, or external docs.
- Or formal contract update reflecting current route design.

Definition of done:
- Public route contracts are exact-match to FDD or formally updated with stakeholder approval.

---

## 8) Announcements lifecycle (validation architecture)

Why it is PARTIALLY BUILT (quality perspective):
- Announcement CRUD behavior works.
- But the dedicated Form Request class remains stubbed/unused.

What exists now:
- Teacher announcement create/edit/delete/list flows are working.

What is still missing for FULLY BUILT:
- Centralized authorization and validation through a proper Form Request.
- Consistent validation architecture matching project conventions.

Definition of done:
- Announcement endpoints use a completed Form Request with authorization and full validation rules.

---

## Quick Priority View

High impact partials to resolve first:
1. Email notifications method mismatch.
2. Contract fidelity decisions (schema/model/routes) if strict sign-off is required.

Medium impact partials:
1. Password reset contract consistency.
2. AI runtime contract granularity alignment.
3. Layout normalization.
4. Announcement validation architecture completion.
