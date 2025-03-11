
/**
 * router/index.ts
 *
 * Automatic routes for `./src/pages/*.vue`
 */

// Composables
import {useCookie} from '@/composables/useCookie';
import { useJWT } from '@/composables/useJWT';
import {ref} from 'vue';

import { createRouter, createWebHistory } from 'vue-router/auto'
import AdminUsers from '@/pages/admin/index.vue';
import Login from '@/pages/auth/index.vue';
import Index from '@/pages/index.vue';
import Register from '@/pages/auth/register.vue';
import Information from '@/pages/users/information.vue';
import Payments from '@/pages/users/Payments.vue';
import Historys from '@/pages/users/history.vue';

const {getCookie} = useCookie();
const {getItem} = useJWT();
const items = ref(getItem(getCookie('jwt')));
// import AdminView from '/admin/users.vue'
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes:[
    {
      path:'/',
      component:Index,
      name:"Home"
    },
    {
      path:'/login',component:Login,
      name:"Login"
    },
    {
      path:'/register',component:Register,
      name:"Register"
    },
    {
      path:'/users',
      name:"Users",
      children: [
        { path: 'info', component:Information ,name:"UserInfo"},
         { path: 'history', component:Historys ,ame:"Historys"},
      
      ], 
      meta: { requiresAuth: true ,role:'emp'} // 🟢 Public Route
    },
    {
      path:'/Payments',
      name:"Payments",
      component:Payments,
      meta: { requiresAuth: true ,role:'emp'} // 🟢 Public Route
    },
    {
      path:'/admin',component:AdminUsers,
      meta: { requiresAuth: true ,role:'admin'} // 🟢 Public Route
    }
  ],
})
router.beforeEach((to, from, next) => {
  const userRole = items?.value?.n; // 'admin' หรือ 'user'

  if (to.meta.requiresAuth) {
    if (!userRole) {
      next({ name: 'Home' }); // ❌ ไม่ได้ Login
    } else if (to.meta.role && to.meta.role !== userRole) {
      next({ name: 'Home' }); // ❌ Role ไม่ตรง
    } else {
      next(); // ✅ ผ่านได้
    }
  } else {
    next(); // ✅ Public Route
  }
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
