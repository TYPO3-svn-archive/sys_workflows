#
# Table structure for table 'sys_workflows'
#
CREATE TABLE sys_workflows (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  description text NOT NULL,
  tablename varchar(60) DEFAULT '' NOT NULL,
  tablename_ver varchar(60) DEFAULT '' NOT NULL,
  tablename_del varchar(60) DEFAULT '' NOT NULL,
  tablename_move varchar(60) DEFAULT '' NOT NULL,
  working_area int(11) DEFAULT '0' NOT NULL,
  allowed_groups int(11) DEFAULT '0' NOT NULL,
  target_groups tinyblob NOT NULL,
  review_users int(11) DEFAULT '0' NOT NULL,
  publishing_users int(11) DEFAULT '0' NOT NULL,
  final_target int(11) DEFAULT '0' NOT NULL,
  final_unhide tinyint(4) DEFAULT '0' NOT NULL,
  final_perms_userid int(11) DEFAULT '0' NOT NULL,
  final_perms_groupid int(11) DEFAULT '0' NOT NULL,
  final_perms_user tinyint(11) DEFAULT '0' NOT NULL,
  final_perms_group tinyint(11) DEFAULT '0' NOT NULL,
  final_perms_everybody tinyint(11) DEFAULT '0' NOT NULL,
  hidden tinyint(4) DEFAULT '0' NOT NULL,
  final_set_perms tinyint(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid)
);

#
# Table structure for table 'sys_workflows_algr_mm'
#
CREATE TABLE sys_workflows_algr_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'sys_workflows_rvuser_mm'
#
CREATE TABLE sys_workflows_rvuser_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'sys_workflows_pubuser_mm'
#
CREATE TABLE sys_workflows_pubuser_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'sys_todos'
#
CREATE TABLE sys_todos (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
  type varchar(11) DEFAULT '' NOT NULL,
  deadline int(11) unsigned DEFAULT '0' NOT NULL,
  finished tinyint(3) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  description text NOT NULL,
  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY cruser_id (cruser_id)
);

#
# Table structure for table 'sys_todos_users_mm'
#
CREATE TABLE sys_todos_users_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  reject_user int(11) DEFAULT '0' NOT NULL,
  state tinyblob NOT NULL,
  reject_state tinyblob NOT NULL,
  status tinyint(4) DEFAULT '0' NOT NULL,
  status_log mediumblob NOT NULL,
  is_read tinyint(4) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  mm_uid int(11) DEFAULT '0' NOT NULL auto_increment,
  deleted tinyint(4) DEFAULT '0' NOT NULL,
  action varchar(50) DEFAULT '' NOT NULL,
  tablename varchar(50) DEFAULT '' NOT NULL,
  idref varchar(50) DEFAULT '' NOT NULL,
  rec_reference varchar(50) DEFAULT '' NOT NULL,
  finalized tinyint(4) DEFAULT '0' NOT NULL,
  finished_instance tinyint(4) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign),
  PRIMARY KEY (mm_uid)
);

#
# Table structure for table 'sys_todos_users_mm'
#
CREATE TABLE sys_todos_notify_users_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);
