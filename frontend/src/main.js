import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import './style.css'

// Import components
import Login from './components/Login.vue'
import Dashboard from './components/Dashboard.vue'
import InventoryList from './components/InventoryList.vue'
import InventoryForm from './components/InventoryForm.vue'

// Create router
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: Login },
    { 
      path: '/dashboard', 
      component: Dashboard,
      meta: { requiresAuth: true },
      children: [
        { path: '', redirect: '/dashboard/inventory' },
        { path: 'inventory', component: InventoryList },
        { path: 'inventory/add', component: InventoryForm },
        { path: 'inventory/edit/:id', component: InventoryForm }
      ]
    }
  ]
})

// Navigation guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  if (to.meta.requiresAuth && !token) {
    next('/login')
  } else if (to.path === '/login' && token) {
    next('/dashboard')
  } else {
    next()
  }
})

const app = createApp(App)
app.use(router)
app.mount('#app') 