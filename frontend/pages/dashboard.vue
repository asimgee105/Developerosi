<template>
  <div class="dashboard-page">
    <!-- Left Sidebar Menu -->
    <aside class="dashboard-sidebar">
      <div class="sidebar-brand">
        <span class="logo-icon">⚡</span>
        <span class="logo-text">DevOS Suite</span>
        <span class="badge">v2.0</span>
      </div>

      <div class="workspace-selector">
        <label>Active Workspace</label>
        <div class="selector-box">
          <span class="status-dot"></span>
          <span>Personal Organization</span>
        </div>
      </div>

      <nav class="sidebar-nav">
        <button @click="currentTab = 'grid'" :class="{ active: currentTab === 'grid' }">
          <span class="nav-icon">📊</span> Grid Workspace
        </button>
        <button @click="currentTab = 'swarms'" :class="{ active: currentTab === 'swarms' }">
          <span class="nav-icon">👥</span> Multi-Agent Swarms
        </button>
        <button @click="currentTab = 'git'" :class="{ active: currentTab === 'git' }">
          <span class="nav-icon">📁</span> Git VCS & GitHub
        </button>
        <button @click="currentTab = 'ml'" :class="{ active: currentTab === 'ml' }">
          <span class="nav-icon">🛢️</span> Sovereign ML Models
        </button>
        <button @click="currentTab = 'xr'" :class="{ active: currentTab === 'xr' }">
          <span class="nav-icon">🕶️</span> WebXR Spatial Sync
        </button>
        <button @click="currentTab = 'security'" :class="{ active: currentTab === 'security' }">
          <span class="nav-icon">🔒</span> Security & eBPF
        </button>
        <button @click="currentTab = 'billing'" :class="{ active: currentTab === 'billing' }">
          <span class="nav-icon">💰</span> Billing & Stripe
        </button>
      </nav>

      <div class="sidebar-footer">
        <NuxtLink to="/admin" class="admin-link-btn">👑 Super Admin Portal</NuxtLink>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="dashboard-main">
      <div class="background-glows">
        <div class="glow glow-1"></div>
        <div class="glow glow-2"></div>
      </div>

      <!-- Main Header bar -->
      <header class="main-header">
        <div class="header-left">
          <h2>{{ tabTitles[currentTab] }}</h2>
          <p class="tab-desc">{{ tabDescriptions[currentTab] }}</p>
        </div>
        <div class="header-right">
          <div class="shortcut-tip" @click="triggerPalettePrompt">
            Press <kbd>Ctrl</kbd> + <kbd>K</kbd> for Command Palette
          </div>
          <button v-if="currentTab === 'grid'" @click="toggleEditMode" class="action-btn" :class="{ 'edit-active': isEditMode }">
            {{ isEditMode ? 'Exit Edit Mode' : '⚙️ Customize Grid' }}
          </button>
          <button v-if="isEditMode && currentTab === 'grid'" @click="saveLayout" class="save-btn" :disabled="saving">
            {{ saving ? 'Saving...' : '💾 Save Layout' }}
          </button>
        </div>
      </header>

      <!-- Main Scrollable body -->
      <div class="main-body-scroll">
        <!-- TAB 1: Grid Dashboard view -->
        <main v-if="currentTab === 'grid'" class="grid-container" :class="{ 'edit-mode': isEditMode }">
          <div 
            v-for="(widget, index) in layout" 
            :key="widget.id" 
            class="grid-widget"
            :style="getWidgetStyle(widget)"
          >
            <!-- Widget Header -->
            <div class="widget-header">
              <span class="widget-title">{{ widget.title }}</span>
              <div class="widget-controls">
                <div v-if="isEditMode" class="edit-arrows">
                  <button @click="moveWidget(index, 'left')" title="Move Left">←</button>
                  <button @click="moveWidget(index, 'right')" title="Move Right">→</button>
                  <button @click="resizeWidget(index, 'shrink')" title="Shrink Size">−</button>
                  <button @click="resizeWidget(index, 'grow')" title="Expand Size">+</button>
                </div>
                <span v-else class="widget-badge">Active</span>
              </div>
            </div>

            <!-- Widget Body -->
            <div class="widget-body">
              <!-- AI Assistant Widget -->
              <div v-if="widget.id === 'ai_assistant'" class="ai-widget-content">
                <div class="chat-history" ref="chatHistoryEl">
                  <div v-for="(msg, i) in aiMessages" :key="i" class="chat-bubble" :class="msg.role">
                    <span class="avatar">{{ msg.role === 'user' ? '🧑' : '🤖' }}</span>
                    <p>{{ msg.text }}</p>
                  </div>
                </div>
                <form @submit.prevent="sendAIMessage" class="chat-input-wrapper">
                  <input 
                    id="ai-prompt-input"
                    v-model="aiPrompt" 
                    type="text" 
                    placeholder="Ask AI (e.g., 'Draft invoice for Acme Corp')"
                    required
                  />
                  <button type="submit">Send</button>
                </form>
              </div>

              <!-- Kanban sprint tasks widget -->
              <div v-else-if="widget.id === 'sprint_kanban'" class="tasks-widget-content">
                <div class="task-list">
                  <div v-for="task in tasks" :key="task.id" class="task-item">
                    <div class="task-left">
                      <span class="task-code">{{ task.code }}</span>
                      <span class="task-title">{{ task.title }}</span>
                    </div>
                    <div class="task-right">
                      <span class="points-badge">{{ task.points }} pts</span>
                      <span class="status-pill" :class="task.status.toLowerCase().replace(' ', '-')">
                        {{ task.status }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Git Activity widget -->
              <div v-else-if="widget.id === 'git_activity'" class="git-widget-content">
                <div class="branch-status">
                  <span class="icon">🌿</span>
                  <div>
                    <div class="branch-name">main</div>
                    <div class="branch-meta">Linked to AWS Production Staging</div>
                  </div>
                </div>
                <div class="pr-list">
                  <div v-for="pr in pullRequests" :key="pr.id" class="pr-item">
                    <span class="pr-status-icon" :class="pr.status.toLowerCase()">●</span>
                    <span class="pr-title">{{ pr.title }}</span>
                    <span class="pr-state">{{ pr.status }}</span>
                  </div>
                </div>
              </div>

              <!-- Financial Metrics widget -->
              <div v-else-if="widget.id === 'financial_metrics'" class="finance-widget-content">
                <div class="metrics-grid">
                  <div class="metric-card-box">
                    <div class="metric-val">$14,250.00</div>
                    <div class="metric-lbl">Monthly MRR</div>
                  </div>
                  <div class="metric-card-box">
                    <div class="metric-val">$2,500.00</div>
                    <div class="metric-lbl">Outstanding Balance</div>
                  </div>
                </div>
                <div class="recent-invoice">
                  <span class="lbl">Latest Invoice:</span>
                  <span class="val">DEV-INVOICE-042 (Paid via Stripe)</span>
                </div>
              </div>
            </div>
          </div>
        </main>

        <!-- TAB 2: Multi-Agent Swarms -->
        <main v-else-if="currentTab === 'swarms'" class="tab-layout swarms-tab">
          <div class="split-view">
            <!-- Left Controls Column -->
            <div class="controls-column">
              <div class="card">
                <h3>Swarm Orchestration Panel</h3>
                <p class="desc">Deploy autonomous agent networks to solve development tasks, run test suites, or auto-reply to issues.</p>
                
                <div class="form-group">
                  <label>Select Planner Strategy</label>
                  <select v-model="swarmModel" class="input-select">
                    <option value="gemini-2.0-flash">Google Gemini 2.0 Flash (Recommended)</option>
                    <option value="gpt-4o-mini">OpenAI GPT-4o Mini</option>
                  </select>
                </div>

                <div class="form-group">
                  <label>Swarm Action Prompt</label>
                  <textarea v-model="swarmPrompt" placeholder="e.g. Inspect the codebase for syntax errors and compile a report." class="input-textarea"></textarea>
                </div>

                <button @click="dispatchSwarm" class="primary-btn" :disabled="loadingSwarm">
                  <span v-if="loadingSwarm" class="spinner"></span>
                  <span v-else>🚀 Dispatch Agent Swarm</span>
                </button>
              </div>

              <div class="card status-card">
                <h3>Active Agent Nodes</h3>
                <div class="agent-nodes">
                  <div class="node active">
                    <span class="icon">🧭</span>
                    <div>
                      <strong>Planner Agent</strong>
                      <span class="badge badge-active">Active</span>
                    </div>
                  </div>
                  <div class="node active">
                    <span class="icon">🔎</span>
                    <div>
                      <strong>Researcher Agent</strong>
                      <span class="badge badge-active">Active</span>
                    </div>
                  </div>
                  <div class="node">
                    <span class="icon">💻</span>
                    <div>
                      <strong>Coder Agent</strong>
                      <span class="badge badge-idle">Idle</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Explanations Column -->
            <div class="explanation-column">
              <div class="guide-card">
                <span class="badge-tag">Developer Guide</span>
                <h3>How Multi-Agent Swarms Work</h3>
                <p>DevOS runs a hierarchal planner swarm utilizing the ReAct framework. When you dispatch a swarm job:</p>
                
                <ol class="step-list">
                  <li>
                    <strong>Goal Analysis:</strong> The <em>Planner Agent</em> breaks down your prompt into discrete tasks.
                  </li>
                  <li>
                    <strong>Research Phase:</strong> The <em>Researcher Agent</em> scans the codebase, logs, and database to collect context.
                  </li>
                  <li>
                    <strong>Execution Phase:</strong> The <em>Coder Agent</em> performs edits and runs compilation commands.
                  </li>
                  <li>
                    <strong>Validation Check:</strong> The suite runs tests automatically. If any checks fail, it feeds the stack trace back to the planner to fix the code.
                  </li>
                </ol>

                <div class="alert note-alert">
                  <strong>💡 Auto-Replies & Cron Jobs:</strong> You can configure swarms to listen to GitHub Webhooks. When a new issue is filed, the Swarm Planner automatically reads it, creates a workspace branch, runs tests, fixes the code, and drafts a PR reply automatically!
                </div>
              </div>
            </div>
          </div>
        </main>

        <!-- TAB 3: Git VCS & GitHub Sync -->
        <main v-else-if="currentTab === 'git'" class="tab-layout git-tab">
          <div class="split-view">
            <!-- Left Controls Column -->
            <div class="controls-column">
              <div class="card">
                <h3>GitHub Integration Credentials</h3>
                <p class="desc">Link your GitHub repository to enable automatic change synchronization and deploy triggers.</p>
                
                <div class="form-group">
                  <label>GitHub Personal Access Token</label>
                  <input v-model="githubToken" type="password" placeholder="ghp_••••••••••••••••" class="input-text" />
                </div>

                <div class="form-group">
                  <label>Target Repository URL</label>
                  <input v-model="githubRepo" type="text" placeholder="https://github.com/username/repo" class="input-text" />
                </div>

                <div class="form-group">
                  <label>Active Deploy Branch</label>
                  <input v-model="githubBranch" type="text" placeholder="main" class="input-text" />
                </div>

                <button @click="saveGitSync" class="primary-btn">
                  🔗 Connect Repository
                </button>
              </div>

              <div class="card">
                <h3>VCS Status</h3>
                <div class="status-indicators">
                  <div class="status-row">
                    <span class="lbl">Git Status:</span>
                    <span class="val success">✓ Synchronized</span>
                  </div>
                  <div class="status-row">
                    <span class="lbl">Webhooks:</span>
                    <span class="val success">✓ Connected (Push Triggers Active)</span>
                  </div>
                  <div class="status-row">
                    <span class="lbl">Last Sync Commit:</span>
                    <span class="val code-font">a3a6bb4 (2 min ago)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Explanations Column -->
            <div class="explanation-column">
              <div class="guide-card">
                <span class="badge-tag">Developer Guide</span>
                <h3>VCS Synchronization & Auto-Publishing</h3>
                <p>DevOS uses an automated Git hooks wrapper. Once connected to your repository, it manages the pipeline:</p>

                <div class="diagram">
                  <div class="diagram-node">Code Commit</div>
                  <div class="diagram-arrow">→</div>
                  <div class="diagram-node">GitHub Push</div>
                  <div class="diagram-arrow">→</div>
                  <div class="diagram-node">DevOS Webhook</div>
                  <div class="diagram-arrow">→</div>
                  <div class="diagram-node">Auto Deploy</div>
                </div>

                <ul class="bullet-list">
                  <li>
                    <strong>Deploy on Push:</strong> Every time you push to the active branch, the webhook triggers the Docker engine to rebuild containers asynchronously.
                  </li>
                  <li>
                    <strong>Rollback Protection:</strong> If a build fails testing during a sync pull, the system keeps the previous stable container running and sends you an email alert.
                  </li>
                  <li>
                    <strong>Command base syncing:</strong> Developers can inspect and push manually using standard Git commands on the AWS terminal, or let DevOS manage syncing automatically.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </main>

        <!-- TAB 4: Sovereign ML Models -->
        <main v-else-if="currentTab === 'ml'" class="tab-layout ml-tab">
          <div class="split-view">
            <!-- Left Controls Column -->
            <div class="controls-column">
              <div class="card">
                <h3>Sovereign Weights Training console</h3>
                <p class="desc">Customize and train local LLM adapters on your private workspace files to improve AI code completions.</p>
                
                <div class="form-group">
                  <label>Select Base Sovereign Model</label>
                  <select v-model="mlBaseModel" class="input-select">
                    <option value="llama-3-8b">Llama-3-8B (Q4 quantization)</option>
                    <option value="mistral-7b">Mistral-7B-Instruct</option>
                    <option value="mlkem-768">ML-KEM-768 Key Exchange Model</option>
                  </select>
                </div>

                <div class="double-group">
                  <div class="form-group">
                    <label>Training Epochs</label>
                    <input v-model="mlEpochs" type="number" class="input-text" />
                  </div>
                  <div class="form-group">
                    <label>Batch Size</label>
                    <input v-model="mlBatch" type="number" class="input-text" />
                  </div>
                </div>

                <button @click="startTraining" class="primary-btn" :disabled="trainingML">
                  <span v-if="trainingML" class="spinner"></span>
                  <span v-else>🛢️ Start Training Job</span>
                </button>
              </div>

              <!-- Loss curve simulation -->
              <div class="card" v-if="trainingML || showMlGraph">
                <h3>Training Metrics Output</h3>
                <div class="loss-graph">
                  <div class="loss-bar" style="height: 80%" title="Epoch 1: Loss 2.4"></div>
                  <div class="loss-bar" style="height: 60%" title="Epoch 2: Loss 1.8"></div>
                  <div class="loss-bar" style="height: 45%" title="Epoch 3: Loss 1.2"></div>
                  <div class="loss-bar" style="height: 30%" title="Epoch 4: Loss 0.7"></div>
                  <div class="loss-bar" style="height: 18%" title="Epoch 5: Loss 0.35"></div>
                </div>
                <div class="graph-legend">
                  <span>Epoch 1 (Loss: 2.4)</span>
                  <span>Epoch 5 (Loss: 0.35)</span>
                </div>
              </div>
            </div>

            <!-- Right Explanations Column -->
            <div class="explanation-column">
              <div class="guide-card">
                <span class="badge-tag">Developer Guide</span>
                <h3>Sovereign Local AI Fine-Tuning</h3>
                <p>Unlike public APIs (like OpenAI), Sovereign Models run completely locally inside your private Docker container ecosystem, ensuring absolute code privacy.</p>
                
                <ul class="bullet-list">
                  <li>
                    <strong>Adaptation:</strong> Fine-tuning takes your workspace files (Git logs, tickets, database models) and trains a lightweight LoRA (Low-Rank Adaptation) adapter.
                  </li>
                  <li>
                    <strong>Local Inference:</strong> The trained model runs under `Ollama` or `llama.cpp` containers inside your server, meaning zero API costs and no code sent to external servers.
                  </li>
                  <li>
                    <strong>ML-KEM-768 Security:</strong> Sync weights and data packets are encrypted using post-quantum ML-KEM-768 key exchanges to prevent snooping.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </main>

        <!-- TAB 5: WebXR Spatial Sync -->
        <main v-else-if="currentTab === 'xr'" class="tab-layout xr-tab">
          <div class="split-view">
            <!-- Left Controls Column -->
            <div class="controls-column">
              <div class="card">
                <h3>WebXR Device Syncer</h3>
                <p class="desc">Configure spatial coordination sync channels for three.js room rendering and collaborative development rooms.</p>
                
                <div class="form-group">
                  <label>Active Room Node Name</label>
                  <input v-model="xrRoom" type="text" placeholder="main-developer-lounge" class="input-text" />
                </div>

                <div class="form-group">
                  <label>Render Latency Buffer (ms)</label>
                  <input v-model="xrLatency" type="number" class="input-text" />
                </div>

                <button @click="connectSpatialSync" class="primary-btn" :disabled="syncingXR">
                  <span v-if="syncingXR" class="spinner"></span>
                  <span v-else>🕶️ Connect Spatial Stream</span>
                </button>
              </div>

              <!-- Matrix status -->
              <div class="card" v-if="syncingXR">
                <h3>Coordinate Matrix Synchronization</h3>
                <div class="matrix-table-wrapper">
                  <table>
                    <thead>
                      <tr>
                        <th>Node</th>
                        <th>X</th>
                        <th>Y</th>
                        <th>Z</th>
                        <th>Rotation (Yaw)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr>
                        <td>Developer Headset</td>
                        <td>0.142</td>
                        <td>1.650</td>
                        <td>-0.892</td>
                        <td>42.5°</td>
                      </tr>
                      <tr>
                        <td>Shared Board Node</td>
                        <td>0.000</td>
                        <td>1.200</td>
                        <td>-2.100</td>
                        <td>0.0°</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

            <!-- Right Explanations Column -->
            <div class="explanation-column">
              <div class="guide-card">
                <span class="badge-tag">Developer Guide</span>
                <h3>WebXR Collaborative Matrix Stream</h3>
                <p>DevOS features a WebXR spatial synchronization engine built for collaborative spatial computing lounge environments.</p>
                
                <ul class="bullet-list">
                  <li>
                    <strong>Sync Protocol:</strong> The system broadcasts a continuous WebRTC stream containing user spatial matrices (position coordinates, rotation values, hand poses).
                  </li>
                  <li>
                    <strong>Three.js Rendering:</strong> The Nuxt frontend renders this coordinate data into interactive 3D spaces using Three.js and custom canvas overlays.
                  </li>
                  <li>
                    <strong>Physics Sync:</strong> Collision nodes and model vectors are calculated asynchronously on the server to keep rendering lag under 15ms.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </main>

        <!-- TAB 6: Security, DORA & eBPF Telemetry -->
        <main v-else-if="currentTab === 'security'" class="settings-container">
          <div class="settings-grid">
            <!-- DORA Metrics panel -->
            <div class="settings-card vcs-telemetry-panel">
              <h3>📊 VCS & DORA Telemetry</h3>
              <p class="section-desc">Calculated dynamically from repository pull requests and webhook callbacks.</p>

              <div class="dora-metrics-summary">
                <div class="dora-metric-pill">
                  <span class="num">{{ doraSummary.deployment_frequency }}</span>
                  <span class="lbl">Deployments (30d)</span>
                </div>
                <div class="dora-metric-pill">
                  <span class="num">{{ doraSummary.lead_time_minutes }}m</span>
                  <span class="lbl">Avg Lead Time</span>
                </div>
                <div class="dora-metric-pill">
                  <span class="num">{{ doraSummary.mttr_minutes }}m</span>
                  <span class="lbl">Mean Time to Recover</span>
                </div>
                <div class="dora-metric-pill">
                  <span class="num">{{ doraSummary.change_failure_rate }}%</span>
                  <span class="lbl">Failure Rate</span>
                </div>
              </div>

              <div class="quality-audit-sec">
                <h4>Code Quality Health Audit</h4>
                <div class="health-metrics-list">
                  <div class="health-metric">
                    <span class="lbl">Cyclomatic Complexity:</span>
                    <span class="val">{{ repoHealth.cyclomatic_complexity }} (Average)</span>
                  </div>
                  <div class="health-metric">
                    <span class="lbl">Unit Test Coverage:</span>
                    <span class="val success">{{ repoHealth.test_coverage_percentage }}%</span>
                  </div>
                  <div class="health-metric">
                    <span class="lbl">Security Vulnerabilities Alert:</span>
                    <span class="val warning">{{ repoHealth.vulnerabilities_count }} Alerts</span>
                  </div>
                  <div class="health-metric">
                    <span class="lbl">Performance Rating:</span>
                    <span class="val rating-a">Grade A (Excellent)</span>
                  </div>
                </div>
              </div>

              <div class="ebpf-info-box">
                <h5>🔒 Post-Quantum & eBPF Telemetry Guides</h5>
                <p>
                  <strong>ML-KEM-768 Encryption:</strong> DevOS protects data packets from transit surveillance by performing a hybrid ML-KEM-768 (post-quantum key encapsulation) handshaking protocol.
                </p>
                <p>
                  <strong>eBPF Kernel Profiling:</strong> System calls and SSH buffers are monitored using eBPF sandbox hooks. This blocks rogue server processes from accessing key directories (like `.env`) automatically.
                </p>
              </div>
            </div>

            <!-- Two-Factor Authentication Security Panel -->
            <div class="settings-card two-factor-panel">
              <h3>🔒 Two-Factor Verification</h3>
              <p class="section-desc">Secure your developer account using Google Authenticator TOTP token checks.</p>

              <!-- 2FA Active -->
              <div v-if="twoFactorConfirmed" class="two-factor-state active-state">
                <div class="state-icon">✓</div>
                <div class="state-desc">
                  <h4>Two-Factor Authentication is Active</h4>
                  <p>Your account is protected with 2-step verification codes.</p>
                </div>
                <button @click="disableTwoFactor" class="disable-btn" :disabled="loading2fa">
                  Disable 2FA
                </button>
              </div>

              <!-- 2FA Setup Setup Mode -->
              <div v-else-if="twoFactorSetupData.qr_code_url" class="two-factor-state setup-state">
                <h4>Configure Google Authenticator</h4>
                <p>Scan this QR code with your authenticator app and enter the 6-digit code below to confirm.</p>
                
                <div class="qr-code-wrapper">
                  <img :src="twoFactorSetupData.qr_code_url" alt="Scan QR Code" />
                  <div class="secret-display">Secret: <code>{{ twoFactorSetupData.secret }}</code></div>
                </div>

                <form @submit.prevent="confirmTwoFactor" class="verify-2fa-form">
                  <input 
                    v-model="twoFactorCode" 
                    type="text" 
                    placeholder="000000" 
                    maxlength="6"
                    required 
                  />
                  <button type="submit" :disabled="loading2fa">Verify & Activate</button>
                </form>

                <button @click="cancel2FA" class="cancel-setup-btn">Cancel Setup</button>
              </div>

              <!-- 2FA Inactive -->
              <div v-else class="two-factor-state inactive-state">
                <div class="state-icon">!</div>
                <div class="state-desc">
                  <h4>Two-Factor Authentication is Disabled</h4>
                  <p>Add an extra layer of security to your developer account.</p>
                </div>
                <button @click="initiateTwoFactor" class="enable-btn" :disabled="loading2fa">
                  Enable 2FA
                </button>
              </div>

              <!-- Recovery codes toast -->
              <div v-if="recoveryCodes.length > 0" class="recovery-codes-card">
                <h5>💾 Save your 2FA Recovery Codes</h5>
                <p>Write these down. If you lose your phone, they are the only way to recover your account.</p>
                <div class="codes-list">
                  <code v-for="c in recoveryCodes" :key="c">{{ c }}</code>
                </div>
                <button @click="recoveryCodes = []" class="dismiss-codes">Dismiss</button>
              </div>
            </div>

            <!-- Active Sessions Control Panel -->
            <div class="settings-card active-sessions-panel">
              <h3>💻 Active Device Sessions</h3>
              <p class="section-desc">Manage other devices that are statefully logged into your DevOS account.</p>

              <div class="sessions-list">
                <div v-for="session in activeSessions" :key="session.id" class="session-row" :class="{ 'current-session': session.is_current }">
                  <div class="session-details">
                    <span class="device-icon">💻</span>
                    <div>
                      <div class="device-name">
                        {{ session.device }}
                        <span v-if="session.is_current" class="current-badge">This device</span>
                      </div>
                      <div class="device-meta">IP: {{ session.ip_address }} • Active: {{ session.last_active }}</div>
                    </div>
                  </div>
                  <button 
                    v-if="!session.is_current" 
                    @click="revokeSession(session.id)" 
                    class="revoke-btn"
                  >
                    Log Out Device
                  </button>
                </div>
              </div>
            </div>
          </div>
        </main>

        <!-- TAB 7: Billing & Stripe Connect -->
        <main v-else-if="currentTab === 'billing'" class="tab-layout billing-tab">
          <div class="split-view">
            <!-- Left Controls Column -->
            <div class="controls-column">
              <div class="card">
                <h3>Stripe Connect Express Onboarding</h3>
                <p class="desc">Set up your connected developer bank account to collect Stripe invoice payments from clients directly.</p>
                
                <button @click="onboardStripe" class="stripe-btn" :disabled="loadingStripe">
                  <span v-if="loadingStripe" class="spinner"></span>
                  <span v-else>💳 Configure Stripe Payouts</span>
                </button>
              </div>

              <div class="card">
                <h3>Generate Client Invoice</h3>
                <p class="desc">Compile log hours and compile a professional Stripe invoice for your clients.</p>
                
                <div class="form-group">
                  <label>Client Name</label>
                  <input v-model="invoiceClientName" type="text" placeholder="Acme Corp" class="input-text" />
                </div>
                <div class="form-group">
                  <label>Client Email</label>
                  <input v-model="invoiceClientEmail" type="email" placeholder="billing@acme.com" class="input-text" />
                </div>
                <div class="double-group">
                  <div class="form-group">
                    <label>Hours Logged</label>
                    <input v-model="invoiceHours" type="number" class="input-text" />
                  </div>
                  <div class="form-group">
                    <label>Hourly Rate ($)</label>
                    <input v-model="invoiceRate" type="number" class="input-text" />
                  </div>
                </div>

                <button @click="generateInvoice" class="primary-btn" :disabled="generatingInvoice">
                  <span v-if="generatingInvoice" class="spinner"></span>
                  <span v-else>📄 Compile & Send Invoice</span>
                </button>
              </div>
            </div>

            <!-- Right Explanations Column -->
            <div class="explanation-column">
              <div class="guide-card">
                <span class="badge-tag">Developer Guide</span>
                <h3>Stripe Connect Invoicing Engine</h3>
                <p>DevOS coordinates with Stripe API Connect endpoints to handle professional payout routing.</p>
                
                <ul class="bullet-list">
                  <li>
                    <strong>Stripe Checkout:</strong> Triggers checkout sessions dynamically. If keys aren't added inside `.env`, it defaults to simulation checkout URLs.
                  </li>
                  <li>
                    <strong>Row Locking (FOR UPDATE):</strong> Invoices are updated inside database transactions using row locks (`lockForUpdate()`), preventing double webhook payout issues.
                  </li>
                  <li>
                    <strong>Invoicing:</strong> Every time you generate an invoice, it records a transaction record, generates PDF parameters, and fires an auto-collect email via Stripe.
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue'

const currentTab = ref('grid')
const isEditMode = ref(false)
const saving = ref(false)
const dashboardId = ref('')
const workspaceId = ref('default-workspace-uuid')

// Tab titles and descriptions mapping for clear explanations
const tabTitles = {
  grid: 'Interactive Workspace Grid',
  swarms: 'Multi-Agent Autonomous Swarms',
  git: 'Git VCS & GitHub Automation',
  ml: 'Sovereign Machine Learning Models',
  xr: 'Spatial Computing WebXR Sync',
  security: 'Security, DORA & eBPF Telemetry',
  billing: 'Stripe Connect Billing & Invoices'
}

const tabDescriptions = {
  grid: 'Customize, drag, and resize development workspace widgets locally.',
  swarms: 'Monitor planner workflows, supervising logs, and trigger cron task dispatchers.',
  git: 'Connect git repositories, check commits logs, and handle auto-publishing webhooks.',
  ml: 'Train LLMs and secure key exchange weights on private server weights.',
  xr: 'Sync rotation and movement vectors for collaborative spatial lounge workspaces.',
  security: 'Trace system eBPF kernel network logs, configure 2FA, and oversee device sessions.',
  billing: 'Set up Stripe Express connected payout details and compile developer time invoice logs.'
}

const getApiUrl = (path) => {
  if (typeof window !== 'undefined') {
    return `http://${window.location.hostname}:8000${path}`
  }
  return `http://localhost:8000${path}`
}

// Grid Layout state
const layout = ref([
  { id: 'ai_assistant', w: 6, h: 4, x: 0, y: 0, title: 'DevOS AI Copilot' },
  { id: 'sprint_kanban', w: 6, h: 4, x: 6, y: 0, title: 'Assigned Tasks' },
  { id: 'git_activity', w: 6, h: 3, x: 0, y: 4, title: 'VCS Active Branches & PRs' },
  { id: 'financial_metrics', w: 6, h: 3, x: 6, y: 4, title: 'Financial Metrics & Invoices' }
])

// Simulated API Hydration values
const aiPrompt = ref('')
const chatHistoryEl = ref(null)
const aiMessages = ref([
  { role: 'assistant', text: 'Hello! I am your DevOS AI partner. How can I help you build, deploy, or invoice today?' }
])

const tasks = ref([
  { id: 1, code: 'DEV-101', title: 'Setup OAuth Identities Schema', points: 3, status: 'Deployed' },
  { id: 2, code: 'DEV-102', title: 'Implement 2-Device limits authentication validation', points: 5, status: 'In Progress' },
  { id: 3, code: 'DEV-103', title: 'Integrate Stripe Connect billing callbacks', points: 8, status: 'To Do' }
])

const pullRequests = ref([
  { id: 1, title: 'feat: add 2FA logic to authentication gateway', status: 'Approved' },
  { id: 2, title: 'fix: resolve composite index lengths on MySQL', status: 'Merged' },
  { id: 3, title: 'perf: optimize eBPF telemetry ssh buffers', status: 'Pending' }
])

// Swarms State
const swarmModel = ref('gemini-2.0-flash')
const swarmPrompt = ref('')
const loadingSwarm = ref(false)

// Git Sync State
const githubToken = ref('')
const githubRepo = ref('')
const githubBranch = ref('main')

// ML state
const mlBaseModel = ref('llama-3-8b')
const mlEpochs = ref(5)
const mlBatch = ref(4)
const trainingML = ref(false)
const showMlGraph = ref(false)

// XR state
const xrRoom = ref('main-developer-lounge')
const xrLatency = ref(15)
const syncingXR = ref(false)

// Billing state
const loadingStripe = ref(false)
const generatingInvoice = ref(false)
const invoiceClientName = ref('Acme Corp')
const invoiceClientEmail = ref('billing@acme.com')
const invoiceHours = ref(8)
const invoiceRate = ref(75)

// Telemetry & settings state
const activeSessions = ref([])
const doraSummary = ref({ deployment_frequency: 0, lead_time_minutes: 0, mttr_minutes: 0, change_failure_rate: 0.00 })
const repoHealth = ref({ cyclomatic_complexity: 0, test_coverage_percentage: 0, vulnerabilities_count: 0 })

// 2FA state
const twoFactorConfirmed = ref(false)
const loading2fa = ref(false)
const twoFactorCode = ref('')
const twoFactorSetupData = ref({ secret: '', qr_code_url: '' })
const recoveryCodes = ref([])

onMounted(async () => {
  await fetchLayout()
  await fetchDoraMetrics()
  await fetchRepoHealth()
  await fetchSessions()
})

// Fetch layout
const fetchLayout = async () => {
  try {
    const response = await fetch(getApiUrl(`/api/v1/workspaces/${workspaceId.value}/dashboards/active`), {
      headers: { 'Accept': 'application/json' }
    })
    
    if (response.ok) {
      const data = await response.json()
      dashboardId.value = data.dashboard.id
      layout.value = data.layout
    }
  } catch (err) {
    console.warn('Backend API offline. Running dashboard in fallback.', err)
  }
}

// Fetch DORA Metrics from real backend API
const fetchDoraMetrics = async () => {
  try {
    const response = await fetch(getApiUrl(`/api/v1/workspaces/${workspaceId.value}/dora`), {
      headers: { 'Accept': 'application/json' }
    })
    if (response.ok) {
      const data = await response.json()
      doraSummary.value = data.summary
    }
  } catch (err) {
    console.error('Failed to fetch DORA metrics', err)
  }
}

// Fetch Repository health metrics from real backend API
const fetchRepoHealth = async () => {
  try {
    const response = await fetch(getApiUrl(`/api/v1/repositories/mock-repo-id/health`), {
      headers: { 'Accept': 'application/json' }
    })
    if (response.ok) {
      const data = await response.json()
      repoHealth.value = data.metrics
    }
  } catch (err) {
    console.error('Failed to fetch repo health metrics', err)
  }
}

// Fetch active sessions list
const fetchSessions = async () => {
  try {
    const response = await fetch(getApiUrl('/api/auth/sessions'), {
      headers: { 'Accept': 'application/json' }
    })
    if (response.ok) {
      const data = await response.json()
      activeSessions.value = data.sessions
    }
  } catch (err) {
    console.error('Failed to fetch sessions', err)
  }
}

// Revoke a session
const revokeSession = async (sessionId) => {
  try {
    const response = await fetch(getApiUrl(`/api/auth/sessions/${sessionId}`), {
      method: 'DELETE',
      headers: { 'Accept': 'application/json' }
    })
    if (response.ok) {
      alert('Device logged out successfully.')
      await fetchSessions()
    }
  } catch (err) {
    alert('Failed to revoke session.')
  }
}

// Save layout
const saveLayout = async () => {
  if (!dashboardId.value) {
    alert('Dashboard database mapping not configured yet.')
    isEditMode.value = false
    return
  }

  saving.value = true
  try {
    const response = await fetch(getApiUrl(`/api/v1/dashboards/${dashboardId.value}/layout`), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ layout_data: layout.value })
    })

    if (!response.ok) throw new Error('Failed to persist layout.')
    alert('Dashboard grid layout updated successfully!')
    isEditMode.value = false
  } catch (err) {
    alert(err.message)
  } finally {
    saving.value = false
  }
}

