/* @author: Prabuddha Chakraborty */

module.exports = {
  'Step One : Configure nginx-helpers settings from dashboard  ': function(browser) {
    var data = browser.globals;
    browser
      .maximizeWindow()
      .wplogin(data.URLS.LOGIN, data.TESTADMINUSERNAME, data.TESTADMINPASSWORD)
      .nginxSettings()
      .pause(2000)
      .getAttribute('#enable_purge', "checked", function(result) {
        if (result.value) {
          console.log('check box is already enabled');
        } else {
          browser.click('#enable_purge');
          browser.click('#smart_http_expire_save');
        }
      })
      .enableAllPurgeCheckbox()
      .click('#purge_method_unlink_files')
      .click('#smart_http_expire_save')
      .pause(1000)
},
  'step two: Upload post to check ': function(browser) {
    var data = browser.globals;
    browser
      .goToAddNewPost()
      .clearValue('#title')
      .clearValue('textarea[id="content"]')
      .setValue('#title', 'test title for nightwatch test')
      .setValue('textarea[id="content"]', "test post for nightwatch test")
      .pause(1000)
      .click('#publish')
      .pause(2000)
      .wplogout()
      .url(data.URLS.LOGIN)
      .assert.containsText("#main", "test title for nightwatch test")
      .assert.containsText("#main", "test post for nightwatch test")
  },

  'Step three : Update Post Home Page Check ': function(browser) {
    var data = browser.globals;
    browser
      .wplogin(data.URLS.LOGIN, data.TESTADMINUSERNAME, data.TESTADMINPASSWORD)
      .url(data.URLS.LOGIN)
      .click('.post-edit-link')
      .clearValue('#title')
      .clearValue('textarea[id="content"]')
      .setValue('#title', 'test title for nightwatch test')
      .setValue('textarea[id="content"]', "test post for nightwatch test update")
      .click('#publish')
      .wplogout()
      .url(data.URLS.LOGIN)
      .assert.containsText("#main", "test title for nightwatch test")
      .assert.containsText("#main", "test post for nightwatch test update")
},
  'Step four : post comment check ': function(browser) {
    var data = browser.globals;
    browser
      .wplogin(data.URLS.LOGIN, data.TESTADMINUSERNAME, data.TESTADMINPASSWORD)
      .url(data.URLS.LOGIN)
      .click('xpath', '//a[text()="test title for nightwatch test"]')
      .setValue('textarea[name="comment"]', 'this is a demo test comment')
      .click('input[value="Post Comment"]')
      .assert.containsText("#main", "this is a demo test comment")
      .wplogout()
},

  'Step five : Update Post check ': function(browser) {
    var data = browser.globals;
    browser
      .url(data.URLS.LOGIN)
      .click('xpath', '//a[text()="test title for nightwatch test"]')
      .assert.containsText("#main", "test title for nightwatch test")
      .assert.containsText("#main", "test post for nightwatch test update")
      .assert.containsText("#main", "this is a demo test comment")
},
  'Step six : Update  Archives check (Uncategorized)': function(browser) {
    var data = browser.globals;
    browser
      .url(data.URLS.LOGIN)
      .click('xpath', '//a[text()="Uncategorized"]')
      .assert.containsText("#main", "test title for nightwatch test")
      .assert.containsText("#main", "test post for nightwatch test update")
  },

/*
  'Step seven : Update  Archives check (Admin && Date)': function(browser) {
    var data = browser.globals;
    browser
      .url(data.URLS.LOGIN + 'author/admin/')

      .assert.containsText("#main", "test title for nightwatch test")
      .assert.containsText("#main", "test post for nightwatch test update")
      .url(data.URLS.LOGIN)
      .click('.entry-date')
      .verify.containsText("#main", "test title for nightwatch test")
      .verify.containsText("#main", "test post for nightwatch test update")
  },
*/
  'Step eight : Set as trash ': function(browser) {
    var data = browser.globals;
    browser
      .wplogin(data.URLS.LOGIN, data.TESTADMINUSERNAME, data.TESTADMINPASSWORD)
      .url(data.URLS.LOGIN)
      .click('.post-edit-link')
      .click('xpath', '//a[text()="Move to Trash"]')
      .pause(2500)
      .wplogout()
      .url(data.URLS.LOGIN);
    browser.expect.element('#main').text.to.not.contain('test title for nightwatch test');
    browser.expect.element('#main').text.to.not.contain('test post for nightwatch test update');
  },

  'Step nine : Set as Draft ': function(browser) {
    var data = browser.globals;
    browser
      .wplogin(data.URLS.LOGIN, data.TESTADMINUSERNAME, data.TESTADMINPASSWORD)
      .goToAddNewPost()
      .clearValue('#title')
      .clearValue('textarea[id="content"]')
      .setValue('#title', 'test title for draft post test')
      .setValue('textarea[id="content"]', "test post for draft post test")
      .pause(1000)
      .click('#publish')
      .pause(2000)
      .url(data.URLS.LOGIN)
      .assert.containsText("#main", "test title for draft post test")
      .assert.containsText("#main", "test post for draft post test")
      .click('.post-edit-link')
      .click('.edit-post-status')
      .pause(300)
      .click('option[value="draft"]')
      .pause(500)
      .click('#publish')
      .pause(2500)
      .wplogout()
      .url(data.URLS.LOGIN);
    browser.expect.element('#main').text.to.not.contain('test title for draft post test');
    browser.expect.element('#main').text.to.not.contain('test post for draft post test');
    browser
      .end()
  }

};
