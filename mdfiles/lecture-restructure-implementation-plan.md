# Lecture Restructure Implementation Plan

## Goal

Complete the Lecture experience as a NetAcad-style hierarchy while preserving the existing lecture stages:

```text
Lecture Module
├── Pre-Test
├── Content
│   ├── Domain / Sub-part 1
│   │   ├── Lesson 1.1
│   │   └── Lesson 1.2
│   └── Domain / Sub-part 2
└── Post-Test
```

A student should open a Lecture module into a structured outline, not directly into a file. A Domain/Sub-part with no lessons remains a content leaf. A Domain/Sub-part with lessons opens a lesson list, and the actual body/file is shown only after the student selects a lesson.

## Current System Check

### Already present

- `module_subparts` table and `ModuleSubpart` model.
- `subpart_progress` table and `SubpartProgress` model.
- `subpart_lessons` table and `SubpartLesson` model.
- `lesson_progress` table and `LessonProgress` model.
- `ModuleSubpartController` with teacher CRUD and student progress endpoints.
- `SubpartLessonController` with lesson CRUD and progress methods.
- Student lecture stage shell in `resources/views/pages/student/modules.blade.php`:
  - Pre-Test tab.
  - Content tab.
  - Post-Test tab.
  - Domain/Sub-part list.
  - Leaf content viewer and sub-part progress tracking.
- Teacher-side lecture pre-test/post-test listing in `resources/views/pages/teacher/manageclass.blade.php`.
- Lecture module discovery endpoint in `ClassManagerController::listLectureModulesJson()`.

### Confirmed gaps and risks

1. **Lesson routes are not registered.**
   `routes/web.php` contains the module sub-part routes but does not contain the lesson routes from the snippet file. `php artisan route:list --path=subparts` showed only seven sub-part routes and no lesson endpoints.

2. **`SubpartLessonController` is in the wrong filesystem location.**
   The class namespace is `App\\Http\\Controllers`, but the file currently sits at `app/Models/SubpartLessonController.php`. It should be moved to `app/Http/Controllers/SubpartLessonController.php` before routing it, otherwise Composer PSR-4 autoloading can fail.

3. **The lesson route snippet is only a standalone file.**
   `app/Models/subpart_lessons_routes_snippet.php` contains route declarations but is not imported by `routes/web.php`. It should be treated as reference material, not as an active route file.

4. **Student rendering stops at the Domain/Sub-part level.**
   The current `renderSubpartViewer()` immediately renders a sub-part body/file. It does not check whether the sub-part has lessons, does not fetch lesson data, and does not render a lesson list or lesson viewer.

5. **Student module JSON does not include nested lessons.**
   `studentModules()` loads modules but does not eager-load sub-parts, lessons, or their per-student progress. The current JavaScript therefore has no lesson data to render.

6. **Teacher UI has sub-part management but no visible lesson management flow.**
   The teacher-side module dialog references lecture content and tests, but there is no verified lesson list/create/edit/delete interface wired to the lesson controller.

7. **Progress roll-up needs one defined rule.**
   Existing code rolls lesson progress into sub-part progress and then module progress. The implementation must explicitly preserve this behavior and avoid double-counting a sub-part's own leaf progress when it has lessons.

8. **Existing data compatibility needs protection.**
   Old sub-parts without lessons must continue to open their existing body/file directly. New nested lessons must not break old lecture modules.

## Target Behavior

### Student flow

1. Student opens a class Modules page.
2. Student selects a Lecture module.
3. The existing Pre-Test, Content, and Post-Test tabs remain available.
4. In Content:
   - Show ordered Domains/Sub-parts.
   - Show progress and completion state for each Domain.
   - If a Domain has no lessons, show its body/file directly.
   - If a Domain has lessons, show an ordered lesson list instead of opening the Domain file immediately.
   - Selecting a Lesson opens its body/file and starts lesson progress tracking.
5. Student can return from a Lesson to its Domain lesson list and from the Domain to the Lecture content list without losing state.
6. Progress updates are forward-only and roll up as:

```text
Lesson progress -> Domain/Sub-part progress -> Module progress
```

### Teacher flow

1. Teacher creates or edits a Lecture module.
2. Teacher manages ordered Domains/Sub-parts.
3. Teacher can open a Domain and manage its Lessons:
   - Create.
   - Edit.
   - Replace or remove attached file as supported by current conventions.
   - Reorder.
   - Delete.
4. Teacher configures Pre-Test and Post-Test separately from Content Domains.
5. The teacher interface clearly keeps this structure:

```text
Pre-Test -> Domains/Lessons -> Post-Test
```

### Authorization and validation

- Students may view only modules belonging to classes in which they are enrolled.
- Teachers/admins may manage only modules they own or are authorized to manage.
- Lesson and sub-part route model binding must not allow cross-module access.
- Lesson uploads must use the existing public storage convention and allowed file types.
- Lesson ordering must accept only IDs belonging to the current sub-part.
- Progress must remain between 0 and 100 and must not move backward.

## Implementation Phases

### Phase 0: Repository and route cleanup

1. Move `SubpartLessonController.php` from `app/Models/` to `app/Http/Controllers/`.
2. Add the controller import to `routes/web.php`.
3. Register the seven lesson routes from the snippet using the existing route naming style.
4. Keep the route declarations inside the authenticated route area used by the existing module routes.
5. Remove or archive the route snippet only if it is confirmed to be unused; do not leave two competing route sources.
6. Run `php artisan route:list --path=lessons` and confirm all lesson routes exist.

**Acceptance check:** all lesson routes resolve to `App\\Http\\Controllers\\SubpartLessonController` and route model binding loads the correct `SubpartLesson`.

### Phase 1: Nested lesson data contract

