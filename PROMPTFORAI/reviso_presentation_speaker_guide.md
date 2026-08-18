# REVISO CAPSTONE: Analysis & Design Phase — Speaker Guide

> **For:** Pasig Catholic College Board Examination Review System  
> **Phase:** Analysis & Design  
> **Based on:** Actual presentation slides and FDD diagram

---

## SLIDE 1: Analysis & Design Phase Overview

### What to Say (30 seconds)

> "During the analysis and design phase of Reviso, both functional and non-functional requirements were carefully identified to ensure the system meets the needs of Pasig Catholic College's board-taking programs."

### How to Explain It

**Open with the blueprint metaphor:**
> "Before we code a single line, we did two things: **analysis**—understanding what the system needs to do—and **design**—deciding how to build it. This phase is where we figured out the blueprint."

**Why this phase matters:**
> "Skipping this phase is how projects go sideways. You end up coding, realizing you misunderstood requirements, ripping out code. We did the thinking upfront."

**The two categories you identified:**
1. **Functional** — What the system must *do*
2. **Non-functional** — How well it must *do* those things

> "Functional requirements answer: What features does Reviso need? Non-functional requirements answer: What quality standards must those features meet?"

---
 
## SLIDE 2: Software Requirements — The Current Problem

### What to Say (45 seconds)

**Paint the picture of chaos:**
> "Right now at Pasig Catholic College, review materials are scattered everywhere. Students have lecture slides from Google Drive, PDFs emailed by teachers, printed handouts, notes from classmates. Before an exam, they're hunting through 5 different places just to find all the materials. Teachers manually create exams—making the process time-consuming. Feedback is limited to raw scores with minimal analysis. There's no real-time tracking of student progress, making it challenging for both students and teachers to identify strengths and weaknesses. It's chaos."

**Panelist takeaway:** You've identified a *real problem*, not a made-up one.

---

## SLIDE 3: Functional Requirements (What Reviso Must Do)

### Your 4 Requirements:

| # | Requirement | In Your Own Words | Success Metric |
|---|-------------|-------------------|----------------|
| 1 | **Centralize review materials** | "All lecture notes, PDFs, past exams, practice problems—everything goes in one place. Teachers upload, students find. No more hunting through drives." | Students say "Everything I need is in one app" |
| 2 | **AI-assisted exam creation** | "Teachers spend hours manually writing questions. With Reviso, AI analyzes lecture documents and generates questions in minutes. Teachers review and approve—AI is a time-saver, not a replacement." | Teachers save 2+ hours per exam |
| 3 | **Detailed performance analytics** | "Students don't just see 75%. They see which topics they aced and which they need to review. Teachers see class-level dashboards showing exactly where the whole class is struggling." | Real-time dashboards show weakness areas |
| 4 | **Controlled access to assessments** | "During exams, students can't navigate away, can't access lecture materials. Every submission is timestamped server-side. Teachers set availability windows—'This exam opens March 15 at 9am.'" | Teachers trust scores reflect actual knowledge |

### Visual Table for Your Slide

| Requirement | What It Does | Why It Matters |
|-------------|--------------|----------------|
| **Centralize Materials** | One place for all resources | Students save time, don't hunt |
| **AI Exam Creation** | Generate questions in minutes | Teachers save 2+ hours |
| **Performance Analytics** | Real-time feedback + insights | Data-driven teaching & learning |
| **Controlled Access** | Lock exams, timestamp submissions | Protect academic integrity |

---

## SLIDE 4: Non-Functional Requirements (Quality Standards)

### Your 4 Quality Requirements:

| Requirement | What It Means for PCC | Panelist Concern |
|-------------|----------------------|------------------|
| **Scalable** | Support multiple concurrent users without slowdown | Will it handle peak load? |
| **Secure** | Protect sensitive student data, encrypted transmissions | Is student data protected? |
| **Reliable** | Prevent downtime during critical exam periods | Will it crash when we need it? |
| **User-friendly** | Easy adoption by students and faculty without extensive training | Will people actually use it? |

### How to Explain Each

**Scalable:**
> "On exam day, 200+ students might be taking assessments simultaneously. The system must handle that load without crashing. We designed the architecture to scale—add resources as needed."

**Secure:**
> "Student academic records are sensitive. Passwords encrypted. Data in transit encrypted. Access controlled—teachers only see their own students. Every action logged. This is compliance—FERPA requires protecting student data."

**Reliable:**
> "If Reviso goes down during exam week, it's a disaster. Missed deadlines, lost trust. We design for high availability: automated backups, error handling. We need this to work when it matters most."

