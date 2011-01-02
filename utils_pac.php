<?php

//------------------------------------------------------------------------------------------------------------- 
//SYSTEM------------------------------------------------------------------------------------------------------- 
//------------------------------------------------------------------------------------------------------------- 

//read file and create chars------------------
function read_stage($file_name){
	$x = 1;
	$y = 1;
	$fp = fopen($file_name, "r");
	while ($line = fgetc($fp)) {
		if($line == "\n"){
			$x++;
			$y = 1;
			continue;
		}
		
		if($line == "@"){
			$user = "{$line}:{$x}:{$y}:0:0";
			$line = " ";
		}else if($line != "#" && $line != "." && $line != " "){
			$chars[] = "{$line}:{$x}:{$y}:0:0"; 
			$line = " ";
		}

		$fields[$x][$y] = $line;
		$y++;
	}

	fclose($fp);
	return array($fields, $user, $chars);
}

//set character on field------------------------
function set_char($fields, $user, $chars){
	foreach($chars as $char){
		$char_st = preg_split("/:/",$char);
		$fields[$char_st[1]][$char_st[2]] = $char_st[0];
	}
	$char_st = preg_split("/:/",$user);
	$fields[$char_st[1]][$char_st[2]] = $char_st[0];

	return $fields;
}

//game_continue--------------------------------
function check_dead($user, $chars){
	$user_st = preg_split("/:/",$user);
	foreach($chars as $char){
		$char_st = preg_split("/:/",$char);
		$y_n = ($user_st[1] == $char_st[1]);
		$x_n = ($user_st[2] == $char_st[2]);
		$y_b_u = ($user_st[1] == $char_st[3]);
		$x_b_u = ($user_st[2] == $char_st[4]);
		$y_b_c = ($char_st[1] == $user_st[3]);
		$x_b_c = ($char_st[2] == $user_st[4]);

		if ($y_n && $x_n){
			return true;
		}
		if ($y_b_u && $x_b_u && $y_b_c && $x_b_c){
			return true;
		}
	}
	return false;
}

//check_clear----------------------------------
function check_clear($fields){
	foreach($fields as $field){
		if(in_array(".", $field)){
			return false;
		}  
	}
	return true;
}

//output fields---------------------------------
function draw_fields($fields){
	$fields_drawble = "";
	foreach($fields as $keys => $vals){
		foreach($vals as $key => $val){
			$fields_drawble .= $val;
		}
	$fields_drawble .= "\n";
	}
	return $fields_drawble;
}


//------------------------------------------------------------------------------------------------------------------------ 
//ABOUT USER-------------------------------------------------------------------------------------------------------------- 
//------------------------------------------------------------------------------------------------------------------------ 

//move_user--------------------------------------
function move_user($fields, $user){
	global $input_log;
	
	$user_st = preg_split("/:/",$user);
	echo "Please input h or j or k or l:";
	$input = fgets(STDIN,4096);
	$input = rtrim($input, "\n");

	$n_user_st = check_move_user($fields, $user_st, $input);

	if ($n_user_st){
		if ($fields[$user_st[1]][$user_st[2]] == "."){
			//.ゲット時の動作----------------------------
			echo ".get\n";
			$fields[$user_st[1]][$user_st[2]] = " ";
			//------------------------------------------
		}
		$user = "{$user_st[0]}:{$n_user_st[1]}:{$n_user_st[2]}:{$user_st[1]}:{$user_st[2]}";
		
		$input_log .= "$input";
		return array($fields, $user);
	}

	$user = "{$user_st[0]}:{$user_st[1]}:{$user_st[2]}:{$user_st[1]}:{$user_st[2]}";
	return array($fields, $user);
}

//check_move_user------------------------------------
function check_move_user($fields, $char_st, $input){
	if($input == "j"){
		$char_st[1]++;
	}else if($input == "k"){
		$char_st[1]--;
	}else if($input == "h"){
		$char_st[2]--;
	}else if($input == "l"){
		$char_st[2]++;
	}else{
		echo "'{$input}' was invalid\n";
		return false;
	}

	echo "'{$input}' was inputed.\n";

	if($fields[$char_st[1]][$char_st[2]] == "#"){
		echo "can't move\n";
		return false;
	}else{
		return $char_st;
	}
}

