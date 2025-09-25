@extends('layouts.app')

@section('title', 'Contact Us - BellGas')

@section('content')
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-4">Get in Touch</h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Have questions about our LPG services? Need support with your order? We're here to help!
            </p>
        </div>

        <div class="grid lg:grid-cols-3 gap-12">
            <!-- Contact Information -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Contact Details -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-6">Contact Information</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-phone text-blue-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Phone</p>
                                <p class="text-gray-600">+61 2 1234 5678</p>
                                <p class="text-sm text-gray-500">Mon-Fri: 8AM-6PM, Sat: 9AM-4PM</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-envelope text-green-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Email</p>
                                <p class="text-gray-600">support@bellgas.com.au</p>
                                <p class="text-sm text-gray-500">We respond within 24 hours</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-map-marker-alt text-orange-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Address</p>
                                <p class="text-gray-600">123 Gas Street<br>Sydney NSW 2000<br>Australia</p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                            </div>
                            <div>
                                <p class="font-medium text-gray-800">Emergency</p>
                                <p class="text-gray-600">+61 2 9999 0000</p>
                                <p class="text-sm text-gray-500">24/7 Emergency LPG Support</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Business Hours -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Business Hours</h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Monday - Friday</span>
                            <span class="font-medium text-gray-800">8:00 AM - 6:00 PM</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Saturday</span>
                            <span class="font-medium text-gray-800">9:00 AM - 4:00 PM</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-700">Sunday</span>
                            <span class="font-medium text-red-600">Closed</span>
                        </div>
                        <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                            <span class="text-gray-700">Public Holidays</span>
                            <span class="font-medium text-red-600">Closed</span>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                        <p class="text-sm text-blue-700">
                            <i class="fas fa-info-circle mr-2"></i>
                            Emergency support available 24/7 for existing customers
                        </p>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Quick Help</h3>
                    
                    <div class="space-y-3">
                        <a href="/orders" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-shopping-bag text-blue-600"></i>
                                <div>
                                    <p class="font-medium text-gray-800">Track Your Order</p>
                                    <p class="text-sm text-gray-500">Check order status and delivery info</p>
                                </div>
                            </div>
                        </a>

                        <a href="/faq" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-question-circle text-green-600"></i>
                                <div>
                                    <p class="font-medium text-gray-800">FAQ</p>
                                    <p class="text-sm text-gray-500">Find answers to common questions</p>
                                </div>
                            </div>
                        </a>

                        <a href="/safety" class="block p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-shield-alt text-orange-600"></i>
                                <div>
                                    <p class="font-medium text-gray-800">Safety Information</p>
                                    <p class="text-sm text-gray-500">LPG safety guidelines and tips</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-8" x-data="contactForm()">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-6">Send us a Message</h3>
                    
                    <form @submit.prevent="submitForm()">
                        <div class="grid md:grid-cols-2 gap-6 mb-6">
                            <!-- First Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                                <input x-model="form.first_name" 
                                       type="text" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       :class="errors.first_name ? 'border-red-500' : 'border-gray-300'">
                                <p x-show="errors.first_name" class="mt-1 text-sm text-red-600" x-text="errors.first_name?.[0]"></p>
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                                <input x-model="form.last_name" 
                                       type="text" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       :class="errors.last_name ? 'border-red-500' : 'border-gray-300'">
                                <p x-show="errors.last_name" class="mt-1 text-sm text-red-600" x-text="errors.last_name?.[0]"></p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input x-model="form.email" 
                                       type="email" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       :class="errors.email ? 'border-red-500' : 'border-gray-300'">
                                <p x-show="errors.email" class="mt-1 text-sm text-red-600" x-text="errors.email?.[0]"></p>
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input x-model="form.phone" 
                                       type="tel" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                       :class="errors.phone ? 'border-red-500' : 'border-gray-300'">
                                <p x-show="errors.phone" class="mt-1 text-sm text-red-600" x-text="errors.phone?.[0]"></p>
                            </div>
                        </div>

                        <!-- Subject -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subject *</label>
                            <select x-model="form.subject" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                    :class="errors.subject ? 'border-red-500' : 'border-gray-300'">
                                <option value="">Please select a subject</option>
                                <option value="general">General Inquiry</option>
                                <option value="order">Order Support</option>
                                <option value="delivery">Delivery Issue</option>
                                <option value="billing">Billing Question</option>
                                <option value="technical">Technical Support</option>
                                <option value="safety">Safety Concern</option>
                                <option value="feedback">Feedback/Suggestion</option>
                                <option value="business">Business Partnership</option>
                            </select>
                            <p x-show="errors.subject" class="mt-1 text-sm text-red-600" x-text="errors.subject?.[0]"></p>
                        </div>

                        <!-- Order Number (conditional) -->
                        <div x-show="['order', 'delivery', 'billing'].includes(form.subject)" class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order Number</label>
                            <input x-model="form.order_number" 
                                   type="text" 
                                   placeholder="e.g., BG-ABCD1234"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Message -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                            <textarea x-model="form.message" 
                                      rows="6" 
                                      required
                                      placeholder="Please provide as much detail as possible to help us assist you better..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                      :class="errors.message ? 'border-red-500' : 'border-gray-300'"></textarea>
                            <p x-show="errors.message" class="mt-1 text-sm text-red-600" x-text="errors.message?.[0]"></p>
                            <p class="mt-2 text-sm text-gray-500">
                                <span x-text="form.message.length"></span>/1000 characters
                            </p>
                        </div>

                        <!-- Priority -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Priority Level</label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-gray-400 transition"
                                       :class="form.priority === 'low' ? 'border-green-500 bg-green-50' : 'border-gray-300'">
                                    <input x-model="form.priority" type="radio" value="low" class="sr-only">
                                    <div class="flex-1 text-center">
                                        <i class="fas fa-circle text-green-500 mb-1"></i>
                                        <p class="text-sm font-medium">Low</p>
                                        <p class="text-xs text-gray-500">Response in 2-3 days</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-gray-400 transition"
                                       :class="form.priority === 'medium' ? 'border-yellow-500 bg-yellow-50' : 'border-gray-300'">
                                    <input x-model="form.priority" type="radio" value="medium" class="sr-only">
                                    <div class="flex-1 text-center">
                                        <i class="fas fa-circle text-yellow-500 mb-1"></i>
                                        <p class="text-sm font-medium">Medium</p>
                                        <p class="text-xs text-gray-500">Response in 24 hours</p>
                                    </div>
                                </label>

                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:border-gray-400 transition"
                                       :class="form.priority === 'high' ? 'border-red-500 bg-red-50' : 'border-gray-300'">
                                    <input x-model="form.priority" type="radio" value="high" class="sr-only">
                                    <div class="flex-1 text-center">
                                        <i class="fas fa-circle text-red-500 mb-1"></i>
                                        <p class="text-sm font-medium">High</p>
                                        <p class="text-xs text-gray-500">Response in 4 hours</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Newsletter Subscription -->
                        <div class="mb-6">
                            <label class="flex items-center">
                                <input x-model="form.subscribe_newsletter" 
                                       type="checkbox" 
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">
                                    Subscribe to our newsletter for LPG tips, safety information, and special offers
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex justify-end">
                            <button type="submit" 
                                    :disabled="loading"
                                    class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                                <span x-show="!loading" class="flex items-center">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Send Message
                                </span>
                                <span x-show="loading" class="flex items-center">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Sending...
                                </span>
                            </button>
                        </div>
                    </form>

                    <!-- Success Message -->
                    <div x-show="submitted" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-3"></i>
                            <div>
                                <h4 class="font-medium text-green-800">Message sent successfully!</h4>
                                <p class="text-sm text-green-700 mt-1">
                                    Thank you for contacting us. We'll respond within 
                                    <span x-text="form.priority === 'high' ? '4 hours' : form.priority === 'medium' ? '24 hours' : '2-3 days'"></span>.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-800 mb-4">Frequently Asked Questions</h2>
                <p class="text-xl text-gray-600">Quick answers to common questions</p>
            </div>

            <div class="grid md:grid-cols-2 gap-8" x-data="{ openFaq: null }">
                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-md">
                        <button @click="openFaq = openFaq === 1 ? null : 1" 
                                class="w-full p-6 text-left flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">What areas do you deliver to?</h3>
                            <i :class="openFaq === 1 ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-gray-500"></i>
                        </button>
                        <div x-show="openFaq === 1" class="px-6 pb-6">
                            <p class="text-gray-600">We deliver across the entire Sydney metropolitan area, including all surrounding suburbs within a 50km radius of the CBD.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md">
                        <button @click="openFaq = openFaq === 2 ? null : 2" 
                                class="w-full p-6 text-left flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">How quickly can I get my LPG delivered?</h3>
                            <i :class="openFaq === 2 ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-gray-500"></i>
                        </button>
                        <div x-show="openFaq === 2" class="px-6 pb-6">
                            <p class="text-gray-600">We offer same-day delivery for orders placed before 2PM on weekdays. Standard delivery is next business day. Emergency delivery is available for existing customers.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md">
                        <button @click="openFaq = openFaq === 3 ? null : 3" 
                                class="w-full p-6 text-left flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">What payment methods do you accept?</h3>
                            <i :class="openFaq === 3 ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-gray-500"></i>
                        </button>
                        <div x-show="openFaq === 3" class="px-6 pb-6">
                            <p class="text-gray-600">We accept all major credit cards, debit cards, and digital payment methods through our secure Stripe payment system.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-lg shadow-md">
                        <button @click="openFaq = openFaq === 4 ? null : 4" 
                                class="w-full p-6 text-left flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">Are your LPG cylinders safe and certified?</h3>
                            <i :class="openFaq === 4 ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-gray-500"></i>
                        </button>
                        <div x-show="openFaq === 4" class="px-6 pb-6">
                            <p class="text-gray-600">Yes, all our cylinders are regularly inspected and certified to Australian standards. We follow strict safety protocols for storage, handling, and delivery.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md">
                        <button @click="openFaq = openFaq === 5 ? null : 5" 
                                class="w-full p-6 text-left flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">Can I exchange my empty cylinder?</h3>
                            <i :class="openFaq === 5 ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-gray-500"></i>
                        </button>
                        <div x-show="openFaq === 5" class="px-6 pb-6">
                            <p class="text-gray-600">Absolutely! We offer a cylinder exchange service. Just bring your empty cylinder to our location or arrange for pickup during delivery for a discounted refill rate.</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-md">
                        <button @click="openFaq = openFaq === 6 ? null : 6" 
                                class="w-full p-6 text-left flex justify-between items-center">
                            <h3 class="font-semibold text-gray-800">Do you offer business accounts?</h3>
                            <i :class="openFaq === 6 ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="text-gray-500"></i>
                        </button>
                        <div x-show="openFaq === 6" class="px-6 pb-6">
                            <p class="text-gray-600">Yes, we offer special business accounts with volume discounts, priority delivery, and flexible payment terms for restaurants, cafes, and other commercial customers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function contactForm() {
    return {
        form: {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            subject: '',
            order_number: '',
            message: '',
            priority: 'medium',
            subscribe_newsletter: false
        },
        errors: {},
        loading: false,
        submitted: false,
        
        async submitForm() {
            this.loading = true;
            this.errors = {};
            this.submitted = false;
            
            try {
                // In a real app, this would make an API call
                await new Promise(resolve => setTimeout(resolve, 2000));
                
                this.submitted = true;
                this.form = {
                    first_name: '',
                    last_name: '',
                    email: '',
                    phone: '',
                    subject: '',
                    order_number: '',
                    message: '',
                    priority: 'medium',
                    subscribe_newsletter: false
                };
                
            } catch (error) {
                this.errors = error.response?.data?.errors || {};
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection