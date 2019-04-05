workflow "Deploy" {
  resolves = ["WordPress Plugin Deploy"]
  on = "push"
}

# Filter for tag
action "tag" {
    uses = "actions/bin/filter@master"
    args = "tag"
}

action "WordPress Plugin Deploy" {
  needs = ["tag"]
  uses = "rtcamp/github-actions-library/wp-plugin-deploy@master"
  secrets = ["WORDPRESS_USERNAME", "WORDPRESS_PASSWORD"]
  env = {
    SLUG = "nginx-helper"
    EXCLUDE_LIST = ".gitattributes .gitignore .travis.yml README.md deploy.sh readme.sh tests map.conf nginx.log"
  }
}
