<?php session_start();?>
<?php
require("class_mysql.php");

/*
// input
filehandle -> handler for comics
data -> in array "FILES" (POST)

// output
filehandle -> same as input
comic -> the url for previewing the comics
*/

if($_SESSION['username'] == null )
{
	$json['errorCode'] = 20501;
	echo json_encode($json);
	exit;
}

$comic_dir = "C:\\AppServ\\www\\proxy\\comic\\";
$filehandle = intval($_GET['filehandle']);

if( $filehandle == "" )
{
	$json['errorCode'] = 205001;
	echo json_encode($json);
	exit;
}

// change FILES array to be more readable
function fixFilesArray(&$files)
{
    $names = array( 'name' => 1, 'type' => 1, 'tmp_name' => 1, 'error' => 1, 'size' => 1);

    foreach ($files as $key => $part)
	{
        $key = (string) $key;
        if (isset($names[$key]) && is_array($part))
		{
            foreach ($part as $position => $value)
			{
                $files[$position][$key] = $value;
            }
            unset($files[$key]);
        }
    }
}

fixFilesArray($_FILES['data']);
$comics = array();
$i = 0;
foreach($_FILES['data'] as $file)
{
	$tmp_name = $file["tmp_name"];
    $name = $file["name"];
	$comicname = "comic/".$filehandle."_".$name;
    move_uploaded_file($tmp_name, $comicname);
	$i++;
}

$sql = "UPDATE  `project`.`book` SET  `pages` =  '".$i."',`modifiedtime` = NOW( ) WHERE  `book`.`filehandle` ='".$filehandle."'";

if( !$che = mysql_query($sql) )
{
	$json['errorCode'] = 1000;
	echo json_encode($json);
	exit;
}

$json['filehandle'] = $filehandle;
$json['comic'] = "preview.php?comic=".$filehandle."&page=".$i;
$json['errorCode'] = 0;
echo json_encode($json);
?>