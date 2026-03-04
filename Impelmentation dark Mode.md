Dark Mode Implementation Plan
Proposed Changes
We will implement a global dark mode toggle for the application using Tailwind CSS v4's class-based dark mode.

1. Tailwind CSS Configuration
[MODIFY] resources/css/app.css
Add the @custom-variant dark (&:is(.dark *)); directive to 
app.css
 to enable class-based dark mode in Tailwind v4.
Adjust some root CSS variables and add .dark scope for existing custom components to respond to dark mode, specifically .card, .table-modern, .btn-secondary, and .form-input which are currently hardcoded to white backgrounds.
2. Admin Layout Modification
[MODIFY] resources/views/layouts/admin.blade.php
Inject an inline <script> tag inside the <head> section to read localStorage and window.matchMedia('(prefers-color-scheme: dark)') immediately on load. This applies the .dark class to <html> to prevent FOUC (Flash of Unstyled Content).
Add the Dark Mode Toggle button in the header, placed next to the Server Time / Logout button.
Add dark:bg-slate-900 dark:text-slate-100 and similar dark variant classes to the <body>, <header>, and other structural wrapper elements in the layout.
Add event listeners at the bottom of the layout to handle toggle interactions, switch icons, and save preferences to localStorage.
Verification Plan
Manual Verification
Open the Admin Dashboard in the browser.
Verify that the initial theme matches the system preference (if no prior localStorage data exists).
Click the Dark Mode toggle (sun/moon icon) in the header.
Verify that the page instantly turns dark (or light) correctly.
Refresh the page to verify that the selected preference is remembered (no unstyled flashing).
Verify that UI elements (cards, tables, buttons, inputs) look readable and aesthetically pleasing in both dark and light modes.