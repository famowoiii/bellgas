import { test, expect } from '@playwright/test';

test.describe('Test Logout Functionality', () => {
    
    test('Complete login and logout flow', async ({ page }) => {
        console.log('\n=== TESTING LOGOUT FUNCTIONALITY ===\n');

        // Step 1: Go to login page
        console.log('1. Going to login page...');
        await page.goto('http://localhost:8000/login');
        await page.waitForTimeout(2000);

        // Step 2: Login
        console.log('2. Logging in...');
        await page.fill('input[name="email"]', 'stripetester@bellgas.com');
        await page.fill('input[name="password"]', 'password123');
        
        await page.click('button[type="submit"]');
        await page.waitForTimeout(5000); // Wait for login to process

        const currentUrl = page.url();
        console.log('After login URL:', currentUrl);

        // Step 3: Check if logged in
        const authState = await page.evaluate(() => {
            return {
                hasToken: !!localStorage.getItem('access_token'),
                user: window.app?.user,
                axiosHeader: axios.defaults.headers.common['Authorization']
            };
        });
        console.log('Auth state after login:', authState);

        // Step 4: Go to a page where we can test user menu
        await page.goto('http://localhost:8000/');
        await page.waitForTimeout(2000);

        // Step 5: Take screenshot before logout
        await page.screenshot({ path: 'debug-logout/01-before-logout.png', fullPage: true });

        // Step 6: Look for user dropdown and click logout
        console.log('3. Looking for user menu...');
        
        // Try to find user dropdown - look for user name or profile icon
        try {
            // Wait for user menu to be visible (assuming user is logged in)
            const userMenuExists = await page.locator('div:has-text("Test")').isVisible({ timeout: 5000 });
            
            if (userMenuExists) {
                console.log('User menu found, clicking to open...');
                await page.click('div:has-text("Test")');
                await page.waitForTimeout(1000);

                // Take screenshot of opened menu
                await page.screenshot({ path: 'debug-logout/02-user-menu-opened.png', fullPage: true });

                // Look for logout button
                const logoutButton = page.locator('button:has-text("Logout")');
                const logoutButtonExists = await logoutButton.isVisible();
                
                if (logoutButtonExists) {
                    console.log('4. Logout button found, clicking...');
                    
                    // Listen for console logs
                    page.on('console', msg => {
                        if (msg.text().includes('Logout') || msg.text().includes('logout')) {
                            console.log('Browser console:', msg.text());
                        }
                    });

                    await logoutButton.click();
                    await page.waitForTimeout(3000); // Wait for logout process

                    // Step 7: Check post-logout state
                    const postLogoutUrl = page.url();
                    console.log('After logout URL:', postLogoutUrl);

                    const postLogoutAuth = await page.evaluate(() => {
                        return {
                            hasToken: !!localStorage.getItem('access_token'),
                            user: window.app?.user,
                            axiosHeader: axios.defaults.headers.common['Authorization']
                        };
                    });
                    console.log('Auth state after logout:', postLogoutAuth);

                    // Take screenshot after logout
                    await page.screenshot({ path: 'debug-logout/03-after-logout.png', fullPage: true });

                    // Verify logout success
                    if (!postLogoutAuth.hasToken && !postLogoutAuth.user) {
                        console.log('✅ Logout successful - user state cleared');
                    } else {
                        console.log('❌ Logout may have failed - user state not cleared');
                    }

                } else {
                    console.log('❌ Logout button not found');
                    await page.screenshot({ path: 'debug-logout/02-no-logout-button.png', fullPage: true });
                }

            } else {
                console.log('❌ User menu not found - user may not be logged in');
                await page.screenshot({ path: 'debug-logout/02-no-user-menu.png', fullPage: true });
            }

        } catch (error) {
            console.log('❌ Error finding user interface:', error.message);
            await page.screenshot({ path: 'debug-logout/02-error-finding-ui.png', fullPage: true });
        }

        console.log('\n=== LOGOUT TEST COMPLETED ===');
    });
});