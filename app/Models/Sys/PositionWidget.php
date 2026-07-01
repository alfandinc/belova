<?php

namespace App\Models\Sys;

use Illuminate\Database\Eloquent\Model;

class PositionWidget extends Model
{
    protected $table = 'sys_position_widgets';
    protected $fillable = ['position_id', 'widget_id', 'row_index', 'order_index', 'column_span'];

    public function widget()
    {
        return $this->belongsTo(DashboardWidget::class, 'widget_id');
    }

    public function position()
    {
        return $this->belongsTo(\App\Models\HRD\Position::class, 'position_id');
    }
}
