@extends('layouts.appStudent') {{-- Replace with your student layout name --}}

@section('title', $mockBoard->title)

@section('content')
<div class="exam-container">
    {{-- Top Navigation Bar --}}
    <div class="exam-header-fixed">
        <div class="header-content">
            <div class="exam-info">
                <h1>{{ $mockBoard->title }}</h1>
                <span class="phase-badge">{{ strtoupper(str_replace('_', ' ', $phase)) }}</span>
            </div>
            <div class="timer-box">
                <i class="fas fa-clock"></i>
                <span id="timer">00:00</span>
            </div>
        </div>
        <div class="progress-wrapper">
            <div id="progress-bar" class="progress-fill"></div>
        </div>
    </div>

    {{-- Question Area --}}
    <main class="question-main">
        <div class="question-card">
            <div class="q-meta">
                <span class="q-count">Question <span id="current-q-index">1</span> of {{ $questions->count() }}</span>
            </div>
            
            <div id="q-content">
                <p id="q-text" class="question-text"></p>
                
                <div id="options-container" class="options-list">
                    {{-- Options injected by JS --}}
                </div>
            </div>
        </div>
    </main>

    {{-- Bottom Navigation --}}
    <footer class="exam-footer">
        <div class="footer-content">
            <button id="prev-btn" class="rv-btn rv-btn-outline" disabled>
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            
            <div class="q-navigation-dots">
                {{-- Optional: could add small dots for progress here --}}
            </div>

            <button id="next-btn" class="rv-btn rv-btn-primary">
                Next <i class="fas fa-chevron-right"></i>
            </button>
            
            <button id="submit-btn" class="rv-btn rv-btn-success hidden" onclick="confirmSubmit()">
                Submit Final Answers <i class="fas fa-check-double"></i>
            </button>
        </div>
    </footer>
</div>

@endsection

