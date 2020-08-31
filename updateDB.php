<?php
	// check this file's MD5 to make sure it wasn't called before
	$prevMD5=@implode('', @file(dirname(__FILE__).'/setup.md5'));
	$thisMD5=md5(@implode('', @file("./updateDB.php")));
	if($thisMD5==$prevMD5) {
		$setupAlreadyRun=true;
	}else{
		// set up tables
		if(!isset($silent)) {
			$silent=true;
		}

		// set up tables
		setupTable('Shortlisted_Applicant_Details', "create table if not exists `Shortlisted_Applicant_Details` (   `Serial_Number` INT not null , primary key (`Serial_Number`), `Shortlist_date` DATE null , `First_Name` VARCHAR(40) null , `Middle_Name` VARCHAR(40) null , `Last_Name` VARCHAR(40) null , `Level_of_Education` VARCHAR(40) null , `DOB` DATE null , `Age` VARCHAR(40) null , `District_of_Origin` VARCHAR(40) null , `Recruitment_centre` VARCHAR(40) null , `Gender` VARCHAR(40) null , `Telephone_Contact` VARCHAR(40) null , `Religion` VARCHAR(40) null , `Nationality` VARCHAR(40) null default 'Ugandan' , `NIN` VARCHAR(40) null , `Tribe` VARCHAR(40) null , `Special_attribute` VARCHAR(40) null , `OLevel_School` VARCHAR(40) null , `English_Grade` VARCHAR(40) null , `Math_Grade` VARCHAR(40) null , `Description` TEXT null , `Total_Mark` VARCHAR(40) null ) CHARSET utf8", $silent, array( " ALTER TABLE `Shortlisted_Applicant_Details` CHANGE `Level_of_Education` `Level_of_Education` VARCHAR(40) null "));
		setupTable('MEDICAL_EXAMINATION', "create table if not exists `MEDICAL_EXAMINATION` (   `Medical_Examination_ID` INT not null , primary key (`Medical_Examination_ID`), `First_name` VARCHAR(40) null , `Middle_name` VARCHAR(40) null , `Last_name` VARCHAR(40) null , `Name_of_Medical_officer` VARCHAR(40) null , `Examinaton_date` DATE null , `Result` VARCHAR(40) null , `Remark` VARCHAR(100) null , `Place_of_Examination` VARCHAR(40) null ) CHARSET utf8", $silent);
		setupTable('INTERVIEW_RESULTS', "create table if not exists `INTERVIEW_RESULTS` (   `Interview_ID` VARCHAR(40) not null , primary key (`Interview_ID`), `Written_interview` INT null , `Oral_Interview` INT null , `Total_Mark` VARCHAR(40) null , `Interview_Date` DATE null , `Remark` VARCHAR(100) null , `Selection` VARCHAR(40) null ) CHARSET utf8", $silent);


		// save MD5
		if($fp=@fopen(dirname(__FILE__).'/setup.md5', 'w')) {
			fwrite($fp, $thisMD5);
			fclose($fp);
		}
	}


	function setupIndexes($tableName, $arrFields) {
		if(!is_array($arrFields)) {
			return false;
		}

		foreach($arrFields as $fieldName) {
			if(!$res=@db_query("SHOW COLUMNS FROM `$tableName` like '$fieldName'")) {
				continue;
			}
			if(!$row=@db_fetch_assoc($res)) {
				continue;
			}
			if($row['Key']=='') {
				@db_query("ALTER TABLE `$tableName` ADD INDEX `$fieldName` (`$fieldName`)");
			}
		}
	}


	function setupTable($tableName, $createSQL='', $silent=true, $arrAlter='') {
		global $Translation;
		ob_start();

		echo '<div style="padding: 5px; border-bottom:solid 1px silver; font-family: verdana, arial; font-size: 10px;">';

		// is there a table rename query?
		if(is_array($arrAlter)) {
			$matches=array();
			if(preg_match("/ALTER TABLE `(.*)` RENAME `$tableName`/", $arrAlter[0], $matches)) {
				$oldTableName=$matches[1];
			}
		}

		if($res=@db_query("select count(1) from `$tableName`")) { // table already exists
			if($row = @db_fetch_array($res)) {
				echo str_replace("<TableName>", $tableName, str_replace("<NumRecords>", $row[0],$Translation["table exists"]));
				if(is_array($arrAlter)) {
					echo '<br>';
					foreach($arrAlter as $alter) {
						if($alter!='') {
							echo "$alter ... ";
							if(!@db_query($alter)) {
								echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
								echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
							}else{
								echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
							}
						}
					}
				}else{
					echo $Translation["table uptodate"];
				}
			}else{
				echo str_replace("<TableName>", $tableName, $Translation["couldnt count"]);
			}
		}else{ // given tableName doesn't exist

			if($oldTableName!='') { // if we have a table rename query
				if($ro=@db_query("select count(1) from `$oldTableName`")) { // if old table exists, rename it.
					$renameQuery=array_shift($arrAlter); // get and remove rename query

					echo "$renameQuery ... ";
					if(!@db_query($renameQuery)) {
						echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
						echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
					}else{
						echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
					}

					if(is_array($arrAlter)) setupTable($tableName, $createSQL, false, $arrAlter); // execute Alter queries on renamed table ...
				}else{ // if old tableName doesn't exist (nor the new one since we're here), then just create the table.
					setupTable($tableName, $createSQL, false); // no Alter queries passed ...
				}
			}else{ // tableName doesn't exist and no rename, so just create the table
				echo str_replace("<TableName>", $tableName, $Translation["creating table"]);
				if(!@db_query($createSQL)) {
					echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
					echo '<div class="text-danger">' . $Translation['mysql said'] . db_error(db_link()) . '</div>';
				}else{
					echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
				}
			}
		}

		echo "</div>";

		$out=ob_get_contents();
		ob_end_clean();
		if(!$silent) {
			echo $out;
		}
	}
?>