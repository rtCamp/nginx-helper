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

    // Assert that the checkbox is disabled
    await expect(page.locator("input#enable_log")).toBeDisabled();

    expect(
      page.locator(
        "#post_form > div:nth-child(5) > div > table > tbody > tr:nth-child(1) > td > pre"
      )
    ).toHaveText(
      "[NOTE: To activate the logging feature, you must define the constant define( 'NGINX_HELPER_LOG', true ) in your wp-config.php]"
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
