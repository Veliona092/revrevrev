# Plan: Update Reset Confirmation Dialog CSS

## Problem
The reset quiz attempt confirmation dialog uses an outdated design with simple "OK" and "Cancel" buttons. It needs to be updated to match the modern UI design used throughout the application.

## Current State
- Uses browser default `confirm()` dialog or basic styling
- Simple OK/Cancel button layout
- No modern styling (shadows, rounded corners, color scheme)

## Target Design Pattern
Based on the application design system:
- Rounded corners (12px border-radius)
- Soft shadows (0 10px 40px rgba(0,0,0,0.15))
- Primary action button: dark background (#111), white text
- Secondary action button: light background (#f3f4f6), dark text
- Proper spacing and typography
- Smooth animations

## Files to Modify

1. **Primary file**: `resources/views/pages/student/modules.blade.php`
   - Contains the reset confirmation logic (around `resetMyAttempt` function)
   - Has `confirm()` call that triggers the dialog

2. **CSS Location**: Within the same blade file in `<style>` section or inline

## Implementation Steps

1. [x] Locate the `resetMyAttempt` function and confirm dialog
2. [x] Create custom modal HTML structure
3. [x] Add modern CSS styling for the modal
4. [x] Replace `confirm()` with custom modal trigger
5. [ ] Test the new design

## CSS Specifications

### Modal Container
- Background: white (#fff)
- Border-radius: 12px
- Padding: 24px
- Box-shadow: 0 10px 40px rgba(0,0,0,0.15)
- Max-width: 400px

### Title
- Font-size: 18px
- Font-weight: 600
- Color: #111
- Margin-bottom: 12px

### Message
- Font-size: 14px
- Color: #6b7280
- Margin-bottom: 24px

### Button Layout
- Display: flex
- Gap: 12px
- Justify-content: flex-end

### Primary Button (Reset)
- Background: #111
- Color: white
- Border-radius: 10px
- Padding: 10px 20px
- Font-weight: 500

### Secondary Button (Cancel)
- Background: #f3f4f6
- Color: #374151
- Border-radius: 10px
- Padding: 10px 20px
- Font-weight: 500

## Testing Checklist
- [ ] Dialog appears with modern styling
- [ ] Reset button works correctly
- [ ] Cancel button closes dialog
- [ ] Responsive on mobile
- [ ] Animation/transition smooth