//---------------------------------------------------------------------------------------------------------------------- 
//ABOUT CHAR------------------------------------------------------------------------------------------------------------ 
//---------------------------------------------------------------------------------------------------------------------- 

//move_char_first---------------------------------------
function move_char_first($fields, $user, $chars){
	$user_st = preg_split("/:/",$user);
	$input = array("j","h","k","l");

	foreach($chars as $key => $val){
		$char_st = preg_split("/:/",$val);
		
		for($i = 0; $i <= 4; $i++){		
			$n_char_st = check_move_char($fields, $char_st, $input[$i]);
			if($n_char_st){
				$chars[$key] = "{$char_st[0]}:{$n_char_st[1]}:{$n_char_st[2]}:{$char_st[1]}:{$char_st[2]}";
				break;
			}
			$chars[$key] = "{$char_st[0]}:{$char_st[1]}:{$char_st[2]}:{$char_st[1]}:{$char_st[2]}";
		}
	}
	return array($fields, $chars);
}

//move_char---------------------------------------
function move_char($fields, $user, $chars){
	$user_st = preg_split("/:/",$user);

	foreach($chars as $key => $val){
		$char_st = preg_split("/:/",$val);
		
		//敵の状況を判断するメソッド
		list($situation, $inputs) = judge_situation($fields, $char_st);
		
		//状況毎の敵キャラの動き
		if((int)$situation === 1){
			//行き止まりマスの場合の動き
			$n_char_st = check_move_char($fields, $char_st, $inputs[0]);
		}else if((int)$situation === 2){
			$n_char_st = char_line_way($char_st, $inputs);
		}else{
			//交差点マスの場合は敵キャラの種類による動き
			if($char_st[0] == "V"){
				$n_char_st = v_carrefour($fields, $user_st, $char_st, $inputs);
			}
			if($char_st[0] == "H"){
				$n_char_st = h_carrefour($fields, $user_st, $char_st, $inputs);
			}
			if($char_st[0] == "L"){
				$n_char_st = l_carrefour($fields, $char_st);
			}
			if($char_st[0] == "R"){
				$n_char_st = r_carrefour($fields, $char_st);
			}
			if($char_st[0] == "J"){
				$n_char_st = l_carrefour($fields, $char_st);
			}
			if($char_st[0] == "j"){
				$n_char_st = r_carrefour($fields, $char_st);
			}
		}

		$chars[$key] = "{$n_char_st[0]}:{$n_char_st[1]}:{$n_char_st[2]}:{$char_st[1]}:{$char_st[2]}";
	}
	return array($fields, $chars);
}

//敵Vの動き--------------------------------------------------
function v_carrefour($fields, $user_st, $char_st, $inputs){ 
	$dy = $user_st[1] - $char_st[1];
	$dx = $user_st[2] - $char_st[2];

	if(gmp_sign($dy) < 0){
		$n_char_st = check_move_char($fields, $char_st, "k");
		if($n_char_st){
			return $n_char_st;
		}
	}

	if(gmp_sign($dy) > 0){
		$n_char_st = check_move_char($fields, $char_st, "j");
		if($n_char_st){
			return $n_char_st;
		}
	}

	if(gmp_sign($dx) < 0){
		$n_char_st = check_move_char($fields, $char_st, "h");
		if($n_char_st){
			return $n_char_st;
		}
	}

	if(gmp_sign($dx) > 0){
		$n_char_st = check_move_char($fields, $char_st, "l");
		if($n_char_st){
			return $n_char_st;
		}
	}

	foreach($inputs as $input){
		$n_char_st = check_move_char($fields, $char_st, $input);
		if($n_char_st){
			return $n_char_st;
		}
	}
	return $char_st;
}

