#!/usr/bin/env bash

set -ex

######################################################
######################## VARS ########################
SITE_NAME='nginx-helper.com'
SITE_ROOT="/var/www/$SITE_NAME/htdocs"
SITE_URL="http://$SITE_NAME/"
function ee() { wo "$@"; }
#####################################################

# Start required services for site creation
function start_services() {

    echo "Starting services"
    git config --global user.email "nobody@example.com"
    git config --global user.name "nobody"
    rm /etc/nginx/conf.d/stub_status.conf /etc/nginx/sites-available/22222 /etc/nginx/sites-enabled/22222
    rm -rf /var/www/22222
    ee stack start --nginx --mysql --php74
    ee stack status --nginx --mysql --php74
}


# Create, setup and populate learn.rtcamp.com base site with data
function create_and_configure_site () {

    ee site create $SITE_NAME --wp --php74
    cd $SITE_ROOT/wp-content/plugins/
    rm -rf nginx-helper
    ls
    mkdir nginx-helper
    rsync -azh $GITHUB_WORKSPACE/ $SITE_ROOT/wp-content/plugins/nginx-helper
    echo "127.0.0.1 $SITE_NAME" >> /etc/hosts
    ls
    wp plugin activate nginx-helper --allow-root
    wp user create automation automation@example.com --role=administrator --user_pass=automation --allow-root
}


# Install WPe2e dependency
function install_playwright_package () {

    cd $GITHUB_WORKSPACE/tests/e2e-playwright
    npm install

}

#build packages
function build_package(){
    cd $GITHUB_WORKSPACE/tests/e2e-playwright
    npm run build
}

function install_playwright(){
    cd $GITHUB_WORKSPACE/tests/e2e-playwright
    npx playwright install
}

# Run test for new deployed site
function run_playwright_tests () {
    cd $GITHUB_WORKSPACE/tests/e2e-playwright
    npm run test-e2e:playwright -- specs/
}

function maybe_install_node_dep() {

	if [[ -n "$NODE_VERSION" ]]; then

		echo "Setting up $NODE_VERSION"
		NVM_LATEST_VER=$(curl -s "https://api.github.com/repos/nvm-sh/nvm/releases/latest" |
			grep '"tag_name":' |
			sed -E 's/.*"([^"]+)".*/\1/') &&
			curl -fsSL "https://raw.githubusercontent.com/nvm-sh/nvm/$NVM_LATEST_VER/install.sh" | bash
		export NVM_DIR="$([ -z "${XDG_CONFIG_HOME-}" ] && printf %s "${HOME}/.nvm" || printf %s "${XDG_CONFIG_HOME}/nvm")"
		[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" # This loads nvm

		nvm install "$NODE_VERSION"
		nvm use "$NODE_VERSION"

		[[ -z "$NPM_VERSION" ]] && NPM_VERSION="latest" || echo ''
		export npm_install=$NPM_VERSION
		curl -fsSL https://www.npmjs.com/install.sh | bash
	fi
}

function main() {
    start_services
    create_and_configure_site
    maybe_install_node_dep
    install_playwright_package
    build_package
    install_playwright
    run_playwright_tests
}

main
