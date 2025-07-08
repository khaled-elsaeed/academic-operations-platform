<?php

if (!function_exists('formatDate')) {
    /**
     * Format a date using Carbon.
     *
     * @param string|\DateTimeInterface|null $date
     * @param string $format
     * @return string|null
     */
    function formatDate($date, $format = 'd M h:i A')
    {
        if (!$date) {
            return null;
        }
        return \Carbon\Carbon::parse($date)->format($format);
    }
} 