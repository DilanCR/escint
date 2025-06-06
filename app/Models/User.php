<?php

namespace App\Models;

use App\Constants\GlobalConst;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $appends = ['fullname','userImage','stringStatus','lastLogin','kycStringStatus'];
    protected $dates = ['deleted_at'];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ["id"];

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
        'id' => 'integer', 
        'firstname' => 'string',
        'lastname' => 'string',
        'username' => 'string',
        'email' => 'string',
        'mobile_code' => 'string',
        'mobile' => 'string',
        'full_mobile' => 'string',
        'password' => 'string',
        'refferal_user_id' => 'integer',
        'image' => 'string',
        'email_verified_at' => 'datetime',
        'email_verified' => 'integer', 
        'sms_verified' => 'integer', 
        'kyc_verified' => 'integer', 
        'two_factor_verified' => 'integer', 
        'two_factor_secret' => 'string', 
        'two_factor_status' => 'integer', 
        'email_verified_at' => 'datetime',
        'address'           => 'object',
        'status' => 'integer', 
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    public function scopeEmailUnverified($query)
    {
        return $query->where('email_verified', false);
    }

    public function scopeEmailVerified($query) {
        return $query->where("email_verified",true);
    }

    public function scopeKycVerified($query) {
        return $query->where("kyc_verified",GlobalConst::VERIFIED);
    }

    public function scopeKycUnverified($query)
    {
        return $query->whereNot('kyc_verified',GlobalConst::VERIFIED);
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeBanned($query)
    {
        return $query->where('status', false);
    }

    public function kyc()
    {
        return $this->hasOne(UserKycData::class);
    }

    public function getFullnameAttribute()
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function wallets()
    {
        return $this->hasMany(UserWallet::class);
    }
    
    public function getUserImageAttribute() {
        $image = $this->image;

        if($image == null) {
            return files_asset_path('profile-default');
        }else if(filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }else {
            return files_asset_path("user-profile") . "/" . $image;
        }
    }

    public function passwordResets() {
        return $this->hasMany(UserPasswordReset::class,"user_id");
    }

    public function scopeGetSocial($query,$credentials) {
        return $query->where("email",$credentials);
    }

    public function getStringStatusAttribute() {
        $status = $this->status;
        $data = [
            'class' => "",
            'value' => "",
        ];
        if($status == GlobalConst::ACTIVE) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => __("Active"),
            ];
        }else if($status == GlobalConst::BANNED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("Banned"),
            ];
        }
        return (object) $data;
    }

    public function getKycStringStatusAttribute() {
        $status = $this->kyc_verified;
        $data = [
            'class' => "",
            'value' => "",
        ];
        if($status == GlobalConst::APPROVED) {
            $data = [
                'class'     => "badge badge--success",
                'value'     => __("Verified"),
            ];
        }else if($status == GlobalConst::PENDING) {
            $data = [
                'class'     => "badge badge--warning",
                'value'     => __("Pending"),
            ];
        }else if($status == GlobalConst::REJECTED) {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("Rejected"),
            ];
        }else {
            $data = [
                'class'     => "badge badge--danger",
                'value'     => __("Unverified"),
            ];
        }
        return (object) $data;
    }

    public function loginLogs(){
        return $this->hasMany(UserLoginLog::class);
    }

    public function getLastLoginAttribute() {
        if($this->loginLogs()->count() > 0) {
            return $this->loginLogs()->get()->last()->created_at->format("H:i A, d M Y");
        }

        return "N/A";
    }

    public function scopeSearch($query,$data) {
        return $query->where(function($q) use ($data) {
            $q->where("username","like","%".$data."%");
        })->orWhere("email","like","%".$data."%")->orWhere("full_mobile","like","%".$data."%");
    }
    public function modelGuardName() {
        return "web";
    }
}
