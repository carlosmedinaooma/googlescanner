<?php
	//Database string
	$dbCon['dbHost'] = "10.66.12.194";
	$dbCon['dbUid'] = "fgenieuser";
	$dbCon['dbPw'] = "chl0eB0nnie";//"chl0eB0nnie";
	$dbCon['db'] = "phoneofficer";
	
	//$api_key="AIzaSyC6u5tblWKDDgWPFL-9twv5ckt9d5NYzW4"; //Google API key
        $api_key[0]="AIzaSyAeU5aB81_uAoN_AfNOY57EZCkZePh2Nxs"; //Google API key 11Feb2015 cmedina80
        $api_key[1]="AIzaSyBWJ9Hs8xp_DAMc5KzLyGdcIHgM37vVxJE"; //Google api KEY 5 Feb 2015 mcboda
        $api_key[2]="AIzaSyBgnAj2lgniAdA011rNR25oKwADvaPyvoQ"; // Google api key 27 Feb jimenez.maria.20151@gmail.com
        $api_key[3]="AIzaSyCR8S6_n2gYS9Mgxpti1MsP1vqCCA0ykoM"; // Google api key 27 Feb molina.alberto.20151
        $api_key[4]="AIzaSyAMVSIG0j5ZiNKU8PPO_Vg3nOn8fSk9CwU"; // Google api key 05 Mar camila.yanez.2015@gmail.com
        $api_key[5]="AIzaSyDPjBwRJiBdII8v_c2Jqhlew8WVYxzQISA"; // Google api key 05 Mar james.novely.89@gmail.com
		$api_key[6]="AIzaSyDUvMpYCPU9QSeCB267RBdQnoARUZVdCnE"; // Google api key 01 Abr martinezgustavoaxtel@gmail.com
        $api_key[7]="AIzaSyC7GiTAn29FwUAyge28PXhQi4UnWu6mkbg"; // Google api key 01 Abr martinezgustavotelefonia@gmail.com
	
	$server_ip="10.66.12.193";
	$max_timeout_bot = 7200;
	$max_timeout_internal = 1800;
	$batch_type_code="006";

	$isBatch=0; //Debug on log file or
	$isDebug = 0; //Debug on browser, for both 1 means true 0 false

	require_once("dbCon.inc"); // DB

?>
