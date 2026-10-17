---
title: Theming & Dark Mode
description: Match MediaFlow's Blade components to your Laravel app with accent presets, light and dark mode, folder colours and CSS custom properties.
---

# Theming & Dark Mode

MediaFlow's components are styled with CSS custom properties, so they can follow your app's design. There are four ways to adjust them, from broadest to most specific.

## 1. Settings page

On `/mediaflow/settings`, under Appearance, choose an accent preset or a custom hex colour, a folder colour and the colour mode. The choice is stored in the database and applies everywhere.

Accent presets: `indigo`, `emerald`, `violet`, `sky`, `amber`, `rose`, `slate`, `custom` and `system` (inherit from your app).

Colour modes: `light`, `dark` and `system` (follow the operating system).

## 2. Component options

Override the theme for a single component:

```blade
<x-mediaflow-input name="cover_id" theme="slate" mode="light" folder-color="#2563eb" />

<x-mediaflow::manager theme="emerald" color-mode="dark" folder-color="#10b981" />
```

The input field uses `mode`; the manager uses `color-mode`.

## 3. Your app's dark mode

The components switch to dark mode automatically when your page uses any of these:

- `class="dark"` on `<html>` or `<body>` (Tailwind)
- `data-theme="dark"` (daisyUI and similar switchers)
- `data-mf-mode="dark"` or the `mf-mode-dark` class

`data-mf-mode="light"`, `data-theme="light"`, the `light` class or `mf-mode-light` force light mode. With none of these, the components follow `prefers-color-scheme`.

## 4. CSS custom properties

Override MediaFlow's tokens in your own stylesheet:

```css
:root {
  --mf-primary: #2563eb;
  --mf-primary-hover: #1d4ed8;
  --mf-primary-light: rgba(37, 99, 235, 0.12);

  --mf-folder-color: #64748b;
  --mf-folder-bg: rgba(100, 116, 139, 0.12);

  --mf-radius-md: 8px;
}

html.dark {
  --mf-bg-main: #111318;
  --mf-bg-card: #181b21;
  --mf-border: #2a2f37;
  --mf-text-main: #e8eaed;
}
```

### Inherited tokens

If your app already defines common design tokens, MediaFlow picks them up without any overrides. For example `--primary`, `--background`, `--card`, `--border`, `--foreground`, `--muted-foreground` and `--radius` (as used by shadcn/ui-style themes) map to the matching `--mf-*` variables.

| MediaFlow token | Falls back to |
| --- | --- |
| `--mf-primary` | `--primary`, `--theme-primary`, `--color-primary` |
| `--mf-bg-main` | `--background`, `--bg-main`, `--color-background` |
| `--mf-bg-card` | `--card`, `--surface`, `--color-surface` |
| `--mf-border` | `--border`, `--color-border` |
| `--mf-text-main` | `--foreground`, `--text-main`, `--color-text` |
| `--mf-text-muted` | `--muted-foreground`, `--text-muted`, `--color-muted` |
| `--mf-radius-md` | `--radius`, `--radius-md` |
| `--mf-folder-color` | `--folder-color` |
| `--mf-font-sans` | `--font-sans`, `--font-family` |

Theme colours saved on the Settings page are validated as hex values both when saved and when rendered.
