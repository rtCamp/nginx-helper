/* @author: Prabuddha Chakraborty */

exports.command = function(url, username, password) {
  var client = this;
  var data = client.globals;
  var dash = data.URLS.LOGIN + 'wp-admin/post-new.php'
  client
    .pause(100)
    .url(dash)
    .waitForElementVisible('body', 3000)
    .getTitle(function(title) {
      console.log("We are in Add new Post Page :" + title);
    })

  return this;
};
