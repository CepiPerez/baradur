<?php

class EloquentUserProvider implements UserProvider
{
    protected $model;
    protected $queryCallback;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function retrieveById($identifier)
    {
        $model = $this->createModel();

        return $this->newModelQuery($model)
                    ->where($model->getAuthIdentifierName(), $identifier)
                    ->first();
    }

    public function retrieveByToken($identifier, $token)
    {
        $model = $this->createModel();

        $retrievedModel = $this->newModelQuery($model)
            ->where($model->getAuthIdentifierName(), $identifier)
            ->where($model->getRememberTokenName(), $token)
            ->first();

        if (! $retrievedModel) {
            return null;
        }

        return $retrievedModel;
    }

    public function updateRememberToken($user, $token)
    {
        $user->setRememberToken($token);

        //$timestamps = $user->timestamps;

        //$user->timestamps = false;

        $user->save();

        //$user->timestamps = $timestamps;
    }

    public function retrieveByCredentials($credentials)
    {
        /* $credentials = array_filter(
            $credentials,
            fn ($key) => ! str_contains($key, 'password'),
            ARRAY_FILTER_USE_KEY
        ); */


        if (!is_array($credentials) || empty($credentials)) {
            return;
        }

        // First we will add each credential element to the query as a where clause.
        // Then we can execute the query and, if we found a user, return it in a
        // Eloquent User "model" that will be utilized by the Guard instances.
        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if ($key != 'password') {
                $query->where($key, $value);
            }
        }

        return $query->first();
    }

    
    public function validateCredentials($user, $credentials)
    {
        if (!$user || !isset($credentials['password'])) {
            return false;
        }

        return strcmp($user->getAuthPassword(), md5($credentials['password']))===0;
    }

    protected function newModelQuery($model = null)
    {
        $query = is_null($model)
                ? $this->createModel()->newQuery()
                : $model->newQuery();

        //with($query, $this->queryCallback);

        return $query;
    }

    public function createModel()
    {
        //$class = '\\'.ltrim($this->model, '\\');

        return new $this->model;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    public function getQueryCallback()
    {
        return $this->queryCallback;
    }

    public function withQuery($queryCallback = null)
    {
        $this->queryCallback = $queryCallback;

        return $this;
    }
}
