<template>
  <div class="dashboard-page">
    <!-- Background glows -->
    <div class="background-glows">
      <div class="glow glow-1"></div>
      <div class="glow glow-2"></div>
    </div>

    <!-- Header bar -->
    <header class="dashboard-header">
      <div class="header-left">
        <span class="logo-icon">⚡</span>
        <span class="logo-text">DevOS Workspace</span>
        <span class="badge">Agency Tier</span>
      </div>
      
      <div class="header-right">
        <!-- Navigation Tabs -->
        <div class="nav-tabs">
          <button @click="currentTab = 'grid'" :class="{ active: currentTab === 'grid' }">📊 Dashboard Grid</button>
          <button @click="currentTab = 'security'" :class="{ active: currentTab === 'security' }">🔒 Security & Telemetry</button>
        </div>

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
            <!-- Edit Mode Controls -->
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
          <!-- Widget 1: AI Assistant -->
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

          <!-- Widget 2: Kanban Sprint Tasks -->
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

          <!-- Widget 3: Git Activity -->
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

          <!-- Widget 4: Financial Metrics -->
          <div v-else-if="widget.id === 'financial_metrics'" class="finance-widget-content">
            <div class="metrics-grid">
              <div class="metric-card">
                <div class="metric-val">$14,250.00</div>
                <div class="metric-lbl">Monthly MRR</div>
              </div>
              <div class="metric-card">
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

    <!-- TAB 2: Security & Telemetry settings -->
    <main v-else class="settings-container">
      <div class="settings-grid">
        <!-- 1. Real DORA & VCS Health Panel -->
        <div class="settings-card vcs-telemetry-panel">
          <h3>📊 VCS & DORA Telemetry (Chapter 5)</h3>
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
                <span class="lbl">Health Performance Rating:</span>
                <span class="val rating-a">Grade A (Excellent)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- 2. Two-Factor Authentication Security Panel -->
        <div class="settings-card two-factor-panel">
          <h3>🔒 Two-Factor Verification (Chapter 2)</h3>
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

        <!-- 3. Active Sessions Control Panel -->
        <div class="settings-card active-sessions-panel">
          <h3>💻 Active Device Sessions (Chapter 2)</h3>
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
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue'

const currentTab = ref('grid')
const isEditMode = ref(false)
const saving = ref(false)
const dashboardId = ref('')
const workspaceId = ref('default-workspace-uuid')

