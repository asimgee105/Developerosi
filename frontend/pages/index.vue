<script setup>
import { onMounted } from 'vue';
import { useRouter } from 'vue-router';

const router = useRouter();

onMounted(() => {
  // Redirect root page visits directly to login page
  router.push('/login');
});
</script>

<template>
  <div class="flex items-center justify-center min-h-screen bg-zinc-950 text-zinc-400">
    <div class="text-center">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-indigo-500 mx-auto mb-4"></div>
      <p class="text-sm">Redirecting to login...</p>
    </div>
  </div>
</template>
