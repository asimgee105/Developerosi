<template>
  <div class="admin-page">
    <div class="background-glows">
      <div class="glow glow-1"></div>
      <div class="glow glow-2"></div>
    </div>

    <!-- Passcode Verification Screen -->
    <div v-if="!isAuthenticated" class="auth-overlay">
      <div class="auth-card">
        <div class="logo">
          <span class="logo-icon">👑</span>
          <span class="logo-text">DevOS Super Admin</span>
        </div>
        <h2>Admin Authentication</h2>
        <p class="subtitle">Enter the secret passcode to access visual filesystem, database execution, user management, and API catalogs.</p>
        
        <form @submit.prevent="verifyPasscode" class="auth-form">
          <div class="form-group">
            <label for="passcode">Admin Passcode</label>
            <input 
              v-model="passcode" 
              type="password" 
              id="passcode" 
              placeholder="••••••••••••" 
              required 
              autofocus
            />
          </div>
          <div v-if="authError" class="alert error-alert">
            <span class="alert-icon">⚠️</span>
            <span class="alert-text">{{ authError }}</span>
          </div>
          <button type="submit" class="submit-btn">Verify Identity</button>
        </form>
      </div>
    </div>

    <!-- Admin Console Dashboard -->
    <div v-else class="admin-container">
      <!-- Header -->
      <header class="admin-header">
        <div class="header-left">
          <NuxtLink to="/dashboard" class="back-link">← Back to Workspace</NuxtLink>
          <h1>DevOS Central Control Console</h1>
          <p class="subtitle">Full system virtualization, database querying, filesystem overrides, user credit allocation, and route listings.</p>
        </div>
        
        <div class="header-actions">
          <div class="admin-tabs">
            <button @click="switchTab('stats')" :class="{ active: activeTab === 'stats' }">📊 Stats</button>
            <button @click="switchTab('users')" :class="{ active: activeTab === 'users' }">👥 Users</button>
            <button @click="switchTab('files')" :class="{ active: activeTab === 'files' }">📂 File Manager</button>
            <button @click="switchTab('database')" :class="{ active: activeTab === 'database' }">🛢️ SQL Console</button>
            <button @click="switchTab('routes')" :class="{ active: activeTab === 'routes' }">🔌 API Routes</button>
          </div>
          <button @click="handleRefresh" class="refresh-btn" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span v-else>↻ Refresh</span>
          </button>
          <button @click="logoutAdmin" class="logout-btn">🔒 Lock</button>
        </div>
      </header>

      <!-- TAB 1: Stats -->
      <div v-if="activeTab === 'stats'" class="tab-content">
        <div v-if="loading && !metrics" class="loading-state">
          <div class="spinner-large"></div>
          <p>Fetching stats...</p>
        </div>

        <div v-else-if="error" class="error-state">
          <span class="error-icon">⚠️</span>
          <h3>Failed to load metrics</h3>
          <p>{{ error }}</p>
        </div>

        <div v-else-if="metrics" class="metrics-wrapper">
          <div class="stats-grid">
            <div class="metric-card card-purple">
              <div class="card-glow"></div>
              <div class="card-header">
                <span class="icon">👥</span>
                <span class="label">Total Registered Users</span>
              </div>
              <div class="value">{{ metrics.total_registered_users }}</div>
              <div class="trend">Registered accounts</div>
            </div>

            <div class="metric-card card-blue">
              <div class="card-glow"></div>
              <div class="card-header">
                <span class="icon">🏢</span>
                <span class="label">Workspaces</span>
              </div>
              <div class="value">{{ metrics.total_workspaces }}</div>
              <div class="trend">Created organizations</div>
            </div>

            <div class="metric-card card-green">
              <div class="card-glow"></div>
              <div class="card-header">
                <span class="icon">💳</span>
                <span class="label">Paid Subscribers</span>
              </div>
              <div class="value">{{ metrics.total_active_subscribers }}</div>
              <div class="trend">Active Stripe subscriptions</div>
            </div>

            <div class="metric-card card-gold">
              <div class="card-glow"></div>
              <div class="card-header">
                <span class="icon">💰</span>
                <span class="label">Gross Revenue</span>
              </div>
              <div class="value">${{ parseFloat(metrics.total_invoices_paid_amount).toFixed(2) }}</div>
              <div class="trend">Total collected billing</div>
            </div>
          </div>

          <!-- Subscriptions Table -->
          <div class="table-section">
            <h2>Active Platform Subscriptions</h2>
            <div class="table-wrapper">
              <table v-if="metrics.active_subscriptions && metrics.active_subscriptions.length > 0">
                <thead>
                  <tr>
                    <th>Workspace Name</th>
                    <th>Stripe Price Plan ID</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(sub, index) in metrics.active_subscriptions" :key="index">
                    <td class="workspace-cell">
                      <span class="avatar">⚡</span>
                      {{ sub.workspace_name }}
                    </td>
                    <td><code class="price-code">{{ sub.stripe_price_id }}</code></td>
                    <td>
                      <span class="badge" :class="sub.status === 'active' ? 'badge-active' : 'badge-pending'">
                        {{ sub.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="empty-table">
                <span class="empty-icon">📂</span>
                <p>No active subscriptions found in the database.</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: Users Management -->
      <div v-else-if="activeTab === 'users'" class="tab-content users-tab">
        <div class="table-section">
          <div class="table-header-row">
            <h2>Registered Developer Accounts</h2>
            <p class="desc">Instantly review accounts signed up in the local MySQL database. Double click to allocate credits.</p>
          </div>
          
          <div v-if="loadingUsers" class="loading-state">
            <div class="spinner-large"></div>
            <p>Loading developer accounts...</p>
          </div>

          <div v-else-if="usersList && usersList.length > 0" class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Created At</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="user in usersList" :key="user.id">
                  <td class="code-font">{{ user.id }}</td>
                  <td><strong>{{ user.name }}</strong></td>
                  <td>{{ user.email }}</td>
                  <td>{{ user.created_at }}</td>
                  <td>
                    <button @click="showCreditPrompt(user.id, user.name)" class="action-table-btn">Alloc Credits</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="empty-table">
            <span class="empty-icon">👥</span>
            <p>No user accounts returned from database.</p>
          </div>
        </div>
      </div>

      <!-- TAB 3: File Manager -->
      <div v-else-if="activeTab === 'files'" class="tab-content files-tab">
        <div class="filemanager-layout">
          <!-- File tree side list -->
          <div class="file-sidebar">
            <div class="sidebar-header">
              <h3>Backend Codebase</h3>
              <input v-model="fileSearch" type="text" placeholder="Filter files..." class="search-input" />
            </div>
            <div class="file-list">
              <div 
                v-for="file in filteredFiles" 
                :key="file.path" 
                @click="selectFile(file.path)"
                class="file-item"
                :class="{ active: selectedFilePath === file.path }"
              >
                <span class="file-icon">📄</span>
                <div class="file-meta">
                  <div class="file-name">{{ file.name }}</div>
                  <div class="file-path">{{ file.path }}</div>
                </div>
              </div>
              <div v-if="filteredFiles.length === 0" class="empty-sidebar">
                No matching files found.
              </div>
            </div>
          </div>

          <!-- Code Editor Panel -->
          <div class="editor-panel">
            <div class="editor-header" v-if="selectedFilePath">
              <div class="active-file">
                <span class="icon">📝</span>
                <div>
                  <h4>{{ selectedFilePath.split('/').pop() }}</h4>
                  <p>{{ selectedFilePath }}</p>
                </div>
              </div>
              <button @click="saveFileContent" class="save-file-btn" :disabled="savingFile">
                <span v-if="savingFile" class="spinner"></span>
                <span v-else>💾 Save Code Changes</span>
              </button>
            </div>
            
            <div class="editor-body" v-if="selectedFilePath">
              <textarea 
                v-model="selectedFileContent" 
                class="code-textarea" 
                spellcheck="false"
              ></textarea>
            </div>

            <div class="editor-placeholder" v-else>
              <span class="icon">📂</span>
              <h3>Filesystem Editor</h3>
              <p>Select a Laravel backend file from the repository sidebar to view and edit its code live in the browser.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 4: SQL Database Executor -->
      <div v-else-if="activeTab === 'database'" class="tab-content database-tab">
        <div class="db-layout">
          <!-- SQL query input box -->
          <div class="query-box">
            <div class="box-header">
              <h3>SQL Query Console</h3>
              <button @click="runQuery" class="run-query-btn" :disabled="runningQuery">
                <span v-if="runningQuery" class="spinner"></span>
                <span v-else>⚡ Run SQL Query</span>
              </button>
            </div>
            <textarea 
              v-model="sqlQuery" 
              placeholder="e.g. SELECT * FROM users LIMIT 10; OR SHOW TABLES;" 
              class="sql-textarea"
              spellcheck="false"
            ></textarea>
            <div class="quick-helpers">
              Quick commands: 
              <button @click="setSql('SHOW TABLES;')">Show Tables</button>
              <button @click="setSql('SELECT * FROM users LIMIT 5;')">Select Users</button>
              <button @click="setSql('SELECT * FROM organizations LIMIT 5;')">Select Workspaces</button>
              <button @click="setSql('SELECT * FROM billing_subscriptions LIMIT 5;')">Select Subs</button>
            </div>
          </div>

          <!-- SQL Results panel -->
          <div class="results-box">
            <h3>Query Results</h3>
            <div v-if="queryError" class="query-error">
              <span class="icon">⚠️</span>
              <p>{{ queryError }}</p>
            </div>
            <div v-else-if="queryResults && queryResults.length > 0" class="results-table-wrapper">
              <table>
                <thead>
                  <tr>
                    <th v-for="key in Object.keys(queryResults[0])" :key="key">{{ key }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in queryResults" :key="idx">
                    <td v-for="(val, colIdx) in Object.values(row)" :key="colIdx">
                      <span v-if="val === null" class="null-val">NULL</span>
                      <span v-else-if="typeof val === 'object'" class="object-val">{{ JSON.stringify(val) }}</span>
                      <span v-else>{{ val }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="empty-results">
              <span class="icon">📋</span>
              <p>Write an SQL query above and click "Run Query" to fetch database records.</p>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5: API Routes Catalog -->
      <div v-else-if="activeTab === 'routes'" class="tab-content routes-tab">
        <div class="table-section">
          <div class="table-header-row">
            <h2>Laravel API Endpoints Catalog</h2>
            <input v-model="routeSearch" type="text" placeholder="Search endpoints (e.g. api/v1)..." class="route-search-input" />
          </div>
          
          <div v-if="loadingRoutes" class="loading-state">
            <div class="spinner-large"></div>
            <p>Scanning backend routes catalog...</p>
          </div>

          <div v-else-if="filteredRoutes && filteredRoutes.length > 0" class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Method</th>
                  <th>URI / Endpoint</th>
                  <th>Action Handler</th>
                  <th>Route Name</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(route, idx) in filteredRoutes" :key="idx">
                  <td>
                    <span class="method-badge" :class="route.method.toLowerCase().split('|')[0]">
                      {{ route.method }}
                    </span>
                  </td>
                  <td><code class="uri-code">{{ route.uri }}</code></td>
                  <td><code class="action-code">{{ route.action }}</code></td>
                  <td class="name-col">{{ route.name }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="empty-table">
            <span class="empty-icon">🔌</span>
            <p>No active routes matched search query.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const activeTab = ref('stats') // stats, users, files, database, routes
const loading = ref(false)
const error = ref('')

// Super Admin auth state
const isAuthenticated = ref(false)
const passcode = ref('')
const authError = ref('')

// Telemetry state
const metrics = ref(null)

// File Manager state
const filesList = ref([])
const fileSearch = ref('')
const selectedFilePath = ref('')
const selectedFileContent = ref('')
const loadingFile = ref(false)
const savingFile = ref(false)

// SQL Database state
const sqlQuery = ref('SHOW TABLES;')
const runningQuery = ref(false)
const queryResults = ref(null)
const queryError = ref('')

// Dynamic users list
const usersList = ref([])
const loadingUsers = ref(false)

// Dynamic API routes catalog
const routesList = ref([])
const routeSearch = ref('')
const loadingRoutes = ref(false)

const getApiUrl = (path) => {
  if (typeof window !== 'undefined') {
    return `http://${window.location.hostname}:8000${path}`
  }
  return `http://localhost:8000${path}`
}

const handleRefresh = () => {
  if (activeTab.value === 'stats') {
    fetchAdminMetrics()
  } else if (activeTab.value === 'users') {
    fetchUsersList()
  } else if (activeTab.value === 'files') {
    fetchFilesList()
  } else if (activeTab.value === 'routes') {
    fetchRoutesList()
  }
}

const switchTab = (tab) => {
  activeTab.value = tab
  handleRefresh()
}

// Get admin secret passcode from session storage
const getAdminSecret = () => {
  if (typeof window !== 'undefined') {
    return window.sessionStorage.getItem('devos_admin_secret') || ''
  }
  return ''
}

// Verify Passcode
const verifyPasscode = async () => {
  authError.value = ''
  try {
    const response = await fetch(getApiUrl('/api/admin/metrics'), {
      headers: { 
        'Accept': 'application/json',
        'X-Admin-Secret': passcode.value
      }
    })
    if (response.status === 401) {
      throw new Error('Invalid secret passcode.')
    }
    if (!response.ok) {
      throw new Error('Connection error.')
    }
    
    // Save in session storage
    if (typeof window !== 'undefined') {
      window.sessionStorage.setItem('devos_admin_secret', passcode.value)
    }
    isAuthenticated.value = true
    metrics.value = await response.json()
    fetchFilesList()
  } catch (err) {
    authError.value = err.message
  }
}

const logoutAdmin = () => {
  if (typeof window !== 'undefined') {
    window.sessionStorage.removeItem('devos_admin_secret')
  }
  isAuthenticated.value = false
  passcode.value = ''
  metrics.value = null
  filesList.value = []
  usersList.value = []
  routesList.value = []
}

// 1. Fetch Platform Stats
const fetchAdminMetrics = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await fetch(getApiUrl('/api/admin/metrics'), {
      headers: { 
        'Accept': 'application/json',
        'X-Admin-Secret': getAdminSecret()
      }
    })
    if (response.status === 401) {
      logoutAdmin()
      throw new Error('Session expired or unauthorized.')
    }
    if (!response.ok) throw new Error('Server metrics endpoint offline.')
    metrics.value = await response.json()
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

// 2. Fetch Users List dynamically using SQL Executor background query
const fetchUsersList = async () => {
  loadingUsers.value = true
  try {
    const response = await fetch(getApiUrl('/api/admin/db/query'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Admin-Secret': getAdminSecret()
      },
      body: JSON.stringify({ query: 'SELECT id, name, email, created_at FROM users;' })
    })
    if (response.ok) {
      const data = await response.json()
      usersList.value = data.results
    }
  } catch (err) {
    console.error('Failed to fetch users list', err)
  } finally {
    loadingUsers.value = false
  }
}

// Alloc credits helper helper popup
const showCreditPrompt = async (userId, name) => {
  const credits = prompt(`Enter credits amount to allocate for user ${name}:`, '100')
  if (!credits) return
  
  const query = `UPDATE users SET credits = credits + ${parseInt(credits)} WHERE id = ${userId};`
  try {
    const response = await fetch(getApiUrl('/api/admin/db/query'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Admin-Secret': getAdminSecret()
      },
      body: JSON.stringify({ query })
    })
    if (response.ok) {
      alert(`Successfully added ${credits} credits to ${name}'s account!`)
      fetchUsersList()
    }
  } catch (err) {
    alert('Query failed.')
  }
}

// 3. Fetch File tree list
const fetchFilesList = async () => {
  loading.value = true
  try {
    const response = await fetch(getApiUrl('/api/admin/files'), {
      headers: { 
        'Accept': 'application/json',
        'X-Admin-Secret': getAdminSecret()
      }
    })
    if (response.status === 401) {
      logoutAdmin()
      return
    }
    if (response.ok) {
      const data = await response.json()
      filesList.value = data.files
    }
  } catch (err) {
    console.error('Failed to load file tree', err)
  } finally {
    loading.value = false
  }
}

const filteredFiles = computed(() => {
  if (!fileSearch.value) return filesList.value
  const s = fileSearch.value.toLowerCase()
  return filesList.value.filter(f => f.path.toLowerCase().includes(s))
})

const selectFile = async (path) => {
  selectedFilePath.value = path
  selectedFileContent.value = ''
  loadingFile.value = true
  try {
    const response = await fetch(getApiUrl(`/api/admin/files/content?path=${encodeURIComponent(path)}`), {
      headers: { 
        'Accept': 'application/json',
        'X-Admin-Secret': getAdminSecret()
      }
    })
    if (response.ok) {
      const data = await response.json()
      selectedFileContent.value = data.content
    }
  } catch (err) {
    alert('Failed to load file content.')
  } finally {
    loadingFile.value = false
  }
}

const saveFileContent = async () => {
  if (!selectedFilePath.value) return
  savingFile.value = true
  try {
    const response = await fetch(getApiUrl('/api/admin/files/content'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Admin-Secret': getAdminSecret()
      },
      body: JSON.stringify({
        path: selectedFilePath.value,
        content: selectedFileContent.value
      })
    })
    if (response.ok) {
      alert('File changes written to disk successfully!')
    } else {
      const data = await response.json()
      alert('Failed to save file: ' + (data.message || 'Unknown error'))
    }
  } catch (err) {
    alert('Failed to save file changes.')
  } finally {
    savingFile.value = false
  }
}

// 4. Fetch dynamic routes
const fetchRoutesList = async () => {
  loadingRoutes.value = true
  try {
    const response = await fetch(getApiUrl('/api/admin/routes'), {
      headers: { 
        'Accept': 'application/json',
        'X-Admin-Secret': getAdminSecret()
      }
    })
    if (response.ok) {
      const data = await response.json()
      routesList.value = data.routes
    }
  } catch (err) {
    console.error('Failed to load route catalog', err)
  } finally {
    loadingRoutes.value = false
  }
}

const filteredRoutes = computed(() => {
  if (!routeSearch.value) return routesList.value
  const s = routeSearch.value.toLowerCase()
  return routesList.value.filter(r => 
    r.uri.toLowerCase().includes(s) || 
    r.method.toLowerCase().includes(s) || 
    r.action.toLowerCase().includes(s)
  )
})

// 5. Database Execution methods
const setSql = (sql) => {
  sqlQuery.value = sql
}

const runQuery = async () => {
  if (!sqlQuery.value.trim()) return
  runningQuery.value = true
  queryResults.value = null
  queryError.value = ''
  
  try {
    const response = await fetch(getApiUrl('/api/admin/db/query'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Admin-Secret': getAdminSecret()
      },
      body: JSON.stringify({ query: sqlQuery.value })
    })
    
    const data = await response.json()
    if (response.ok) {
      queryResults.value = data.results
    } else {
      queryError.value = data.error || 'SQL execution failed.'
    }
  } catch (err) {
    queryError.value = 'Failed to execute query. Connection refused.'
  } finally {
    runningQuery.value = false
  }
}

onMounted(() => {
  const secret = getAdminSecret()
  if (secret) {
    isAuthenticated.value = true
    fetchAdminMetrics()
    fetchFilesList()
  }
})
</script>

<style scoped>
.admin-page {
  min-height: 100vh;
  background-color: #09090b;
  position: relative;
  overflow-x: hidden;
  padding: 2.5rem 1.5rem;
  color: #fafafa;
  font-family: Inter, system-ui, sans-serif;
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
  width: 500px;
  height: 500px;
  border-radius: 50%;
  filter: blur(120px);
  opacity: 0.08;
}

.glow-1 {
  background: #a855f7;
  top: -150px;
  left: 10%;
}

.glow-2 {
  background: #3b82f6;
  bottom: -150px;
  right: 10%;
}

/* Auth Challenge Panel */
.auth-overlay {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 4rem 1rem;
  min-height: 80vh;
  position: relative;
  z-index: 10;
}

.auth-card {
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 3rem;
  border-radius: 28px;
  width: 100%;
  max-width: 480px;
  box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8);
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.logo-icon { font-size: 1.8rem; }
.logo-text {
  font-weight: 700;
  font-size: 1.6rem;
  background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.auth-card h2 {
  font-size: 1.5rem;
  text-align: center;
  margin: 0 0 0.5rem 0;
}

.auth-card .subtitle {
  color: #a1a1aa;
  text-align: center;
  font-size: 0.9rem;
  line-height: 1.6;
  margin: 0 0 2rem 0;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #d4d4d8;
}

input {
  background: rgba(9, 9, 11, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.75rem 1rem;
  border-radius: 12px;
  color: #fff;
  font-size: 0.95rem;
}

input:focus {
  outline: none;
  border-color: #a855f7;
}

.alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 1.25rem;
  border-radius: 12px;
  font-size: 0.9rem;
}

.error-alert {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #fca5a5;
}

.submit-btn {
  background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
  color: #fff;
  border: none;
  padding: 0.85rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}

.submit-btn:hover {
  opacity: 0.95;
}

.admin-container {
  max-width: 1250px;
  margin: 0 auto;
  position: relative;
  z-index: 2;
}

/* Header style */
.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 2.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 1.5rem;
}

.back-link {
  color: #a1a1aa;
  text-decoration: none;
  font-size: 0.9rem;
  transition: color 0.2s;
  display: inline-block;
  margin-bottom: 0.5rem;
}

.back-link:hover {
  color: #a855f7;
}

h1 {
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.04em;
  margin: 0 0 0.25rem 0;
  background: linear-gradient(135deg, #ffffff 40%, #c084fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.subtitle {
  color: #71717a;
  margin: 0;
  font-size: 0.95rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

/* Tab Navigation Buttons */
.admin-tabs {
  display: flex;
  background: rgba(255, 255, 255, 0.03);
  padding: 0.25rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.admin-tabs button {
  background: transparent;
  border: none;
  color: #a1a1aa;
  padding: 0.5rem 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
}

.admin-tabs button.active {
  background: rgba(168, 85, 247, 0.15);
  color: #d8b4fe;
}

.refresh-btn {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #e4e4e7;
  padding: 0.5rem 1rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.refresh-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
  color: #fff;
}

.logout-btn {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.2);
  color: #fca5a5;
  padding: 0.5rem 1rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.logout-btn:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #fff;
}

/* Loading state */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 0;
  color: #71717a;
}

.spinner-large {
  width: 40px;
  height: 40px;
  border: 3px solid rgba(168, 85, 247, 0.15);
  border-top-color: #a855f7;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

/* Error state */
.error-state {
  text-align: center;
  background: rgba(239, 68, 68, 0.03);
  border: 1px solid rgba(239, 68, 68, 0.1);
  padding: 2.5rem;
  border-radius: 20px;
  color: #fca5a5;
}

/* Stats view */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.25rem;
  margin-bottom: 3rem;
}

.metric-card {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 1.5rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.4);
}

.card-glow {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 150px;
  height: 150px;
  border-radius: 50%;
  filter: blur(40px);
  opacity: 0.06;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.card-header .label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #71717a;
  text-transform: uppercase;
}

.metric-card .value {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.metric-card .trend {
  font-size: 0.8rem;
  color: #71717a;
}

.card-purple .card-glow { background: #a855f7; }
.card-blue .card-glow { background: #3b82f6; }
.card-green .card-glow { background: #22c55e; }
.card-gold .card-glow { background: #eab308; }

.table-section {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  padding: 1.75rem;
}

.table-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.table-header-row h2 {
  font-size: 1.15rem;
  font-weight: 600;
  margin: 0;
}

.table-header-row .desc {
  font-size: 0.85rem;
  color: #71717a;
  margin: 0;
}

.action-table-btn {
  background: rgba(168, 85, 247, 0.15);
  border: 1px solid rgba(168, 85, 247, 0.25);
  color: #d8b4fe;
  padding: 0.35rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.action-table-btn:hover {
  background: #a855f7;
  color: #fff;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #71717a;
  padding: 0.85rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  text-align: left;
}

td {
  padding: 1rem 0.85rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  font-size: 0.9rem;
}

.code-font {
  font-family: monospace;
  font-weight: 600;
}

.workspace-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.avatar {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
  width: 26px;
  height: 26px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
}

.price-code {
  background: rgba(255, 255, 255, 0.03);
  padding: 0.2rem 0.4rem;
  border-radius: 5px;
  font-family: monospace;
  font-size: 0.8rem;
}

.badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.badge-active {
  background: rgba(34, 197, 94, 0.1);
  color: #4ade80;
}

.badge-pending {
  background: rgba(234, 179, 8, 0.1);
  color: #fde047;
}

/* File Manager Tab */
.filemanager-layout {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 1.5rem;
  height: 65vh;
}

.file-sidebar {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-header {
  padding: 1.25rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.sidebar-header h3 {
  font-size: 1rem;
  margin: 0 0 0.75rem 0;
  color: #d4d4d8;
}

.search-input {
  width: 100%;
  background: rgba(9, 9, 11, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
  color: #fff;
  font-size: 0.85rem;
}

.search-input:focus {
  outline: none;
  border-color: #a855f7;
}

.file-list {
  flex: 1;
  overflow-y: auto;
  padding: 0.75rem;
}

.file-item {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.65rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
  margin-bottom: 0.25rem;
}

.file-item:hover {
  background: rgba(255, 255, 255, 0.03);
}

.file-item.active {
  background: rgba(168, 85, 247, 0.1);
  border: 1px solid rgba(168, 85, 247, 0.2);
}

.file-icon {
  font-size: 1rem;
}

.file-meta {
  min-width: 0;
}

.file-name {
  font-size: 0.85rem;
  font-weight: 500;
  color: #fff;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}

.file-path {
  font-size: 0.7rem;
  color: #71717a;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
  margin-top: 0.15rem;
}

.empty-sidebar {
  text-align: center;
  padding: 2rem 0;
  color: #71717a;
  font-size: 0.85rem;
}

/* Editor Panel */
.editor-panel {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.editor-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(0, 0, 0, 0.15);
}

.active-file {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.active-file h4 {
  font-size: 0.9rem;
  margin: 0;
  color: #fff;
}

.active-file p {
  font-size: 0.75rem;
  color: #71717a;
  margin: 0.15rem 0 0 0;
}

.save-file-btn {
  background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
  color: #fff;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.code-textarea {
  width: 100%;
  height: 100%;
  background: #09090b;
  border: none;
  color: #cbd5e1;
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.9rem;
  padding: 1.5rem;
  resize: none;
}

.code-textarea:focus {
  outline: none;
}

.editor-placeholder {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #71717a;
  text-align: center;
  padding: 2rem;
}

.editor-placeholder .icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.editor-placeholder h3 {
  font-size: 1.15rem;
  color: #d4d4d8;
  margin: 0 0 0.5rem 0;
}

.editor-placeholder p {
  max-width: 320px;
  font-size: 0.85rem;
  margin: 0;
  line-height: 1.5;
}

/* Database Tab */
.db-layout {
  display: grid;
  grid-template-rows: auto 1fr;
  gap: 1.5rem;
}

.query-box {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 1.5rem;
}

.box-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.box-header h3 {
  font-size: 1rem;
  margin: 0;
  color: #d4d4d8;
}

.run-query-btn {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  color: #fff;
  border: none;
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.sql-textarea {
  width: 100%;
  height: 100px;
  background: #09090b;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 10px;
  color: #10b981;
  font-family: 'Courier New', Courier, monospace;
  font-size: 0.95rem;
  padding: 1rem;
  resize: vertical;
}

.sql-textarea:focus {
  outline: none;
  border-color: #22c55e;
}

.quick-helpers {
  margin-top: 0.75rem;
  font-size: 0.8rem;
  color: #71717a;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.quick-helpers button {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  color: #cbd5e1;
  padding: 0.25rem 0.5rem;
  border-radius: 5px;
  font-size: 0.75rem;
  cursor: pointer;
}

.quick-helpers button:hover {
  background: rgba(255, 255, 255, 0.08);
  color: #fff;
}

.results-box {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 1.5rem;
  min-height: 250px;
  display: flex;
  flex-direction: column;
}

.results-box h3 {
  font-size: 1rem;
  margin: 0 0 1rem 0;
  color: #d4d4d8;
}

.query-error {
  background: rgba(239, 68, 68, 0.05);
  border: 1px solid rgba(239, 68, 68, 0.15);
  padding: 1.25rem;
  border-radius: 12px;
  color: #fca5a5;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.query-error p {
  margin: 0;
  font-size: 0.85rem;
  font-family: monospace;
}

.results-table-wrapper {
  overflow-x: auto;
  flex: 1;
}

.null-val {
  color: #ef4444;
  font-style: italic;
  font-weight: 600;
  font-size: 0.8rem;
}

.object-val {
  color: #a855f7;
  font-family: monospace;
  font-size: 0.8rem;
}

.empty-results {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: #71717a;
  text-align: center;
}

.empty-results .icon {
  font-size: 2.5rem;
  margin-bottom: 0.75rem;
}

.empty-results p {
  font-size: 0.85rem;
  margin: 0;
}

/* API Routes Catalog Tab */
.route-search-input {
  background: rgba(0, 0, 0, 0.3);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.5rem 1rem;
  border-radius: 10px;
  color: #fff;
  font-size: 0.85rem;
  width: 250px;
}

.route-search-input:focus {
  outline: none;
  border-color: #a855f7;
}

.method-badge {
  font-family: monospace;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  text-transform: uppercase;
}

.method-badge.get { background: rgba(34, 197, 94, 0.15); color: #4ade80; }
.method-badge.post { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
.method-badge.put { background: rgba(234, 179, 8, 0.15); color: #fde047; }
.method-badge.delete { background: rgba(239, 68, 68, 0.15); color: #fca5a5; }

.uri-code {
  font-family: monospace;
  font-weight: 600;
  color: #e4e4e7;
  font-size: 0.85rem;
}

.action-code {
  font-family: monospace;
  color: #a1a1aa;
  font-size: 0.8rem;
}

.name-col {
  color: #71717a;
  font-size: 0.85rem;
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
