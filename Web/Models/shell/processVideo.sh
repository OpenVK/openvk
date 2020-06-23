tmpfile="$RANDOM-$(date +%s%N)"

cp $2 "/tmp/vid_$tmpfile.bin"
cp ../files/video/rendering.apng $3${4:0:2}/$4.gif
cp ../files/video/rendering.ogv  $3/${4:0:2}/$4.ogv

nice ffmpeg -i "/tmp/vid_$tmpfile.bin" -ss 00:00:01.000 -vframes 1 $3${4:0:2}/$4.gif
nice -n 20 ffmpeg -i "/tmp/vid_$tmpfile.bin" -c:v libtheora -q:v 7 -c:a libvorbis -q:a 4 -vf scale=640x360,setsar=1:1 -y "/tmp/ffmOi$tmpfile.ogv"

rm -rf $3${4:0:2}/$4.ogv
mv "/tmp/ffmOi$tmpfile.ogv" $3${4:0:2}/$4.ogv

rm -f "/tmp/ffmOi$tmpfile.ogv"
rm -f "/tmp/vid_$tmpfile.bin"
