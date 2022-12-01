/**
 * WordPress dependencies
 */
const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

test.describe("Enable the debug option", () => {
  test("Should able to validate the Nginx Timestamp in HTML", async ({
    admin,
    page,
  }) => {
    await admin.visitAdminPage("/");

    await page.hover('role=link[name="Settings"i]');

    await page.click('role=link[name="Nginx Helper"i]');

    expect(page.locator(".rt_option_title")).toHaveText("Nginx Settings");

    if (await page.locator("#enable_log").uncheck) {
      await page.click("#enable_log");
    }
    if (await page.locator("#enable_stamp").uncheck) {
      await page.click("#enable_stamp");
    }

    await page.click("#smart_http_expire_save");

    await page.waitForTimeout(1000);
    expect(page.locator("div[class='updated'] p")).toHaveText(
      "Settings saved."
    );
  });

  test("Should able to validate the timestamp in posts.", async ({
    admin,
    page,
    pageUtils,
  }) => {
    await admin.createNewPost({ title: "Test post" });

    await page.click(
      ".components-button.editor-post-publish-panel__toggle.editor-post-publish-button__button.is-primary"
    );

    await page.click(
      ".components-button.editor-post-publish-button.editor-post-publish-button__button.is-primary"
    );

    await page.waitForTimeout(1000);

    await page.click('[aria-label="Editor publish"] >> text=View Post', {
      timeout: 50000,
    });
    await page.waitForTimeout(1000);

    await pageUtils.pressKeyWithModifier("ctrl", "U");

    // Store the page source in the Array and check the timestamp value.
    var pagecontent = [];
    pagecontent = await page.content();
    pagecontent.includes("Cached using Nginx-Helper");
  });
});
