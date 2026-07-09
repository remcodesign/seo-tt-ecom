<script setup lang="ts" />

<template>
  <div
    class="overflow-hidden rounded-xl border border-[#19140035] bg-white shadow-xs transition-shadow hover:shadow-md dark:border-[#3E3E3A] dark:bg-[#161615]"
    v-bind="$attrs"
  >
    <slot />
  </div>
</template>