**User-Friendly:**
> "A powerful system that confuses teachers won't get used. We've focused on clean dashboards, drag-and-drop uploads, clear feedback. Tested with actual users. Features don't matter if the UX is bad."

---

## SLIDE 5: Hardware Specifications

### Actual Specs from Your Slide:

| Item | Quantity | Specification |
|------|----------|---------------|
| Operating System | — | Windows 10/11 (64-bit) |
| RAM | 1 unit | 8GB RAM (minimum) |
| Storage | 1 HDD or SSD | 256GB SSD or higher |
| GPU | 1 onboard | Integrated Graphics |
| Processor | 1 | Intel i5 / AMD Ryzen 5 or equivalent |
| Internet Connection | 1 | Stable Internet (at least 25 Mbps) |

### How to Present This

**Connect to real-world context:**
> "These specs represent standard PC configurations available at Pasig Catholic College. We're not requiring specialized hardware—just what they already have. The system runs on Windows 10/11 with 8GB RAM minimum, 256GB SSD for responsiveness, and standard integrated graphics. The key requirement is stable internet—at least 25 Mbps—for cloud-based AI features and real-time syncing."

**Panelist takeaway:** You've chosen realistic, accessible hardware.

---

## SLIDE 6: Functional Decomposition Diagram (FDD)

### This is Your Visual Centerpiece

**Opening (30 seconds):**
> "This is our Functional Decomposition Diagram. It shows every component Reviso needs, organized hierarchically. Start at the top—Reviso—and drill down. It answers: What does each module do? What are its sub-functions? Who uses it?"

### Walking Through the 7 Modules (2-3 minutes)

#### 1. Authentication Module (Foundation)
> "Every user—student, teacher, admin—logs in here first. Four sub-functions:
> - **Sign Up** — New users create accounts
> - **Log In** — Existing users authenticate  
> - **Forget Password** — Account recovery
> - **Temporary Password** — *Password reset flow: temporary credentials issued, user must change on first login*"

#### 2. Student Learning Module (Core User Experience)
> "This is what students interact with most. Six sub-functions:
> - **Dashboard** — Overview of classes and progress
> - **Lectures** — Access review materials (implements 'centralize materials')
> - **Assessment** — Take quizzes and exams
> - **Lecture Lock** — *Exam anti-cheat system: students can't navigate away during assessments (tab change = auto-submit)*
> - **Progress Tracker** — Visual improvement over time
> - **Account Manager** — Settings and profile"
>
> *Point out the flow:* "Notice Assessment → Lecture Lock → Progress Tracker. Assessment security ensures scores reflect actual knowledge, then progress tracks improvement."

#### 3. Teacher Class Management Module (Enabler)
> "Teachers control the learning environment:
> - **Dashboard** — Teacher's overview
> - **Make Class** — Set up new review classes
> - **Class Manager** — Add/remove students
> - **Upload Lectures** — Add materials students see
>
> *Key point:* "What teachers upload here, students see in their Lectures function. These modules feed into each other."

#### 4. Assessment Creation Module (The AI Differentiator)
> "This is where exams are built—teacher-facing assessment management:
> - **Test Manager** — *The entire teacher quiz/assessment interface: create, schedule, manage*
> - **Teacher Create Assessment** — Manual question writing
> - **AI Create Assessment** — AI generates questions from uploaded documents
> - **Teacher Edit Assessment** — Review and approve AI output
>
> *Key point:* "Teachers choose: build from scratch or use AI as a starting point. The AI drafts, but teachers control quality. Test Manager is the comprehensive assessment dashboard."

#### 5. Performance Analytics Module (Data Layer)
> "Takes raw quiz scores and creates meaningful reports:
> - **Class Progress Tracker** — See how the whole class is doing
> - **Student Assessment Data** — Individual student performance
> - **Class Assessment Data** — Aggregated by exam"

#### 6. AI Analysis Module (Intelligence Layer)
> "Goes deeper than raw scores—comparing individual vs. class performance:
> - **AI Read data and Metadata** — *Student analysis insights: per-student vs. class-level performance comparison*
> - **Create Assessment** — Generates questions
> - **Student Assessment Analysis** — Individual insights
> - **Class Assessment Data** — Cohort-level patterns
>
> *Example:* "This generates insights like 'Student scored 75% vs. class average of 68% on Cardiology'—personalized vs. baseline comparison."

#### 7. Admin Module (Oversight)
> "Sits above everything:
> - **Dashboard** — System-wide overview
> - **Database Access** — For audits and troubleshooting
> - **AI Adjustments** — Tune system settings"

### Why the FDD Matters

