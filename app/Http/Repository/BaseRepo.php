<?php

namespace App\Http\Repository;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepo
{
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    public function model()
    {
        return $this->model;
    }


    public function find($column, $id)
    {
        return $this->model->where($column, $id)->first();
    }

    public function get_class_name()
    {
        return get_class($this->model);
    }
}
