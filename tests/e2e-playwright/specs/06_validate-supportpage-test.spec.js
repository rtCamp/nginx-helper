/**
 * WordPress dependencies
 */
 const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

 test.describe("Validate the support page", () => {
   test("Should able to validate the support page", async ({ admin, page }) => {
     await admin.visitAdminPage("/");
 
     await page.hover('role=link[name="Settings"i]');
 
     await page.click('role=link[name="Nginx Helper"i]');
     await page.screenshot({path: "uploads/img3.png"});
    
     await page.waitForTimeout(1000);
 
     expect(page.locator(".rt_option_title")).toHaveText("Nginx Settings");

     await page.click( 'role=link[name="Support"i]' );

     expect(page.locator( "div[id='post-body-content'] span" )).toHaveText( 'Support Forums' );
   });
 });
 