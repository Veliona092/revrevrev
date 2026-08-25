@extends('layouts.appTeach')

@section('content')
<style>
    .tb-wrap { max-width: 1280px; margin: 0 auto; }
    .tb-head { margin-bottom: 22px; }
    .tb-head h1 { margin: 0; font-size: 30px; }
    .tb-head p { margin: 5px 0 0; color: #777; }
    .tb-grid { display: grid; grid-template-columns: 350px minmax(0, 1fr); gap: 22px; }
    .tb-card { background: #fff; border: 1px solid #e6e6e6; border-radius: 12px; padding: 20px; }
    .tb-card h2 { margin: 0 0 16px; font-size: 19px; }
    .tb-field { margin-bottom: 12px; }
    .tb-field label { display: block; color: #666; font-size: 13px; font-weight: 600; margin-bottom: 5px; }
    .tb-field input, .tb-field select, .tb-field textarea, .tb-filter input, .tb-filter select, .tb-destination { width: 100%; border: 1px solid #ddd; border-radius: 7px; padding: 9px; font: inherit; }
    .tb-options { display: grid; grid-template-columns: 1fr; gap: 8px; }
    .tb-option-row { display: grid; grid-template-columns: 40px 1fr auto; gap: 8px; align-items: center; }
    .tb-option-row .letter { font-weight: 700; color: #555; text-align: center; }
    .tb-option-row .remove-btn { border: 1px solid #ccc; background: #fff; color: #b74343; border-radius: 7px; padding: 8px 10px; cursor: pointer; font: inherit; }
    .tb-option-row .remove-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .tb-filter { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; }
    .tb-table { width: 100%; border-collapse: collapse; font-size: 14px; }
    .tb-table th, .tb-table td { border-bottom: 1px solid #eee; padding: 12px 8px; text-align: left; vertical-align: top; }
    .tb-table th { color: #777; font-size: 12px; text-transform: uppercase; }
    .tb-tag { display: inline-block; padding: 3px 7px; background: #f0f0f5; border-radius: 99px; font-size: 12px; margin: 0 3px 3px 0; }
    .tb-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin: 14px 0; }
    .tb-btn { border: 0; border-radius: 7px; padding: 9px 12px; background: #3a4180; color: #fff; cursor: pointer; font: inherit; text-decoration: none; }
    .tb-btn.alt { background: #fff; border: 1px solid #ccc; color: #333; }
    .tb-btn.danger { background: #b74343; }
    .tb-alert { margin-bottom: 16px; padding: 12px; border-radius: 8px; background: #e8f5ee; color: #17603c; }
    .tb-errors { margin: 0 0 16px; padding: 12px 28px; border-radius: 8px; background: #fdecec; color: #982b2b; }

    .pagination { display: flex; list-style: none; gap: 6px; padding: 0; margin: 0; flex-wrap: wrap; }
    .pagination .page-item .page-link {
        display: inline-block;
        padding: 7px 12px;
        border: 1px solid #ddd;
        border-radius: 7px;
        color: #3a4180;
        text-decoration: none;
        font-size: 14px;
        background: #fff;
    }
    .pagination .page-item .page-link:hover { background: #f0f0f5; }
    .pagination .page-item.active .page-link {
        background: #3a4180;
        border-color: #3a4180;
        color: #fff;
    }
    .pagination .page-item.disabled .page-link {
        color: #bbb;
        pointer-events: none;
        background: #fafafa;
    }
    @media (max-width: 900px) { .tb-grid { grid-template-columns: 1fr; } .tb-filter { grid-template-columns: 1fr 1fr; } }
</style>

<div class="tb-wrap">
    <div class="tb-head">
        <h1>Test Bank</h1>
        <p>Create questions once, then reuse approved questions in pre-tests, post-tests, assessments, and mock boards.</p>
    </div>

    @if(session('success'))<div class="tb-alert">{{ session('success') }}</div>@endif
    @if($errors->any())<ul class="tb-errors">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif

    <div class="tb-grid">
        <section class="tb-card">
            <h2>{{ $editQuestion ? 'Edit Test Bank Question' : 'Add Test Bank Question' }}</h2>
            <form method="POST" action="{{ $editQuestion ? route('test-bank.update', $editQuestion) : route('test-bank.store') }}">
                @csrf
                @if($editQuestion) @method('PUT') @endif

                <div class="tb-field">
                    <label>Question</label>
                    <textarea name="question_text" rows="4" required>{{ old('question_text', $editQuestion?->question_text) }}</textarea>
                </div>

                <div class="tb-field">
                    <label>Choices</label>
                    <div class="tb-options" id="optionsContainer">
                        {{-- JS will render the option rows --}}
                    </div>
                    <div style="margin-top: 10px;">
                        <button type="button" class="tb-btn alt" id="addOptionBtn">+ Add choice</button>
                    </div>
                </div>

                <div class="tb-field">
                    <label>Correct choice</label>
                    <select name="correct_option" id="correctOptionSelect" required>
                        {{-- JS will populate --}}
                    </select>
                </div>

                <div class="tb-field">
                    <label>Difficulty</label>
                    <select name="difficulty">
                        @foreach(['Average', 'Normal', 'Hard'] as $difficulty)
                            <option value="{{ $difficulty }}" @selected(old('difficulty', $editQuestion?->difficulty ?? 'Normal') === $difficulty)>{{ $difficulty }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="tb-field">
                    <label>Topic</label>
                    <input type="text" name="topic" value="{{ old('topic', $editQuestion?->topic) }}" placeholder="e.g. Chapter 3 — Cash Flow">
                </div>

                <input type="hidden" name="points" value="1">
                <input type="hidden" name="status" value="approved">

                <button class="tb-btn" type="submit">{{ $editQuestion ? 'Update Test Bank Question' : 'Save to Test Bank' }}</button>
                @if($editQuestion)
                    <a class="tb-btn alt" href="{{ route('test-bank.index') }}">Cancel</a>
                @endif
            </form>
        </section>

        <section class="tb-card">
            <h2>Find and Reuse Questions</h2>
            <form method="GET" class="tb-filter">
                <input name="search" value="{{ request('search') }}" placeholder="Search questions...">
                <input name="topic" value="{{ request('topic') }}" placeholder="Filter by topic...">
                <select name="difficulty">
                    <option value="">All difficulties</option>
                    @foreach(['Average', 'Normal', 'Hard'] as $difficulty)
                        <option value="{{ $difficulty }}" @selected(request('difficulty') === $difficulty)>{{ $difficulty }}</option>
                    @endforeach
                </select>
                <button class="tb-btn" type="submit">Filter</button>
                <a class="tb-btn alt" href="{{ route('test-bank.index') }}">Clear</a>
            </form>

            <form id="addToModuleForm" method="POST" action="{{ url('/test-bank/modules/__MODULE__/questions') }}">
                @csrf
                <div class="tb-actions">
                    <select class="tb-destination" id="moduleSelect" required>
                        <option value="">Select destination assessment</option>
                        @foreach($modules as $module)
                            <option value="{{ $module->id }}">
                                {{ $module->title }}{{ $module->assessment_purpose ? ' · '.ucfirst(str_replace('_', ' ', $module->assessment_purpose)) : '' }}
                            </option>
                        @endforeach
                    </select>
                    <button class="tb-btn" type="submit">Add selected questions</button>
                </div>

                <div id="moduleQuestionsPreview" style="display:none;margin-bottom:14px;padding:12px;background:#fafafa;border:1px solid #eee;border-radius:8px;">
                    <div style="font-weight:600;font-size:13px;color:#666;margin-bottom:8px;" id="moduleQuestionsTitle"></div>
                    <div id="moduleQuestionsList" style="font-size:13px;"></div>
                </div>

                <table class="tb-table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Question</th>
                            <th>Topic</th>
                            <th>Classification</th>
                            <th>Choices</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($questions as $question)
                            <tr>
                                <td><input type="checkbox" name="test_bank_question_ids[]" value="{{ $question->id }}"></td>
                                <td><strong>{{ Str::limit($question->question_text, 120) }}</strong></td>
                                <td>{{ $question->topic ?? '—' }}</td>
                                <td><span class="tb-tag">{{ $question->difficulty }}</span></td>
                                <td>{{ is_array($question->options) ? implode(', ', array_keys($question->options)) : '' }}</td>
                                <td>
                                    <a class="tb-btn alt" href="{{ route('test-bank.index', ['edit' => $question->id]) }}">Edit</a>
                                    <button class="tb-btn danger" type="submit" form="archive-{{ $question->id }}">Archive</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5">No Test Bank questions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </form>

            @foreach($questions as $question)
                <form id="archive-{{ $question->id }}" method="POST" action="{{ route('test-bank.archive', $question) }}">
                    @csrf
                    @method('PATCH')
                </form>
            @endforeach

            <div style="margin-top:16px;">{{ $questions->links('pagination::bootstrap-5') }}</div>
        </section>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    const minOptions = 2;
    const maxOptions = 10;          // adjust if you want a higher limit
    const container = document.getElementById('optionsContainer');
    const correctSelect = document.getElementById('correctOptionSelect');
    const addBtn = document.getElementById('addOptionBtn');

    // Existing data when editing (or old() on validation error)
    const existingOptions = @json(old('options', $editQuestion?->options ?? []));
    const existingCorrect = @json(old('correct_option', $editQuestion?->correct_option ?? 'A'));

    function getCurrentLetters() {
        return Array.from(container.querySelectorAll('.tb-option-row')).map(row => row.dataset.letter);
    }

    function rebuildCorrectSelect(selected) {
        const current = getCurrentLetters();
        correctSelect.innerHTML = '';
        current.forEach(letter => {
            const opt = document.createElement('option');
            opt.value = letter;
            opt.textContent = letter;
            if (letter === selected) opt.selected = true;
            correctSelect.appendChild(opt);
        });
        // Fallback if previously selected letter was removed
        if (!current.includes(selected) && current.length) {
            correctSelect.value = current[0];
        }
    }

    function updateRemoveButtons() {
        const rows = container.querySelectorAll('.tb-option-row');
        rows.forEach(row => {
            const btn = row.querySelector('.remove-btn');
            btn.disabled = rows.length <= minOptions;
        });
        addBtn.disabled = rows.length >= maxOptions;
    }

    function createOptionRow(letter, value = '') {
        const row = document.createElement('div');
        row.className = 'tb-option-row';
        row.dataset.letter = letter;

        row.innerHTML = `
            <span class="letter">${letter}</span>
            <input type="text" name="options[${letter}]" value="${value.replace(/"/g, '&quot;')}" required placeholder="Choice ${letter}">
            <button type="button" class="remove-btn" title="Remove choice">×</button>
        `;

        row.querySelector('.remove-btn').addEventListener('click', () => {
            if (container.querySelectorAll('.tb-option-row').length <= minOptions) return;
            row.remove();
            renumberLetters();
            rebuildCorrectSelect(correctSelect.value);
            updateRemoveButtons();
        });

        return row;
    }

    function renumberLetters() {
        const rows = container.querySelectorAll('.tb-option-row');
        rows.forEach((row, index) => {
            const letter = letters[index];
            row.dataset.letter = letter;
            row.querySelector('.letter').textContent = letter;
            const input = row.querySelector('input');
            input.name = `options[${letter}]`;
            input.placeholder = `Choice ${letter}`;
        });
    }

    function addOption(value = '') {
        const currentCount = container.querySelectorAll('.tb-option-row').length;
        if (currentCount >= maxOptions) return;

        const letter = letters[currentCount];
        container.appendChild(createOptionRow(letter, value));
        rebuildCorrectSelect(correctSelect.value || 'A');
        updateRemoveButtons();
    }

    // Initial render
    const initialKeys = Object.keys(existingOptions || {});
    if (initialKeys.length >= minOptions) {
        // Preserve order if possible (A, B, C...)
        const ordered = letters.filter(l => initialKeys.includes(l));
        // Also include any extra keys that might exist
        initialKeys.forEach(k => { if (!ordered.includes(k)) ordered.push(k); });

        ordered.forEach(letter => {
            addOption(existingOptions[letter] ?? '');
        });
    } else {
        // Default: 4 choices
        for (let i = 0; i < 4; i++) {
            addOption(existingOptions[letters[i]] ?? '');
        }
    }

    // Set the correct answer after options are rendered
    rebuildCorrectSelect(existingCorrect);

    addBtn.addEventListener('click', () => addOption());

    // Keep the existing module-select logic
    document.getElementById('addToModuleForm').addEventListener('submit', function () {
        this.action = this.action.replace('__MODULE__', document.getElementById('moduleSelect').value);
    });

    // ── Ipakita ang mga tanong ng napiling assessment ──
    const moduleSelectEl = document.getElementById('moduleSelect');
    const previewBox = document.getElementById('moduleQuestionsPreview');
    const previewTitle = document.getElementById('moduleQuestionsTitle');
    const previewList = document.getElementById('moduleQuestionsList');

    moduleSelectEl.addEventListener('change', function () {
        const moduleId = this.value;

        if (!moduleId) {
            previewBox.style.display = 'none';
            return;
        }

        previewBox.style.display = 'block';
        previewTitle.textContent = 'Loading...';
        previewList.innerHTML = '';

        fetch(`/test-bank/modules/${moduleId}/questions`, {
            headers: { 'Accept': 'application/json' },
        })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    previewTitle.textContent = 'Failed to load questions.';
                    return;
                }

                const n = data.questions.length;
                previewTitle.textContent = `${data.module_title} — ${n} question(s) currently in this assessment`;

                if (n === 0) {
                    previewList.innerHTML = '<div style="color:#aaa;">Wala pang tanong sa assessment na ito.</div>';
                    return;
                }

                previewList.innerHTML = data.questions.map((q, i) => `
                    <div style="padding:6px 0;border-bottom:1px solid #eee;">
                        ${i + 1}. ${q.question_text}
                        ${q.test_bank_question_id ? '<span class="tb-badge" style="margin-left:6px;">From Test Bank</span>' : ''}
                    </div>
                `).join('');
            })
            .catch(() => {
                previewTitle.textContent = 'Failed to load questions.';
            });
    });
})();
</script>
@endsection