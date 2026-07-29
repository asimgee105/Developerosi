# DevOS Administrator & Operations Handbook

Welcome to the DevOS administrative control reference. This handbook details how to configure multi-tenant limits, track LLM costs, check security alerts, and manage git webhook integrations.

---

## 1. Multi-Tenant Infrastructure & Isolation

DevOS runs on a single-database multi-tenant architecture using organization UUID scopes:

* **Scope Enforcement**: Every database table containing workspace specific data (like `dashboards`, `tasks`, and `dora_metrics_daily`) links to `workspace_id` referencing the `organizations` table.
* **Security Checks**: Ensure that controllers enforce active membership checks on endpoints. For instance:
  ```php
  $isMember = DB::table('organization_members')
      ->where('organization_id', $workspaceId)
      ->where('user_id', $user->id)
      ->exists();
  ```

---

## 2. LLM Gateway & Billing Telemetry

The AI Gateway meters all completions requests statefully to prevent billing resource exhaustion:

1. **Telemetry Logs**: All prompts routed through `/api/v1/ai/chat/completions` are logged inside the `llm_request_logs` table.
2. **Pricing Audits**: Each log entry records:
   - `model_used`: GPT-4o, Gemini Flash, etc.
   - `prompt_tokens` & `completion_tokens`.
   - `cost_usd`: Calculated based on dynamic input/output pricing parameters.
3. **Workspace Cost Quota Enforcement**: Run periodic cron triggers or query scopes to block completions once a workspace hits its limit (e.g. $5.00 limit for Free Tier). Query formula:
   ```sql
   SELECT SUM(cost_usd) FROM llm_request_logs WHERE workspace_id = 'org-uuid';
   ```

---

## 3. Configuring Repository Webhooks

VCS webhook integrations stream real-time events to calculate DORA metrics and link task statuses:

* **Endpoint**: `POST https://yourdomain.com/api/v1/vcs/webhooks/{provider}`
* **Supported Providers**: `github`, `gitlab`
* **Secrets Configuration**: Set the webhook payload secret in your repository settings and match it with `webhook_secret` in the `git_repositories` table.
* **Auto-Linker Matching**: Webhooks scan PR branch names and commit messages using the regex `/([A-Z]+-\d+)/i` (e.g., matching task code `DEV-102`). This updates the task status to `inreview` on PR creation, and `done` on PR merges, recording links in the `task_git_links` table.

---

## 4. Concurrent Sessions Limitation

To enforce account integrity, a maximum limit of **2 concurrent stateful sessions** is enforced:

* **Configuration**: Session life limits are governed by the Laravel session driver. Ensure `SESSION_DRIVER=database` is configured in your `.env`.
* **Telemetry Queries**: To check a user's active session count:
  ```php
  $activeSessions = DB::table('sessions')
      ->where('user_id', $userId)
      ->where('last_activity', '>=', time() - (config('session.lifetime') * 60))
      ->get();
  ```
* **Eviction Policies**: When `force => true` is submitted, DevOS deletes the oldest active record from the `sessions` table (ordered by `last_activity` ascending) to free up slot capacity.

---

## 5. Stripe Subscriptions & Connect Settings (Chapter 7)

DevOS administrative controls for billing and client express connected payouts:

* **Stripe Credentials Config**: Set your Stripe API credentials in the backend `.env` files:
  ```env
  STRIPE_KEY=sk_test_...
  STRIPE_SECRET=whsec_...
  ```
  If unconfigured, the billing subsystem runs in fallback simulation mode (providing mock checkout session redirects and connected accounts).
* **Subscriptions Table**: Workspace membership states are registered in the `billing_subscriptions` table.
* **Connected Accounts Table**: Express connected accounts details are managed in the `workspace_stripe_connects` table.
* **Invoicing Ledger Table**: Client invoices are stored in the `invoices` table. To compute pending client billing limits:
  ```sql
  SELECT SUM(amount) FROM invoices WHERE workspace_id = 'org-uuid' AND status = 'sent';
  ```

