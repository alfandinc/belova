<?php

namespace App\Support\Dashboard\Widgets;

use App\Models\User;

/**
 * A home dashboard widget's data. The Blade view (config "view") only renders what data() returns.
 *
 * $filter holds the dashboard period: period_start / period_end (Carbon), start_date / end_date
 * (Y-m-d), year, month. Widget-specific options come from request() inputs marked
 * data-dashboard-filter-input in the view, which the dashboard sends back on every reload.
 */
interface Widget
{
    public function data(User $user, array $filter): array;
}
