import { test, expect } from '@playwright/test';

test('Cart persistence fix verification', async ({ page }) => {
    console.log('🔍 Testing cart persistence fix...');
    
    // Go to our test page
    await page.goto('http://localhost:8000/test-cart-fix.html');
    
    // Wait for test to complete
    await page.waitForFunction(() => {
        const results = document.getElementById('test-results');
        const logs = results.querySelectorAll('.log');
        return logs.length > 5; // Wait for several log entries
    }, { timeout: 30000 });
    
    // Wait a bit more to ensure completion
    await page.waitForTimeout(5000);
    
    // Check if any error messages exist
    const errors = await page.locator('.log.error').count();
    const successes = await page.locator('.log.success').count();
    
    console.log(`Test completed: ${successes} successes, ${errors} errors`);
    
    // Get all log messages for debugging
    const logs = await page.locator('.log').allTextContents();
    logs.forEach(log => console.log('  ' + log));
    
    // Test should have no errors
    expect(errors).toBe(0);
    expect(successes).toBeGreaterThan(3);
});