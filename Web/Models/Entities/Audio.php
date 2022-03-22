<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Util\Shell\Exceptions\UnknownCommandException;
use openvk\Web\Util\Shell\Shell;

/**
 * @method setName(string)
 * @method setPerformer(string)
 * @method setLyrics(string)
 * @method setExplicit(bool)
 */
class Audio extends Media
{
    protected $tableName     = "audios";
    protected $fileExtension = "mpd";

    # Taken from winamp :D
    const genres = [
        'Blues','Big Band','Classic Rock','Chorus','Country','Easy Listening','Dance','Acoustic','Disco','Humour','Funk','Speech','Grunge','Chanson','Hip-Hop','Opera','Jazz','Chamber Music','Metal','Sonata','New Age','Symphony','Oldies','Booty Bass','Other','Primus','Pop','Porn Groove','R&B','Satire','Rap','Slow Jam','Reggae','Club','Rock','Tango','Techno','Samba','Industrial','Folklore','Alternative','Ballad','Ska','Power Ballad','Death Metal','Rhythmic Soul','Pranks','Freestyle','Soundtrack','Duet','Euro-Techno','Punk Rock','Ambient','Drum Solo','Trip-Hop','A Cappella','Vocal','Euro-House','Jazz+Funk','Dance Hall','Fusion','Goa','Trance','Drum & Bass','Classical','Club-House','Instrumental','Hardcore','Acid','Terror','House','Indie','Game','BritPop','Sound Clip','Negerpunk','Gospel','Polsk Punk','Noise','Beat','AlternRock','Christian Gangsta Rap','Bass','Heavy Metal','Soul','Black Metal','Punk','Crossover','Space','Contemporary Christian','Meditative','Christian Rock','Instrumental Pop','Merengue','Instrumental Rock','Salsa','Ethnic','Thrash Metal','Gothic','Anime','Darkwave','JPop','Techno-Industrial','Synthpop','Electronic','Abstract','Pop-Folk','Art Rock','Eurodance','Baroque','Dream','Bhangra','Southern Rock','Big Beat','Comedy','Breakbeat','Cult','Chillout','Gangsta Rap','Downtempo','Top 40','Dub','Christian Rap','EBM','Pop / Funk','Eclectic','Jungle','Electro','Native American','Electroclash','Cabaret','Emo','New Wave','Experimental','Psychedelic','Garage','Rave','Global','Showtunes','IDM','Trailer','Illbient','Lo-Fi','Industro-Goth','Tribal','Jam Band','Acid Punk','Krautrock','Acid Jazz','Leftfield','Polka','Lounge','Retro','Math Rock','Musical','New Romantic','Rock & Roll','Nu-Breakz','Hard Rock','Post-Punk','Folk','Post-Rock','Folk-Rock','Psytrance','National Folk','Shoegaze','Swing','Space Rock','Fast Fusion','Trop Rock','Bebob','World Music','Latin','Neoclassical','Revival','Audiobook','Celtic','Audio Theatre','Bluegrass','Neue Deutsche Welle','Avantgarde','Podcast','Gothic Rock','Indie Rock','Progressive Rock','G-Funk','Psychedelic Rock','Dubstep','Symphonic Rock','Garage Rock','Slow Rock','Psybient','Psychobilly','Touhou'
    ];

    private function fileLength(string $filename): int
    {
        if(!Shell::commandAvailable("ffmpeg") || !Shell::commandAvailable("ffprobe"))
            throw new \Exception();

        $error   = NULL;
        $streams = Shell::ffprobe("-i", $filename, "-show_streams", "-select_streams a", "-loglevel error")->execute($error);
        if($error !== 0)
            throw new \DomainException("$filename is not recognized as media container");
        else if(empty($streams) || ctype_space($streams))
            throw new \DomainException("$filename does not contain any audio streams");

        $vstreams = Shell::ffprobe("-i", $filename, "-show_streams", "-select_streams v", "-loglevel error")->execute($error);
        if(!empty($vstreams) && !ctype_space($vstreams))
            throw new \DomainException("$filename is a video");

        $durations = [];
        preg_match_all('%duration=([0-9\.]++)%', $streams, $durations);
        if(sizeof($durations[1]) === 0)
            throw new \DomainException("$filename does not contain any meaningful audio streams");

        $length = 0;
        foreach($durations[1] as $duration) {
            $duration = floatval($duration);
            if($duration < 1.0 || $duration > 65536.0)
                throw new \DomainException("$filename does not contain any meaningful audio streams");
            else
                $length = max($length, $duration);
        }

        return (int) round($length, 0, PHP_ROUND_HALF_EVEN);
    }

    /**
     * @throws \Exception
     */
    protected function saveFile(string $filename, string $hash): bool
    {
        $duration = $this->fileLength($filename);

        $kid = openssl_random_pseudo_bytes(16);
        $key = openssl_random_pseudo_bytes(16);
        $tok = openssl_random_pseudo_bytes(28);
        $ss  = ceil($duration / 15);

        $this->stateChanges("kid", $kid);
        $this->stateChanges("key", $key);
        $this->stateChanges("token", $tok);
        $this->stateChanges("segment_size", $ss);
        $this->stateChanges("length", $ss);

        try {
            $args = [
                OPENVK_ROOT,
                $this->getBaseDir(),
                $hash,
                $filename,

                bin2hex($kid),
                bin2hex($key),
                bin2hex($tok),
                $ss,
            ];

            if(Shell::isPowershell())
                Shell::powershell("-executionpolicy bypass", "-File", __DIR__ . "/../shell/processAudio.ps1", ...$args)
                    ->start();
            else
                Shell::bash(__DIR__ . "/../shell/processAudio.sh", ...$args)->start();

            # Wait until processAudio will consume the file
            $start = time();
            while(file_exists($filename))
                if(time() - $start > 5)
                    exit("Timed out waiting for ffmpeg");

        } catch(UnknownCommandException $ucex) {
            exit(OPENVK_ROOT_CONF["openvk"]["debug"] ? "bash/pwsh is not installed" : VIDEOS_FRIENDLY_ERROR);
        }

        return true;
    }