> "This diagram ensures we haven't missed functionality. It shows data flow—Student takes Assessment here, data flows to Performance Analytics here, Teacher sees insights here. It's our requirements specification. If it's not in this diagram, it's out of scope. If it's in the diagram but not built, we know we're not done."

---

## SLIDE 7: Connecting Requirements to Design

### Mapping Table (Show this explicitly)

| Functional Requirement | FDD Module / Function |
|------------------------|----------------------|
| Centralize materials | Student Learning → Lectures |
| AI exam creation | Assessment Creation → AI Create Assessment |
| Performance analytics | Performance Analytics + AI Analysis Modules |
| Controlled access | Student Learning → Lecture Lock |

### Bridge Statement

> "The FDD isn't just boxes—it's the detailed view of our functional requirements. When I said 'AI-assisted exam creation,' I pointed to the **AI Create Assessment** function. When I said 'controlled access,' I showed **Lecture Lock**. The diagram proves we know *how* we'll deliver each requirement."

---

## DELIVERY TIPS

### 5 Rules for Presenting

| # | Rule | Example |
|---|------|---------|
| 1 | **Don't just list** | BAD: "Requirements are: centralize, AI, analytics, access." GOOD: "Functional requirements answer: what does the system do? We identified four core needs..." |
| 2 | **Connect to concerns** | BAD: "The FDD has 7 modules." GOOD: "If a panelist asks 'How do students access materials?' I can point to the exact function. It's precise, not hand-wavy." |
| 3 | **Use the diagram as a prop** | Point to modules, trace hierarchy, show data flow: "Assessment → Analytics → Teacher sees insights." |
| 4 | **Know your hierarchy** | Practice: Reviso (top) → 7 Modules (level 2) → Functions (level 3) |
| 5 | **Defend scope confidently** | If asked "Why 7 modules?": "Authentication (foundation), Student Learning (workflow), Teacher Management (enabler), Assessment Creation (AI differentiator), Analytics (data), AI Analysis (insights), Admin (oversight). Core needs covered." |

### Common Questions — Answer Templates

| Question | Your Answer |
|----------|-------------|
| "How do you know the AI won't give wrong answers?" | "Teachers review and approve all AI output before deployment. AI drafts; humans validate." |
| "Did you talk to actual teachers?" | "Yes—we identified the 4-hour manual exam creation pain point from direct interviews." |
| "Can this scale to the whole college?" | "Architecture supports it. We're demonstrating at pilot scale. Full deployment needs load testing we identified as future work." |
| "Who maintains this after you graduate?" | "Laravel and PHP—skills PCC's IT vendor has. Plus deployment docs and admin training materials." |
| "Isn't 7 modules over-engineered?" | "Each maps to a core need. Authentication is foundation. Student Learning is the main workflow. Nothing here is nice-to-have." |

### Timing Guide (15-minute presentation)

| Section | Time | Key Point |
|---------|------|-----------|
| Slides 1-2: Overview + Problem | 3 min | "Chaos of scattered materials" |
| Slide 3: Functional Requirements | 4 min | 4 hours → 30 minutes story |
| Slide 4: Non-Functional | 2 min | Quality table, pilot honesty |
| Slide 5: Hardware | 1 min | Realistic PC specs |
| Slide 6: FDD Walkthrough | 3 min | Trace hierarchy, show dependencies |
| Slide 7: Connection | 1 min | Requirements → Modules table |
| Buffer | 1 min | Q&A preparation |

---

## QUICK REFERENCE CHEAT SHEET

### If You Forget What to Say

**Remember these anchors:**
1. **The problem:** "Materials scattered across 5 places, manual exams, no tracking"
2. **The 4 requirements:** Centralize → AI Create → Analytics → Controlled Access
3. **The 7 modules:** Authentication, Student Learning, Teacher Management, Assessment Creation, Performance Analytics, AI Analysis, Admin
4. **The FDD flow:** Student Assessment → Analytics → Teacher Insights
5. **The hardware:** Standard Windows PC, 8GB RAM, 256GB SSD, 25 Mbps internet

### Confidence Mantras

- "I've mapped every requirement to a specific module function."
- "The FDD proves we know HOW, not just WHAT."
- "These specs are realistic for PCC's existing infrastructure."
- "Panelists want to see that I THOUGHT—not that I'm perfect."

---

## FINAL CHECKLIST

Before walking in:

- [ ] I can explain the 4 functional requirements in 30 seconds
- [ ] I can walk through the 7 FDD modules top-down
- [ ] I know the hardware specs by heart
- [ ] I've practiced pointing to the diagram while speaking
- [ ] I have an honest answer to "What would you do differently?"

---

*Speaker guide based on actual Reviso Analysis & Design presentation slides.*  
*Pasig Catholic College — Board Examination Review System*
