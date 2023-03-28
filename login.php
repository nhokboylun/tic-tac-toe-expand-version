<?php session_start(); /* Starts the session */
	
	/* Check Login form submitted */	
	if(isset($_POST['Submit'])){
		if(isset($_POST['Username']) && isset($_POST['Password'])){
			$Username = $_POST['Username'];
			$Password = $_POST['Password'];
		if($_POST['Submit']=="Login"){
		// check whith database
		$lines= file("./files/database.txt");
		foreach($lines as $line){
			$tmp_list = explode(",",$line);
			if($tmp_list[0]==$Username){
				if($tmp_list[1]=$Password){
				$_SESSION['UserData']['Username']=$tmp_list[0];
				$_SESSION['UserData']['Password']=$tmp_list[1];
				$_SESSION['UserData']['Score']=$tmp_list[2];				
				print_login_page("Log in succesful. Transfering in 3s.",true);	
				header("refresh:3;url=caro.php");
				exit;
				}
				else{
					$msg="Password incorrect";
				}				
			}
		}
			$msg="User not found. Please refill and click Sign Up.";			
		}
		else if($_POST['Submit']=="Signup"){
			$lines= file("./files/database.txt");
			$existed=false;
			foreach($lines as $line){
				$tmp_list = explode(",",$line);
				if($tmp_list[0]==$Username){
					$msg="Username is existed";
					$existed=true;
					break;	
				}
			}
			if($existed==false){
				//create new account
				$linedata = $Username.",".$Password.",0";
				$fp = fopen('./files/database.txt', 'a');
				fwrite($fp,$linedata);
				fclose($fp);
				$_SESSION['UserData']['Username']=$Username;
				$_SESSION['UserData']['Password']=$Password;
				$_SESSION['UserData']['Score']=0;
				print_login_page("Created new User succesful. Transfering in 3s.",true);
				header("refresh:3;url=caro.php");
				exit;
				}
				
			}
		}
		else{
			$msg="Invaild Input";
		}
		print_login_page($msg,false);
	}
else{
	if(isset($_SESSION['UserData']))
	{
		header("location:caro.php");
		exit;
	}
	print_login_page("",false);
}
function print_login_page($msg,$pass){
	echo
		"
<!doctype html>
<html>
<head>
<meta charset='utf-8'>
<title>Login</title>
<link href='./css/login.css' rel='stylesheet'>
</head>
<body>
<div id='main'>
<div id='wrap-main-content'>
	<div id='wrap-login-content'>
	<span class='login-title'>Login</span>
	<form class ='a-form' action='' method='post' name='Login_Form'>
		<div class='wrap-input' data-validate = 'Username is required'>
			<span class='label-input'>Username</span>
			<input class='textbox-input' type='text' name='Username' placeholder='Type your username'>			
		</div>
		<div class='wrap-input' data-validate = 'Password is required'>
			<span class='label-input'>Password</span>
			<input class='textbox-input' type='password' name='Password' placeholder='Type your password'>		
		</div>
		<span class='error-msg'>".$msg."</span>		
		<div class='wrap-btn'>	
			<span class='label-btn1'>Login</span>";
			if($pass==false)
				echo "<input name='Submit' type='submit' value='Login' class='btn-input' id='login_btn'>";
		echo "
			<div class='background-btn' id='background-btn1'></div>		 				
		</div>				 	
		<div class='wrap-btn'>
			<span class='label-btn2'>Sign Up</span>";
			if($pass==false)
				echo "<input name='Submit' type='submit' value='Signup' class='btn-input' id='signup_btn'>";
		echo "			
			<div class='background-btn' id='background-btn2'></div>
		</div>
	</form>
	</div>
	
	<div id='wrap-help-content'>
	 <span class='help_tille'>About Game</span>
    <ul>
    <li>The <span style='color:green'>Caro Game</span> will play in table 10x10.</li>
    <li>Human will be player as <span style='color:blue'>O sticks</span></li>
	<li>AI will be player as  <span style='color:red'>X stickes</span>.</li>
	<li>Who get <span style='color:green'>5 sticks</span> in row will Win</li>
    </ul>
    <span class='help_tille'>Developer</span>
	<h2>Leader:</h2>
    <ul>
    <li>Nhon La</li>
    </ul>
	<h2>Members:</h2>
	<ul>
    <li>Tran Nguyen</li>
	<li>Joel Biju </li>
	<li>Brandon Thompson</li>
    </ul>
	<br>
	</div>
	</div>
</div>
</body>
</html>";

}	
?>