//敵Hの動き--------------------------------------------------
function h_carrefour($fields, $user_st, $char_st, $inputs){ 
	$dy = $user_st[1] - $char_st[1];
	$dx = $user_st[2] - $char_st[2];

	if(gmp_sign($dx) < 0){
		$n_char_st = check_move_char($fields, $char_st, "h");
		if($n_char_st){
			return $n_char_st;
		}
	}

	if(gmp_sign($dx) > 0){
		$n_char_st = check_move_char($fields, $char_st, "l");
		if($n_char_st){
			return $n_char_st;
		}
	}

	if(gmp_sign($dy) < 0){
		$n_char_st = check_move_char($fields, $char_st, "k");
		if($n_char_st){
			return $n_char_st;
		}
	}

	if(gmp_sign($dy) > 0){
		$n_char_st = check_move_char($fields, $char_st, "j");
		if($n_char_st){
			return $n_char_st;
		}
	}

	foreach($inputs as $input){
		$n_char_st = check_move_char($fields, $char_st, $input);
		if($n_char_st){
			return $n_char_st;
		}
	}
	return $char_st;
}

//敵Lの動き--------------------------------------------------
function l_carrefour($fields, $char_st){ 
	$direction_y = $char_st[1] - $char_st[3]; 
	$direction_x = $char_st[2] - $char_st[4];
	
	if($direction_y > 0){
		$inputs = array('l','j','h');
	}
	if($direction_y < 0){
		$inputs = array('h','k','l');
	}
	if($direction_x > 0){
		$inputs = array('k','l','j');
	}
	if($direction_x < 0){
		$inputs = array('j','h','k');
	}

	if($char_st[0] == "J"){
		$char_st[0] = "j";
	}

	foreach($inputs as $input){
		$n_char_st = check_move_char($fields, $char_st, $input);
		if($n_char_st){
			return $n_char_st;
		}
	}
	return $char_st;
}

//敵Rの動き--------------------------------------------------
function r_carrefour($fields, $char_st){ 
	$direction_y = $char_st[1] - $char_st[3]; 
	$direction_x = $char_st[2] - $char_st[4];
	
	if($direction_y > 0){
		$inputs = array('h','j','l');
	}
	if($direction_y < 0){
		$inputs = array('l','k','h');
	}
	if($direction_x > 0){
		$inputs = array('j','l','k');
	}
	if($direction_x < 0){
		$inputs = array('k','h','j');
	}

	if($char_st[0] == "j"){
		$char_st[0] = "J";
	}

	foreach($inputs as $input){
		$n_char_st = check_move_char($fields, $char_st, $input);
		if($n_char_st){
			return $n_char_st;
		}
	}
	return $char_st;
}

//check_move_char------------------------------------
function check_move_char($fields, $char_st, $input){
	if($input == "j"){
		$char_st[1]++;
	}else if($input == "k"){
		$char_st[1]--;
	}else if($input == "h"){
		$char_st[2]--;
	}else if($input == "l"){
		$char_st[2]++;
	}else{
		return false;
	}

	if($fields[$char_st[1]][$char_st[2]] == "#"){
		return false;
	}else{
		return $char_st;
	}
}

//judge_situation-------------------------------------
function judge_situation($fields, $char_st){
	$counts = 0;
	$inputs = array();

	$x = $char_st[2];
	$y = $char_st[1];

	$up = $y - 1;
	$down = $y + 1;
	$left = $x - 1;
	$right = $x + 1;

	$positions = array(
		'j' => $fields[$down][$x], 
		'h' => $fields[$y][$left],
		'k' => $fields[$up][$x],
		'l' => $fields[$y][$right]
		);

	foreach($positions as $key => $position){
		if($position != "#"){
			$counts++;
			$inputs[] = $key;
		}
	}

	return array($counts, $inputs);	
}

//通路マス時------------------------------------
function char_line_way($char_st, $inputs){
	foreach($inputs as $input){
		if($input == "j"){
			$char_new[1] =  $char_st[1] + 1;
			$char_new[2] =  $char_st[2];
		}else if($input == "k"){
			$char_new[1] =  $char_st[1] - 1;
			$char_new[2] =  $char_st[2];
		}else if($input == "h"){
			$char_new[1] =  $char_st[1];
			$char_new[2] =  $char_st[2] - 1;
		}else if($input == "l"){
			$char_new[1] =  $char_st[1];
			$char_new[2] =  $char_st[2] + 1;
		}else{
			return false;
		}
		
		if ((int)$char_new[1] !== (int)$char_st[3] || (int)$char_new[2] !== (int)$char_st[4]){
			$char_st[1] = $char_new[1];	
			$char_st[2] = $char_new[2];	
			return $char_st;
		}
	}
	echo "something WRONG\n";
	return $char_st;

}












