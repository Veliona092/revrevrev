# Final Lecture Structure Report and Implementation Plan

## Confirmed Direction

The current direct-file Module format will no longer be the final Lecture format.

A Lecture will be one container with a fixed learning order:

```text
Lecture
├── Pre-Test                 optional, uploaded by the teacher
├── Content
│   ├── Domain 1
│   │   ├── Subdomain / Lesson 1.1
│   │   └── Subdomain / Lesson 1.2
│   ├── Domain 2
│   │   └── Subdomain / Lesson 2.1
│   └── Domain 3
└── Post-Test                optional, uploaded by the teacher
```

The important rule is placement:

- If a Pre-Test exists, it is always shown first.
- Content Domains/Subdomains are always in the middle.
- If a Post-Test exists, it is always shown last.
- The teacher may upload either test when needed, but neither test becomes a separate ordinary Module.
- There should be no new Lecture content that opens directly as a standalone PDF/file from the module outline.

## What “Module” Means After the Change

For this Lecture feature, the existing Module record becomes the Lecture container/header. Its old direct file content must no longer be the primary student experience.

The student should see:

```text
Lecture title
  Pre-Test
  Content
    Domain 1
      Subdomain/Lesson 1.1
      Subdomain/Lesson 1.2
    Domain 2
  Post-Test
```

The current database `modules` table can remain as the parent record for compatibility and relationships. The UI and creation workflow must stop treating a Lecture as a direct document module.

## Current System Findings

### Already implemented

- `module_subparts` exists and is represented by `ModuleSubpart`.
- `subpart_lessons` exists and is represented by `SubpartLesson`.
- Progress tables exist for Domains/Subparts and Lessons.
- Pre-Test/Post-Test questions use `quiz_stage` values of `pre_test` and `post_test`.
- Student page already has a Pre-Test -> Content -> Post-Test shell.
- Student nested Domain -> Lesson rendering has been started.
- Lesson CRUD controllers and lesson routes now exist.
- Teacher Lecture Content dialog can create and delete Domains and Lessons.

### Current blockers or incomplete behavior

1. Existing document modules can still open directly as a file.
2. A new plain document module may not be discoverable as a Lecture container because Lecture discovery currently depends on existing subparts or tests.
3. The teacher UI still begins from the old Upload Document flow, so it can create the wrong type of item for the new Lecture structure.
4. Pre-Test/Post-Test management is still embedded beside older standalone assessment flows and needs to be clearly scoped to a Lecture container.
5. Domain/Subdomain terminology is not yet consistent: the code mostly uses `subpart`, while the intended user-facing hierarchy is Domain -> Subdomain/Lesson.
6. Teacher lesson management currently needs complete edit, file upload, and reorder support.
7. Existing direct module files need a deliberate compatibility/migration rule.
8. Migration/test ordering has an existing issue around timestamp-less `quiz_attempt_snapshots` creation; it must be fixed or isolated before reliable `RefreshDatabase` tests can run.

## Target User Experience

### Teacher

1. Create a Lecture container with title, description, class, visibility, and ordering.
2. Open Lecture Content management.
3. Add ordered Domains.
4. Add ordered Subdomains/Lessons inside each Domain.
5. Add text, PDF, presentation, document, or supported video content to a Domain or Lesson.
6. Optionally upload/configure a Pre-Test.
7. Optionally upload/configure a Post-Test.
8. Reorder Domains and Lessons.
9. Edit or delete content with confirmation.
10. Preview the same hierarchy the student will see.

### Student

1. Select a Lecture from the class outline.
2. See the Lecture structure rather than an immediate file viewer.
3. Enter the first available stage:
   - Pre-Test if configured and not yet completed.
   - Otherwise Content.
   - Post-Test remains at the end.
4. Open Content and see ordered Domains.
5. Open a Domain:
   - If it has Lessons, show the Lesson/Subdomain list first.
   - If it has no Lessons, show its own content as a leaf.
6. Select a Lesson/Subdomain to open its actual body/file.
7. Track progress from Lesson to Domain to Lecture.
8. Move between stages without losing progress.

## Data and Compatibility Strategy

### Parent Lecture record

Keep the existing `modules` row as the Lecture parent for now. Do not introduce a second parent table unless implementation proves that the existing module relationships cannot support the flow.

The parent module should be identified as Lecture content by an explicit, stable rule. Preferred direction:

- Add or reuse a module type/flag that clearly means `lecture`.
- Do not infer Lecture status only from whether it happens to have subparts or tests.
- Keep standalone quizzes and mock-board modules outside this structure.

### Existing direct files

Before changing old records, inventory modules that have a direct `file_path` but no Domains.

Recommended compatibility behavior:

- Existing records remain readable during migration.
- New Lecture records cannot rely on direct `modules.file_path` as their main student content.
- A migration/admin action should convert an old direct file into a first Domain or Lesson, preserving title, description, file path, and file type.
- Do not delete the original file until the converted content is verified.
- After conversion, the student view uses the Domain/Lesson hierarchy.

### Pre-Test and Post-Test

- Keep `quiz_stage = pre_test` and `quiz_stage = post_test` on questions.
- Scope test creation/editing to the Lecture parent module.
- Do not create separate visible Module rows for the Pre-Test or Post-Test.
- The student UI renders them as fixed first/last stages.
- Missing tests are allowed while a teacher is building the Lecture; the Content stage must still work.

## Implementation Plan

### Phase 1: Make Lecture a first-class parent type

1. Identify the current module creation fields and type values.
2. Add a clear Lecture creation path in the teacher UI.
3. Ensure a Lecture with zero Domains is still discoverable and editable by its owner.
4. Exclude Lecture containers from the old direct-document student rendering path.
5. Keep standalone quizzes, mock boards, and unrelated legacy documents on their existing paths until explicitly migrated.
6. Ensure the Lecture list endpoint returns Lecture containers even before a Domain or test exists.

