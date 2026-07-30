<template>
  <div class="admin-page">
    <div class="background-glows">
      <div class="glow glow-1"></div>
      <div class="glow glow-2"></div>
    </div>

    <div class="admin-container">
      <!-- Header -->
      <header class="admin-header">
        <div class="header-left">
          <NuxtLink to="/dashboard" class="back-link">← Back to Workspace</NuxtLink>
          <h1>Super Admin Dashboard</h1>
          <p class="subtitle">Real-time platform telemetry, active billing subscriptions, and user registration insights.</p>
        </div>
        <button @click="fetchAdminMetrics" class="refresh-btn" :disabled="loading">
          <span v-if="loading" class="spinner"></span>
          <span v-else>↻ Refresh Stats</span>
        </button>
      </header>

      <!-- Loading State -->
      <div v-if="loading && !metrics" class="loading-state">
        <div class="spinner-large"></div>
        <p>Fetching platform metrics...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <span class="error-icon">⚠️</span>
        <h3>Failed to load admin metrics</h3>
        <p>{{ error }}</p>
        <button @click="fetchAdminMetrics" class="retry-btn">Try Again</button>
      </div>

      <!-- Metrics Content -->
      <div v-else-if="metrics" class="metrics-content">
        <!-- 4 Stats Cards Grid -->
        <div class="stats-grid">
          <!-- Card 1: Users -->
          <div class="metric-card card-purple">
            <div class="card-glow"></div>
            <div class="card-header">
              <span class="icon">👥</span>
              <span class="label">Total Registered Users</span>
            </div>
            <div class="value">{{ metrics.total_registered_users }}</div>
            <div class="trend">Active on system</div>
          </div>

          <!-- Card 2: Workspaces -->
          <div class="metric-card card-blue">
            <div class="card-glow"></div>
            <div class="card-header">
              <span class="icon">🏢</span>
              <span class="label">Active Workspaces</span>
            </div>
            <div class="value">{{ metrics.total_workspaces }}</div>
            <div class="trend">Company orgs created</div>
          </div>

          <!-- Card 3: Paid Subscribers -->
          <div class="metric-card card-green">
            <div class="card-glow"></div>
            <div class="card-header">
              <span class="icon">💳</span>
              <span class="label">Active Paid Subscribers</span>
            </div>
            <div class="value">{{ metrics.total_active_subscribers }}</div>
            <div class="trend">Workspaces on active plans</div>
          </div>

          <!-- Card 4: Platform Revenue -->
          <div class="metric-card card-gold">
            <div class="card-glow"></div>
            <div class="card-header">
              <span class="icon">💰</span>
              <span class="label">Total Platform Revenue</span>
            </div>
            <div class="value">${{ parseFloat(metrics.total_invoices_paid_amount).toFixed(2) }}</div>
            <div class="trend">Stripe Connect payouts</div>
          </div>
        </div>

        <!-- Subscriptions Table Section -->
        <div class="table-section">
          <h2>Active Platform Subscriptions</h2>
          <div class="table-wrapper">
            <table v-if="metrics.active_subscriptions && metrics.active_subscriptions.length > 0">
              <thead>
                <tr>
                  <th>Workspace Name</th>
                  <th>Stripe Price/Plan ID</th>
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
            <!-- Empty state for subscriptions -->
            <div v-else class="empty-table">
              <span class="empty-icon">📂</span>
              <p>No active paid subscriptions found in database yet.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const loading = ref(false)
const error = ref('')
const metrics = ref(null)

const getApiUrl = (path) => {
  if (typeof window !== 'undefined') {
    return `http://${window.location.hostname}:8000${path}`
  }
  return `http://localhost:8000${path}`
}

const fetchAdminMetrics = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await fetch(getApiUrl('/api/admin/metrics'), {
      headers: {
        'Accept': 'application/json'
      }
    })
    if (!response.ok) {
      throw new Error('Server returned an error status.')
    }
    metrics.value = await response.json()
  } catch (err) {
    error.value = err.message || 'Failed to fetch admin stats. Ensure backend is running.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAdminMetrics()
})
</script>

<style scoped>
.admin-page {
  min-height: 100vh;
  background-color: #09090b;
  position: relative;
  overflow-x: hidden;
  padding: 3rem 1.5rem;
  color: #fafafa;
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
  background: #22c55e;
  bottom: -150px;
  right: 10%;
}

