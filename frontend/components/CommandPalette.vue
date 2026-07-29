<template>
  <div v-if="isOpen" class="palette-overlay" @click.self="closePalette">
    <div class="palette-modal">
      <div class="search-wrapper">
        <span class="search-icon">🔍</span>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Type a command or search..."
          ref="searchInput"
          @keydown.down.prevent="navigateDown"
          @keydown.up.prevent="navigateUp"
          @keydown.enter.prevent="executeSelected"
        />
        <span class="esc-badge">ESC</span>
      </div>

      <div class="results-wrapper">
        <div v-if="filteredGroups.length === 0" class="no-results">
          No commands found matching "{{ searchQuery }}"
        </div>

        <div v-else v-for="group in filteredGroups" :key="group.title" class="group-section">
          <div class="group-title">{{ group.title }}</div>
          <div class="group-items">
            <div 
              v-for="item in group.items" 
              :key="item.id" 
              class="palette-item"
              :class="{ active: item === selectedItem }"
              @mouseenter="selectedItem = item"
              @click="executeItem(item)"
            >
              <div class="item-left">
                <span class="item-icon">{{ item.icon }}</span>
                <span class="item-name">{{ item.name }}</span>
              </div>
              <span v-if="item.shortcut" class="item-shortcut">{{ item.shortcut }}</span>
            </div>
          </div>
        </div>
      </div>

      <div class="palette-footer">
        <span>↑↓ to navigate</span>
        <span>↵ to select</span>
        <span>esc to close</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from '#app'

const router = useRouter()
const isOpen = ref(false)
const searchQuery = ref('')
const searchInput = ref(null)
const selectedItem = ref(null)

// Hardcoded Command Palette items list
const commands = [
  {
    group: 'Navigation',
    items: [
      { id: 'nav_dashboard', name: 'Go to Dashboard', icon: '📊', action: () => router.push('/dashboard') },
      { id: 'nav_login', name: 'Go to Sign In', icon: '🔑', action: () => router.push('/login') },
      { id: 'nav_register', name: 'Go to Register', icon: '📝', action: () => router.push('/register') }
    ]
  },
  {
    group: 'Actions',
    items: [
      { id: 'action_theme', name: 'Toggle Dark / Light Mode', icon: '🌓', shortcut: '⌘T', action: () => toggleTheme() },
      { id: 'action_logout', name: 'Sign Out / Log Out', icon: '🚪', shortcut: '⌘L', action: () => logoutUser() }
    ]
  },
  {
    group: 'AI Helper',
    items: [
      { id: 'ai_chat', name: 'Ask DevOS AI Assistant...', icon: '🤖', action: () => focusAIChat() }
    ]
  }
]

// Open / Close helper
const openPalette = () => {
  isOpen.value = true
  searchQuery.value = ''
  nextTick(() => {
    if (searchInput.value) {
      searchInput.value.focus()
    }
  })
}

const closePalette = () => {
  isOpen.value = false
}

// Event Listeners for Cmd/Ctrl + K
const handleKeyDown = (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault()
    if (isOpen.value) {
      closePalette()
    } else {
      openPalette()
    }
  }

  if (e.key === 'Escape' && isOpen.value) {
    closePalette()
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})

// Fuzzy search filtering
const filteredGroups = computed(() => {
  if (!searchQuery.value) return commands

  const query = searchQuery.value.toLowerCase()
  return commands
    .map(group => {
      const matchedItems = group.items.filter(item => 
        item.name.toLowerCase().includes(query) || 
        group.group.toLowerCase().includes(query)
      )
      return { ...group, items: matchedItems }
    })
    .filter(group => group.items.length > 0)
})

// Navigation inside results
const flatItems = computed(() => {
  return filteredGroups.value.reduce((acc, group) => acc.concat(group.items), [])
})

watch(flatItems, (newVal) => {
  if (newVal.length > 0) {
    selectedItem.value = newVal[0]
  } else {
    selectedItem.value = null
  }
}, { immediate: true })

const navigateDown = () => {
  const items = flatItems.value
  if (items.length === 0) return
  const currentIndex = items.indexOf(selectedItem.value)
  const nextIndex = (currentIndex + 1) % items.length
  selectedItem.value = items[nextIndex]
}

const navigateUp = () => {
  const items = flatItems.value
  if (items.length === 0) return
  const currentIndex = items.indexOf(selectedItem.value)
  const prevIndex = (currentIndex - 1 + items.length) % items.length
  selectedItem.value = items[prevIndex]
}

const executeSelected = () => {
  if (selectedItem.value) {
    executeItem(selectedItem.value)
  }
}

const executeItem = (item) => {
  closePalette()
  if (item.action) {
    item.action()
  }
}

// Action Implementations
const toggleTheme = () => {
  const currentBg = document.body.style.backgroundColor
  if (currentBg === 'rgb(250, 250, 250)' || currentBg === 'white') {
    document.body.style.backgroundColor = '#09090b'
    document.body.style.color = '#fafafa'
  } else {
    document.body.style.backgroundColor = 'white'
    document.body.style.color = '#09090b'
  }
  alert('Theme toggled!')
}

const logoutUser = () => {
  alert('Logging out from session...')
  router.push('/login')
}

const focusAIChat = () => {
  router.push('/dashboard')
  setTimeout(() => {
    const aiInput = document.getElementById('ai-prompt-input')
    if (aiInput) aiInput.focus()
  }, 100)
}
</script>

<style scoped>
.palette-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(8px);
  z-index: 1000;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding-top: 10vh;
  padding-left: 1rem;
  padding-right: 1rem;
}

.palette-modal {
  background: #18181b; /* Zinc 900 */
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  width: 100%;
  max-width: 600px;
  box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.8);
  overflow: hidden;
}

.search-wrapper {
  display: flex;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  gap: 1rem;
}

.search-icon {
  font-size: 1.2rem;
  color: #71717a;
}

input {
  flex: 1;
  background: transparent;
  border: none;
  color: #fff;
  font-size: 1.1rem;
  font-family: inherit;
}

input:focus {
  outline: none;
}

.esc-badge {
  background: #27272a;
  color: #a1a1aa;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.results-wrapper {
  max-height: 350px;
  overflow-y: auto;
  padding: 1rem 0.5rem;
}

.no-results {
  padding: 2rem;
  text-align: center;
  color: #71717a;
  font-size: 0.95rem;
}

.group-section {
  margin-bottom: 1.25rem;
}

.group-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #71717a;
  padding: 0 1rem;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.group-items {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.palette-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.palette-item.active {
  background: rgba(255, 255, 255, 0.05);
}

.item-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.item-icon {
  font-size: 1.2rem;
}

.item-name {
  font-size: 0.95rem;
  color: #e4e4e7;
}

.palette-item.active .item-name {
  color: #fff;
}

.item-shortcut {
  color: #52525b;
  font-size: 0.8rem;
  font-weight: 500;
}

.palette-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: 0.75rem 1.5rem;
  background: #111113;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  gap: 1.5rem;
  font-size: 0.75rem;
  color: #52525b;
}
</style>
