<?php

namespace App\Models\Sys;

use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    protected $table = 'sys_dashboard_widgets';
    protected $fillable = ['widget_name', 'component_path', 'description', 'is_active'];

    public function positionMappings()
    {
        return $this->hasMany(PositionWidget::class, 'widget_id');
    }
}
