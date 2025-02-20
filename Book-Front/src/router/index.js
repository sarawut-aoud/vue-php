
/**
 * router/index.ts
 *
 * Automatic routes for `./src/pages/*.vue`
 */

// Composables

import { createRouter, createWebHistory } from 'vue-router/auto'
import AdminUsers from '@/pages/admin/users.vue';
import Login from '@/pages/auth/index.vue';
// import AdminView from '/admin/users.vue'
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes:[
    {
      path:'/login',component:Login
    },
    {
      path:'/admin',
      children: [
        { path: 'users', component:AdminUsers },
        // { path: 'users', component:  },
        // { path: 'users/:id', component:  },
      ], 
    }
  ],
})

// Workaround for https://github.com/vitejs/vite/issues/11804
router.onError((err, to) => {
  if (err?.message?.includes?.('Failed to fetch dynamically imported module')) {
    if (!localStorage.getItem('vuetify:dynamic-reload')) {
      console.log('Reloading page to fix dynamic import error')
      localStorage.setItem('vuetify:dynamic-reload', 'true')
      location.assign(to.fullPath)
    } else {
      console.error('Dynamic import error, reloading page did not fix it', err)
    }
  } else {
    console.error(err)
  }
})

router.isReady().then(() => {
  localStorage.removeItem('vuetify:dynamic-reload')
})

export default router
