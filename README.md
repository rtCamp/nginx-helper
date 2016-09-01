# Multisite Fixes

## Changes from main repo
- Fixes purging of single sites in a multisite setup (Important: Redis Only!! FastCGI Cache support is not implemented yet.)

### Details
Now when you click the purge cache link in the toolbar on a site, will only purge the cache of that site. As mentioned above, this is only supported in Redis atm, as Redis supports purging with wildcards.
The big red button in Nginx Helper settings still purges the entire cache.

### Tests
Tested on a "subfolder" multisite setup with Redis Cache.
Should also work in a "subdomain" multisite, but is untested.
