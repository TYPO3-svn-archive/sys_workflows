<?php
/**
* Default  TCA_DESCR for "sys_workflows"
*/

$LOCAL_LANG = Array (
	'default' => Array (
		'.description' => 'Workflow records defines a certain task to be done by a To-Do item in the Task Center. ',
		'.details' => 'Basically a Workflow record lets you define a database table in which a record should be created, a review user and Backend usergroups from which the issuer and recipients of a workflow task (To-Do) is selected. It also contains information about what happens with the record attached to the workflow after it done. For instance you can configure a workflow to create the record in one page and by the event of finalizing the workflow it\'s automatically moved to another page and maybe even unhidden (published online).

Workflows are an integrated part of the Task Center\'s To-Do facility.',
		'title.description' => 'Enter a title as it will appear in the Task Centers list of To-Do workflows.',
		'description.description' => 'Describe the general purpose of the workflow. This will be visible in the To-Do Details view.',
		'hidden.description' => 'Check this to temporarily disable the workflow.',
		'tablename.description' => 'Enter the table in which the workflow should create a record when it\'s begun.',
		'tablename.details' => '<strong>Notice:</strong> The user/group which the workflow task is assigned to should be allowed to work on this table and furthermore have regular edit permissions set up in the Working area. In addition records from this table should be allowed to be created in the Working Area as defined by the Pagetype of the Working Area page.',
		'working_area.description' => 'The page where the new records from the above table are created and edited during the workflow process.',
		'working_area.details' => '<strong>Notice: </strong>See Workflow / Table description for information about permission setting!
',
		'_working_area.seeAlso' => 'sys_workflows:tablename',
		'allowed_groups.description' => 'Backend user groups, allowed to assign the workflow to other users through To-Do items.',
		'target_groups.description' => 'Backend user groups to which, this workflow can be assigned.',
		'review_users.description' => 'Review users (first is default) which should review items before they are finalized by the owner.',
		'final_unhide.description' => 'If you want the workflow to un-hide the record attached when the workflow is finalized, set this option.',
		'final_set_perms.description' => 'If you want page permissions to be changed when the workflow is finalized, set this option (Only if the Table is "Page").',
		'final_set_perms.details' => 'When you set this option it makes sense only if the table operated on by the workflow is "Page" because only pages has the permission fields. However it does no harm if the table is not "Page" - it just does not make sense.
When the option is set, save the record and then you can set the permissions afterwards.',
		'final_target.description' => 'Target page to which the record is moved when the workflow is finalized.',
		'final_perms_userid.description' => 'Insert the owner Backend user.',
		'final_perms_groupid.description' => 'Select the owner Backend usergroup.',
		'final_perms_user.description' => 'Enter page permissions for the Backend user.',
		'final_perms_group.description' => 'Enter page permissions for the Backend usergroups.',
		'final_perms_everybody.description' => 'Enter page permissions for Everybody (all Backend users).',
	),
	'dk' => Array (
	),
	'de' => Array (
	),
	'no' => Array (
	),
	'it' => Array (
	),
	'fr' => Array (
	),
	'es' => Array (
	),
	'nl' => Array (
	),
	'cz' => Array (
	),
	'pl' => Array (
	),
	'si' => Array (
	),
	'fi' => Array (
	),
	'tr' => Array (
	),
	'se' => Array (
	),
	'pt' => Array (
	),
	'ru' => Array (
	),
	'ro' => Array (
	),
	'ch' => Array (
	),
	'sk' => Array (
	),
	'lt' => Array (
	),
	'is' => Array (
	),
	'hr' => Array (
	),
	'hu' => Array (
	),
	'gl' => Array (
	),
	'th' => Array (
	),
	'gr' => Array (
	),
	'hk' => Array (
	),
	'eu' => Array (
	),
	'bg' => Array (
	),
	'br' => Array (
	),
	'et' => Array (
	),
	'ar' => Array (
	),
	'he' => Array (
	),
	'ua' => Array (
	),
);
?>