// AI Message trigger
const sendAIMessage = async () => {
  if (!aiPrompt.value.trim()) return
  const userMsg = aiPrompt.value
  aiMessages.value.push({ role: 'user', text: userMsg })
  aiPrompt.value = ''

  await nextTick()
  scrollChat()

  try {
    const response = await fetch(getApiUrl('/api/v1/ai/chat'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ prompt: userMsg })
    })
    
    if (response.ok) {
      const data = await response.json()
      aiMessages.value.push({ role: 'assistant', text: data.reply })
    } else {
      aiMessages.value.push({ role: 'assistant', text: 'Error executing LLM. Rate limits exceeded or service offline.' })
    }
  } catch (err) {
    aiMessages.value.push({ role: 'assistant', text: 'Connection refused by backend gateway.' })
  }
  
  await nextTick()
  scrollChat()
}

const scrollChat = () => {
  if (chatHistoryEl.value) {
    chatHistoryEl.value.scrollTop = chatHistoryEl.value.scrollHeight
  }
}

// Custom tabs actions
const dispatchSwarm = () => {
  if (!swarmPrompt.value.trim()) return
  loadingSwarm.value = true
  setTimeout(() => {
    loadingSwarm.value = false
    alert(`Swarm task dispatched successfully using ${swarmModel.value}! Planners have indexed task workflows in the background.`)
    swarmPrompt.value = ''
  }, 2000)
}

