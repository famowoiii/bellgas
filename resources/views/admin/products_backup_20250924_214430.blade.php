@extends('layouts.app')

@section('title', 'Product Management - BellGas Admin')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="adminProducts()" x-init="init()">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Product Management</h1>
                <p class="text-gray-600">Manage your product catalog and inventory</p>
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

        <!-- Product Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-box text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.total"></p>
                        <p class="text-gray-600 text-sm">Total Products</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.active"></p>
                        <p class="text-gray-600 text-sm">Active Products</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.low_stock"></p>
                        <p class="text-gray-600 text-sm">Low Stock</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-times-circle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-800" x-text="stats.out_of_stock"></p>
                        <p class="text-gray-600 text-sm">Out of Stock</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Products Table -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold">Product List</h2>
                    <div class="flex space-x-2">
                        <select x-model="categoryFilter" @change="filterProducts()" 
                                class="text-sm border border-gray-300 rounded px-3 py-1">
                            <option value="">All Categories</option>
                            <template x-for="category in categories" :key="category.id">
                                <option :value="category.id" x-text="category.name"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full object-cover"
                                                 :src="getProductImage(product)"
                                                 :alt="product.name"
                                                 @error="handleImageError($event)">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900" x-text="product.name"></div>
                                            <div class="text-sm text-gray-500" x-text="product.slug"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="getProductCategory(product)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<span x-text="product.variants?.[0]?.price_aud || '0.00'"></span></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <span x-text="getTotalStock(product)"></span> units
                                    </div>
                                    <div class="text-xs" :class="getStockStatusColor(getTotalStock(product))" 
                                         x-text="getStockStatus(getTotalStock(product))"></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                          x-text="product.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button @click="editProduct(product)" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button @click="toggleProductStatus(product)" 
                                                class="text-yellow-600 hover:text-yellow-900">
                                            <i class="fas fa-power-off"></i>
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

                <div x-show="filteredProducts.length === 0" class="p-12 text-center text-gray-500">
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
                                    <template x-for="category in categories" :key="category.id">
                                        <option :value="category.id" x-text="category.name"></option>
                                    </template>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Price (AUD)</label>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       x-model.number="productForm.price"
                                       required
                                       @input="console.log('Price input changed to:', $event.target.value)"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                                <input type="number"
                                       x-model.number="productForm.stock"
                                       min="0"
                                       step="1"
                                       required
                                       @input="console.log('Stock input changed to:', $event.target.value)"
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
                            <input type="file" @change="handleImageUpload($event)" accept="image/*"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                            <p class="text-xs text-gray-500 mt-1">Select an image file (JPG, PNG, etc.)</p>
                            
                            <!-- Image preview -->
                            <div x-show="imagePreview" class="mt-3">
                                <img :src="imagePreview" class="w-32 h-32 object-cover rounded-lg border">
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
// Authentication is handled globally in app.blade.php layout

