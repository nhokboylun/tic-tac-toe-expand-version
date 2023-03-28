<!-- 
CSC4370: Project 2
Name: Nhon Phu La (900889407)
-->
<?php
// Starting page.
session_start();
if (!isset($_SESSION['UserData']['Username'])) {
	header("location:login.php");
	exit;
}
// logged in
const Human = -1;
const AI = 1;
const Blank = 0;
// get user rank;
$Username = $_SESSION['UserData']['Username'];
$Password = $_SESSION['UserData']['Password'];
$Score = (int) $_SESSION['UserData']['Score'];
$lines = file("./files/database.txt");
$rank = 0;
foreach ($lines as $line) {
	$rank++;
	if (explode(",", $line)[0] == $Username)
		break;
}
$_SESSION['UserData']['Rank']=$rank;
// check database of game	
if (isset($_SESSION['data']))
	$data = $_SESSION['data'];
else {
	// create blank data
	for ($i = 0; $i <= 9; $i++)
		for ($j = 0; $j <= 9; $j++)
			$data[$i][$j] = 0;
	$_SESSION['data'] = $data;	
}
// check banner _POST first
if(isset($_POST["banner"])){
	switch ($_POST["banner"]){
		case "Try_again": 
			unset($_SESSION['data']);
			unset($_SESSION['previous_chose']);	
			unset($_SESSION['winner']);
			unset($_SESSION['result_win']);
			header("location:caro.php");
			break;
		case "Logout": 
			session_destroy();
			header("location:login.php");
			break;
		default: break;
	}
}
// check table moving input second
if (isset($_POST["choose"])){
	// got button clicked
	$grid_name = $_POST["choose"];
	// check if user refresh F5 or re-submit same location
	if (isset($_SESSION["previous_chose"]) && $_SESSION["previous_chose"] == $grid_name)
		print_continue_game($data, -1, -1);
	else {
		$x = (int)$grid_name[4];
		$y = (int)$grid_name[5];
		// check if data input correct
		if ($data[$x][$y] == Blank)
			$data[$x][$y] = Human;
		else {
			// Player cheating
			print_continue_game($data, -1, -1);
			exit;
		}
		$_SESSION["previous_chose"] = $grid_name;
		$_SESSION["data"] = $data;
		// Check Human move is win
		$result_win=checkwin($data, $x, $y, Human);
		if ( $result_win[0][0]==5) {
			// Human Win
			$list_user[0][0]=$Username;
			$list_user[0][1]=$Password;
			$list_user[0][2]=$Score;
			//Export all user data to update rank
			foreach ($lines as $line) {
				$tmp_user = explode(",", $line);
				$list_user[sizeof($list_user)][0] = $tmp_user[0];
				$list_user[sizeof($list_user) - 1][1] = $tmp_user[1];
				$list_user[sizeof($list_user) - 1][2] = (int) $tmp_user[2];
				if ($tmp_user[0] == $Username){
					$list_user[sizeof($list_user) - 1][2]++;
					$_SESSION['UserData']['Score']=$list_user[sizeof($list_user) - 1][2];
				}
			}
			// sorting high score first
			for ($i = 0; $i < sizeof($list_user) - 1; $i++)
				for ($k = 0; $k < sizeof($list_user); $k++)
					if ($list_user[$i][2] < $list_user[$k][2]) {
						//swap
						$tmp_user[0] = $list_user[$i][0];
						$tmp_user[1] = $list_user[$i][1];
						$tmp_user[2] = $list_user[$i][2];
						$list_user[$i][0] = $list_user[$k][0];
						$list_user[$i][1] = $list_user[$k][1];
						$list_user[$i][2] = $list_user[$k][2];
						$list_user[$k][0] = $tmp_user[0];
						$list_user[$k][1] = $tmp_user[1];
						$list_user[$k][2] = $tmp_user[2];
					}

			$newdata = "";
			for ($i = 0; $i < sizeof($list_user) - 1; $i++)
				$newdata = $newdata . $list_user[$i][0] . "," . $list_user[$i][1] . "," . $list_user[$i][2] . "\n";
			file_put_contents("./files/database.txt", $newdata);
			$_SESSION["winner"]=Human;
			$_SESSION["result_win"]=$result_win;
			print_winner_page($data, Human,$result_win);
		} else {
			// Human Not win, AI move
			$AI_x = -1;
			$AI_y = -1;
			AI_move_next($data, $AI_x, $AI_y);
			//print_r(array("x="=>$AI_x,"y="=>$AI_y));
			$result_win= checkwin($data, $AI_x, $AI_y, AI);
			print_r($result_win);
			if ( $result_win[0][0]==5){
				$_SESSION["winner"]=AI;
				$_SESSION["result_win"]=$result_win;
				print_winner_page($data, AI, $result_win);				
			}
			else
				print_continue_game($data, $AI_x, $AI_y);
		}
	}
	return;
}
if(isset($_SESSION["winner"]) && isset($_SESSION["result_win"])){
	print_winner_page($data,$_SESSION["winner"],$_SESSION["result_win"]);			
}
else{	
	print_continue_game($data, -1, -1);
}
/****************************** Section for Function Render HTML Start ******************************************/
function print_winner_page($data, $winner, $result_win)
{
	echo "<p> winer is " . (($winner == AI) ? "AI" : "Human") . "</p>";
	unset($_SESSION['data']);
	unset($_SESSION['previous_chose']);
	echo "<a href='./caro.php'>Try new game</a>";
	print_top_html();
	echo
	"
    <div id='grid-container'>";
	// shows the game Tables Base one Data
	for ($i = 0; $i <= 9; $i++) {
		for ($j = 0; $j <= 9; $j++) {
			$grid_name = "slot" . $i . $j;
			$color = $data[$i][$j];
			if($winner==AI){
				if ($color == 0) {
					echo
					"<div style='grid-area:" . $grid_name . ";' class='slot'></div>";
				} else if ($color == 1) {
					// AI mark
					$printed=false;
					for($k=1;$k<=5;$k++)
						if ($i == $result_win[$k][0] && $j == $result_win[$k][1]) {
							echo "<div style='grid-area:" . $grid_name . ";background-color: yellow;' class='slotX'></div>";
							$printed=true;
							break;							
					}
					if($printed==false)
						echo "<div style='grid-area:" . $grid_name . ";' class='slotX'></div>";				
				} else {
					// Human Mark;				
					echo "<div style='grid-area:" . $grid_name . ";' class='slotO'></div>";
				}
			}
			else if($winner==Human) {
				if ($color == 0) {
					echo
					"<div style='grid-area:" . $grid_name . ";' class='slot'></div>";
				} else if ($color == 1) {
				// AI mark
					echo "<div style='grid-area:" . $grid_name . ";' class='slotX'></div>";
				}
		 		else {
				// Human Mark;
					$printed=false;
					for($k=1;$k<=5;$k++)
						if ($i == $result_win[$k][0] && $j == $result_win[$k][1]) {
							echo "<div style='grid-area:" . $grid_name . ";background-color: yellow;' class='slotO'></div>";
							$printed=true;
							break;							
					}
					if($printed==false)
						echo "<div style='grid-area:" . $grid_name . ";' class='slotO'></div>";
				}					
			
			}else{
				if ($color == 0) {
				echo
				"<div style='grid-area:" . $grid_name . ";' class='slot'></div>";
				} else if ($color == 1) {
				// AI mark				
					echo "<div style='grid-area:" . $grid_name . ";' class='slotX'></div>";
				
				} else {
				// Human Mark;				
					echo "<div style='grid-area:" . $grid_name . ";' class='slotO'></div>";
				}
			}
		}
	}
	echo "</div>";
	print_bottom_html();
}

