<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Inventory Management</h1>
      <p class="mt-2 text-gray-600">Manage your inventory items</p>
    </div>
    
    <!-- Search and Actions -->
    <div class="mb-6 flex flex-col sm:flex-row gap-4">
      <div class="flex-1">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search items..."
            class="input-field pl-10"
            @input="handleSearch"
          />
          <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>
      
      <router-link to="/dashboard/inventory/add" class="btn-primary flex items-center">
        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Add Item
      </router-link>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="card">
      <div class="text-center">
        <p class="text-red-600">{{ error }}</p>
        <button @click="loadInventory" class="btn-primary mt-4">Try Again</button>
      </div>
    </div>
    
    <!-- Inventory Table -->
    <div v-else class="card">
      <div v-if="filteredItems.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No items found</h3>
        <p class="mt-1 text-sm text-gray-500">
          {{ searchQuery ? 'Try adjusting your search terms.' : 'Get started by adding your first inventory item.' }}
        </p>
      </div>
      
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div>
                  <div class="text-sm font-medium text-gray-900">{{ item.name }}</div>
                  <div class="text-sm text-gray-500">{{ item.description }}</div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                  {{ item.category }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="[
                  'text-sm font-medium',
                  item.quantity > 10 ? 'text-green-600' : item.quantity > 0 ? 'text-yellow-600' : 'text-red-600'
                ]">
                  {{ item.quantity }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                ${{ parseFloat(item.price).toFixed(2) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ item.sku }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end space-x-2">
                  <router-link
                    :to="`/dashboard/inventory/edit/${item.id}`"
                    class="text-primary-600 hover:text-primary-900"
                  >
                    Edit
                  </router-link>
                  <button
                    @click="deleteItem(item.id)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { inventoryAPI } from '../services/api'

export default {
  name: 'InventoryList',
  setup() {
    const items = ref([])
    const loading = ref(false)
    const error = ref('')
    const searchQuery = ref('')
    
    const filteredItems = computed(() => {
      if (!searchQuery.value) return items.value
      
      const query = searchQuery.value.toLowerCase()
      return items.value.filter(item => 
        item.name.toLowerCase().includes(query) ||
        item.description.toLowerCase().includes(query) ||
        item.category.toLowerCase().includes(query) ||
        item.sku.toLowerCase().includes(query)
      )
    })
    
    const loadInventory = async () => {
      loading.value = true
      error.value = ''
      
      try {
        const response = await inventoryAPI.getAll()
        items.value = response.data
      } catch (err) {
        error.value = 'Failed to load inventory items'
        console.error('Error loading inventory:', err)
      } finally {
        loading.value = false
      }
    }
    
    const deleteItem = async (id) => {
      if (!confirm('Are you sure you want to delete this item?')) return
      
      try {
        await inventoryAPI.delete(id)
        items.value = items.value.filter(item => item.id !== id)
      } catch (err) {
        alert('Failed to delete item')
        console.error('Error deleting item:', err)
      }
    }
    
    const handleSearch = () => {
      // Search is handled by computed property
    }
    
    onMounted(() => {
      loadInventory()
    })
    
    return {
      items,
      loading,
      error,
      searchQuery,
      filteredItems,
      loadInventory,
      deleteItem,
      handleSearch
    }
  }
}
</script> 