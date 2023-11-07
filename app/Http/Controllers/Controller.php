<?php

namespace App\Http\Controllers;

use Brevo\Client\Api\TransactionalEmailsApi;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $brevoApiInstance;

    public function __construct(TransactionalEmailsApi $brevoApiInstance)
    {
        $this->brevoApiInstance = $brevoApiInstance;
    }
}
