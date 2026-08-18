# Plan: Disable Anti-Cheat for Pre-Assessments

## Problem
Pre-assessments (non-formal assessments) currently have anti-cheat enabled, which restricts students with tab switching detection and fullscreen requirements. This should only apply to formal assessments.

## Current Implementation

### File: `resources/views/pages/student/assessment-take.blade.php`

1. **JavaScript Variable Declarations (lines 488-495)**
   ```javascript
   var csrfToken            = '{{ csrf_token() }}';
   var moduleId             = {{ $module->id }};
   var timeLimit            = {{ $module->time_limit ?? 0 }};
   var assessmentReturnUrl  = '{{ route("assessment") }}';
   ```

2. **launchAssessment() function (lines 571-593)**
   - Always calls `startAntiCheat()` on line 591
   - No check for formal vs pre-assessment

3. **Anti-Cheat Functions (lines 1117-1162)**
   - `startAntiCheat()` - adds event listeners for visibilitychange and blur
   - `stopAntiCheat()` - removes event listeners
   - `handleTab()` - counts violations, auto-submits after 3 violations

## Required Changes

### 1. Add JavaScript Variable for Assessment Type

Add after line 493 in `assessment-take.blade.php`:
```javascript
var isFormalAssessment   = {{ $module->is_formal_assessment ? 'true' : 'false' }};
```

### 2. Conditionally Start Anti-Cheat

Modify `launchAssessment()` function (around line 591):
```javascript
function launchAssessment() {
    // ... existing code ...
    
    startTimer(timeLimit);
    
    // Only enable anti-cheat for formal assessments
    if (isFormalAssessment) {
        startAntiCheat();
    }
}
```

### 3. Optional: Update handleTab() Safety Check

Modify `handleTab()` (around line 1147) to check if anti-cheat is active:
```javascript
function handleTab() {
    if (!isQuizActive) { return; }
    if (!isFormalAssessment) { return; } // Skip for pre-assessments
    
    // ... rest of violation handling ...
}
```

## Implementation Steps

1. [x] Add `isFormalAssessment` JavaScript variable
2. [x] Conditionally call `startAntiCheat()` only for formal assessments
3. [x] Add safety check in `handleTab()` 
4. [ ] Test both pre-assessment and formal assessment scenarios

## Testing Checklist

- [ ] Pre-assessment: Can switch tabs without penalties
- [ ] Pre-assessment: No fullscreen requirement
- [ ] Formal assessment: Anti-cheat still works normally
- [ ] Formal assessment: Tab switching triggers violations
- [ ] Formal assessment: 3 violations auto-submit the quiz