.admin-container {
  max-width: 1200px;
  margin: 0 auto;
  position: relative;
  z-index: 2;
}

/* Header style */
.admin-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 3rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding-bottom: 2rem;
}

.back-link {
  color: #a1a1aa;
  text-decoration: none;
  font-size: 0.9rem;
  transition: color 0.2s;
  display: inline-block;
  margin-bottom: 0.75rem;
}

.back-link:hover {
  color: #a855f7;
}

h1 {
  font-size: 2.25rem;
  font-weight: 700;
  letter-spacing: -0.04em;
  margin: 0 0 0.5rem 0;
  background: linear-gradient(135deg, #ffffff 40%, #e9d5ff 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.subtitle {
  color: #71717a;
  margin: 0;
  font-size: 1rem;
}

.refresh-btn {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #e4e4e7;
  padding: 0.65rem 1.25rem;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.refresh-btn:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
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
  padding: 3rem;
  border-radius: 20px;
  color: #fca5a5;
  margin: 2rem 0;
}

.error-icon {
  font-size: 2.5rem;
  display: block;
  margin-bottom: 1rem;
}

.error-state h3 {
  font-size: 1.25rem;
  margin: 0 0 0.5rem 0;
}

.error-state p {
  color: #71717a;
  margin: 0 0 1.5rem 0;
}

.retry-btn {
  background: #ef4444;
  color: #fff;
  border: none;
  padding: 0.6rem 1.25rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

/* Stats Cards Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 3.5rem;
}

.metric-card {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 20px;
  padding: 1.75rem;
  position: relative;
  overflow: hidden;
  box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.4);
  transition: transform 0.3s ease, border-color 0.3s ease;
}

.metric-card:hover {
  transform: translateY(-2px);
  border-color: rgba(255, 255, 255, 0.12);
}

.card-glow {
  position: absolute;
  top: -50px;
  right: -50px;
  width: 150px;
  height: 150px;
  border-radius: 50%;
  filter: blur(40px);
  opacity: 0.08;
  pointer-events: none;
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.card-header .icon {
  font-size: 1.25rem;
}

.card-header .label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #71717a;
  letter-spacing: 0.02em;
  text-transform: uppercase;
}

.metric-card .value {
  font-size: 2.25rem;
  font-weight: 700;
  letter-spacing: -0.04em;
  margin-bottom: 0.5rem;
}

.metric-card .trend {
  font-size: 0.8rem;
  color: #71717a;
}

/* Card theme variations */
.card-purple .card-glow { background: #a855f7; }
.card-purple:hover { border-color: rgba(168, 85, 247, 0.3); }

.card-blue .card-glow { background: #3b82f6; }
.card-blue:hover { border-color: rgba(59, 130, 246, 0.3); }

.card-green .card-glow { background: #22c55e; }
.card-green:hover { border-color: rgba(34, 197, 94, 0.3); }

.card-gold .card-glow { background: #eab308; }
.card-gold:hover { border-color: rgba(234, 179, 8, 0.3); }

/* Table Section style */
.table-section {
  background: rgba(255, 255, 255, 0.015);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 24px;
  padding: 2rem;
  box-shadow: 0 15px 40px -15px rgba(0, 0, 0, 0.5);
}

.table-section h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-top: 0;
  margin-bottom: 1.5rem;
  letter-spacing: -0.02em;
}

.table-wrapper {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

th {
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
  color: #71717a;
  padding: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  letter-spacing: 0.03em;
}

td {
  padding: 1.2rem 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  font-size: 0.95rem;
  color: #e4e4e7;
}

tr:last-child td {
  border-bottom: none;
}

.workspace-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 500;
}

.avatar {
  background: rgba(168, 85, 247, 0.15);
  color: #c084fc;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
}

.price-code {
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-family: monospace;
  font-size: 0.85rem;
  color: #cbd5e1;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
}

.badge-active {
  background: rgba(34, 197, 94, 0.1);
  color: #4ade80;
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.badge-pending {
  background: rgba(234, 179, 8, 0.1);
  color: #fde047;
  border: 1px solid rgba(234, 179, 8, 0.2);
}

/* Empty Table state */
.empty-table {
  text-align: center;
  padding: 4rem 2rem;
  color: #71717a;
}

.empty-icon {
  font-size: 2rem;
  display: block;
  margin-bottom: 0.75rem;
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
