<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ConvertLinkPublikasiToJson extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration will:
     * 1. Add a temporary JSON column `link_publikasi_tmp`.
     * 2. Convert existing `link_publikasi` values into a structured JSON mapping.
     *    Heuristics used:
     *      - If the existing value is valid JSON, use it.
     *      - If it's a delimited string ("||" or comma), split into array.
     *      - If it's a single string, store as single-element array and attempt to map
     *        to the first platform when platform data exists.
     *      - If platforms exist and link array length === platforms length, map by index
     *        to { platform => url }.
     * 3. Create a new `link_publikasi` JSON column, copy converted values into it,
     *    and remove the old column.
     *
     * Note: Back up your database before running.
     */
    public function up()
    {
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->json('link_publikasi_tmp')->nullable()->after('link_asset');
        });

        // Chunk through rows and populate link_publikasi_tmp
        DB::table('marketing_content_plans')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $r) {
                $old = $r->link_publikasi;
                $platformRaw = $r->platform;
                $platforms = [];

                // normalize platforms (may be JSON or comma-separated)
                if ($platformRaw) {
                    if (is_string($platformRaw)) {
                        $decoded = json_decode($platformRaw, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $platforms = $decoded;
                        } else {
                            // try comma split
                            if (strpos($platformRaw, ',') !== false) {
                                $platforms = array_map('trim', explode(',', $platformRaw));
                            } else {
                                $platforms = [$platformRaw];
                            }
                        }
                    } elseif (is_array($platformRaw)) {
                        $platforms = $platformRaw;
                    }
                }

                $new = null;

                // If already JSON
                if ($old && is_string($old)) {
                    $decoded = json_decode($old, true);
                    if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                        $new = $decoded;
                    }
                }

                if ($new === null) {
                    if (is_array($old)) {
                        $new = $old;
                    } elseif ($old && is_string($old)) {
                        // delimiter heuristics
                        if (strpos($old, '||') !== false) {
                            $parts = array_map('trim', explode('||', $old));
                            $new = $parts;
                        } elseif (strpos($old, ',') !== false) {
                            $parts = array_map('trim', explode(',', $old));
                            $new = $parts;
                        } else {
                            // single string
                            $new = [$old];
                        }
                    } else {
                        $new = null;
                    }
                }

                // Attempt to map to platforms where sensible
                $final = null;
                if ($new === null) {
                    $final = null;
                } elseif (is_array($new)) {
                    // if associative array (string keys), use as-is
                    $isAssoc = array_keys($new) !== range(0, count($new) - 1);
                    if ($isAssoc) {
                        $final = $new;
                    } else {
                        // numeric array: if platforms length matches, map by index
                        if (!empty($platforms) && count($platforms) === count($new)) {
                            $map = [];
                            foreach ($platforms as $i => $p) {
                                $map[$p] = $new[$i] ?? null;
                            }
                            $final = $map;
                        } else {
                            // else, store numeric array as-is (client code will handle mapping heuristics)
                            $final = array_values(array_filter($new, function ($v) { return $v !== null && $v !== ''; }));
                        }
                    }
                } else {
                    // fallback
                    $final = $new;
                }

                if ($final !== null) {
                    DB::table('marketing_content_plans')->where('id', $r->id)->update([
                        'link_publikasi_tmp' => json_encode($final)
                    ]);
                }
            }
        });

        // Drop old column and create new json column, then copy data from tmp
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_content_plans', 'link_publikasi')) {
                $table->dropColumn('link_publikasi');
            }
        });

        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->json('link_publikasi')->nullable()->after('link_asset');
        });

        // Copy values from tmp to new column
        DB::table('marketing_content_plans')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $r) {
                    if ($r->link_publikasi_tmp !== null) {
                    DB::table('marketing_content_plans')->where('id', $r->id)->update([
                        'link_publikasi' => $r->link_publikasi_tmp
                    ]);
                }
            }
        });

        // drop temporary column
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_content_plans', 'link_publikasi_tmp')) {
                $table->dropColumn('link_publikasi_tmp');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * This will attempt to convert JSON back into a string using '||' as separator
     * for arrays. Please backup before running.
     */
    public function down()
    {
        // add a temporary text column to hold old-format values
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->text('link_publikasi_old')->nullable()->after('link_asset');
        });

        // convert json -> string (join arrays with '||')
        DB::table('marketing_content_plans')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $r) {
                $val = $r->link_publikasi;
                $out = null;
                if ($val === null) {
                    $out = null;
                } else {
                    if (is_string($val)) {
                        $decoded = @json_decode($val, true);
                        if (json_last_error() === JSON_ERROR_NONE) $val = $decoded;
                    }
                    if (is_array($val)) {
                        // if associative, try to keep only values joined by ||
                        $isAssoc = array_keys($val) !== range(0, count($val) - 1);
                        if ($isAssoc) {
                            $out = implode('||', array_values($val));
                        } else {
                            $out = implode('||', $val);
                        }
                    } else {
                        $out = (string) $val;
                    }
                }
                DB::table('marketing_content_plans')->where('id', $r->id)->update([
                    'link_publikasi_old' => $out
                ]);
            }
        });

        // drop the json column and restore old column name
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_content_plans', 'link_publikasi')) {
                $table->dropColumn('link_publikasi');
            }
        });

        Schema::table('marketing_content_plans', function (Blueprint $table) {
            $table->text('link_publikasi')->nullable()->after('link_asset');
        });

        // copy back old values
        DB::table('marketing_content_plans')->orderBy('id')->chunk(100, function ($rows) {
            foreach ($rows as $r) {
                DB::table('marketing_content_plans')->where('id', $r->id)->update([
                    'link_publikasi' => $r->link_publikasi_old
                ]);
            }
        });

        // drop temporary old column
        Schema::table('marketing_content_plans', function (Blueprint $table) {
            if (Schema::hasColumn('marketing_content_plans', 'link_publikasi_old')) {
                $table->dropColumn('link_publikasi_old');
            }
        });
    }
}
