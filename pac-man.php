<?php
require_once(dirname(__FILE__)."/utils_pac.php");

//-SETTING-----------------------------
define('STAGE','stage2.txt');
define('TIME_LIMIT',50);
//-SETTING_END-------------------------

echo "------------------------PACMAN_START------------------------\n";

//フィールドを読み込み初期状態を表示。
$input_log = "";
list($fields, $user, $chars) = read_stage(STAGE);
$fields_fixed = set_char($fields, $user, $chars);
$fields_drawble = draw_fields($fields_fixed);
echo $fields_drawble;
echo "\n";

echo "TURN (1/".TIME_LIMIT .") START\n";
list($fields, $chars) = move_char_first($fields, $user, $chars);
list($fields, $user) = move_user($fields, $user);
$fields_fixed = set_char($fields, $user, $chars);
$fields_drawble = draw_fields($fields_fixed);
echo $fields_drawble;
echo "TURN(1/".TIME_LIMIT.") END\n\n";

/*-------------
ここにユーザーの入力を待ち、
敵キャラを動かす処理を入れる。
--------------*/
for($i = 2; $i <= TIME_LIMIT; $i++){
	echo "TURN ({$i}/".TIME_LIMIT .") START\n";
	list($fields, $chars) = move_char($fields, $user, $chars);
	list($fields, $user) = move_user($fields, $user);
	$dead = check_dead($user, $chars);
	if($dead){
		echo "--------------------GAME OVER------------------\n";
		break;
	}
 	$clear = check_clear($fields);
	if($clear){	
		echo "-------------------GAME CLEAR------------------\n";
		break;
	}

	$fields_fixed = set_char($fields, $user, $chars);
	$fields_drawble = draw_fields($fields_fixed);
	echo $fields_drawble;
	echo "TIME({$i}/".TIME_LIMIT.") END\n\n";
}

echo "INPUTED:{$input_log}\n";
echo "-------------------------PACMAN_END-------------------------\n";




