
exports.command = function(url, username, password) {
  var client = this;
  var loginurl = url + "/wp-admin";

  client
    .url(loginurl)
    .pause(500)
    .setValue('input[id="user_login"]', username)
    .setValue('input[id="user_pass"]', password)
    .click('input[type=submit]')

  return this;
};
