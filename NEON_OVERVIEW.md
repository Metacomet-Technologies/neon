# Neon Bot Project Overview

**Project Name:** Neon (Neon Bot)
**Website:** [https://neon-bot.com](https://neon-bot.com)
**Type:** Private SaaS
**Audience:** Power Discord users, with tailored campaigns for Twitch, YouTube Gaming, and TikTok communities
**Tech Stack:**
- **Backend:** Laravel 12
- **Frontend:** Inertia + React 19 + TypeScript
- **Billing:** Stripe (lifetime + monthly support)
- **Architecture:** Multi-region containerized deployment with leader election, high availability, and dynamic guild distribution

---

## 🚀 High-Level Vision

Neon is a next-generation Discord bot platform tailored for power users and creators who want complete control and flexibility. Unlike rigid, one-size-fits-all bots, Neon empowers server admins to create and customize their own slash and text commands with intelligent behavior.

Whether it's utility automation, server moderation, live stream engagement, or AI-powered response generation, Neon transforms Discord into an extensible, programmable workspace.

---

## 🔧 MVP Feature Set

### 1. **Custom Command Builder**
- Define commands using a mini templating language: `{user}`, `{args}`, `{channel}`, etc.
- Commands can be slash-based or prefixed text commands.
- Easily add/edit/delete via dashboard.

### 2. **Discord OAuth2 + Guild Selector**
- OAuth2 login pulls all managed guilds.
- Guilds labeled as:
  - ✅ "Configured" (bot installed)
  - ➕ "Not Configured" (bot not invited)
- Intuitive onboarding: Invite bot → Configure guild → Create commands

### 3. **Role-Based Configuration Access**
- Only Discord owners/admins can manage bot settings.
- Ensures security and reduces user confusion.

### 4. **Interactive Dashboard**
- Inertia.js + React for seamless navigation.
- Sidebar by guild → Feature tabs → Command management, integrations, billing.

### 5. **Scalable Bot Infrastructure**
- Multi-region deployment across US, EU, Asia-Pacific
- Leader election governs event coordination.
- Redis-backed guild assignment ensures high availability and fault tolerance.

### 6. **Stripe Billing Integration**
- Support for lifetime purchases and monthly subscriptions.
- Payment in USD only (future plan: read-only currency conversion).
- Billing portal via Stripe hosted pages.

### 7. **Privacy & Compliance**
- Sign in via Discord only, no passwords stored.
- Postmark-managed unsubscribe logic and suppression list.
- Cookie consent + GDPR/CCPA compliance notices.
- Delete-my-data route to satisfy PII erasure requests.

---

## 🧠 Future Capabilities

- Natural language AI interaction with the bot ("Command Copilot")
- Automation template library for common tasks
- Streaming platform integrations (Twitch, YouTube Gaming, TikTok Live)
- Notification preference management (email & Discord)
- CMS-powered changelog/blog feed + RSS support

---

## 📋 Feature Tasks & AI Prompts

### 🎯 Task 1: Discord OAuth2 Flow + Guild Selector

> Build a Laravel + Inertia + React component that uses Discord OAuth2 to authenticate a user, fetch their managed guilds (via Discord API), and show a list. Each guild should be marked "Configured" or "Not Configured" based on whether the Neon bot is present. Add CTA buttons: "Configure" or "Invite".

---

### 🎯 Task 2: Command Authoring Interface

> Create a React component that allows users to define a custom command name, description, and response template using tokens like `{user}`, `{args}`, etc. Save the configuration via Axios to a Laravel backend. List existing commands and allow editing or deleting them.

---

### 🎯 Task 3: DiscordPHP Event Listener

> Using DiscordPHP and Laravel, build a command listener that maps incoming interactions to saved commands per guild. Evaluate templates like "Hello {user}" and respond via Discord. Use Laravel's queue system for async command processing.

---

### 🎯 Task 4: Stripe Billing Setup

> Add Laravel Cashier (Stripe) support to allow users to subscribe to Neon. Provide options for monthly and one-time lifetime subscriptions. Expose a billing portal via Stripe where users can update payment methods or cancel. Store subscription status in the user model.

---

### 🎯 Task 5: Multi-Instance Bot Deployment

> Design a Laravel-compatible process for launching multiple DiscordPHP bot instances in different regions (e.g., US, EU, Asia). Use Redis to store which instance owns which guilds. On startup, bots should claim unassigned guilds. If a bot goes offline, reassign its guilds after a timeout.

---

### 🎯 Task 6: AI Command Dispatcher

> Build a message dispatcher in Laravel that routes certain commands to an AI response service (e.g., OpenAI). Parse messages like "/ask What’s the weather?" and send them to a queue. Generate the response using an LLM API and reply back to the Discord channel.

---

### 🎯 Task 7: CMS-Driven Blog Feed

> Use Laravel Nova to create a CMS panel for blog posts and announcements. Posts should include title, slug, markdown content, and published toggle. Display on `/announcements` via public route. Include RSS feed support.

---
