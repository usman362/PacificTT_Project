<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyUpdate extends Model
{
    public const TYPES = ['priority', 'alert', 'success', 'general'];

    public const DEPARTMENTS = ['Company-wide', 'Enrollment', 'Training', 'Operations', 'Finance', 'Leadership'];

    protected $fillable = ['message', 'type', 'department', 'published_by'];
}
