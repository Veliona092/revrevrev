# REVISO CAPSTONE: Analysis & Design Phase Presentation
## How to Explain It to Panelists (Without Scripts—Just Framework)

---

# SECTION 1: ANALYSIS AND DESIGN PHASE OVERVIEW

## What You're Saying
> "During the analysis and design phase of Reviso, both functional and non-functional requirements were carefully identified to ensure the system meets the needs of Pasig Catholic College's board-taking programs."

## How to Explain It (Not Just Read It)

### Opening: What Analysis & Design Means
**Don't assume panelists know what "analysis and design phase" is.** Clarify it:

> "Before we code a single line, we did two things: **analysis**—understanding what the system needs to do—and **design**—deciding how to build it. This phase is where we figured out the blueprint."

### Why This Phase Matters
> "Skipping this phase is how projects go sideways. You end up coding, realizing you misunderstood requirements, ripping out code. We did the thinking upfront."

### What We Identified
You identified **two categories of requirements**:

1. **Functional** — What the system must *do*
2. **Non-functional** — How well it must *do* those things

Say it simply:
> "Functional requirements answer: What features does Reviso need? Non-functional requirements answer: What quality standards must those features meet?"

---

# SECTION 2: SOFTWARE REQUIREMENTS

## The Current Problem (What You're Starting From)

Your text says:
> "The current system relies on traditional review methods such as printed materials and lecture slides, resulting in fragmented and non-centralized resources that are difficult to organize and access."

### How to Explain This
**Don't just read the paragraph.** Paint a picture of the *pain point*:

> "Right now at Pasig Catholic College, review materials are scattered everywhere. Students have lecture slides from Google Drive, PDFs emailed by teachers, printed handouts, notes from classmates. Before an exam, they're hunting through 5 different places just to find all the materials. Teachers can't see what materials exist or where they are. It's chaos."

**Panelist takes away:** You've identified a *real problem*, not a made-up one.

---

## Functional Requirements (What Reviso Must Do)

Your text lists:
1. Centralize review materials
2. Allow AI-assisted exam creation
3. Generate detailed performance analytics
4. Provide controlled access to assessments to maintain integrity

### How to Explain Each One (With Context, Not As a List)

#### Requirement 1: Centralize Review Materials
> "All lecture notes, PDFs, past exams, practice problems—everything goes in one place within Reviso. Teachers upload, students find. Students can search by topic, download for offline study, bookmark. No more hunting through drives."

**Why it matters:** Organization, discoverability, student time saved

**How you'll know it's working:** Students say "everything I need is in one app"

---

#### Requirement 2: AI-Assisted Exam Creation
> "Teachers spend 2-4 hours manually writing exam questions. Instead, a teacher uploads lecture notes into Reviso. Our system uses AI to analyze the document and generate 50 multiple-choice and short-answer questions in minutes. Teachers then review, edit, and approve before students see it.
>
> The key is: AI is a *time-saver*, not a replacement. Teachers still control quality."

**Why it matters:** Saves teacher time; exams created faster

**How you'll know it's working:** Teachers say "I spent 30 minutes on an exam instead of 3 hours"

---

#### Requirement 3: Generate Detailed Performance Analytics
> "When a student finishes an assessment, they don't just see a score. They see: which questions they got right, which they struggled on, recommended materials for weak topics. Teachers see a class-level dashboard: average performance per topic, which students are at-risk, trends across multiple exams.
>
> This closes the loop: students know what to study, teachers know where to focus."

**Why it matters:** Real-time feedback; data-driven teaching

**How you'll know it's working:** Teachers say "I can now see exactly where my class is struggling"

---

#### Requirement 4: Controlled Access to Assessments
> "During an exam, a student can't navigate away to Google, can't access lecture materials, can't see other students' work. The system locks them in. Every submission is timestamped server-side—tamper-proof. Teachers can set rules: 'This exam is available only during class hours, March 15-20.'
>
> This protects the integrity of the exam. A score means something."

**Why it matters:** Academic integrity; trustworthy assessments

**How you'll know it's working:** Teachers trust that exam scores reflect actual student knowledge

---

### Visual on Your Slide
Instead of a bullet list, use icons or a simple table:

| Requirement | What It Does | Why It Matters |
|---|---|---|
| **Centralize Materials** | One place for all resources | Students save time, don't hunt |
| **AI Exam Creation** | Generate 50 questions in minutes | Teachers save 2-4 hours |
| **Performance Analytics** | Real-time feedback + insights | Data-driven teaching & learning |
| **Controlled Access** | Lock exams, timestamp submissions | Protect integrity |

---

# SECTION 3: NON-FUNCTIONAL REQUIREMENTS

These answer: **How well must the system work?**

Your requirements are:
1. Scalable — Support multiple concurrent users
2. Secure — Protect sensitive data
3. Reliable — Prevent downtime
4. User-friendly — Easy adoption by students and faculty

### How to Explain Each One

#### Non-Functional 1: Scalable
> "On exam day, 500 students might be taking an assessment simultaneously. The system must handle that load without crashing or slowing down. We design the architecture so it can scale—add more servers as needed."

