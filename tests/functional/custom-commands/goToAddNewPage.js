/* @author: Prabuddha Chakraborty */

exports.command = function(url, username, password) {
  var client = this;
  var data = client.globals;
  var dash = data.URLS.LOGIN + 'wp-admin/post-new.php?post_type=page'
  client
    .pause(100)
    .url(dash)
    .getTitle(function(title) {
      console.log("We are in Add new Post Page :" + title);
    })
  return this;
};
