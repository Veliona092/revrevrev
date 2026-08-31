@extends('layouts.appTeach')

@section('title', 'Student Performance')
@section('page-heading', 'Student Performance')

@section('content')
<style>
    .sp-wrap {
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .sp-sub {
        font-size: 19px;
        color: #888;
        margin: -6px 0 8px;
    }

    /* Class switcher */
    .sp-switcher {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .sp-switcher-label {
        font-size: 19px;
        font-weight: 500;
        color: #888;
        white-space: nowrap;
    }

    .sp-class-tabs {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .sp-class-tab {
        height: 38px; padding: 0 16px; border-radius: 10px;
        font-size: 18px; font-weight: 500;
        border: 1px solid #e4e4e4; background: #fff; color: #555;
        cursor: pointer; text-decoration: none;
        display: inline-flex; align-items: center;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }

    .sp-class-tab:hover  { border-color: #bbb; color: #111; }
    .sp-class-tab.active { background: #0f0f0f; color: #fff; border-color: #0f0f0f; }

    .sp-top-grid {
        display: grid;
        grid-template-columns: 180px minmax(0, 1fr) minmax(0, 1fr);
        gap: 18px;
        align-items: stretch;
    }

    .sp-bottom-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 18px;
    }

    .sp-card {
        background: #fff;
        border: 1px solid #ebebeb;
        border-radius: 14px;
        padding: 1.35rem 1.5rem;
        min-width: 0;
    }

    .sp-section-title {
        font-size: 22px;
        font-weight: 500;
        color: #111;
        margin: 0 0 16px;
    }

    /* Class Avg card */
    .sp-avg-card {
        display: flex; flex-direction: column;
        justify-content: center; align-items: center;
        border-width: 1px; border-style: solid; border-radius: 12px;
        padding: 1rem 1.1rem; min-width: 0; transition: background 0.3s;
        min-height: 300px;
        text-align: center;
    }

    .sp-avg-card.high { background: #e1f5ee; border-color: #9fe1cb; }
    .sp-avg-card.mid  { background: #faeeda; border-color: #fac775; }
    .sp-avg-card.low  { background: #fcebeb; border-color: #f7c1c1; }

    .sp-card-label {
        font-size: 17px; font-weight: 500;
        letter-spacing: 0.06em; text-transform: uppercase; margin: 0 0 8px;
        text-align: center;
        white-space: nowrap;
    }

    .sp-avg-card.high .sp-card-label { color: #0f6e56; }
    .sp-avg-card.mid  .sp-card-label { color: #854f0b; }
    .sp-avg-card.low  .sp-card-label { color: #a32d2d; }

    .sp-avg-val {
        font-family: 'DM Sans', sans-serif;
        font-size: 48px; font-weight: 500; line-height: 1; margin: 0 0 6px;
    }

    .sp-avg-card.high .sp-avg-val { color: #085041; }
    .sp-avg-card.mid  .sp-avg-val { color: #633806; }
    .sp-avg-card.low  .sp-avg-val { color: #791f1f; }

    .sp-avg-unit { font-size: 17px; opacity: 0.7; }

    .sp-chart-card { display: flex; flex-direction: column; }

    .sp-chart-wrap {
        position: relative; width: 100%; flex: 1; min-height: 220px;
    }

    .sp-chart-wrap canvas { display: block; margin: 0 auto; }


    /* Question items (legacy list, kept for the empty state / fallback) */
    .sp-q-list {
        display: flex; flex-direction: column; gap: 8px;
        max-height: 300px; overflow-y: auto; padding-right: 4px;
    }

    .sp-q-list::-webkit-scrollbar { width: 4px; }
    .sp-q-list::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 99px; }

    .sp-q-item {
        padding: 12px 14px; border-radius: 10px;
        border-left: 3px solid transparent; background: #fafafa;
        border-top: 1px solid #f0f0f0; border-right: 1px solid #f0f0f0; border-bottom: 1px solid #f0f0f0;
    }

    .sp-q-item.q-high { background: #f0fdf7; border-left-color: #1d9e75; }
    .sp-q-item.q-mid  { background: #fffbf0; border-left-color: #ef9f27; }
    .sp-q-item.q-low  { background: #fff8f8; border-left-color: #e24b4a; }

    .sp-q-meta {
        display: flex; justify-content: space-between;
        align-items: baseline; margin-bottom: 3px; gap: 10px;
    }

    .sp-q-text  { font-size: 18px; color: #333; line-height: 1.5; flex: 1; word-break: break-word; }
    .sp-q-pct   { font-size: 19px; font-weight: 500; white-space: nowrap; flex-shrink: 0; }

    .sp-q-item.q-high .sp-q-pct { color: #0f6e56; }
    .sp-q-item.q-mid  .sp-q-pct { color: #854f0b; }
    .sp-q-item.q-low  .sp-q-pct { color: #a32d2d; }

    .sp-q-count { font-size: 18px; color: #999; display: block; margin-bottom: 7px; }

    .sp-bar-track { height: 6px; background: rgba(0,0,0,0.06); border-radius: 99px; overflow: hidden; }
    .sp-bar-fill  { height: 100%; border-radius: 99px; transition: width 0.5s ease; }

    /* Ranking table */
    .sp-table { width: 100%; border-collapse: collapse; font-size: 18px; }

    .sp-table thead th {
        font-size: 14px; font-weight: 500; letter-spacing: 0.08em;
        text-transform: uppercase; color: #bbb;
        padding: 10px 10px 12px; text-align: left; border-bottom: 1px solid #f0f0f0;
    }

    .sp-table tbody tr { border-bottom: 1px solid #f7f7f7; transition: background 0.1s; }
    .sp-table tbody tr:hover { background: #fafafa; }
    .sp-table tbody td { padding: 12px 10px; color: #333; vertical-align: middle; }

    .sp-rank-num { font-size: 19px; color: #999; font-weight: 500; width: 32px; }

    .sp-rank-medal {
        display: inline-flex; align-items: center; justify-content: center;
        width: 28px; height: 28px; border-radius: 50%;
        font-size: 16px; font-weight: 500;
    }

    .sp-rank-medal.gold   { background: #faeeda; color: #854f0b; }
    .sp-rank-medal.silver { background: #f1efe8; color: #5f5e5a; }
    .sp-rank-medal.bronze { background: #faece7; color: #712b13; }

    .sp-score-pill {
        display: inline-block; font-size: 18px; font-weight: 500;
        padding: 4px 11px; border-radius: 99px; background: #f3f3f3; color: #555;
    }

    .sp-score-pill.high { background: #e1f5ee; color: #0f6e56; }
    .sp-score-pill.mid  { background: #faeeda; color: #854f0b; }
    .sp-score-pill.low  { background: #fcebeb; color: #a32d2d; }

    /* AI card */
    .sp-ai-card { display: flex; flex-direction: column; border-left: 3px solid #7f77dd !important; }

    .sp-ai-body { font-size: 18px; color: #444; line-height: 1.8; flex: 1; }
    .sp-ai-body p            { margin: 0 0 8px; }
    .sp-ai-body p:last-child  { margin-bottom: 0; }
    .sp-ai-body strong        { font-weight: 500; color: #111; }
    .sp-ai-body ul            { padding-left: 1.2rem; margin: 4px 0 8px; }
    .sp-ai-body li            { margin-bottom: 3px; }

    .sp-ai-footer { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #f0f0f0; }

    .sp-refresh-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 32px;
        padding: 0 12px;
        background: #fff;
        border: 1px solid #e4e4e4;
        border-radius: 8px;
        font-family: 'DM Sans', sans-serif;
        font-size: 13px;
        font-weight: 500;
        color: #555;
        white-space: nowrap;
        line-height: 1;
        cursor: pointer;
        transition: border-color 0.15s, color 0.15s, background 0.15s;
        text-decoration: none;
        box-sizing: border-box;
    }

    .sp-analysis-btn {
        height: 30px;
        padding: 0 10px;
        font-size: 12px;
        gap: 5px;
        white-space: nowrap;
    }

    .sp-analysis-btn i {
        font-size: 12px;
        margin-right: 0;
        flex-shrink: 0;
    }

    .sp-refresh-btn:hover { border-color: #111; color: #111; background: #f8fafc; }

    .sp-empty { font-size: 19px; color: #bbb; text-align: center; padding: 2.25rem 0; }

    .sp-alert { padding: 12px 16px; border-radius: 10px; font-size: 18px; margin-bottom: 6px; }
    .sp-alert.success { background: #e1f5ee; color: #0f6e56; }
    .sp-alert.error   { background: #fcebeb; color: #a32d2d; }

    @media (max-width: 860px) {
        .sp-top-grid    { grid-template-columns: 180px 1fr; }
        .sp-bottom-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 640px) {
        .sp-top-grid { grid-template-columns: 1fr; }
        .sp-avg-card { min-height: 220px; }
    }

     /* ITEM ANALYSIS DIALOG (redesigned) */
    dialog.ia-dialog {
        border: none;
        border-radius: 16px;
        padding: 0;
        width: 760px;
        max-width: 95vw;
        max-height: 90vh;
        margin: auto;
        overflow: hidden;
        box-shadow: 0 24px 80px rgba(0,0,0,0.18);
        font-family: 'DM Sans', sans-serif;
    }

    dialog.ia-dialog::backdrop {
        background: rgba(0,0,0,0.45);
        backdrop-filter: blur(3px);
    }

    /* Dialog header */
    .ia-dialog-head {
        padding: 24px 28px 18px;
        border-bottom: 1px solid #f0f0f0;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        background: #fff;
    }

    .ia-dialog-name {
        font-family: 'DM Sans', sans-serif;
        font-size: 24px;
        font-weight: 500;
        color: #111;
        margin: 0 0 6px;
        letter-spacing: -0.02em;
    }

    .ia-dialog-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .ia-dialog-close {
        width: 38px; height: 38px; border: 1px solid #e4e4e4; background: #fff;
        border-radius: 10px; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; color: #888; flex-shrink: 0;
        transition: border-color 0.15s, color 0.15s;
    }

    .ia-dialog-close:hover { border-color: #111; color: #111; }

    /* Score summary strip */
    .ia-score-strip {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        border-bottom: 1px solid #f0f0f0;
        background: #fafafa;
    }

    .ia-score-cell {
        padding: 14px 18px;
        border-right: 1px solid #f0f0f0;
        text-align: center;
    }

    .ia-score-cell:last-child { border-right: none; }

    .ia-score-cell-label {
        font-size: 14px; font-weight: 500; letter-spacing: 0.08em;
        text-transform: uppercase; color: #bbb; margin: 0 0 4px;
    }

    .ia-score-cell-val {
        font-size: 24px; font-weight: 500; color: #111; line-height: 1;
    }

    .ia-score-cell-val.green { color: #1d9e75; }
    .ia-score-cell-val.red   { color: #e24b4a; }
    .ia-score-cell-val.amber { color: #854f0b; }

    /* Answer list */
    .ia-answer-list {
        padding: 18px 24px;
        overflow-y: auto;
        max-height: calc(90vh - 220px);
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: #fff;
    }

    .ia-answer-list::-webkit-scrollbar { width: 4px; }
    .ia-answer-list::-webkit-scrollbar-thumb { background: #e8e8e8; border-radius: 99px; }

    .ia-answer-item {
        display: flex;
        gap: 14px;
        padding: 14px 16px;
        border-radius: 10px;
        border: 1px solid #f0f0f0;
        background: #fafafa;
        align-items: flex-start;
        transition: background 0.1s;
    }

    .ia-answer-item.correct { background: #f0fdf7; border-color: #c6f0e0; }
    .ia-answer-item.wrong   { background: #fff8f8; border-color: #fad4d4; }

    /* Icon */
    .ia-answer-icon {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 17px; flex-shrink: 0; margin-top: 1px;
    }

    .ia-answer-item.correct .ia-answer-icon { background: #1d9e75; color: #fff; }
    .ia-answer-item.wrong   .ia-answer-icon { background: #e24b4a; color: #fff; }

    /* Content */
    .ia-answer-content { flex: 1; min-width: 0; }

    .ia-answer-q-num {
        font-size: 14px; font-weight: 500; letter-spacing: 0.08em;
        text-transform: uppercase; color: #bbb; margin: 0 0 3px;
    }

    .ia-answer-q-text {
        font-size: 17px; color: #111; font-weight: 500;
        line-height: 1.5; margin: 0 0 10px; word-break: break-word;
    }

    .ia-answer-options {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .ia-option-chip {
        display: inline-flex; align-items: center; gap: 5px;
        font-size: 16px; font-weight: 500;
        padding: 4px 11px; border-radius: 99px;
        border: 1px solid transparent;
    }

    .ia-option-chip.selected-correct {
        background: #e1f5ee; color: #0f6e56; border-color: #9fe1cb;
    }

    .ia-option-chip.selected-wrong {
        background: #fcebeb; color: #a32d2d; border-color: #f7c1c1;
    }

    .ia-option-chip.correct-answer {
        background: #e1f5ee; color: #0f6e56; border-color: #9fe1cb;
    }

    .ia-option-chip.neutral {
        background: #f3f3f3; color: #888; border-color: #e4e4e4;
    }

    .ia-chip-label { font-size: 15px; opacity: 0.75; }

    /* Empty / loading states */
    .ia-loading {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center; padding: 3rem; gap: 10px;
        color: #bbb; font-size: 17px;
    }

    .ia-loading i { font-size: 24px; opacity: 0.3; }

    .ia-no-data {
        display: flex; flex-direction: column; align-items: center;
        justify-content: center; padding: 3rem; gap: 8px;
        text-align: center;
    }

    .ia-no-data i { font-size: 28px; color: #e8e8e8; }
    .ia-no-data p { font-size: 17px; color: #999; margin: 0; }
    .ia-no-data small { font-size: 15px; color: #bbb; }

    /* Performance tabs */
    .sp-tab-nav {
        display: flex;
        gap: 0;
        border-bottom: 2px solid #f0f0f0;
    }

    .sp-tab-btn {
        height: 38px;
        padding: 0 20px;
        background: transparent;
        border: none;
        border-bottom: 2px solid transparent;
        margin-bottom: -2px;
        font-family: 'DM Sans', sans-serif;
        font-size: 17px;
        font-weight: 500;
        color: #aaa;
        cursor: pointer;
        transition: color 0.15s, border-bottom-color 0.15s;
    }

    .sp-tab-btn:hover  { color: #555; }
    .sp-tab-btn.active { color: #111; border-bottom-color: #111; }

    .sp-tab-panel        { display: none; flex-direction: column; gap: 14px; }
    .sp-tab-panel.active { display: flex; }

    .sp-bottom-grid--full { grid-template-columns: 1fr; }
</style>

@php
    $teacherClasses = $teacherClasses ?? collect();
    $payload = $payload ?? [
        'classAverage' => $classAverage ?? 0,
        'passCount' => $passCount ?? 0,
        'failCount' => $failCount ?? 0,
        'questionStats' => $questionStats ?? collect(),
        'topStudents' => $topStudents ?? collect(),
        'remainingCount' => $remainingCount ?? 0,
        'aiSummary' => $aiSummary ?? 'No AI summary yet. Click refresh to generate one.',
    ];

    $classAverage   = (float) ($payload['classAverage'] ?? 0);
    $passCount      = (int) ($payload['passCount'] ?? 0);
    $failCount      = (int) ($payload['failCount'] ?? 0);
    $questionStats  = collect($payload['questionStats'] ?? collect());
    $topStudents    = collect($payload['topStudents'] ?? collect());
    $remainingCount = (int) ($payload['remainingCount'] ?? 0);
    $classSummaryEnabled = (bool) ($payload['classSummaryEnabled'] ?? true);
    $aiSummary      = $classSummaryEnabled
        ? ($payload['aiSummary'] ?? 'No AI summary yet. Click refresh to generate one.')
        : 'AI class insights are disabled for this class.';
    $avgClass       = $classAverage >= 75 ? 'high' : ($classAverage >= 50 ? 'mid' : 'low');

    $assessPayload   = $assessmentPayload ?? [];
    $aClassAverage   = (float) ($assessPayload['classAverage'] ?? 0);
    $aPassCount      = (int) ($assessPayload['passCount'] ?? 0);
    $aFailCount      = (int) ($assessPayload['failCount'] ?? 0);
    $aQuestionStats  = collect($assessPayload['questionStats'] ?? []);
    $aTopStudents    = collect($assessPayload['topStudents'] ?? []);
    $aRemainingCount = (int) ($assessPayload['remainingCount'] ?? 0);
    $assessmentAnalysisEnabled = (bool) ($assessPayload['assessmentAnalysisEnabled'] ?? true);
    $aAiSummary      = $assessmentAnalysisEnabled
        ? ($assessPayload['aiSummary'] ?? 'No assessment insights available yet.')
        : 'Assessment AI analysis is disabled for this class.';
    $aAvgClass       = $aClassAverage >= 75 ? 'high' : ($aClassAverage >= 50 ? 'mid' : 'low');

    // Pre-build the JS-facing question breakdown arrays here (outside @json())
    // so Blade's directive parser never has to deal with a multi-line
    // fn(...) => [...] arrow-array literal inline inside @json(...).
    $currentQuestionStatsArr = $questionStats->map(function ($q) {
        return [
            'question_text' => html_entity_decode(strip_tags($q->question_text)),
            'pct_correct' => (float) $q->pct_correct,
            'correct_count' => $q->correct_count,
            'total_answers' => $q->total_answers,
        ];
    })->values();

    $currentAssessQuestionStatsArr = $aQuestionStats->map(function ($q) {
        return [
            'question_text' => html_entity_decode(strip_tags($q->question_text)),
            'pct_correct' => (float) $q->pct_correct,
            'correct_count' => $q->correct_count,
            'total_answers' => $q->total_answers,
        ];
    })->values();
@endphp

<div class="sp-wrap">

    {{-- Class switcher --}}
    @if($teacherClasses->count() > 1)
    <div class="sp-switcher">
        <span class="sp-switcher-label">Class:</span>
        <div class="sp-class-tabs">
            @foreach($teacherClasses as $tc)
                <a href="{{ route('student.performance', $tc) }}"
                   data-class-id="{{ $tc->id }}"
                   class="sp-class-tab js-class-tab {{ $tc->id === $class->id ? 'active' : '' }}">
                    {{ $tc->name }}
                </a>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Performance type tabs --}}
    <div class="sp-tab-nav">
        <button class="sp-tab-btn active" data-tab="quiz">Pre-Assessment</button>
        <button class="sp-tab-btn" data-tab="assessment">Assessments</button>
    </div>

    {{-- Pre-Assessment panel --}}
    <div class="sp-tab-panel active" id="tab-quiz">
    <p class="sp-sub" id="spSubtitle">{{ $class->name ?? 'Class' }} - pre-assessment results and question breakdown.</p>
    {{-- Top row --}}
    <div class="sp-top-grid">

        <div class="sp-avg-card {{ $avgClass }}">
            <p class="sp-card-label">Class avg</p>
            <p class="sp-avg-val" id="classAverageValue">{{ number_format($classAverage, 1) }}</p>
            <p class="sp-avg-unit">percent</p>
        </div>

        <div class="sp-card sp-chart-card">
            <p class="sp-section-title">Pass / Fail</p>
            @if(($passCount + $failCount) > 0)
                <div class="sp-chart-wrap" id="chartWrap">
                    <canvas id="passFailChart"></canvas>
                </div>
            @else
                <p class="sp-empty">No attempts yet.</p>
            @endif
        </div>

        <div class="sp-card sp-chart-card">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                <p class="sp-section-title" style="margin: 0;">Question Breakdown</p>
                <div class="sp-chart-legend" style="display: flex; align-items: center; gap: 12px; font-size: 12px; font-weight: 500;">
                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #166534;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #1d9e75; display: inline-block;"></span> &ge;70% High</span>
                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #9a3412;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #ef9f27; display: inline-block;"></span> 40–69% Moderate</span>
                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #991b1b;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #e24b4a; display: inline-block;"></span> &lt;40% Low</span>
                </div>
            </div>
            @if($questionStats->isNotEmpty())
                <div class="sp-chart-wrap" id="questionChartWrap">
                    <canvas id="questionBreakdownChart"></canvas>
                </div>
            @else
                <p class="sp-empty" id="questionBreakdownEmpty">No answer data yet.</p>
            @endif
        </div>

    </div>

    {{-- Bottom row --}}
    <div class="sp-bottom-grid">

        <div class="sp-card">
            <p class="sp-section-title">Student Rankings</p>
            @if($topStudents->isNotEmpty())
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th style="text-align:right">Avg Score</th>
                            <th style="text-align:right"></th>
                        </tr>
                    </thead>
                    <tbody id="topStudentsBody">
                        @foreach($topStudents as $i => $student)
                            @php
                                $avg        = (float) $student->average_score;
                                $pillClass  = $avg >= 75 ? 'high' : ($avg >= 50 ? 'mid' : 'low');
                                $medalClass = $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : ''));
                            @endphp
                            <tr>
                                <td>
                                    @if($medalClass)
                                        <span class="sp-rank-medal {{ $medalClass }}">{{ $i + 1 }}</span>
                                    @else
                                        <span class="sp-rank-num">{{ $i + 1 }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $student->name }}
                                    @if(!empty($student->program))
                                        <span style="font-size: 16px;color:#bbb;margin-left:4px;">{{ $student->program }}</span>
                                    @endif
                                </td>
                                <td style="text-align:right">
                                    <span class="sp-score-pill {{ $pillClass }}">
                                        {{ number_format($avg, 1) }}%
                                    </span>
                                </td>
                                <td style="text-align:right">
                                    <button class="sp-refresh-btn sp-analysis-btn" type="button"
                                            onclick="openItemAnalysis({{ $student->id }}, '{{ addslashes($student->name) }}')">
                                        <i class="fas fa-chart-bar"></i> Analysis
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if(isset($remainingCount) && $remainingCount > 0)
                    <p style="font-size: 16px;color:#bbb;margin-top:10px;" id="remainingStudentsLabel">
                        +{{ $remainingCount }} more students
                    </p>
                @endif
            @else
                <p class="sp-empty">No student data yet.</p>
            @endif
        </div>

        <div class="sp-card sp-ai-card">
            <p class="sp-section-title">AI Class Insights</p>
            <div id="aiRawText" style="display:none;">{{ $aiSummary ?? 'No insights available yet.' }}</div>
            <div class="sp-ai-body" id="aiSummaryBody"></div>
            <div class="sp-ai-footer">
                <form action="{{ route('student.performance.refresh', ['class' => $class->id]) }}"
                      method="POST" id="refreshInsightsForm">
                    @csrf
                    <button type="submit" class="sp-refresh-btn" id="refreshInsightsBtn" {{ $classSummaryEnabled ? '' : 'disabled' }}>
                        {{ $classSummaryEnabled ? 'Refresh insights' : 'Insights Disabled' }}
                    </button>
                </form>
            </div>
        </div>

    </div>

</div>{{-- /tab-quiz --}}

{{-- Assessments panel --}}
<div class="sp-tab-panel" id="tab-assessment">
    <p class="sp-sub" id="spAssessSubtitle">{{ $class->name ?? 'Class' }} - formal assessment results.</p>

    {{-- Assessment top row --}}
    <div class="sp-top-grid">
        <div class="sp-avg-card {{ $aAvgClass }}" id="assessAvgCard">
            <p class="sp-card-label">Class avg</p>
            <p class="sp-avg-val" id="assessClassAverageValue">{{ number_format($aClassAverage, 1) }}</p>
            <p class="sp-avg-unit">percent</p>
        </div>

        <div class="sp-card sp-chart-card" id="assessChartCard">
            <p class="sp-section-title">Pass / Fail</p>
            @if(($aPassCount + $aFailCount) > 0)
                <div class="sp-chart-wrap" id="assessChartWrap">
                    <canvas id="assessPassFailChart"></canvas>
                </div>
            @else
                <p class="sp-empty">No attempts yet.</p>
            @endif
        </div>

        <div class="sp-card sp-chart-card" id="assessQuestionChartCard">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                <p class="sp-section-title" style="margin: 0;">Question Breakdown</p>
                <div class="sp-chart-legend" style="display: flex; align-items: center; gap: 12px; font-size: 12px; font-weight: 500;">
                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #166534;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #1d9e75; display: inline-block;"></span> &ge;70% High</span>
                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #9a3412;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #ef9f27; display: inline-block;"></span> 40–69% Moderate</span>
                    <span style="display: inline-flex; align-items: center; gap: 5px; color: #991b1b;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #e24b4a; display: inline-block;"></span> &lt;40% Low</span>
                </div>
            </div>
            @if($aQuestionStats->isNotEmpty())
                <div class="sp-chart-wrap" id="assessQuestionChartWrap">
                    <canvas id="assessQuestionBreakdownChart"></canvas>
                </div>
            @else
                <p class="sp-empty" id="assessQuestionBreakdownEmpty">No answer data yet.</p>
            @endif
        </div>
    </div>

    {{-- Assessment bottom row --}}
    <div class="sp-bottom-grid">
        <div class="sp-card">
            <p class="sp-section-title">Student Rankings</p>
            @if($aTopStudents->isNotEmpty())
                <table class="sp-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th style="text-align:right">Avg Score</th>
                            <th style="text-align:right"></th>
                        </tr>
                    </thead>
                    <tbody id="assessTopStudentsBody">
                        @foreach($aTopStudents as $aIdx => $aStudent)
                            @php
                                $aAvg        = (float) $aStudent->average_score;
                                $aPillClass  = $aAvg >= 75 ? 'high' : ($aAvg >= 50 ? 'mid' : 'low');
                                $aMedalClass = $aIdx === 0 ? 'gold' : ($aIdx === 1 ? 'silver' : ($aIdx === 2 ? 'bronze' : ''));
                            @endphp
                            <tr>
                                <td>
                                    @if($aMedalClass)
                                        <span class="sp-rank-medal {{ $aMedalClass }}">{{ $aIdx + 1 }}</span>
                                    @else
                                        <span class="sp-rank-num">{{ $aIdx + 1 }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $aStudent->name }}
                                    @if(!empty($aStudent->program))
                                        <span style="font-size: 16px;color:#bbb;margin-left:4px;">{{ $aStudent->program }}</span>
                                    @endif
                                </td>
                                <td style="text-align:right">
                                    <span class="sp-score-pill {{ $aPillClass }}">
                                        {{ number_format($aAvg, 1) }}%
                                    </span>
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <div style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                                        <button class="sp-refresh-btn sp-analysis-btn" type="button"
                                                onclick="openItemAnalysis({{ $aStudent->id }}, '{{ addslashes($aStudent->name) }}', true)">
                                            <i class="fas fa-chart-bar"></i> Latest
                                        </button>
                                        <a href="{{ route('student.assessment.analysis', [$class, $aStudent->id]) }}"
                                           class="sp-refresh-btn sp-analysis-btn"
                                           style="text-decoration:none;">
                                            <i class="fas fa-robot"></i> AI Analysis
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                @if($aRemainingCount > 0)
                    <p style="font-size: 16px;color:#bbb;margin-top:10px;" id="assessRemainingStudentsLabel">
                        +{{ $aRemainingCount }} more students
                    </p>
                @endif
            @else
                <p class="sp-empty">No student data yet.</p>
            @endif
        </div>

        <div class="sp-card sp-ai-card">
            <p class="sp-section-title">AI Class Insights</p>
            <div id="assessAiRawText" style="display:none;">{{ $aAiSummary }}</div>
            <div class="sp-ai-body" id="assessAiSummaryBody"></div>
            <div class="sp-ai-footer">
                <form action="{{ route('student.performance.assessment.refresh', ['class' => $class->id]) }}"
                      method="POST" id="refreshAssessmentInsightsForm">
                    @csrf
                    <button type="submit" class="sp-refresh-btn" id="refreshAssessmentInsightsBtn" {{ $assessmentAnalysisEnabled ? '' : 'disabled' }}>
                        {{ $assessmentAnalysisEnabled ? 'Refresh insights' : 'Insights Disabled' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>{{-- /tab-assessment --}}

</div>

{{-- Item Analysis Dialog --}}
<dialog class="ia-dialog" id="itemAnalysisDialog">

    {{-- Head --}}
    <div class="ia-dialog-head">
        <div>
            <p class="ia-dialog-name" id="iaStudentName">Student Name</p>
            <div class="ia-dialog-meta" id="iaDialogMeta">
                <span class="sp-score-pill" id="iaScorePill">-</span>
                <span style="font-size: 17px;color:#999;" id="iaAttemptLabel">-</span>
            </div>
        </div>
        <button class="ia-dialog-close" onclick="document.getElementById('itemAnalysisDialog').close()">
            &#x2715;
        </button>
    </div>

    {{-- Attempt Limit / Grant Extra Attempt (formal assessments only) --}}
    <div id="iaAttemptLimitBox" style="display:none; padding: 14px 24px; border-bottom: 1px solid #f0f0f0; background: #fafafa;">
        <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
            <div style="font-size:16px; color:#555;">
                Attempts used: <strong id="iaAttemptsUsed">-</strong> / <span id="iaAttemptsAllowed">-</span>
                <span style="color:#bbb; font-size:14px;" id="iaAttemptsBreakdown"></span>
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" class="sp-refresh-btn" onclick="toggleMaxAttemptsForm()">
                    <i class="fas fa-sliders-h"></i> Edit Base Limit
                </button>
                <button type="button" class="sp-refresh-btn" id="iaGrantToggleBtn" onclick="toggleGrantForm()">
                    <i class="fas fa-plus"></i> Grant Extra Attempt
                </button>
            </div>
        </div>
        <div id="iaMaxAttemptsForm" style="display:none; margin-top:12px; gap:8px; align-items:center; flex-wrap:wrap;">
            <label style="font-size:15px; color:#666;">New base limit:</label>
            <input type="number" id="iaMaxAttemptsInput" min="1" max="20" value="1" style="width:70px; padding:6px 8px; border:1px solid #e4e4e4; border-radius:8px;">
            <button type="button" class="sp-refresh-btn" style="background:#0f0f0f; color:#fff; border-color:#0f0f0f;" onclick="submitMaxAttempts()">
                Confirm
            </button>
        </div>
        <div id="iaGrantForm" style="display:none; margin-top:12px; gap:8px; align-items:center; flex-wrap:wrap;">
            <label style="font-size:15px; color:#666;">Extra attempts:</label>
            <input type="number" id="iaGrantAmount" min="1" max="10" value="1" style="width:70px; padding:6px 8px; border:1px solid #e4e4e4; border-radius:8px;">
            <input type="text" id="iaGrantReason" placeholder="Reason (optional)" style="flex:1; min-width:160px; padding:6px 10px; border:1px solid #e4e4e4; border-radius:8px;">
            <button type="button" class="sp-refresh-btn" style="background:#0f0f0f; color:#fff; border-color:#0f0f0f;" onclick="submitGrant()">
                Confirm
            </button>
        </div>
    </div>

    {{-- Score summary strip --}}
    <div class="ia-score-strip" id="iaScoreStrip" style="display:none;">
        <div class="ia-score-cell">
            <p class="ia-score-cell-label">Score</p>
            <p class="ia-score-cell-val" id="iaStripScore">-</p>
        </div>
        <div class="ia-score-cell">
            <p class="ia-score-cell-label">Percentage</p>
            <p class="ia-score-cell-val" id="iaStripPct">-</p>
        </div>
        <div class="ia-score-cell">
            <p class="ia-score-cell-label">Correct</p>
            <p class="ia-score-cell-val green" id="iaStripCorrect">-</p>
        </div>
        <div class="ia-score-cell">
            <p class="ia-score-cell-label">Wrong</p>
            <p class="ia-score-cell-val red" id="iaStripWrong">-</p>
        </div>
    </div>

    {{-- Answer list --}}
    <div class="ia-answer-list" id="iaAnswerList">
        <div class="ia-loading">
            <i class="fas fa-circle-notch fa-spin"></i>
            Loading...
        </div>
    </div>

</dialog>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var passFailChart  = null;
var currentClassId = {{ $class->id }};
var currentPassCount = {{ $passCount }};
var currentFailCount = {{ $failCount }};
var assessPassFailChart = null;
var currentAssessPassCount = {{ $aPassCount }};
var currentAssessFailCount = {{ $aFailCount }};

/* Question breakdown chart state */
var questionChart = null;
var assessQuestionChart = null;
var currentQuestionStats = @json($currentQuestionStatsArr);
var currentAssessQuestionStats = @json($currentAssessQuestionStatsArr);

const itemAnalysisRouteTemplate = @json(route('student.performance.student-item-analysis', ['class' => '__CLASS__', 'student' => '__STUDENT__']));
const assessmentAnalysisRouteTemplate = @json(route('student.assessment.analysis', ['class' => '__CLASS__', 'student' => '__STUDENT__']));
const refreshInsightsRouteTemplate = @json(route('student.performance.refresh', ['class' => '__CLASS__']));
const refreshAssessmentInsightsRouteTemplate = @json(route('student.performance.assessment.refresh', ['class' => '__CLASS__']));

/* Pass/Fail Chart */
function buildChart() {
    var wrap   = document.getElementById('chartWrap');
    var canvas = document.getElementById('passFailChart');
    if (!wrap || !canvas) return;

    var dpr = window.devicePixelRatio || 1;
    var w   = wrap.offsetWidth;
    var h   = wrap.offsetHeight || 160;

    canvas.width  = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width  = w + 'px';
    canvas.style.height = h + 'px';

    if (passFailChart) { passFailChart.destroy(); passFailChart = null; }

    passFailChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: ['Passed', 'Failed'],
            datasets: [{
                data: [currentPassCount, currentFailCount],
                backgroundColor: ['#1d9e75', '#e24b4a'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: false,
            devicePixelRatio: dpr,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { family: 'DM Sans', size: 18, weight: '600' }, color: '#666', boxWidth: 14, padding: 22 }
                }
            }
        }
    });
}

function renderPassFailCard() {
    var chartCard = document.querySelector('.sp-chart-card');
    if (!chartCard) {
        return;
    }

    if ((currentPassCount + currentFailCount) > 0) {
        if (!document.getElementById('chartWrap')) {
            chartCard.innerHTML = '<p class="sp-section-title">Pass / Fail</p><div class="sp-chart-wrap" id="chartWrap"><canvas id="passFailChart"></canvas></div>';
        }
        buildChart();
        return;
    }

    if (passFailChart) {
        passFailChart.destroy();
        passFailChart = null;
    }

    chartCard.innerHTML = '<p class="sp-section-title">Pass / Fail</p><p class="sp-empty">No attempts yet.</p>';
}

renderPassFailCard();

if (currentQuestionStats.length) {
    buildQuestionChart(currentQuestionStats);
}

var resizeTimer;
window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
        if ((currentPassCount + currentFailCount) > 0) {
            buildChart();
        }
        if (currentQuestionStats.length) {
            buildQuestionChart(currentQuestionStats);
        }
        var assessPanel = document.getElementById('tab-assessment');
        if (assessPanel && assessPanel.classList.contains('active')) {
            if ((currentAssessPassCount + currentAssessFailCount) > 0) {
                buildAssessmentChart();
            }
            if (currentAssessQuestionStats.length) {
                buildAssessmentQuestionChart(currentAssessQuestionStats);
            }
        }
    }, 150);
});

/* AI markdown */
function parseAI(text) {
    var t = document.createElement('textarea');
    t.innerHTML = text || '';
    text = t.value;
    text = text.replace(/<[^>]+>/g, '');
    text = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n');
    text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    var lines = text.split('\n'), html = '', inList = false;
    lines.forEach(function (line) {
        line = line.trim();
        if (!line) { if (inList) { html += '</ul>'; inList = false; } return; }
        if (/^[-*•]\s+/.test(line) || /^\d+\.\s+/.test(line)) {
            if (!inList) { html += '<ul>'; inList = true; }
            html += '<li>' + line.replace(/^[-*•]\s+/, '').replace(/^\d+\.\s+/, '') + '</li>';
            return;
        }
        if (inList) { html += '</ul>'; inList = false; }
        if (/:\s*$/.test(line) && line.length < 60) { html += '<p><strong>' + line + '</strong></p>'; return; }
        html += '<p>' + line + '</p>';
    });
    if (inList) html += '</ul>';
    return html;
}

(function () {
    var rawEl  = document.getElementById('aiRawText');
    var bodyEl = document.getElementById('aiSummaryBody');
    if (rawEl && bodyEl) bodyEl.innerHTML = parseAI(rawEl.textContent || '');

    var assessRawEl  = document.getElementById('assessAiRawText');
    var assessBodyEl = document.getElementById('assessAiSummaryBody');
    if (assessRawEl && assessBodyEl) assessBodyEl.innerHTML = parseAI(assessRawEl.textContent || '');
})();

function scoreClass(v) { return v >= 75 ? 'high' : (v >= 50 ? 'mid' : 'low'); }
function diffColor(pct) { return pct >= 70 ? '#1d9e75' : (pct >= 40 ? '#ef9f27' : '#e24b4a'); }
function diffCls(pct)   { return pct >= 70 ? 'q-high' : (pct >= 40 ? 'q-mid' : 'q-low'); }

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function decodeAndStripHtml(value) {
    var txt = document.createElement('textarea');
    txt.innerHTML = String(value ?? '');
    return txt.value.replace(/<[^>]+>/g, '');
}

/* Question Breakdown - Pre-Assessment (Chart) */
function buildQuestionChart(stats) {
    var wrap = document.getElementById('questionChartWrap');
    var canvas = document.getElementById('questionBreakdownChart');
    if (!wrap || !canvas) return;

    var labels = stats.map(function (q, i) { return 'Q' + (i + 1); });
    var fullLabels = stats.map(function (q) { return q.question_text; });
    var values = stats.map(function (q) { return q.pct_correct; });
    var colors = values.map(function (v) { return diffColor(v); });
    var counts = stats.map(function (q) { return q.correct_count + '/' + q.total_answers; });

    if (questionChart) { questionChart.destroy(); questionChart = null; }

    questionChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '% Correct',
                data: values,
                backgroundColor: colors,
                borderRadius: 4,
                maxBarThickness: 36,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function (items) { return fullLabels[items[0].dataIndex] || ''; },
                        label: function (ctx) {
                            return ctx.parsed.y + '% correct (' + counts[ctx.dataIndex] + ')';
                        }
                    }
                }
            },
            scales: {
                x: { ticks: { maxRotation: 0 } },
                y: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } }
            }
        }
    });
}

function renderQuestionStats(items) {
    var stats = Array.isArray(items) ? items : [];
    currentQuestionStats = stats;

    var wrap = document.getElementById('questionChartWrap');
    var card = document.querySelector('.sp-top-grid .sp-card.sp-chart-card:last-child');

    var qbHeaderHtml = '<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;"><p class="sp-section-title" style="margin: 0;">Question Breakdown</p><div class="sp-chart-legend" style="display: flex; align-items: center; gap: 12px; font-size: 12px; font-weight: 500;"><span style="display: inline-flex; align-items: center; gap: 5px; color: #166534;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #1d9e75; display: inline-block;"></span> &ge;70% High</span><span style="display: inline-flex; align-items: center; gap: 5px; color: #9a3412;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #ef9f27; display: inline-block;"></span> 40–69% Moderate</span><span style="display: inline-flex; align-items: center; gap: 5px; color: #991b1b;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #e24b4a; display: inline-block;"></span> &lt;40% Low</span></div></div>';

    if (!stats.length) {
        if (questionChart) { questionChart.destroy(); questionChart = null; }
        if (card) card.innerHTML = qbHeaderHtml + '<p class="sp-empty" id="questionBreakdownEmpty">No answer data yet.</p>';
        return;
    }

    if (!wrap) {
        if (card) card.innerHTML = qbHeaderHtml + '<div class="sp-chart-wrap" id="questionChartWrap"><canvas id="questionBreakdownChart"></canvas></div>';
    }

    buildQuestionChart(stats);
}

function renderTopStudents(items, remainingCount) {
    var body = document.getElementById('topStudentsBody');
    var card = document.querySelector('.sp-bottom-grid .sp-card:first-child');
    if (!card) {
        return;
    }

    var students = Array.isArray(items) ? items : [];
    if (!students.length) {
        card.innerHTML = '<p class="sp-section-title">Student Rankings</p><p class="sp-empty">No student data yet.</p>';
        return;
    }

    if (!body) {
        card.innerHTML = `
            <p class="sp-section-title">Student Rankings</p>
            <table class="sp-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th style="text-align:right">Avg Score</th>
                        <th style="text-align:right"></th>
                    </tr>
                </thead>
                <tbody id="topStudentsBody"></tbody>
            </table>
            <p style="font-size: 16px;color:#bbb;margin-top:10px;display:none;" id="remainingStudentsLabel"></p>
        `;
        body = document.getElementById('topStudentsBody');
    }

    body.innerHTML = students.map(function (student, i) {
        var avg = Number(student.average_score || 0);
        var pillClass = scoreClass(avg);
        var medalClass = i === 0 ? 'gold' : (i === 1 ? 'silver' : (i === 2 ? 'bronze' : ''));
        var studentId = Number(student.id || 0);
        var safeName = String(student.name || 'Student').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        var rankBadge = medalClass
            ? `<span class="sp-rank-medal ${medalClass}">${i + 1}</span>`
            : `<span class="sp-rank-num">${i + 1}</span>`;
        var program = student.program
            ? `<span style="font-size: 16px;color:#bbb;margin-left:4px;">${escapeHtml(student.program)}</span>`
            : '';
        return `
            <tr>
                <td>${rankBadge}</td>
                <td>
                    ${escapeHtml(student.name || 'Unknown Student')}
                    ${program}
                </td>
                <td style="text-align:right">
                    <span class="sp-score-pill ${pillClass}">${avg.toFixed(1)}%</span>
                </td>
                <td style="text-align:right">
                    <button class="sp-refresh-btn sp-analysis-btn" type="button"
                        onclick="openItemAnalysis(${studentId}, '${safeName}')">
                        <i class="fas fa-chart-bar"></i> Analysis
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    var remainingLabel = document.getElementById('remainingStudentsLabel');
    var remaining = Number(remainingCount || 0);
    if (remainingLabel) {
        if (remaining > 0) {
            remainingLabel.style.display = '';
            remainingLabel.textContent = '+' + remaining + ' more students';
        } else {
            remainingLabel.style.display = 'none';
            remainingLabel.textContent = '';
        }
    }
}

function renderAiSummary(summaryText) {
    var rawEl = document.getElementById('aiRawText');
    var bodyEl = document.getElementById('aiSummaryBody');
    if (!rawEl || !bodyEl) {
        return;
    }

    rawEl.textContent = summaryText || 'No insights available yet.';
    bodyEl.innerHTML = parseAI(rawEl.textContent || '');
}

function setClassInsightsEnabled(enabled) {
    var refreshBtn = document.getElementById('refreshInsightsBtn');
    if (!refreshBtn) {
        return;
    }

    refreshBtn.disabled = !enabled;
    refreshBtn.textContent = enabled ? 'Refresh insights' : 'Insights Disabled';

    if (!enabled) {
        renderAiSummary('AI class insights are disabled for this class.');
    }
}

function updateRefreshFormAction(classId) {
    var form = document.getElementById('refreshInsightsForm');
    if (!form) {
        return;
    }

    form.action = refreshInsightsRouteTemplate.replace('__CLASS__', String(classId));
}

function renderAssessmentAiSummary(summaryText) {
    var rawEl = document.getElementById('assessAiRawText');
    var bodyEl = document.getElementById('assessAiSummaryBody');
    if (!rawEl || !bodyEl) {
        return;
    }

    rawEl.textContent = summaryText || 'No assessment insights available yet.';
    bodyEl.innerHTML = parseAI(rawEl.textContent || '');
}

function setAssessmentInsightsEnabled(enabled) {
    var refreshBtn = document.getElementById('refreshAssessmentInsightsBtn');
    if (!refreshBtn) {
        return;
    }

    refreshBtn.disabled = !enabled;
    refreshBtn.textContent = enabled ? 'Refresh insights' : 'Insights Disabled';

    if (!enabled) {
        renderAssessmentAiSummary('Assessment AI analysis is disabled for this class.');
    }
}

function updateAssessmentRefreshFormAction(classId) {
    var form = document.getElementById('refreshAssessmentInsightsForm');
    if (!form) {
        return;
    }

    form.action = refreshAssessmentInsightsRouteTemplate.replace('__CLASS__', String(classId));
}

/* Assessment Pass/Fail chart */
function buildAssessmentChart() {
    var wrap   = document.getElementById('assessChartWrap');
    var canvas = document.getElementById('assessPassFailChart');
    if (!wrap || !canvas) { return; }

    var dpr = window.devicePixelRatio || 1;
    var w   = wrap.offsetWidth;
    var h   = wrap.offsetHeight || 160;

    canvas.width  = w * dpr;
    canvas.height = h * dpr;
    canvas.style.width  = w + 'px';
    canvas.style.height = h + 'px';

    if (assessPassFailChart) { assessPassFailChart.destroy(); assessPassFailChart = null; }

    assessPassFailChart = new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: ['Passed', 'Failed'],
            datasets: [{
                data: [currentAssessPassCount, currentAssessFailCount],
                backgroundColor: ['#1d9e75', '#e24b4a'],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: false,
            devicePixelRatio: dpr,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { family: 'DM Sans', size: 18, weight: '600' }, color: '#666', boxWidth: 14, padding: 22 }
                }
            }
        }
    });
}

function renderAssessmentPassFailCard() {
    var chartCard = document.getElementById('assessChartCard');
    if (!chartCard) { return; }

    if ((currentAssessPassCount + currentAssessFailCount) > 0) {
        if (!document.getElementById('assessChartWrap')) {
            chartCard.innerHTML = '<p class="sp-section-title">Pass / Fail</p><div class="sp-chart-wrap" id="assessChartWrap"><canvas id="assessPassFailChart"></canvas></div>';
        }
        buildAssessmentChart();
        return;
    }

    if (assessPassFailChart) { assessPassFailChart.destroy(); assessPassFailChart = null; }
    chartCard.innerHTML = '<p class="sp-section-title">Pass / Fail</p><p class="sp-empty">No attempts yet.</p>';
}

/* Question Breakdown - Assessment (Chart) */
function buildAssessmentQuestionChart(stats) {
    var wrap = document.getElementById('assessQuestionChartWrap');
    var canvas = document.getElementById('assessQuestionBreakdownChart');
    if (!wrap || !canvas) return;

    var labels = stats.map(function (q, i) { return 'Q' + (i + 1); });
    var fullLabels = stats.map(function (q) { return q.question_text; });
    var values = stats.map(function (q) { return q.pct_correct; });
    var colors = values.map(function (v) { return diffColor(v); });
    var counts = stats.map(function (q) { return q.correct_count + '/' + q.total_answers; });

    if (assessQuestionChart) { assessQuestionChart.destroy(); assessQuestionChart = null; }

    assessQuestionChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '% Correct',
                data: values,
                backgroundColor: colors,
                borderRadius: 4,
                maxBarThickness: 36,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: function (items) { return fullLabels[items[0].dataIndex] || ''; },
                        label: function (ctx) {
                            return ctx.parsed.y + '% correct (' + counts[ctx.dataIndex] + ')';
                        }
                    }
                }
            },
            scales: {
                x: { ticks: { maxRotation: 0 } },
                y: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } }
            }
        }
    });
}

function renderAssessmentQuestionStats(items) {
    var stats = Array.isArray(items) ? items : [];
    currentAssessQuestionStats = stats;

    var wrap = document.getElementById('assessQuestionChartWrap');
    var card = document.getElementById('assessQuestionChartCard');

    var qbHeaderHtml = '<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;"><p class="sp-section-title" style="margin: 0;">Question Breakdown</p><div class="sp-chart-legend" style="display: flex; align-items: center; gap: 12px; font-size: 12px; font-weight: 500;"><span style="display: inline-flex; align-items: center; gap: 5px; color: #166534;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #1d9e75; display: inline-block;"></span> &ge;70% High</span><span style="display: inline-flex; align-items: center; gap: 5px; color: #9a3412;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #ef9f27; display: inline-block;"></span> 40–69% Moderate</span><span style="display: inline-flex; align-items: center; gap: 5px; color: #991b1b;"><span style="width: 9px; height: 9px; border-radius: 2px; background: #e24b4a; display: inline-block;"></span> &lt;40% Low</span></div></div>';

    if (!stats.length) {
        if (assessQuestionChart) { assessQuestionChart.destroy(); assessQuestionChart = null; }
        if (card) card.innerHTML = qbHeaderHtml + '<p class="sp-empty" id="assessQuestionBreakdownEmpty">No answer data yet.</p>';
        return;
    }

    if (!wrap) {
        if (card) card.innerHTML = qbHeaderHtml + '<div class="sp-chart-wrap" id="assessQuestionChartWrap"><canvas id="assessQuestionBreakdownChart"></canvas></div>';
    }

    buildAssessmentQuestionChart(stats);
}

function renderAssessmentTopStudents(items, remainingCount) {
    var body = document.getElementById('assessTopStudentsBody');
    var card = document.querySelector('#tab-assessment .sp-bottom-grid .sp-card');
    if (!card) { return; }

    var students = Array.isArray(items) ? items : [];
    if (!students.length) {
        card.innerHTML = '<p class="sp-section-title">Student Rankings</p><p class="sp-empty">No student data yet.</p>';
        return;
    }

    if (!body) {
        card.innerHTML = `
            <p class="sp-section-title">Student Rankings</p>
            <table class="sp-table">
                <thead>
                    <tr><th>#</th><th>Student</th><th style="text-align:right">Avg Score</th><th style="text-align:right"></th></tr>
                </thead>
                <tbody id="assessTopStudentsBody"></tbody>
            </table>
            <p style="font-size: 16px;color:#bbb;margin-top:10px;display:none;" id="assessRemainingStudentsLabel"></p>
        `;
        body = document.getElementById('assessTopStudentsBody');
    }

    body.innerHTML = students.map(function (student, i) {
        var avg = Number(student.average_score || 0);
        var pillClass = scoreClass(avg);
        var medalClass = i === 0 ? 'gold' : (i === 1 ? 'silver' : (i === 2 ? 'bronze' : ''));
        var studentId = Number(student.id || 0);
        var safeName = String(student.name || 'Student').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
        var rankBadge = medalClass
            ? `<span class="sp-rank-medal ${medalClass}">${i + 1}</span>`
            : `<span class="sp-rank-num">${i + 1}</span>`;
        var program = student.program
            ? `<span style="font-size: 16px;color:#bbb;margin-left:4px;">${escapeHtml(student.program)}</span>`
            : '';
        return `
            <tr>
                <td>${rankBadge}</td>
                <td>${escapeHtml(student.name || '')} ${program}</td>
                <td style="text-align:right">
                    <span class="sp-score-pill ${pillClass}">${avg.toFixed(1)}%</span>
                </td>
                <td style="text-align:right;white-space:nowrap;">
                    <div style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                        <button class="sp-refresh-btn sp-analysis-btn" type="button"
                            onclick="openItemAnalysis(${studentId}, '${safeName}', true)">
                            <i class="fas fa-chart-bar"></i> Latest
                        </button>
                        <a href="${assessmentAnalysisRouteTemplate.replace('__CLASS__', currentClassId).replace('__STUDENT__', studentId)}"
                           class="sp-refresh-btn sp-analysis-btn"
                           style="text-decoration:none;">
                            <i class="fas fa-robot"></i> AI Analysis
                        </a>
                    </div>
                </td>
            </tr>
        `;
    }).join('');

    var remainingLabel = document.getElementById('assessRemainingStudentsLabel');
    var remaining = Number(remainingCount || 0);
    if (remainingLabel) {
        if (remaining > 0) {
            remainingLabel.style.display = '';
            remainingLabel.textContent = '+' + remaining + ' more students';
        } else {
            remainingLabel.style.display = 'none';
        }
    }
}

/* Tab switching */
document.querySelectorAll('.sp-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var tab = this.dataset.tab;
        document.querySelectorAll('.sp-tab-btn').forEach(function (b) {
            b.classList.toggle('active', b.dataset.tab === tab);
        });
        document.querySelectorAll('.sp-tab-panel').forEach(function (p) {
            p.classList.toggle('active', p.id === 'tab-' + tab);
        });
        if (tab === 'quiz') {
            renderPassFailCard();
            if (currentQuestionStats.length) {
                buildQuestionChart(currentQuestionStats);
            }
        } else {
            renderAssessmentPassFailCard();
            if (currentAssessQuestionStats.length) {
                buildAssessmentQuestionChart(currentAssessQuestionStats);
            }
        }
    });
});

/* Class tabs */
document.querySelectorAll('.js-class-tab').forEach(function (tab) {
    tab.addEventListener('click', async function (e) {
        e.preventDefault();
        const classId = Number(this.dataset.classId);
        const href    = this.getAttribute('href');
        if (!classId || !href || classId === currentClassId) return;
        try {
            const res = await fetch(href, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error();
            const payload = await res.json();
            currentClassId   = classId;
            currentPassCount = Number(payload.passCount || 0);
            currentFailCount = Number(payload.failCount || 0);
            document.getElementById('spSubtitle').textContent = `${payload.class.name} - pre-assessment results and question breakdown.`;
            var avgV = Number(payload.classAverage || 0);
            var avgEl = document.getElementById('classAverageValue');
            if (avgEl) {
                avgEl.textContent = avgV.toFixed(1);
                var avgCard = avgEl.closest('.sp-avg-card');
                if (avgCard) { avgCard.classList.remove('high','mid','low'); avgCard.classList.add(scoreClass(avgV)); }
            }
            renderQuestionStats(payload.questionStats || []);
            renderTopStudents(payload.topStudents || [], payload.remainingCount || 0);
            var classSummaryEnabled = Object.prototype.hasOwnProperty.call(payload, 'classSummaryEnabled')
                ? Boolean(payload.classSummaryEnabled)
                : true;
            setClassInsightsEnabled(classSummaryEnabled);
            renderAiSummary(classSummaryEnabled
                ? (payload.aiSummary || 'No insights available yet.')
                : 'AI class insights are disabled for this class.');
            updateRefreshFormAction(classId);
            updateAssessmentRefreshFormAction(classId);
            document.querySelectorAll('.js-class-tab').forEach(t => t.classList.toggle('active', Number(t.dataset.classId) === classId));
            window.history.replaceState({}, '', href);
            renderPassFailCard();
            if (currentQuestionStats.length) {
                buildQuestionChart(currentQuestionStats);
            }
            if (payload.assessment) {
                currentAssessPassCount = Number(payload.assessment.passCount || 0);
                currentAssessFailCount = Number(payload.assessment.failCount || 0);
                var aAvgV = Number(payload.assessment.classAverage || 0);
                var aAvgEl = document.getElementById('assessClassAverageValue');
                if (aAvgEl) {
                    aAvgEl.textContent = aAvgV.toFixed(1);
                    var aAvgCard = document.getElementById('assessAvgCard');
                    if (aAvgCard) { aAvgCard.className = 'sp-avg-card ' + scoreClass(aAvgV); }
                }
                renderAssessmentQuestionStats(payload.assessment.questionStats || []);
                renderAssessmentTopStudents(payload.assessment.topStudents || [], payload.assessment.remainingCount || 0);
                var assessmentInsightsEnabled = Object.prototype.hasOwnProperty.call(payload.assessment, 'assessmentAnalysisEnabled')
                    ? Boolean(payload.assessment.assessmentAnalysisEnabled)
                    : true;
                setAssessmentInsightsEnabled(assessmentInsightsEnabled);
                renderAssessmentAiSummary(assessmentInsightsEnabled
                    ? (payload.assessment.aiSummary || 'No assessment insights available yet.')
                    : 'Assessment AI analysis is disabled for this class.');
                var aSubEl = document.getElementById('spAssessSubtitle');
                if (aSubEl) { aSubEl.textContent = payload.class.name + ' - formal assessment results.'; }
                var assessPanel = document.getElementById('tab-assessment');
                if (assessPanel && assessPanel.classList.contains('active')) {
                    renderAssessmentPassFailCard();
                    if (currentAssessQuestionStats.length) {
                        buildAssessmentQuestionChart(currentAssessQuestionStats);
                    }
                }
            }
        } catch (err) { window.location.href = href; }
    });
});

/* ITEM ANALYSIS DIALOG */
async function openItemAnalysis(studentId, studentName, isAssessment) {
    const dialog    = document.getElementById('itemAnalysisDialog');
    const nameEl    = document.getElementById('iaStudentName');
    const metaEl    = document.getElementById('iaDialogMeta');
    const scorePill = document.getElementById('iaScorePill');
    const attemptLbl= document.getElementById('iaAttemptLabel');
    const strip     = document.getElementById('iaScoreStrip');
    const list      = document.getElementById('iaAnswerList');

    // Reset
    nameEl.textContent    = studentName || 'Student';
    scorePill.textContent = '-';
    scorePill.className   = 'sp-score-pill';
    attemptLbl.textContent= 'Loading...';
    strip.style.display   = 'none';
    list.innerHTML        = `
        <div class="ia-loading">
            <i class="fas fa-circle-notch fa-spin"></i>
            Loading item analysis...
        </div>`;

    dialog.showModal();

    try {
        const itemAnalysisUrl = itemAnalysisRouteTemplate
            .replace('__CLASS__', String(currentClassId))
            .replace('__STUDENT__', String(studentId))
            + (isAssessment ? '?type=assessment' : '');

        const res = await fetch(itemAnalysisUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });

        if (!res.ok) {
            throw new Error(`Request failed (${res.status})`);
        }

        const data = await res.json();

        if (!data.attempt) {
            attemptLbl.textContent = 'No attempts found';
            strip.style.display    = 'none';
            list.innerHTML = `
                <div class="ia-no-data">
                    <i class="fas fa-inbox"></i>
                    <p>No quiz attempts yet</p>
                    <small>This student hasn't submitted any quizzes in this class.</small>
                </div>`;
            return;
        }

        const pct      = Number(data.attempt.percentage || 0);
        const answers  = data.answers || [];
        const correct  = answers.filter(a => Number(a.is_correct) === 1).length;
        const wrong    = answers.length - correct;
        const total    = answers.length;

// Update header
        scorePill.textContent = pct.toFixed(1) + '%';
        scorePill.className   = 'sp-score-pill ' + scoreClass(pct);
        attemptLbl.textContent= `Attempt #${data.attempt.id} - ${total} questions`;

        // Attempt limit / grant control (formal assessments only)
        renderAttemptLimitBox(data.attempt, studentId);

        // Score strip
        strip.style.display = '';
        document.getElementById('iaStripScore').textContent   = `${correct}/${total}`;
        document.getElementById('iaStripPct').textContent     = pct.toFixed(1) + '%';
        document.getElementById('iaStripCorrect').textContent = correct;
        document.getElementById('iaStripWrong').textContent   = wrong;

        // Answer list
        if (!answers.length) {
            list.innerHTML = `
                <div class="ia-no-data">
                    <i class="fas fa-question-circle"></i>
                    <p>No answers recorded</p>
                    <small>Answers may not have been saved for this attempt.</small>
                </div>`;
            return;
        }

        list.innerHTML = answers.map(function (a, idx) {
            const ok         = Number(a.is_correct) === 1;
            const selected   = a.selected_option ?? '-';
            const correct_op = a.correct_option   ?? '-';
            const qText      = String(a.question_text || '').replace(/<[^>]+>/g, '');

            const selectedChipClass = ok ? 'selected-correct' : 'selected-wrong';
            const selectedLabel     = ok ? 'Your answer (correct)' : 'Your answer (wrong)';

            const correctChip = !ok ? `
                <span class="ia-option-chip correct-answer">
                    ${correct_op}
                    <span class="ia-chip-label">Correct</span>
                </span>` : '';

            return `
                <div class="ia-answer-item ${ok ? 'correct' : 'wrong'}">
                    <div class="ia-answer-icon">
                        <i class="fas fa-${ok ? 'check' : 'times'}"></i>
                    </div>
                    <div class="ia-answer-content">
                        <p class="ia-answer-q-num">Question ${idx + 1}</p>
                        <p class="ia-answer-q-text">${qText}</p>
                        <div class="ia-answer-options">
                            <span class="ia-option-chip ${selectedChipClass}">
                                ${selected}
                                <span class="ia-chip-label">${selectedLabel}</span>
                            </span>
                            ${correctChip}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

    } catch (err) {
        var errorMessage = err && err.message ? err.message : 'Please try again.';
        attemptLbl.textContent = 'Failed to load';
        strip.style.display    = 'none';
        list.innerHTML = `
            <div class="ia-no-data">
                <i class="fas fa-exclamation-circle" style="color:#e24b4a;"></i>
                <p style="color:#a32d2d;">Failed to load item analysis</p>
                <small>${escapeHtml(errorMessage)}</small>
            </div>`;
    }
}
var currentGrantContext = { moduleId: null, studentId: null };

function renderAttemptLimitBox(attempt, studentId) {
    var box = document.getElementById('iaAttemptLimitBox');
    var grantForm = document.getElementById('iaGrantForm');

    if (!attempt.is_formal_assessment) {
        box.style.display = 'none';
        grantForm.style.display = 'none';
        return;
    }

    box.style.display = '';
    grantForm.style.display = 'none';
    document.getElementById('iaMaxAttemptsForm').style.display = 'none';
    document.getElementById('iaMaxAttemptsInput').value = attempt.base_max_attempts;

    document.getElementById('iaAttemptsUsed').textContent = attempt.attempts_used;
    document.getElementById('iaAttemptsAllowed').textContent = attempt.attempts_allowed;

    var breakdown = document.getElementById('iaAttemptsBreakdown');
    breakdown.textContent = attempt.extra_attempts_granted > 0
        ? `(base ${attempt.base_max_attempts} + ${attempt.extra_attempts_granted} granted)`
        : `(base ${attempt.base_max_attempts})`;

    currentGrantContext = { moduleId: attempt.module_id, studentId: studentId };
}

function toggleGrantForm() {
    var form = document.getElementById('iaGrantForm');
    form.style.display = form.style.display === 'none' ? 'flex' : 'none';
}

async function submitGrant() {
    if (!currentGrantContext.moduleId || !currentGrantContext.studentId) {
        alert('Walang napiling module/student. Subukan ulit buksan ang analysis.');
        return;
    }

    var amount = parseInt(document.getElementById('iaGrantAmount').value, 10) || 1;
    var reason = document.getElementById('iaGrantReason').value || '';

    var url = `/modules/${currentGrantContext.moduleId}/quiz/grant-attempt/${currentGrantContext.studentId}`;

    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ extra_attempts: amount, reason: reason }),
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert(data.message || 'Hindi ma-grant ang extra attempt.');
            return;
        }

        alert(data.message);
        document.getElementById('iaGrantForm').style.display = 'none';

        // I-refresh ang dialog para makita ang updated na attempts_allowed
        var isAssessment = true; // grant is only for formal assessments
        openItemAnalysis(currentGrantContext.studentId, document.getElementById('iaStudentName').textContent, isAssessment);
    } catch (err) {
        alert('May nangyaring error habang nagbibigay ng extra attempt.');
    }
}

function toggleMaxAttemptsForm() {
    var form = document.getElementById('iaMaxAttemptsForm');
    form.style.display = form.style.display === 'none' ? 'flex' : 'none';
}

async function submitMaxAttempts() {
    if (!currentGrantContext.moduleId) {
        alert('Walang napiling module. Subukan ulit buksan ang analysis.');
        return;
    }

    var value = parseInt(document.getElementById('iaMaxAttemptsInput').value, 10) || 1;
    var url = `/modules/${currentGrantContext.moduleId}/quiz/max-attempts`;

    try {
        const res = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ max_attempts: value }),
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
            alert(data.message || 'Hindi na-save ang max attempts.');
            return;
        }

        alert(data.message);
        document.getElementById('iaMaxAttemptsForm').style.display = 'none';

        openItemAnalysis(currentGrantContext.studentId, document.getElementById('iaStudentName').textContent, true);
    } catch (err) {
        alert('May error habang nagse-save ng max attempts.');
    }
}
</script>
@endsection