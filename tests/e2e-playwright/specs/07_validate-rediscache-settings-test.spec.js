/**
 * WordPress dependencies
 */
 const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

 test.describe("Validate the radis cache settings", () => {
   test("Should able to validate the radis settings", async ({ admin, page }) => {
     await admin.visitAdminPage("/");
 
     await page.hover('role=link[name="Settings"i]');
 
     await page.click('role=link[name="Nginx Helper"i]');
    
     await page.waitForTimeout(1000);
 
     expect(page.locator(".rt_option_title")).toHaveText("Nginx Settings");

     // Validate the radis cache settings. 
     await page.click( 'role=radio[name="Redis cache"i]' );
     expect( page.locator("div[class='postbox cache_method_redis'] span")).toBeVisible();

    await page.click("#smart_http_expire_save");

    await page.waitForTimeout(1000);
    expect(page.locator("div[class='updated'] p")).toHaveText(
      "Settings saved."
    );
   });
 });
 