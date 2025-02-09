<?php

declare(strict_types=1);

namespace openvk\Web\Util;

use Chandler\Session\Session;

class DateTime
{
    public const RELATIVE_FORMAT_NORMAL = 0;
    public const RELATIVE_FORMAT_LOWER  = 1;
    public const RELATIVE_FORMAT_SHORT  = 2;

    private $timestamp;
    private $localizator;

    public function __construct(?int $timestamp = null)
    {
        $this->timestamp   = $timestamp ?? time();
        $this->localizator = Localizator::i();
    }

    protected function zmdate(): string
    {
        $then = date_create("@" . $this->timestamp);
        $now  = date_create();
        $diff = date_diff($now, $then);

        $sessionOffset = intval(Session::i()->get("_timezoneOffset"));
        if ($diff->invert === 0) {
            return ovk_strftime_safe("%e %B %Y ", $this->timestamp) . tr("time_at_sp") . ovk_strftime_safe(" %R", $this->timestamp);
        }

        if (($this->timestamp + $sessionOffset) >= (strtotime("midnight") + $sessionOffset)) { # Today
            if ($diff->h >= 1) {
                return tr("time_today") . tr("time_at_sp") . ovk_strftime_safe(" %R", $this->timestamp);
            } elseif ($diff->i < 2) {
                return tr("time_just_now");
            } else {
                return $diff->i === 5 ? tr("time_exactly_five_minutes_ago") : tr("time_minutes_ago", $diff->i);
            }
        } elseif (($this->timestamp + $sessionOffset) >= (strtotime("-1day midnight") + $sessionOffset)) { # Yesterday
            return tr("time_yesterday") . tr("time_at_sp") . ovk_strftime_safe(" %R", $this->timestamp);
        } elseif (ovk_strftime_safe("%Y", $this->timestamp) === ovk_strftime_safe("%Y", time())) { # In this year
            return ovk_strftime_safe("%e %h ", $this->timestamp) . tr("time_at_sp") . ovk_strftime_safe(" %R", $this->timestamp);
        } else {
            return ovk_strftime_safe("%e %B %Y ", $this->timestamp) . tr("time_at_sp") . ovk_strftime_safe(" %R", $this->timestamp);
        }
    }

    public function format(string $format, bool $useDate = false): string
    {
        if (!$useDate) {
            return ovk_strftime_safe($format, $this->timestamp);
        } else {
            return date($format, $this->timestamp);
        }
    }

    public function relative(int $type = 0): string
    {
        switch ($type) {
            case static::RELATIVE_FORMAT_NORMAL:
                return mb_convert_case($this->zmdate(), MB_CASE_TITLE_SIMPLE);
            case static::RELATIVE_FORMAT_LOWER:
                return $this->zmdate();
            case static::RELATIVE_FORMAT_SHORT:
                return "";
        }
    }

    public function html(bool $capitalize = false, bool $short = false): string
    {
        if ($short) {
            $dt = $this->relative(static::RELATIVE_FORMAT_SHORT);
        } elseif ($capitalize) {
            $dt = $this->relative(static::RELATIVE_FORMAT_NORMAL);
        } else {
            $dt = $this->relative(static::RELATIVE_FORMAT_LOWER);
        }

        return "<time>$dt</time>";
    }

    public function timestamp(): int
    {
        return $this->timestamp;
    }

    public function __toString(): string
    {
        return $this->relative(static::RELATIVE_FORMAT_LOWER);
    }
}
