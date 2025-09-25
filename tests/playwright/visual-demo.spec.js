import { test, expect } from '@playwright/test';

// Configure for visual recording at global level
test.use({ 
  video: 'on',
  screenshot: 'on',
  trace: 'on'
});

test.describe('🎬 BellGas Laravel - Visual Demo Testing', () => {

  test('Demo 1: 🏠 Home Page Journey with Screenshots', async ({ page }) => {
    console.log('🎬 Starting Home Page Visual Demo...');
    
    // Go to home page
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    
    // Take full page screenshot
    await page.screenshot({ path: 'demo-screenshots/01-home-page.png', fullPage: true });
    console.log('📸 Screenshot: Home page loaded');
    
    // Wait and interact with elements
    await page.waitForTimeout(3000);
    
    // Try to find and click navigation elements
    const navElements = await page.locator('nav a, header a, .nav a, .navbar a').all();
    if (navElements.length > 0) {
      console.log(`📍 Found ${navElements.length} navigation elements`);
      
      // Highlight navigation by hovering
      for (let i = 0; i < Math.min(navElements.length, 3); i++) {
        await navElements[i].hover();
        await page.waitForTimeout(1000);
      }
      
      await page.screenshot({ path: 'demo-screenshots/02-navigation-highlighted.png' });
    }
    
    // Check for any content sections
    const contentSections = await page.locator('main, .main, .content, section, .section, article').all();
    if (contentSections.length > 0) {
      console.log(`📄 Found ${contentSections.length} content sections`);
      await page.screenshot({ path: 'demo-screenshots/03-content-sections.png' });
    }
    
    // Scroll down to see more content
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'demo-screenshots/04-scrolled-content.png' });
    
    // Scroll to bottom
    await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight));
    await page.waitForTimeout(2000);
    await page.screenshot({ path: 'demo-screenshots/05-bottom-content.png' });
    
    console.log('✅ Home Page Visual Demo completed');
  });

  test('Demo 2: 🌐 Multi-Page Navigation Tour', async ({ page }) => {
    console.log('🎬 Starting Multi-Page Navigation Demo...');
    
    const testRoutes = [
      { url: '/', name: 'Home Page', description: 'Landing page' },
      { url: '/home', name: 'Home Route', description: 'Home route page' },
      { url: '/products', name: 'Products Page', description: 'Product catalog' },
      { url: '/login', name: 'Login Page', description: 'User login form' },
      { url: '/register', name: 'Register Page', description: 'User registration' },
      { url: '/cart', name: 'Cart Page', description: 'Shopping cart' },
      { url: '/about', name: 'About Page', description: 'About information' },
      { url: '/contact', name: 'Contact Page', description: 'Contact form' }
    ];
    
    for (let i = 0; i < testRoutes.length; i++) {
      const route = testRoutes[i];
      console.log(`🔄 ${i+1}/${testRoutes.length} Testing: ${route.name} (${route.url})`);
      
      try {
        const response = await page.goto(`http://localhost:8000${route.url}`, { 
          waitUntil: 'networkidle',
          timeout: 15000 
        });
        
        await page.waitForTimeout(2000); // Allow page to fully render
        
        // Check response status
        const status = response ? response.status() : 'unknown';
        console.log(`📊 Status: ${status}`);
        
        // Take screenshot of each page
        await page.screenshot({ 
          path: `demo-screenshots/page-${String(i+1).padStart(2, '0')}-${route.name.toLowerCase().replace(/\s+/g, '-')}.png`,
          fullPage: true 
        });
        
        console.log(`📸 Screenshot: ${route.name} captured (${status})`);
        
        // Try to interact with common elements
        const interactiveElements = await page.locator('button, a, input, select').all();
        if (interactiveElements.length > 0) {
          console.log(`🎯 Found ${interactiveElements.length} interactive elements`);
          
          // Hover over first few elements to show interactivity
          for (let j = 0; j < Math.min(interactiveElements.length, 3); j++) {
            try {
              await interactiveElements[j].hover({ timeout: 1000 });
              await page.waitForTimeout(500);
            } catch (e) {
              // Element might not be hoverable
            }
          }
        }
        
      } catch (error) {
        console.log(`⚠️ ${route.name}: ${error.message}`);
        await page.screenshot({ 
          path: `demo-screenshots/error-${String(i+1).padStart(2, '0')}-${route.name.toLowerCase().replace(/\s+/g, '-')}.png` 
        });
      }
      
      await page.waitForTimeout(1000); // Brief pause between pages
    }
    
    console.log('✅ Multi-Page Navigation Demo completed');
  });

  test('Demo 3: 📱 Responsive Design Testing', async ({ page }) => {
    console.log('🎬 Starting Responsive Design Demo...');
    
    const viewports = [
      { width: 1920, height: 1080, name: 'Desktop-Large', icon: '🖥️' },
      { width: 1366, height: 768, name: 'Desktop-Standard', icon: '💻' },
      { width: 768, height: 1024, name: 'Tablet-Portrait', icon: '📱' },
      { width: 1024, height: 768, name: 'Tablet-Landscape', icon: '📱' },
      { width: 375, height: 667, name: 'Mobile-iPhone', icon: '📱' },
      { width: 360, height: 640, name: 'Mobile-Android', icon: '📱' }
    ];
    
    for (let i = 0; i < viewports.length; i++) {
      const viewport = viewports[i];
      console.log(`${viewport.icon} ${i+1}/${viewports.length} Testing ${viewport.name}: ${viewport.width}x${viewport.height}`);
      
      await page.setViewportSize({ width: viewport.width, height: viewport.height });
      await page.goto('http://localhost:8000');
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(3000); // Allow responsive changes to settle
      
      // Screenshot for each viewport
      await page.screenshot({ 
        path: `demo-screenshots/responsive-${String(i+1).padStart(2, '0')}-${viewport.name.toLowerCase()}.png`,
        fullPage: true 
      });
      
      // Test navigation on mobile (hamburger menu, etc.)
      if (viewport.width <= 768) {
        const mobileMenuTriggers = await page.locator('.hamburger, .mobile-menu, .menu-toggle, [data-toggle="menu"]').all();
        if (mobileMenuTriggers.length > 0) {
          console.log(`🍔 Found mobile menu trigger, testing interaction`);
          await mobileMenuTriggers[0].click();
          await page.waitForTimeout(1000);
          
          await page.screenshot({ 
            path: `demo-screenshots/mobile-menu-${viewport.name.toLowerCase()}.png`
          });
        }
      }
      
      console.log(`📸 Screenshot: ${viewport.name} view captured`);
    }
    
    console.log('✅ Responsive Design Demo completed');
  });

  test('Demo 4: 🎯 Interactive Elements Testing', async ({ page }) => {
    console.log('🎬 Starting Interactive Elements Demo...');
    
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    
    // Initial page screenshot
    await page.screenshot({ path: 'demo-screenshots/interactive-01-initial.png', fullPage: true });
    
    // Test different types of interactive elements
    const elementTypes = [
      { selector: 'button', name: 'Buttons', action: 'click' },
      { selector: 'a', name: 'Links', action: 'hover' },
      { selector: 'input', name: 'Inputs', action: 'focus' },
      { selector: 'select', name: 'Dropdowns', action: 'click' },
      { selector: '[role="button"]', name: 'Role Buttons', action: 'click' }
    ];
    
    for (let i = 0; i < elementTypes.length; i++) {
      const elementType = elementTypes[i];
      const elements = await page.locator(elementType.selector).all();
      
      if (elements.length > 0) {
        console.log(`🎯 Found ${elements.length} ${elementType.name}`);
        
        // Interact with first few elements
        for (let j = 0; j < Math.min(elements.length, 3); j++) {
          try {
            if (elementType.action === 'click') {
              // For links, check if they're external or will navigate
              if (elementType.name === 'Links') {
                const href = await elements[j].getAttribute('href');
                if (href && !href.startsWith('http') && !href.startsWith('mailto')) {
                  await elements[j].click({ timeout: 2000 });
                  await page.waitForTimeout(1000);
                  await page.goBack();
                  await page.waitForTimeout(1000);
                }
              } else {
                await elements[j].click({ timeout: 2000 });
                await page.waitForTimeout(1000);
              }
            } else if (elementType.action === 'hover') {
              await elements[j].hover({ timeout: 2000 });
              await page.waitForTimeout(1000);
            } else if (elementType.action === 'focus') {
              await elements[j].focus({ timeout: 2000 });
              await page.waitForTimeout(1000);
            }
            
          } catch (error) {
            console.log(`⚠️ Could not interact with ${elementType.name} ${j+1}: ${error.message}`);
          }
        }
        
        // Screenshot after interactions
        await page.screenshot({ 
          path: `demo-screenshots/interactive-${String(i+2).padStart(2, '0')}-${elementType.name.toLowerCase()}.png` 
        });
      } else {
        console.log(`ℹ️ No ${elementType.name} found on page`);
      }
    }
    
    console.log('✅ Interactive Elements Demo completed');
  });

  test('Demo 5: 🔍 Form Testing and API Interactions', async ({ page }) => {
    console.log('🎬 Starting Form Testing Demo...');
    
    // Test registration form if available
    await page.goto('http://localhost:8000/register');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: 'demo-screenshots/form-01-register-initial.png', fullPage: true });
    
    // Look for common form elements
    const formInputs = {
      name: { selectors: ['input[name="name"]', 'input[placeholder*="name" i]', '#name'], value: 'Visual Test User' },
      email: { selectors: ['input[name="email"]', 'input[type="email"]'], value: `visual-test-${Date.now()}@example.com` },
      password: { selectors: ['input[name="password"]', 'input[type="password"]:first-of-type'], value: 'password123' },
      confirmPassword: { selectors: ['input[name="password_confirmation"]', 'input[type="password"]:last-of-type'], value: 'password123' }
    };
    
    let formFound = false;
    let filledFields = 0;
    
    for (const [fieldName, config] of Object.entries(formInputs)) {
      for (const selector of config.selectors) {
        const element = page.locator(selector).first();
        if (await element.count() > 0) {
          formFound = true;
          console.log(`📝 Found and filling ${fieldName} field`);
          
          await element.scrollIntoViewIfNeeded();
          await element.fill(config.value);
          await page.waitForTimeout(1000);
          filledFields++;
          
          // Screenshot after each field
          await page.screenshot({ 
            path: `demo-screenshots/form-02-filled-${fieldName}.png` 
          });
          
          break; // Found this field, move to next
        }
      }
    }
    
    if (formFound) {
      console.log(`✅ Found and filled ${filledFields} form fields`);
      
      // Look for submit button
      const submitSelectors = [
        'button[type="submit"]', 
        'input[type="submit"]', 
        'button:has-text("Register")', 
        'button:has-text("Sign Up")',
        '.btn-submit',
        '#submit'
      ];
      
      for (const selector of submitSelectors) {
        const submitBtn = page.locator(selector).first();
        if (await submitBtn.count() > 0) {
          console.log(`🔘 Found submit button, taking final screenshot`);
          await page.screenshot({ path: 'demo-screenshots/form-03-ready-to-submit.png', fullPage: true });
          
          // Note: We don't actually submit to avoid creating test data
          console.log(`ℹ️ Form ready for submission (skipping actual submit for demo)`);
          break;
        }
      }
    } else {
      console.log(`ℹ️ No registration form found, showing page as-is`);
    }
    
    // Test login page too
    await page.goto('http://localhost:8000/login');
    await page.waitForLoadState('networkidle');
    await page.screenshot({ path: 'demo-screenshots/form-04-login-page.png', fullPage: true });
    
    console.log('✅ Form Testing Demo completed');
  });

  test('Demo 6: 🎨 Visual Elements and UI Components', async ({ page }) => {
    console.log('🎬 Starting UI Components Demo...');
    
    await page.goto('http://localhost:8000');
    await page.waitForLoadState('networkidle');
    
    // Try to capture specific UI elements
    const uiElements = [
      { selector: 'header, .header, nav, .navbar', name: 'Header/Navigation', description: 'Top navigation area' },
      { selector: 'main, .main, .content, #content', name: 'Main Content', description: 'Primary content area' },
      { selector: 'aside, .sidebar, .aside', name: 'Sidebar', description: 'Side navigation or content' },
      { selector: 'footer, .footer', name: 'Footer', description: 'Bottom page information' },
      { selector: '.card, .product, .item, .box', name: 'Cards/Products', description: 'Product or content cards' },
      { selector: 'form, .form', name: 'Forms', description: 'Form elements' },
      { selector: '.hero, .banner, .jumbotron', name: 'Hero Section', description: 'Main banner or hero area' }
    ];
    
    for (let i = 0; i < uiElements.length; i++) {
      const element = uiElements[i];
      const locator = page.locator(element.selector).first();
      
      if (await locator.count() > 0) {
        try {
          console.log(`🎨 ${i+1}. Capturing ${element.name}: ${element.description}`);
          
          // Scroll element into view
          await locator.scrollIntoViewIfNeeded();
          await page.waitForTimeout(1000);
          
          // Highlight element by adding a border (temporarily)
          await page.evaluate((selector) => {
            const el = document.querySelector(selector);
            if (el) {
              el.style.outline = '3px solid #ff0000';
              el.style.outlineOffset = '2px';
            }
          }, element.selector);
          
          await page.waitForTimeout(1000);
          
          // Take screenshot of the specific element
          await locator.screenshot({ 
            path: `demo-screenshots/ui-element-${String(i+1).padStart(2, '0')}-${element.name.toLowerCase().replace(/[^a-z0-9]/g, '-')}.png` 
          });
          
          // Remove highlight
          await page.evaluate((selector) => {
            const el = document.querySelector(selector);
            if (el) {
              el.style.outline = '';
              el.style.outlineOffset = '';
            }
          }, element.selector);
          
          console.log(`📸 Captured: ${element.name}`);
          
        } catch (error) {
          console.log(`⚠️ Could not capture ${element.name}: ${error.message}`);
        }
      } else {
        console.log(`ℹ️ ${element.name} not found on page`);
      }
    }
    
    // Final full page screenshot
    await page.screenshot({ path: 'demo-screenshots/ui-complete-page.png', fullPage: true });
    
    console.log('✅ UI Components Demo completed');
  });
});