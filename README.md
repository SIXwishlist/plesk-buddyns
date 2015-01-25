# BuddyNS Plesk Extension
A Plesk extension for using BuddyNS Secondary DNS from Plesk Servers.
This extension adds to plesk's custom-backend dns feature to create, update and remove zones as plesk does.  buddyns.php is registered as a custom backend, and Plesk will pass the zone data to the script via stdin. 

## Plesk Zone Actions
* Create = Create
* Update = SyncNow! & create if not found
* Delete = Delete