**Acceptance criteria:**

- Teacher can create an empty Lecture container.
- Empty Lecture appears in teacher Lecture Content management.
- Student sees the Lecture shell, not an immediate PDF viewer.
- Standalone quiz behavior is unchanged.

### Phase 2: Normalize the fixed stage flow

1. Keep the stage order as Pre-Test -> Content -> Post-Test.
2. Render only configured tests; never show a blank test stage.
3. Set the initial stage deterministically:
   - unfinished Pre-Test first;
   - otherwise Content;
   - otherwise Post-Test if no Content exists.
4. Keep stage navigation available according to the product decision on gating; do not accidentally introduce a new prerequisite rule.
5. Make teacher labels explicit: “Lecture Pre-Test” and “Lecture Post-Test”.
6. Ensure test attempt and scoring data remain associated with the parent Lecture module.

**Acceptance criteria:** tests never appear as separate Modules, and their visual order is stable in every Lecture.

### Phase 3: Complete Domain and Subdomain/Lesson management

1. Use one consistent user-facing label. Recommended:
   - Domain = top-level content group.
   - Lesson = content item inside a Domain.
   - “Subdomain” may be displayed as a secondary descriptor if required by the curriculum.
2. Add Domain edit support.
3. Add Lesson edit support.
4. Add file upload and replacement to Lesson forms.
5. Add optional Domain file/body support for leaf Domains.
6. Add Domain reorder support.
7. Add Lesson reorder support.
8. Add delete confirmation for both levels.
9. Validate that reorder IDs belong to the current parent.
10. Keep teacher authorization checks on every endpoint.

**Acceptance criteria:** teacher can create, edit, upload, reorder, and delete the complete hierarchy without leaving the Lecture manager.

### Phase 4: Complete the student hierarchy

1. Remove direct-file-first behavior for Lecture containers.
2. Render Domain list under Content.
3. Render Lesson list when a Domain has Lessons.
4. Render leaf Domain content only when that Domain has no Lessons.
5. Add clear back navigation:
   - Lesson -> Domain lesson list.
   - Domain -> Lecture Content list.
6. Preserve ordering from the database.
7. Preserve progress and completion badges at each level.
8. Use safe content rendering for body HTML and dynamic labels.
9. Confirm PDF/video URLs are authorized and use the expected storage/view route.
10. Make the layout usable at desktop and mobile widths.

**Acceptance criteria:** the screenshot-style direct PDF experience is replaced for Lecture containers by the structured outline.

### Phase 5: Progress and migration integrity

1. Lesson progress rolls up to its Domain.
2. Domain progress rolls up to the Lecture.
3. A Domain with Lessons does not double-count its own parent body/file.
4. A leaf Domain uses its own progress tracking.
5. Adding the first Lesson changes the Domain into a container.
6. Deleting the final Lesson returns the Domain to leaf behavior, unless the teacher explicitly converted its content.
7. Existing direct files remain recoverable during conversion.
8. Do not mark a Lecture 100% complete merely because its tests are absent; define whether completion is based on Content only or all configured stages.

### Phase 6: Fix migration ordering and tests

1. Give `create_quiz_attempt_snapshots_table.php` a timestamp earlier than migrations that alter that table, or create a safe forward migration strategy for existing databases.
2. Do not use destructive `migrate:fresh` or `migrate:refresh` against shared/production data.
3. Add feature tests for:
   - Empty Lecture discovery.
   - Lecture shell rendering.
   - Fixed stage ordering.
   - Domain CRUD authorization.
   - Lesson CRUD authorization.
   - Lesson reorder validation.
   - Student enrolled/non-enrolled access.
   - Leaf Domain fallback.
   - Nested Lesson rendering.
   - Progress roll-up.
   - Existing direct-file compatibility.
4. Run focused tests before the full suite.

## Required Decisions Before Final Coding

These are the only product decisions still needed:

1. **Pre-Test/Post-Test optionality:** teacher may upload either or both, correct?
2. **Domain naming:** use `Domain` and `Lesson`, or display `Domain` and `Subdomain`?
3. **Existing direct file conversion:** automatically convert into the first Lesson, or let the teacher choose the destination?
4. **Lecture completion:** does completion require all configured tests plus all Content, or Content completion only?
5. **Stage gating:** can students open Content before completing Pre-Test, or must Pre-Test be completed first?
6. **Supported uploads:** keep current PDF/PPT/PPTX/DOCX/MOV support, or add MP4/images?

## Recommended Order of Work

1. Confirm the six decisions above.
2. Make Lecture an explicit parent type and discoverable while empty.
3. Change teacher creation flow so new Lecture content cannot become a direct-file module.
4. Finish teacher Domain/Lesson management.
5. Finish student hierarchy and remove Lecture direct-file rendering.
6. Define and verify progress behavior.
7. Migrate or preserve existing direct-file records.
8. Fix test migration ordering.
9. Add focused feature tests.
10. Perform browser verification on desktop and mobile.

## Definition of Done

The restructure is complete when:

- No newly-created Lecture opens directly to a file.
- Every Lecture has the fixed conceptual order Pre-Test -> Content -> Post-Test.
- Pre-Test/Post-Test are optional teacher-configured stages, not separate Modules.
- Content is organized as Domain -> Lesson/Subdomain.
- Teacher can manage the hierarchy and ordering.
- Student can navigate the hierarchy and consume content.
- Progress is accurate at Lesson, Domain, and Lecture levels.
- Existing direct-file records have a documented conversion/compatibility path.
- Focused tests pass, and the migration-order issue no longer blocks `RefreshDatabase`.
