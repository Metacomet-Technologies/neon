# 🧠 AI Context: Neon Project

This file provides detailed context about the **Neon** project for use by AI tools, IDE copilots, and contributors. Update this file when project architecture or conventions change.

---

## 🧱 Architecture Overview

- **Monorepo** using the default Laravel + Inertia.js structure
- **Frontend**: React 19, TypeScript, Tailwind CSS v4
- **Backend**: Laravel 12, PHP-FPM 8.4
- **Build Tools**: Vite, PostCSS
- **Runtime**: Node.js 22

---

## 📦 Frontend Conventions

- **Component Naming**: `PascalCase` (e.g. `UserProfile.tsx`)
- **Route Paths**: `kebab-case` (e.g. `/user-settings`)
- **API Endpoints**: `kebab-case`, no versioning yet (e.g. `/api/submit-command`)
- **Folder/File Naming**: Mixed by context

### Frontend Libraries

- **Tailwind CSS v4**
- **Catalyst (from Tailwind UI)**
- **Shadcn UI**
- **Inertia.js v2**

### Frontend Practices

- No global state management currently — planned for future
- TypeScript-only codebase
- Inertia pages rendered via Laravel controllers
- Assets built with Vite, but still served from server due to CDN caching issues with hash-based filenames

---

## ⚙️ Backend Conventions

- **Validation**: Laravel built-in validation only
- **Permissions**: Laravel `can` middleware
- **Auth**: Discord SSO for all protected features
- **Helpers**: Modularized under `app/Helpers/Discord/`
- **Enums**: Centralized in `app/Enums/`
- **Jobs & Commands**: Separated and idiomatic
- **Middleware**: Mostly applied via controller or route group, not explicitly in routes

---

## 🌐 Routing & Inertia

- No `Inertia::render()` in route files — rendering is **controller-based**
- Example Pages:
  - `Command/Index`, `Command/Create`, `Command/Edit`
  - `Profile`, `Servers/Index`, `Servers/Show`, `Unsubscribe`, `Markdown` (shared layout for ToS/Privacy)

---

## 🚀 Deployment & DevOps

- **Hosting**: DigitalOcean Droplets (PHP-FPM, Node)
- **CI/CD**: GitHub Actions
  - Runs Pest tests
  - Formats code
  - Triggers deploys via Laravel Envoyer
- **Planned Migration**: AWS EKS

---

## 🧪 Testing

- **Backend**: Pest
- **Frontend**: No current test suite
- **E2E Testing**: Planned with Playwright
- **Test Coverage**: Minimal (1–2 feature/unit tests currently)

---

## 📁 Folder Structure Highlights

```
/app
  ├── Console/Commands/
  ├── Enums/
  ├── Helpers/Discord/
  ├── Http/Controllers/
  └── Jobs/

/resources/js
  ├── Components/
  ├── Pages/
  ├── Layout/
  ├── hooks/
  ├── lib/
  └── types/
```

---

## 🧭 Known Challenges

- Asset cache invalidation due to Vite hash strategy not aligning with CDN
- No frontend or end-to-end test coverage yet
- Global state and frontend middleware patterns are TBD

---

## ✅ How to Use This File

- Use as a reference for AI-based code tools (e.g. GitHub Copilot, ChatGPT)
- Update when conventions, tooling, or architecture change
- Include in project root or `.github/` for easy discovery
