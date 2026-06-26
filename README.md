# User Watch

Whenever a user is updated, created or password changed, it will 
dump users table to a specified path (suffixed with `_user_dump.txt`) and 
dump users table to a specified path (suffixed with `_user_dump_sql.txt`).

Also it will send requests:

When **creating a new user** will send a POST request to `on_created_url` with 
a json like this:
`  {
  "uid": "userx",
  "displayname": "X User",
  "password": "3|$argon2id$v=19$m=65536,t=4,p=1$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "uid_lower": "userx",
  "groups": [
    "mygroup",
    "student"
  ]
}`

When **updating a password** of a user will send a PUT request to `on_password_updated_url`,
taking in account that {uid} `on_password_updated_url` in url will be replaced 
by the uid of the modified user) with a json like this:
`  {
  "uid": "userx",
  "displayname": "X User",
  "password": "3|$argon2id$v=19$m=65536,t=4,p=1$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
  "uid_lower": "userx",
  "groups": [
    "mygroup",
    "student"
  ]
}`

When deleting a user will send a delete request to `on_deleted_url`, taking in
account that {uid}  `on_deleted_url` in url will be replaced by the uid of the 
deleted user.



It also exposes the api endpoint `https://example.org/ocs/v1.php/apps/user_watch/v1/dump`
to (by now) hardcoded groups `admin` and `dovecot_provider` to be able to 
obtain the same data as dump but in json format and using nextcloud app api
to be able to use authentication.



Settings are on `/settings/admin`.

