/**
 * WordPress dependencies
 */
const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

test.describe("Validate the settings", () => {
  test("Should able to validate the settings", async ({ admin, page }) => {
    await admin.visitAdminPage("/");

    await page.hover('role=link[name="Settings"i]');

    await page.click('role=link[name="Nginx Helper"i]');
    
    await page.waitForTimeout(2000);

    expect(page.locator(".rt_option_title")).toHaveText("Nginx Settings");
  });
});
