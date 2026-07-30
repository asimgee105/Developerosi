<template>
  <div class="auth-page">
    <div class="background-glows">
      <div class="glow glow-1"></div>
      <div class="glow glow-2"></div>
    </div>

    <div class="auth-card">
      <div class="logo">
        <span class="logo-icon">⚡</span>
        <span class="logo-text">DevOS</span>
      </div>

      <h2>Create your Account</h2>
      <p class="subtitle">Get your enterprise-grade workspace and AI coding environment ready in seconds.</p>

      <form @submit.prevent="handleRegister" class="auth-form">
        <div class="form-group">
          <label for="name">Full Name</label>
          <input 
            v-model="form.name" 
            type="text" 
            id="name" 
            placeholder="John Doe" 
            required 
            :disabled="loading"
          />
        </div>

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
          <label for="password">Password</label>
          <input 
            v-model="form.password" 
            type="password" 
            id="password" 
            placeholder="••••••••••••" 
            required 
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="password_confirmation">Confirm Password</label>
          <input 
            v-model="form.password_confirmation" 
            type="password" 
            id="password_confirmation" 
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
          <span v-else>Register & Provision Workspace</span>
        </button>
      </form>

      <div class="auth-footer">
        Already have an account? <NuxtLink to="/login" class="link">Sign In</NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from '#app'

const router = useRouter()
const loading = ref(false)
const error = ref('')

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: ''
})

const getApiUrl = (path) => {
  if (typeof window !== 'undefined') {
    return `http://${window.location.hostname}:8000${path}`
  }
  return `http://localhost:8000${path}`
}

const handleRegister = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await fetch(getApiUrl('/register'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(form)
    })

    const data = await response.json()

    if (!response.ok) {
      throw new Error(data.message || 'Registration failed. Please try again.')
    }

    // Success - redirect to login page
    router.push('/login?registered=true')
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
  background: #6366f1; /* Indigo */
  top: -100px;
  left: -100px;
}

.glow-2 {
  background: #38bdf8; /* Sky */
  bottom: -100px;
  right: -100px;
}

/* Glassmorphic signup card */
.auth-card {
  position: relative;
  z-index: 2;
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(24px);
  border: 1px solid rgba(255, 255, 255, 0.08);
  padding: 3rem;
  border-radius: 28px;
  width: 100%;
  max-width: 500px;
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
  animation: pulse 2s infinite ease-in-out;
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
  transition: all 0.25s ease;
  font-family: inherit;
}

input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
  background: rgba(9, 9, 11, 0.8);
}

/* Custom error alerts */
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

/* Submit Button */
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
  transition: color 0.2s ease;
}

.link:hover {
  color: #a5b4fc;
}

/* Loading spinner */
.spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: #fff;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.05); }
}
</style>
