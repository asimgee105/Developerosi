<?php

use App\Http\Controllers\AgentController;
use App\Http\Controllers\AiGatewayController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CrmController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TelemetryController;
use App\Http\Controllers\VcsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public webhook ingestion route
Route::post('/v1/vcs/webhooks/{provider}', [VcsController::class, 'handleWebhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/v1/workspaces/{workspace_id}/dashboards/active', [DashboardController::class, 'getActiveDashboard']);
    Route::put('/v1/dashboards/{dashboard_id}/layout', [DashboardController::class, 'updateLayout']);

    // Unified AI Engine Gateway & Multi-Agent Telemetry
    Route::post('/v1/ai/chat/completions', [AiGatewayController::class, 'chatCompletions']);
    Route::post('/v1/ai/agents/task', [AiGatewayController::class, 'triggerAgentTask']);
    Route::get('/v1/ai/agents/task/{task_id}', [AiGatewayController::class, 'getAgentTaskStatus']);
    Route::post('/v1/ai/context/sync', [AiGatewayController::class, 'syncContext']);

    // VCS DORA Analytics & Code Audits
    Route::get('/v1/workspaces/{workspace_id}/dora', [VcsController::class, 'getDoraMetrics']);
    Route::get('/v1/repositories/{repository_id}/health', [VcsController::class, 'getRepositoryHealth']);

    // Projects, Sprints, & Kanban Task Board
    Route::get('/v1/workspaces/{workspace_id}/projects', [ProjectController::class, 'index']);
    Route::post('/v1/projects', [ProjectController::class, 'store']);
    Route::post('/v1/projects/{project_id}/sprints', [ProjectController::class, 'storeSprint']);
    Route::get('/v1/sprints/{sprint_id}/tasks', [TaskController::class, 'indexBySprint']);
    Route::post('/v1/tasks', [TaskController::class, 'store']);
    Route::put('/v1/tasks/{task_id}/status', [TaskController::class, 'updateStatus']);
    Route::put('/v1/tasks/{task_id}/position', [TaskController::class, 'updatePosition']);

    // Stripe Billing & Invoicing Client portals
    Route::post('/v1/billing/subscriptions/checkout', [BillingController::class, 'createCheckoutSession']);
    Route::post('/v1/billing/connect/onboard', [BillingController::class, 'connectOnboard']);
    Route::get('/v1/workspaces/{workspace_id}/invoices', [BillingController::class, 'getWorkspaceInvoices']);
    Route::post('/v1/invoices/generate-from-timelogs', [BillingController::class, 'generateInvoiceFromTimelogs']);
    Route::post('/v1/invoices/{invoice_id}/pay', [BillingController::class, 'payInvoice']);

    // CRM Leads Pipeline & Custom Fields
    Route::get('/v1/workspaces/{workspace_id}/crm/contacts', [CrmController::class, 'getContacts']);
    Route::post('/v1/crm/contacts', [CrmController::class, 'createContact']);
    Route::get('/v1/workspaces/{workspace_id}/crm/deals', [CrmController::class, 'getDeals']);
    Route::post('/v1/crm/deals', [CrmController::class, 'createDeal']);
    Route::put('/v1/crm/deals/{deal_id}/stage', [CrmController::class, 'updateDealStage']);
    Route::post('/v1/crm/custom-fields', [CrmController::class, 'createCustomField']);
    Route::post('/v1/crm/entities/{entity_id}/custom-field-values', [CrmController::class, 'setCustomFieldValue']);
    Route::get('/v1/crm/contacts/{contact_id}/interactions', [CrmController::class, 'getInteractions']);
    Route::post('/v1/crm/interactions', [CrmController::class, 'createInteraction']);

    // eBPF Telemetry & Server Analytics
    Route::get('/v1/workspaces/{workspace_id}/servers', [TelemetryController::class, 'getServers']);
    Route::post('/v1/servers', [TelemetryController::class, 'createServer']);
    Route::get('/v1/servers/{server_id}/metrics', [TelemetryController::class, 'getServerMetrics']);
    Route::get('/v1/servers/{server_id}/ebpf-network-logs', [TelemetryController::class, 'getEbpfNetworkLogs']);
    Route::get('/v1/servers/{server_id}/ssh-audits', [TelemetryController::class, 'getSshAudits']);

    // AI Coding Agent Subsystem
    Route::post('/v1/agent/run', [AgentController::class, 'startAgentRun']);
    Route::get('/v1/agent/runs/{run_id}', [AgentController::class, 'getAgentRun']);
    Route::post('/v1/agent/context/analyze', [AgentController::class, 'analyzeContext']);
    Route::post('/v1/agent/action/modify-code', [AgentController::class, 'modifyCode']);

    // Chapter 11: SRE, QA, and Web3 Escrow
    Route::post('/v1/sre/incidents/{id}/rollback', [\App\Http\Controllers\SreController::class, 'approveRollback']);
    Route::post('/v1/qa/runs/trigger', [\App\Http\Controllers\QaController::class, 'triggerQaRun']);
    Route::get('/v1/web3/bounties/{issue_id}/signature', [\App\Http\Controllers\Web3EscrowController::class, 'getOracleSignature']);
    Route::post('/v1/web3/bounties/lock', [\App\Http\Controllers\Web3EscrowController::class, 'lockBounty']);
    Route::put('/v1/web3/bounties/{escrow_id}/status', [\App\Http\Controllers\Web3EscrowController::class, 'updateBountyStatus']);

    // Chapter 12: Ephemeral CDEs and Sovereign MLOps
    Route::post('/v1/cde/workspaces/launch', [\App\Http\Controllers\CdeController::class, 'launchWorkspace']);
    Route::put('/v1/cde/workspaces/{id}/hibernate', [\App\Http\Controllers\CdeController::class, 'hibernateWorkspace']);
    Route::get('/v1/cde/workspaces/{id}/auto-hibernate', [\App\Http\Controllers\CdeController::class, 'autoHibernateInactive']);
    Route::post('/v1/mlops/models/fine-tune', [\App\Http\Controllers\MlopsController::class, 'triggerFineTune']);
    Route::post('/v1/mlops/inference/completions', [\App\Http\Controllers\MlopsController::class, 'generateInferenceCompletion']);

    // Chapter 13: Spatial Workspaces, Business Agents & PQC Security
    Route::post('/v1/spatial/rooms/create', [\App\Http\Controllers\SpatialController::class, 'createRoom']);
    Route::post('/v1/agents/objectives/launch', [\App\Http\Controllers\BusinessAgentController::class, 'launchObjective']);
    Route::post('/v1/agents/{id}/halt', [\App\Http\Controllers\BusinessAgentController::class, 'haltAgent']);
    Route::get('/v1/security/pqc/public-key', [\App\Http\Controllers\PqcSecurityController::class, 'getPostQuantumKey']);
});

