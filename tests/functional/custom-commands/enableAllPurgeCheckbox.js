/* @author: Prabuddha Chakraborty */

exports.command = function(url, username, password) {
  var browser = this;
  var data = browser.globals;
  browser
    .pause(100)
    .getAttribute('#purge_homepage_on_edit', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {

        browser.click('#purge_homepage_on_edit');
      }
    })
    .getAttribute('#purge_homepage_on_del', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {
        browser.click('#purge_homepage_on_del');
      }
    })
    .getAttribute('#purge_page_on_mod', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {
        browser.click('#purge_page_on_mod');
      }
    })
    .getAttribute('#purge_page_on_new_comment', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {
        browser.click('#purge_page_on_new_comment');
      }
    })
    .getAttribute('#purge_page_on_deleted_comment', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {
        browser.click('#purge_page_on_deleted_comment');
      }
    })
    .getAttribute('#purge_archive_on_edit', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {
        browser.click('#purge_archive_on_edit');
      }
    })
    .getAttribute('#purge_archive_on_del', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {
        browser.click('#purge_archive_on_del');
      }
    })
    .getAttribute('#purge_archive_on_new_comment', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {
        browser.click('#purge_archive_on_new_comment');
      }
    })
    .getAttribute('#purge_archive_on_deleted_comment', "checked", function(result) {
      if (result.value) {
        console.log('check box is already enabled');
      } else {
        browser.click('#purge_archive_on_deleted_comment');
      }
    })
  
  return this;
};
