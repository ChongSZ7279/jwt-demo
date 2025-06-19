<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">
        {{ isEditing ? 'Edit Item' : 'Add New Item' }}
      </h1>
      <p class="mt-2 text-gray-600">
        {{ isEditing ? 'Update the item details below' : 'Fill in the details to add a new inventory item' }}
      </p>
    </div>
    
    <!-- Loading State -->
    <div v-if="loading && isEditing" class="flex justify-center py-12">
      <svg class="animate-spin h-8 w-8 text-primary-600" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
    </div>
    
    <!-- Form -->
    <div v-else class="card max-w-2xl">
      <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Name -->
          <div class="md:col-span-2">
            <label for="name" class="block text-sm font-medium text-gray-700">
              Item Name *
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="input-field mt-1"
              placeholder="Enter item name"
            />
          </div>
          
          <!-- Description -->
          <div class="md:col-span-2">
            <label for="description" class="block text-sm font-medium text-gray-700">
              Description
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="3"
              class="input-field mt-1"
              placeholder="Enter item description"
            ></textarea>
          </div>
          
          <!-- Category -->
          <div>
            <label for="category" class="block text-sm font-medium text-gray-700">
              Category *
            </label>
            <select
              id="category"
              v-model="form.category"
              required
              class="input-field mt-1"
            >
              <option value="">Select a category</option>
              <option value="Electronics">Electronics</option>
              <option value="Accessories">Accessories</option>
              <option value="Clothing">Clothing</option>
              <option value="Books">Books</option>
              <option value="Home & Garden">Home & Garden</option>
              <option value="Sports">Sports</option>
              <option value="Other">Other</option>
            </select>
          </div>
          
          <!-- SKU -->
          <div>
            <label for="sku" class="block text-sm font-medium text-gray-700">
              SKU *
            </label>
            <input
              id="sku"
              v-model="form.sku"
              type="text"
              required
              class="input-field mt-1"
              placeholder="Enter SKU"
            />
          </div>
          
          <!-- Quantity -->
          <div>
            <label for="quantity" class="block text-sm font-medium text-gray-700">
              Quantity *
            </label>
            <input
              id="quantity"
              v-model.number="form.quantity"
              type="number"
              min="0"
              required
              class="input-field mt-1"
              placeholder="Enter quantity"
            />
          </div>
          
          <!-- Price -->
          <div>
            <label for="price" class="block text-sm font-medium text-gray-700">
              Price *
            </label>
            <div class="relative mt-1">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500 sm:text-sm">$</span>
              </div>
              <input
                id="price"
                v-model.number="form.price"
                type="number"
                min="0"
                step="0.01"
                required
                class="input-field pl-7"
                placeholder="0.00"
              />
            </div>
          </div>
        </div>
        
        <!-- Error Message -->
        <div v-if="error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ error }}</p>
        </div>
        
        <!-- Form Actions -->
        <div class="flex justify-end space-x-4">
          <router-link
            to="/dashboard/inventory"
            class="btn-secondary"
          >
            Cancel
          </router-link>
          <button
            type="submit"
            :disabled="submitting"
            class="btn-primary flex items-center"
          >
            <svg
              v-if="submitting"
              class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            {{ submitting ? (isEditing ? 'Updating...' : 'Creating...') : (isEditing ? 'Update Item' : 'Create Item') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { inventoryAPI } from '../services/api'

export default {
  name: 'InventoryForm',
  setup() {
    const route = useRoute()
    const router = useRouter()
    
    const loading = ref(false)
    const submitting = ref(false)
    const error = ref('')
    
    const form = reactive({
      name: '',
      description: '',
      category: '',
      sku: '',
      quantity: 0,
      price: 0
    })
    
    const isEditing = computed(() => route.params.id !== undefined)
    
    const loadItem = async (id) => {
      loading.value = true
      error.value = ''
      
      try {
        const response = await inventoryAPI.getById(id)
        const item = response.data
        
        form.name = item.name
        form.description = item.description
        form.category = item.category
        form.sku = item.sku
        form.quantity = parseInt(item.quantity)
        form.price = parseFloat(item.price)
      } catch (err) {
        error.value = 'Failed to load item details'
        console.error('Error loading item:', err)
      } finally {
        loading.value = false
      }
    }
    
    const validateForm = () => {
      const errors = []

      if (!form.name || form.name.trim() === '') {
        errors.push('Name is required')
      }

      if (!form.description || form.description.trim() === '') {
        errors.push('Description is required')
      }

      if (!form.category || form.category.trim() === '') {
        errors.push('Category is required')
      }

      if (!form.sku || form.sku.trim() === '') {
        errors.push('SKU is required')
      }

      if (form.quantity === null || form.quantity === undefined || form.quantity < 0) {
        errors.push('Quantity must be 0 or greater')
      }

      if (form.price === null || form.price === undefined || form.price < 0) {
        errors.push('Price must be 0 or greater')
      }

      return errors
    }

    const handleSubmit = async () => {
      submitting.value = true
      error.value = ''

      // Validate form before submitting
      const validationErrors = validateForm()
      if (validationErrors.length > 0) {
        error.value = validationErrors.join(', ')
        submitting.value = false
        return
      }

      try {
        // Prepare clean data object
        const submitData = {
          name: form.name.trim(),
          description: form.description.trim(),
          category: form.category.trim(),
          sku: form.sku.trim(),
          quantity: parseInt(form.quantity) || 0,
          price: parseFloat(form.price) || 0
        }

        console.log('Submitting data:', submitData)

        if (isEditing.value) {
          await inventoryAPI.update(route.params.id, submitData)
        } else {
          await inventoryAPI.create(submitData)
        }

        router.push('/dashboard/inventory')
      } catch (err) {
        error.value = err.response?.data?.error || 'Failed to save item'
        console.error('Error saving item:', err)
        console.error('Full error response:', err.response)
      } finally {
        submitting.value = false
      }
    }
    
    onMounted(() => {
      if (isEditing.value) {
        loadItem(route.params.id)
      }
    })
    
    return {
      form,
      loading,
      submitting,
      error,
      isEditing,
      handleSubmit,
      validateForm
    }
  }
}
</script> 