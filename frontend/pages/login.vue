<template>
  <div class="auth-page">
    <div class="background-glows">
      <div class="glow glow-1"></div>
      <div class="glow glow-2"></div>
    </div>

    <!-- Login & 2FA Card -->
    <div class="auth-card">
      <div class="logo">
        <span class="logo-icon">⚡</span>
        <span class="logo-text">DevOS</span>
      </div>

      <!-- Section 1: Standard Login Form -->
      <div v-if="currentStep === 'login'">
        <h2>Sign In to DevOS</h2>
        <p class="subtitle">Access your unified AI developer workspace, tasks, and financials.</p>

        <!-- Registration Success Toast -->
        <div v-if="registeredSuccess" class="alert success-alert">
          <span class="alert-icon">✓</span>
          <span class="alert-text">Registration successful! Please sign in.</span>
        </div>

        <form @submit.prevent="handleLogin" class="auth-form">
          <div class="form-group">
            <label for="email">Email Address</label>
            <input 
              v-model="form.email" 
              type="email" 
              id="email" 
              placeholder="dev@example.com" 
              required 
              :disabled="loading"
            />
          </div>

          <div class="form-group">
            <div class="label-wrapper">
              <label for="password">Password</label>
              <a href="#" class="forgot-link">Forgot?</a>
            </div>
            <input 
              v-model="form.password" 
              type="password" 
              id="password" 
              placeholder="••••••••••••" 
              required 
              :disabled="loading"
            />
          </div>

          <div v-if="error" class="alert error-alert">
            <span class="alert-icon">⚠️</span>
            <span class="alert-text">{{ error }}</span>
          </div>

          <button type="submit" class="submit-btn" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Continue</span>
          </button>
        </form>

        <div class="auth-footer">
          Don't have an account? <NuxtLink to="/register" class="link">Create workspace</NuxtLink>
        </div>
      </div>

      <!-- Section 2: Two-Factor Authentication Challenge -->
      <div v-else-if="currentStep === 'two_factor'">
        <h2>2-Step Verification</h2>
        <p class="subtitle">Enter the 6-digit verification code from your authenticator app (Google Authenticator, Authy, etc.).</p>

        <form @submit.prevent="handle2FA" class="auth-form">
          <div class="form-group">
            <label for="code">Verification Code</label>
            <input 
              v-model="twoFactorCode" 
              type="text" 
              id="code" 
              placeholder="000 000" 
              maxlength="6"
              pattern="\d*"
              required 
              autofocus
              :disabled="loading"
              class="code-input"
            />
          </div>

          <div v-if="error" class="alert error-alert">
            <span class="alert-icon">⚠️</span>
            <span class="alert-text">{{ error }}</span>
          </div>

          <button type="submit" class="submit-btn" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Verify Identity</span>
          </button>

          <button type="button" @click="currentStep = 'login'" class="back-btn" :disabled="loading">
            Back to Sign In
          </button>
        </form>
      </div>
    </div>

    <!-- Section 3: Device Limit Warning Modal (Overlaps the screen) -->
    <div v-if="showDeviceLimitModal" class="modal-overlay">
      <div class="modal-card">
        <div class="warning-header">
          <span class="warning-icon">🚨</span>
          <h3>Device Limit Reached</h3>
        </div>
        
        <p class="modal-desc">
          Your DevOS plan restricts active sessions to a **maximum of 2 concurrent devices**. 
          You currently have 2 devices logged in:
        </p>

        <div class="devices-list">
          <div v-for="session in activeSessions" :key="session.id" class="device-item">
            <div class="device-details">
              <span class="device-icon">💻</span>
              <div>
                <div class="device-name">{{ session.device }}</div>
                <div class="device-meta">IP: {{ session.ip_address }} • Active: {{ session.last_active }}</div>
              </div>
            </div>
          </div>
        </div>

        <p class="modal-action-note">
          Would you like to terminate your oldest active device session and log in here?
        </p>

        <div class="modal-actions">
          <button @click="showDeviceLimitModal = false" class="cancel-btn">Cancel</button>
          <button @click="handleForceLogin" class="confirm-btn" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Terminate Oldest & Login</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from '#app'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const error = ref('')
const currentStep = ref('login') // 'login' or 'two_factor'

// Registration indicator
const registeredSuccess = ref(false)

// Form inputs
const form = reactive({
  email: '',
  password: '',
  remember: true
})

// 2FA Code
const twoFactorCode = ref('')

// Device Limiting State
const showDeviceLimitModal = ref(false)
const activeSessions = ref([])

onMounted(() => {
  if (route.query.registered === 'true') {
    registeredSuccess.value = true
  }
})

// Main Login handler
const handleLogin = async (forceLogin = false) => {
  loading.value = true
  error.value = ''

  try {
    const payload = { ...form }
    if (forceLogin) {
      payload.force = true
    }

    const response = await fetch('http://localhost:8000/api/auth/login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    })

    const data = await response.json()

    // Handle Active Device Limit Warning
    if (response.status === 423 && data.code === 'session_limit_exceeded') {
      activeSessions.value = data.sessions
      showDeviceLimitModal.value = true
      return
    }

    if (!response.ok) {
      throw new Error(data.message || 'Login failed. Please verify credentials.')
    }

    // Check if 2-Step verification is required
    if (data.two_factor) {
      currentStep.value = 'two_factor'
      showDeviceLimitModal.value = false
      return
    }

    // Login successful
    showDeviceLimitModal.value = false
    alert('Logged in successfully to Workspace: ' + (data.active_organization?.name || 'Personal'))
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}

// User confirms terminating the oldest device to login here
const handleForceLogin = async () => {
  await handleLogin(true)
}

// 2FA verification code submission
const handle2FA = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await fetch('http://localhost:8000/two-factor-challenge', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        code: twoFactorCode.value
      })
    })

    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.message || 'Invalid two-factor code.')
    }

    // Authenticated successfully!
    alert('Two-factor validation successful! Welcome back.')
    currentStep.value = 'login'
    twoFactorCode.value = ''
  } catch (err) {
    error.value = err.message
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: #09090b;
  position: relative;
  overflow: hidden;
  padding: 2rem 1rem;
}