function print_continue_game($data, $AI_x, $AI_y)
{
	//echo $AI_x.",".$AI_y;
	print_top_html();
	echo
	"
    <div id='grid-container'>";

	// shows the game Tables Base one Data
	for ($i = 0; $i <= 9; $i++) {
		for ($j = 0; $j <= 9; $j++) {
			$grid_name = "slot" . $i . $j;
			$color = $data[$i][$j];
			if ($color == 0) {
				echo
				"<div style='grid-area:" . $grid_name . ";' class='slot'>" .
					"<input type='submit' name='choose' class='button' value=" . $grid_name . "></div>";
			} else if ($color == 1) {
				// AI mark
				if ($i == $AI_x && $j == $AI_y) {
					echo
					"<div style='grid-area:" . $grid_name . ";background-color: yellow;' class='slotX'></div>";
				} else {
					echo
					"<div style='grid-area:" . $grid_name . ";' class='slotX'></div>";
				}
			} else {
				// Human Mark;
				echo
				"<div style='grid-area:" . $grid_name . ";' class='slotO'></div>";
			}
		}
	}
	echo "</div>";
	print_bottom_html();
}

function print_top_html()
{
	// Print the head body HTML
	echo
	"<!DOCTYPE html>
<html lang='en'>

<head>
    <title> Caro Game</title> 
    <meta charset='UTF-8'>
    <link rel='stylesheet' type='text/css' href='./css/caro.css'>
</head>

<body>
	<div id='main'>
	<form action='caro.php' method='post'>	
    <div id='banner'>
	<span class='player_span'>Player Name:<br>".$_SESSION['UserData']["Username"]
		."<br>Total Win: ".$_SESSION['UserData']['Score']
		."<br>Rank: ".$_SESSION['UserData']['Rank']."</span>
	<span class='title_game'>";
	if(isset($_SESSION["winner"]))
	{
		echo "Winner is <span style='color:yellow;'>".($_SESSION["winner"]==1?"AI":"Human")."</span><br>";
		echo "<label class='Tryagian_label' for='try_again_button'>Click for New Game</label>
			<input type='submit' name='banner' class='buttonHidden' value='Try_again' id='try_again_button'>";
	}
	else
		echo " Welcome To Caro Game";
	echo "</span>
	<label class='logout_label' for='logout_button'>Log out<br><br><br></label>
	<input type='submit' name='banner' class='buttonHidden' value='Logout' id='logout_button'>	
	</div>";
}

