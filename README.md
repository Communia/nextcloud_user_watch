# User Watch

Whenever a user is updated, created or password changed, it will 
dump users table to a specified path (suffixed with `_user_dump.txt`) and 
dump users table to a specified path (suffixed with `_user_dump_sql.txt`) 
and will ping a specified url.

It also exposes the api endpoint `https://example.org/ocs/v1.php/apps/user_watch/v1/dump`
to (by now) hardcoded groups `admin` and `dovecot_provider` to be able to 
obtain the same data as dump but in json format and using nextcloud app api
to be able to use authentication.

Settings on `/settings/admin`

