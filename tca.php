<?php

	
// ******************************************************************
// sys_workflows
// ******************************************************************
$TCA['sys_workflows'] = Array (
	'ctrl' => $TCA['sys_workflows']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title,description,tablename,working_area,allowed_groups,review_users,final_target,final_unhide,final_set_perms'
	),
	'columns' => Array (	
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',	
				'size' => '25',
				'max' => '256',
				'eval' => 'trim,required'
			)
		),
		'description' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.description',
			'config' => Array (
				'type' => 'text',
				'rows' => 10,
				'cols' => 48
			)
		),
		'hidden' => Array (
			'label' => 'Deactivated:',
			'config' => Array (
				'type' => 'check'
			)
		),
		'tablename' => Array (
			'label' => 'Table:',
			'config' => Array (
				'type' => 'select',
				'special' => 'tables',
				'size' => '1',
				'items' => Array(
					Array('','')
				)
			)
		),
		'working_area' => Array (
			'label' => 'Draft page:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'allowed_groups' => Array (
			'label' => 'Groups allowed to assign workflow:',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'MM' => 'sys_workflows_algr_mm',
				'size' => '3',
				'maxitems' => '20'
			)
		),
		'target_groups' => Array (
			'label' => 'Target groups for workflow:',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'size' => '3',
				'maxitems' => '20'
			)
		),
		'review_users' => Array (
			'label' => 'Review users:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users',
				'MM' => 'sys_workflows_rvuser_mm',
				'size' => '3',
				'maxitems' => '200',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),		
		'publishing_users' => Array (
			'label' => 'Review users:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users',
				'MM' => 'sys_workflows_pubuser_mm',
				'size' => '3',
				'maxitems' => '200',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),		
		'final_unhide' => Array (
			'label' => 'Unhide when finalizing:',
			'config' => Array (
				'type' => 'check'
			)
		),
		'final_set_perms' => Array (
			'label' => 'Set permissions when finalizing ("page" only):',
			'config' => Array (
				'type' => 'check'
			)
		),
		'final_target' => Array (
			'label' => 'Move to page when finalizing:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'final_perms_userid' => Array (
			'label' => 'Owner User:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0'
			)
		),		
		'final_perms_groupid' => Array (
			'label' => 'Owner Group:',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'items' => Array(
					Array('','')
				)
			)
		),		
		'final_perms_user' => Array (
			'exclude' => 1,
			'label' => 'Owner User Access:',
			'config' => Array (
				'type' => 'check',
				'items' => Array (	
					Array('Show page', ''),
					Array('Edit page', ''),
					Array('Delete page', ''),
					Array('New pages', ''),
					Array('Edit content', '')
				),
				'cols' => 5
			)
		),
		'final_perms_group' => Array (
			'exclude' => 1,
			'label' => 'Owner Group Access:',
			'config' => Array (
				'type' => 'check',
				'items' => Array (	
					Array('Show page', ''),
					Array('Edit page', ''),
					Array('Delete page', ''),
					Array('New pages', ''),
					Array('Edit content', '')
				),
				'cols' => 5
			)
		),
		'final_perms_everybody' => Array (
			'exclude' => 1,
			'label' => 'Everybody Access:',
			'config' => Array (
				'type' => 'check',
				'items' => Array (	
					Array('Show page', ''),
					Array('Edit page', ''),
					Array('Delete page', ''),
					Array('New pages', ''),
					Array('Edit content', '')
				),
				'cols' => 5
			)
			),
		"existing_record" => Array (        
			"exclude" => 1,        
			"label" => 'Existing record',        
			"config" => Array (
        "type" => "group",    
				"internal_type" => "db",    
				"allowed" => "pages",    
				"size" => 1,    
				"minitems" => 0,
				"maxitems" => 1,
				)
			),

	),
	'types' => Array (					
										/*		'0' => Array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2,description,tablename;;;;3-3-3,working_area,existing_record,allowed_groups,target_groups,review_users,--div--,final_target;;;;5-5-5,final_unhide,final_set_perms;;;;5-5-5'),*/
		'0' => Array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2,description,--div--;table,tablename;;;;3-3-3,working_area,existing_record,--div--;roles,allowed_groups,target_groups,review_users,publishing_users,--div--;misc,final_target;;;;5-5-5,final_unhide,final_set_perms;;;;5-5-5,final_perms_userid,final_perms_groupid,final_perms_user,final_perms_group,final_perms_everybody')
	)
);





?>