function print_bottom_html()
{
	// Print the clode body HTML
	echo
	"</form></div></body>";
}
/****************************** Section for Function Render HTML END ******************************************/
/****************************** Section for  Gaming Function Start ********************************************/
function checkwin($data,$point_x,$point_y,$target){
	$result[0][0]=1;
	$result[0][1]=-1;
	$result[1][0]=$point_x;
	$result[1][1]=$point_y;
	$result[2][0]=-1;
	$result[2][1]=-1;
	$result[3][0]=-1;
	$result[3][1]=-1;
	$result[4][0]=-1;
	$result[4][1]=-1;
	$result[5][0]=-1;
	$result[5][1]=-1;
	// check top to bottom
	for ($i = $point_x+1; $i <= 9; $i++) {
		if($data[$i][$point_y]==$target){
			$result[0][0]++;
			$result[$result[0][0]][0]=$i;
			$result[$result[0][0]][1]=$point_y;
			if($result[0][0]==5) return $result;
		}else{
			break;
		}
	}
	for ($i = $point_x-1; $i >= 0; $i--) {
		if($data[$i][$point_y]==$target){
			$result[0][0]++;
			$result[$result[0][0]][0]=$i;
			$result[$result[0][0]][1]=$point_y;
			if($result[0][0]==5) return $result;
		}else{
			break;
		}
	}
	if($result[0][0]!=5) $result[0][0]=1;
	// check left to right
	for ($i = $point_y+1; $i <= 9; $i++) {
		if($data[$point_x][$i]==$target){
			$result[0][0]++;
			$result[$result[0][0]][0]=$point_x;
			$result[$result[0][0]][1]=$i;
			if($result[0][0]==5) return $result;
		}else{
			break;
		}
	}
	for ($i = $point_y-1; $i >= 0; $i--) {
		if($data[$point_x][$i]==$target){
			$result[0][0]++;
			$result[$result[0][0]][0]=$point_x;
			$result[$result[0][0]][1]=$i;
			if($result[0][0]==5) return $result;
		}else{
			break;
		}
	}
	if($result[0][0]!=5) $result[0][0]=1;
	// check top-left to bottom-right
	for ($i = 1; $i <= 9; $i++) {
		if($point_x+$i <=9 && $point_y+$i <=9)
			if($data[$point_x+$i][$point_y+$i]==$target){
				$result[0][0]++;
				$result[$result[0][0]][0]=$point_x+$i;
				$result[$result[0][0]][1]=$point_y+$i;
				if($result[0][0]==5) return $result;
			}else{
				break;
			}
		else
			break;
	}
	
	for ($i = 1; $i <= 9; $i++) {
		if($point_x-$i >=0 && $point_y-$i >=0)
			if($data[$point_x-$i][$point_y-$i]==$target){
				$result[0][0]++;
				$result[$result[0][0]][0]=$point_x-$i;
				$result[$result[0][0]][1]=$point_y-$i;
				if($result[0][0]==5) return $result;
			}else{
				break;
			}
		else
			break;
		}
	if($result[0][0]!=5) $result[0][0]=1;
	// check top-right to bottom-left	
	for ($i = 1; $i <= 9; $i++) {
		if($point_x+$i <=9 && $point_y-$i >=0)
			if($data[$point_x+$i][$point_y-$i]==$target){
				$result[0][0]++;
				$result[$result[0][0]][0]=$point_x+$i;
				$result[$result[0][0]][1]=$point_y-$i;
				if($result[0][0]==5) return $result;
			}else{
				break;
			}
		else
			break;
	}
	
	for ($i = 1; $i <= 9; $i++) {
		if($point_x-$i >=0 && $point_y+$i <=9)
			if($data[$point_x-$i][$point_y+$i]==$target){
				$result[0][0]++;
				$result[$result[0][0]][0]=$point_x-$i;
				$result[$result[0][0]][1]=$point_y+$i;
				if($result[0][0]==5) return $result;
			}else{
				break;
			}
		else
			break;
		}
	return $result;
}

