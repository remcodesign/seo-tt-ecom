<script setup lang="ts">
import AppNav from './AppNav.vue';
</script>

<template>
  <header class="border-b border-[#19140035] dark:border-[#3E3E3A] shadow-xs">
    <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <router-link
        :to="{ name: 'home' }"
        class="text-lg font-semibold tracking-tight"
      >
        SEO TT Ecom
      </router-link>

      <AppNav />
    </div>
  </header>
</template>