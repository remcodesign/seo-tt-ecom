import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import '../css/app.css';

const app = createApp(App);

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: () => import('./pages/HomePage.vue'),
        },
        {
            path: '/blog/posts',
            name: 'posts.index',
            component: () => import('./pages/blog/PostIndexPage.vue'),
        },
        {
            path: '/blog/posts/:slug',
            name: 'posts.show',
            component: () => import('./pages/blog/PostShowPage.vue'),
        },
    ],
});

app.use(router);
app.mount('#app');