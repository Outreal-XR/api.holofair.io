<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'avatar',
        'phone_number',
        'uuid',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->uuid = Str::uuid();
        });
    }

    public function invites()
    {
        return $this->hasMany(InvitedUser::class, 'email', 'email');
    }

    public function subscription()
    {
        return $this->hasMany(Subscription::class, 'user_id', 'id');
    }

    public function plan()
    {
        return $this->hasOne(Plan::class, 'id', 'stripe_plan_id');
    }

    public function dashboardSettings()
    {
        return $this->belongsToMany(DashboardSetting::class, 'user_dashboard_settings', 'user_id', 'dashboard_setting_id')
            ->withPivot('value')
            ->withTimestamps();
    }

    /**
     * Get the full name of the user.
     * @return string
     */
    public function fullName(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function sendEmailVerificationNotification()
    {
        $url = $this->verificationUrl();

        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail->setSender(array('name' => 'HoloFair', 'email' => 'tech@holofair.io'));
        $sendSmtpEmail->setTo(array(array('name' => $this->fullName(), 'email' => $this->email)));
        $sendSmtpEmail->setTemplateId(7);
        $sendSmtpEmail->setParams(array(
            'firstname' => $this->first_name,
            'verificationlink' => $url
        ));

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
        $brevoApiInstance = new TransactionalEmailsApi(new Client(), $config);

        try {
            return $brevoApiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (Exception $e) {
            echo 'Exception when calling TransactionalEmailsApi->sendTransacEmail: ', $e->getMessage(), PHP_EOL;
        }
    }

    private function verificationUrl()
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );
    }

    public function sendPasswordResetNotification($token)
    {
        $url = env('FRONT_URL') . '/password-change?token=' . $token;

        $sendSmtpEmail = new SendSmtpEmail();
        $sendSmtpEmail->setSender(array('name' => 'HoloFair', 'email' => 'tech@holofair.io'));
        $sendSmtpEmail->setTo(array(array('name' => $this->fullName(), 'email' => $this->email)));
        $sendSmtpEmail->setHtmlContent(view('emails.reset-password')->with([
            'url' => $url
        ])->render());
        $sendSmtpEmail->setSubject('Reset Password');

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', env('BREVO_API_KEY'));
        $brevoApiInstance = new TransactionalEmailsApi(new Client(), $config);

        try {
            return $brevoApiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (Exception $e) {
            echo 'Exception when calling TransactionalEmailsApi->sendTransacEmail: ', $e->getMessage(), PHP_EOL;
        }
    }
}
