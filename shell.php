<?php	
bannerStart();
while(1)
{
	echo ':';
	$line = trim(fgets(STDIN));
	switch($line)
	{
		case 'help':
			help();
			break;
		case 'quit':
			exit();
			break;
		default:
			echo 'Unrecognized command, type help for information';
	}
	echo "\n";
//	echo $line.'<br />';
}
bannerEnd();

function help()
{
	echo 'info';
}

function bannerStart()
{
	echo "*******************************************************\n";
	echo "* WOXCORE INTERACTIVE SHELL ***************************\n";
	echo "*******************************************************\n";
}

function bannerEnd()
{
	echo "*******************************************************\n";
	echo "* WOXCORE INTERACTIVE SHELL ***************************\n";
	echo "*******************************************************\n";
}