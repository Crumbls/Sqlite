<?php

namespace Crumbls\Sqlite\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Database extends Model {

    use \Sushi\Sushi;



    /**
     * Get a list of Sqlite connections.
     * @return mixed
     */
    public function getRows()
    {
        return Cache::remember(__METHOD__, 1, function() {
                $temp = array_filter(config('database.connections'), function(array $item) {
                        if (!array_key_exists('driver', $item) || $item['driver'] != 'sqlite') {
                            return false;
                        }
                        if (!array_key_exists('database', $item) || !is_file($item['database'])) {
                            return false;
                        }
                        return true;
                    });

            $temp = array_map(function($key, $temp) {
                $temp['name'] = $key;
                return $temp;
            }, array_keys($temp), array_values($temp));

            /**
             * Not necessary.  Stripped with the array_map.  Retained for backwards compatability.
             */
            return array_values($temp);
        });
    }
}