    function getTitle(): string
    {
        return $this->getRecord()->name;
    }

    function getPerformer(): string
    {
        return $this->getRecord()->performer;
    }

    function getName(): string
    {
        return $this->getTitle() . " - " . $this->getPerformer();
    }

    function getLyrics(): ?string
    {
        return $this->getRecord()->lyrics ?? NULL;
    }

    function getLength(): int
    {
        return $this->getRecord()->length;
    }

    function getSegmentSize(): float
    {
        return $this->getRecord()->segment_size;
    }

    function getListens(): int
    {
        return $this->getRecord()->listens;
    }

    function getOriginalURL(bool $force = false): string
    {
        $disallowed = OPENVK_ROOT_CONF["openvk"]["preferences"]["music"]["exposeOriginalURLs"] && !$force;
        if(!$this->isAvailable() || $disallowed)
            return ovk_scheme(true)
                . $_SERVER["HTTP_HOST"] . ":"
                . $_SERVER["HTTP_PORT"]
                . "/assets/packages/static/openvk/audio/nomusic.mp3";

        $key     = bin2hex($this->getRecord()->token);
        $garbage = sha1((string) time());

        return str_replace(".mpd", "_fragments", $this->getURL()) . "/original_$key.mp3?tk=$garbage";
    }

    function getKeys(): array
    {
        $keys[bin2hex($this->getRecord()->kid)] = bin2hex($this->getRecord()->key);

        return $keys;
    }

    function isExplicit(): bool
    {
        return $this->getRecord()->explicit;
    }

    function isWithdrawn(): bool
    {
        return $this->getRecord()->withdrawn;
    }

    function isUnlisted(): bool
    {
        return $this->getRecord()->unlisted;
    }

    # NOTICE may flush model to DB if it was just processed
    function isAvailable(): bool
    {
        if($this->getRecord()->processed)
            return true;

        # throttle requests to isAvailable to prevent DoS attack if filesystem is actually an S3 storage
        if(time() - $this->getRecord()->checked < 5)
            return false;

        try {
            $fragments = str_replace(".mpd", "_fragments", $this->getFileName());
            $original = "original_" . bin2hex($this->getRecord()->token) . "mp3";
            if (file_exists("$fragments/$original")) {
                # Original gets uploaded after fragments
                $this->stateChanges("processed", 0x01);

                return true;
            }
        } finally {
            $this->stateChanges("checked", time());
            $this->save();
        }

        return false;
    }

    function isInLibraryOf($entity): bool
    {
        return sizeof(DatabaseConnection::i()->getContext()->table("audio_relations")->where([
            "entity" => $entity->getId() * ($entity instanceof Club ? -1 : 1),
            "audio"  => $this->getId(),
        ])) != 0;
    }

    function add($entity): bool
    {
        if($this->isInLibraryOf($entity))
            return false;

        DatabaseConnection::i()->getContext()->table("audio_relations")->insert([
            "entity" => $entity->getId() * ($entity instanceof Club ? -1 : 1),
            "audio"  => $this->getId(),
        ]);

        return true;
    }

    function remove($entity): bool
    {
        if(!$this->isInLibraryOf($entity))
            return false;

        DatabaseConnection::i()->getContext()->table("audio_relations")->where([
            "entity" => $entity->getId() * ($entity instanceof Club ? -1 : 1),
            "audio"  => $this->getId(),
        ])->delete();

        return true;
    }

    function listen(User $user): bool
    {
        $listensTable = DatabaseConnection::i()->getContext()->table("audio_listens");
        $lastListen   = $listensTable->where([
            "user"  => $user->getId(),
            "audio" => $this->getId(),
        ])->fetch();

        if(!$lastListen || (time() - $lastListen->time >= 900)) {
            $listensTable->insert([
                "user"  => $user->getId(),
                "audio" => $this->getId(),
                "time"  => time(),
            ]);
            $this->stateChanges("listens", $this->getListens() + 1);

            return true;
        }

        $lastListen->update([
            "time" => time(),
        ]);

        return false;
    }

    function setGenre(string $genre): void
    {
        if(!in_array($genre, Audio::genres)) {
            $this->stateChanges("genre", NULL);
        }

        $this->stateChanges("genre", $genre);
    }

    function setCopyrightStatus(bool $withdrawn = true): void {
        $this->stateChanges("withdrawn", $withdrawn);
    }

    function setSearchability(bool $searchable = true): void {
        $this->stateChanges("unlisted", !$searchable);
    }

    function setToken(string $tok): void {
        throw new \LogicException("Changing keys is not supported.");
    }

    function setKid(string $kid): void {
        throw new \LogicException("Changing keys is not supported.");
    }

    function setKey(string $key): void {
        throw new \LogicException("Changing keys is not supported.");
    }

    function setLength(int $len): void {
        throw new \LogicException("Changing length is not supported.");
    }

    function setSegment_Size(int $len): void {
        throw new \LogicException("Changing length is not supported.");
    }
}