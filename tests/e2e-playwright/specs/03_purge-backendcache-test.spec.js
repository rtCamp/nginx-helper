/**
 * WordPress dependencies
 */
 const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

 test.describe("Validate the purge backend cache", () => {
   test("Should able to purge the backend cache", async ({ admin, page }) => {
     await admin.visitAdminPage("/");

     await page.waitForTimeout(1000);
 
     await page.click("a[title='Purge Cache']");

     await page.waitForTimeout(2000);

     expect( page.locator( "div[class='updated'] p" )).toHaveText( 'Purge initiated' );
   });
 });
 
