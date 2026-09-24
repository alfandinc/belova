<?php

namespace App\Models\HRD;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Position extends Model
{
    use HasFactory;

    public const LEVEL_OPTIONS = [
        'Staff',
        'Koordinator',
        'Penanggung Jawab',
        'Manager',
        'Head Manager',
        'Direktur',
    ];

    protected $table = 'hrd_position';

    protected $fillable = ['name', 'level', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'hrd_employee_position')
                    ->withPivot('is_primary')
                    ->withTimestamps();
    }

    public function divisionMappings()
    {
        return $this->hasMany(PositionDivision::class, 'position_id');
    }

    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'hrd_position_division', 'position_id', 'division_id')
            ->withPivot('parent_position_id')
            ->withTimestamps();
    }

    public function parentPositions()
    {
        return $this->belongsToMany(self::class, 'hrd_position_division', 'position_id', 'parent_position_id')
            ->withPivot('division_id')
            ->withTimestamps();
    }

    public function childPositions()
    {
        return $this->belongsToMany(self::class, 'hrd_position_division', 'parent_position_id', 'position_id')
            ->withPivot('division_id')
            ->withTimestamps();
    }

    public function syncDivisionHierarchy(array $divisionIds, array $parentPositionIds = [], array $organizationUnits = []): void
    {
        $organizationUnits = collect($organizationUnits)
            ->map(function ($unit) {
                return [
                    'division_id' => filled($unit['division_id'] ?? null) ? (int) $unit['division_id'] : null,
                    'parent_position_id' => filled($unit['parent_position_id'] ?? null) ? (int) $unit['parent_position_id'] : null,
                ];
            })
            ->filter(function (array $unit) {
                return filled($unit['division_id']);
            })
            ->reject(function (array $unit) {
                return filled($unit['parent_position_id']) && (int) $unit['parent_position_id'] === (int) $this->id;
            })
            ->unique(fn (array $unit) => $unit['division_id'] . ':' . ($unit['parent_position_id'] ?? 'null'))
            ->values();

        if ($organizationUnits->isEmpty()) {
            $divisionIds = collect($divisionIds)
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $parentPositionIds = collect($parentPositionIds)
                ->filter(fn ($id) => filled($id) && (int) $id !== (int) $this->id)
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            foreach ($divisionIds as $divisionId) {
                if ($parentPositionIds->isEmpty()) {
                    $organizationUnits->push([
                        'division_id' => $divisionId,
                        'parent_position_id' => null,
                    ]);
                    continue;
                }

                foreach ($parentPositionIds as $parentPositionId) {
                    $organizationUnits->push([
                        'division_id' => $divisionId,
                        'parent_position_id' => $parentPositionId,
                    ]);
                }
            }
        }

        if (!$this->exists) {
            $this->save();
        }

        $this->divisionMappings()->delete();

        if ($organizationUnits->isNotEmpty()) {
            $this->divisionMappings()->createMany($organizationUnits->all());
        }

        $this->unsetRelation('divisionMappings');
        $this->unsetRelation('divisions');
        $this->unsetRelation('parentPositions');
    }

    public function getDivisionNamesAttribute(): string
    {
        return $this->collectRelatedNames('divisions');
    }

    public function getDivisionIdsAttribute(): array
    {
        return $this->collectRelatedIds('divisions');
    }

    public function getParentNamesAttribute(): string
    {
        return $this->collectRelatedNames('parentPositions');
    }

    public function getParentPositionIdsAttribute(): array
    {
        return $this->collectRelatedIds('parentPositions');
    }

    public function directParentPositions(): Collection
    {
        return ($this->relationLoaded('parentPositions')
            ? $this->getRelation('parentPositions')
            : $this->parentPositions()->get())
            ->filter()
            ->unique('id')
            ->values();
    }

    public function directChildPositions(): Collection
    {
        return ($this->relationLoaded('childPositions')
            ? $this->getRelation('childPositions')
            : $this->childPositions()->get())
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function collectRelatedNames(string $relation, ?string $fallback = null): string
    {
        $items = $this->relationLoaded($relation)
            ? $this->getRelation($relation)
            : $this->{$relation}()->get();

        if (!$items instanceof Collection) {
            return $fallback ?? '-';
        }

        $names = $items->pluck('name')->filter()->unique()->values();

        if ($names->isNotEmpty()) {
            return $names->implode(', ');
        }

        return $fallback ?: '-';
    }

    protected function collectRelatedIds(string $relation, $fallback = null): array
    {
        $items = $this->relationLoaded($relation)
            ? $this->getRelation($relation)
            : $this->{$relation}()->get();

        if (!$items instanceof Collection) {
            return array_filter([$fallback]);
        }

        $ids = $items->pluck('id')->filter()->unique()->values()->all();

        if (!empty($ids)) {
            return $ids;
        }

        return array_filter([$fallback]);
    }
}