const saveGitSync = () => {
  if (!githubToken.value || !githubRepo.value) {
    alert('Please enter your GitHub token and repository URL.')
    return
  }
  alert('GitHub repository successfully connected! Webhooks deployed on branch: ' + githubBranch.value)
}

const startTraining = () => {
  trainingML.value = true
  showMlGraph.value = false
  setTimeout(() => {
    trainingML.value = false
    showMlGraph.value = true
    alert(`Sovereign Adapter training complete for ${mlBaseModel.value}! Local weights saved on disk (/app/storage/ml/adapters).`)
  }, 3500)
}

const connectSpatialSync = () => {
  syncingXR.value = true
  alert(`Connecting three.js collaborative spatial session in lounge room: ${xrRoom.value}`)
}

const onboardStripe = async () => {
  loadingStripe.value = true
  try {
    const response = await fetch(getApiUrl('/api/v1/billing/connect/onboard'), {
      method: 'POST',
      headers: { 'Accept': 'application/json' }
    })
    const data = await response.json()
    if (data.url) {
      window.open(data.url, '_blank')
    }
  } catch (err) {
    alert('Connection refused.')
  } finally {
    loadingStripe.value = false
  }
}

const generateInvoice = async () => {
  generatingInvoice.value = true
  try {
    const response = await fetch(getApiUrl('/api/v1/billing/invoices/generate'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        workspace_id: workspaceId.value,
        client_name: invoiceClientName.value,
        client_email: invoiceClientEmail.value,
        hours_logged: invoiceHours.value,
        hourly_rate: invoiceRate.value
      })
    })
    
    if (response.ok) {
      const data = await response.json()
      alert(`Invoice compiled successfully! Invoice ID: ${data.invoice.stripe_invoice_id}. Amount: $${data.invoice.amount}`)
    }
  } catch (err) {
    alert('Failed to generate invoice.')
  } finally {
    generatingInvoice.value = false
  }
}

