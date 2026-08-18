# Reviso Data and Process Modelling Diagrams

## Data Flow Diagram

> Figure 7: Data and Process Modelling (Context-Level)

```mermaid
flowchart LR
    subgraph Actors
        direction TB
        Student[Student]
        Teacher[Teacher]
        Admin[Admin]
    end

    subgraph IdentityProcesses
        direction TB
        P1[P1 Signup and Verification]
        P2[P2 Authentication and Approval]
    end

    subgraph LearningProcesses
        direction TB
        P3[P3 Class and Enrollment Management]
        P4[P4 Content Delivery]
        P5[P5 Assessment Attempts, Progress, and Insights]
        P6[P6 Communication and Notifications]
    end

    subgraph CoreDataStores
        direction TB
        D1[D1 Signups]
        D2[D2 Users and Sessions]
        D3[D3 Classes and Enrollments]
        D4[D4 Modules and Lectures]
        D5[D5 Quiz Attempts, Answers, Progress, and Attempt Count]
        D6[D6 Announcements and Chats]
    end

    subgraph AIDataStores
        direction TB
        D7[D7 AI Settings]
        D8[D8 Cached Student and Class AI Insights]
    end

    subgraph ExternalServices
        direction TB
        Email[Email Service]
        AI[AI Service]
    end

    Student -->|registration details| P1
    P1 -->|pending signup| D1
    P1 -->|verification email| Email
    Email -->|verification link| Student

    Student -->|login credentials| P2
    Teacher -->|login credentials| P2
    Admin -->|login credentials| P2
    Admin -->|approval decision| P2
    P2 -->|reads pending signup| D1
    P2 -->|approved account and session| D2
    P2 -->|authentication status| Student
    P2 -->|authentication status| Teacher
    P2 -->|authentication status| Admin

    Teacher -->|create class and add students| P3
    Admin -->|manage class access| P3
    Student -->|class membership use| P3
    P3 -->|class records| D3

    Teacher -->|upload modules, quizzes, lectures| P4
    Student -->|view learning materials| P4
    P4 -->|content records| D4
    P4 -->|uses class scope| D3

    Student -->|quiz answers and attempt submission| P5
    Teacher -->|review attempts| P5
    Student -->|reset pre-assessment attempt| P5
    P5 -->|attempts, answers, progress, attempt count| D5
    P5 -->|module context| D4
    P5 -->|class context| D3
    P5 -->|feature toggle lookup| D7
    P5 -->|read cached insight| D8
    P5 -->|insight request when cache is missing or reset| AI
    AI -->|generated insight| P5
    P5 -->|store generated insight| D8

    Teacher -->|post announcements and messages| P6
    Student -->|read announcements and send messages| P6
    P6 -->|announcement and chat data| D6
    P6 -->|class-scoped communication| D3
```

## Process Model

> Figure 8: Data and Process Modelling (Level-1)

```mermaid
flowchart TD
    A[Start] --> B[Student submits signup form]
    B --> C[System stores pending signup]
    C --> D[System sends verification email]
    D --> E[Student verifies email]
    E --> F[Admin reviews signup]
    F --> G{Approved?}
    G -->|Pending| H[Keep request pending]
    G -->|Rejected| I[Reject request]
    G -->|Yes| J[Create user account]
    J --> K[User logs in]
    K --> L{Role?}

    L -->|Admin| M[Manage approvals, users, and AI settings]
    L -->|Teacher| N[Create class]
    L -->|Student| O[Open assigned dashboard]

    N --> P[Add or enroll students]
    P --> Q[Post announcements and create chats]
    Q --> R[Create modules or assessments]

    O --> S[Join class and read announcements]
    S --> T[Open module content]
    T --> U{Assessment module?}
    U -->|No| V[Mark learning progress]
    U -->|Yes| W{Attempt available?}
    W -->|No| AE[Show attempt-used state]
    W -->|Yes| X[Answer assessment questions]
    X --> Y[Save answers, attempt, and attempt count]
    Y --> Z[Compute score and percentage]
    Z --> AA[Update module progress and teacher review records]
    AA --> AB{Cached insight exists?}
    AB -->|Yes| AC[Use cached student or class insight]
    AB -->|No| AD[Generate and store AI insight]

    R --> AF[Teacher reviews performance and activity]
    V --> AF
    AC --> AF
    AD --> AF
    AE --> AF
    M --> AF
    AF --> AG{Reset pre-assessment attempt?}
    AG -->|Yes| AH[Student resets own pre-assessment attempt]
    AH --> O
    AG -->|No| AI[End]
    H --> AI
    I --> AI
```

## Notes

- The data flow diagram is a logical model of how information moves through Reviso.
- The process model shows the main end-to-end LMS flow from signup to learning and monitoring.
- `lectures` are included as a content store, but the current class learning path is centered on `modules` and quizzes.