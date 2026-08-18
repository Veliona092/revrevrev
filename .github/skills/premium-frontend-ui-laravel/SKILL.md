---
name: premium-frontend-ui-laravel
description: Laravel + Livewire adaptation of premium frontend UI patterns with performance and accessibility guardrails for Blade/Vite projects.
---

# Premium Frontend UI for Laravel + Livewire

Use this skill when a user asks for polished UI work in this repository: redesigns, landing sections, page facelifts, responsive improvements, theme refreshes, motion upgrades, and component visual quality.

## Scope and Triggers

Apply this skill when prompts include words like:
- redesign, premium UI, modernize layout, landing page, visual polish
- responsive fix, improve CSS, improve typography, add animation
- make this look better, improve UX, improve hero, improve navbar

Do not apply for backend-only tasks, migrations, or database operations.

## Laravel-Specific Implementation Rules

- Preserve existing Laravel structure and conventions.
- Prefer updating existing Blade views in resources/views before creating new structures.
- If interactivity is needed, prefer Livewire/Alpine patterns already used in the project.
- Keep CSS changes in existing asset paths (for example resources/css or public/assets/css) based on current project conventions.
- Use Vite-compatible asset inclusion patterns already present in views/layouts.
- Do not add new dependencies unless the user approves.

## Visual Direction

Avoid generic defaults. Choose one intentional direction per page/feature:
- Editorial: sharp contrast, oversized type, clear grid rhythm
- Organic: soft gradients, smooth curves, glass layers
- Technical: bold geometry, restrained glow accents, dense info hierarchy

Define tokens first:
- CSS variables for color, spacing, border radius, shadows
- fluid typography with clamp()
- motion durations/easings with reduced-motion fallbacks

## Motion and Interaction

- Prefer transform/opacity animations only.
- Add meaningful entrance choreography for hero and key blocks.
- Use pointer/hover-only effects behind media queries:
  @media (hover: hover) and (pointer: fine)
- Respect accessibility:
  @media (prefers-reduced-motion: reduce)

## Accessibility and Performance Guardrails

- Maintain readable contrast and visible focus states.
- Preserve semantic headings and landmark structure.
- Avoid layout-thrashing animations (top/left/width/height).
- Keep mobile first: test common breakpoints and long text overflow.

## Output Requirements

When implementing UI work:
1. State the chosen visual direction in one sentence.
2. Apply a consistent token system (colors/type/spacing/motion).
3. Deliver responsive behavior for mobile and desktop.
4. Include accessibility-safe interaction states.
5. Keep code maintainable and aligned with existing project style.
