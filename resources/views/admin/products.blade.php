@extends('layouts.app')

@section('title', 'Product Management - BellGas Admin')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="simpleAdminProducts()" x-init="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Product Management</h1>
                <p class="text-gray-600">Manage your product catalog</p>
            </div>

            <!-- Actions -->
            <div class="flex space-x-3">
                <button @click="showAddModal = true"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add Product
                </button>
                <button @click="refreshProducts()"
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Refresh
                </button>
            </div>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="flex items-center justify-center py-12">
            <div class="animate-spin rounded-full h-8 w-8 border-2 border-blue-500 border-t-transparent"></div>
            <span class="ml-2 text-gray-600">Loading products...</span>
        </div>

        <!-- Error State -->
        <div x-show="error && !loading" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
            <div class="flex items-center space-x-2 text-red-800">
                <i class="fas fa-exclamation-triangle"></i>
                <span class="font-medium">Error loading products</span>
            </div>
            <p class="text-red-600 text-sm mt-1" x-text="error"></p>
        </div>

        <!-- Products Table -->
        <div x-show="!loading && !error" class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Product List</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="product in products" :key="product.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-12 w-12 rounded overflow-hidden border border-gray-200">
                                            <img x-show="product.image_url" :src="product.image_url" :alt="product.name"
                                                 class="w-full h-full object-cover">
                                            <div x-show="!product.image_url" class="w-full h-full bg-gray-100 flex items-center justify-center">
                                                <i class="fas fa-fire text-orange-400 text-lg"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900" x-text="product.name"></div>
                                            <div class="text-sm text-gray-500" x-text="product.slug || 'no-slug'"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="product.category || 'N/A'"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    $<span x-text="product.price || '0.00'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="product.is_active ? 'Active' : 'Inactive'">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="editProduct(product)" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="deleteProduct(product)" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div x-show="products.length === 0" class="p-12 text-center text-gray-500">
                    <i class="fas fa-box text-4xl mb-4"></i>
                    <p>No products found</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div x-show="showAddModal || showEditModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg max-w-2xl w-full max-h-96 overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold" x-text="showAddModal ? 'Add New Product' : 'Edit Product'"></h3>
                    <button @click="closeModals()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

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
                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select x-model="productForm.category_id" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                    <option value="">Select Category</option>
                                    <option value="1">Refill</option>
                                    <option value="2">Full Tank</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Price (AUD)</label>
                                <input type="number" step="0.01" min="0" x-model="productForm.price" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                                <input type="number" x-model="productForm.stock" min="0" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                                <input type="number" step="0.1" x-model="productForm.weight"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                            <input type="file" @change="handleImageChange" accept="image/*"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <div x-show="imagePreview" class="mt-2">
                                <img :src="imagePreview" class="w-32 h-32 object-cover rounded border">
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" x-model="productForm.is_active" id="is_active"
                                   class="mr-2 rounded border-gray-300">
                            <label for="is_active" class="text-sm font-medium text-gray-700">Active Product</label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="closeModals()"
                                class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                            <span x-text="showAddModal ? 'Add Product' : 'Update Product'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function simpleAdminProducts() {
    return {
        products: [],
        loading: false,
        error: null,
        showAddModal: false,
        showEditModal: false,
        editingProduct: null,
        imageFile: null,
        imagePreview: null,
        productForm: {
            name: '',
            description: '',
            category_id: '',
            price: '',
            stock: '',
            weight: '',
            is_active: true
        },

        async init() {
            console.log('Admin Products with CRUD initializing...');
            await this.loadProducts();
        },

        async loadProducts() {
            this.loading = true;
            this.error = null;

            try {
                const response = await axios.get('/api/products');

                if (response.data && response.data.data) {
                    this.products = response.data.data.map(product => ({
                        id: product.id,
                        name: product.name || 'Unnamed Product',
                        slug: product.slug,
                        category: product.category?.name || this.getCategoryName(product.category_id) || 'N/A',
                        category_id: product.category_id,
                        price: product.variants && product.variants[0] ? product.variants[0].price_aud : '0.00',
                        stock: product.variants && product.variants[0] ? product.variants[0].stock_quantity : 0,
                        is_active: product.is_active,
                        image_url: product.image_url || (product.photos && product.photos[0] ? product.photos[0].url : null)
                    }));
                    console.log('Products loaded:', this.products.length);
                } else {
                    this.products = [];
                }
            } catch (error) {
                console.error('Error loading products:', error);
                this.error = 'Failed to load products';
                this.products = [];
            } finally {
                this.loading = false;
            }
        },

        async refreshProducts() {
            await this.loadProducts();
        },

        resetForm() {
            this.productForm = {
                name: '',
                description: '',
                category_id: '',
                price: '',
                stock: '',
                weight: '',
                is_active: true
            };
            this.imageFile = null;
            this.imagePreview = null;
        },

        handleImageChange(event) {
            const file = event.target.files[0];
            if (file) {
                this.imageFile = file;
                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },

        closeModals() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.editingProduct = null;
            this.resetForm();
        },

        getCategoryName(categoryId) {
            const categories = {
                1: 'Refill',
                2: 'Full Tank'
            };
            return categories[categoryId] || 'N/A';
        },

        editProduct(product) {
            this.editingProduct = product;
            this.productForm = {
                name: product.name,
                description: product.description || '',
                category_id: product.category_id || '',
                price: product.price,
                stock: product.stock || 0,
                weight: '',
                is_active: product.is_active
            };
            this.showEditModal = true;
        },

        async saveProduct() {
            try {
                // Basic frontend validation
                if (!this.productForm.name || !this.productForm.category_id || !this.productForm.price || !this.productForm.stock) {
                    if (window.app && window.app.showNotification) {
                        window.app.showNotification('Please fill in all required fields', 'error');
                    }
                    return;
                }

                // Use FormData for file upload
                const formData = new FormData();
                formData.append('name', this.productForm.name.trim());
                formData.append('description', this.productForm.description?.trim() || '');
                formData.append('category_id', parseInt(this.productForm.category_id));
                formData.append('price', parseFloat(this.productForm.price));
                formData.append('stock', parseInt(this.productForm.stock));
                formData.append('weight', this.productForm.weight ? parseFloat(this.productForm.weight) : 0);
                formData.append('is_active', this.productForm.is_active ? 1 : 0);

                // Add image if selected
                if (this.imageFile) {
                    formData.append('image', this.imageFile);
                }

                console.log('Submitting product data with FormData');

                let response;
                if (this.showAddModal) {
                    response = await axios.post('/api/products', formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                    if (window.app && window.app.showNotification) {
                        window.app.showNotification('Product created successfully!', 'success');
                    }
                } else if (this.showEditModal && this.editingProduct) {
                    // Laravel doesn't support PUT with FormData for file upload, use POST with _method
                    formData.append('_method', 'PUT');
                    response = await axios.post(`/api/products/${this.editingProduct.id}`, formData, {
                        headers: { 'Content-Type': 'multipart/form-data' }
                    });
                    if (window.app && window.app.showNotification) {
                        window.app.showNotification('Product updated successfully!', 'success');
                    }
                }

                this.closeModals();
                await this.refreshProducts();
            } catch (error) {
                console.error('Error saving product:', error);
                console.error('Error details:', error.response?.data);

                if (window.app && window.app.showNotification) {
                    let message = 'Failed to save product';
                    if (error.response?.data?.errors) {
                        // Show validation errors
                        const errors = error.response.data.errors;
                        message = Object.values(errors).flat().join(', ');
                    } else if (error.response?.data?.message) {
                        message = error.response.data.message;
                    }
                    window.app.showNotification(message, 'error');
                }
            }
        },

        async deleteProduct(product) {
            if (!confirm(`Are you sure you want to delete "${product.name}"?`)) {
                return;
            }

            try {
                await axios.delete(`/api/products/${product.id}`);
                if (window.app && window.app.showNotification) {
                    window.app.showNotification('Product deleted successfully!', 'success');
                }
                await this.refreshProducts();
            } catch (error) {
                console.error('Error deleting product:', error);
                if (window.app && window.app.showNotification) {
                    const message = error.response?.data?.message || 'Failed to delete product';
                    window.app.showNotification(message, 'error');
                }
            }
        }
    }
}
</script>
@endsection