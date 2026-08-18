# Reviso Data and Process Modelling Diagrams

## Data Flow Diagram

> Figure 7: Data and Process Modelling (Context-Level)

```mermaid
flowchart LR
    subgraph Actors
        direction LR
        Admin[Admin]
        Teacher[Teacher]
        Student[Student]
    end
    Email[Email Service]
    AI[AI Service]

    P1[P1 Signup and Verify]
    P2[P2 Login and Approval]
    P3[P3 Classes and Enrollment]
    P4[P4 Learning Content]
    P5[P5 Quiz and Progress]
    P6[P6 Announcements and Messages]

    D1[D1 Signups]
    D2[D2 Users and Sessions]
    D3[D3 Classes and Enrollments]
    D4[D4 Modules and Lectures]
    D5[D5 Quiz Attempts and Answers]
    D6[D6 Announcement and Chat Data]
    D7[D7 AI Settings]
    D8[D8 Cached AI Insights]

    Student -->|signup| P1
    P1 -->|save pending| D1
    P1 -->|send verification| Email
    Email -->|verify account| Student

    Student -->|login| P2
    Teacher -->|login| P2
    Admin -->|login and approve| P2
    P2 -->|check pending| D1
    P2 -->|save session| D2
    P2 -->|access result| Student
    P2 -->|access result| Teacher
    P2 -->|access result| Admin

    Teacher -->|create class| P3
    Admin -->|manage class access| P3
    Student -->|join class| P3
    P3 -->|save class data| D3

    Teacher -->|upload content| P4
    Student -->|view content| P4
    P4 -->|save content| D4
    P4 -->|use class info| D3

    Student -->|submit quiz| P5
    Student -->|reset pre-assessment| P5
    Teacher -->|review results| P5
    P5 -->|save attempts and progress| D5
    P5 -->|use modules| D4
    P5 -->|use class info| D3
    P5 -->|read settings| D7
    P5 -->|read cache| D8
    P5 -->|request insight if needed| AI
    AI -->|return insight| P5
    P5 -->|save insight| D8

    Teacher -->|post updates| P6
    Student -->|read and message| P6
    P6 -->|save announcements and chats| D6
    P6 -->|use class scope| D3
```

## Process Model

> Figure 8: Data and Process Modelling (Level-1)

```mermaid
flowchart TD
    A[Start] --> B[Student submits signup form]
    B --> C[System saves signup as pending]
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
    L -->|Student| O[Open student dashboard]

    N --> P[Add or enroll students]
    P --> Q[Post announcements and create chats]
    Q --> R[Create modules or assessments]

    O --> S[Join class and read announcements]
    S --> T[Open module content]
    T --> U{Assessment module?}
    U -->|No| V[Mark learning progress]
    U -->|Yes| W{Attempt available?}
    W -->|No| AE[Show attempt already used]
    W -->|Yes| X[Answer assessment questions]
    X --> Y[Save answers, attempt, and attempt count]
    Y --> Z[Compute score and percentage]
    Z --> AA[Update module progress and review records]
    AA --> AB{Cached insight exists?}
    AB -->|Yes| AC[Use cached student/class insight]
    AB -->|No| AD[Generate and save AI insight]

    R --> AF[Review class performance and activity]
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