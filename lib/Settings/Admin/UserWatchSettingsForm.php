<?php

declare(strict_types=1);

namespace OCA\UserWatch\Settings\Admin;

use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\IDeclarativeSettingsForm;

class UserWatchSettingsForm implements IDeclarativeSettingsForm {
    public function getSchema(): array {
        
return [
            'id' => 'user_watch_settings_form', // unique form id
            'priority' => 10, // declarative section priority (ordering)
            'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN, // admin, personal
            'section_id' => 'server', // existing section id or your custom section id
            'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_INTERNAL, // external, internal (handled by core to store in appconfig and preferences)
            'title' => 'User watch', // NcSettingsSection name
            'description' => 'Configure user changes reactions parameters', // NcSettingsSection description
	    'doc_url' => '', // NcSettingsSection doc_url for documentation or help page, empty string if not needed
	    'fields' => [
		    [
			    'id' => 'on_password_updated_url', // configkey
			    'title' => 'Url to request on user password update', // name or label
			    'description' => 'Url where to send an http request from this server when updating passwords, whatch for this to react. Place {uid} in this url that wil lbe replaced with deleted uid', // hint
			    'type' => DeclarativeSettingsTypes::URL,
			    'placeholder' => 'https://yourexample/{uid}?token=arbitrary_to_be_sure', // input placeholder
			    'default' => '',
		    ],
		    [
			    'id' => 'on_created_url', // configkey
			    'title' => 'Url to request on user created', // name or label
			    'description' => 'Url where to send an http request from this server when creating user, whatch for this to react.', // hint
			    'type' => DeclarativeSettingsTypes::URL,
			    'placeholder' => 'https://yourexample/some?token=arbitrary_to_be_sure', // input placeholder
			    'default' => '',
		    ],
		    [
			    'id' => 'on_deleted_url', // configkey
			    'title' => 'Url to request on user deleted', // name or label
			    'description' => 'Url where to send an http request from this server when deleting user, whatch for this to react. Place {uid} in this url that wil lbe replaced with deleted uid', // hint
			    'type' => DeclarativeSettingsTypes::URL,
			    'placeholder' => 'https://yourexample/{uid}?token=arbitrary_to_be_sure', // input placeholder
			    'default' => '',
		    ],
		   /*[
                            // must be enabled WHEN issue https://github.com/nextcloud/server/issues/60903 is solved.
			    'id' => 'do_ping',
			    'title' => 'Ping',
			    'description' => 'The ping whenever there are user events',
			    'type' => DeclarativeSettingsTypes::CHECKBOX, // checkbox, multiple-checkbox
			    'label' => 'Enable ping',
			    'default' => false,
		   ],*/
		    [
			    'id' => 'path', // configkey
			    'title' => 'Dump path', // name or label
			    'description' => 'Path where users table dumps. Will create two files suffixed with \'_user_dump_sql.txt\' (sql) and \'_user_dump.txt\' (csv)', // hint
			    'type' => DeclarativeSettingsTypes::TEXT,
			    'placeholder' => '/home/user/safe_and_private_place/users_of_this_instance.sql', // input placeholder
			    'default' => '',
		    ],
            ]
        ];
    }
}