---

## 6. CRM pipeline & custom schemas (Chapter 8)

DevOS administrative controls for prospective leads and pipelines deal tracking:

* **Prospects Database Table**: Leads are stored in the `crm_contacts` table.
* **Deals Pipeline Database Table**: Negotiations are tracked in the `crm_deals` table. Stage limits and deal close probability mappings are handled inside the backend `CrmController`.
* **Dynamic Custom Fields definitions**: Custom attribute metadata schemas are declared in the `crm_custom_fields` table, linking values statefully to the `crm_custom_field_values` table. To query all dynamic metadata values for a contact:
  ```sql
  SELECT cf.field_name, cfv.field_value 
  FROM crm_custom_field_values cfv 
  JOIN crm_custom_fields cf ON cf.id = cfv.custom_field_id 
  WHERE cfv.entity_id = 'contact-uuid';
  ```
* **Interactions audit logging**: Logs of customer communication types (`email`, `call`, `note`, `meeting`) are stored inside the `crm_interactions` table.

---

## 7. eBPF Telemetry & Cluster Analytics (Chapter 9)

DevOS administrative controls for server resource analytics and SSH security tracking:

* **Servers Database Table**: Cluster nodes are declared in the `servers` table.
* **Hourly Resource Metrics Table**: System CPU, RAM, and Disk capacity loads are recorded in the `server_metrics_hourly` table. Load profiles simulation parameters are defined in the backend `TelemetryController`.
* **eBPF Socket Network logs Table**: Network traces are logged in the `ebpf_network_logs` table. To fetch all packet records where network duration exceeds 10ms (low-level bottleneck diagnostics):
  ```sql
  SELECT source_ip, destination_ip, duration_ms, protocol 
  FROM ebpf_network_logs 
  WHERE server_id = 'server-uuid' AND duration_ms > 10.0000;
  ```
* **SSH Access audit logs Table**: User logins are tracked in the `ssh_access_audits` table. To count failed external SSH connection attempts (intrusion alerts checks):
  ```sql
  SELECT COUNT(*), ip_address 
  FROM ssh_access_audits 
  WHERE server_id = 'server-uuid' AND status = 'failed' 
  GROUP BY ip_address;
  ```

---

## 8. AI Agent runs queue & step Traces (Chapter 10)

DevOS administrative controls for autonomous coding jobs and workflows telemetry tracking:

* **Agent Runs Database Table**: Main job runs are registered in the `agent_runs` table.
* **Agent Step Traces Database Table**: Sequential logs (planning, coding, lint, test) are tracked in the `agent_run_steps` table. Step trace simulation metrics are handled in the backend `AgentController`.
* **AST Code Modifier modifications check**: The `/api/v1/agent/action/modify-code` endpoint applies precise code updates. To query failed agent steps logs for diagnostic audits:
  ```sql
  SELECT step_name, step_type, output 
  FROM agent_run_steps 
  WHERE agent_run_id = 'run-uuid' AND status = 'failed';
  ```

---

## 9. SRE, QA & Cryptographic Oracles (Chapter 11)

DevOS administrative controls for incident responses, Playwright QA tracking, and Web3 oracle key configs:

* **SRE Incidents event log Table**: Alerts are declared in the `incidents` table, linking actions to the `incident_logs` table.
* **QA Runs telemetry Table**: Playwright test outcomes are logged in the `qa_runs` table.
* **Web3 Bounties Escrows Table**: USDC payment locks are tracked in the `bounty_escrows` table.
* **KMS HSM Cryptographic Key configuration**: Configure Oracle HSM signing key hashes in your environment variables:
  ```env
  ORACLE_KMS_SIGNING_KEY=aws_kms_hsm_key_default_signature_hash
  ```
  To audit pending dispute escrows in the system:
  ```sql
  SELECT smart_contract_address, client_wallet, amount_usdc 
  FROM bounty_escrows 
  WHERE status = 'Disputed';
  ```
