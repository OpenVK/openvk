$ovkRoot = $args[0]
$file    = $args[1]
$dir     = $args[2]
$hash    = $args[3]
$hashT   = $hash.substring(0, 2)
$temp    = [System.IO.Path]::GetTempFileName()
$temp2   = [System.IO.Path]::GetTempFileName()

Move-Item $file $temp

# video stub logic was implicitly deprecated, so we start processing at once
ffmpeg -i $temp -ss 00:00:01.000 -vframes 1 "$dir$hashT/$hash.gif"
start /B /Low /Wait /Shared ffmpeg -i $temp -c:v libtheora -q:v 7 -c:a libvorbis -q:a 4 -vf scale=640x360,setsar=1:1 -y $temp2

Move-Item $temp2 "$dir$hashT/$hash.ogv"
Remove-Item $temp
Remove-Item $temp2