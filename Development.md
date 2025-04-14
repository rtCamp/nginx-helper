# Development
[ For internal contributors only ]

## Getting Started

To set up the repo locally, choose one of the following methods:

### Method 1 (For FastCGI or Srcache)

1. Create a WordPress website.
2. Clone the [nginx](https://github.com/nginx/nginx) source code in a separate directory.
3. Set up nginx using either FastCGI cache or Srcache module.
4. Manually compile the binary for Nginx.
5. Add a configuration block to redirect incoming requests to the WordPress website.
6. Clone this repository inside the `wp-content/plugins` directory.

### Method 2 (For Redis Cache)

1. Install EasyEngine by following the [instructions](https://easyengine.io/docs/install/).
2. Create a site with caching enabled using the command: `ee site create <site_name> --cache`

3. Clone this repository inside the `wp-content/plugins` directory of the newly created site.

## Testing

To verify if a page is cached:

1. Enable the timestamp option in Nginx Helper from the WordPress Admin Page:
- This adds a comment with cache time and query count.
- Cached pages should show only 1 query.

2. Manually check the Redis database:
- Search for keys beginning with the prefix specified in your Redis config, followed by a URL.

## Contributing

### Raising a Pull Request (PR)

1. Create a new branch from `master` for your changes.
2. Make your changes and commit them to your branch.
3. Raise a PR targeting the `develop` branch.
4. Assign the PR to a reviewer.
5. Address any feedback and make necessary changes.
6. Once approved, merge the PR into the `develop` branch.

### Merging to Master

1. After thorough testing on the `develop` branch, create a new PR.
2. Set the source branch as the original feature branch that was merged into `develop`.
3. Set the target branch as `master`.
4. Assign reviewers and await final approval.
5. Once approved, merge the PR into the `master` branch.