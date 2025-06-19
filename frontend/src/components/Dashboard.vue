<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center">
            <h1 class="text-xl font-semibold text-gray-900">
              Inventory Management System
            </h1>
          </div>
          
          <div class="flex items-center space-x-4">
            <span class="text-sm text-gray-700">
              Welcome, {{ user?.username }}
            </span>
            <button
              @click="handleLogout"
              class="btn-secondary text-sm"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </nav>
    
    <!-- Sidebar and Main Content -->
    <div class="flex">
      <!-- Sidebar -->
      <div class="w-64 bg-white shadow-sm min-h-screen">
        <nav class="mt-8">
          <div class="px-4 space-y-2">
            <router-link
              to="/dashboard/inventory"
              class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200"
              :class="[
                $route.path === '/dashboard/inventory'
                  ? 'bg-primary-50 text-primary-700'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              ]"
            >
              <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              Inventory
            </router-link>
            
            <router-link
              to="/dashboard/inventory/add"
              class="flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200"
              :class="[
                $route.path === '/dashboard/inventory/add'
                  ? 'bg-primary-50 text-primary-700'
                  : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
              ]"
            >
              <svg class="mr-3 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              Add Item
            </router-link>
          </div>
        </nav>
      </div>
      
      <!-- Main Content -->
      <div class="flex-1 p-8">
        <router-view />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authAPI } from '../services/api'

export default {
  name: 'Dashboard',
  setup() {
    const router = useRouter()
    const user = ref(null)
    
    onMounted(() => {
      const userData = localStorage.getItem('user')
      if (userData) {
        user.value = JSON.parse(userData)
      }
    })
    
    const handleLogout = async () => {
      try {
        await authAPI.logout()
      } catch (error) {
        console.error('Logout error:', error)
      } finally {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        router.push('/login')
      }
    }
    
    return {
      user,
      handleLogout
    }
  }
}
</script> 