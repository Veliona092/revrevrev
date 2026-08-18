<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Assessment - progressive Q nav</title>
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <style>
    :root { --accent:#0b74de; --answered:#e6f7ff; --muted:#666; --danger:#d9534f; --bg:#f7f8fa; }
    html,body { height:100%; }
    body { margin:0; font-family:system-ui,Segoe UI,Roboto,Arial; height:100vh; display:flex; background:var(--bg); }
    .sidebar { width:260px; background:#f5f5f5; padding:20px; box-sizing:border-box; border-right:1px solid #e6e6e6; overflow:auto; }
    .sidebar h2 { margin:0 0 12px 0; font-size:18px; }
    .exam-item { background:#fff; padding:12px; margin-bottom:12px; border-radius:8px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 1px 3px rgba(0,0,0,0.04); }
    .exam-item button { background:var(--accent); color:#fff; border:0; padding:8px 10px; border-radius:6px; cursor:pointer; }
    .exam-item button[disabled] { opacity:.5; cursor:not-allowed; }
    .main { flex:1; display:flex; flex-direction:column; min-height:0; }
    .topbar { background:#fff; padding:12px 20px; display:flex; align-items:center; border-bottom:1px solid #ddd; box-shadow:0 1px 0 rgba(0,0,0,0.02); }
    .topbar .brand { font-weight:700; }
    .content { padding:20px; overflow:auto; flex:1; }
    .exam-area { max-width:1100px; margin:0 auto; display:flex; gap:20px; align-items:flex-start; }
    .question-panel { flex:1; min-width:0; }
    .nav-wrapper { margin-bottom:12px; }
    .nav-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:6px; }
    .nav-btn { text-align:center; padding:8px 10px; border-radius:6px; border:1px solid #e8e8e8; background:#fff; cursor:pointer; min-width:48px; font-weight:600; }
    .nav-btn.answered { background:var(--answered); border-color:var(--accent); color:var(--accent); }
    .nav-btn.current { outline:2px solid var(--accent); box-shadow:0 0 0 3px rgba(11,116,222,0.06); }
    .question { background:#fff; padding:16px; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.04); margin-bottom:12px; }
    .answers { margin-top:12px; display:flex; flex-direction:column; gap:8px; }
    .answers label { display:flex; gap:8px; align-items:center; padding:8px; border-radius:6px; border:1px solid #eee; cursor:pointer; background:#fff; }
    .answers input[type="radio"] { transform:scale(1.05); }
    .controls { display:flex; gap:8px; margin-top:12px; }
    .btn { padding:8px 12px; border-radius:6px; border:0; cursor:pointer; }
    .btn.primary { background:var(--accent); color:#fff; }
    .btn.ghost { background:#fff; border:1px solid #ddd; }
    .status { margin-top:8px; color:var(--muted); font-size: 16px; }
    .overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:999; }
    .modal { background:#fff; padding:20px; border-radius:8px; max-width:420px; width:100%; box-sizing:border-box; }
    .failed-svg { position:relative; width:320px; margin:28px auto 12px; }
    .failed-svg svg { display:block; }
    .failed-svg .failed-text { position:absolute; left:0; right:0; top:22px; text-align:center; color:#fff; font-weight:800; font-size:20px; letter-spacing:0.4px; }
    .failed-desc { text-align:center; color:var(--danger); font-weight:700; margin-top:8px; }
    /* responsive */
    @media (max-width:900px){
      .sidebar { display:none; }
      .exam-area { padding:0 12px; }
      .failed-svg { width:260px; }
    }
  </style>
</head>
<body>
  <div class="sidebar" aria-hidden="false">
    <h2>Assessments</h2>

    <div class="exam-item" data-exam-id="exam-1">
      <div>
        <div><strong>Exam 1</strong></div>
        <div style="font-size: 16px;color:var(--muted)">10 questions -· 30 minutes</div>
      </div>
      <div>
        <button class="start-btn">Start</button>
      </div>
    </div>

    <div class="exam-item" data-exam-id="exam-2">
      <div>
        <div><strong>Exam 2</strong></div>
        <div style="font-size: 16px;color:var(--muted)">10 questions -· 30 minutes</div>
      </div>
      <div>
        <button class="start-btn">Start</button>
      </div>
    </div>

    <div class="exam-item" data-exam-id="exam-3">
      <div>
        <div><strong>Exam 3</strong></div>
        <div style="font-size: 16px;color:var(--muted)">10 questions -· 30 minutes</div>
      </div>
      <div>
        <button class="start-btn">Start</button>
      </div>
    </div>
  </div>

  <div class="main" role="main">
    <div class="topbar" role="banner">
      <div class="brand">Placeholder</div>
      <div style="margin-left:auto;color:var(--muted)">Session: <span id="session-state">idle</span></div>
    </div>

    <div class="content">
      <div class="exam-area" id="exam-area">
        <div class="question-panel" id="question-panel">
          <div class="nav-wrapper" id="nav-wrapper" style="display:none" aria-hidden="false">
            <div class="nav-row" id="nav-answered-row" aria-label="Answered questions"></div>
            <div class="nav-row" id="nav-remaining-row" aria-label="Remaining questions"></div>
          </div>

          <h1>Assessment</h1>
          <p>Select an exam and click Start. Questions are revealed one by one as you press Next. Answered questions move to the top row and are clickable.</p>
        </div>
      </div>
    </div>
  </div>

  <div id="modal" class="overlay" style="display:none" role="dialog" aria-modal="true">
    <div class="modal" role="document">
      <div id="modal-text">Warning</div>
      <div style="text-align:right;margin-top:12px;">
        <button class="btn ghost" id="modal-close">Close</button>
      </div>
    </div>
  </div>

<script>
(function(){
  const KEY_STARTED = 'exam_started';
  const KEY_WARN_COUNT = 'exam_warn_count';
  const KEY_FAILED = 'exam_failed';

  const examArea = document.getElementById('question-panel');
  const sessionStateEl = document.getElementById('session-state');
  const modal = document.getElementById('modal');
  const modalText = document.getElementById('modal-text');
  const modalClose = document.getElementById('modal-close');
  const navWrapper = document.getElementById('nav-wrapper');
  const navAnsweredRow = document.getElementById('nav-answered-row');
  const navRemainingRow = document.getElementById('nav-remaining-row');

  modalClose.addEventListener('click', ()=> modal.style.display='none');

  function generateQuestions(count){
    const qs = [];
    for(let i=1;i<=count;i++){
      qs.push({
        id: 'q'+i,
        text: `Question ${i}: Which option is correct for sample question ${i}?`,
        choices: [
          `Option A for ${i}`,
          `Option B for ${i}`,
          `Option C for ${i}`,
          `Option D for ${i}`
        ]
      });
    }
    return qs;
  }

  function createNavButton(index, isAnswered, isCurrent, visible){
    const btn = document.createElement('button');
    btn.className = 'nav-btn' + (isAnswered ? ' answered' : '') + (isCurrent ? ' current' : '');
    btn.textContent = `Q${index+1}` + (isAnswered ? ' -œ“' : '');
    btn.dataset.index = index;
    if(!visible) btn.style.display = 'none';
    btn.addEventListener('click', ()=> {
      if(btn.style.display === 'none') return;
      if(typeof window._renderExamUI === 'function'){
        window._renderExamUI(index);
      }
    });
    return btn;
  }

  function lockAllStartButtons(exceptId){
    document.querySelectorAll('.start-btn').forEach(btn=>{
      const parent = btn.closest('.exam-item');
      const id = parent.dataset.examId;
      if(exceptId && id === exceptId) btn.disabled = false;
      else btn.disabled = true;
    });
  }

  function renderFailedSemiCircle(){
    // SVG semi-circle with text and description
    examArea.innerHTML = `
      <div class="failed-svg" role="status" aria-live="assertive">
        <svg viewBox="0 0 200 100" width="320" height="160" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false">
          <path d="M0,100 A100,100 0 0,1 200,100 L200,100 L0,100 Z" fill="#d9534f"></path>
        </svg>
        <div class="failed-text">Exam Failed</div>
      </div>
      <div class="failed-desc">You exceeded the allowed number of warnings. The exam is locked.</div>
    `;
  }

  function markFailed(examId){
    sessionStorage.setItem(KEY_FAILED, examId);
    renderFailedSemiCircle();
    setSessionState('failed');
    lockAllStartButtons();
    removeVisibilityHandlers();
    navWrapper.style.display = 'none';
  }

  function setSessionState(text){ sessionStateEl.textContent = text; }

  // visibility handlers
  let visibilityHandler=null, blurHandler=null, beforeUnloadHandler=null;
  function attachVisibilityHandlers(){
    removeVisibilityHandlers();
    visibilityHandler = ()=>{ if(document.hidden) handleViolation('You switched away from the exam tab.'); };
    blurHandler = ()=>{ handleViolation('Window lost focus (possible alt-tab).'); };
    beforeUnloadHandler = (e)=>{ const count = incrementWarnCount(); if(count>=3){ markFailed(sessionStorage.getItem(KEY_STARTED)); return; } e.preventDefault(); e.returnValue=''; return ''; };
    document.addEventListener('visibilitychange', visibilityHandler);
    window.addEventListener('blur', blurHandler);
    window.addEventListener('beforeunload', beforeUnloadHandler);
  }
  function removeVisibilityHandlers(){
    if(visibilityHandler) document.removeEventListener('visibilitychange', visibilityHandler);
    if(blurHandler) window.removeEventListener('blur', blurHandler);
    if(beforeUnloadHandler) window.removeEventListener('beforeunload', beforeUnloadHandler);
    visibilityHandler = blurHandler = beforeUnloadHandler = null;
  }
  function incrementWarnCount(){
    let count = parseInt(sessionStorage.getItem(KEY_WARN_COUNT) || '0',10);
    count++; sessionStorage.setItem(KEY_WARN_COUNT, String(count)); return count;
  }
  function handleViolation(message){
    const started = sessionStorage.getItem(KEY_STARTED);
    if(!started) return;
    if(sessionStorage.getItem(KEY_FAILED)) return;
    const count = incrementWarnCount();
    if(count===1||count===2){ modalText.textContent = `${message} Warning ${count} of 2.`; modal.style.display='flex'; }
    else if(count>=3){ modalText.textContent = 'Exceeded warnings. Exam failed.'; modal.style.display='flex'; setTimeout(()=>{ modal.style.display='none'; markFailed(started); },1200); }
  }

  // Main: progressive reveal logic with nav above question
  function startExam(examId){
    if(sessionStorage.getItem(KEY_FAILED)){ modalText.textContent='An exam is already failed.'; modal.style.display='flex'; return; }
    if(sessionStorage.getItem(KEY_STARTED) && sessionStorage.getItem(KEY_STARTED)!==examId){ modalText.textContent='Another exam is in progress.'; modal.style.display='flex'; return; }

    sessionStorage.setItem(KEY_STARTED, examId);
    sessionStorage.setItem(KEY_WARN_COUNT, '0');
    setSessionState('in progress ('+examId+')');
    lockAllStartButtons(examId);

    const questions = generateQuestions(10);
    const answers = {}; // index -> choice
    const answeredOrder = []; // indices in order first answered
    let currentIndex = 0;
    let maxVisibleIndex = 0; // only indices <= this are visible

    window._renderExamUI = function(index){
      if(index > maxVisibleIndex) return; // prevent jumping to unrevealed questions
      currentIndex = index;
      render();
    };

    navWrapper.style.display = 'block';

    function renderNavRows(){
      navAnsweredRow.innerHTML = '';
      navRemainingRow.innerHTML = '';

      // answered in the order they were first answered
      answeredOrder.forEach(idx=>{
        const btn = createNavButton(idx, true, idx===currentIndex, true);
        navAnsweredRow.appendChild(btn);
      });

      // remaining: numeric order, but only reveal up to maxVisibleIndex
      for(let i=0;i<questions.length;i++){
        if(!answeredOrder.includes(i)){
          const visible = i <= maxVisibleIndex;
          const btn = createNavButton(i, false, i===currentIndex, visible);
          navRemainingRow.appendChild(btn);
        }
      }
    }

    function render(){
      // remove previous question blocks but keep nav wrapper
      const toRemove = examArea.querySelectorAll('.question, .controls, h2, .status');
      toRemove.forEach(n=>n.remove());

      renderNavRows();

      const q = questions[currentIndex];
      const container = document.createElement('div');
      container.innerHTML = `
        <h2>Exam: ${examId.replace('-',' ').toUpperCase()}</h2>
        <div class="status">Question ${currentIndex+1} of ${questions.length}</div>
        <div class="question" role="group" aria-labelledby="qtitle-${currentIndex}">
          <div id="qtitle-${currentIndex}">${q.text}</div>
          <div class="answers" role="radiogroup" aria-label="Answer choices">
            ${q.choices.map((c,i)=>`<label><input type="radio" name="choice" value="${i}" ${answers[currentIndex]===i?'checked':''} aria-checked="${answers[currentIndex]===i?'true':'false'}"> <span>${c}</span></label>`).join('')}
          </div>
        </div>
        <div class="controls">
          <button class="btn ghost" id="prev-btn" ${currentIndex===0?'disabled':''}>Previous</button>
          <button class="btn primary" id="next-btn">${currentIndex===questions.length-1?'Submit':'Next'}</button>
          <div style="margin-left:auto; align-self:center;">
            <span id="warn-count" class="status">Warnings: ${sessionStorage.getItem(KEY_WARN_COUNT)||0}</span>
          </div>
        </div>
      `;
      examArea.appendChild(container);

      document.getElementById('prev-btn').addEventListener('click', ()=>{
        if(currentIndex>0){ currentIndex--; render(); }
      });

      document.getElementById('next-btn').addEventListener('click', ()=>{
        const selected = document.querySelector('input[name="choice"]:checked');
        if(selected){
          const val = parseInt(selected.value,10);
          const wasAnswered = answers.hasOwnProperty(currentIndex) && answers[currentIndex] !== null;
          answers[currentIndex] = val;
          if(!wasAnswered) answeredOrder.push(currentIndex);
        }
        // reveal next question when Next is pressed (even if unanswered)
        if(currentIndex < questions.length-1){
          maxVisibleIndex = Math.max(maxVisibleIndex, currentIndex+1);
          currentIndex++;
          render();
        } else {
          finishExam();
        }
      });

      // update nav rows after rendering
      renderNavRows();
    }

    function finishExam(){
      const answeredCount = Object.keys(answers).length;
      examArea.innerHTML = `<h2>Exam Submitted</h2><p>You answered ${answeredCount} of ${questions.length} questions.</p><p>Result: <strong>Submitted for grading</strong></p>`;
      setSessionState('submitted ('+examId+')');
      removeVisibilityHandlers();
      lockAllStartButtons();
      navWrapper.style.display = 'none';
    }

    attachVisibilityHandlers();
    // reveal Q1 initially
    maxVisibleIndex = 0;
    render();
  }

  // Wire start buttons
  document.querySelectorAll('.start-btn').forEach(btn=>{
    btn.addEventListener('click', (e)=>{
      const examId = btn.closest('.exam-item').dataset.examId;
      if(sessionStorage.getItem(KEY_FAILED) === examId){
        modalText.textContent = 'This exam has been marked failed and cannot be started.';
        modal.style.display = 'flex';
        return;
      }
      startExam(examId);
    });
  });

  // Restore session if needed
  (function restoreSession(){
    const started = sessionStorage.getItem(KEY_STARTED);
    const failed = sessionStorage.getItem(KEY_FAILED);
    if(failed){
      renderFailedSemiCircle();
      setSessionState('failed');
      return;
    }
    if(started){
      lockAllStartButtons(started);
      setSessionState('in progress ('+started+')');
      examArea.innerHTML = `
        <h2>Resume Exam</h2>
        <p>You have an exam in progress: <strong>${started}</strong>.</p>
        <p><button id="resume-btn" class="btn primary">Resume</button></p>
      `;
      document.getElementById('resume-btn').addEventListener('click', ()=>{
        startExam(started);
      });
    } else {
      setSessionState('idle');
    }
  })();

})();
</script>
</body>
</html>