1. Add `lessons` relationships to the module/sub-part loading path used by `studentModules()`.
2. Build a student-safe JSON shape for:
   - Domain/Sub-part metadata.
   - Domain progress.
   - Whether it has lessons.
   - Ordered lesson metadata.
   - Per-student lesson progress.
3. Avoid exposing management-only fields or unnecessary user data.
4. Preserve the current quiz attempt key format for lecture Pre-Test/Post-Test.
5. Keep old leaf sub-parts functional when `lessons` is empty.
6. Decide whether to load nested data in the initial Blade payload or request it through the student endpoints. Prefer one consistent approach and avoid an N+1 query pattern.

**Acceptance check:** a module payload contains ordered domains, each domain can indicate whether it has lessons, and each lesson has the current student's progress.

### Phase 2: Student Domain -> Lesson UI

1. Extend the existing lecture Content renderer instead of creating a second module page.
2. When a Domain has lessons:
   - Render an ordered lesson list.
   - Display lesson completion/progress indicators.
   - Do not render the Domain's body/file as the first action.
3. When a Domain has no lessons:
   - Keep the existing direct body/file viewer.
4. Add a clear back action from Lesson viewer to the lesson list.
5. Add lesson progress tracking for text, PDF, and video using the current sub-part tracking patterns.
6. Update sidebar/list progress immediately after a lesson progress save.
7. Escape or safely render dynamic titles/descriptions/body content according to the current content policy; do not introduce unsafe HTML interpolation accidentally.
8. Make the nested layout responsive for the existing desktop and mobile module views.

**Acceptance check:**

- Domain with no lessons opens its existing content.
- Domain with lessons opens a lesson list.
- Selecting a lesson opens its content.
- Completion and progress update without a page refresh.

### Phase 3: Teacher Lesson Management UI

1. Add a lesson-management section to the existing Lecture Content management flow.
2. Reuse the current sub-part management patterns and button/modal styles.
3. Add create/edit forms for lesson title, description, body, file, and order where appropriate.
4. Add reorder interaction and wire it to the exact-match reorder validation.
5. Add delete confirmation and ensure stored files are deleted through the controller.
6. Refresh only the affected Domain/Lesson section after mutations.
7. Keep Pre-Test/Post-Test editing separate from Domain/Lesson content management.

**Acceptance check:** a teacher can create a Domain, create two Lessons under it, reorder them, edit one, delete one, and see the same order on the student side.

### Phase 4: Progress integrity and compatibility

1. Confirm the lesson progress roll-up uses the average of all lessons in a Domain.
2. Confirm module progress uses the average of all Domains.
3. Confirm a Domain with lessons does not also count its parent body/file as a second content item.
4. Decide behavior when a teacher adds the first lesson to a previously leaf Domain:
   - Existing parent body/file remains stored but is no longer shown as the primary student content, or
   - Existing content is migrated into the first lesson.
5. Decide behavior when the final lesson is deleted: the Domain returns to leaf behavior.
6. Add unique/index constraints only if required by observed query patterns; existing migrations already include relevant foreign keys and ordering indexes.

**Acceptance check:** progress values stay consistent after adding, deleting, reordering, and completing lessons.

### Phase 5: Tests and runtime verification

Add focused PHPUnit feature tests for:

- Authorized teacher can create/update/delete/reorder lessons.
- Unauthorized teacher cannot manage another teacher's lessons.
- Enrolled student can list lessons and progress.
- Non-enrolled student receives 403.
- Student progress is forward-only.
- Lesson progress rolls up to sub-part and module progress.
- Empty lesson collection preserves old leaf behavior.
- Ordered lesson output is stable.
- Invalid reorder IDs are rejected.
- Invalid file types and invalid progress values are rejected.

Run the narrow tests first, then the related existing module/progress tests. Finish with:

```powershell
vendor/bin/pint --dirty --format agent
php artisan route:list --path=lessons
php artisan test --compact
```

For browser verification, check at least:

- Desktop lecture with two Domains and nested Lessons.
- Mobile/narrow viewport with the same hierarchy.
- A legacy Domain with no Lessons.
- A lesson with body content only.
- A lesson with PDF/video attachment.
- Progress after leaving and reopening the page.

## Decisions Needed Before Coding

These decisions affect data behavior and should be confirmed before Phase 2 or Phase 4:

1. **Parent content when Lessons exist:** should the existing Domain body/file be hidden, migrated into a Lesson, or remain available as an additional item?
2. **Naming:** should the UI use `Domain`, `Sub-part`, or one consistent label? The current code uses `subpart` while the desired hierarchy describes Domains.
3. **Lesson numbering:** should numbering be generated from order (`1.1`, `1.2`) or should teachers enter a custom number/title?
4. **Pre-Test/Post-Test placement:** should every Lecture require both tests, or may a Lecture temporarily have only one while being built?
5. **Content types:** should lesson uploads support exactly the current `pdf,ppt,pptx,docx,mov` set, or should common formats such as images and MP4 be added?
6. **Existing data migration:** should existing standalone assessment/lecture records be converted, or should the new hierarchy apply only to newly managed Lecture modules?

## Recommended Execution Order

1. Confirm the six decisions above.
2. Fix controller placement and register lesson routes.
3. Add the nested student data contract.
4. Implement student Domain -> Lesson navigation.
5. Add teacher lesson management UI.
6. Verify progress roll-up and legacy compatibility.
7. Add and run focused tests.
8. Perform desktop/mobile browser verification.

## Out of Scope for This Plan

- Replacing the existing quiz engine.
- Changing the Pre-Test/Post-Test scoring or attempt-limit rules.
- Rebuilding the entire student Modules page layout.
- Migrating unrelated standalone quizzes or mock-board modules.
- Removing legacy `Lecture` file-upload pages unless a separate decision is made.
