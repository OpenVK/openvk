tmpfile="$RANDOM-$(date +%s%N)"
tmpdir="${TMPDIR:-/tmp}"
vidfile="$tmpdir/vid_$tmpfile.bin"
outfile="$tmpdir/ffmOi$tmpfile.mp4"
ffpid=""

cleanup() {
    [ -n "$ffpid" ] && kill "$ffpid" 2>/dev/null
    rm -f "$vidfile" "$outfile"
}
trap cleanup EXIT
trap 'exit 1' INT TERM HUP

cp "$2" "$vidfile"

nice ffmpeg -i "$vidfile" -ss 00:00:01.000 -vframes 1 "$3${4:0:2}/$4.gif" &
ffpid=$!
wait "$ffpid"

nice -n 20 ffmpeg -i "$vidfile" -c:v libx264 -q:v 7 -c:a libmp3lame -q:a 4 -tune zerolatency -vf "scale=iw*min(1\,if(gt(iw\,ih)\,640/iw\,(640*sar)/ih)):(floor((ow/dar)/2))*2" -y "$outfile" &
ffpid=$!
wait "$ffpid"

rm -rf "$3${4:0:2}/$4.mp4"
mv "$outfile" "$3${4:0:2}/$4.mp4"