function countScore($data, $point_x, $point_y, $vitual)
{
	$max_point = 0;
	// check top to bottom
	$countO = 0;
	$countX = 0;
	for ($i = 0; $i <= 9; $i++) {
		if ($vitual != Blank) {
			if ($vitual == Human) {
				if ($max_point < $countO)
					$max_point = $countO;
			} else {
				if ($max_point < $countX)
					$max_point = $countX;
			}
			if ($i == $point_x) {
				if ($vitual == Human) {
					$countX = 0;
					$countO = $countO + 1;
				} else {
					$countX = $countX + 1;
					$countO = 0;
				}
				continue;
			}
		}
		if ($data[$i][$point_y] == Human) {
			$countX = 0;
			$countO = $countO + 1;
		} else if ($data[$i][$point_y] == AI) {
			$countX = $countX + 1;
			$countO = 0;
		} else {
			$countO = 0;
			$countX = 0;
		}
	}
	if ($vitual == Human) {
		if ($max_point < $countO)
			$max_point = $countO;
	} else {
		if ($max_point < $countX)
			$max_point = $countX;
	}
	// check left to right
	$countO = 0;
	$countX = 0;
	for ($i = 0; $i <= 9; $i++) {
		if ($vitual != Blank) {
			if ($vitual == Human) {
				if ($max_point < $countO)
					$max_point = $countO;
			} else {
				if ($max_point < $countX)
					$max_point = $countX;
			}
			if ($i == $point_y) {
				if ($vitual == Human) {
					$countX = 0;
					$countO = $countO + 1;
				} else {
					$countX = $countX + 1;
					$countO = 0;
				}
				continue;
			}
		}
		if ($data[$point_x][$i] == Human) {
			$countX = 0;
			$countO = $countO + 1;
		} else if ($data[$point_x][$i] == AI) {
			$countX = $countX + 1;
			$countO = 0;
		} else {
			$countO = 0;
			$countX = 0;
		}
	}
	if ($vitual == Human) {
		if ($max_point < $countO)
			$max_point = $countO;
	} else {
		if ($max_point < $countX)
			$max_point = $countX;
	}

	// check top-left to bottom-right
	$countO = 0;
	$countX = 0;
	$reducer = ($point_x >= $point_y) ? $point_y : $point_x;
	for ($i = 0; $i <= 9; $i++) {
		if ($vitual == Human) {
			if ($max_point < $countO)
				$max_point = $countO;
		} else {
			if ($max_point < $countX)
				$max_point = $countX;
		}
		$tmp_x = $point_x - $reducer + $i;
		$tmp_y = $point_y - $reducer + $i;
		if ($tmp_x > 9 || $tmp_y > 9) break;
		if ($vitual != Blank) {
			if ($tmp_x == $point_x && $tmp_y == $point_y) {
				if ($vitual == Human) {
					$countX = 0;
					$countO = $countO + 1;
				} else {
					$countX = $countX + 1;
					$countO = 0;
				}
				continue;
			}
		}
		if ($data[$tmp_x][$tmp_y] == Human) {
			$countX = 0;
			$countO = $countO + 1;
		} else if ($data[$tmp_x][$tmp_y] == AI) {
			$countX = $countX + 1;
			$countO = 0;
		} else {
			$countO = 0;
			$countX = 0;
		}
	}

	if ($vitual == Human) {
		if ($max_point < $countO)
			$max_point = $countO;
	} else {
		if ($max_point < $countX)
			$max_point = $countX;
	}

	// check top-right to bottom-left
	$countO = 0;
	$countX = 0;
	if ($point_y + $point_x <= 9)
		$reducer = $point_x;
	else
		$reducer = 9 - $point_y;
	for ($i = 0; $i <= 9; $i++) {
		if ($vitual == Human) {
			if ($max_point < $countO)
				$max_point = $countO;
		} else {
			if ($max_point < $countX)
				$max_point = $countX;
		}
		$tmp_x = $point_x - $reducer + $i;
		$tmp_y = $point_y + $reducer - $i;
		if ($tmp_x > 9 || $tmp_y < 0) break;
		if ($vitual != Blank) {
			if ($tmp_x == $point_x && $tmp_y == $point_y) {
				if ($vitual == Human) {
					$countX = 0;
					$countO = $countO + 1;
				} else {
					$countX = $countX + 1;
					$countO = 0;
				}
				continue;
			}
		}
		if ($data[$tmp_x][$tmp_y] == Human) {
			$countX = 0;
			$countO = $countO + 1;
		} else if ($data[$tmp_x][$tmp_y] == AI) {
			$countX = $countX + 1;
			$countO = 0;
		} else {

			$countO = 0;
			$countX = 0;
		}
	}
	if ($vitual == Human) {
		if ($max_point < $countO)
			$max_point = $countO;
	} else {
		if ($max_point < $countX)
			$max_point = $countX;
	}
	return $max_point;
}
function AI_move_next(&$data, &$AI_x, &$AI_y)
{

	//hard part	
	// target locking user move
	// defend point	
	$list_defend_point = getList($data, Human);
	$list_attack_point = getList($data, AI);
	//print_r($list_attack_point);
	//print_r(array("slipt" => "true"));
	//print_r($list_defend_point);
	//check win point
	for ($i = 1; $i < sizeof($list_attack_point); $i++) {
		$result_win = checkwin($data, $list_attack_point[$i][1], $list_attack_point[$i][2], AI);
		if ($result_win[0][0]==5) {
			$AI_x = $list_attack_point[$i][1];
			$AI_y = $list_attack_point[$i][2];
			$data[$AI_x][$AI_y] = AI;
			$_SESSION['data'] = $data;
			//print_r(array("attack1" => "true"));
			return;
		}
	}

	for ($i = 1; $i < sizeof($list_defend_point); $i++) {
		$result_win =checkwin($data, $list_defend_point[$i][1], $list_defend_point[$i][2], Human);
		if ($result_win[0][0]==5) {
			$AI_x = $list_defend_point[$i][1];
			$AI_y = $list_defend_point[$i][2];
			$data[$AI_x][$AI_y] = AI;
			$_SESSION['data'] = $data;
			//print_r(array("defend1" => "true"));
			return;
		}
	}

	// defend
	$mDefend = $list_defend_point[1][0];
	$maxDefendScore = 0;
	$DefendChose = 1;
	for ($i = 1; $i < sizeof($list_defend_point); $i++) {
		if ($list_defend_point[$i][0] == $mDefend) {
			$s = countScore($data, $list_defend_point[$i][1], $list_defend_point[$i][2], Human);
			if ($maxDefendScore < $s) {
				$maxDefendScore = $s;
				$DefendChose = $i;
			}
		} else break;
	}
	//attack
	$mAttack = (sizeof($list_attack_point) >= 2) ? $list_attack_point[1][0] : 0;
	$maxAttackScore = 0;
	$AttackChose = 1;
	for ($i = 1; $i < sizeof($list_attack_point); $i++) {
		if ($list_attack_point[$i][0] == $mAttack) {
			$s = countScore($data, $list_attack_point[$i][1], $list_attack_point[$i][2], AI);
			if ($maxAttackScore < $s) {
				$maxAttackScore = $s;
				$AttackChose = $i;
			}
		} else break;
	}
	if ($maxAttackScore >= $maxDefendScore) {
		$AI_x = $list_attack_point[$AttackChose][1];
		$AI_y = $list_attack_point[$AttackChose][2];
		//print_r(array("attack2" => "true"));
	} else {
		$AI_x = $list_defend_point[$DefendChose][1];
		$AI_y = $list_defend_point[$DefendChose][2];
		//print_r(array("defend2" => "true"));
	}
	// check who can win


	$data[$AI_x][$AI_y] = AI;
	$_SESSION['data'] = $data;
	return;
}
function getList($data, $target)
{
	//initail
	for ($k = 0; $k <= 4; $k++)
		for ($i = 0; $i <= 9; $i++)
			for ($j = 0; $j <= 9; $j++)
				$target_layer[$k][$i][$j] = 0;
	// check left to right	
	$max_target_layer[0] = 0;
	for ($i = 0; $i <= 9; $i++) {
		$countPrevent = 0;
		$countTarget = 0;
		$holder[0] = -1;
		$holder[1] = -1;
		$holder[2] = -1;
		for ($j = 0; $j <= 9; $j++) {
			if ($data[$i][$j] == $target) {
				$countTarget = $countTarget + 1;
				if ($j - 1 >= 0)
					if ($data[$i][$j - 1] == Blank)
						$holder[0] = $j - 1;
			} else if ($data[$i][$j] != Blank) {
				if ($countTarget != 0) {
					if($countTarget>=4)
						$target_layer[0][$i][$holder[0]] = 4;
					else
						$target_layer[0][$i][$holder[0]] = $countTarget - 1;
				}
				$countPrevent = 1;
				$countTarget = 0;
				$holder[0] = -1;
				$holder[1] = -1;
				$holder[2] = -1;
				continue;
			} else {
				if ($countTarget == 0) {
					$holder[0] = -1;
					$holder[1] = -1;
					$holder[2] = -1;
					$countPrevent = 0;
					continue;
				}
				$holder[1] = $j;
				$k = $j + 1;
				$in_seri = 0;
				while ($k <= 9) {
					if ($data[$i][$k] == $target) {
						$countTarget = $countTarget + 1;
						$k++;
						$in_seri++;
					} else if ($data[$i][$k] == Blank) {
						if ($in_seri != 0)
							$holder[2] = $k;
						break;
					} else {
						$countPrevent = $countPrevent + 1;
						break;
					}
				}
				if ($k > 9) {
					$j = 9;
				} else {
					$j = $k;
				}
				if ($countPrevent == 2 && $countTarget < 4) {					
						$target_layer[0][$i][$holder[1]] = 0;					
				} else {
					for ($m = 0; $m <= 2; $m++)
						if ($holder[$m] != -1)
							if($countTarget >= 4)
							$target_layer[0][$i][$holder[$m]] = 4;
					else
						$target_layer[0][$i][$holder[$m]] = $countTarget - $countPrevent;
				}
				if ($max_target_layer[0] < $target_layer[0][$i][$holder[1]]) {
					$max_target_layer[0] = $target_layer[0][$i][$holder[1]];
				}
				//clean up 
				$countPrevent = 0;
				$countTarget = 0;
				$holder[0] = -1;
				$holder[1] = -1;
				$holder[2] = -1;
			}
		}
	}

	// check top to bottom	
	$max_target_layer[1] = 0;
	for ($j = 0; $j <= 9; $j++) {
		$countPrevent = 0;
		$countTarget = 0;
		$holder[0] = -1;
		$holder[1] = -1;
		$holder[2] = -1;
		for ($i = 0; $i <= 9; $i++) {
			if ($data[$i][$j] == $target) {
				$countTarget = $countTarget + 1;
				if ($i - 1 >= 0)
					if ($data[$i - 1][$j] == Blank)
						$holder[0] = $i - 1;
			} else if ($data[$i][$j] != Blank) {
				if ($countTarget != 0) {
					if($countTarget>=4)
						$target_layer[1][$holder[0]][$j] = 4;
					else 
						$target_layer[1][$holder[0]][$j] = $countTarget - 1;
				}
				$countPrevent = 1;
				$countTarget = 0;
				$holder[0] = -1;
				$holder[1] = -1;
				$holder[2] = -1;
				continue;
			} else {
				if ($countTarget == 0) {
					$holder[0] = -1;
					$holder[1] = -1;
					$holder[2] = -1;
					$countPrevent = 0;
					continue;
				}
				$holder[1] = $i;
				$k = $i + 1;
				$in_seri = 0;
				while ($k <= 9) {
					if ($data[$k][$j] == $target) {
						$countTarget = $countTarget + 1;
						$k++;
						$in_seri++;
					} else if ($data[$k][$j] == Blank) {
						if ($in_seri != 0)
							$holder[2] = $k;
						break;
					} else {
						$countPrevent = $countPrevent + 1;
						break;
					}
				}
				if ($k > 9) {
					$i = 9;
				} else {
					$i = $k;
				}
				if ($countPrevent == 2 && $countTarget < 4) {					
						$target_layer[1][$holder[1]][$j] = 0;					
				} else {
					for ($m = 0; $m <= 2; $m++)
						if ($holder[$m] != -1)
							if($countTarget >= 4)
							$target_layer[1][$holder[$m]][$j] = 4;
					else
						$target_layer[1][$holder[$m]][$j] = $countTarget - $countPrevent;
				}
				if ($max_target_layer[1] < $target_layer[1][$holder[1]][$j]) {
					$max_target_layer[1] = $target_layer[1][$holder[1]][$j];
				}
				// clean up
				$countPrevent = 0;
				$countTarget = 0;
				$holder[0] = -1;
				$holder[1] = -1;
				$holder[2] = -1;
			}
		}
	}

	// check top-left to bottom-right
	$max_target_layer[2] = 0;
	$turn = 0;
	for ($point = 0; $point <= 9; $point++) {
		if ($turn == 0) {
			// half right
			$startX = 0;
			$startY = $point;
		} else {
			// half left
			$startX = $point;
			$startY = 0;
		}
		$countPrevent = 0;
		$countTarget = 0;
		$holderA[0][0] = -1;
		$holderA[0][1] = -1;
		$holderA[1][0] = -1;
		$holderA[1][1] = -1;
		$holderA[2][0] = -1;
		$holderA[2][1] = -1;
		for ($jump = 0; $jump <= 9; $jump++) {
			$i = $startX + $jump;
			$j = $startY + $jump;
			if ($i > 9 || $j > 9) break;
			if ($data[$i][$j] == $target) {
				$countTarget = $countTarget + 1;
				if ($i - 1 >= 0 && $j - 1 >= 0)
					if ($data[$i - 1][$j - 1] == Blank) {
						$holderA[0][0] = $i - 1;
						$holderA[0][1] = $j - 1;
					}
			} else if ($data[$i][$j] != Blank) {
				if ($countTarget != 0) {
					if($countTarget>=4)
						$target_layer[2][$holderA[0][0]][$holderA[0][1]] = 4;
					else
						$target_layer[2][$holderA[0][0]][$holderA[0][1]] = $countTarget - 1;
				}
				$countPrevent = 1;
				$countTarget = 0;
				$holderA[0][0] = -1;
				$holderA[0][1] = -1;
				$holderA[1][0] = -1;
				$holderA[1][1] = -1;
				$holderA[2][0] = -1;
				$holderA[2][1] = -1;
				continue;
				$countPrevent = 1;
			} else {
				if ($countTarget == 0) {
					$holderA[0][0] = -1;
					$holderA[0][1] = -1;
					$holderA[1][0] = -1;
					$holderA[1][1] = -1;
					$holderA[2][0] = -1;
					$holderA[2][1] = -1;
					$countPrevent = 0;
					continue;
				}
				$holderA[1][0] = $i;
				$holderA[1][1] = $j;
				$k1 = $i + 1;
				$k2 = $j + 1;
				$in_seri = 0;
				while ($k1 <= 9 && $k2 <= 9) {
					if ($data[$k1][$k2] == $target) {
						$countTarget = $countTarget + 1;
						$k1++;
						$k2++;
						$in_seri++;
					} else if ($data[$k1][$k2] == Blank) {
						if ($in_seri != 0) {
							$holderA[2][0] = $k1;
							$holderA[2][1] = $k2;
						}
						break;
					} else {
						$countPrevent = $countPrevent + 1;
						break;
					}
				}
				if ($k1 > 9) {
					$jump = 9 - $startX;
				} else {
					$jump = $k1 - $startX;
				}
				
				if ($countPrevent == 2 && $countTarget < 4) {					
						$target_layer[2][$holderA[1][0]][$holderA[1][1]] = 0;
				} else {
					for ($m = 0; $m <= 2; $m++)
						if ($holderA[$m][0] != -1)
							if ($countTarget >= 4)
								$target_layer[2][$holderA[$m][0]][$holderA[$m][1]] = 4;
							else
								$target_layer[2][$holderA[$m][0]][$holderA[$m][1]] = $countTarget - $countPrevent;
				}
				if ($max_target_layer[2] < $target_layer[2][$holderA[1][0]][$holderA[1][1]]) {
					$max_target_layer[2] = $target_layer[2][$holderA[1][0]][$holderA[1][1]];
				}
				// clean up
				$countPrevent = 0;
				$countTarget = 0;
				$holderA[0][0] = -1;
				$holderA[0][1] = -1;
				$holderA[1][0] = -1;
				$holderA[1][1] = -1;
				$holderA[2][0] = -1;
				$holderA[2][1] = -1;
			}
		}
		if ($point == 9 && $turn == 0) {
			$point = 0;
			$turn = 1;
		}
	}
	// check top-right to bottom-left
	$max_target_layer[3] = 0;
	$turn = 0;
	for ($point = 9; $point >= 0; $point--) {
		if ($turn == 0) {
			// half right
			$startX = 0;
			$startY = $point;
		} else {
			// half left
			$startX = $point;
			$startY = 9;
		}
		$countPrevent = 0;
		$countTarget = 0;
		$holderA[0][0] = -1;
		$holderA[0][1] = -1;
		$holderA[1][0] = -1;
		$holderA[1][1] = -1;
		$holderA[2][0] = -1;
		$holderA[2][1] = -1;
		for ($jump = 0; $jump <= 9; $jump++) {
			$i = $startX + $jump;
			$j = $startY - $jump;
			if ($i > 9 || $j < 0) break;
			if ($data[$i][$j] == $target) {
				$countTarget = $countTarget + 1;
				if ($j + 1 <= 9 && $i - 1 >= 0)
					if ($data[$i - 1][$j + 1] == Blank) {
						$holderA[0][0] = $i - 1;
						$holderA[0][1] = $j + 1;
					}
			} else if ($data[$i][$j] != Blank) {
				if ($countTarget != 0) {
					if($countTarget>=4)
						$target_layer[3][$holderA[0][0]][$holderA[0][1]] = 4;
					else  
						$target_layer[3][$holderA[0][0]][$holderA[0][1]] = $countTarget - 1;
				}
				$countPrevent = 1;
				$countTarget = 0;
				$holderA[0][0] = -1;
				$holderA[0][1] = -1;
				$holderA[1][0] = -1;
				$holderA[1][1] = -1;
				$holderA[2][0] = -1;
				$holderA[2][1] = -1;
			} else {
				if ($countTarget == 0) {
					$holderA[0][0] = -1;
					$holderA[0][1] = -1;
					$holderA[1][0] = -1;
					$holderA[1][1] = -1;
					$holderA[2][0] = -1;
					$holderA[2][1] = -1;
					$countPrevent = 0;
					continue;
				}
				$holderA[1][0] = $i;
				$holderA[1][1] = $j;
				$k1 = $i + 1;
				$k2 = $j - 1;
				$in_seri = 0;
				while ($k1 <= 9 && $k2 >= 0) {
					if ($data[$k1][$k2] == $target) {
						$countTarget = $countTarget + 1;
						$k1++;
						$k2--;
						$in_seri++;
					} else if ($data[$k1][$k2] == Blank) {
						if ($in_seri != 0) {
							$holderA[2][0] = $k1;
							$holderA[2][1] = $k2;
						}
						break;
					} else {
						$countPrevent = $countPrevent + 1;
						break;
					}
				}
				if ($k1 > 9) {
					$jump = 9 - $startX;
				} else {
					$jump = $k1 - $startX;
				}
				
					
				if ($countPrevent == 2 && $countTarget < 4) {					
						$target_layer[3][$holderA[1][0]][$holderA[1][1]] = 0;					
						
				} else {
					for ($m = 0; $m <= 2; $m++)
						if ($holderA[$m][0] != -1)
							if ($countTarget >= 4)
							$target_layer[3][$holderA[$m][0]][$holderA[$m][1]] = 4;
						else 
						$target_layer[3][$holderA[$m][0]][$holderA[$m][1]] = $countTarget - $countPrevent;
				}
				if ($max_target_layer[3] < $target_layer[3][$holderA[1][0]][$holderA[1][1]]) {
					$max_target_layer[3] = $target_layer[3][$holderA[1][0]][$holderA[1][1]];
				}
				// clean up
				$countPrevent = 0;
				$countTarget = 0;
				$holderA[0][0] = -1;
				$holderA[0][1] = -1;
				$holderA[1][0] = -1;
				$holderA[1][1] = -1;
				$holderA[2][0] = -1;
				$holderA[2][1] = -1;
			}
		}
		if ($point == 0 && $turn == 0) {
			$point = 10;
			$turn = 1;
		}
	}
	// collet data
	$max_total = 0;
	$tmp_array[0][0] = -1;
	$tmp_array[0][1] = -1;
	$tmp_array[0][2] = -1;
	$tmp_array[0][3] = -1;
	for ($k = 0; $k <= 3; $k++) {
		if ($max_total < $max_target_layer[$k]) {
			$max_total = $max_target_layer[$k];
		}
	}
	if ($max_total == 0) return $tmp_array;
	for ($m = $max_total; $m >= 2; $m--) {
		for ($k = 0; $k <= 3; $k++) {
			if ($m == $max_target_layer[$k]) {
				for ($i = 0; $i <= 9; $i++) {
					for ($j = 0; $j <= 9; $j++) {
						if ($target_layer[$k][$i][$j] == $m) {
							$got = false;
							// add to existed
							for ($n = 1; $n < sizeof($tmp_array); $n++) {
								if ($tmp_array[$n][1] == $i && $tmp_array[$n][2] == $j) {
									$tmp_array[$n][3]++;
									$got = true;
									break;
								}
							}
							if ($got == false) {
								$size = sizeof($tmp_array);
								$tmp_array[$size][0] = $m;
								$tmp_array[$size][1] = $i;
								$tmp_array[$size][2] = $j;
								$tmp_array[$size][3] = 1;
							}
						}
					}
				}
			}
		}
	}
	if ($max_total >= 2)
		if (sizeof($tmp_array) > 1) {
			for ($m = $max_total; $m >= 2; $m--) {
				for ($k = 1; $k < sizeof($tmp_array); $k++) {
					//sorting
					if ($tmp_array[$k][0] == $m) {
						for ($i = $k + 1; $i < sizeof($tmp_array); $i++) {
							if ($tmp_array[$i][0] == $m) {
								if ($tmp_array[$k][3] < $tmp_array[$i][3]) {
									//swap
									$tmp[1] = $tmp_array[$k][1];
									$tmp[2] = $tmp_array[$k][2];
									$tmp[3] = $tmp_array[$k][3];
									$tmp_array[$k][1] = $tmp_array[$i][1];
									$tmp_array[$k][2] = $tmp_array[$i][2];
									$tmp_array[$k][3] = $tmp_array[$i][3];
									$tmp_array[$i][1] = $tmp[1];
									$tmp_array[$i][2] = $tmp[2];
									$tmp_array[$i][3] = $tmp[3];
								}
							} else {
								break;
							}
						}
					}
				}
			}
			return $tmp_array;
		}

	// sum 4 layer	
	$max_target_layer[4] = 0;
	for ($i = 0; $i <= 9; $i++) {
		for ($j = 0; $j <= 9; $j++) {
			$target_layer[4][$i][$j] = 0;
			for ($k = 0; $k <= 3; $k++)
				$target_layer[4][$i][$j] = $target_layer[4][$i][$j] + $target_layer[$k][$i][$j];
			if ($target_layer[4][$i][$j] > $max_target_layer[4])
				$max_target_layer[4] = $target_layer[4][$i][$j];
		}
	}
	$layer_chose = 0;
	$max_total = 0;
	for ($k = 0; $k <= 4; $k++) {
		if ($max_total < $max_target_layer[$k]) {
			$max_total = $max_target_layer[$k];
		}
	}
	for ($k = 0; $k <= 4; $k++) {
		if ($max_total == $max_target_layer[$k]) {
			$layer_chose = $k;
		}
	}
	// 	

	for ($i = 0; $i <= 9; $i++) {
		for ($j = 0; $j <= 9; $j++) {
			if ($target_layer[$layer_chose][$i][$j] == $max_total) {
				$size = sizeof($tmp_array);
				$tmp_array[$size][0] = $max_total;
				$tmp_array[$size][1] = $i;
				$tmp_array[$size][2] = $j;
				$tmp_array[$size][3] = 1;
			}
		}
	}
	return $tmp_array;
}
/****************************** Section for  Gaming Function End ********************************************/
?>