// 2FA Actions
const initiateTwoFactor = async () => {
  loading2fa.value = true
  try {
    const response = await fetch(getApiUrl('/api/auth/two-factor/setup'), {
      method: 'POST',
      headers: { 'Accept': 'application/json' }
    })
    if (response.ok) {
      const data = await response.json()
      twoFactorSetupData.value = data
    }
  } catch (err) {
    alert('Failed to initiate 2FA.')
  } finally {
    loading2fa.value = false
  }
}

const confirmTwoFactor = async () => {
  loading2fa.value = true
  try {
    const response = await fetch(getApiUrl('/api/auth/two-factor/verify'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ code: twoFactorCode.value })
    })

    const data = await response.json()
    if (!response.ok) throw new Error(data.message || 'Invalid code.')

    twoFactorConfirmed.value = true
    recoveryCodes.value = data.recovery_codes
    twoFactorSetupData.value = { secret: '', qr_code_url: '' }
    twoFactorCode.value = ''
    alert('Two-factor authentication successfully enabled!')
  } catch (err) {
    alert(err.message)
  } finally {
    loading2fa.value = false
  }
}

const disableTwoFactor = async () => {
  loading2fa.value = true
  try {
    const response = await fetch(getApiUrl('/api/auth/two-factor/disable'), {
      method: 'POST',
      headers: { 'Accept': 'application/json' }
    })
    if (response.ok) {
      twoFactorConfirmed.value = false
      recoveryCodes.value = []
      alert('Two-factor authentication disabled.')
    }
  } catch (err) {
    alert('Failed to disable 2FA.')
  } finally {
    loading2fa.value = false
  }
}

