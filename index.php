<?php
error_reporting(E_ERROR);

$basedir = "/mnt/Videos";
$pidfile = "/tmp/vlc";
$transcode = "--sout='#transcode{soverlay,ab=48,samplerate=44100,channels=1,acodec=mp4a,vcodec=h264,width=512,height=288,vfilter=\"canvas{width=512,height=288,aspect=16:9}\",fps=25,vb=384,venc=x264{vbv-bufsize=200,partitions=all,level=12,no-cabac,subme=7,threads=4,ref=2,mixed-refs=1,bframes=0,min-keyint=1,keyint=50,qpmax=51}}:gather:rtp{mp4a-latm,dst=127.0.0.1,port-audio=20000,port-video=20002,ttl=127,sdp=file:/usr/local/movies/movie.sdp}'";

function run_in_background($Command, $Priority = 0) {
	if($Priority)
		$PID = shell_exec("nohup nice -n $Priority $Command 2> /dev/null & echo $!");
	else
		$PID = shell_exec("nohup $Command 2> /dev/null & echo $!");
	return($PID);
}

function is_process_running($PID) {
	exec("ps $PID", $ProcessState);
	return(count($ProcessState) >= 2);
}

function kill_process($PID) {
	return (exec("kill $PID"));
}

function getPid($pidfile) {
	$ret = null;
	if (file_exists($pidfile)) {
		$pidfileHandle = fopen($pidfile, "r");
		$ret = fread($pidfileHandle, filesize($pidfile));
		fclose($pidfileHandle);
	}
	return $ret;
}

function right($value, $count) {
    return substr($value, ($count*-1));
}

function stop() {
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">

<head>
	<title>Streaming Server Browser</title>
	<meta http-equiv="content-type" content="text/html;charset=ISO-8859-1" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<style type="text/css">
	img {
		border: none;
	}
	</style>
</head>
<body>
<?php
$page = $_SERVER["SCRIPT_NAME"];

$pid = getPid($pidfile);

if (!is_null($pid) && is_process_running($pid)) {
	kill_process($pid);
}

$dir = $_GET['dir'];
if (0 !== strpos($dir, $basedir)) {
	$dir = $basedir;
}

$dir = rtrim(str_replace("\\", "", $dir), " /");

$f = $_GET['file'];
if (0 !== strpos($f, $basedir)) {
	$f = null;
}

if (!is_null($f)) {
	$f = rtrim(str_replace("\\", "", $f), " /");

	echo "<h1>".$f."</h1>\n";
	echo "<div>\n";

	$command = "vlc --pidfile=$pidfile -d \"$f\" $transcode";
	run_in_background($command);
	echo "<a href=\"rtsp://www.rickbassham.com:8082/movie.sdp\">Watch Now</a>\n";
	echo "<a href=\"$page?dir=".htmlentities(dirname($f), ENT_COMPAT, "ISO-8859-1")."\">Stop</a>\n";
	echo "</div>\n";
}
else {
	echo "<h1>".htmlentities($dir, ENT_COMPAT, "ISO-8859-1")."</h1>\n";
	echo "<div>\n";
	if (is_dir($dir)) {
		echo "<ul>\n";
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if (is_dir($dir."/".$file)) {
					if ("." === $file) {
//						echo "<li><a href=\"$page?dir=".htmlentities($dir, ENT_COMPAT, "ISO-8859-1")."\">".htmlentities($file, ENT_COMPAT, "ISO-8859-1")."</a></li>\n";
					}
					else if (".." === $file) {
						echo "<li><a href=\"$page?dir=".htmlentities(dirname($dir), ENT_COMPAT, "ISO-8859-1")."\">$file</a></li>\n";
					}
					else {
						echo "<li><a href=\"$page?dir=".htmlentities($dir, ENT_COMPAT, "ISO-8859-1")."/".htmlentities($file, ENT_COMPAT, "ISO-8859-1")."\">".htmlentities($file, ENT_COMPAT, "ISO-8859-1")."</a></li>\n";
					}
				}
				else {
					if (false !== ($movie = new ffmpeg_movie($dir."/".$file, false))) {
						$duration = $movie->getDuration();
						if ($duration > 1) {
							echo "<li><a href=\"$page?file=".htmlentities($dir, ENT_COMPAT, "ISO-8859-1")."/".htmlentities($file, ENT_COMPAT, "ISO-8859-1")."\">".htmlentities($file, ENT_COMPAT, "ISO-8859-1")."</a></li>\n";
						}
					}
					else if (".ifo" === strtolower(right($file, 4))) {
						echo "<li><a href=\"$page?file=".htmlentities($dir, ENT_COMPAT, "ISO-8859-1")."/".htmlentities($file, ENT_COMPAT, "ISO-8859-1")."\">".htmlentities($file, ENT_COMPAT, "ISO-8859-1")."</a></li>\n";
					}
				}
			}
			closedir($handle);
		}
		echo "</ul>\n";
	}
	echo "</div>\n";
}
?>
	<p>
		<a href="http://validator.w3.org/check?uri=referer"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid XHTML 1.0 Strict" height="31" width="88" /></a>
		<a href="http://jigsaw.w3.org/css-validator/check/referer"><img src="http://jigsaw.w3.org/css-validator/images/vcss"alt="Valid CSS!" height="31" width="88" /></a>
	</p>
</body>
</html>

