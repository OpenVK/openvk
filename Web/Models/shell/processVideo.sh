tmpfile="$RANDOM-$(date +%s%N)"

cp $2 "/tmp/vid_$tmpfile.bin"

nice ffmpeg -i "/tmp/vid_$tmpfile.bin" -ss 00:00:01.000 -vframes 1 $3${4:0:2}/$4.gif
nice -n 20 ffmpeg -i "/tmp/vid_$tmpfile.bin" -c:v libx264 -q:v 7 -c:a libmp3lame -q:a 4 -tune zerolatency -vf "scale=640:480:force_original_aspect_ratio=decrease,pad=640:480:(ow-iw)/2:(oh-ih)/2,setsar=1" -y "/tmp/ffmOi$tmpfile.mp4"

rm -rf $3${4:0:2}/$4.mp4
mv "/tmp/ffmOi$tmpfile.mp4" $3${4:0:2}/$4.mp4

rm -f "/tmp/ffmOi$tmpfile.mp4"
rm -f "/tmp/vid_$tmpfile.bin"
