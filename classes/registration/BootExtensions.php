<?php

namespace OFFLINE\Mall\Classes\Registration;

use App;
use Backend\Widgets\Form;
use Illuminate\Support\Facades\Event;
use OFFLINE\Mall\Models\Customer;
use OFFLINE\Mall\Models\CustomerGroup;
use OFFLINE\Mall\Models\Tax;
use OFFLINE\Mall\Models\User;
use RainLab\Location\Models\Country as RainLabCountry;
use RainLab\User\Models\User as RainLabUser;

trait BootExtensions
{
    protected function registerExtensions()
    {
        $this->extendRainLabCountry();
        $this->extendRainLabUser();
    }

    protected function extendRainLabCountry()
    {
        RainLabCountry::extend(function ($model) {
            $model->belongsToMany['taxes'] = [
                Tax::class,
                'table'    => 'offline_mall_country_tax',
                'key'      => 'country_id',
                'otherKey' => 'tax_id',
            ];
        });
    }

    protected function extendRainLabUser()
    {
        // Use custom user model
        App::singleton('user.auth', function () {
            return \OFFLINE\Mall\Classes\Customer\AuthManager::instance();
        });

        RainLabUser::extend(function ($model) {
            $model->hasOne['customer']          = Customer::class;
            $model->belongsTo['customer_group'] = [CustomerGroup::class, 'key' => 'offline_mall_customer_group_id'];
            $model->rules['surname'] = 'required';
            $model->rules['name'] = 'required';
        });
        User::extend(function ($model) {
            $model->rules['surname'] = 'required';
            $model->rules['name'] = 'required';
        });

        // Add Customer Groups menu entry to RainLab.User
        Event::listen('backend.menu.extendItems', function ($manager) {
            $manager->addSideMenuItems('RainLab.User', 'user', [
                'users' => [
                    'label'       => 'rainlab.user::lang.users.menu_label',
                    'url'         => \Backend::url('rainlab/user/users'),
                    'icon'        => 'icon-user',
                    'permissions' => ['rainlab.users.*'],
                ],
            ]);

            $manager->addSideMenuItems('RainLab.User', 'user', [
                'customer_groups' => [
                    'label'       => 'offline.mall::lang.common.customer_groups',
                    'url'         => \Backend::url('offline/mall/customergroups'),
                    'icon'        => 'icon-users',
                    'permissions' => ['rainlab.users.*', 'offline.mall.manage_customer_groups'],
                ],
            ]);
        });

        // Add Customer Groups relation to RainLab.User form
        Event::listen('backend.form.extendFields', function (Form $widget) {
            if ( ! $widget->getController() instanceof \RainLab\User\Controllers\Users) {
                return;
            }

            if ( ! $widget->model instanceof \RainLab\User\Models\User) {
                return;
            }

            $widget->addTabFields([
                'customer_group' => [
                    'label'       => trans('offline.mall::lang.common.customer_group'),
                    'type'        => 'relation',
                    'nameFrom'    => 'name',
                    'emptyOption' => trans('offline.mall::lang.common.none'),
                    'tab'         => 'rainlab.user::lang.user.account',
                ],
            ]);
        });
    }
}
