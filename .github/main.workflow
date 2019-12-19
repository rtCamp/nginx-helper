workflow "Deploy" {
  on = "create"
  resolves = ["WordPress Plugin Deploy"]
}

# Filter for tag
action "tag" {
    uses = "actions/bin/filter@master"
    args = "tag"
}

action "WordPress Plugin Deploy" {
  needs = ["tag"]
  uses = "rtCamp/action-wordpress-org-plugin-deploy@master"
  secrets = ["WORDPRESS_USERNAME", "WORDPRESS_PASSWORD"]
  env = {
    SLUG = "nginx-helper"
    EXCLUDE_LIST = ".gitattributes .gitignore .travis.yml README.md deploy.sh readme.sh tests map.conf nginx.log wercker.yml vendor"
  }
}
