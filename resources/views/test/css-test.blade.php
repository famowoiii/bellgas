@extends('layouts.app')

@section('title', 'CSS Test - Firefox Compatibility')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- CSS Test Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-4">CSS Loading Test</h1>
            <p class="text-gray-600">This page tests if CSS is loading properly in different browsers.</p>

            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <h3 class="font-semibold text-blue-800">Browser Detection:</h3>
                <p id="browser-info" class="text-blue-600"></p>
            </div>
        </div>

        <!-- Tailwind Test Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-check text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Blue Box Test</h3>
                        <p class="text-sm text-gray-600">Should have blue background</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-red-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-heart text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Red Box Test</h3>
                        <p class="text-sm text-gray-600">Should have red background</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-green-500 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-star text-white text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800">Green Box Test</h3>
                        <p class="text-sm text-gray-600">Should have green background</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CSS Loading Status -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">CSS Loading Status</h2>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span>Tailwind CSS:</span>
                    <span id="tailwind-status" class="px-3 py-1 rounded text-sm">Checking...</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span>Font Awesome:</span>
                    <span id="fontawesome-status" class="px-3 py-1 rounded text-sm">Checking...</span>
                </div>

                <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                    <span>Alpine.js:</span>
                    <span id="alpine-status" class="px-3 py-1 rounded text-sm">Checking...</span>
                </div>
            </div>
        </div>

        <!-- Debug Info -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h2 class="text-xl font-semibold mb-4">Debug Information</h2>
            <pre id="debug-info" class="bg-gray-100 p-4 rounded text-sm overflow-auto"></pre>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Browser detection
    const browserInfo = document.getElementById('browser-info');
    const userAgent = navigator.userAgent;
    let browser = 'Unknown';

    if (userAgent.includes('Firefox')) browser = 'Mozilla Firefox';
    else if (userAgent.includes('Chrome')) browser = 'Google Chrome';
    else if (userAgent.includes('Safari')) browser = 'Safari';
    else if (userAgent.includes('Edge')) browser = 'Microsoft Edge';

    browserInfo.textContent = browser + ' - ' + userAgent;

    // Test Tailwind CSS
    setTimeout(() => {
        const testEl = document.createElement('div');
        testEl.className = 'bg-blue-500';
        testEl.style.visibility = 'hidden';
        document.body.appendChild(testEl);

        const bgColor = window.getComputedStyle(testEl).backgroundColor;
        document.body.removeChild(testEl);

        const tailwindStatus = document.getElementById('tailwind-status');
        if (bgColor.includes('59, 130, 246') || bgColor.includes('rgb(59, 130, 246)')) {
            tailwindStatus.textContent = 'Loaded ✓';
            tailwindStatus.className = 'px-3 py-1 rounded text-sm bg-green-100 text-green-800';
        } else {
            tailwindStatus.textContent = 'Failed ✗';
            tailwindStatus.className = 'px-3 py-1 rounded text-sm bg-red-100 text-red-800';
        }
    }, 1000);

    // Test Font Awesome
    setTimeout(() => {
        const faStatus = document.getElementById('fontawesome-status');
        const faIcon = document.querySelector('.fas');
        if (faIcon) {
            const iconStyles = window.getComputedStyle(faIcon);
            if (iconStyles.fontFamily.includes('Font Awesome')) {
                faStatus.textContent = 'Loaded ✓';
                faStatus.className = 'px-3 py-1 rounded text-sm bg-green-100 text-green-800';
            } else {
                faStatus.textContent = 'Failed ✗';
                faStatus.className = 'px-3 py-1 rounded text-sm bg-red-100 text-red-800';
            }
        } else {
            faStatus.textContent = 'No icons found ✗';
            faStatus.className = 'px-3 py-1 rounded text-sm bg-red-100 text-red-800';
        }
    }, 1500);

    // Test Alpine.js
    setTimeout(() => {
        const alpineStatus = document.getElementById('alpine-status');
        if (typeof Alpine !== 'undefined' || window.Alpine) {
            alpineStatus.textContent = 'Loaded ✓';
            alpineStatus.className = 'px-3 py-1 rounded text-sm bg-green-100 text-green-800';
        } else {
            alpineStatus.textContent = 'Failed ✗';
            alpineStatus.className = 'px-3 py-1 rounded text-sm bg-red-100 text-red-800';
        }
    }, 2000);

    // Debug info
    setTimeout(() => {
        const debugInfo = {
            browser: browser,
            userAgent: userAgent,
            tailwindLoaded: typeof tailwind !== 'undefined',
            alpineLoaded: typeof Alpine !== 'undefined',
            axiosLoaded: typeof axios !== 'undefined',
            documentReady: document.readyState,
            stylesheets: Array.from(document.styleSheets).map(sheet => {
                try {
                    return {
                        href: sheet.href,
                        rules: sheet.cssRules ? sheet.cssRules.length : 'inaccessible'
                    };
                } catch (e) {
                    return { href: sheet.href, rules: 'CORS blocked' };
                }
            })
        };

        document.getElementById('debug-info').textContent = JSON.stringify(debugInfo, null, 2);
    }, 2500);
});
</script>
@endsection