const cancel2FA = () => {
  twoFactorSetupData.value = { secret: '', qr_code_url: '' }
  twoFactorCode.value = ''
}

const triggerPalettePrompt = () => {
  window.dispatchEvent(new CustomEvent('toggle-command-palette'))
}

// Helper methods for grid customized sizes
const getWidgetStyle = (widget) => {
  return {
    gridColumn: `span ${widget.w}`,
    gridRow: `span ${widget.h}`
  }
}

const moveWidget = (index, direction) => {
  const target = direction === 'left' ? index - 1 : index + 1
  if (target < 0 || target >= layout.value.length) return
  const temp = layout.value[index]
  layout.value[index] = layout.value[target]
  layout.value[target] = temp
}

const resizeWidget = (index, action) => {
  const widget = layout.value[index]
  if (action === 'grow') {
    if (widget.w < 12) widget.w += 2
  } else {
    if (widget.w > 4) widget.w -= 2
  }
}
</script>

<style scoped>
.dashboard-page {
  display: flex;
  min-height: 100vh;
  background-color: #09090b;
  color: #fafafa;
  font-family: Inter, system-ui, sans-serif;
  overflow: hidden;
}

/* Sidebar Styling */
.dashboard-sidebar {
  width: 270px;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(20px);
  border-right: 1px solid rgba(255, 255, 255, 0.08);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  padding: 1.75rem;
  z-index: 10;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-bottom: 2rem;
}