// 4 Standard Widgets Grid Layout
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
    const response = await fetch(`http://localhost:8000/api/v1/workspaces/${workspaceId.value}/dashboards/active`, {
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
    const response = await fetch(`http://localhost:8000/api/v1/workspaces/${workspaceId.value}/dora`, {
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
    // Standard mock repository ID mapping
    const response = await fetch(`http://localhost:8000/api/v1/repositories/mock-repo-id/health`, {
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
    const response = await fetch('http://localhost:8000/api/auth/sessions', {
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
    const response = await fetch(`http://localhost:8000/api/auth/sessions/${sessionId}`, {
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
    const response = await fetch(`http://localhost:8000/api/v1/dashboards/${dashboardId.value}/layout`, {
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

// 2FA Actions
const initiateTwoFactor = async () => {
  loading2fa.value = true
  try {
    const response = await fetch('http://localhost:8000/api/auth/two-factor/setup', {
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
    const response = await fetch('http://localhost:8000/api/auth/two-factor/verify', {
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
  if (!confirm('Are you sure you want to disable 2FA?')) return
  loading2fa.value = true
  try {
    const response = await fetch('http://localhost:8000/api/auth/two-factor/disable', {
      method: 'POST',
      headers: { 'Accept': 'application/json' }
    })
    if (response.ok) {
      twoFactorConfirmed.value = false
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

// Convert grid layout variables into CSS grid style rules
const getWidgetStyle = (widget) => {
  return {
    gridColumnStart: widget.x + 1,
    gridColumnEnd: `span ${widget.w}`,
    gridRowStart: widget.y + 1,
    gridRowEnd: `span ${widget.h}`
  }
}

// Widget Layout editor modifiers
const toggleEditMode = () => {
  isEditMode.value = !isEditMode.value
}

const moveWidget = (index, direction) => {
  const widget = layout.value[index]
  if (direction === 'left' && widget.x > 0) {
    widget.x -= 1
  } else if (direction === 'right' && widget.x < 11) {
    widget.x += 1
  }
}

const resizeWidget = (index, action) => {
  const widget = layout.value[index]
  if (action === 'grow' && widget.w < 12) {
    widget.w += 1
  } else if (action === 'shrink' && widget.w > 3) {
    widget.w -= 1
  }
}

// AI chat simulator
const sendAIMessage = () => {
  if (!aiPrompt.value) return

  const userQuery = aiPrompt.value
  aiMessages.value.push({ role: 'user', text: userQuery })
  aiPrompt.value = ''

  nextTick(() => {
    if (chatHistoryEl.value) {
      chatHistoryEl.value.scrollTop = chatHistoryEl.value.scrollHeight
    }
  })

  // Simulated streaming response
  setTimeout(() => {
    let responseText = "I have scanned the workspace code. "
    if (userQuery.toLowerCase().includes('invoice')) {
      responseText += "I found unbilled time logs amounting to 12.5 hours for Acme Corp. I can generate an invoice for $625.00 using your default contract. Should I proceed?"
    } else if (userQuery.toLowerCase().includes('auth')) {
      responseText += "The IAM module is active. Two-step verification (TOTP) and the 2-device session limiting limits are fully active."
    } else {
      responseText += "I am ready to help you write code, deploy pipelines, or manage your CRM billing."
    }

    aiMessages.value.push({ role: 'assistant', text: responseText })
    
    nextTick(() => {
      if (chatHistoryEl.value) {
        chatHistoryEl.value.scrollTop = chatHistoryEl.value.scrollHeight
      }
    })
  }, 600)
}

const triggerPalettePrompt = () => {
  const event = new KeyboardEvent('keydown', {
    key: 'k',
    ctrlKey: true,
    metaKey: true,
    bubbles: true
  })
  window.dispatchEvent(event)
}
</script>

<style scoped>
.dashboard-page {
  min-height: 100vh;
  background-color: #09090b;
  color: #fff;
  padding: 1.5rem 2rem;
  position: relative;
  overflow-x: hidden;
}

/* Glowing backdrops */
.background-glows {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1;
}

.glow {
  position: absolute;
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(140px);
  opacity: 0.08;
}

.glow-1 {
  background: #6366f1;
  top: -200px;
  left: -200px;
}

.glow-2 {
  background: #3b82f6;
  bottom: -200px;
  right: -200px;
}

/* Header design */
.dashboard-header {
  position: relative;
  z-index: 2;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  padding-bottom: 1.25rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.logo-icon {
  font-size: 1.6rem;
}

.logo-text {
  font-size: 1.3rem;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.badge {
  background: rgba(99, 102, 241, 0.15);
  color: #a5b4fc;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-weight: 500;
  font-size: 0.75rem;
  border: 1px solid rgba(99, 102, 241, 0.3);
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.25rem;
}

/* Tab buttons */
.nav-tabs {
  display: flex;
  background: rgba(255, 255, 255, 0.03);
  padding: 0.25rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.nav-tabs button {
  background: transparent;
  border: none;
  color: #a1a1aa;
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
  font-weight: 500;
  border-radius: 8px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s ease;
}

.nav-tabs button.active {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}

.shortcut-tip {
  font-size: 0.85rem;
  color: #71717a;
  background: rgba(255, 255, 255, 0.03);
  padding: 0.5rem 1rem;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.05);
  cursor: pointer;
  display: flex;
  gap: 0.25rem;
  align-items: center;
}

kbd {
  background: #27272a;
  padding: 0.1rem 0.35rem;
  border-radius: 4px;
  font-size: 0.75rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #e4e4e7;
}

.action-btn {
  background: transparent;
  color: #d4d4d8;
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.5rem 1.25rem;
  border-radius: 12px;
  cursor: pointer;
  font-family: inherit;
  font-weight: 500;
  transition: all 0.2s ease;
}

.action-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}

.action-btn.edit-active {
  background: rgba(239, 68, 68, 0.15);
  border-color: rgba(239, 68, 68, 0.3);
  color: #fca5a5;
}

.save-btn {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #fff;
  border: none;
  padding: 0.5rem 1.25rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
}

/* Tab 1: Grid Layout styling */
.grid-container {
  position: relative;
  z-index: 2;
  display: grid;
  grid-template-columns: repeat(12, 1fr);
  grid-auto-rows: 90px;
  gap: 1.5rem;
  transition: all 0.3s ease;
}

.grid-container.edit-mode {
  background: rgba(255, 255, 255, 0.01);
  border-radius: 16px;
  outline: 2px dashed rgba(99, 102, 241, 0.3);
  padding: 1rem;
}

.grid-widget {
  background: rgba(15, 23, 42, 0.35);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.5);
  transition: transform 0.2s ease, border-color 0.2s ease;
}

.grid-widget:hover {
  border-color: rgba(255, 255, 255, 0.12);
  transform: translateY(-2px);
}

/* Widget headers & bodies */
.widget-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  background: rgba(255, 255, 255, 0.01);
}

.widget-title {
  font-weight: 600;
  font-size: 0.95rem;
  color: #e4e4e7;
}

.widget-badge {
  font-size: 0.7rem;
  background: rgba(34, 197, 94, 0.12);
  color: #86efac;
  padding: 0.15rem 0.5rem;
  border-radius: 9999px;
  font-weight: 500;
}

.edit-arrows {
  display: flex;
  gap: 0.25rem;
}

.edit-arrows button {
  background: #27272a;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff;
  border-radius: 6px;
  padding: 0.15rem 0.4rem;
  font-size: 0.8rem;
  cursor: pointer;
}

.edit-arrows button:hover {
  background: #3f3f46;
}

.widget-body {
  flex: 1;
  padding: 1.25rem;
  overflow-y: auto;
}

/* Widget Content Styles (AI Chat, Sprint, Git, Invoices) */
.ai-widget-content {
  display: flex;
  flex-direction: column;
  height: 100%;
}

.chat-history {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.chat-bubble {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  font-size: 0.9rem;
}

.chat-bubble .avatar {
  background: #27272a;
  padding: 0.35rem;
  border-radius: 8px;
}

.chat-bubble p {
  margin: 0;
  background: rgba(255, 255, 255, 0.03);
  padding: 0.6rem 0.9rem;
  border-radius: 12px;
  color: #d4d4d8;
  max-width: 85%;
}

.chat-bubble.user p {
  background: rgba(99, 102, 241, 0.1);
  color: #e0e7ff;
  border: 1px solid rgba(99, 102, 241, 0.15);
}

.chat-input-wrapper {
  display: flex;
  gap: 0.5rem;
}

.chat-input-wrapper input {
  flex: 1;
  background: rgba(0, 0, 0, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.6rem 1rem;
  border-radius: 10px;
  color: #fff;
  font-size: 0.85rem;
}

.chat-input-wrapper button {
  background: #6366f1;
  color: #fff;
  border: none;
  padding: 0 1rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
}

.task-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.task-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.65rem 1rem;
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 10px;
}

.task-left {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.task-code {
  font-size: 0.75rem;
  font-weight: 600;
  color: #818cf8;
  background: rgba(129, 140, 248, 0.1);
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
}

.task-title {
  font-size: 0.85rem;
}

.status-pill {
  font-size: 0.75rem;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-weight: 500;
}

.status-pill.deployed {
  background: rgba(34, 197, 94, 0.1);
  color: #4ade80;
}

.status-pill.in-progress {
  background: rgba(234, 179, 8, 0.1);
  color: #facc15;
}

.status-pill.to-do {
  background: rgba(255, 255, 255, 0.05);
  color: #a1a1aa;
}

.branch-status {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.pr-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pr-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8rem;
}

.pr-status-icon.approved { color: #22c55e; }
.pr-status-icon.merged { color: #a855f7; }
.pr-status-icon.pending { color: #eab308; }

.metrics-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.metric-card {
  background: rgba(255, 255, 255, 0.02);
  border: 1px solid rgba(255, 255, 255, 0.04);
  border-radius: 12px;
  padding: 0.75rem 1rem;
}

.metric-val {
  font-size: 1.3rem;
  font-weight: 700;
}

.metric-lbl {
  font-size: 0.75rem;
  color: #71717a;
}

.recent-invoice {
  font-size: 0.8rem;
  background: rgba(34, 197, 94, 0.05);
  border: 1px solid rgba(34, 197, 94, 0.1);
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
}

/* TAB 2: Settings and Security Section styling */
.settings-container {
  position: relative;
  z-index: 2;
  width: 100%;
}

.settings-grid {
  display: grid;
  grid-template-columns: 1.2fr 1fr;
  gap: 2rem;
}

.settings-card {
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 24px;
  padding: 2.5rem;
  box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7);
  display: flex;
  flex-direction: column;
}

.settings-card h3 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.02em;
}

.section-desc {
  color: #71717a;
  font-size: 0.85rem;
  margin-top: 0;
  margin-bottom: 2rem;
}

/* DORA Grid styling */
.dora-metrics-summary {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.dora-metric-pill {
  background: rgba(9, 9, 11, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.06);
  padding: 1.25rem;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  align-items: center;
}

.dora-metric-pill .num {
  font-size: 1.8rem;
  font-weight: 700;
  color: #818cf8;
}

.dora-metric-pill .lbl {
  font-size: 0.75rem;
  color: #71717a;
}

.quality-audit-sec h4 {
  font-size: 0.95rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #e4e4e7;
}

.health-metrics-list {
  background: rgba(9, 9, 11, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  overflow: hidden;
}

.health-metric {
  display: flex;
  justify-content: space-between;
  padding: 0.85rem 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 0.85rem;
}

.health-metric:last-child {
  border-bottom: none;
}

.health-metric .lbl {
  color: #a1a1aa;
}

.health-metric .val {
  font-weight: 500;
  color: #fff;
}

.health-metric .val.success { color: #4ade80; }
.health-metric .val.warning { color: #f87171; }
.health-metric .val.rating-a {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  padding: 0.15rem 0.5rem;
  border-radius: 6px;
  font-weight: 700;
}

/* Two-Factor verification styling */
.two-factor-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  background: rgba(9, 9, 11, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 18px;
  padding: 2rem;
}

.state-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 1.25rem;
}

.active-state .state-icon {
  background: rgba(34, 197, 94, 0.15);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.3);
}

.inactive-state .state-icon {
  background: rgba(234, 179, 8, 0.15);
  color: #facc15;
  border: 1px solid rgba(234, 179, 8, 0.3);
}

.state-desc h4 {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.state-desc p {
  color: #a1a1aa;
  font-size: 0.85rem;
  margin: 0 0 1.5rem 0;
}

.enable-btn {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #fff;
  border: none;
  padding: 0.65rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
}

.disable-btn {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  color: #fca5a5;
  padding: 0.65rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  font-family: inherit;
}

.qr-code-wrapper {
  background: #fff;
  padding: 1rem;
  border-radius: 14px;
  display: inline-block;
  margin: 1.5rem 0;
  text-align: center;
}

.qr-code-wrapper img {
  width: 180px;
  height: 180px;
  display: block;
}

.secret-display {
  color: #09090b;
  font-size: 0.8rem;
  margin-top: 0.75rem;
  font-family: monospace;
}

.verify-2fa-form {
  display: flex;
  gap: 0.5rem;
  width: 100%;
  max-width: 320px;
  margin-bottom: 1rem;
}

.verify-2fa-form input {
  flex: 1;
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.6rem 1rem;
  border-radius: 8px;
  color: #fff;
  text-align: center;
  font-size: 1.1rem;
  letter-spacing: 0.1em;
}

.verify-2fa-form button {
  background: #4ade80;
  color: #09090b;
  border: none;
  padding: 0 1rem;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
}

.cancel-setup-btn {
  background: transparent;
  border: none;
  color: #71717a;
  cursor: pointer;
  font-size: 0.8rem;
}

.recovery-codes-card {
  margin-top: 1.5rem;
  background: rgba(99, 102, 241, 0.1);
  border: 1px solid rgba(99, 102, 241, 0.2);
  padding: 1.25rem;
  border-radius: 12px;
  text-align: left;
}

.recovery-codes-card h5 {
  font-size: 0.9rem;
  margin: 0 0 0.5rem 0;
  color: #a5b4fc;
}

.recovery-codes-card p {
  font-size: 0.75rem;
  color: #a1a1aa;
  margin: 0 0 1rem 0;
  line-height: 1.4;
}

.codes-list {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.5rem;
  background: rgba(0, 0, 0, 0.3);
  padding: 0.75rem;
  border-radius: 8px;
  font-family: monospace;
  font-size: 0.75rem;
  margin-bottom: 1rem;
}

.dismiss-codes {
  background: rgba(255, 255, 255, 0.1);
  border: none;
  color: #fff;
  padding: 0.4rem 1rem;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
}

/* Active sessions row styling */
.active-sessions-panel {
  grid-column: span 2;
  margin-top: 1rem;
}

.sessions-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.session-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  background: rgba(9, 9, 11, 0.4);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 14px;
}

.session-row.current-session {
  border-color: rgba(99, 102, 241, 0.3);
  background: rgba(99, 102, 241, 0.03);
}

.session-details {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.device-name {
  font-weight: 600;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.current-badge {
  background: rgba(99, 102, 241, 0.15);
  color: #a5b4fc;
  font-size: 0.7rem;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  font-weight: 500;
}

.device-meta {
  font-size: 0.75rem;
  color: #71717a;
  margin-top: 0.25rem;
}

.revoke-btn {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s ease;
}

.revoke-btn:hover {
  background: rgba(239, 68, 68, 0.2);
}
</style>