/* Dynamic ambient glows */
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
  width: 400px;
  height: 400px;
  border-radius: 50%;
  filter: blur(100px);
  opacity: 0.12;
}

.glow-1 {
  background: #6366f1;
  top: -100px;
  left: -100px;
}

.glow-2 {
  background: #38bdf8;
  bottom: -100px;
  right: -100px;
}

/* Glassmorphic Login card */
.auth-card {
  position: relative;
  z-index: 2;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 3rem;
  border-radius: 28px;
  width: 100%;
  max-width: 460px;
  box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8);
}

.logo {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  margin-bottom: 2rem;
}

.logo-icon {
  font-size: 1.8rem;
}

.logo-text {
  font-weight: 700;
  font-size: 1.6rem;
  background: linear-gradient(135deg, #ffffff 30%, #a5b4fc 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

h2 {
  font-size: 1.75rem;
  font-weight: 600;
  text-align: center;
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.03em;
}

.subtitle {
  color: #a1a1aa;
  text-align: center;
  font-size: 0.95rem;
  line-height: 1.5;
  margin-top: 0;
  margin-bottom: 2.5rem;
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

.label-wrapper {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

label {
  font-size: 0.85rem;
  font-weight: 500;
  color: #d4d4d8;
}

.forgot-link {
  font-size: 0.8rem;
  color: #818cf8;
  text-decoration: none;
}

input {
  background: rgba(9, 9, 11, 0.6);
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 0.75rem 1rem;
  border-radius: 12px;
  color: #fff;
  font-size: 0.95rem;
  transition: all 0.25s ease;
  font-family: inherit;
}

input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
  background: rgba(9, 9, 11, 0.8);
}

.code-input {
  text-align: center;
  font-size: 1.5rem;
  letter-spacing: 0.25em;
  font-weight: 600;
}

/* Alerts */
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

.success-alert {
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.2);
  color: #86efac;
}

/* Action Buttons */
.submit-btn {
  background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
  color: #fff;
  border: none;
  padding: 0.85rem;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.25s ease;
  margin-top: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: inherit;
}

.submit-btn:hover:not(:disabled) {
  opacity: 0.95;
  transform: translateY(-1px);
  box-shadow: 0 10px 20px -10px rgba(99, 102, 241, 0.5);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.back-btn {
  background: transparent;
  color: #a1a1aa;
  border: 1px solid rgba(255, 255, 255, 0.12);
  padding: 0.75rem;
  border-radius: 12px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  font-family: inherit;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}

.auth-footer {
  margin-top: 2rem;
  text-align: center;
  font-size: 0.9rem;
  color: #71717a;
}

.link {
  color: #818cf8;
  text-decoration: none;
  font-weight: 500;
}

.link:hover {
  color: #a5b4fc;
}

/* Device Limits Warning Overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(12px);
  z-index: 100;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
}

.modal-card {
  background: #18181b; /* Zinc 900 */
  border: 1px solid rgba(239, 68, 68, 0.25);
  border-radius: 24px;
  width: 100%;
  max-width: 500px;
  padding: 2.5rem;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.9);
}

.warning-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.warning-icon {
  font-size: 2rem;
}

.modal-card h3 {
  font-size: 1.5rem;
  font-weight: 600;
  margin: 0;
  color: #fca5a5;
  letter-spacing: -0.02em;
}

.modal-desc {
  color: #d4d4d8;
  font-size: 0.95rem;
  line-height: 1.6;
  margin-bottom: 1.5rem;
}

.devices-list {
  background: #09090b;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 14px;
  overflow: hidden;
  margin-bottom: 1.5rem;
}

.device-item {
  padding: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.device-item:last-child {
  border-bottom: none;
}

.device-details {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.device-icon {
  font-size: 1.5rem;
  color: #71717a;
}

.device-name {
  font-weight: 500;
  font-size: 0.95rem;
  color: #fff;
}

.device-meta {
  font-size: 0.8rem;
  color: #71717a;
  margin-top: 0.15rem;
}

.modal-action-note {
  color: #a1a1aa;
  font-size: 0.9rem;
  line-height: 1.5;
  margin-bottom: 2rem;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
}

.cancel-btn {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #d4d4d8;
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  cursor: pointer;
  font-family: inherit;
  font-weight: 500;
}

.cancel-btn:hover {
  background: rgba(255, 255, 255, 0.05);
  color: #fff;
}

.confirm-btn {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  border: none;
  color: #fff;
  padding: 0.75rem 1.25rem;
  border-radius: 10px;
  cursor: pointer;
  font-family: inherit;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
}

.confirm-btn:hover:not(:disabled) {
  opacity: 0.95;
  box-shadow: 0 10px 20px -10px rgba(239, 68, 68, 0.5);
}

.confirm-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  width: 18px;
  height: 18px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
