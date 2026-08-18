# Proposed Diff: Make Figure 7 a True Context-Level DFD

This diff updates Figure 7 to a true context-level diagram by replacing multiple internal processes and data stores with one system process and only external entities.

Render note: Mermaid renderers must receive only Mermaid text (or a standalone mermaid block). For direct rendering, use `docs/reviso-context-level-figure7.mmd`.

````diff
diff --git a/docs/reviso-data-process-models copy.md b/docs/reviso-data-process-models copy.md
--- a/docs/reviso-data-process-models copy.md
+++ b/docs/reviso-data-process-models copy.md
@@
 ## Data Flow Diagram
 
 > Figure 7: Data and Process Modelling (Context-Level)
 
 ```mermaid
 flowchart LR
-    subgraph Actors
-        direction TB
-        Student[Student]
-        Teacher[Teacher]
-        Admin[Admin]
-    end
-
-    subgraph IdentityProcesses
-        direction TB
-        P1[P1 Signup and Verification]
-        P2[P2 Authentication and Approval]
-    end
-
-    subgraph LearningProcesses
-        direction TB
-        P3[P3 Class and Enrollment Management]
-        P4[P4 Content Delivery]
-        P5[P5 Assessment Attempts, Progress, and Insights]
-        P6[P6 Communication and Notifications]
-    end
-
-    subgraph CoreDataStores
-        direction TB
-        D1[D1 Signups]
-        D2[D2 Users and Sessions]
-        D3[D3 Classes and Enrollments]
-        D4[D4 Modules and Lectures]
-        D5[D5 Quiz Attempts, Answers, Progress, and Attempt Count]
-        D6[D6 Announcements and Chats]
-    end
-
-    subgraph AIDataStores
-        direction TB
-        D7[D7 AI Settings]
-        D8[D8 Cached Student and Class AI Insights]
-    end
-
-    subgraph ExternalServices
-        direction TB
-        Email[Email Service]
-        AI[AI Service]
-    end
-
-    Student -->|registration details| P1
-    P1 -->|pending signup| D1
-    P1 -->|verification email| Email
-    Email -->|verification link| Student
-
-    Student -->|login credentials| P2
-    Teacher -->|login credentials| P2
-    Admin -->|login credentials| P2
-    Admin -->|approval decision| P2
-    P2 -->|reads pending signup| D1
-    P2 -->|approved account and session| D2
-    P2 -->|authentication status| Student
-    P2 -->|authentication status| Teacher
-    P2 -->|authentication status| Admin
-
-    Teacher -->|create class and add students| P3
-    Admin -->|manage class access| P3
-    Student -->|class membership use| P3
-    P3 -->|class records| D3
-
-    Teacher -->|upload modules, quizzes, lectures| P4
-    Student -->|view learning materials| P4
-    P4 -->|content records| D4
-    P4 -->|uses class scope| D3
-
-    Student -->|quiz answers and attempt submission| P5
-    Teacher -->|review attempts| P5
-    Student -->|reset pre-assessment attempt| P5
-    P5 -->|attempts, answers, progress, attempt count| D5
-    P5 -->|module context| D4
-    P5 -->|class context| D3
-    P5 -->|feature toggle lookup| D7
-    P5 -->|read cached insight| D8
-    P5 -->|insight request when cache is missing or reset| AI
-    AI -->|generated insight| P5
-    P5 -->|store generated insight| D8
-
-    Teacher -->|post announcements and messages| P6
-    Student -->|read announcements and send messages| P6
-    P6 -->|announcement and chat data| D6
-    P6 -->|class-scoped communication| D3
+    Student[Student]
+    Teacher[Teacher]
+    Admin[Admin]
+    Email[Email Service]
+    AI[AI Service]
+
+    System[Reviso LMS]
+
+    Student -->|signup, login, learning actions, quiz answers, messages| System
+    Teacher -->|login, class/content management, reviews, announcements| System
+    Admin -->|login, approvals, user management, AI settings| System
+
+    System -->|verification email| Email
+    Email -->|verification link| Student
+
+    System -->|insight request| AI
+    AI -->|generated insight| System
+
+    System -->|authentication status, classes, content, scores, notifications| Student
+    System -->|class rosters, monitoring and insight dashboards| Teacher
+    System -->|approval queues and administration dashboards| Admin
 ```
````

Notes:
- This keeps Figure 7 correctly at context level.
- Your existing Figure 8 process flow can remain as is.
