<div class="p-6">
    <form @submit.prevent="saveProduct()">
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
                <input type="text" x-model="productForm.name" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea x-model="productForm.description" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Type</label>
                    <select x-model="productForm.category" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">Select Product Type</option>
                        <option value="REFILL">Refill (Gas Only)</option>
                        <option value="FULL_TANK">Full Tank (Gas + Cylinder)</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        <span x-show="productForm.category === 'REFILL'">Refill: Customer brings their own cylinder</span>
                        <span x-show="productForm.category === 'FULL_TANK'">Full Tank: Includes cylinder + gas, supports pickup/delivery</span>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Size Category</label>
                    <select x-model="productForm.category_id" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        <option value="">Select Size</option>
                        <template x-for="category in categories" :key="category.id">
                            <option :value="category.id" x-text="category.name"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price (AUD)</label>
                    <input type="number" step="0.01" x-model="productForm.price" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                    <input type="number" step="0.1" x-model="productForm.weight"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                    <input type="number" x-model="productForm.stock" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2">
                </div>

                <div class="flex items-center">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="productForm.is_active"
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                        <span class="ml-2 text-sm text-gray-700">Product is active</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                <input type="file" @change="handleImageUpload($event)" accept="image/*"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                <div x-show="imagePreview" class="mt-2">
                    <img :src="imagePreview" alt="Preview" class="h-20 w-20 object-cover rounded">
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
            <button type="button" @click="closeModals()"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </button>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <span x-text="showAddModal ? 'Add Product' : 'Update Product'"></span>
            </button>
        </div>
    </form>
</div>