function adminProducts() {
    return {
        products: [],
        filteredProducts: [],
        categories: [],
        categoryFilter: '',
        stats: {
            total: 0,
            active: 0,
            low_stock: 0,
            out_of_stock: 0
        },
        showAddModal: false,
        showEditModal: false,
        editingProduct: null,
        productForm: {
            name: '',
            description: '',
            category_id: '',
            price: '',
            stock: '',
            weight: '',
            is_active: true
        },
        imagePreview: null,
        selectedImageFile: null,
        
        async init() {
            await this.loadProducts();
            await this.loadCategories();
        },
        
        async loadProducts() {
            try {
                const response = await axios.get('/api/products');
                this.products = response.data.data || [];
                this.filteredProducts = this.products;
                this.calculateStats();
                console.log('Products loaded:', this.products.length, 'products');
            } catch (error) {
                console.error('Failed to load products:', error);
                this.showNotification('Failed to load products', 'error');
            }
        },
        
        async loadCategories() {
            try {
                const response = await axios.get('/api/categories');
                this.categories = response.data.data || [];
            } catch (error) {
                console.error('Failed to load categories:', error);
            }
        },
        
        async refreshProducts() {
            await this.loadProducts();
            this.showNotification('Products refreshed successfully', 'success');
        },
        
        filterProducts() {
            if (this.categoryFilter) {
                this.filteredProducts = this.products.filter(product => 
                    product.category_id == this.categoryFilter
                );
            } else {
                this.filteredProducts = this.products;
            }
        },
        
        calculateStats() {
            this.stats = {
                total: this.products.length,
                active: this.products.filter(p => p.is_active).length,
                low_stock: this.products.filter(p => this.getTotalStock(p) > 0 && this.getTotalStock(p) <= 10).length,
                out_of_stock: this.products.filter(p => this.getTotalStock(p) === 0).length
            };
        },
        
        getTotalStock(product) {
            if (!product.variants || product.variants.length === 0) return 0;
            return product.variants.reduce((total, variant) => total + (variant.stock_quantity || 0), 0);
        },
        
        getStockStatus(stock) {
            if (stock === 0) return 'Out of Stock';
            if (stock <= 10) return 'Low Stock';
            return 'In Stock';
        },
        
        getStockStatusColor(stock) {
            if (stock === 0) return 'text-red-600';
            if (stock <= 10) return 'text-yellow-600';
            return 'text-green-600';
        },
        
        handleImageUpload(event) {
            const file = event.target.files[0];
            if (file) {
                this.selectedImageFile = file;
                
                // Create preview
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.imagePreview = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        },
        
        editProduct(product) {
            if (!product || !product.id) {
                this.showNotification('Product not found. Please refresh the page.', 'error');
                return;
            }
            
            this.editingProduct = product;
            this.productForm = {
                name: product.name,
                description: product.description || '',
                category_id: product.category_id,
                price: product.variants?.[0]?.price_aud || 0,
                stock: product.variants?.[0]?.stock_quantity || 0, // Default to number 0, not string
                weight: product.variants?.[0]?.weight_kg || 0,
                is_active: product.is_active
            };

            console.log('📝 Edit product form data:', this.productForm);
            console.log('📦 Original stock value:', product.variants?.[0]?.stock_quantity);
            this.imagePreview = product.image_url || null;
            this.selectedImageFile = null;
            this.showEditModal = true;
        },
        
        async saveProduct() {
            try {
                const formData = new FormData();
                
                // Add form fields with proper handling and parsing
                const fieldsToAdd = {
                    name: this.productForm.name,
                    description: this.productForm.description || '',
                    category_id: parseInt(this.productForm.category_id) || 0,
                    price: parseFloat(this.productForm.price) || 0,
                    stock: parseInt(this.productForm.stock) || 0, // Parse stock as integer
                    weight: this.productForm.weight ? parseFloat(this.productForm.weight) : 0
                };

                console.log('📦 FormData fields to append:', fieldsToAdd);

                // Add all fields
                Object.entries(fieldsToAdd).forEach(([key, value]) => {
                    if (value !== null && value !== '') {
                        formData.append(key, value.toString());
                    }
                });
                
                // Always add is_active
                formData.append('is_active', this.productForm.is_active ? 1 : 0);
                
                console.log('FormData contents:');
                for (let [key, value] of formData.entries()) {
                    console.log(key, value);
                }
                
                // Add image file if selected
                if (this.selectedImageFile) {
                    formData.append('image', this.selectedImageFile);
                }
                
                if (this.showAddModal) {
                    await axios.post('/api/products', formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    });
                    this.showNotification('Product added successfully', 'success');
                } else {
                    // For updates, check if we have an image to upload
                    if (this.selectedImageFile) {
                        // Use FormData for file uploads
                        formData.append('_method', 'PUT');
                        await axios.post(`/api/products/${this.editingProduct.id}`, formData, {
                            headers: {
                                'Content-Type': 'multipart/form-data'
                            }
                        });
                    } else {
                        // Parse and validate form data carefully
                        const stockValue = this.productForm.stock;
                        const parsedStock = parseInt(stockValue);

                        console.log('🔍 Debug stock parsing:');
                        console.log('  Raw stock value:', stockValue, 'Type:', typeof stockValue);
                        console.log('  Parsed stock value:', parsedStock);
                        console.log('  Is NaN?', isNaN(parsedStock));

                        // Use regular JSON for updates without files
                        const updateData = {
                            name: this.productForm.name,
                            description: this.productForm.description || '',
                            category_id: parseInt(this.productForm.category_id) || 0,
                            price: parseFloat(this.productForm.price) || 0,
                            stock: isNaN(parsedStock) ? 0 : parsedStock, // Safe stock parsing
                            weight: this.productForm.weight ? parseFloat(this.productForm.weight) : 0,
                            is_active: this.productForm.is_active ? 1 : 0
                        };

                        console.log('🔄 Updating product with validated data:', updateData);

                        await axios.put(`/api/products/${this.editingProduct.id}`, updateData);
                    }
                    this.showNotification('Product updated successfully', 'success');
                }
                
                this.closeModals();
                await this.loadProducts();
                
            } catch (error) {
                console.error('Failed to save product:', error);
                console.error('Error response:', error.response?.data);
                
                if (error.response?.data?.errors) {
                    // Show all validation errors
                    const errors = error.response.data.errors;
                    const errorMessages = Object.keys(errors).map(field => {
                        return `${field}: ${errors[field].join(', ')}`;
                    });
                    this.showNotification(`Validation errors: ${errorMessages.join('; ')}`, 'error');
                } else {
                    this.showNotification(error.response?.data?.message || 'Failed to save product', 'error');
                }
            }
        },
        
        async toggleProductStatus(product) {
            if (!product || !product.id) {
                this.showNotification('Product not found. Please refresh the page.', 'error');
                return;
            }
            
            try {
                await axios.put(`/api/products/${product.id}`, {
                    name: product.name,
                    description: product.description || '',
                    category_id: product.category_id,
                    price: product.variants?.[0]?.price_aud || 0,
                    stock: product.variants?.[0]?.stock_quantity || 0,
                    weight: product.variants?.[0]?.weight_kg || 0,
                    is_active: !product.is_active ? 1 : 0
                });
                
                product.is_active = !product.is_active;
                this.calculateStats();
                this.showNotification(`Product ${product.is_active ? 'activated' : 'deactivated'}`, 'success');
                
            } catch (error) {
                console.error('Failed to toggle product status:', error);
                if (error.response?.status === 404) {
                    this.showNotification('Product not found. Refreshing list...', 'error');
                    await this.loadProducts();
                } else {
                    this.showNotification(error.response?.data?.message || 'Failed to update product status', 'error');
                }
            }
        },
        
        async deleteProduct(product) {
            if (!product || !product.id) {
                this.showNotification('Product not found. Please refresh the page.', 'error');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete "${product.name}"?`)) return;
            
            try {
                await axios.delete(`/api/products/${product.id}`);
                await this.loadProducts();
                this.showNotification('Product deleted successfully', 'success');
                
            } catch (error) {
                console.error('Failed to delete product:', error);
                if (error.response?.status === 404) {
                    this.showNotification('Product not found. Refreshing list...', 'error');
                    await this.loadProducts();
                } else {
                    this.showNotification(error.response?.data?.message || 'Failed to delete product', 'error');
                }
            }
        },
        
        closeModals() {
            this.showAddModal = false;
            this.showEditModal = false;
            this.editingProduct = null;
            this.productForm = {
                name: '',
                description: '',
                category_id: '',
                price: '',
                stock: '',
                weight: '',
                is_active: true
            };
            this.imagePreview = null;
            this.selectedImageFile = null;
        },
        
        getProductImage(product) {
            // Try multiple image sources in priority order
            if (product.image_url) {
                return product.image_url;
            }

            if (product.photos && product.photos.length > 0) {
                // Use the first photo's URL
                return product.photos[0].url || `/storage/products/${product.photos[0].filename}`;
            }

            // Default fallback image
            return '/images/default-product.png';
        },

        getProductCategory(product) {
            // Try different category sources
            if (product.category && product.category.name) {
                return product.category.name;
            }

            // Check if category is a string (enum)
            if (product.category && typeof product.category === 'string') {
                return product.category.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            }

            // Look up category by ID
            if (product.category_id && this.categories.length > 0) {
                const category = this.categories.find(cat => cat.id === product.category_id);
                return category ? category.name : 'Unknown Category';
            }

            return 'N/A';
        },

        handleImageError(event) {
            // Fallback to default image if loading fails
            event.target.src = '/images/default-product.png';
        },

        showNotification(message, type = 'info') {
            if (window.app && window.app.showNotification) {
                window.app.showNotification(message, type);
            } else {
                alert(message);
            }
        }
    }
}
</script>

@endsection