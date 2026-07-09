<script setup lang="ts">
import FeatureComments from '@/components/blog/homepage/FeatureComments.vue';
import FeaturePosts from '@/components/blog/homepage/FeaturePosts.vue';
</script>

<template>
    <div>
        <FeaturePosts title="Latest Blog Posts" />
        <FeatureComments title="Latest Blog Comments" />
    </div>
</template>