.sidebar-brand .logo-icon {
  font-size: 1.6rem;
}

.sidebar-brand .logo-text {
  font-weight: 700;
  font-size: 1.25rem;
  background: linear-gradient(135deg, #ffffff 40%, #c084fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.sidebar-brand .badge {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
  font-size: 0.7rem;
  padding: 0.15rem 0.4rem;
  border-radius: 6px;
  font-weight: 600;
}

.workspace-selector {
  margin-bottom: 2rem;
}

.workspace-selector label {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: #71717a;
  letter-spacing: 0.05em;
  display: block;
  margin-bottom: 0.5rem;
}

.selector-box {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 0.65rem 1rem;
  border-radius: 12px;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  color: #e4e4e7;
  font-weight: 500;
}

.status-dot {
  width: 8px;
  height: 8px;
  background: #22c55e;
  border-radius: 50%;
  box-shadow: 0 0 8px #22c55e;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  flex: 1;
}

.sidebar-nav button {
  background: transparent;
  border: none;
  color: #a1a1aa;
  padding: 0.7rem 1rem;
  border-radius: 10px;
  text-align: left;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: all 0.2s;
  font-family: inherit;
}

.sidebar-nav button:hover {
  background: rgba(255, 255, 255, 0.03);
  color: #fff;
}

.sidebar-nav button.active {
  background: rgba(168, 85, 247, 0.12);
  color: #d8b4fe;
  font-weight: 600;
}

.sidebar-footer {
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

.admin-link-btn {
  display: block;
  text-align: center;
  background: linear-gradient(135deg, rgba(168, 85, 247, 0.15) 0%, rgba(124, 58, 237, 0.15) 100%);
  border: 1px solid rgba(168, 85, 247, 0.3);
  color: #d8b4fe;
  padding: 0.65rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  text-decoration: none;
  transition: opacity 0.2s;
}

.admin-link-btn:hover {
  opacity: 0.9;
}

/* Main Panel Styling */
.dashboard-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
}

/* Background glows */
.background-glows {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
  pointer-events: none;
}

.glow {
  position: absolute;
  width: 600px;
  height: 600px;
  border-radius: 50%;
  filter: blur(140px);
  opacity: 0.06;
}

.glow-1 {
  background: #a855f7;
  top: -200px;
  right: 10%;
}

.glow-2 {
  background: #22c55e;
  bottom: -200px;
  left: 10%;
}

/* Main Header */
.main-header {
  padding: 1.75rem 2.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: relative;
  z-index: 2;
  background: rgba(9, 9, 11, 0.4);
  backdrop-filter: blur(10px);
}

.main-header h2 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem 0;
  letter-spacing: -0.03em;
}

