flowchart LR
    subgraph "Users"
        Student[Student]
        Teacher[Teacher]
        Admin[Admin]
    end

    subgraph "Core Processes"
        direction TB
        Signup[Signup & Email Verification]
        Auth[Login, Authentication & Admin Approval]
        ClassMgmt[Class Creation & Enrollment]
        Content[Upload & View Lessons / Materials]
        Assessment[Quizzes, Attempts, Progress & AI Insights]
        Comm[Announcements & Class Messages]
    end

    subgraph "Data Storage"
        direction TB
        UsersDB[User Accounts & Sessions]
        ClassesDB[Classes & Enrollments]
        ContentDB[Modules, Lectures & Quizzes]
        ProgressDB[Quiz Answers, Progress & Insights]
        CommDB[Announcements & Chats]
        AISettings[AI Preferences & Cached Insights]
    end

    subgraph "External Tools"
        EmailService[Email Service]
        AIService[AI Service]
    end

    %% Connections with plain descriptions
    Student -->|Registers| Signup
    Signup -->|Sends| EmailService
    EmailService -->|Verification| Student
    Student & Teacher & Admin -->|Logs in| Auth
    Admin -->|Approves| Auth
    Auth -->|Creates account| UsersDB

    Teacher & Admin -->|Manages| ClassMgmt
    Student -->|Joins / Uses| ClassMgmt
    ClassMgmt -->|Stores| ClassesDB

    Teacher -->|Uploads| Content
    Student -->|Views| Content
    Content -->|Stores| ContentDB

    Student -->|Submits answers| Assessment
    Teacher -->|Reviews| Assessment
    Assessment -->|Stores progress| ProgressDB
    Assessment -->|Uses AI when needed| AIService
    AIService -->|Returns insights| Assessment
    Assessment -->|Caches insights| AISettings

    Teacher -->|Posts| Comm
    Student -->|Reads / Replies| Comm
    Comm -->|Stores| CommDB