**Panelist takeaway:** You've thought about peak load, not just happy-path scenarios.

---

#### Non-Functional 2: Secure
> "Student academic records are sensitive. Passwords must be encrypted. Data in transit must be encrypted. Access must be controlled—a teacher shouldn't see another teacher's students. Every action must be logged for accountability.
>
> This isn't paranoia; it's compliance. FERPA regulations require protecting student data."

**Panelist takeaway:** You understand institutional and legal requirements.

---

#### Non-Functional 3: Reliable
> "If Reviso goes down during exam week, it's a disaster. Students miss deadlines, grades are lost, trust erodes. We design for high availability: redundant servers, automated backups, monitoring 24/7. Target uptime is 99.5%."

**Panelist takeaway:** You've considered real-world failure scenarios.

---

#### Non-Functional 4: User-Friendly
> "The system could be powerful but confusing. Teachers wouldn't use it; students would struggle. We've tested the UI with actual teachers and students. We iterate based on feedback—making sure upload is drag-and-drop, dashboards are at-a-glance, error messages are clear."

**Panelist takeaway:** You understand that technical features don't matter if the UX is bad.

---

# SECTION 4: HARDWARE SPECIFICATIONS

(Image 1 is blank in your upload, so I can't see specifics—but here's how to present whatever hardware specs you have.)

### General Framework

**What panelists want to know:**
- What servers/machines will run this?
- What's the cost estimate?
- Is it realistic for your use case?

### How to Explain It
**Don't just list specs.** Connect them to requirements:

> "We need to handle 500 concurrent users during peak exam times. For that, we're using: [your server specs]. We chose this because [reason—cost, performance, scalability]. Here's the estimated cost: [amount]. For a college budget, this is [realistic/conservative/ambitious]."

**Example (if using cloud):**
> "We're deploying on AWS/Azure with: 2 load-balanced application servers, 1 database server with replication, and monitoring. This gives us scalability and redundancy without huge upfront infrastructure costs."

**Panelist takeaway:** You've thought about deployment, not just code.

---

# SECTION 5: FUNCTIONAL DECOMPOSITION DIAGRAM (FDD)

This is the visual centerpiece of your design phase.

## What the FDD Shows

Your diagram (Image 2) shows:
- **Top level:** Reviso system
- **Second level:** 7 modules (Authentication, Student Learning, Teacher Class Management, Assessment Creation, Performance Analytics, AI Analysis, Admin)
- **Third level:** Specific functions within each module

### How to Explain It (Not Just Point and Click)

#### Opening (30 seconds)
> "This is our Functional Decomposition Diagram. It shows every functional component Reviso needs, organized hierarchically. Start at the top—Reviso—and drill down. It answers: What does each module do? What are its sub-functions? Who uses it?"

#### Walking Through the Diagram (2-3 minutes)

**Start with the foundation:**
> "At the base, we have **Authentication Module**. Every user—student, teacher, admin—logs in here first. The module breaks down into: Sign Up (new users create accounts), Log In (existing users authenticate), Forgot Password (account recovery), Temporary Password (admin-issued credentials). These are the four sub-functions of authentication."

**Move to the core workflows:**
> "**Student Learning Module** is what students interact with most. It breaks into: Dashboard (see your classes and progress), Lectures (access review materials), Assessment (take quizzes and exams), Lecture Lock (can't access certain lectures until you pass), Account Manager (settings), and Progress Tracker (see your improvement over time).
>
> Notice: Assessment flows to Lecture Lock—there's a dependency. You can't take an assessment that's locked."

> "**Teacher Class Management Module** has: Dashboard (teacher's overview), Make Class (set up a new class), Class Manager (manage students in the class), Upload Lectures (add review materials). These functions feed into the Student Learning Module—what teachers upload, students see."

> "**Assessment Creation Module** is where exams are built. It has: Test Manager (organize assessments), Teacher Create Assessment (manually write questions), AI Create Assessment (AI generates questions), Teacher Edit Assessment (approve/modify AI output). Teachers choose: build from scratch or use AI as a starting point."

**Quick mention of backend layers:**
> "**Performance Analytics Module** takes all the data students generate—quiz scores, time spent—and creates **Class Progress Tracker** and **Student Assessment Data** reports. Teachers use this to see which topics the class struggled on.
>
> **AI Analysis Module** goes deeper—it reads the raw data, identifies patterns, generates insights like 'Students consistently miss questions on Topic X' or 'This cohort is ahead of last year's cohort.'
>
> **Admin Module** sits above it all. Admins have a Dashboard, Database Access (for audits), and AI Adjustments (tune system settings)."

---

## Why the FDD Matters (Tell Panelists This)

> "This diagram ensures we haven't missed functionality. It shows how modules connect—what flows where. It's our requirements specification. If we design or code something not in this diagram, it's out of scope. If there's a function in this diagram we haven't built, we know we're not done."

**Panelist takeaway:** You have a complete, organized view of what the system must do.

---

## Connection to Functional Requirements

