<?php

class FeatureInstance
{
    protected $scope;

    public function __construct($scope=null)
    {
        $this->scope = $scope? $scope : Auth::user();
    }

    protected function runCallback($callback)
    {
        if (!is_closure($callback)) {
            if (is_object($callback)) {
                return Helpers::ensureValueIsNotObject($callback);
            }
            return $callback;
        }

        list($class, $method) = getCallbackFromString($callback);

        $result = executeCallback($class, $method, array($this->scope));

        return Helpers::ensureValueIsNotObject($result);
    }

    private function getScope()
    {
        $scope = get_class($this->scope);
        $scope .= $this->scope instanceof Model
            ? '_' . $this->scope->getRouteKey()
            : '';

        return $scope;
    }

    private function store($feature, $result)
    {
        global $app_cache, $appCached;

        if (is_object($result)) {
            $result = Helpers::ensureValueIsNotObject($result);
        }

        if (is_bool($result)) {
            $result = $result ? 'bool:true' : 'bool:false';
        }

        $scope = $this->getScope();

        DB::table('features')->insert(array(
            'name' => $feature,
            'scope' => $this->getScope(),
            'value' => $result,
            'slug' => $feature."_".$scope
        ));

        Arr::set($appCached, 'features.'.$feature.'.'.$scope, $result);
        $app_cache->put('Baradur_cache', $appCached, 86400);
    }

    private function retrieve($feature)
    {
        global $appCached;

        $scope = $this->getScope();

        if (Arr::has($appCached, 'features.'.$feature.'.'.$scope)) {
            $res = Arr::get($appCached, 'features.'.$feature.'.'.$scope);
            return $res;
        }

        $result = DB::table('features')
            ->toBase()
            ->where('name', $feature)
            ->where('scope', $scope)
            ->first();

        return $result ? $result->value : null;
    }
    

    public function forget($feature)
    {
        global $app_cache, $appCached;

        $scope = $this->getScope();

        DB::table('features')
            ->where('name', $feature)
            ->where('scope', $scope)
            ->delete();

        Arr::forget($appCached, 'features.'.$feature.'.'.$scope);
        $app_cache->put('Baradur_cache', $appCached, 86400);
    }

    public function value($feature)
    {
        global $_class_list, $_feature_list;

        $stored = $this->retrieve($feature);

        if ($stored) {
            if ($stored==='bool:true') {
                return true;
            } elseif ($stored==='bool:false') {
                return false;
            } else {
                return $stored;
            }
        }

        if (isset($_class_list[$feature]) && in_array($feature, $_feature_list)) {
            $class = new $feature;
            $result = $class->resolve($this->scope);
        }
        else {
            $callback = Feature::__getFeature($feature);
            $result = $this->runCallback($callback);
        }

        if (is_object($result)) {
            $result = Helpers::ensureValueIsNotObject($result);
        }

        $this->store($feature, $result);

        return $result;
    }

    public function active($feature)
    {
        $result = $this->value($feature);

        $result = Helpers::ensureValueIsNotObject($result);

        if (!is_bool($result)) {
            throw new LogicException("Feature [$feature] should return boolean");
        }
        
        return $result;
    }

    public function inactive($feature)
    {
        return !$this->active($feature);
    }

    public function allAreActive($features)
    {
        $features = is_array($features) ? $features : array($features);

        foreach ($features as $feature) {
            if (!$this->active($feature)) {
                return false;
            }           
        }

        return true;
    }

    public function someAreActive($features)
    {
        $features = is_array($features) ? $features : array($features);

        $result = false;

        foreach ($features as $feature) {
            if ($this->active($feature)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    public function allAreInactive($features)
    {
        return !$this->allAreActive($features);
    }

    public function someAreInactive($features)
    {
        $features = is_array($features) ? $features : array($features);

        $result = false;

        foreach ($features as $feature) {
            if ($this->inactive($feature)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    public function when($feature, $callback_true, $callback_false=null)
    {
        return $this->active($feature)
            ? $this->runCallback($callback_true)
            : $this->runCallback($callback_false);
    }

    public function unless($feature, $callback_true, $callback_false=null)
    {
        return !$this->active($feature)
            ? $this->runCallback($callback_true)
            : $this->runCallback($callback_false);
    }

    public function values($features)
    {
        $features = is_array($features) ? $features : array($features);

        $result = array();

        foreach ($features as $feature) {
            $result[] = $this->value($feature);
        }

        return $result;
    }

    public function all()
    {
        global $_feature_list;

        $features = array_keys(Feature::__getFeatures());

        $features = array_merge($features, $_feature_list);

        $result = array();

        foreach ($features as $feature) {
            $result[$feature] = $this->value($feature);
        }

        return $result;
    }

    public function activate($feature)
    {
        global $app_cache, $appCached;

        $scope = $this->getScope();

        DB::table('features')->updateOrInsert(
            array(
                'name' => $feature,
                'scope' => $scope,
                'slug' => $feature."_".$scope
            ),
            array(
                'value' => 'bool:true',
            )
        );

        Arr::set($appCached, 'features.'.$feature.'.'.$scope, 'bool:true');
        $app_cache->put('Baradur_cache', $appCached, 86400);
    }

    public function deactivate($feature)
    {
        global $app_cache, $appCached;

        $scope = $this->getScope();

        DB::table('features')->updateOrInsert(
            array(
                'name' => $feature,
                'scope' => $scope,
                'slug' => $feature."_".$scope
            ),
            array(
                'value' => 'bool:false',
            )
        );

        Arr::set($appCached, 'features.'.$feature.'.'.$scope, 'bool:false');
        $app_cache->put('Baradur_cache', $appCached, 86400);
    }
    
    public function activateForEveryone($feature, $value=null)
    {
        global $app_cache, $appCached;

        $value = $value? $value : 'bool:true';

        DB::table('features')
            ->where('name', $feature)
            ->update(array('value' => $value? $value : 'bool:true'));

        foreach(array_keys($appCached['features'][$feature]) as $scope) {
            $appCached['features'][$feature][$scope] = $value;
        }
        $app_cache->put('Baradur_cache', $appCached, 86400);
    }

    public function deactivateForEveryone($feature)
    {
        global $app_cache, $appCached;

        DB::table('features')
            ->where('name', $feature)
            ->update(array('value' => 'bool:false'));

        foreach(array_keys($appCached['features'][$feature]) as $scope) {
            $appCached['features'][$feature][$scope] = 'bool:false';
        }
        $app_cache->put('Baradur_cache', $appCached, 86400);
    }

    public function purge($features)
    {
        global $app_cache, $appCached;

        if (is_null($features)) {
            DB::table('features')->truncate();
            $appCached['features'] = array();
            $app_cache->put('Baradur_cache', $appCached, 86400);
            return;
        }

        $features = is_array($features) ? $features : array($features);

        DB::table('features')
            ->whereIn('name', $features)
            ->delete();

        foreach($features as $feature) {
            unset($appCached['features'][$feature]);
        }
        $app_cache->put('Baradur_cache', $appCached, 86400);
    }
    

}