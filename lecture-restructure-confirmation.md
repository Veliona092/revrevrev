# Pag-unawa sa Gusto Mong Mangyari — Para sa Kumpirmasyon

> Layunin nito: i-summarize kung ano ang naiintindihan ko sa dalawang magkaugnay na hiling mo,
> bago ako gumalaw pa ng code. Sa dulo, may mga tanong ako sa mga part na hindi pa 100% malinaw
> sa akin.

---

## 1. Dalawang magkaibang bagay pero magkadugtong

Base sa mga na-upload mong report (`lecture-restructure-report.md`, `LESSON_LEVEL_REPORT.md`) at
sa mga sinabi mo, mukhang dalawang magkahiwalay na "layer" ito pero kailangan magkasundo:

### A. Structural rule sa loob ng ISANG Module (Lecture)
> "gusto ko may pretest lagi sa unahan at post test lagi sa dulo... pag may nagawa yung teacher
> outside siya sa domains"

Ibig sabihin, kapag gumawa ang teacher ng Lecture module, laging ganito ang pagkaka-ayos, hindi
puwedeng ibahin:

```
[ Pre-Test ]  →  [ Content = mga Domains/Sub-parts ]  →  [ Post-Test ]
```

- Ang **Pre-Test** at **Post-Test** ay hindi "domain" — hiwalay sila, laging nasa unahan at
  hulihan, kahit ilan pa ang domains sa gitna.
- Ang mga **Domains** (tawag mo, pareho ng "Sub-part" sa lumang report) ay laman lang ng
  gitnang Content stage — dito lang pumapasok ang mga 1.1, 1.2, 1.3 atbp.
- Wala nang hiwalay na standalone "Assessment module" sa labas — isang Module lang, may
  quiz_stage (`pre_test` / `null` / `post_test`) para malaman kung saang bahagi napupunta ang
  bawat tanong.
- Ito yung dahilan kung bakit sinabi kong "kulang pa" ang teacher-side na Modules dialog sa
  `classes.blade.php` — meron pa doon hiwalay na tabs na "Pre-Assessment" at "Assessment" na
  gumagawa ng sarili nilang Module row, imbes na maging bahagi lang ng iisang Lecture flow.

### B. NetAcad-style na paglalahad sa STUDENT side (expand/collapse, hindi direktang file)
> "babaguhin ko din format nung modules side sa student wala ng pipindutin mo file agad, dapat
> para siyang netacad na pag pinindot mo andaming subdomain module 1>1.1>1.2"

Ito yung tinutukoy ng `LESSON_LEVEL_REPORT.md`:

```
Module 1
 ├─ 1.1 (Domain/Sub-part) ─── kung walang Lessons: direktang lumalabas ang content nito (leaf)
 └─ 1.2 (Domain/Sub-part) ─── kung may Lessons: hindi na file agad ang lumalabas, kundi listahan
      ├─ 1.2.1 Lesson              ng mga Lesson (1.2.1, 1.2.2, ...) na pag pinindot saka
      ├─ 1.2.2 Lesson              lalabas yung talagang content (video/PDF/text)
      └─ ...
```

- Isang click sa Module = hindi agad file, kundi listahan ng Domains.
- Isang click sa isang Domain na may Lessons = hindi agad file, kundi listahan ng Lessons.
- Isang click sa Lesson (o sa Domain na walang Lessons) = dito palang lalabas ang totoong content.

---

## 2. Ano na ang tapos, ano ang kulang (base sa mga file mong na-upload)

| Piraso | Status |
|---|---|
| `module_subparts` table + `ModuleSubpart` model | ✅ Gawa na |
| `subpart_progress` table + `SubpartProgress` model | ✅ Gawa na |
| `quiz_stage` column sa `quiz_questions`, `quiz_attempts`(?), `quiz_attempt_snapshots` | ✅ Migrations gawa na |
| `ModuleSubpartController` (teacher CRUD + student progress) | ✅ Gawa na |
| `subpart_lessons` table + `SubpartLesson` model | ✅ Nagawa sa nakaraang session — **kailangan kong i-verify kung na-paste mo na talaga sa repo** |
| `lesson_progress` table + `LessonProgress` model | ✅ Nagawa rin — same, need i-verify kung naka-apply na |
| `SubpartLessonController` | ✅ Nagawa rin — same |
| Routes para sa lessons | ✅ Snippet gawa na — need i-verify kung na-paste mo sa `web.php` |
| **Student-side `modules.blade.php`**: expand/collapse ng Module→Domain→Lesson, `renderLessonViewer()`, x/y badge | ❌ **Wala pa** — ito yung binanggit kong pinaka-malaking piraso ng frontend work |
| **Teacher-side `classes.blade.php`**: tanggalin ang hiwalay na "Pre-Assessment"/"Assessment" tabs, gawing isang Lecture flow (Pre-Test → Domains → Post-Test) | ❌ **Wala pa** — kailangang i-redesign yung Modules dialog |
| Pag-migrate ng lumang standalone Assessment modules papunta sa bagong istruktura | ❌ Open question pa rin (hindi pa nadedecide) |

---

## 3. Mga tanong bago ako sumulat ng code

1. **Sequencing** — alin muna talaga ang gusto mong tapusin? Base sa huling tanong ko sa nakaraang
   session, ito pa rin ang tatlong opsyon:
   - (1) Teacher-side: i-redesign ang Modules dialog para maging Pre-Test → Domains → Post-Test,
     tanggalin ang lumang Pre-Assessment/Assessment tabs
   - (2) Student-side: tapusin ang expand/collapse na Lesson viewer
   - (3) Pareho, sunod-sunod — gusto mo munang makita yung buong plano

2. **Yung mga file na ginawa noong nakaraang session** (`SubpartLesson`, `LessonProgress`,
   `SubpartLessonController`, mga migration, routes snippet) — na-paste mo na ba talaga sa
   project mo, o kailangan ko munang i-verify/hanapin sa codebase kung nandiyan na?

3. **Yung "wala ng pipindutin mo file agad" sa Module level** — ibig mo bang sabihin, kahit
   yung Module mismo (hindi lang Domain) ay hindi na dapat magbukas agad ng file kapag walang
   pre-test/post-test? Kasi kung tama pagkaka-intindi ko, click Module → laging listahan muna
   (ng Domains, kung meron man siyang Pre-Test/Post-Test tabs din), hindi kailanman direktang
   file.

4. **Existing standalone Assessment modules** (yung mga ginawa na ng teacher gamit yung lumang
   Pre-Assessment/Assessment tabs bago pa ito) — gusto mo bang ilipat/i-convert sila papunta sa
   bagong istruktura, o pababayaan muna sila habang tinatapos natin ang bagong flow para sa mga
   bagong gagawing Lecture?

Sagutin mo na lang yung mga tanong sa itaas (o pindutin sa button kung lalabas), tapos
magpapatuloy na ako sa actual na pag-code base dun.