@section('styles')
<style>
    /* Full Page Layout */
    .exam-container {
        background-color: #f8f9fa;
        min-height: 100vh;
        padding-top: 100px; /* Space for fixed header */
        padding-bottom: 80px; /* Space for fixed footer */
    }

    /* Fixed Header */
    .exam-header-fixed {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        z-index: 1000;
    }

    .header-content {
        max-width: 900px;
        margin: 0 auto;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .exam-info h1 {
        font-size: 1.2rem;
        margin: 0;
        color: #2d3748;
    }

    .phase-badge {
        font-size: 0.75rem;
        background: #edf2f7;
        padding: 2px 8px;
        border-radius: 4px;
        color: #4a5568;
        font-weight: 600;
    }

    .timer-box {
        background: #245E55;
        color: white;
        padding: 8px 16px;
        border-radius: 50px;
        font-weight: 700;
        font-family: monospace;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Progress Bar */
    .progress-wrapper {
        height: 6px;
        background: #e2e8f0;
        width: 100%;
    }

    .progress-fill {
        height: 100%;
        background: #38a169;
        width: 0%;
        transition: width 0.3s ease;
    }

    /* Question Card */
    .question-main {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .question-card {
        background: white;
        padding: 40px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        min-height: 400px;
    }

    .q-meta {
        margin-bottom: 20px;
        color: #718096;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .question-text {
        font-size: 1.25rem;
        line-height: 1.6;
        color: #1a202c;
        margin-bottom: 30px;
    }

    /* Options Buttons */
    .options-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .option-btn {
        display: flex;
        align-items: center;
        width: 100%;
        padding: 18px 25px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        background: white;
        text-align: left;
        font-size: 1.05rem;
        cursor: pointer;
        transition: all 0.2s ease;
        color: #4a5568;
    }

    .option-btn:hover {
        border-color: #245E55;
        background: #f0fdfa;
    }

    .option-btn.selected {
        border-color: #245E55;
        background: #245E55;
        color: white;
        box-shadow: 0 4px 12px rgba(36, 94, 85, 0.2);
    }

    /* Footer Navigation */
    .exam-footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        padding: 15px 0;
        border-top: 1px solid #e2e8f0;
    }

    .footer-content {
        max-width: 900px;
        margin: 0 auto;
        display: flex;
        justify-content: space-between;
        padding: 0 20px;
    }

    .hidden { display: none !important; }

    /* Buttons */
    .rv-btn { padding: 10px 25px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; }
    .rv-btn-outline { border: 1px solid #cbd5e0; background: white; }
    .rv-btn-primary { background: #245E55; color: white; }
    .rv-btn-success { background: #38a169; color: white; }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> {{-- Optional: For pretty alerts --}}
<script>
    const questions = @json($questions);
    let currentIndex = 0;
    let answers = {}; 

    document.addEventListener('DOMContentLoaded', () => {
        renderQuestion();
        startTimer({{ ($timeLimit ?? 60) * 60 }});
        
        // Prevent accidental back navigation
        window.history.pushState(null, null, window.location.href);
        window.onpopstate = function () {
            window.history.go(1);
        };
    });

    function renderQuestion() {
        const q = questions[currentIndex];
        document.getElementById('q-text').innerText = q.question_text;
        document.getElementById('current-q-index').innerText = currentIndex + 1;
        
        const container = document.getElementById('options-container');
        container.innerHTML = ''; 

        ['a', 'b', 'c', 'd'].forEach(letter => {
            const optionText = q['option_' + letter];
            const btn = document.createElement('button');
            btn.className = `option-btn ${answers[q.id] === letter ? 'selected' : ''}`;
            btn.innerHTML = `<span style="margin-right:15px; font-weight:bold">${letter.toUpperCase()}.</span> ${optionText}`;
            btn.onclick = () => selectOption(q.id, letter);
            container.appendChild(btn);
        });

        // Navigation visibility
        document.getElementById('prev-btn').disabled = currentIndex === 0;
        
        if (currentIndex === questions.length - 1) {
            document.getElementById('next-btn').classList.add('hidden');
            document.getElementById('submit-btn').classList.remove('hidden');
        } else {
            document.getElementById('next-btn').classList.remove('hidden');
            document.getElementById('submit-btn').classList.add('hidden');
        }

        // Progress bar update
        const progress = ((currentIndex + 1) / questions.length) * 100;
        document.getElementById('progress-bar').style.width = `${progress}%`;
    }

    function selectOption(qId, letter) {
        answers[qId] = letter;
        renderQuestion();
    }

    document.getElementById('next-btn').onclick = () => {
        if (currentIndex < questions.length - 1) {
            currentIndex++;
            window.scrollTo(0, 0);
            renderQuestion();
        }
    };

    document.getElementById('prev-btn').onclick = () => {
        if (currentIndex > 0) {
            currentIndex--;
            window.scrollTo(0, 0);
            renderQuestion();
        }
    };

    function startTimer(duration) {
        let timer = duration, minutes, seconds;
        const display = document.getElementById('timer');
        
        const countdown = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);
            display.textContent = (minutes < 10 ? "0" : "") + minutes + ":" + (seconds < 10 ? "0" : "") + seconds;

            if (--timer < 0) {
                clearInterval(countdown);
                autoSubmit();
            }
        }, 1000);
    }

    function confirmSubmit() {
        const answeredCount = Object.keys(answers).length;
        const total = questions.length;
        
        if (answeredCount < total) {
            if(!confirm(`You have only answered ${answeredCount} out of ${total} questions. Submit anyway?`)) return;
        } else {
            if(!confirm("Are you sure you want to submit your exam?")) return;
        }
        submitExam();
    }

    function autoSubmit() {
        alert("Time is up! Your answers are being submitted automatically.");
        submitExam();
    }

    function submitExam() {
        // Here you'll make an Axios or Fetch POST request to your backend
        console.log("Saving answers...", answers);
        // Temporary feedback
        document.body.innerHTML = "<div style='text-align:center; padding-top:100px;'><h1>Submitting... Please do not refresh.</h1></div>";
        
        // You will replace this with: 
        // window.location.href = "{{ route('student.mock-boards.results', $mockBoard) }}";
    }
</script>
@endsection