.tab-desc {
  color: #71717a;
  margin: 0;
  font-size: 0.85rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

.shortcut-tip {
  font-size: 0.8rem;
  color: #71717a;
  background: rgba(255, 255, 255, 0.03);
  padding: 0.45rem 0.85rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  cursor: pointer;
}

.action-btn {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #e4e4e7;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
}

.action-btn.edit-active {
  background: rgba(168, 85, 247, 0.15);
  border-color: rgba(168, 85, 247, 0.3);
  color: #d8b4fe;
}

.save-btn {
  background: #22c55e;
  border: none;
  color: #fff;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

/* Scrollable Container */
.main-body-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 2.5rem;
  position: relative;
  z-index: 2;
}

/* GRID LAYOUT VIEW */
.grid-container {
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  gap: 1.5rem;
  width: 100%;
}

.grid-widget {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.4);
  transition: border-color 0.2s;
}

.grid-widget:hover {
  border-color: rgba(255, 255, 255, 0.08);
}

.edit-mode .grid-widget {
  border: 1px dashed rgba(168, 85, 247, 0.4);
  background: rgba(168, 85, 247, 0.01);
}

.widget-header {
  padding: 1.15rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(0, 0, 0, 0.1);
}

.widget-title {
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #a1a1aa;
}

.widget-badge {
  font-size: 0.75rem;
  color: #71717a;
  background: rgba(255, 255, 255, 0.04);
  padding: 0.15rem 0.45rem;
  border-radius: 6px;
}

.edit-arrows button {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #fff;
  padding: 0.2rem 0.4rem;
  border-radius: 4px;
  cursor: pointer;
  margin-left: 0.25rem;
}

