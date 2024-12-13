$ovkRoot = $args[0]
$file    = $args[1]
$dir     = $args[2]
$hash    = $args[3]
$hashT   = $hash.substring(0, 2)
$temp    = [System.IO.Path]::GetTempFileName()
$temp2   = [System.IO.Path]::GetTempFileName()

$shell = Get-WmiObject Win32_process -filter "ProcessId = $PID"
$shell.SetPriority(16384)

Move-Item $file $temp

# video stub logic was implicitly deprecated, so we start processing at once
ffmpeg -i $temp -ss 00:00:01.000 -vframes 1 "$dir$hashT/$hash.gif"
ffmpeg -i $temp -c:v libx264 -q:v 7 -c:a libmp3lame -q:a 4 -tune zerolatency -vf "scale=iw*min(1\,if(gt(iw\,ih)\,640/iw\,(640*sar)/ih)):(floor((ow/dar)/2))*2" -y $temp2

Move-Item $temp2 "$dir$hashT/$hash.mp4"
Remove-Item $temp
Remove-Item $temp2
