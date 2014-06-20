<?php 

// declare the config global array, where all deployment-specific things live
$config = [];

// this is appended to passwords before hashing; CHANGE IT for your installation
$config['SHA1_PEPPER'] = "security_by_obscurity";

// this is the password for the admin user 
// (can upload to the data folder & erase saved time-tables)
$config['ADMIN_PASS'] = "mynameisluis";

// folder for saved time-tables
$config['SAVE_DIR'] = "./save";

// folder with the up-to-date data files
$config['DATA_DIR'] = "./data";

// folder with hashes of login + pass + pepper
$config['PASS_DIR'] = "./pass";

// cookies
$config['cookie_path'] = '/';
$config['cookie_timeout'] = 60 * 60 * 1; // 1h en segundos
$config['cookie_name'] = 'PLANIFICADOR_DOCENCIA';
