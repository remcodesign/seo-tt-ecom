import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { library } from '@fortawesome/fontawesome-svg-core';
import { faTrash } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import App from './App.vue';
import '../css/app.css';

const app = createApp(App);

library.add(faTrash);
app.component('FontAwesomeIcon', FontAwesomeIcon);

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
        {
            path: '/blog/comments',
            name: 'comments.index',
            component: () => import('./pages/blog/CommentIndexPage.vue'),
        },
    ],
});

app.use(router);
app.mount('#app');