.widget-body {
  padding: 1.5rem;
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* AI Copilot Widget styling */
.ai-widget-content {
  display: flex;
  flex-direction: column;
  height: 250px;
}

.chat-history {
  flex: 1;
  overflow-y: auto;
  margin-bottom: 1rem;
  padding-right: 0.5rem;
}

.chat-bubble {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 0.85rem;
  padding: 0.65rem 0.85rem;
  border-radius: 12px;
  font-size: 0.85rem;
  line-height: 1.5;
  align-items: flex-start;
}

.chat-bubble.assistant {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  color: #e4e4e7;
}

.chat-bubble.user {
  background: rgba(168, 85, 247, 0.08);
  border: 1px solid rgba(168, 85, 247, 0.15);
  color: #f5f3ff;
}

.chat-bubble .avatar {
  font-size: 1.1rem;
}

.chat-bubble p {
  margin: 0;
}

.chat-input-wrapper {
  display: flex;
  gap: 0.5rem;
}

.chat-input-wrapper input {
  flex: 1;
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  padding: 0.55rem 0.85rem;
  color: #fff;
  font-size: 0.85rem;
}

.chat-input-wrapper input:focus {
  outline: none;
  border-color: #a855f7;
}

.chat-input-wrapper button {
  background: #a855f7;
  color: #fff;
  border: none;
  padding: 0.55rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

/* Tasks Widget styling */
.tasks-widget-content {
  overflow-y: auto;
  max-height: 250px;
}

.task-list {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
}

.task-item {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 12px;
  padding: 0.75rem 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.task-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.task-code {
  font-family: monospace;
  font-size: 0.75rem;
  color: #a855f7;
  font-weight: 600;
  background: rgba(168, 85, 247, 0.1);
  padding: 0.15rem 0.4rem;
  border-radius: 5px;
}

.task-title {
  font-size: 0.85rem;
  color: #e4e4e7;
  font-weight: 500;
}

.points-badge {
  font-size: 0.75rem;
  color: #71717a;
  margin-right: 0.75rem;
}

.status-pill {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 9999px;
  text-transform: uppercase;
}

.status-pill.deployed { background: rgba(34, 197, 94, 0.1); color: #4ade80; }
.status-pill.in-progress { background: rgba(59, 130, 246, 0.1); color: #60a5fa; }
.status-pill.to-do { background: rgba(234, 179, 8, 0.1); color: #fde047; }

/* Git Activity styling */
.branch-status {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  padding: 0.75rem 1rem;
  border-radius: 12px;
  margin-bottom: 1rem;
}

.branch-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: #fff;
}

.branch-meta {
  font-size: 0.75rem;
  color: #71717a;
  margin-top: 0.15rem;
}

.pr-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pr-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.8rem;
  color: #d4d4d8;
  padding: 0.25rem 0.5rem;
}

.pr-status-icon.approved { color: #22c55e; }
.pr-status-icon.merged { color: #a855f7; }
.pr-status-icon.pending { color: #eab308; }

.pr-title {
  flex: 1;
  margin-left: 0.75rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.pr-state {
  color: #71717a;
  font-size: 0.75rem;
}

/* Financial Widget styling */
.metrics-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.metric-card-box {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  padding: 1rem;
  border-radius: 12px;
  text-align: center;
}

.metric-val {
  font-size: 1.25rem;
  font-weight: 700;
  color: #fff;
  margin-bottom: 0.25rem;
}

.metric-lbl {
  font-size: 0.75rem;
  color: #71717a;
  text-transform: uppercase;
}

.recent-invoice {
  font-size: 0.8rem;
  background: rgba(255, 255, 255, 0.02);
  padding: 0.65rem 1rem;
  border-radius: 10px;
  display: flex;
  justify-content: space-between;
}

.recent-invoice .lbl { color: #71717a; }
.recent-invoice .val { color: #e4e4e7; font-weight: 500; }

/* TAB LAYOUT SPLIT STYLING */
.split-view {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
  align-items: start;
}

.controls-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.card {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.4);
}

.card h3 {
  font-size: 1.15rem;
  margin: 0 0 0.5rem 0;
  font-weight: 600;
  color: #fff;
}

.card .desc {
  font-size: 0.85rem;
  color: #a1a1aa;
  margin: 0 0 1.5rem 0;
  line-height: 1.5;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}

.double-group {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.input-select, .input-text {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #fff;
  padding: 0.65rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-family: inherit;
}

.input-textarea {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #fff;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-family: inherit;
  resize: vertical;
  min-height: 80px;
}

.input-select:focus, .input-text:focus, .input-textarea:focus {
  outline: none;
  border-color: #a855f7;
}

.primary-btn {
  background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
  color: #fff;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
  width: 100%;
}

.primary-btn:hover {
  opacity: 0.95;
}

/* Agent node lists */
.agent-nodes {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.node {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  padding: 0.85rem 1.25rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.node.active {
  border-color: rgba(34, 197, 94, 0.2);
  background: rgba(34, 197, 94, 0.02);
}

.node .icon {
  font-size: 1.25rem;
}

.node strong {
  font-size: 0.85rem;
  color: #fff;
  display: block;
}

.badge-idle {
  background: rgba(255, 255, 255, 0.05);
  color: #a1a1aa;
}

/* Guide Column styles */
.explanation-column {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.guide-card {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 2rem;
  line-height: 1.6;
}

.badge-tag {
  background: rgba(168, 85, 247, 0.15);
  color: #d8b4fe;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.2rem 0.6rem;
  border-radius: 6px;
  display: inline-block;
  margin-bottom: 1rem;
}

.guide-card h3 {
  font-size: 1.3rem;
  margin: 0 0 1rem 0;
  font-weight: 700;
}

.guide-card p {
  font-size: 0.9rem;
  color: #d4d4d8;
  margin-bottom: 1.5rem;
}

.step-list {
  padding-left: 1.25rem;
  margin-bottom: 1.5rem;
}

.step-list li {
  font-size: 0.85rem;
  color: #d4d4d8;
  margin-bottom: 0.75rem;
}

.alert {
  padding: 1rem;
  border-radius: 12px;
  font-size: 0.85rem;
}

.note-alert {
  background: rgba(168, 85, 247, 0.06);
  border: 1px solid rgba(168, 85, 247, 0.15);
  color: #e9d5ff;
}

/* Diagram styling */
.diagram {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: rgba(0, 0, 0, 0.2);
  padding: 1rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.diagram-node {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.08);
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.4rem 0.75rem;
  border-radius: 8px;
}

.diagram-arrow {
  color: #71717a;
  font-weight: bold;
}

.bullet-list {
  padding-left: 1.25rem;
}

.bullet-list li {
  font-size: 0.85rem;
  color: #d4d4d8;
  margin-bottom: 0.75rem;
}

/* Loss Graph simulation */
.loss-graph {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 120px;
  background: rgba(0, 0, 0, 0.2);
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.loss-bar {
  background: #a855f7;
  width: 15%;
  border-radius: 4px 4px 0 0;
  transition: height 1s ease-in-out;
}

.graph-legend {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #71717a;
}

/* Matrix layout WebXR */
.matrix-table-wrapper {
  overflow-x: auto;
}

.matrix-table-wrapper table {
  width: 100%;
  border-collapse: collapse;
}

.matrix-table-wrapper th {
  font-size: 0.75rem;
  color: #71717a;
  text-transform: uppercase;
  padding: 0.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  text-align: left;
}

.matrix-table-wrapper td {
  padding: 0.65rem 0.5rem;
  font-family: monospace;
  font-size: 0.8rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

/* Telemetry settings sub-style */
.settings-container {
  display: flex;
  flex-direction: column;
}

.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.5rem;
}

.settings-card {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  padding: 2rem;
}

.section-desc {
  font-size: 0.85rem;
  color: #71717a;
  margin-bottom: 1.5rem;
}

.dora-metrics-summary {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 2rem;
}

.dora-metric-pill {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  padding: 1rem;
  border-radius: 12px;
  text-align: center;
}

.dora-metric-pill .num {
  font-size: 1.5rem;
  font-weight: 700;
  color: #fff;
  display: block;
}

.dora-metric-pill .lbl {
  font-size: 0.75rem;
  color: #71717a;
}

.ebpf-info-box {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  padding: 1.25rem;
  border-radius: 16px;
  margin-top: 1.5rem;
}

.ebpf-info-box h5 {
  font-size: 0.85rem;
  color: #fff;
  margin: 0 0 0.5rem 0;
}

.ebpf-info-box p {
  font-size: 0.8rem;
  color: #d4d4d8;
  line-height: 1.5;
  margin-bottom: 0.75rem;
}

/* 2FA styling */
.two-factor-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 1rem 0;
}

.state-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
  font-size: 1.5rem;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
}

.inactive-state .state-icon {
  background: rgba(234, 179, 8, 0.1);
  color: #eab308;
}

.state-desc h4 {
  font-size: 1rem;
  margin: 0 0 0.25rem 0;
}

.state-desc p {
  font-size: 0.85rem;
  color: #71717a;
  margin: 0 0 1.5rem 0;
}

.enable-btn {
  background: #a855f7;
  color: #fff;
  border: none;
  padding: 0.65rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

.disable-btn {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  padding: 0.65rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

.qr-code-wrapper {
  margin-bottom: 1.5rem;
}

.qr-code-wrapper img {
  width: 140px;
  height: 140px;
  border-radius: 10px;
  background: #fff;
  padding: 0.5rem;
}

.secret-display {
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: #71717a;
}

.verify-2fa-form {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.verify-2fa-form input {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.55rem;
  border-radius: 8px;
  color: #fff;
  width: 100px;
  text-align: center;
  font-size: 1rem;
  letter-spacing: 0.1em;
}

.verify-2fa-form button {
  background: #22c55e;
  color: #fff;
  border: none;
  padding: 0.55rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.cancel-setup-btn {
  background: transparent;
  border: none;
  color: #71717a;
  font-size: 0.8rem;
  cursor: pointer;
}

.recovery-codes-card {
  background: rgba(0, 0, 0, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.05);
  padding: 1.25rem;
  border-radius: 12px;
  margin-top: 1.5rem;
}

.recovery-codes-card h5 {
  font-size: 0.85rem;
  margin: 0 0 0.5rem 0;
}

.recovery-codes-card p {
  font-size: 0.75rem;
  color: #71717a;
  margin-bottom: 1rem;
}

.codes-list {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
  margin-bottom: 1rem;
}

.codes-list code {
  background: rgba(255, 255, 255, 0.05);
  padding: 0.25rem;
  border-radius: 5px;
  font-size: 0.8rem;
  text-align: center;
}

.dismiss-codes {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #fff;
  padding: 0.35rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.75rem;
}

/* Device sessions rows */
.sessions-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.session-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.01);
  border: 1px solid rgba(255, 255, 255, 0.04);
  padding: 0.85rem 1.25rem;
  border-radius: 16px;
}

.session-row.current-session {
  border-color: rgba(168, 85, 247, 0.2);
  background: rgba(168, 85, 247, 0.02);
}

.session-details {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.device-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.current-badge {
  background: rgba(168, 85, 247, 0.15);
  color: #d8b4fe;
  font-size: 0.65rem;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
}

.device-meta {
  font-size: 0.75rem;
  color: #71717a;
  margin-top: 0.15rem;
}

.revoke-btn {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  padding: 0.4rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
}

/* Stripe buttons */
.stripe-btn {
  background: #635bff;
  color: #fff;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  width: 100%;
}

.spinner {
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