**Bridge from the diagram back to functional requirements:**

> "This FDD is the detailed view of our functional requirements. When I said 'centralize review materials,' it's the **Lectures function** under Student Learning. When I said 'AI-assisted exam creation,' it's the **AI Create Assessment function** under Assessment Creation. When I said 'performance analytics,' it's the Performance Analytics Module. When I said 'controlled access,' it's the **Lecture Lock** function. The diagram shows *how* we'll implement each requirement."

---

# HOW TO STRUCTURE YOUR SLIDES

## Slide 1: Analysis & Design Phase Overview
- **Title:** "Analysis & Design Phase"
- **Content:** 
  - What we did: identified functional and non-functional requirements
  - Why it matters: blueprint before code
  - Outcome: complete specification

## Slide 2: Current Situation (The Problem You're Solving)
- **Title:** "Current Challenges at Pasig Catholic College"
- **Content:** Paint the picture of fragmented materials, manual exams, no tracking
- **Visual:** Maybe show a chaotic image of scattered files/materials

## Slide 3: Functional Requirements (High-Level)
- **Title:** "What Reviso Must Do (Functional Requirements)"
- **Content:** 4 requirements in a simple table or 4-card layout
  | Icon | Requirement | What It Does |
  | ---- | --- | --- |

## Slide 4: Non-Functional Requirements (Quality Standards)
- **Title:** "How Well It Must Work (Non-Functional Requirements)"
- **Content:** 4x2 matrix
  ```
  SCALABLE          SECURE
  RELIABLE          USER-FRIENDLY
  ```
  (Fill in 1-2 key points for each)

## Slide 5: Hardware Specifications
- **Title:** "Hardware & Infrastructure"
- **Content:** Your server specs, deployment model, cost estimate
- **Visual:** Simple diagram or cost breakdown

## Slide 6: Functional Decomposition Diagram
- **Title:** "Functional Decomposition Diagram (FDD)"
- **Content:** Your Image 2 (the full hierarchy)
- **Speaker notes:** Use the walkthrough framework above

## Slide 7: Connecting It All
- **Title:** "From Requirements to Design"
- **Content:** Quick table showing:
  - Functional requirement → FDD module/function that implements it
  - Example: "Centralize materials" → "Lectures function (Student Learning Module)"

---

# DELIVERY TIPS FOR THIS SECTION

### Tip 1: Don't Just List Things
**Bad:**
> "Functional requirements are: centralize materials, AI exam creation, analytics, and controlled access."

**Good:**
> "Functional requirements answer: what does the system do? We identified four core things it must do: [explain each with context]"

---

### Tip 2: Connect to Panelist Concerns
**Bad:** Just describe the FDD structure.

**Good:**
> "This FDD ensures we have a complete specification. If a panelist asks 'How do students access lecture materials?' I can point to the diagram and show the exact function. It's not hand-wavy—it's precise."

---

### Tip 3: Use the Diagram as a Prop
- **Point to modules as you discuss them.** Don't just describe; trace the hierarchy.
- **Show data flow.** "Student takes Assessment here. Data flows to Performance Analytics here. Teacher sees insights here."
- **Highlight dependencies.** "Notice Assessment flows to Lecture Lock—these are connected."

---

### Tip 4: Know Your Hierarchy
Practice this flow until it's smooth:
1. **Reviso** (top)
2. **7 Modules** (second level)
3. **Specific functions** (third level)

When panelists ask "How does [feature] work?" you can immediately point to the right module and break it down.

---

### Tip 5: Be Ready to Defend Scope
Panelists might ask: "Why these 7 modules and not more?" Or "Isn't this over-engineered?"

**Answer template:**
> "These 7 modules cover the core needs: authentication (foundation), student learning (main user workflow), teacher management (enabler of student learning), assessment creation (AI differentiator), analytics (data-driven improvement), AI analysis (advanced insights), and admin (institutional oversight). Anything beyond these would be nice-to-have."

---

# QUICK REFERENCE: FUNCTIONAL REQUIREMENTS SUMMARY

| Requirement | In Plain English | How You'll Measure It |
|---|---|---|
| **Centralize Materials** | All review resources in one app | Students say "Everything I need is here" |
| **AI Exam Creation** | Teachers upload docs, AI generates questions | Teachers save 2+ hours per exam |
| **Performance Analytics** | Students & teachers see detailed feedback | Real-time dashboards show student weakness areas |
| **Controlled Access** | Lock exams, timestamp submissions | Teachers trust scores reflect actual knowledge |

---

# QUICK REFERENCE: NON-FUNCTIONAL REQUIREMENTS SUMMARY

| Requirement | What It Means | Panelist Concern |
|---|---|---|
| **Scalable** | 500 concurrent users without slowdown | Will it handle peak load? |
| **Secure** | Encrypted data, access control, audit logs | Is student data protected? |
| **Reliable** | 99.5% uptime, redundant servers, backups | Will it crash when we need it most? |
| **User-Friendly** | Tested with users, clear UI, easy adoption | Will teachers and students actually use it? |

---

Good luck with your presentation! 🎓
