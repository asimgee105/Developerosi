# DevOS User Guide: Workspace & Coding Handbook

Welcome to your unified developer operating system. DevOS integrates code repositories, task boards, and AI assistance into a single dashboard.

---

## 1. Quick Onboarding & Workspaces

1. **Sign Up**: Register at `/register`. Upon registration, DevOS automatically provisions a **Default Personal Workspace** for you and marks your account as the workspace Owner.
2. **Access Dashboard**: Visit `/dashboard` to view your workspace.
3. **Workspace Swapping**: Open the **Command Palette** (`Ctrl/Cmd + K`) and type a workspace name to navigate between teams.

---

## 2. Managing Two-Factor Authentication (2FA)

Protect your credentials with Google Authenticator or any TOTP app:

1. Navigate to the **Security & Telemetry** tab from the top header on your dashboard.
2. Under the **Two-Factor Verification** section, click **Enable 2FA**.
3. Scan the generated QR code using your authenticator app, or copy the raw secret key.
4. Input the 6-digit verification code from your app into the input field and click **Verify & Activate**.
5. **CRITICAL**: Copy and store the displayed **Recovery Codes** in a safe place. They are the only way to recover your account if you lose your device.

---

## 3. Active Devices & Logins (Concurrent Limit)

Your DevOS account enforces a **maximum of 2 concurrent active device logins**:

* **Limit Warning**: If you attempt to log in on a 3rd device, DevOS will block the login and display a red alert showing your active browser sessions (IP and last active time).
* **Eviction**: Click **"Terminate Oldest & Login"** to automatically logout your oldest active session and authenticate your current device.
* **Manual Revocation**: From the **Security & Telemetry** tab, you can inspect your active sessions list and click **"Log Out Device"** to instantly invalidate any specific browser session.

---

## 4. Customizing your Grid Dashboard

You can customize the layout of your mission control widgets:

1. Click **Customize Grid** (`⚙️`) in the dashboard header.
2. Use the arrow controls (`←`, `→`) on widget headers to shift their position.
3. Use size controls (`+`, `−`) to expand or shrink widget widths (built on a responsive 12-column grid).
4. Click **Save Layout** (`💾`) to persist the grid configurations directly back to the database.

---

## 5. Global Command Palette (Cmd + K)

Quickly navigate or trigger actions without clicking:

* **Trigger**: Press `Ctrl + K` (Windows/Linux) or `Cmd + K` (macOS).
* **Fuzzy Navigation**: Type "Go to Dashboard" or "Go to Sign In" and press `Enter` to jump pages instantly.
* **Quick Theme Toggle**: Type "Toggle Dark / Light Mode" (or press shortcut `Cmd + T`) to swap theme shades.
* **AI Copilot Focus**: Type "Ask DevOS AI..." and press `Enter` to instantly focus your cursor inside the AI Chat prompt.

---

## 6. Subscriptions, Payments & Connected Payouts (Chapter 7)

DevOS supports multi-tenant subscriptions and connected payouts for customer invoicing:

1. **Subscribing to premium tiers**: Subscriptions checkout are managed via Stripe. Requesting a checkout session redirects you to Stripe Checkout.
2. **Connected Express Payouts**: Workspace owners can onboard their connected Express payout accounts. Once completed, payouts are enabled, allowing you to charge client invoices and receive automated payouts directly to your bank account.
3. **Generating Client Invoices**: Convert unbilled developer time-logs into professional invoices by submitting client details (Name, Email, hours logged, and rate). DevOS auto-compiles the invoice and saves it in a `draft` state.

---

## 7. CRM, Lead Pipeline & Custom Fields (Chapter 8)

DevOS includes a full sales CRM to manage prospective clients, developer recruitment leads, and contract deals:

1. **Prospects Ledger**: Access your contacts list to track company alignments, emails, phone numbers, and lead acquisition sources.
2. **Visual Deals Pipeline**: Track ongoing contract negotiations. Drag-and-drop deals across sales stages (`lead`, `contacted`, `qualified`, `proposal`, `won`, `lost`). DevOS automatically configures deal close probabilities (e.g., transitioning to `proposal` sets probability to 70%, `won` sets to 100%).
3. **Custom CRM Fields**: Workspace admins can define custom attributes (e.g., "preferred_tech_stack") and map values to contacts dynamically.
4. **Interaction Trails**: Audit your communications. Log phone calls, meeting notes, or email updates to a contact's timeline.

---

## 8. eBPF Telemetry & Server Monitoring (Chapter 9)

DevOS allows you to register server nodes and monitor their health using low-level kernel analytics:

1. **Server Clusters Register**: Add virtual server nodes under the workspace, entering IP address, CPU cores, RAM size, and OS configuration details.
2. **Real-time Load Analytics**: Click on a server node to inspect 12-hour resource usage graphs (CPU, RAM, and Disk capacity metrics).
3. **eBPF Socket Network logs**: Trace network traffic packet activity. View source/destination IPs, ports, duration, bytes sent, and protocols.
4. **SSH Audit Trail Logs**: Track access attempts. Audits list active connection dates, connection IPs, usernames, and validation status (successful vs. failed bruteforce attempts).

---

## 9. AI Autonomous Coding Agent Subsystem (Chapter 10)

DevOS features a stateful autonomous agentic workflow to automate coding refactoring tasks:

1. **Triggering Coding Runs**: Submit coding tasks (e.g. "Fix the getCurrentOtp method in AuthTest"). DevOS will queue the job.
2. **Monitoring Step Progression**: Trace steps in real-time on your dashboard:
   - **Scan & Plan**: Audits dependencies and code context imports.
   - **Apply Modification**: Executes precise line edits and refactors.
   - **Format & Lint Check**: Confirms file syntax and styling validations.
   - **Execute Tests**: Runs tests suite to verify compiler safety and prevent regression breaks.
3. **Workspace Context Scanner**: Parse codebase tree structures to check import relations, file sizes, and traversals.

---

## 10. SRE, Autonomous QA & Web3 Escrow (Chapter 11)

DevOS supports automated incident rollbacks, autonomous Playwright test executions, and decentralized USDC escrow bounty payments:

1. **AI-Driven SRE Alerts & Rollbacks**: When telemetry spikes occur, the AI automatically proposes a GitOps rollback to the last stable commit. You can override or approve the rollback. A 30-minute cooldown prevents deploy-rollback loop cascades.
2. **Autonomous Playwright QA Previews**: On Pull Requests, the AI spins up warm browser pool contexts, writes Playwright E2E testing scripts, and asserts flow completions. DOM visual regression engines verify button and layout shifts to prevent UI breaks.
3. **Web3 USDC Escrow Bounties**: Lock funds for issue tickets. Clients lock USDC in smart contracts, developers link completed PRs, and the secure Oracle KMS relayer signs cryptographically verified payments releasing directly to developer MetaMask wallets on approvals.
